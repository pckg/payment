<?php namespace Pckg\Payment\Handler;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Transaction;
use Derive\Orders\Record\OrdersBill;
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
        Configuration::environment($this->environment->config('braintree.environment'));
        Configuration::merchantId($this->environment->config('braintree.merchant'));
        Configuration::publicKey($this->environment->config('braintree.public'));
        Configuration::privateKey($this->environment->config('braintree.private'));

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
            $this->braintreeClientToken = ClientToken::generate();
        } catch (Throwable $e) {
            response()->unavailable('Braintree payments are not available at the moment: ' . $e->getMessage());
        }

        $this->paymentRecord->addLog('created', $this->braintreeClientToken);
    }

    public function postStartPartial()
    {
        $braintreeNonce = request()->post('payment_method_nonce');

        if (!$braintreeNonce) {
            response()->bad('Missing payment method nonce.');
        }

        $this->getPaymentRecord()->addLog('submitted');

        $result = $braintreeNonce == $this->paymentRecord->getJsonData('braintree_payment_method_nonce')
            ? Transaction::find($this->paymentRecord->transaction_id)
            : Transaction::sale([
                                    'amount'             => $this->getTotal(),
                                    'paymentMethodNonce' => $braintreeNonce,
                                    'options'            => [
                                        'submitForSettlement' => true,
                                    ],
                                ]);

        $this->paymentRecord->setJsonData('braintree_payment_method_nonce', $braintreeNonce)->save();

        //Check for errors
        if (!$result->success) {
            $this->paymentRecord->setAndSave([
                                                 'status' => 'error',
                                             ]);
            $this->paymentRecord->addLog('error', $result);

            /**
             * @T00D00 - redirect to error page with error $result->message
             */
            $this->environment->flash(
                'pckg.payment.order.' . $this->order->getId() . '.error',
                $result->message
            );
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
        $this->paymentRecord->setAndSave([
                                             'transaction_id' => $transaction->id,
                                             'status'         => $transaction->status,
                                         ]);

        //SUBMITTED_FOR_SETTLEMENT means it's practically paid
        if ($transaction->status == Transaction::SUBMITTED_FOR_SETTLEMENT) {
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

            return;
        }

        $this->paymentRecord->addLog($transaction->status, $transaction);

        if ($transaction->status == Transaction::PROCESSOR_DECLINED) {
            $this->environment->flash(
                'pckg.payment.order.' . $this->order->getId() . '.error',
                $transaction->processorResponseText
            );

            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    [
                        'handler' => 'braintree',
                        'order'   => $this->order->getId(),
                    ]
                )
            );
        } else if ($transaction->status == Transaction::GATEWAY_REJECTED) {
            $this->environment->flash(
                'pckg.payment.order.' . $this->order->getId() . '.error',
                $transaction->gatewayRejectionReason
            );

            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    ['handler' => 'braintree', 'order' => $this->order->getOrder()]
                )
            );
        }

        $this->environment->flash(
            'pckg.payment.order.' . $this->order->getId() . '.error',
            'Unknown payment'
        );

        $this->environment->redirect(
            $this->environment->url(
                'derive.payment.error',
                ['handler' => 'braintree', 'order' => $this->order->getOrder()]
            )
        );
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