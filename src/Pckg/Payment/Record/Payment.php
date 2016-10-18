<?php namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Payments;

class Payment extends Record
{

    protected $entity = Payments::class;

}