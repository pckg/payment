<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Adapter\Log;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Record\Payment;

abstract class AbstractHandler implements Handler
{

    protected $config = [];

    protected $order;

    protected $log;

    /**
     * @var Payment
     */
    protected $paymentRecord;

    /**
     * @var Environment
     */
    protected $environment;

    public function __construct(Order $order = null)
    {
        $this->order = $order;
    }

    public function setPaymentRecord($record)
    {
        $this->paymentRecord = $record;

        return $this;
    }

    public function initHandler()
    {
        return $this;
    }

    public function initPayment()
    {
        return $this;
    }

    public function setLogger(Log $log)
    {
        $this->log = $log;

        return $this;
    }

    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function log($data)
    {
        $this->log->log($data);
    }

    public function getStart()
    {
    }

    public function getInfo()
    {
    }

    public function check()
    {
    }

    public function postStart()
    {
    }

    public function getPaymentRecord()
    {
        return $this->paymentRecord;
    }

    public function success()
    {
    }

    public function error()
    {
    }

    public function waiting()
    {
    }

    public function postNotification()
    {
    }

    public function getNotification()
    {
        return 'GET notification is not supported.';
    }

    public function cancel()
    {
    }

    public function getDownload()
    {

    }

    public function postDownload()
    {

    }

    public function postUploadFile()
    {

    }

    public function refund(Payment $payment, $amount = null)
    {
    }

    public function getTotal()
    {
        return number_format($this->order->getTotal(), 2, '.', '');
    }

    public function getTotalToPay()
    {
        return number_format($this->order->getTotalToPay(), 2, '.', '');
    }

    public function getDescription()
    {
        return $this->paymentRecord->getDescription();
    }

    public function setPaymentId($paymentId)
    {
        $this->paymentRecord->setAndSave(['payment_id' => $paymentId]);
    }

    public function waitPayment($description, $log, $transactionId, $status = 'waiting')
    {
        $this->paymentRecord->addLog($status, $log);

        $this->order->getBills()->keyBy('order_id')->each(function(OrdersBill $ordersBill) use ($description) {
            $order = $ordersBill->order;
            /**
             * Payment confirms order so stock is okay.
             */
            $order->confirm();
            /**
             * Payment status is set as waiting.
             */
            $order->waitingForPayment();
        });

        $this->paymentRecord->setAndSave([
                                             'status'         => $status,
                                             'transaction_id' => $transactionId,
                                         ]);
    }

    public function approvePayment($description, $log, $transactionId, $status = 'approved')
    {
        $this->paymentRecord->addLog($status, $log);

        $this->order->getBills()->each(function(OrdersBill $ordersBill) use ($description) {
            $ordersBill->confirm($description);
        });

        $this->paymentRecord->setAndSave([
                                             'status'         => $status,
                                             'transaction_id' => $transactionId,
                                         ]);
    }
    
    public function approveRefund($description, $log, $transactionId)
    {
        $this->paymentRecord->addLog('completed', $log);

        $this->paymentRecord->setAndSave(['status' => 'refund', 'transaction_id' => $json->id]);

        $instalments = $this->paymentRecord->getBills();
        $order = $instalments->first()->order();

        OrdersBill::create([
            'order_id'     => $order->id,
            'dt_added'     => date('Y-m-d H:i:s'),
            'dt_confirmed' => date('Y-m-d H:i:s'),
            'dt_valid'     => date('Y-m-d H:i:s'),
            'type'         => 'refund',
            'price'        => $amount,
            'payed'        => $amount,
            'notes'        => $description,
        ]);    
    }

    public function errorPayment($data = null, $logStatus = 'error')
    {
        $this->paymentRecord->addLog($logStatus, $data);
        $this->paymentRecord->setAndSave([
                                             'status' => 'error',
                                         ]);
    }

    public function getCurrency()
    {
        return config('pckg.payment.currency');
    }

    public function getStartUrl()
    {
        return $this->environment->url('derive.payment.start', [
            // 'handler' => $this->handler, // ?needed?
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getErrorUrl()
    {
        return $this->environment->url('derive.payment.error', [
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getWaitingUrl()
    {
        return $this->environment->url('derive.payment.waiting', [
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getSuccessUrl()
    {

        return $this->environment->url('derive.payment.success', [
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getNotificationUrl()
    {
        return $this->environment->url('derive.payment.notification', [
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getCheckUrl()
    {
        return $this->environment->url('derive.payment.check', [
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getCancelUrl()
    {
        return $this->environment->url('derive.payment.cancel', [
            'payment' => $this->paymentRecord,
        ]);
    }

    public function getDownloadUrl()
    {
        return $this->environment->url('derive.payment.download', [
            'payment' => $this->paymentRecord,
        ]);
    }

}