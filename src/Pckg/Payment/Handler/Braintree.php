<?php namespace Pckg\Payment\Handler;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Transaction;
use Throwable;

class Braintree extends AbstractHandler implements Handler
{

    protected $braintreeClientToken;

    protected $handler = 'braintree';

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

        $result = $braintreeNonce == $this->paymentRecord->getJsonData('braintree_payment_method_nonce') ? Transaction::find($this->paymentRecord->transaction_id) : Transaction::sale([
                                                                                                                                                                                           'amount'             => $this->getTotal(),
                                                                                                                                                                                           'paymentMethodNonce' => $braintreeNonce,
                                                                                                                                                                                           'options'            => [
                                                                                                                                                                                               'submitForSettlement' => true,
                                                                                                                                                                                           ],
                                                                                                                                                                                       ]);

        $this->paymentRecord->setJsonData('braintree_payment_method_nonce', $braintreeNonce)->save();

        /**
         * @T00D00 - redirect to error page with error $result->message
         */
        if (!$result->success) {
            $this->errorPayment($result);

            $this->environment->flash('pckg.payment.order.' . $this->order->getId() . '.error', $result->message);
            $this->environment->redirect($this->getErrorUrl());
        }

        /**
         * If everything went fine, we got a transaction object.
         * Confirm payment when its submitted for settlement.
         */
        $transaction = $result->transaction;
        if ($transaction->status == Transaction::SUBMITTED_FOR_SETTLEMENT) {
            $this->approvePayment("Braintree #" . $transaction->id, $result, $transaction->id, $transaction->status);
            $this->environment->redirect($this->getSuccessUrl());

            return;
        }

        $this->errorPayment($transaction, $transaction->status);

        $flash = 'Unknown payment error';
        if ($transaction->status == Transaction::PROCESSOR_DECLINED) {
            $flash = $transaction->processorResponseText;
        } elseif ($transaction->status == Transaction::GATEWAY_REJECTED) {
            $flash = $transaction->gatewayRejectionReason;
        }

        $this->environment->flash('pckg.payment.order.' . $this->order->getId() . '.error', $flash);

        $this->environment->redirect($this->getErrorUrl());
    }

}