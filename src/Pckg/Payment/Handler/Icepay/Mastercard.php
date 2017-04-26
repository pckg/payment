<?php namespace Pckg\Payment\Handler\Icepay;

class Mastercard extends CreditCard
{

    protected $issuer = 'MASTER';

    protected $handler = 'icepay-mastercard';

}