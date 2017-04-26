<?php namespace Pckg\Payment\Handler\Icepay;

class Visa extends CreditCard
{

    protected $issuer = 'VISA';

    protected $handler = 'icepay-visa';

}