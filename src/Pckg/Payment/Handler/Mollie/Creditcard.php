<?php

namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Creditcard extends Mollie
{
    protected $issuer = 'CREDITCARD';

    protected $handler = 'mollie-creditcard';
}
