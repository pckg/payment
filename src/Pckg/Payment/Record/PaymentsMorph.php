<?php

namespace Pckg\Payment\Record;

use Pckg\Database\Record;
use Pckg\Payment\Entity\PaymentsMorphs;

class PaymentsMorph extends Record
{
    protected $entity = PaymentsMorphs::class;
}
