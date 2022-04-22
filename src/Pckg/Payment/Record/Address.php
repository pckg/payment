<?php

namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\Addresses;

/**
 * Class Address
 * @property Country $country
 * @property string $name
 * @property string $business
 * @property string $vat_number
 * @property string $address_line1
 * @property string $address_line2
 * @property string $city
 * @property string $postal
 * @property string $phone
 */
class Address extends Record
{
    protected $entity = Addresses::class;
}
