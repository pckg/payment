<?php namespace Pckg\Payment\Handler;

use Ampeco\OmnipayBankart\Gateway;
use Omnipay\Common\Message\NotificationInterface;
use Omnipay\Omnipay;
use PaymentGateway\Client\Client;
use PaymentGateway\Client\Data\Customer;
use PaymentGateway\Client\Transaction\Debit;
use PaymentGateway\Client\Transaction\Result;
use Pckg\Payment\Adapter\Order;
use Throwable;

class Bankart extends AbstractHandler implements Handler
{

    protected $handler = 'bankart';

    /**
     * @var Gateway
     */
    protected $client;

    public function __construct(Order $order = null)
    {
        parent::__construct($order);
    }

    /**
     * @return AbstractHandler|void
     * @throws \PaymentGateway\Client\Exception\InvalidValueException
     */
    public function initHandler()
    {
        /**
         * Set config.
         */
        $username = $this->environment->config('bankart.apiUsername');
        $password = $this->environment->config('bankart.apiPassword');
        $apiKey = $this->environment->config('bankart.apiKey');
        $sharedSecret = $this->environment->config('bankart.sharedSecret');

        /**
         * Instantiate payment gateway client.
         */
        $this->client = Omnipay::create('\Ampeco\OmnipayBankart\Gateway');
        $this->client->initialize([
            'username' => $username,
            'password' => $password,
            'apiKey' => $apiKey,
            'sharedSecret' => $sharedSecret,
        ]);
    }

    /**
     * @return string
     * Prepare Stripe processor for payment.
     */
    public function postStart()
    {
        /**
         * Set customer details.
         */
        $client = $this->client;
        $merchantTransactionId = $this->getPaymentRecord()->hash;

        /**
         * The following fields are mandatory: customer.billingAddress1, customer.billingCity, customer.billingPostcode
         */
        $orderCustomer = $this->order->getCustomer();
        $billingAddress = $this->order->getBillingAddress();
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
        if ($orderCustomer) {
            $customer = array_merge($customer, [
                'email' => $orderCustomer->getEmail(),
                'identification' => $orderCustomer->getId(),
                'first_name' => $orderCustomer->getFirstName(),
                'last_name' => $orderCustomer->getLastName(),
            ]);
        }
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

        $data = [
            'transaction_id' => $merchantTransactionId,
            'amount' => $this->getTotalToPay(),
            'currency' => $this->getCurrency(),
            'description' => $this->getDescription(),
            'return_url' => $this->getSuccessUrl(),
            'error_url' => $this->getErrorUrl(),
            'cancel_url' => $this->getCancelUrl(),
            'notify_url' => $this->getNotificationUrl(),
            'customer' => $customer
        ];

        $response = $this->client->purchase($data)->send();

        if (!$response->isSuccessful()) {
            return [
                'success' => false,
                'message' => $response->getMessage(),
            ];
        }
        $this->paymentRecord->setAndSave([
            'payment_id' => $response->getTransactionReference(),
        ]);

        return [
            'success' => true,
            'redirect' => $response->getRedirectUrl(),
        ];
    }

    public function postNotification()
    {
        $client = $this->client;

        $response = $this->client->acceptNotification();

        if ($response->getTransactionStatus() === NotificationInterface::STATUS_COMPLETED) {
            $myTransactionId = $response->getTransactionId();
            $gatewayTransactionId = $response->getTransactionReference();

            if ($callbackResult->getResult() == \PaymentGateway\Client\Callback\Result::RESULT_OK) {
                $this->approvePayment("Bankart #" . $gatewayTransactionId, $response, $gatewayTransactionId);

            } elseif ($response->getResult() == \PaymentGateway\Client\Callback\Result::RESULT_ERROR) {
                $this->errorPayment();
            }
        }

        echo "OK";
        die;
    }

}