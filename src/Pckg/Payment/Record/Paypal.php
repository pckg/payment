<?php namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Paypal as PaypalEntity;

class Paypal extends Record
{

    protected $entity = PaypalEntity::class;

    public function getUniqueId()
    {
        return $this->paypal_hash;
    }

}