<?php namespace Pckg\Payment\Handler;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Transaction;
use Throwable;

/**
 * Class Braintree
 *
 * @package Pckg\Payment\Handler
 */
class Braintree extends AbstractHandler implements Handler
{

    /**
     * @var string
     */
    protected $handler = 'braintree';

    /**
     * @return $this|AbstractHandler
     */
    public function initHandler()
    {
        Configuration::environment($this->environment->config('braintree.environment'));
        Configuration::merchantId($this->environment->config('braintree.merchant'));
        Configuration::publicKey($this->environment->config('braintree.public'));
        Configuration::privateKey($this->environment->config('braintree.private'));

        return $this;
    }

    /**
     * @return array|AbstractHandler
     */
    public function initPayment()
    {
        $token = null;
        try {
            $token = ClientToken::generate();
        } catch (Throwable $e) {
            response()->unavailable('Braintree payments are not available at the moment: ' . $e->getMessage());
        }

        $this->paymentRecord->addLog('created', $token);

        return [
            'token' => $token,
        ];
    }

    /**
     * @return array|void
     */
    public function postStart()
    {
        $braintreeNonce = request()->post('payment_method_nonce');

        if (!$braintreeNonce) {
            return [
                'success' => false,
                'message' => 'Missing payment method nonce.',
            ];
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
         * No success.
         */
        if (!$result->success) {
            $this->errorPayment($result);

            return [
                'success' => false,
                'message' => $result->message,
                'modal'   => 'error',
            ];
        }

        /**
         * If everything went fine, we got a transaction object.
         * Confirm payment when its submitted for settlement.
         */
        $transaction = $result->transaction;
        if ($transaction->status == Transaction::SUBMITTED_FOR_SETTLEMENT) {
            $this->approvePayment("Braintree #" . $transaction->id, $result, $transaction->id);

            return [
                'success' => true,
                'modal'   => 'success',
            ];
        }

        $this->errorPayment($transaction, $transaction->status);

        $message = 'Unknown payment error';
        if ($transaction->status == Transaction::PROCESSOR_DECLINED) {
            $message = $transaction->processorResponseText;
        } elseif ($transaction->status == Transaction::GATEWAY_REJECTED) {
            $message = $transaction->gatewayRejectionReason;
        }

        return [
            'success' => false,
            'message' => $message,
            'modal'   => 'error',
        ];
    }

}