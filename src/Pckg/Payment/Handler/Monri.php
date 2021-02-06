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

            $billingAddress = $t->order->getBillingAddress();
            if ($billingAddress) {
                $data = array_merge($data, [
                    'ch_address' => $billingAddress->address_line1,
                    'ch_city' => $billingAddress->city,
                    'ch_country' => strtoupper($billingAddress->country->code),
                    'ch_full_name' => $billingAddress->name,
                    'ch_phone' => $billingAddress->phone,
                    'ch_zip' => $billingAddress->postal,
                ]);
            }

            return $data;
        };

        $transactionData = $transactionData('purchase');
        $url = $this->environment->config('monri.url');

        $data_string = json_encode(['transaction' => $transactionData]);

        // Execute transaction
        $ch = curl_init($url . '/v2/transaction');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $transaction = curl_exec($ch);

        $decoded = json_decode($transaction, true);
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

    public function postNotification()
    {
    }

}