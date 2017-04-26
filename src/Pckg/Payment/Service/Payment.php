<?php namespace Pckg\Payment\Service;

use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Adapter\Log;
use Pckg\Payment\Adapter\Order;

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
        $this->useHandler($handler);
        $this->getHandler()->setLogger($logger)->setEnvironment($this->environment);

        return $this;
    }

    public function getPaymentMethods()
    {
        $methods = [];
        // $offersPaymentMethods = $this->order->getOrder()->offer->paymentMethods->keyBy('slug');

        foreach (config('pckg.payment') as $method => $config) {
            if (config('pckg.payment.' . $method . '.enabled')/* && $offersPaymentMethods->hasKey($method)*/) {
                $submethods = [];
                foreach (config('pckg.payment.' . $method . '.methods', []) as $submethod) {
                    $submethods[$method . '-' . $submethod] = [
                        'url' => url('derive.payment.startPartial', ['handler' => $method . '-' . $submethod]),
                    ];
                }

                $methods[$method] = [
                    'url'     => url('derive.payment.startPartial', ['handler' => $method]),
                    'methods' => $submethods,
                ];
            }
        }

        return $methods;
    }

}