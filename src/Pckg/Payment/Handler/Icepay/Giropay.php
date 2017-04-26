<?php namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Handler\Icepay;

class Giropay extends Icepay
{

    protected $paymentMethod = 'GIROPAY';

    protected $issuer = 'DEFAULT';

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