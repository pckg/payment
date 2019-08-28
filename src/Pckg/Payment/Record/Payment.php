<?php namespace Pckg\Payment\Record;

use Carbon\Carbon;
use Derive\Orders\Entity\OrdersBills;
use Derive\Orders\Record\OrdersBill;
use Pckg\Collection;
use Pckg\Concept\Reflect;
use Pckg\Database\Record;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Entity\Payments;
use Pckg\Payment\Handler\AbstractHandler;

class Payment extends Record
{

    protected $entity = Payments::class;

    public static function createForRefund(Payment $payment, $amount = null)
    {
        $data = [
            'order_id'   => $payment->getBills()->first()->order_id,
            'user_id'    => auth('frontend')->user('id'),
            'price'      => $amount,
            'handler'    => $payment->handler,
            'status'     => 'created:refund',
            'created_at' => date('Y-m-d H:i:s'),
            'hash'       => sha1($amount . config('hash') . microtime()),
        ];

        return static::create($data);
    }

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
            'hash'       => sha1(json_encode($data) . config('hash') . microtime()),
        ];

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

        /**
         * Let's generate payment ids unique between platforms.
         */
        $id = config('identifier');
        $string = $id  . ':' .json_encode($data) . ':' . config('hash') . ':' . uniqid();
        $data['hash'] = substr($id . sha1($string), 0, 40);

        $payment = static::create($data);

        return $payment;
    }

    public function getBills()
    {
        $data = $this->data('data');

        if (!$data) {
            return collect([]);
        }

        $decoded = json_decode($data);

        if (!$decoded) {
            return collect([]);
        }

        $billIds = $decoded->billIds;

        if (!$billIds) {
            return collect([]);
        }

        return (new OrdersBills())->where('id', $billIds)->all();
    }

    public function getOrdersAttribute()
    {
        return $this->getBills()->map('order')->keyBy('id')->rekey();
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

    public function getFinalTransactionIdAttribute()
    {
        if ($this->transaction_id && $this->payment_id && $this->transaction_id != $this->payment_id) {
            return $this->transaction_id;
        }

        $log = $this->logs->first(function(PaymentLog $paymentLog){
            return in_array($paymentLog->status, ['approved', 'payed']);
        });

        if (!$log) {
            $instalments = $this->getBills()->filter(function(OrdersBill $ordersBill) {
                return strpos($ordersBill->notes, 'Paypal ') !== false;
            });

            if ($instalments->count() > 0) {
                $notes = $instalments->first()->notes;
                $transactionStart = strpos($notes, 'Paypal ') + strlen('Paypal ');
                $transactionEndSpace = strpos($notes, " ", $transactionStart);
                $transactionEndLine = strpos($notes, "\n", $transactionStart);

                if (!$transactionEndSpace && !$transactionEndLine) {
                    return substr($notes, $transactionStart);
                }
                
                $length = ($transactionEndSpace && $transactionEndLine
                    ? ($transactionEndSpace < $transactionEndSpace ? $transactionEndSpace : $transactionEndLine)
                    : ($transactionEndSpace ? $transactionEndSpace : $transactionEndLine)) - $transactionStart;

                $transactionId = substr($notes, $transactionStart, $length);
                
                return $transactionId;
            }

            return null;
        }

        $data = json_decode($log->data('data'));

        return $data->transactions[0]->related_resources[0]->sale->id ?? null;
    }

    /**
     * @return AbstractHandler
     * @throws \Exception
     */
    public function getHandler()
    {
        $handlerClass = $this->handler;
        if (!$handlerClass) {
            return null;
        }

        return Reflect::create($handlerClass);
    }

}