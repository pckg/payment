<?php namespace Pckg\Payment\Migration;

use Pckg\Database\Repository;
use Pckg\Migration\Migration;

class AlterPaymentsTable extends Migration
{

    protected $repository = Repository::class . '.gnp';

    public function up()
    {
        $braintree = $this->table('braintree');
        $braintree->datetime('dt_started')->nullable();

        $this->save();
    }

}