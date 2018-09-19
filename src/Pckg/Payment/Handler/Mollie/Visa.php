<?php namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Visa extends Mollie
{

    protected $issuer = 'VISA';

    protected $handler = 'mollie-visa';

}