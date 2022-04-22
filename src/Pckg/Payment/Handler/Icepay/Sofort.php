<?php

namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Form\Sofort as SofortForm;
use Pckg\Payment\Handler\Icepay;

class Sofort extends Icepay
{
    protected $paymentMethod = 'DIRECTEBANK';
    protected $issuer = 'DIGITAL';
    protected $handler = 'icepay-sofort';
    public function getIcepayData()
    {
        return [
            'Country' => post('country', null),
        ];
    }

    public function startPartialData()
    {
        $this->startIcepayPartialData(SofortForm::class, 'icepay-sofort', ['country']);
        return [];
    }
}
