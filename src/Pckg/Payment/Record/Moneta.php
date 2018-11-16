<?php namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Moneta as MonetaEntity;

class Moneta extends Record
{

    protected $entity = MonetaEntity::class;

    public function getUniqueId()
    {
        return $this->paypal_hash;
    }

}