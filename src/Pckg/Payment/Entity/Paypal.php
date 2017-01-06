<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Database\Repository;
use Pckg\Payment\Record\Paypal as PaypalRecord;

class Paypal extends Entity
{

    protected $record = PaypalRecord::class;

}