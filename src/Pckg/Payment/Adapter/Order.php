<?php namespace Pckg\Payment\Adapter;

interface Order
{

    public function getId();

    public function getTotal();

    public function getTotalToPay();

    public function getVat();

    public function getDelivery();

    public function getDate();

    public function getCurrency();

    public function getDescription();

    public function getProducts();

    public function getCustomer();

    public function setPaid();

    public function getOrder();

}