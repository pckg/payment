<?php namespace Pckg\Payment\Handler;

use Braintree\ClientToken;
use Braintree\Configuration;
use Braintree\Transaction;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Throwable;
use Exception;

/**
 * Class CheckoutPortal
 *
 * @package Pckg\Payment\Handler
 */
class CheckoutPortal extends AbstractHandler implements Handler
{

    /**
     * @var string
     */
    protected $handler = 'checkoutPortal';

    /**
     * @return array|AbstractHandler
     */
    public function initPayment()
    {
        $client = new Client();

        $mode = $this->environment->config('checkout-portal.mode', null);
        $username = $this->environment->config('checkout-portal.username', null);
        $password = $this->environment->config('checkout-portal.password', null);
        $merchantAccount = $this->environment->config('checkout-portal.maid', null);
        $endpoint = $this->environment->config('checkout-portal.endpoint', null);

        $value = number_format($this->paymentRecord->price, 2);
        $currency = config('pckg.payment.currency', null);
        $firstName = auth()->user('name');
        $lastName = auth()->user('surname');
        $ancestor = 'https://' . server('HTTP_HOST', null);

        $transactionType = 'purchase'; // authorization

        $successUrl = $this->getSuccessUrl();
        $errorUrl = $this->getErrorUrl();
        $cancelUrl = $this->getCancelUrl();
        $defaultUrl = $this->getCheckUrl();
        $pendingUrl = $this->getCheckUrl();
        $notificationUrl = $this->getNotificationUrl();

        $data = [
            'payment' => [
                'merchant-account-id'  => [
                    'value' => $merchantAccount,
                ],
                'request-id'           => $this->paymentRecord->hash,
                'transaction-type'     => $transactionType,
                'requested-amount'     => [
                    'value'    => $value,
                    'currency' => $currency,
                ],
                'account-holder'       => [
                    'first-name' => $firstName,
                    'last-name'  => $lastName,
                ],
                'redirect-url'         => $defaultUrl,
                'pending-redirect-url' => $pendingUrl,
                'success-redirect-url' => $successUrl,
                'fail-redirect-url'    => $errorUrl,
                'cancel-redirect-url'  => $cancelUrl,
                /*'three-d'              => [
                    'attempt-three-d' => 'true',
                ],*/
                'notifications'        => [
                    'format'       => 'application/json-signed',
                    'notification' => [
                        [
                            'url' => $notificationUrl,
                        ],
                    ],
                ],
            ],
            'options' => [
                'mode'           => $mode,
                'frame-ancestor' => $ancestor,
            ],
        ];

        if ($mode === 'seamless') {
            $data['payment']['payment-methods'] = [
                'payment-method' => [
                    [
                        'name' => 'creditcard',
                    ],
                ],
            ];
        }

        $this->paymentRecord->addLog('requesting', $data);

        $request = $client->post($endpoint, [
            'json'    => $data,
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
            ],
        ]);

        $response = json_decode($request->getBody()->getContents(), true);

        $this->paymentRecord->addLog('created', $response);

        return [
            'mode'   => $mode,
            'iframe' => $response['payment-redirect-url'],
        ];
    }

    public function postNotification()
    {
        $response = $this->getIPNResponse();
        $state = $response['payment']['transaction-state'] ?? null;

        if ($state === 'success') {
            $description = "CheckoutPortal " . $response['payment']['transaction-id'];
            $this->approvePayment($description, $response, $response['payment']['transaction-id']);

            return;
        }

        if ($state === 'canceled') {
            $this->getPaymentRecord()->addLog('canceled', $response);

            return;
        }

        if ($state === 'error') {
            $this->getPaymentRecord()->addLog('error', $response);

            return;
        }

        if ($state === 'failed') {
            $this->getPaymentRecord()->addLog('failed', $response);

            return;
        }
    }

    private function getIPNResponse()
    {
        $data = post()->all();

        if (!isset($data['response-signature-base64']) || !isset($data['response-signature-algorithm']) ||
            !isset($data['response-base64'])) {
            throw new Exception('Missing request data');
        }

        $secretKey = $this->environment->config('checkout-portal.secret');
        $sig = hash_hmac('sha256', $data['response-base64'], $secretKey, true);

        if (!hash_equals($sig, base64_decode($data['response-signature-base64']))) {
            $this->getPaymentRecord()->addLog('missmatch', $data);

            return;
            throw new Exception('Signature missmatch');
        }

        $response = json_decode(base64_decode($data['response-base64']), true);
        $requestId = $response['payment']['request-id'] ?? null;

        $this->getPaymentRecord()->addLog('notification', $response);

        if (strpos($requestId, $this->getPaymentRecord()->hash) === false) { // $hash, $hash-check-enrollment
            throw new Exception('Payment id missmatch: ' . $requestId);
        }

        return $response;
    }

    public function postSuccess()
    {
        $response = $this->getIPNResponse();

        $state = $response['payment']['transaction-state'] ?? null;

        if ($state != 'success') {
            throw new Exception('Not successful state');
        }

        $ok = collect($response['payment']['statuses']['status'] ?? [])->has(function($status) {
            return $status['code'] == '201.0000';
        });

        if (!$ok) {
            throw new Exception('No successful code');
        }
    }

    public function postError()
    {
        $response = $this->getIPNResponse();
    }

    public function postCancel()
    {
        $response = $this->getIPNResponse();
    }

}