<?php namespace Pckg\Payment\Record;

use Carbon\Carbon;
use Derive\Orders\Entity\OrdersBills;
use Pckg\Collection;
use Pckg\Database\Record;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Entity\Payments;

class Payment extends Record
{

    protected $entity = Payments::class;

    public static function createForOrderAndHandler(Order $order, $handler, $data)
    {
        $data = [
            'order_id'   => $order->getId(),
            'user_id'    => auth('frontend')->user('id'),
            'data'       => json_encode($data),
            'price'      => $order->getTotal(),
            'handler'    => $handler,
            'status'     => 'created',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $data['hash'] = sha1(json_encode($data) . config('hash') . microtime());

        return static::create($data);
    }

    public static function createForInstalments(Collection $instalments, $handler)
    {
        $data = [
            'user_id'    => auth('frontend')->user('id'),
            'data'       => json_encode(['billIds' => $instalments->map('id')->toArray()]),
            'price'      => $instalments->sum('price'),
            'handler'    => $handler,
            'status'     => 'created',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $data['hash'] = sha1(json_encode($data) . config('hash') . microtime());

        return static::create($data);
    }

    public function getBills()
    {
        return (new OrdersBills())->where('id', json_decode($this->data('data'))->billIds)->all();
    }

    public function getJsonData($key)
    {
        return json_decode($this->data('data'))->{$key} ?? null;
    }

    public function setJsonData($key, $val)
    {
        $data = json_decode($this->data('data'), true);
        if (!is_array($data)) {
            $data = [];
        }
        $data[$key] = $val;
        $this->set('data', json_encode($data));

        return $this;
    }

    public function addLog($status, $log = null)
    {
        return PaymentLog::create([
                                      'payment_id' => $this->id,
                                      'created_at' => Carbon::now(),
                                      'status'     => $status,
                                      'data'       => json_encode($log),
                                  ]);
    }

    public function getUniqueId()
    {
        return $this->hash;
    }

    public function applyCompanyConfig()
    {
        $instalments = $this->getBills();
        $instalments->first()->order->applyCompanyConfig();
    }

    public function redirectToSummaryIfNotPayable()
    {

    }

    public function redirectToSummaryIfOverbooked()
    {

    }

    public function getDescription()
    {
        $instalments = $this->getBills();

        return __('order_payment') . '#' . $instalments->map('order')->map(function(\Derive\Orders\Record\Order $order
            ) {
                return '#' . $order->id . '(' . $order->num . ')';
            })->implode(',') . ' - ' . $instalments->map('id')->implode(',') . ')';
    }

    public function addGtm()
    {
        
    }

}