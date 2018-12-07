<?php namespace Pckg\Payment\Controller;

use Pckg\Payment\Handler\PaypalGnp;
use Pckg\Payment\Service\Handlers;
use Pckg\Payment\Service\PckgPayment;

class Payment
{

    use Handlers, PckgPayment;
    
    public function postRefundAction(\Pckg\Payment\Record\Payment $payment)
    {
        /**
         * Currently only paypal is supported.
         */
        if ($payment->handler != PaypalGnp::class) {
            return [
                'success' => false,
                'message' => 'Only Paypal refunds are currently supported',
            ];
        }

        $amount = post('amount');
        if (!$amount || !($amount > 0)) {
            return [
                'success' => false,
                'message' => 'Amount should be set',
            ];
        }

        /**
         * Init proper config.
         */
        $order = $payment->getBills()->first()->order;
        $order->applyCompanyConfig();

        /**
         * Create handler and payment service.
         */
        $paymentService = $this->createPaymentService();
        $paymentService->useHandler($payment->handler);

        /**
         * Issue refund and return response.
         */
        return $paymentService->getHandler()->refund($payment, $amount);
    }

}