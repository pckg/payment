<?php

namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Payment\Record\Company;

/**
 * @method withCountry(callable $callable = null)
 */
class Companies extends Entity
{
    protected $record = Company::class;

    public function boot()
    {
        $this->withCountry();

        return $this;
    }

    public function country()
    {
        return $this->belongsTo(Countries::class)
            ->foreignKey('country_id');
    }
}
