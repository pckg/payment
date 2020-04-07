<?php namespace Pckg\Payment\Entity;

use Derive\Orders\Entity\Orders;
use Derive\Orders\Entity\OrdersBills;
use Pckg\Database\Entity;
use Pckg\Database\Relation\BelongsTo;
use Pckg\Database\Relation\HasMany;
use Pckg\Payment\Record\Payment;

class Payments extends Entity
{

    protected $record = Payment::class;

    public function order()
    {
        return $this->belongsTo(Orders::class)->foreignKey('order_id');
    }

    public function instalments()
    {
        return $this->hasMany(OrdersBills::class)
            ->primaryKey('JSON_EXTRACT(payments.data, "$.billIds")')
            ->foreignKey('id'); // JSON_CONTAINS()
    }

    public function logs()
    {
        return $this->hasMany(PaymentLogs::class)->foreignKey('payment_id');
    }

    public function paymentsMorphs()
    {
        return $this->hasMany(PaymentsMorphs::class)->foreignKey('payment_id');
    }

    public function forPaymentManager()
    {
        return $this->withInstalments(function (HasMany $instalments) {
            $instalments->withOrder(function (BelongsTo $order) {
                $order->withOrdersBills();
            });
        });
    }

}