<?php namespace Pckg\Payment\Handler\Paymill;

use Pckg\Payment\Handler\Paymill;

class Sepa extends Paymill
{

    public function validate($request)
    {
        $rules = [
            'accountholder' => 'required',
            'iban'          => 'required',
            'bic'           => 'required',
        ];

        if (!$this->environment->validates($request, $rules)) {
            return $this->environment->errorJsonResponse();
        }

        return [
            'success' => true,
        ];
    }

    public function getValidateUrl()
    {
        return $this->environment->url('payment.validate', ['paymill-sepa', $this->order->getOrder()]);
    }

    public function getStartUrl()
    {
        return $this->environment->url('payment.start', ['paymill-sepa', $this->order->getOrder()]);
    }

}