<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Payment\Record\Braintree as BraintreeRecord;

class Braintree extends Entity
{

    protected $record = BraintreeRecord::class;

}