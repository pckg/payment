<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Payment\Record\PaymentsMorph;

class PaymentsMorphs extends Entity
{

    protected $record = PaymentsMorph::class;

    public function payment()
    {
        return $this->belongsTo(Payments::class)->foreignKey('payment_id');
    }

}