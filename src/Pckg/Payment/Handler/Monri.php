<?php namespace Pckg\Payment\Handler;

use Carbon\Carbon;
use Throwable;

/**
 * Class Monri
 * @package Pckg\Payment\Handler
 */
class Monri extends AbstractHandler implements Handler
{

    /**
     * @var string
     */
    protected $handler = 'monri';

    /**
     * @return array
     */
    public function initPayment()
    {
        $t = $this;
        $transactionData = function ($transactionType) use ($t) {
            $amount = $t->getTotalToPay();
            $currency = $t->getCurrency();
            $identifier = $t->getPaymentRecord()->hash;
            $apiKey = $t->environment->config('monri.apiKey');
            //digest = SHA512(key + order_number + amount + currency)
            $digest = hash('sha512', $apiKey . $identifier . $amount . $currency);

            $customer = $t->order->getCustomer();

            $data = [
                "transaction_type" => $transactionType,
                "amount" => $amount,
                "ip" => request()->clientIp(),
                'order_info' => $t->order->getDescription(),
                'currency' => $currency,
                'digest' => $digest,
                'order_number' => $identifier,
                'ch_email' => $customer->getEmail(),
                'authenticity_token' => $t->environment->config('monri.authenticityToken'),
                'language' => 'en',
                // This part is important! Extract monriToken from post body
                'temp_card_id' => post('token'),
            ];

            $billingAddress = $t->order->getBillingOrDeliveryAddress();
            if ($billingAddress) {
                $data = array_merge($data, [
                    'ch_address' => $billingAddress->address_line1,
                    'ch_city' => $billingAddress->city,
                    'ch_country' => $billingAddress->country ? $billingAddress->country->getISO2() : '',
                    'ch_full_name' => $billingAddress->name,
                    'ch_phone' => '123123123',//$billingAddress->phone,
                    'ch_zip' => $billingAddress->postal,
                ]);
            }

            return $data;
        };

        $transactionData = $transactionData('purchase');

        try {
            $decoded = $this->callCurl('/v2/payment/new', $transactionData);

            if ($decoded['client_secret'] ?? null) {
                $this->log($decoded);

                return [
                    'url' => $this->environment->config('monri.url'),
                    'authenticityToken' => $this->environment->config('monri.authenticityToken'),
                    'clientSecret' => $decoded['client_secret'],
                    'transactionParams' => [
                        'address' => $transactionData['ch_address'] ?? null,
                        'fullName' => $transactionData['ch_full_name'] ?? null,
                        'city' => $transactionData['ch_city'] ?? null,
                        'zip' => $transactionData['ch_zip'] ?? null,
                        'phone' => $transactionData['ch_phone'] ?? null,
                        'country' => $transactionData['ch_country'] ?? null,
                        'email' => $transactionData['ch_email'] ?? null,
                        'orderInfo' => $transactionData['order_info'] ?? null,
                    ]
                ];
            }

            $this->errorPayment($decoded);

            return [
                'success' => false,
                'message' => 'Monri payments are not available - cannot create payment',
            ];
        } catch (Throwable $e) {
            error_log(exception($e));
            $this->errorPayment(['e' => exception($e)]);

            return [
                'success' => false,
                'message' => 'Monri payments are not available - cannot create payment',
            ];
        }

        $apiKey = $this->environment->config('monri.apiKey');
        $timestamp = (new Carbon())->toISOString();
        $randomToken = $this->getPaymentRecord()->hash;
        $digest = hash('sha512', $apiKey . $randomToken . $timestamp);

        return [
            'authenticityToken' => $this->environment->config('monri.authenticityToken'),
            'digest' => $digest,
            'timestamp' => $timestamp,
            'randomToken' => $randomToken,
            'url' => $this->environment->config('monri.url'),
        ];
    }

    public function postStart()
    {
        $this->log(post()->all());

        return [
            'success' => true,
            'modal' => 'success',
        ];
    }

    /**
     * @return float|string
     */
    public function getTotalToPay()
    {
        return round(parent::getTotalToPay() * 100); // in cents
    }

    protected function callCurl($endpoint, $postData)
    {
        $url = $this->environment->config('monri.url');

        $data_string = json_encode($postData);

        $key = $this->environment->config('monri.apiKey');
        $timestamp = time();
        $digest = hash('sha512', $this->environment->config('monri.apiKey') . $timestamp . $this->environment->config('monri.authenticityToken') . $data_string);
        $authorization = "WP3-v2 " . $this->environment->config('monri.authenticityToken') . " " . $timestamp . " " . $digest;

        // Execute transaction
        $ch = curl_init($url . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string),
                'Authorization: ' . $authorization,
            ]
        );
        $transaction = curl_exec($ch);

        return json_decode($transaction, true);
    }

    public function postNotification()
    {
        $approvalCode = post('approval_code');
        $orderNumber = post('order_number');
        $responseCode = post('response_code');

        // payment should be auto resolved here?
        if (!$approvalCode || !$orderNumber || !$responseCode) {
            throw new \Exception('Missing notification parameter');
        }

        $authorizationHeader = request()->header('Authorization');
        if (!$authorizationHeader) {
            throw new \Exception('Missing authentication header');
        }

        /*if (strpos($authorizationHeader, 'WP3-callback ') !== 0) {
            throw new \Exception('Invalid authentication method');
        }

        [$authMethod, $header] = explode(' ', $authorizationHeader, 2);
        $checkdigest = hash('sha512', $this->environment->config('monri.apiKey') . json_encode(post()->all()));

        if ($digest !== $checkdigest) {
            $this->paymentRecord->addLog('error', post()->all());
            throw new Exception('Digest mismatch');
        }*/

        //$url = 'https://' . server('SERVER_NAME') . dirname(server('REQUEST_URI')) . '?' . server('QUERY_STRING');
        //$url = parse_url(preg_replace('/&digest=[^&]*/', '', $url));
        //$url = 'https://' . $url['host'] . $url['path'] . '?' . $url['query'];

        if ($this->paymentRecord->status === 'approved') {
            $this->paymentRecord->addLog('approved', post()->all());
            return [
                'success' => true,
                'issuerCode' => $responseCode,
                'note' => 'Already approved',
            ];
        }

        if ($responseCode === "0000") {
            $this->approvePayment('Monri #', post()->all(), post('approval_code'));

            // for server?
            return [
                'success' => true,
                'issuerCode' => $responseCode,
            ];
        }

        if ($responseCode === "pending") {
            $this->paymentRecord->addLog('pending', post()->all());

            return [
                'success' => true,
                'issuerCode' => $responseCode,
            ];
        }

        $this->errorPayment(post()->all());

        return parent::postNotification();
    }
}
