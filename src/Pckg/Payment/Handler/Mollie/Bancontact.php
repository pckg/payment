<?php namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Bancontact extends Mollie
{

    protected $issuer = 'BANCONTACT';

    protected $handler = 'mollie-bancontact';

}