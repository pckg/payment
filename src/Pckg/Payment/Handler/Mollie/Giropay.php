<?php

namespace Pckg\Payment\Handler\Mollie;

use Pckg\Payment\Handler\Mollie;

class Giropay extends Mollie
{
    protected $issuer = 'GIROPAY';

    protected $handler = 'mollie-giropay';
}
