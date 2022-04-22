<?php

namespace Pckg\Payment\Entity;

use Pckg\Database\Entity;
use Pckg\Payment\Record\PaymentLog;

class PaymentLogs extends Entity
{
    protected $record = PaymentLog::class;
}
