<?php namespace Pckg\Payment\Entity;

use Derive\Orders\Entity\Orders;
use Derive\Orders\Entity\OrdersBills;
use Pckg\Database\Entity;
use Pckg\Database\Relation\BelongsTo;
use Pckg\Database\Relation\HasMany;
use Pckg\Payment\Record\Payment;
use Pckg\Payment\Record\PaymentsMorph;

class Payments extends Entity
{

    protected $record = Payment::class;

    public function order()
    {
        return $this->belongsTo(Orders::class)->foreignKey('order_id');
    }

    /**
     * Some old orders do not have that connection?
     * Or do we have a new connection?
     * 
     * @return HasMany
     */
    public function instalments()
    {
        return $this->hasManyIn(OrdersBills::class)
            ->primaryKey(function () {
                return [
                    /**
                     * When used in PHP.
                     */
                    function (Payment $payment) {
                        return json_decode($payment->data)->billIds ?? [];
                    },
                    /**
                     * Actual key.
                     */
                    'id',
                    /**
                     * When used in MySQL?
                     */
                    // 'JSON_CONTAINS(payments.data, CAST(orders_bills.id as JSON), \'$.billIds\')',
                    // 'JSON_UNQUOTE(JSON_EXTRACT(payments.data, \'$.billIds\')) = orders_bills.id',
                    /**
                     * When used in belongsToManyIn?
                     */
                    // 'JSON_EXTRACT(payments.data, "$.billIds")',
                ];
            })
            ->foreignKey('id');
    }

    /**
     * @return HasMany
     */
    public function instalmentsOverMorph()
    {
        return $this->hasMany(PaymentsMorph::class);
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