<?php

namespace Pckg\Payment\Record;

use Carbon\Carbon;
use Pckg\Database\Record;
use Pckg\Payment\Adapter\Order;
use Pckg\Payment\Entity\PaymentLogs;
use Pckg\Payment\Entity\Payments;

class PaymentLog extends Record
{
    protected $entity = PaymentLogs::class;
}
