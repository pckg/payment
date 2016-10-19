<?php namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Database\Repository;
use Pckg\Payment\Record\Moneta as MonetaRecord;

class Moneta extends Entity
{

    protected $repositoryName = Repository::class . '.gnp';

    protected $record = MonetaRecord::class;

}