<?php namespace Pckg\Payment\Handler;

class Proforma extends AbstractHandler implements Handler
{

    public function initHandler()
    {
        $this->config = [
            'url_waiting' => $this->environment->config('proforma.url_waiting'),
        ];
    }

    public function postStartPartial()
    {
        return [
            'success'  => true,
            'redirect' => $this->getWaitingUrl(),
        ];
    }

}