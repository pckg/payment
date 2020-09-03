<?php namespace Pckg\Payment\Handler\Omnipay;

use Omnipay\Common\AbstractGateway;
use Omnipay\Common\GatewayInterface;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Common\Message\RedirectResponseInterface;
use Omnipay\Omnipay;
use Pckg\Database\Helper\Convention;
use Pckg\Payment\Handler\AbstractHandler;
use Pckg\Payment\Record\Payment;

abstract class AbstractOmnipay extends AbstractHandler
{

    /**
     * @var string
     */
    protected $gateway;

    /**
     * @var GatewayInterface|AbstractGateway
     */
    protected $client;

    /**
     * @return array
     */
    abstract public function getOmnipayConfigKeys();

    /**
     * @return array
     */
    public function getOmnipayConfig()
    {
        return only($this->environment->config($this->handler), $this->getOmnipayConfigKeys());
    }

    /**
     * @return AbstractHandler|void
     * @throws \PaymentGateway\Client\Exception\InvalidValueException
     */
    public function initHandler()
    {
        /**
         * Get default config.
         */
        $config = $this->getOmnipayConfig();
        $config['testMode'] = ($config['mode'] ?? null) === 'test';

        /**
         * Instantiate payment gateway client.
         */
        $this->client = Omnipay::create('\\' . $this->gateway);
        $this->client->initialize($config);
    }

    /**
     * @throws \Exception
     */
    public function postStart()
    {
        if (!$this->client->supportsPurchase()) {
            throw new \Exception('Gateway does not support purchase()');
        }

        try {
            /**
             * Get customer and order details.
             */
            $data = $this->getOmnipayOrderDetails();

            /**
             * Make the purchase call.
             */
            $response = $this->client->purchase($data)->send();

            /**
             * Check for successful response.
             */
            if (!$response->isSuccessful()) {
                return [
                    'success' => false,
                    'message' => $response->getMessage(),
                ];
            }

            /**
             * Set reference.
             */
            $this->paymentRecord->setAndSave([
                'payment_id' => $response->getTransactionReference(),
            ]);

            /**
             * Send the redirect to the frontend.
             */
            if ($response->isRedirect()) {
                $redirect = $response->getRedirectUrl();
                $method = $response->getRedirectMethod();
                if ($method === 'GET') {
                    return [
                        'success' => true,
                        'redirect' => $redirect,
                    ];
                } else {
                    // $response->getRedirectData() // associative array of fields which must be posted to the redirectUrl
                    return [
                        'success' => false,
                        'message' => 'POST method not supported',
                        'modal' => 'error',
                    ];
                }
            }

            return [
                'success' => true,
                'modal' => 'success',
            ];
        } catch (\Throwable $e) {

            return [
                'success' => false,
                'modal' => 'error',
                'message' => 'Payments are not available at the moment.',
            ];
        }
    }

    /**
     *
     */
    public function postNotification()
    {
        $this->paymentRecord->addLog('notification', post()->all());
        $response = $this->client->acceptNotification();

        if ($response->getTransactionStatus() === NotificationInterface::STATUS_COMPLETED) {
            $myTransactionId = $response->getTransactionId();
            $gatewayTransactionId = $response->getTransactionReference();

            $this->approvePayment(Convention::toCamel($this->handler) . ' #' . $gatewayTransactionId, $response, $gatewayTransactionId);
        } else {
            $this->errorPayment();
        }

        echo "OK";
        die();
    }

    /**
     * @param Payment $payment
     * @param null $amount
     * @return array|bool[]|false[]|void
     */
    public function refund(Payment $payment, $amount = null)
    {
        $refundPaymentRecord = Payment::createForRefund($payment, $amount);

        try {
            $result = $this->client->refund([
                'transaction_id' => $refundPaymentRecord->hash,
                'reference_transaction_id' => $payment->transaction_id,
                'amount' => $amount,
                'currency' => $payment->currency,
            ])->send();

            $refundPaymentRecord->addLog('refund', $result);
            if ($result->isSuccessful()) {
                $this->paymentRecord = $refundPaymentRecord;
                $transactionReference = $result->getTransactionReference();
                $message = 'Refund ' . Convention::toCamel($this->handler) . ' #' . $transactionReference;
                $this->approveRefund($message, $result, $transactionReference);

                return [
                    'success' => true,
                ];
            }

            $refundPaymentRecord->addLog('response:failed', $result);

            return [
                'success' => false,
            ];
        } catch (Throwable $e) {
            $refundPaymentRecord->addLog('response:exception');

            return [
                'success' => false,
                'message' => 'Refunds are not available at the moment.' . exception($e),
            ];
        }
    }

    /**
     * @return array
     */
    protected function getOmnipayOrderDetails()
    {
        return [
            'transaction_id' => $this->paymentRecord->hash,
            'amount' => $this->getTotalToPay(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'return_url' => $this->getCheckUrl(),
            'error_url' => $this->getErrorUrl(),
            'cancel_url' => $this->getCancelUrl(),
            'notify_url' => $this->getNotificationUrl(),
            'customer' => $this->getOmnipayCustomer(),
        ];
    }

    /**
     * @return array
     */
    protected function getOmnipayCustomer()
    {
        /**
         * The following fields are mandatory: customer.billingAddress1, customer.billingCity, customer.billingPostcode
         */
        $customer = [
            'first_name' => null,
            'last_name' => null,
            'identification' => null,
            'email' => null,
            'billingAddress1' => 'None',
            'billingCity' => 'Unknown',
            'billingCountry' => 'NA',
            'billingPostcode' => '0000',
        ];

        /**
         * Customer details
         */
        $orderCustomer = $this->order->getCustomer();
        if ($orderCustomer) {
            $customer = array_merge($customer, [
                'email' => $orderCustomer->getEmail(),
                'identification' => $orderCustomer->getId(),
                'first_name' => $orderCustomer->getFirstName(),
                'last_name' => $orderCustomer->getLastName(),
            ]);
        }

        /**
         * Billing address.
         */
        $billingAddress = $this->order->getBillingAddress();
        if ($billingAddress) {
            $city = $billingAddress->city;
            $postal = $billingAddress->postal;
            if (!$city && !$postal) {
                try {
                    [$city, $postal] = explode(" ", $billingAddress->address_line2, 2);
                } catch (Throwable $e) {

                }
            }
            $customer = array_merge($customer, [
                'billingCountry' => strtoupper($billingAddress->country->code),
                'billingAddress1' => $billingAddress->address_line1,
                'billingCity' => $city,
                'billingPostcode' => $postal,
            ]);
        }

        return $customer;
    }

}