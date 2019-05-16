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
     * @return $this|AbstractHandler
     */
    public function initHandler()
    {
        /*Configuration::environment($this->environment->config('braintree.environment'));
        Configuration::merchantId($this->environment->config('braintree.merchant'));
        Configuration::publicKey($this->environment->config('braintree.public'));
        Configuration::privateKey($this->environment->config('braintree.private'));*/

        return $this;
    }

    /**
     * @return array|AbstractHandler
     */
    public function initPayment()
    {
        $client = new Client();

        $mode = 'embedded';
        $username = '70000-APIDEMO-CARD';
        $password = 'ohysS0-dvfMx';
        $merchantAccount = '7a6dd74f-06ab-4f3f-a864-adc52687270a'; // MAID

        /*$mode = 'seamless';
        $username = '70000-APILUHN-CARD';
        $password = '8mhwavKVb91T';
        $merchantAccount = 'cad16b4a-abf2-450d-bcb8-1725a4cef443';*/

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

        $url = 'https://wpp-test.wirecard.com/api/payment/register';

        $data = [
            'payment' => [
                'merchant-account-id'  => [
                    'value' => $merchantAccount,
                ],
                'request-id'           => $this->paymentRecord->id,
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
                'three-d'              => [
                    'attempt-three-d' => 'true',
                ],
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

        $request = $client->post($url, [
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
    }

    private function getIPNResponse()
    {
        $data = post()->all();

        if (!isset($data['response-signature-base64']) || !isset($data['response-signature-algorithm']) ||
            !isset($data['response-base64'])) {
            throw new Exception('Missing request data');
        }

        $secretKey = 'b3b131ad-ea7e-48bc-9e71-78d0c6ea579d';
        $sigBase64 = base64_encode(hash_hmac('sha256', $data['response-base64'], $secretKey, true));

        if ($data['response-signature-base64'] !== $sigBase64) {
            throw new Exception('Signature missmatch');
        }

        $response = base64_decode($data['response-base64']);
        $requestId = $response['payment']['request-id'] ?? null;

        if ($requestId != $this->getPaymentRecord()->id) {
            throw new Exception('Payment id missmatch');
        }

        return $response;
    }

    public function postSuccessAction()
    {
        $response = $this->getIPNResponse();

        $state = $response['payment']['transaction-state'] ?? null;

        if ($state != 'success') {
            throw new Exception('Not successful state');
        }

        $ok = collect($response['statuses']['status'] ?? [])->has(function($status) {
            return $status['code'] == '201.0000';
        });

        if (!$ok) {
            throw new Exception('No successful code');
        }
    }

    public function postErrorAction()
    {
        $response = $this->getIPNResponse();
    }

    public function postCancelAction()
    {
        $response = $this->getIPNResponse();
    }

}