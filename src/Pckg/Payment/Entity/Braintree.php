<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Database\Repository;
use Pckg\Payment\Record\Braintree as BraintreeRecord;

class Braintree extends Entity
{

    protected $repositoryName = Repository::class . '.gnp';

    protected $record = BraintreeRecord::class;

}