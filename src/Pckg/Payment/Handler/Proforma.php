<?php namespace Pckg\Payment\Handler;

class Proforma extends AbstractHandler implements Handler
{

    public function initHandler()
    {
        $this->config = [
            'url_waiting' => $this->environment->config('proforma.url_waiting'),
        ];
    }

    public function getStart()
    {
        return view('Derive/Basket:payment/start_upn',
                    [
                        'bills' => $this->order->getBills(),
                    ]);
    }

    public function postStart()
    {
        return [
            'success'  => true,
            'redirect' => $this->getWaitingUrl(),
        ];
    }

}