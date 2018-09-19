<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Mollie\Api\MollieApiClient;
use Pckg\Collection;
use Pckg\Payment\Record\Payment;
use Throwable;

class Mollie extends AbstractHandler implements Handler
{

    /**
     * @var MollieApiClient
     */
    protected $molly;

    protected $paymentMethod = null;

    protected $issuer = null;

    protected $handler = 'mollie';

    public function initHandler()
    {
        $this->molly = new \Mollie\Api\MollieApiClient();
        $this->molly->setApiKey("test_2fB7cspaA5WCEwkNx3TCD2SVBUugqR");

        return $this;
    }

    public function startPartial()
    {
        $price = $this->order->getTotal();

        $mollieData = [
            'amount'      => [
                'currency' => config('pckg.payment.currency'),
                'value'    => $price,
            ],
            'description' => __('order_payment') . " #" . $this->order->getId() . ' (' . $this->order->getNum() . ' - ' . $this->order->getBills()
                                                                                                                                      ->map('id')
                                                                                                                                      ->implode(',') . ')',
            'redirectUrl' => url('derive.payment.success',
                                 [
                                     'handler' => $this->handler,
                                     'order'   => $this->paymentRecord->order,
                                 ],
                                 true),
            'webhookUrl'  => url('derive.payment.notification',
                                 [
                                     'handler' => $this->handler,
                                     'order'   => $this->paymentRecord->order,
                                     'payment' => $this->paymentRecord,
                                 ],
                                 true),
            'method'      => 'creditcard',
        ];
        try {
            $payment = $this->molly->payments->create($mollieData);

            $this->paymentRecord->setAndSave(['payment_id' => $payment->id]);
            $url = $payment->getCheckoutUrl();
            $this->paymentRecord->addLog('redirected', $url);
            response()->redirect($url);
        } catch (Throwable $e) {
            $this->paymentRecord->addLog('error', $e->getMessage());
            response()->fatal($e->getMessage());
        }
    }

    public function getTotal()
    {
        return round($this->order->getTotal() * 100);
    }

    public function getTotalToPay()
    {
        return round($this->order->getTotalToPay() * 100);
    }

    public function getPaymentMethods()
    {
        ddd('getting payment methods');
        $response = $this->icepay->payment->getMyPaymentMethods();

        return $response;
    }

    public function getPaymentMethod($method)
    {
        ddd('get payment method');

        return (new Collection($this->getPaymentMethods()->PaymentMethods))->first(function($paymentMethod) use ($method
        ) {
            return $paymentMethod->PaymentMethodCode == $method;
        });
    }

    public function postNotification()
    {
        try {
            /*
             * Retrieve the payment's current state.
             */
            $payment = $mollie->payments->get(post('id'));

            $paymentRecord = Payment::getOrFail([
                                                    'id' => $payment->id,
                                                ]);

            if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {
                /*
                 * The payment is paid and isn't refunded or charged back.
                 * At this point you'd probably want to start the process of delivering the product to the customer.
                 */
                $payment->addLog('payed');

                $this->order->getBills()->each(function(OrdersBill $ordersBill) use ($payment) {
                    $ordersBill->confirm("Molly " . $payment->method . " #" . $payment->id, 'molly');
                });
                $payment->setAndSave([
                                         'status'         => 'approved',
                                         'transaction_id' => $payment->id,
                                     ]);
            } elseif ($payment->isOpen()) {
                /*
                 * The payment is open.
                 */
            } elseif ($payment->isPending()) {
                /*
                 * The payment is pending.
                 */
            } elseif ($payment->isFailed()) {
                /*
                 * The payment has failed.
                 */
            } elseif ($payment->isExpired()) {
                /*
                 * The payment is expired.
                 */
            } elseif ($payment->isCanceled()) {
                /*
                 * The payment has been canceled.
                 */
            } elseif ($payment->hasRefunds()) {
                /*
                 * The payment has been (partially) refunded.
                 * The status of the payment is still "paid"
                 */
            } elseif ($payment->hasChargebacks()) {
                /*
                 * The payment has been (partially) charged back.
                 * The status of the payment is still "paid"
                 */
            }
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            echo "API call failed: " . htmlspecialchars($e->getMessage());
        }
    }

    public function getValidateUrl()
    {
        return $this->environment->url('payment.validate',
                                       ['handler' => 'mollie', 'order' => $this->order->getOrder()]);
    }

    public function getStartUrl()
    {
        return $this->environment->url('payment.start',
                                       ['handler' => 'mollie', 'order' => $this->order->getOrder()]);
    }

}