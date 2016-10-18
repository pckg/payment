<?php namespace Pckg\Payment\Adapter;

abstract class AbstractOrder implements Order
{

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function getCurrency()
    {
        return 'EUR';
    }

    public function getIdString()
    {
        return sha1($this->getId());
    }

}