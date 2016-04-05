<?php namespace Pckg\Payment\Adapter;

abstract class AbstractProduct implements Product
{

    protected $product;

    public function __construct($product)
    {
        $this->product = $product;
    }

}