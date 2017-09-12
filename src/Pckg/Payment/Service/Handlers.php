<?php namespace Pckg\Payment\Service;

use Pckg\Payment\Handler\Braintree;
use Pckg\Payment\Handler\Handler;
use Pckg\Payment\Handler\Icepay;
use Pckg\Payment\Handler\Moneta;
use Pckg\Payment\Handler\Paymill;
use Pckg\Payment\Handler\PaypalGnp;
use Pckg\Payment\Handler\PaypalRest;
use Pckg\Payment\Handler\Proforma;

trait Handlers
{

    protected $handler;

    public function setHandler(Handler $handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return Handler
     */
    public function getHandler()
    {
        return $this->handler;
    }

    public function fullInitHandler(Handler $handler)
    {
        $this->handler = $handler;
        $this->handler->setEnvironment($this->environment);
        $this->handler->initHandler();

        return $this;
    }

    public function useHandler($handler)
    {
        if (method_exists($this, 'use' . ucfirst($handler) . 'Handler')) {
            $this->{'use' . ucfirst($handler) . 'Handler'}();

            return;
        }

        list($handler, $subhandler) = explode('-', $handler);
        $finalHandler = \Pckg\Payment\Handler::class . '\\' . ucfirst($handler) .
                        ($subhandler ? '\\' . ucfirst($subhandler) : '');

        return $this->fullInitHandler(new $finalHandler($this->order));
    }

    public function useBraintreeHandler()
    {
        return $this->fullInitHandler(new Braintree($this->order));
    }

    public function usePaymillHandler()
    {
        return $this->fullInitHandler(new Paymill($this->order));
    }

    public function usePaymillSepaHandler()
    {
        return $this->fullInitHandler(new Paymill\Sepa($this->order));
    }

    public function usePaymillPaypalHandler()
    {
        return $this->fullInitHandler(new Paymill\Paypal($this->order));
    }

    public function usePaypalHandler()
    {
        return $this->fullInitHandler(new PaypalGnp($this->order));
    }

    public function usePaypalRestHandler()
    {
        return $this->fullInitHandler(new PaypalRest($this->order));
    }

    public function useProformaHandler()
    {
        return $this->fullInitHandler(new Proforma($this->order));
    }

    public function useUpnHandler()
    {
        return $this->fullInitHandler(new Proforma($this->order));
    }

    public function useMonetaHandler()
    {
        return $this->fullInitHandler(new Moneta($this->order));
    }

    public function useIcePayHandler()
    {
        return $this->fullInitHandler(new Icepay($this->order));
    }

}