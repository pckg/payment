<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Database\Repository;
use Pckg\Payment\Record\Braintree as BraintreeRecord;
use Pckg\Payment\Record\Paypal as PaypalRecord;

class Paypal extends Entity
{

    protected $repositoryName = Repository::class . '.gnp';

    protected $record = PaypalRecord::class;

}