<?php namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Sofort extends Mollie
{

    protected $issuer = 'SOFORT';

    protected $handler = 'mollie-sofort';

}