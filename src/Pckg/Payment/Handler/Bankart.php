<?php namespace Pckg\Payment\Handler;

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
        return ['apiUsername', 'apiPassword', 'apiKey', 'sharedSecret', 'publicIntegrationKey'];
    }

}