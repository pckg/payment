<?php namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Moneta as MonetaEntity;
use Pckg\Payment\Entity\Paypal as PaypalEntity;

class Moneta extends Record
{

    protected $entity = MonetaEntity::class;

    public function getUniqueId()
    {
        return $this->paypal_hash;
    }

}