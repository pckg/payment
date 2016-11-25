<?php namespace Pckg\Payment\Handler;

use Braintree_ClientToken;
use Braintree_Configuration;
use Braintree_Transaction;
use Carbon\Carbon;
use Derive\Orders\Record\OrdersBill;
use Derive\Orders\Record\OrdersUser;
use Pckg\Payment\Entity\Braintree as BraintreeEntity;
use Pckg\Payment\Record\Braintree as BraintreeRecord;
use Throwable;

class Braintree extends AbstractHandler implements Handler
{

    protected $braintreeClientToken;

    public function validate($request)
    {
        $rules = [
            'holder'     => 'required',
            'number'     => 'required',
            'exp_month'  => 'required',
            'exp_year'   => 'required',
            'cvc'        => 'required',
            'amount_int' => 'required',
        ];

        if (!$this->environment->validates($request, $rules)) {
            return $this->environment->errorJsonResponse();
        }

        return [
            'success' => true,
        ];
    }

    public function initHandler()
    {
        Braintree_Configuration::environment($this->environment->config('braintree.environment'));
        Braintree_Configuration::merchantId($this->environment->config('braintree.merchant'));
        Braintree_Configuration::publicKey($this->environment->config('braintree.public'));
        Braintree_Configuration::privateKey($this->environment->config('braintree.private'));

        return $this;
    }

    public function getTotal()
    {
        return number_format($this->order->getTotal(), 2, '.', '');
    }

    public function getTotalToPay()
    {
        return number_format($this->order->getTotalToPay(), 2, '.', '');
    }

    public function getBraintreeClientToken()
    {
        return $this->braintreeClientToken;
    }

    public function startPartial()
    {
        try {
            $this->braintreeClientToken = Braintree_ClientToken::generate();

        } catch (Throwable $e) {
            response()->unavailable('Braintree payments are not available at the moment');

        }

        $record = BraintreeRecord::create(
            [
                'order_id'                       => $this->order->getId(),
                'user_id'                        => auth('frontend')->user('id') ?? null,
                'order_hash'                     => $this->order->getIdString(),
                'braintree_hash'                 => sha1(microtime() . $this->order->getIdString()),
                'braintree_client_token'         => $this->braintreeClientToken,
                'state'                          => 'started',
                'data'                           => json_encode(
                    [
                        'billIds' => $this->order->getBills()->map('id'),
                    ]
                ),
                'braintree_payment_method_nonce' => null,
                'braintree_transaction_id'       => null,
                'price'                          => null,
                'error'                          => null,
                'dt_started'                     => Carbon::now(),
                'dt_confirmed'                   => null,
            ]
        );

        return $record;
    }

    public function postStartPartial()
    {
        $payment = (new BraintreeEntity())->where('braintree_hash', router()->get('payment'))->oneOrFail();

        $price = $this->order->getTotal();
        $order = $this->order->getOrder();

        /**
         * @T00D00
         */
        if (false && !$order->getIsConfirmedAttribute()) {
            $order->ordersUser->each(
                function(OrdersUser $ordersUser) {
                    if (!$ordersUser->packet->getAvailableStockAttribute()) {
                        response()->bad('Sold out!');
                    }
                }
            );
        }

        $payment->price = $price;
        $payment->save();

        $braintreeNonce = request()->post('payment_method_nonce');

        if (!$braintreeNonce) {
            response()->bad('Missing payment method nonce.');
        }

        if ($braintreeNonce == $payment->braintree_payment_method_nonce) {
            //User pressed F5. Load existing transaction.
            $result = Braintree_Transaction::find($payment->braintree_transaction_id);

        } else {
            //Create a new transaction
            $transactionSettings = [
                'amount'             => $this->getTotal(),
                'paymentMethodNonce' => $braintreeNonce,
                'options'            => [
                    'submitForSettlement' => true,
                ],
            ];

            /**this was never set in old code
             * if (defined('BRAINTREE_MERCHANT_ACCOUNT_ID') && BRAINTREE_MERCHANT_ACCOUNT_ID) {
             * $transactionSettings['merchantAccountId'] = BRAINTREE_MERCHANT_ACCOUNT_ID;
             * }*/

            $result = Braintree_Transaction::sale($transactionSettings);
        }

        //Check for errors
        if (!$result->success) {
            $payment->set(
                [
                    "state"                          => 'error',
                    "braintree_payment_method_nonce" => $braintreeNonce,
                    "error"                          => json_encode($result),
                ]
            )->save();

            /**
             * @T00D00 - redirect to error page with error $result->message
             */
            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    ['handler' => 'braintree', 'order' => $this->order->getOrder()]
                )
            );
        }

        //If everything went fine, we got a transaction object
        $transaction = $result->transaction;

        //Write what we got to the database
        $payment->set(
            [
                "braintree_transaction_id"       => $transaction->id,
                "braintree_payment_method_nonce" => $braintreeNonce,
                "state"                          => 'BT:' . $transaction->status,
            ]
        );
        $payment->save();

        //SUBMITTED_FOR_SETTLEMENT means it's practically paid
        if ($transaction->status == Braintree_Transaction::SUBMITTED_FOR_SETTLEMENT) {
            $this->order->getBills()->each(
                function(OrdersBill $ordersBill) use ($transaction) {
                    $ordersBill->confirm(
                        "Braintree #" . $transaction->id,
                        'braintree'
                    );
                }
            );

            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.success',
                    ['handler' => 'braintree', 'order' => $this->order->getOrder()]
                )
            );

        } else if ($transaction->status == Braintree_Transaction::PROCESSOR_DECLINED) {
            $payment->set(
                [
                    "state" => 'BT:' . $transaction->status,
                    "error" => print_r(
                        [
                            "processorResponseCode"       => $transaction->processorResponseCode,
                            "processorResponseText"       => $transaction->processorResponseText,
                            "additionalProcessorResponse" => $transaction->additionalProcessorResponse,
                        ],
                        true
                    ),
                ]
            );
            $payment->save();

            /**
             * @T00D00 - redirect to error page with error $transaction->processorResponseText
             */
            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    ['handler' => 'braintree', 'order' => $this->order->getOrder()]
                )
            );

        } else if ($transaction->status == Braintree_Transaction::GATEWAY_REJECTED) {
            $payment->set(
                [
                    "state" => 'BT:' . $transaction->status,
                    "error" => print_r(
                        ["gatewayRejectionReason" => $transaction->gatewayRejectionReason],
                        true
                    ),
                ]
            );
            $payment->save();

            /**
             * @T00D00 - redirect to error page with error $transaction->gatewayRejectionReason
             */
            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    ['handler' => 'braintree', 'order' => $this->order->getOrder()]
                )
            );

        } else {
            /**
             * @T00D00 - redirect to error page with error 'Unknown payment error'
             */
            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    ['handler' => 'braintree', 'order' => $this->order->getOrder()]
                )
            );
        }
    }

    public function success()
    {

    }

    public function error()
    {

    }

    public function waiting()
    {

    }

    public function getValidateUrl()
    {
        return $this->environment->url(
            'payment.validate',
            ['handler' => 'paymill', 'order' => $this->order->getOrder()]
        );
    }

    public function getStartUrl()
    {
        return $this->environment->url(
            'payment.start',
            ['handler' => 'braintree', 'order' => $this->order->getOrder()]
        );
    }

}