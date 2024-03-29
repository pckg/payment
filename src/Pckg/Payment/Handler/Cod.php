<?php

namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;

class Cod extends AbstractHandler implements Handler
{
    /**
     * Triggered when user submitts "Pay now" button in payment popup.
     *
     * @return array|void
     */
    public function postStart()
    {
        $this->waitForPaymentOnDelivery('COD #' . $this->paymentRecord->id, null, $this->paymentRecord->id);

        return [
            'success' => true,
            'modal' => 'cod',
        ];
    }

    /**
     * @param $description
     * @param $log
     * @param $transactionId
     * @param string $status
     *
     * Mark payment status as "delivery".
     */
    public function waitForPaymentOnDelivery($description, $log, $transactionId, $status = 'delivery')
    {
        $this->paymentRecord->addLog($status, $log);

        $confirmation = $this->environment->config('cod.confirmation');
        $this->order->getBills()->keyBy('order_id')->each(function (OrdersBill $ordersBill) use ($confirmation) {
            $ordersBill->order->waitingForPaymentOnDelivery();

            if ($confirmation === 'automatic') {
                $ordersBill->order->confirm();
            }
        });

        $this->paymentRecord->setAndSave([
            'status' => $status,
            'transaction_id' => $transactionId,
        ]);
    }
}
