<?php namespace Pckg\Payment\Adapter;

interface Product
{

    public function getId();

    public function getName();

    public function getPrice();

    public function getQuantity();

    public function getVat();

    public function getTotal();

    public function getSku();

}