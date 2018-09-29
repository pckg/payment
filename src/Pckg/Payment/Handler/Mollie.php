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
        $this->mollie->setApiKey(config('pckg.payment.provider.mollie.apiKey'));

        return $this;
    }

    private function getMolliePaymentData()
    {
        return [
            'amount'      => [
                'currency' => $this->order->getCurrency(),
                'value'    => (string)number_format($this->order->getTotal(), 2, '.', ''),
            ],
            'description' => $this->getDescription(),
            'redirectUrl' => $this->getSuccessUrl(),
            'webhookUrl'  => $this->getNotificationUrl(),
            'method'      => substr($this->handler, strpos($this->handler, '-') + 1),
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

            return [
                'success'  => true,
                'redirect' => $url,
            ];

        } catch (ApiException $e) {
            /**
             * Catch Mollie payments exception.
             */
            $this->paymentRecord->addLog('error', $e->getMessage());

            return [
                'success' => false,
                'message' => 'Mollie payments not available: ' . $e->getMessage(),
            ];
        } catch (Throwable $e) {
            /**
             * Catch all other exceptions.
             */
            $this->paymentRecord->addLog('error', $e->getMessage());

            return [
                'success' => false,
                'message' => 'System error:' . $e->getMessage(),
            ];
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
             * Get payment record from Mollie payments.
             */
            $payment = $this->mollie->payments->get($this->paymentRecord->payment_id);

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