<?php

namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Form\Ideal as IdealForm;
use Pckg\Payment\Handler\Icepay;

class Ideal extends Icepay
{
    protected $paymentMethod = 'IDEAL';
    protected $issuer = 'DEFAULT';
    protected $handler = 'icepay-ideal';
    public function getIcepayData()
    {
        return [
            'Country' => 'NL',
            'Issuer'  => post('issuer', null),
        ];
    }

    public function startPartialData()
    {
        $this->startIcepayPartialData(IdealForm::class, 'icepay-ideal', ['issuer']);
        return [];
    }
}
