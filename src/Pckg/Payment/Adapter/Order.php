<?php

namespace Pckg\Payment\Adapter;

interface Order
{
    public function getId();
    public function getIdString();
    public function getTotal();
    public function getTotalToPay();
    public function getVat();
    public function getDelivery();
    public function getDeliveryAddress();
    public function getBillingAddress();
    public function getDate();
    public function getCurrency();
    public function getDescription();
    public function getProducts();
/**
     * @return mixed|Customer
     */
    public function getCustomer();
    public function setPaid();
    public function getOrder();
    public function getBills();
    public function getPaymentStatus();
}
