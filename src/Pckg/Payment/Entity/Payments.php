<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Payment\Record\Payment;

class Payments extends Entity
{

    protected $record = Payment::class;

}