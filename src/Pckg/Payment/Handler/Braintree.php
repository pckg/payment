<?php

namespace Pckg\Payment\Handler;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Gateway;
use Braintree\Transaction;
use Pckg\Payment\Record\Address;
use Pckg\Payment\Record\Payment;
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
            'threeDSecure' => $this->transform3DS(),
        ];
    }

    /**
     * @param $string
     * @return string|string[]|null
     * https://stackoverflow.com/questions/1176904/php-how-to-remove-all-non-printable-characters-in-a-string
     */
    public function cleanASCII($string)
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    }

    public function transform3DS()
    {
        return [
            'amount' => $this->getTotal(),
            'email' => $this->order->getCustomer()->getEmail(),
            'billingAddress' => $this->transformBillingAddress($this->order->getBillingOrDeliveryAddress()),
            'additionalInformation' => $this->transformAdditionalInformation(),
        ];
    }

    public function transformBillingAddress(Address $address = null)
    {
        if (!$address) {
            return [];
        }

        return [
                'givenName' => $this->cleanASCII($address->name),
                'surname' => $this->cleanASCII(''), // @T00D00
                'phoneNumber' => $address->phone,
            ] + $this->transformAddress($address);
    }

    public function transformAdditionalInformation()
    {
        $deliveryAddress = $this->order->getDeliveryAddress();
        if (!$deliveryAddress) {
            return [];
        }

        return [
            'workPhoneNumber' => '',
            'shippingGivenName' => $this->cleanASCII($deliveryAddress->name),
            'shippingSurname' => '',
            'shippingPhone' => $deliveryAddress->phone,
            'shippingAddress' => $deliveryAddress ? $this->transformAddress($deliveryAddress) : [],
        ];
    }

    public function transformAddress(Address $address)
    {
        return [
            'streetAddress' => $address->address_line1,
            'extendedAddress' => $address->address_line2,
            'locality' => $address->city,
            'region' => '', // @T00D00 - some countries have states/regions/provinces
            'postalCode' => $address->postal,
            'countryCodeAlpha2' => $address->country ? $address->country->getISO2() : '',
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
            'amount' => $this->getTotal(),
            'paymentMethodNonce' => $braintreeNonce,
            'options' => [
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
                'modal' => 'error',
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
                'modal' => 'success',
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
            'modal' => 'error',
        ];
    }

    public function refund(Payment $payment, $amount = null)
    {
        $refundPaymentRecord = Payment::createForRefund($payment, $amount);
        try {
            $result = Configuration::gateway()->transaction()->refund($payment->transaction_id, $amount);
            if ($result->success) {
                $this->paymentRecord = $refundPaymentRecord;
                $this->approveRefund('Refund Braintree #' . $result->transaction->id, $result, $result->transaction->id);
                return [
                    'success' => true,
                ];
            }

            $refundPaymentRecord->addLog('response:failed', $result);
        } catch (Throwable $e) {
            $refundPaymentRecord->addLog('response:exception');
            return [
                'success' => false,
                'message' => 'Refunds are not available at the moment.' . exception($e),
            ];
        }

        return [
            'success' => false,
            'message' => 'Refunds are not available at the moment',
        ];
    }
}
