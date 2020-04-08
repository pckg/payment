<?php namespace Pckg\Payment\Handler;

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
     * @var Client
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
        Client::setApiUrl($this->environment->config('bankart.url'));
        $this->client = new Client($username, $password, $apiKey, $sharedSecret);
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
        $customer = new Customer();
        $orderCustomer = $this->order->getCustomer();

        /**
         * The following fields are mandatory: customer.billingAddress1, customer.billingCity, customer.billingPostcode
         */
        $customer->setBillingCountry($orderCustomer->getCountryCode())
            ->setEmail($orderCustomer->getEmail())
            ->setBillingAddress1('Šmartno ob Paki 103')
            ->setBillingCity('Šmartno ob Paki')
            ->setBillingPostcode('3327');

        /**
         * Set direct transaction, no pre or after authorization.
         */
        $debit = new Debit();
        $merchantTransactionId = $this->getPaymentRecord()->hash;

        /**
         * Define transaction.
         */
        $debit->setTransactionId($merchantTransactionId)
            ->setSuccessUrl($this->getSuccessUrl())
            ->setCancelUrl($this->getCancelUrl())
            ->setCallbackUrl($this->getNotificationUrl())
            ->setAmount($this->getTotalToPay())
            ->setCurrency($this->getCurrency())
            ->setCustomer($customer);

        /**
         * Send transaction.
         */
        try {
            $result = $client->debit($debit);

            $this->paymentRecord->addLog('created', json_encode($result));

            /**
             * Handle the result.
             */
            if ($result->isSuccess()) {
                $this->paymentRecord->setAndSave([
                    'payment_id' => $result->getReferenceId(),
                ]);

                $gatewayReferenceId = $result->getReferenceId(); //store it in your database

                if ($result->getReturnType() == Result::RETURN_TYPE_ERROR) {
                    $this->paymentRecord->addLog('error', $result->getErrors());

                    return [
                        'success' => false,
                        'modal' => 'error',
                    ];
                } elseif ($result->getReturnType() == Result::RETURN_TYPE_REDIRECT) {
                    return [
                        'success' => true,
                        'redirect' => $result->getRedirectUrl(),
                    ];
                } elseif ($result->getReturnType() == Result::RETURN_TYPE_PENDING) {
                    return [
                        'success' => true,
                        'redirect' => $this->getWaitingUrl(),
                    ];
                } elseif ($result->getReturnType() == Result::RETURN_TYPE_FINISHED) {
                    return [
                        'success' => true,
                        'redirect' => $result->getRedirectUrl(),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Bankart did not return successful response',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'bankart payments are not available at the moment: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => false,
            'message' => 'Unknown response',
        ];
    }

    public function postNotification()
    {
        $client = $this->client;

        $client->validateCallbackWithGlobals();
        $callbackResult = $client->readCallback(file_get_contents('php://input'));

        $myTransactionId = $callbackResult->getTransactionId();
        $gatewayTransactionId = $callbackResult->getReferenceId();

        if ($callbackResult->getResult() == \PaymentGateway\Client\Callback\Result::RESULT_OK) {
            $callbackResult->getResult();

            $this->approvePayment("Bankart #" . $gatewayTransactionId, $callbackResult, $gatewayTransactionId);

        } elseif ($callbackResult->getResult() == \PaymentGateway\Client\Callback\Result::RESULT_ERROR) {
            $errors = $callbackResult->getErrors();
            $this->errorPayment($errors);
        }

        echo "OK";
        die;
    }

}