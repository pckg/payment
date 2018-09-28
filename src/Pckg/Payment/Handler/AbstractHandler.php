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

    protected $paymentRecord;

    /**
     * @var Environment
     */
    protected $environment;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function validate($request)
    {
        return [
            'success' => true,
        ];
    }

    /**
     * @return $this
     */
    public function createPaymentRecord($data = [])
    {
        $this->paymentRecord = Payment::createForInstalments($this->order->getBills(), static::class);

        return $this;
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

    public function start()
    {
    }

    public function startPartial()
    {
    }

    public function check()
    {
    }

    public function postStart()
    {
    }

    public function postStartPartial()
    {
    }

    public function getPaymentRecord()
    {
        return $this->paymentRecord;
    }

    public function startPartialData()
    {
        return [];
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

    public function cancel()
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

    public function approvePayment($description, $log, $transactionId, $status = 'approved')
    {
        $this->paymentRecord->addLog('payed', $log);

        $this->order->getBills()->each(function(OrdersBill $ordersBill) use ($description) {
            $ordersBill->confirm($description);
        });

        $this->paymentRecord->setAndSave([
                                             'status'         => $status,
                                             'transaction_id' => $transactionId,
                                         ]);
    }

    public function errorPayment($data = null, $logStatus = 'error')
    {
        $this->paymentRecord->addLog($logStatus, $data);
        $this->paymentRecord->setAndSave([
                                             'status' => 'error',
                                         ]);
    }

    public function getValidateUrl()
    {
        return $this->environment->url('derive.payment.validate',
                                       [
                                           'handler' => $this->handler,
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

    public function getStartUrl()
    {
        return $this->environment->url('derive.payment.start',
                                       [
                                           'handler' => $this->handler,
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

    public function getErrorUrl()
    {
        return $this->environment->url('derive.payment.error',
                                       [
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

    public function getWaitingUrl()
    {
        return $this->environment->url('derive.payment.waiting',
                                       [
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

    public function getSuccessUrl()
    {

        return $this->environment->url('derive.payment.success',
                                       [
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

    public function getNotificationUrl()
    {
        return $this->environment->url('derive.payment.notification',
                                       [
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

    public function getCheckUrl()
    {
        return $this->environment->url('derive.payment.check',
                                       [
                                           'payment' => $this->paymentRecord,
                                       ],
                                       true);
    }

    public function getCancelUrl()
    {
        return $this->environment->url('derive.payment.cancel',
                                       [
                                           'payment' => $this->paymentRecord,
                                       ]);
    }

}