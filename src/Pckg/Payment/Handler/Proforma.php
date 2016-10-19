<?php namespace Pckg\Payment\Handler;

class Proforma extends AbstractHandler implements Handler
{

    public function initHandler()
    {
        $this->config = [
            'url_waiting' => $this->environment->config('proforma.url_waiting'),
        ];
    }

    public function startPartial()
    {
        
    }

    public function start()
    {
        $url = $this->environment->url(
            $this->config['url_waiting'],
            ['handler' => 'proforma', 'order' => $this->order->getOrder()]
        );
        $this->environment->redirect($url);
    }

    public function waiting()
    {

    }

}