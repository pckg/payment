<?php namespace Pckg\Payment\Handler;

class Moneta extends AbstractHandler
{

    protected $moneta;

    public function initHandler()
    {
        $this->config = [
            'private_key' => $this->environment->config('paymill.private_key'),
            'public_key'  => $this->environment->config('paymill.public_key'),
        ];

        $this->moneta = ''; // new Moneta

        return $this;
    }

    public function getTotal()
    {
        return $this->order->getTotal();
    }

    public function getTotalToPay()
    {
        return $this->order->getTotalToPay();
    }

    public function getPublicKey()
    {
        return $this->config['public_key'];
    }

    public function start()
    {
        dd("Redirect to moneta!");
    }

    protected function makeTransaction($paymentId)
    {
    }

    protected function handleTransactionResponse($response)
    {
        if ($response->getStatus() == 'closed') {
            $this->order->setPaid();
        }
    }

    public function getStartUrl()
    {
        return $this->environment->url(
            'derive.payment.start',
            ['handler' => 'moneta', 'order' => $this->order->getOrder()]
        );
    }

}