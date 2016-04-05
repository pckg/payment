<?php namespace Pckg\Payment\Adapter;

abstract class AbstractCustomer implements Customer
{

    protected $customer;

    public function __construct($customer)
    {
        $this->customer = $customer;
    }

    public function getFullName()
    {
        return $this->getLastName() . ' ' . $this->getFirstName();
    }

}