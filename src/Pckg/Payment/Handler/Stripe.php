<?php

namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Pckg\Payment\Record\Payment;
use Throwable;

class Stripe extends AbstractHandler implements Handler
{
    protected $clientSecret;

    protected $handler = 'stripe';

    public function initHandler()
    {
        \Stripe\Stripe::setApiKey(config('pckg.payment.provider.stripe.secret'));

        return $this;
    }

    /**
     * @return string
     * Used on frontend form.
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getTotalToPay()
    {
        return round($this->order->getTotalToPay() * 100);
    }

    /**
     * @return string
     * Prepare Stripe processor for payment.
     */
    public function initPayment()
    {
        $responseData = null;
        try {
            $intent = \Stripe\PaymentIntent::create([
                                                        'amount'   => $this->getTotalToPay(),
                                                        'currency' => strtolower($this->order->getCurrency()),
                                                    ]);

            $this->paymentRecord->addLog('created', json_encode($intent));

            if (!$intent->client_secret) {
                return [
                    'success' => false,
                    'message' => 'Payment intent error on Stripe',
                ];
            }

            $this->clientSecret = $intent->client_secret;

            $this->paymentRecord->setAndSave([
                                                 'transaction_id' => $intent->id,
                                             ]);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Stripe payments are not available at the moment: ' . $e->getMessage(),
            ];
        }

        return [
            'clientSecret' => $this->getClientSecret(),
            'publishable'  => config('pckg.payment.provider.stripe.publishable'),
        ];
    }

    public function postNotification()
    {
        // You can find your endpoint's secret in your webhook settings
        $endpoint_secret = config('pckg.payment.provider.stripe.signingSecret');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            response()->code(400);

            return [
                'success' => false,
                'error'   => true,
                'message' => 'Unexpected value',
            ];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            response()->code(400);

            return [
                'success' => false,
                'error'   => true,
                'message' => 'Invalid signature',
            ];
        } catch (Throwable $e) {
            throw $e;
        }

        if ($event->type == "payment_intent.succeeded") {
            $intent = $event->data->object;
            $this->approvePayment("Stripe #" . $intent->id, $intent, $intent->id);

            return [
                'success' => true,
            ];
        } elseif ($event->type == "payment_intent.payment_failed") {
            $intent = $event->data->object;
            $this->errorPayment($intent);

            return [
                'success' => false,
            ];
        }
    }

    public function refund(Payment $payment, $amount = null)
    {
        $refundPaymentRecord = Payment::createForRefund($payment, $amount);
        $params = [
            //'id' => $refundPaymentRecord->hash,
            'amount' => round($amount * 100),
            'currency' => $payment->currency,
            //'description' => 'REFUND',
            'payment_intent' => $payment->transaction_id,
            'reason' => post('reason', 'Test API refund'),
        ];


        /**
         * Save log.
         */
        $refundPaymentRecord->addLog('request:refund', $params);

        try {
            $refund = \Stripe\Refund::create($params);

            if ($refund->status === 'succeeded') {
                $this->paymentRecord = $refundPaymentRecord;
                $this->approveRefund('Refund Stripe #' . $refund->id, $refund, $refund->id);

                return [
                    'success' => true,
                ];
            }

            $refundPaymentRecord->addLog('response:failed', $refund);
        } catch (Throwable $e) {
            $refundPaymentRecord->addLog('response:exception');

            return [
                'success' => false,
                'message' => 'Refunds are not available at the moment.' . exception($e),
            ];
        }

        return [
            'success' => false,
            'message' => 'Refunds are not available at the moment',
        ];
    }
}
