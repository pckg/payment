<?php namespace Pckg\Payment\Handler;

use Carbon\Carbon;
use Throwable;

class Monri extends AbstractHandler implements Handler
{

    protected $handler = 'monri';

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
        ];
    }

    public function getTotalToPay()
    {
        return round(parent::getTotalToPay() * 100); // in cents
    }

    /**
     * @return string
     * Prepare Stripe processor for payment.
     */
    public function postStart()
    {
        $t = $this;
        $transactionData = function($transactionType) use ($t)
        {
            $amount = $t->getTotalToPay();
            $currency = $t->getCurrency();
            $order_number = $t->getPaymentRecord()->hash;
            $apiKey = $t->environment->config('monri.apiKey');
            //digest = SHA512(key + order_number + amount + currency)
            $digest = hash('sha512', $apiKey . $order_number . $amount . $currency);
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
                'authenticity_token' => $t->environment->config('monri.authenticityToken'),
                'language' => 'en',
                // This part is important! Extract monriToken from post body
                'temp_card_id' => post('token'),
            ];
        };

        function transaction($url, $data)
        {
            $data_string = json_encode(['transaction' => $data]);
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

        $transactionData = $transactionData('purchase');
        d($transactionData);
        $url = 'https://ipgtest.monri.com'; // change for production env
        $transaction = transaction($url, $transactionData);
        ddd($transaction);

        /**
         *
         * array(17) {
        ["transaction_type"]=>
        string(8) "purchase"
        ["amount"]=>
        float(1487)
        ["ip"]=>
        string(11) "10.1.10.111"
        ["order_info"]=>
        string(20) "Monri components trx"
        ["ch_address"]=>
        string(6) "Adresa"
        ["ch_city"]=>
        string(4) "Grad"
        ["ch_country"]=>
        string(3) "BIH"
        ["ch_email"]=>
        string(13) "test@test.com"
        ["ch_full_name"]=>
        string(4) "Test"
        ["ch_phone"]=>
        string(11) "061 000 000"
        ["ch_zip"]=>
        string(5) "71000"
        ["currency"]=>
        string(3) "EUR"
        ["digest"]=>
        string(128) "36d5d635683a1fec5fbb097268c4cf9257d1c3e2cdb89783ad9e7ac36558f8c2ce8967721c2c69dabc2e275b3de358378c19ee16cf18289f8e9b73bad809f29d"
        ["order_number"]=>
        string(40) "dev-locala40bad25da6845a169c3af10d4f182e"
        ["authenticity_token"]=>
        string(40) "b5c8f5615d1687b42f2e4388cb86b6749ebb2b88"
        ["language"]=>
        string(2) "en"
        ["temp_card_id"]=>
        string(40) "dev-locala40bad25da6845a169c3af10d4f182e"
        }

        {"transaction":{"id":222889,"acquirer":"integration_acq","order_number":"dev-locala40bad25da6845a169c3af10d4f182e","amount":1487,"currency":"EUR","outgoing_amount":1487,"outgoing_currency":"EUR","approval_code":"417860","response_code":"0000","response_message":"approved","reference_number":"000003047508","systan":"222889","eci":"06","xid":null,"acsv":null,"cc_type":"visa","status":"approved","created_at":"2020-04-09T18:00:42.136+02:00","transaction_type":"purchase","enrollment":"N","authentication":null,"pan_token":null,"issuer":"xml-sim","redirect_url":"?acquirer=integration_acq\u0026amount=1487\u0026approval_code=417860\u0026authentication=\u0026cc_type=visa\u0026ch_full_name=Test\u0026currency=EUR\u0026enrollment=N\u0026issuer=xml-sim\u0026language=en\u0026masked_pan=411111-xxx-xxx-1111\u0026number_of_installments=\u0026order_number=dev-locala40bad25da6845a169c3af10d4f182e\u0026response_code=0000\u0026digest=e2413e2655fc31fbf07c97f6e5e012893d80d71acf14f7375f9fd571dcc52dcb26b46881b6e87607055b1cab90644d197d71f57a1753f4812aaea00eeaedfb2a"}}

         *
         */

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