<?php namespace Pckg\Payment\Entity;

use Derive\Orders\Entity\Orders;
use Pckg\Database\Entity;
use Pckg\Payment\Record\Payment;

class Payments extends Entity
{

    protected $record = Payment::class;

    public function order()
    {
        return $this->belongsTo(Orders::class)
                    ->foreignKey('order_id');
    }

}