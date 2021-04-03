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

    /**
     * @return float|string
     */
    public function getTotalToPay()
    {
        return round(parent::getTotalToPay() * 100); // in cents
    }

    /**
     * @return string
     */
    public function postStart()
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
                    'ch_phone' => $billingAddress->phone,
                    'ch_zip' => $billingAddress->postal,
                ]);
            }

            return $data;
        };

        $transactionData = $transactionData('purchase');

        $decoded = $this->callCurl('/v2/transaction', ['transaction' => $transactionData]);

        if (isset($decoded['secure_message'])) {
            return [
                'success' => true,
                'threeDSecure' => [
                    'action' => $decoded['secure_message']['acs_url'],
                    'PaReq' => $decoded['secure_message']['pareq'],
                    'TermUrl' => null,
                    'MD' => $decoded['secure_message']['authenticity_token'],
                ],
            ];
        }

        if ($decoded['transaction']['response_message'] === 'approved') {
            $this->approvePayment('Monri #' . $decoded['transaction']['id'], $transaction, $decoded['transaction']['id']);
            return [
                'success' => true,
                'modal' => 'success',
            ];
        }

        $this->errorPayment($transaction);

        return [
            'success' => false,
            'modal' => 'error',
            'data' => $decoded,
        ];
    }

    protected function callCurl($endpoint, $postData)
    {
        $url = $this->environment->config('monri.url');

        $data_string = json_encode($postData);

        // Execute transaction
        $ch = curl_init($url . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $transaction = curl_exec($ch);

        return json_decode($transaction, true);
    }

    public function postNotification()
    {
        $approvalCode = post('approval_code');
        $digest = post('digest');
        $orderNumber = post('order_number');
        $responseCode = post('response_code');

        // payment should be auto resolved here?
        if (!$digest || !$approvalCode || !$orderNumber) {
            return;
        }

        try {
            $url = 'https://' . server('SERVER_NAME') . dirname(server('REQUEST_URI')) . '?' . server('QUERY_STRING');
            $url = parse_url(preg_replace('/&digest=[^&]*/', '', $url));
            $url = 'https://' . $url['host'] . $url['path'] . '?' . $url['query'];
            $checkdigest = hash('sha512', $this->environment->config('monri.apiKey') . $url);

            if ($this->paymentRecord->status === 'approved') {
                $this->paymentRecord->addLog('approved', post()->all());
                return [
                    'success' => true,
                    'issuerCode' => $responseCode,
                    'note' => 'Already approved',
                ];
            }

            if (!$digest !== $checkdigest) {
                $this->paymentRecord->addLog('error', post()->all());
                throw new Exception('Digest mismatch');
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

            return [
                'success' => false,
                'issuerCode' => $responseCode,
            ];
        } catch (Exception $e) {
            error_log('MONRI PAYMENT EXCEPTION: ' . exception($e));
        }
    }

    /**
     * Check for valid 3dsecure response
     **/
    function postNotification2()
    {
        $paRes = post('PaRes');
        if (!$paRes) {
            return;
        }

        $resultXml = $this->callCurl('/pares', ['secure_message' => ['MD' => post('MD'), 'PaRes' => post('PaRes')]]);

        if (($resultXml['status'] ?? null) !== 'approved') {
            $this->errorPayment($resultXml);

            return [
                'success' => false,
            ];
        }

        $this->approvePayment($resultXml);

        return [
            'success' => true,
        ];
    }
}
