<?php

namespace Pckg\Payment\Handler\Icepay;

class Americanexpress extends CreditCard
{
    protected $issuer = 'AMEX';
    protected $handler = 'icepay-americanexpress';
}
