<?php

namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Eps extends Mollie
{
    protected $issuer = 'EPS';

    protected $handler = 'mollie-eps';
}
