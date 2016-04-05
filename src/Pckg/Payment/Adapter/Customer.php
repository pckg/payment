<?php namespace Pckg\Payment\Adapter;

interface Customer
{

    public function getId();

    public function getFirstName();

    public function getLastName();

    public function getFullName();

    public function getAddress();

    public function getPostCode();

    public function getCity();

    public function getCountry();

}