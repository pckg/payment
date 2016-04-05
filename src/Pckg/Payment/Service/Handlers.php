<?php namespace Pckg\Payment\Service;

use Pckg\Payment\Handler\Handler;
use Pckg\Payment\Handler\Paymill;
use Pckg\Payment\Handler\Paypal;
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
        return $this->fullInitHandler(new Paypal($this->order));
    }

    public function usePaypalRestHandler()
    {
        return $this->fullInitHandler(new PaypalRest($this->order));
    }

    public function useProformaHandler()
    {
        return $this->fullInitHandler(new Proforma($this->order));
    }

}