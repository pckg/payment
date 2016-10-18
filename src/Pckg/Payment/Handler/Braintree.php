<?php namespace Pckg\Payment\Handler;

use Braintree_ClientToken;
use Braintree_Configuration;
use Exception;
use Paymill\Models\Request\Transaction;
use Pckg\Payment\Record\Payment as PaymentRecord;

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
        $this->config = [
            'environment' => $this->environment->config('braintree.environment'),
            'merchant'    => $this->environment->config('braintree.merchant'),
            'public'      => $this->environment->config('braintree.public'),
            'private'     => $this->environment->config('braintree.private'),
        ];
        
        Braintree_Configuration::environment($this->environment->config('braintree.environment'));
        Braintree_Configuration::merchantId($this->environment->config('braintree.merchant'));
        Braintree_Configuration::publicKey($this->environment->config('braintree.public'));
        Braintree_Configuration::privateKey($this->environment->config('braintree.private'));

        return $this;
    }

    public function getTotal()
    {
        return round($this->order->getTotal() * 100);
    }

    public function getTotalToPay()
    {
        return round($this->order->getTotalToPay() * 100);
    }

    public function getPublicKey()
    {
        return $this->config['public_key'];
    }

    public function getBraintreeClientToken()
    {
        return $this->braintreeClientToken;
    }

    public function start()
    {
        $btPaymentHash = sha1(microtime() . $this->order->getIdString());
        $this->braintreeClientToken = Braintree_ClientToken::generate();

        $billIds = [];
        foreach (explode(',', $_GET['ids']) as $billId) {
            $billIds[] = (int)trim($billId);
        }

        if (false) {
            PaymentRecord::create(
                [
                    'order_id'               => $this->order->getId(),
                    'user_id'                => auth()->getUser()->id ?? null,
                    'order_hash'             => $this->order->getIdString(),
                    'braintree_hash'         => $btPaymentHash,
                    'braintree_client_token' => $this->braintreeClientToken,
                    'state'                  => 'started',
                    'data'                   => json_encode(
                        [
                            'billIds' => $billIds,
                        ]
                    ),
                ]
            );
        }

        $confirmPaymentUrl = url(
            'derive.payment.confirm',
            ['handler' => 'braintree', 'order' => $this->order->getOrder()]
        );

        return;

        return new TwigTpl(
            "modules/braintree/templates/startpayment.twig", [
                                                               "price"                => $makePrice,
                                                               "confirmPaymentUrl"    => $confirmPaymentUrl,
                                                               "braintreeClientToken" => $braintreeClientToken,
                                                               "paymenttable"         => $this->OffersPaymentMethods->getPaymentTable(
                                                                   $rOffer['id']
                                                               ),
                                                               "steps"                => $this->settings['skip'],
                                                           ]
        );
        dd('initialize start ...');
    }

    protected function makeTransaction($paymentId)
    {
        $transaction = new Transaction();
        $transaction->setAmount($this->getTotalToPay())
                    ->setCurrency($this->order->getCurrency())
                    ->setPayment($paymentId)
                    ->setDescription($this->order->getDescription());

        $response = null;
        try {
            $this->log($transaction);
            $response = $this->paymill->create($transaction);
            $this->log($response);
        } catch (Exception $e) {
            $this->log($e);
            throw $e;

        } finally {
            if ($response->getStatus() == 'closed') {
                $this->order->setPaid();

                return true;
            }

        }
    }

    protected function handleTransactionResponse($response)
    {
        if ($response->getStatus() == 'closed') {
            $this->order->setPaid();
        }
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