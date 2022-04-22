<?php

namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Payment\Record\Country;

class Countries extends Entity
{
    protected $record = Country::class;
}
