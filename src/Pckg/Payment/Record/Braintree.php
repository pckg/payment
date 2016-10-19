<?php namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Braintree as BraintreeEntity;

class Braintree extends Record
{

    protected $entity = BraintreeEntity::class;

    public function getUniqueId()
    {
        return $this->braintree_hash;
    }

}