<?php namespace Pckg\Payment\Handler;

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
        $this->paymentRecord = Payment::createForOrderAndHandler(
            $this->order,
            static::class,
            array_merge($data, [
                'billIds' => $this->order->getBills()->map('id'),
            ])
        );

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

}