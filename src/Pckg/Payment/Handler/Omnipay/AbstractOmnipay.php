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
    abstract public function isTestMode();

    /**
     * @return array
     */
    public function getOmnipayConfig()
    {
        $config = [];
        $env = $this->environment->config($this->handler);
        foreach ($this->getOmnipayConfigKeys() as $i => $map) {
            $config[$map] = $env[is_int($i) ? $map : $i];
        }
        $config['testMode'] = $this->isTestMode();
        return $config;
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

        /**
         * Instantiate payment gateway client.
         */
        $this->client = Omnipay::create('\\' . $this->gateway);
        $this->client->initialize($config);
    }

    /**
     * @return array|void
     * @throws \Exception
     */
    public function initPayment()
    {
        if (!isset($this->startOnInit)) {
            return parent::initPayment();
        }

        /**
         * This is used for methods that require a POST request with parameters.
         */
        return $this->postStart();
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
             * Make the purchase call.
             */
            $request = $this->client->purchase($this->getOmnipayOrderDetails());

            /**
             * Some parameters are not supported by the original gateway.
             */
            $response = $request->sendData($this->enrichOmnipayOrderDetails($request->getData()));

            /**
             * Firs check for a redirect.
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
                }

                /**
                 * Submit the form with data on the frontend.
                 */
                return [
                    'success' => true,
                    'form' => [
                        'url' => $redirect,
                        'data' => $response->getRedirectData()
                    ]
                ];
            }

            /**
             * Check for successful response.
             */
            if ($response->isSuccessful()) {
                /**
                 * Set reference.
                 */
                $this->paymentRecord->setAndSave([
                    'payment_id' => $response->getTransactionReference(),
                ]);

                return [
                    'success' => true,
                    'modal' => 'success',
                ];
            }

            return [
                'success' => false,
                'message' => $response->getMessage(),
                'code' => $response->getCode(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'modal' => 'error',
                'exception' => exception($e),
                'message' => 'Payments are not available at the moment.',
            ];
        }
    }

    /**
     * @return bool|void
     */
    public function completePurchase()
    {
        if (!$this->client->supportsCompletePurchase()) {
            return;
        }

        $response = $this->client->completePurchase()->send();
        if ($response->isSuccessful()) {
            $myTransactionId = $response->getTransactionId();
            $gatewayTransactionId = $response->getTransactionReference();

            $this->approvePayment(Convention::toCamel($this->handler) . ' #' . $gatewayTransactionId, $response, $gatewayTransactionId);

            return true;
        }

        $this->errorPayment();
        return false;
    }

    /**
     *
     */
    public function postNotification()
    {
        if (!$this->client->supportsAcceptNotification()) {
            throw new \Exception('Gateway does not support acceptNotification()');
        }

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
        if (!$this->client->supportsRefund()) {
            throw new \Exception('Gateway does not support refund()');
        }

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
     * @param array $data
     * @return array|mixed
     */
    public function enrichOmnipayOrderDetails($data = [])
    {
        return $data;
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