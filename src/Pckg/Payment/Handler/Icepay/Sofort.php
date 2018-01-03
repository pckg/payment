<?php namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Form\Sofort as SofortForm;
use Pckg\Payment\Handler\Icepay;

class Sofort extends Icepay
{

    protected $paymentMethod = 'SOFORT';

    protected $issuer = 'DEFAULT';

    protected $handler = 'icepay-sofort';

    public function startPartial()
    {
        $this->postStartPartial();
    }

    public function getIcepayData()
    {
        return [
            'Language' => 'DE',
            'Country'  => 'DE',
        ];
    }

}