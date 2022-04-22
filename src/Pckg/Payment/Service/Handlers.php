<?php

namespace Pckg\Payment\Service;

use Pckg\Database\Helper\Convention;
use Pckg\Payment\Handler\AbstractHandler;
use Pckg\Payment\Handler\Axcess;
use Pckg\Payment\Handler\Bankart;
use Pckg\Payment\Handler\BankartCC;
use Pckg\Payment\Handler\BankTransfer;
use Pckg\Payment\Handler\Braintree;
use Pckg\Payment\Handler\Handler;
use Pckg\Payment\Handler\Icepay;
use Pckg\Payment\Handler\MojCent;
use Pckg\Payment\Handler\Mollie;
use Pckg\Payment\Handler\Monri;
use Pckg\Payment\Handler\Omnipay\CorvusPay;
use Pckg\Payment\Handler\Omnipay\Revolut;
use Pckg\Payment\Handler\Valu;
use Pckg\Payment\Handler\Paymill;
use Pckg\Payment\Handler\PaypalGnp;
use Pckg\Payment\Handler\PaypalRest;
use Pckg\Payment\Handler\Proforma;
use Pckg\Payment\Handler\VivaWallet;
use Pckg\Payment\Adapter\Environment;
use Pckg\Payment\Handler as HandlerAlias;

/**
 * @property Environment $environment
 * @property mixed $order
 */
trait Handlers
{
    protected $handler;
    public function setHandler(Handler $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    /**
     * @return Handler|AbstractHandler
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

    /**
     * @param $handler
     * @return $this
     * @throws \Exception
     */
    public function useHandler($handler)
    {
        if (class_exists($handler)) {
            return $this->fullInitHandler(new $handler($this->order));
        }

        $useHandlermethod = 'use' . ucfirst(Convention::toCamel($handler)) . 'Handler';
        if (method_exists($this, $useHandlermethod)) {
            $this->{$useHandlermethod}();
            return $this;
        }

        $classes = [];
        if (strpos($handler, '-')) {
            list($mainHandler, $subhandler) = explode('-', $handler);
            $classes[] = HandlerAlias::class . '\\' . ucfirst($mainHandler) . '\\' . ucfirst($subhandler);
            $classes[] = HandlerAlias::class . '\\' . ucfirst($mainHandler);
            $classes[] = HandlerAlias::class . '\\' . str_replace(' ', '', Convention::toPascal(str_replace('-', ' ', $handler)));
        } else {
            $classes[] = HandlerAlias::class . '\\' . ucfirst($handler);
        }

        foreach ($classes as $class) {
            if (!class_exists($class)) {
                continue;
            }

            return $this->fullInitHandler(new $class($this->order));
        }

        throw new \Exception('No handler defined for ' . $handler);
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

    public function useValuHandler()
    {
        return $this->fullInitHandler(new Valu($this->order));
    }

    public function useBankartHandler()
    {
        return $this->fullInitHandler(new Bankart($this->order));
    }

    public function useBankartCCHandler()
    {
        return $this->fullInitHandler(new BankartCC($this->order));
    }

    public function useCorvusPayHandler()
    {
        return $this->fullInitHandler(new CorvusPay($this->order));
    }

    public function useMonriHandler()
    {
        return $this->fullInitHandler(new Monri($this->order));
    }

    public function useIcePayHandler()
    {
        return $this->fullInitHandler(new Icepay($this->order));
    }

    public function useAxcessHandler()
    {
        return $this->fullInitHandler(new Axcess($this->order));
    }

    public function useMojcentHandler()
    {
        return $this->fullInitHandler(new MojCent($this->order));
    }

    public function useVivaWalletHandler()
    {
        return $this->fullInitHandler(new VivaWallet($this->order));
    }

    public function useBankTransferHandler()
    {
        return $this->fullInitHandler(new BankTransfer($this->order));
    }

    public function useMollieHandler()
    {
        return $this->fullInitHandler(new Mollie($this->order));
    }

    public function useRevolutHandler()
    {
        return $this->fullInitHandler(new Revolut($this->order));
    }
}
