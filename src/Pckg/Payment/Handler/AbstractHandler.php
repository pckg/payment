<?php namespace Pckg\Payment\Handler;

use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Adapter\Log;
use Pckg\Payment\Adapter\Order;

abstract class AbstractHandler implements Handler
{

    protected $config = [];

    protected $order;

    protected $log;

    /**
     * @var Environment
     */
    protected $environment;

    public function __construct(Order $order)
    {
        $this->order = $order;
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

}