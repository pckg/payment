<?php namespace Pckg\Payment\Handler;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Pckg\Collection;
use Throwable;

class Mollie extends AbstractHandler implements Handler
{

    /**
     * @var MollieApiClient
     */
    protected $mollie;

    protected $handler = 'mollie';

    public function initHandler()
    {
        /**
         * Create Mollie
         */
        $this->mollie = new \Mollie\Api\MollieApiClient();
        $this->mollie->setApiKey(config('pckg.payment.mollie.apiKey'));

        return $this;
    }

    private function getMolliePaymentData()
    {
        return [
            'amount'      => [
                'currency' => $this->order->getCurrency(),
                'value'    => (string)round($this->order->getTotal(), 2),
            ],
            'description' => $this->getDescription(),
            'redirectUrl' => $this->getSuccessUrl(),
            'webhookUrl'  => $this->getNotificationUrl(),
            'method'      => 'creditcard',
        ];
    }

    public function startPartial()
    {
        try {
            /**
             * Send payment request to Mollie API.
             */
            $paymentData = $this->getMolliePaymentData();
            $payment = $this->mollie->payments->create($paymentData);

            /**
             * Save created payment id for future references.
             */
            $this->setPaymentId($payment->id);

            /**
             * Redirect to URL in Mollie response.
             */
            $url = $payment->getCheckoutUrl();
            $this->paymentRecord->addLog('redirected', $url);
            $this->environment->redirect($url);

        } catch (ApiException $e) {
            /**
             * Catch Mollie payments exception.
             */
            $this->paymentRecord->addLog('error', $e->getMessage());
            response()->fatal('Mollie payments not available: ' . $e->getMessage());
        } catch (Throwable $e) {
            /**
             * Catch all other exceptions.
             */
            $this->paymentRecord->addLog('error', $e->getMessage());
            response()->fatal('System error:' . $e->getMessage());
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
            /**
             * Payment ID should already be set by payment record.
             * Do not proceed if ids do not match.
             */
            $postId = post('id', null);
            if ($this->paymentRecord->payment_id != post('id')) {
                throw new Exception("Internal payment ID and Mollie payment ID do not match (" . $this->paymentRecord->payment_id . " and " . $postId . ").");
            }

            /**
             * Get payment record from Mollie payments.
             */
            $payment = $mollie->payments->get($this->paymentRecord->payment_id);

            /**
             * Start processing info.
             */
            if ($payment->isPaid() && !$payment->hasRefunds() && !$payment->hasChargebacks()) {
                /**
                 * Log successful payment, change payment status, trigger events.
                 */
                $this->approvePayment("Mollie " . $payment->method . " #" . $payment->id, $payment, $payment->id);
            } elseif ($payment->isOpen()) {
            } elseif ($payment->isPending()) {
            } elseif ($payment->isFailed()) {
            } elseif ($payment->isExpired()) {
            } elseif ($payment->isCanceled()) {
            } elseif ($payment->hasRefunds()) {
            } elseif ($payment->hasChargebacks()) {
            }
        } catch (\Mollie\Api\Exceptions\ApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $e;
        }
    }

}