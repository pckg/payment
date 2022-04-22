<?php

namespace Pckg\Payment\Handler;

use Ampeco\OmnipayBankart\Gateway;
use Braintree\Configuration;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Omnipay;
use PaymentGateway\Client\Client;
use PaymentGateway\Client\Data\Customer;
use PaymentGateway\Client\Transaction\Debit;
use PaymentGateway\Client\Transaction\Result;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Handler\Omnipay\AbstractOmnipay;
use Pckg\Payment\Record\Payment;
use Throwable;

class Bankart extends AbstractOmnipay implements Handler
{
    /**
     * @var string
     */
    protected $handler = 'bankart';

    /**
     * @var string
     */
    protected $gateway = Gateway::class;

    /**
     * @return array|string[]
     */
    public function getOmnipayConfigKeys()
    {
        return [
            'apiUsername' => 'username',
            'apiPassword' => 'password',
            'apiKey',
            'sharedSecret',
            'publicIntegrationKey',
        ];
    }

    public function initPayment()
    {
        return [
            'creditedInstalments' => (int)$this->environment->config($this->handler . '.maxInstalments'),
        ];
    }

    /**
     * @return array
     */
    public function enrichOmnipayOrderDetails($data = [])
    {
        /**
         * If you have an agreement with your acquiring banks to offer payments in installments,
         * userField1 is used and becomes mandatory. In such cases send 00 or 01 when no installments are selected.
         * In case of an invalid value, the payment will be declined.
         *
         * This only works for actual payments with instalments where the merchant recives whole payment
         * upfront and the credit card issuer credits the customer. System will mark order as it was paid with single
         * instalment.
         *
         * The customer needs to select number of instalments upfront.
         */
        if (post('bankartInstalments')) {
            $data['extra_data']['userField1'] = str_pad(post('bankartInstalments'), 2, '0', STR_PAD_LEFT);
        }

        return $data;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->environment->config($this->handler . '.url') === 'https://bankart.paymentsandbox.cloud/';
    }
}
