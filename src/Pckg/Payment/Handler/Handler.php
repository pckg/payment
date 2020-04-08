<?php namespace Pckg\Payment\Handler;

use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Adapter\Log;
use Pckg\Payment\Record\Payment;

interface Handler
{

    public function initHandler();

    public function initPayment();

    public function getInfo();

    public function getStart();

    public function check();

    public function refund(Payment $payment, $amound = null);

    public function postNotification();

    public function postStart();

    /**
     * @return $this
     */
    public function setLogger(Log $log);

    /**
     * @return $this
     */
    public function setEnvironment(Environment $environment);

    /**
     * @return Payment
     */
    public function getPaymentRecord();

    /**
     * @param $record
     *
     * @return $this
     */
    public function setPaymentRecord($record);

}