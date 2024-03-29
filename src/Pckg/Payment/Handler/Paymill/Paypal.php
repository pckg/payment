<?php

namespace Pckg\Payment\Handler\Paymill;

use Paymill\Models\Request\Checksum;
use Paymill\Models\Request\Transaction;
use Pckg\Payment\Handler\Paymill;

class Paypal extends Paymill
{
    public function validate($request)
    {
        return [
            'success' => true,
            'checksum' => $this->getChecksum(),
        ];
    }

    public function getChecksum()
    {
        $checksum = new Checksum();
        $checksum->setChecksumType(Checksum::TYPE_PAYPAL)
                 ->setAmount($this->getTotalToPay())
                 ->setCurrency(config('pckg.payment.currency'))
                 ->setDescription('Description')
                 ->setReturnUrl($this->getReturnUrl())
                 ->setCancelUrl($this->getCancelUrl());
        $response = $this->paymill->create($checksum);
        return $response->getId();
    }

    public function getReturnUrl()
    {
        return $this->environment->fullUrl($this->environment->config('paymill-paypal.url_return'), [
                'handler' => 'paymill-paypal',
                'listing' => $this->order->getOrder(),
            ]);
    }

    public function getCancelUrl()
    {
        return $this->environment->fullUrl($this->environment->config('paymill-paypal.url_cancel'), [
                'handler' => 'paymill-paypal',
                'listing' => $this->order->getOrder(),
            ]);
    }

    public function success()
    {
        $transaction = new Transaction();
        $transaction->setId($this->environment->request('paymill_trx_id'));
        $response = $this->paymill->getOne($transaction);
        if ($response->getStatus() == 'closed') {
            $this->order->setPaid();
        }
    }

    public function getValidateUrl()
    {
        return $this->environment->url('payment.validate', ['handler' => 'paymill-paypal', 'order' => $this->order->getOrder()]);
    }

    public function getStartUrl()
    {
        return $this->environment->url('payment.start', ['handler' => 'paymill-paypal', 'order' => $this->order->getOrder()]);
    }
}
