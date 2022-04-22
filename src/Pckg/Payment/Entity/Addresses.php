<?php

namespace Pckg\Payment\Entity;

use Pckg\Auth\Entity\Users;
use Pckg\Database\Entity;
use Pckg\Payment\Record\Address;

class Addresses extends Entity
{
    protected $record = Address::class;

    public function user()
    {
        return $this->belongsTo(Users::class)->foreignKey('user_id');
    }

    public function country()
    {
        return $this->belongsTo(Countries::class)->foreignKey('country_id');
    }
}
