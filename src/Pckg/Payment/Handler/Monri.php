<?php namespace Pckg\Payment\Handler;

use Throwable;

class Monri extends AbstractHandler implements Handler
{

    protected $clientSecret;

    protected $handler = 'stripe';

    /**
     * @return string
     * Used on frontend form.
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    public function getTotalToPay()
    {
        return round($this->order->getTotalToPay() * 100);
    }

    /**
     * @return string
     * Prepare Stripe processor for payment.
     */
    public function initPayment()
    {
        $responseData = null;
        try {
            \Stripe\Stripe::setApiKey(config('pckg.payment.provider.stripe.secret'));

            $intent = \Stripe\PaymentIntent::create([
                'amount' => $this->getTotalToPay(),
                'currency' => strtolower($this->order->getCurrency()),
            ]);

            $this->paymentRecord->addLog('created', json_encode($intent));

            if (!$intent->client_secret) {
                return [
                    'success' => false,
                    'message' => 'Payment intent error on Stripe',
                ];
            }

            $this->clientSecret = $intent->client_secret;

            $this->paymentRecord->setAndSave([
                'transaction_id' => $intent->id,
            ]);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Stripe payments are not available at the moment: ' . $e->getMessage(),
            ];
        }

        return [
            'clientSecret' => $this->getClientSecret(),
            'publishable' => config('pckg.payment.provider.stripe.publishable'),
        ];
    }

    public function postNotification()
    {
        // You can find your endpoint's secret in your webhook settings
        $endpoint_secret = config('pckg.payment.provider.stripe.signingSecret');

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            response()->code(400);

            return [
                'success' => false,
                'error' => true,
                'message' => 'Unexpected value',
            ];
        } catch (\Stripe\Exception\SignatureVerificationException $e) {

            response()->code(400);

            return [
                'success' => false,
                'error' => true,
                'message' => 'Invalid signature',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => true,
                'message' => 'Other exception',
            ];
        }

        if ($event->type == "payment_intent.succeeded") {
            $intent = $event->data->object;
            $this->approvePayment("Stripe #" . $intent->id, $intent, $intent->id);

            return [
                'success' => true,
            ];
        } elseif ($event->type == "payment_intent.payment_failed") {
            $intent = $event->data->object;
            $this->errorPayment($intent);

            return [
                'success' => false,
            ];
        }
    }

    function forMonri()
    {
        function transactionData($transactionType)
        {
            $sec = new Security();
            $amount = '100';
            $currency = 'EUR';
            $order_number = "monri-components" . $sec->generateRandomString(10);
//digest = SHA512(key + order_number + amount + currency)
            $digest = hash('sha512', $key . $order_number . $amount . $currency
return [
    "transaction_type" => $transactionType,
    "amount" => $amount,
    "ip" => '10.1.10.111',
    'order_info' => 'Monri components trx',
    'ch_address' => 'Adresa',
    'ch_city' => 'Grad',
    'ch_country' => 'BIH',
    'ch_email' => 'test@test.com',
    'ch_full_name' => 'Test',
    'ch_phone' => '061 000 000',
    'ch_zip' => '71000',
    'currency' => $currency,
    'digest' => $digest,
    'order_number' => $order_number,
    'authenticity_token' => $authenticity_token,
    'language' => 'en',
// This part is important! Extract monriToken from post body
    'temp_card_id' => Yii::$app->request->post('monriToken')
];
}

        function transaction($url, $data)
        {
            $data_string = Json::encode(['transaction' => $data]);
// Execute transaction
            $ch = curl_init($url . './v2/transaction');
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
// TODO: handle transaction result
            $result = curl_exec($ch);
            return $result;
        }

        function transactionExample()
        {
            $url = 'https://ipgtest.monri.com'; // change for production env
// Prepare transaction payload, include monriToken as `temp-card-id` fie
            $data = transactionData('authorize');
            return transaction($url, $data);
        }


        // only purchase by
        function transactionExample()
        {
            $url = 'https://ipgtest.monri.com'; // change for production env
// Prepare transaction payload, include monriToken as `temp-card-id` fie
            $data = transactionData('purchase');
            return transaction($url, $data);
        }


        // purchase + tokenize
        function transactionExample()
        {
            $url = 'https://ipgtest.monri.com'; // change for production env
// Prepare transaction payload, include monriToken as `temp-card-id` fie
            $data = transactionData('purchase');
// Add tokenize-pan-until field to `$data`
            $data['tokenize_pan_until'] = '2110'; // YYMM
            return transaction($url, $data);
        }
    }

}