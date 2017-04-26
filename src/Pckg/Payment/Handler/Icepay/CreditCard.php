<?php namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Handler\Icepay;

abstract class CreditCard extends Icepay
{

    protected $paymentMethod = 'CREDITCARD';

    public function startPartial()
    {
        $this->postStartPartial();
    }

}