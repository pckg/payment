<?php namespace Pckg\Payment\Handler;

use GuzzleHttp\Client;
use Pckg\Framework\Exception;

class VivaWallet extends AbstractHandler implements Handler
{

    /**
     * @var string
     */
    protected $handler = 'vivawallet';

    /**
     * @var Client
     */
    protected $client;

    public function initHandler()
    {
        $url = $this->environment->config('viva-wallet.url');
        $this->config = [
            'url' => $url,
            'merchantId' => $this->environment->config('viva-wallet.merchantId'),
            'apiKey' => $this->environment->config('viva-wallet.apiKey'),
            'apiCode' => $this->environment->config('viva-wallet.apiCode'),
            'clientId' => $this->environment->config('viva-wallet.clientId'),
            'clientSecret' => $this->environment->config('viva-wallet.clientSecret'),
            'tokenUrl' => strpos($url, 'demo')
                ? 'https://demo-accounts.vivapayments.com/'
                : 'https://accounts.vivapayments.com/',
        ];

        $this->client = new Client([
            'headers' => [
                'Accept' => 'application/json'
            ],
        ]);

        return $this;
    }

    public function getBearer()
    {
        $basic = base64_encode($this->config['clientId'] . ':' . $this->config['clientSecret']);
        $response = $this->client->post($this->config['tokenUrl'] . 'connect/token', ['headers' => [
            'Authorization' => 'Basic ' . $basic,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ], 'form_params' => ['grant_type' => 'client_credentials']]);
        $decoded = json_decode($response->getBody()->getContents(), true);
        return $decoded['access_token'] ?? null;
    }

    public function getToken()
    {
        return base64_encode($this->config['merchantId'] . ':' . $this->config['apiKey']);
    }

    public function getTotalToPay()
    {
        return round(parent::getTotalToPay() * 100);
    }

    public function postStart()
    {
        $currency = $this->getCurrency();

        $currencyCode = null;
        switch ($currency_code) {
            case 'EUR':
                $currencyCode = 978;
                break;
            case 'GBP':
                $currencyCode = 826;
                break;
            case 'BGN':
                $currencyCode = 975;
                break;
            case 'RON':
                $currencyCode = 946;
                break;
        }

        if (!$currency) {
            return [
                'success' => false,
                'message' => $currency . ' is supported as currency'
            ];
        }

        $customer = $this->order->getCustomer();

        $data = [
            'email' => $customer->getEmail(),
            'phone' => '070443244',
            'fullName' => $customer->getFullName(),
            'requestLang' => 'EN',
            'allowRecurring' => false,
            'isPreAuth' => false,
            'amount' => $this->getTotalToPay(),
            'currencyCode' => $currencyCode,
            'merchantTrns' => $this->paymentRecord->hash,
            'customerTrns' => $this->getDescription(),
            'disableCash' => true,
            'sourceCode' => $this->config['apiCode'],
        ];

        $url = $this->config['url'] . 'api/orders';
        $bearer = $this->getToken();
        $response = $this->client->post($url, [
            'json' => $data,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $bearer
            ]
        ]);
        $code = $response->getStatusCode();
        if ($code !== 200) {
            return [
                'error' => true,
                'modal' => 'error',
                'message' => 'Error initiating payment',
            ];
        }
        $content = $response->getBody()->getContents();
        $decoded = json_decode($content, true);
        if ($decoded['ErrorCode'] === 0) {
            $this->paymentRecord->addLog('started', $decoded);
            $this->paymentRecord->setAndSave(['payment_id' => $decoded['OrderCode']]);

            return [
                'redirect' => $this->config['url'] . 'web/checkout?ref=' . $decoded['OrderCode'],
                'success' => true,
            ];
        }

        return [
            'success' => false,
            'message' => 'VivaWallet error',
            'json' => $decoded,
        ];
    }

    public function getCompanyNotification()
    {
        $url = $this->config['url'] . 'api/messages/config/token';
        $bearer = $this->getToken();
        $response = $this->client->get($url, [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $bearer
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function postCompanyNotification()
    {
        $this->paymentRecord->addLog('postCompanyNotification', post()->all());

        $eventTypeId = post('EventTypeId', null);
        if ($eventTypeId === 1796) {
            $transactionId = post('EventData.TransactionId', null);
            $this->approvePayment('VivaWallet #' . $transactionId, post()->all(), $transactionId);

            return [
                'success' => true
            ];
        } elseif ($eventTypeId === 1797) {
            /**
             * Refund a transaction?
             */

            return [
                'success' => true,
            ];
        }

        throw new Exception('Notification method event not supported.');
    }

}