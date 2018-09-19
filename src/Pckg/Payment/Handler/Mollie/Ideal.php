<?php namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Ideal extends Mollie
{

    protected $issuer = 'IDEAL';

    protected $handler = 'mollie-ideal';

}