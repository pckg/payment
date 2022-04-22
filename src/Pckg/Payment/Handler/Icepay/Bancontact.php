<?php

namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Form\Bancontact as BancontactForm;
use Pckg\Payment\Handler\Icepay;

class Bancontact extends Icepay
{
    protected $paymentMethod = 'MISTERCASH';
    protected $issuer = 'MISTERCASH';
    protected $handler = 'icepay-bancontact';
    public function getIcepayData()
    {
        return [
            'Country' => post('country', null),
        ];
    }

    public function startPartialData()
    {
        $this->startIcepayPartialData(BancontactForm::class, 'icepay-bancontact', ['country']);
        return [];
    }
}
