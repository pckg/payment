<?php namespace Pckg\Payment\Handler;

use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Adapter\Log;
use Pckg\Payment\Record\Payment;

interface Handler
{

    public function initHandler();

    /**
     * @return $this
     */
    public function createPaymentRecord();

    public function start();

    public function check();

    public function postNotification();

    public function postStart();

    public function startPartial();

    public function postStartPartial();

    /**
     * @return $this
     */
    public function setLogger(Log $log);

    /**
     * @return $this
     */
    public function setEnvironment(Environment $environment);

    /**
     * @return array
     */
    public function startPartialData();

    /**
     * @return Payment
     */
    public function getPaymentRecord();

    public function setPaymentRecord($record);

}