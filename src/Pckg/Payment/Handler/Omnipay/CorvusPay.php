<?php namespace Pckg\Payment\Handler\Omnipay;

use Omnipay\CorvusPay\Gateway;

class CorvusPay extends AbstractOmnipay
{

    /**
     * @var string
     */
    protected $gateway = Gateway::class;

    /**
     * @var string
     */
    protected $handler = 'corvus-pay';

    /**
     * @return string[]
     */
    public function getOmnipayConfigKeys()
    {
        return ['storeId', 'apiKey'];
    }

}