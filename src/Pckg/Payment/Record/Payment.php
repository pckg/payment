<?php namespace Pckg\Payment\Record;

use Carbon\Carbon;
use Pckg\Database\Record;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Entity\Payments;

class Payment extends Record
{

    protected $entity = Payments::class;

    public static function createForOrderAndMethod(Order $order, $handler, $method, $data)
    {
        $data = [
            'order_id'   => $order->getId(),
            'created_at' => Carbon::now(),
            'data'       => json_encode($data),
            'price'      => $order->getTotal(),
            'handler'    => $handler,
            'method'     => $method,
            'status'     => 'created',
        ];

        $data['hash'] = sha1(json_encode($data) . config('hash'));

        return static::create($data);
    }

    public function addLog($status, $log)
    {
        return PaymentLog::create(
            [
                'payment_id' => $this->id,
                'created_at' => Carbon::now(),
                'status'     => $status,
                'data'       => json_encode($log),
            ]
        );
    }

    public function getUniqueId()
    {
        return $this->hash;
    }

}