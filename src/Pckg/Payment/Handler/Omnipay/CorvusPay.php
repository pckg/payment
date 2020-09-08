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
     * @var bool
     */
    protected $startOnInit = true;

    /**
     * @return string[]
     */
    public function getOmnipayConfigKeys()
    {
        return ['storeId', 'apiKey'];
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->environment->config('corvus-pay.url') === 'https://test-wallet.corvuspay.com/';
    }

}