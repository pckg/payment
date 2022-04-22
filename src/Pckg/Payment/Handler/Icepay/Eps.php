<?php

namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Handler\Icepay;

class Eps extends Icepay
{
    protected $paymentMethod = 'EPS';
    protected $issuer = 'DEFAULT';
    protected $handler = 'icepay-eps';
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
