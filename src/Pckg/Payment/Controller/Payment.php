<?php namespace Pckg\Payment\Controller;

use Derive\Platform\Entity\Companies;
use Derive\Platform\Record\Company;
use Pckg\Generic\Record\SettingsMorph;
use Pckg\Payment\Form\PlatformSettings\Axcess;
use Pckg\Payment\Form\PlatformSettings\Braintree;
use Pckg\Payment\Form\PlatformSettings\Cod;
use Pckg\Payment\Form\PlatformSettings\Icepay;
use Pckg\Payment\Form\PlatformSettings\Mollie;
use Pckg\Payment\Form\PlatformSettings\Valu;
use Pckg\Payment\Form\PlatformSettings\Paypal;
use Pckg\Payment\Form\PlatformSettings\Upn;
use Pckg\Payment\Handler\PaypalGnp;
use Pckg\Payment\Handler\Stripe;
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
        if (!in_array($payment->handler, [PaypalGnp::class, Stripe::class])) {
            return [
                'success' => false,
                'message' => 'Refunds are not supported',
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

    public function getCompanySettingsAction(Company $company, $paymentMethod)
    {
        $company->applyConfig();

        return [
            'paymentMethod' => config('pckg.payment.provider.' . $paymentMethod, []),
        ];
    }

    public function postCompanySettingsAction(Company $company, $paymentMethod)
    {
        $company->applyConfig();
        $mapper = config('pckg.payment.formMapper', []);

        $form = $mapper[$paymentMethod] ?? null;
        if (!$form) {
            throw new \Exception('No mapper defined for payment method');
        }

        $form = resolve($form);
        $data = $form->getData();
        $data['enabled'] = config('pckg.payment.provider.' . $paymentMethod . '.enabled');

        SettingsMorph::makeItHappen(
            'pckg.payment.provider.' . $paymentMethod,
            $data,
            Companies::class,
            $company->id
        );

        return [
            'data'    => $form->getData(),
            'success' => true,
        ];
    }

}