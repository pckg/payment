<?php namespace Pckg\Payment\Service;

use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Adapter\Log;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Handler\Paymill;

class Payment
{

    use Handlers;

    protected $order;

    protected $environment;

    public function setOrder(Order $order)
    {
        $this->order = $order;

        return $this;
    }

    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;

        return $this;
    }

    public function getTotalWithCurrency()
    {
        return number_format($this->getTotal(), 2) . ' ' . $this->getCurrency();
    }

    public function getTotalToPayWithCurrency()
    {
        return number_format($this->getTotalToPay(), 2) . ' ' . $this->getCurrency();
    }

    public function getTotal()
    {
        return $this->order->getTotal();
    }

    public function getTotalToPay()
    {
        return $this->order->getTotalToPay();
    }

    public function getCurrency()
    {
        return $this->order->getCurrency();
    }

    public function getUrl($action, $handler)
    {
        return $this->environment->url('payment.' . $action, [$handler, $this->order->getOrder()]);
    }

    public function has($handler)
    {
        return $this->environment->config($handler . '.enabled');
    }

    public function prepare(Order $order, $handler, Log $logger)
    {
        $this->setOrder($order);
        $this->{'use' . ucfirst(camel_case($handler)) . 'Handler'}();
        $this->getHandler()->setLogger($logger)->setEnvironment($this->environment);
    }

}