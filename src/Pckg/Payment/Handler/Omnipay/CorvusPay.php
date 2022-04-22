<?php

namespace Pckg\Payment\Handler\Omnipay;

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
     * @return array
     */
    public function getOmnipayConfig()
    {
        $config = parent::getOmnipayConfig();
        $config['language'] = localeManager()->getDefaultFrontendLanguage()->slug ?? 'en';

        return $config;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->environment->config('corvus-pay.url') === 'https://test-wallet.corvuspay.com/';
    }

    public function postSuccess()
    {
        return $this->completePurchase();
    }
}
