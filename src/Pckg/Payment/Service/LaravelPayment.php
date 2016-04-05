<?php namespace Pckg\Payment\Service;

use Pckg\Payment\Adapter\Environment\Laravel;

trait LaravelPayment
{

    public function createPaymentService()
    {
        $payment = new Payment();
        $payment->setEnvironment(new Laravel());

        return $payment;
    }

}