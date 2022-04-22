<?php

namespace Pckg\Payment\Service;

use Pckg\Payment\Adapter\Environment\Pckg;

trait PckgPayment
{
    public function createPaymentService()
    {
        $payment = new Payment();
        $payment->setEnvironment(new Pckg());
        context()->bind(Payment::class, $payment);
        return $payment;
    }
}
