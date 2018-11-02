<?php namespace Pckg\Payment\Handler;

use Exception;

class PaypalGnp extends AbstractHandler implements Handler
{

    const ACK_SUCCESS = 'Success';

    const CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED = 'PaymentActionNotInitiated';

    const PAYMENTACTION = 'Sale';

    protected $handler = 'paypal';

    public function initHandler()
    {
        $this->config = [
            'endpoint' => $this->environment->config('paypal.endpoint'),
            'client'   => $this->environment->config('paypal.client'),
            'secret'   => $this->environment->config('paypal.secret'),
        ];

        return $this;
    }

    protected function getAccessToken()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://" . $this->config['endpoint'] . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['client'] . ":" . $this->config['secret']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $result = curl_exec($ch);

        curl_close($ch);

        if (empty($result)) {
            return false;
        } else {
            $json = json_decode($result);

            return $json->access_token;
        }
    }

    public function postStart()
    {
        $price = $this->getTotal();
        $accessToken = $this->getAccessToken();

        $arrData = [
            "intent"        => "sale",
            "redirect_urls" => [
                "return_url" => $this->getCheckUrl(),
                "cancel_url" => $this->getCancelUrl(),
            ],
            "payer"         => [
                "payment_method" => "paypal",
            ],
            "transactions"  => [
                [
                    "amount"      => [
                        "total"    => $price,
                        "currency" => $this->getCurrency(),
                    ],
                    "description" => $this->getDescription(),
                ],
            ],
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://" . $this->config['endpoint'] . "/v1/payments/payment");
        curl_setopt($ch,
                    CURLOPT_HTTPHEADER,
                    [
                        "Authorization: Bearer " . $accessToken,
                        "Content-Type: application/json",
                    ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            return [
                'success' => false,
                'message' => 'Paypal payments are not available at the moment.',
            ];
        }

        $json = json_decode($result);

        if (isset($json->state) && $json->state == "created") {
            $this->paymentRecord->setAndSave(['payment_id' => $json->id]);

            return [
                'success'  => true,
                'redirect' => $json->links[1]->href,
            ];
        }

        return [
            'success' => false,
            'message' => 'Unknown paypal error',
            'info'    => $json,
        ];
    }

    public function check()
    {
        $accessToken = $this->getAccessToken();

        $arrData = [
            "payer_id" => get('PayerID'),
        ];

        $ch = curl_init();

        curl_setopt($ch,
                    CURLOPT_URL,
                    "https://" . $this->config['endpoint'] . "/v1/payments/payment/" . $this->paymentRecord->payment_id . "/execute/");
        curl_setopt($ch,
                    CURLOPT_HTTPHEADER,
                    [
                        "Authorization: Bearer " . $accessToken,
                        "Content-Type: application/json",
                    ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            throw new Exception(__('error_title_cannot_execute_order'));
        }

        $json = json_decode($result);

        if (!isset($json->state)) { // unknown error
            if ($json->name == 'PAYMENT_ALREADY_DONE') {
            }

            return $this->environment->redirect($this->getErrorUrl());
        }

        /**
         * Handle successful payment.
         */
        if ($json->state == "approved") {
            $transaction = end($json->transactions);
            $resource = end($transaction->related_resources);
            $this->approvePayment("Paypal " . $resource->sale->id, $json, $json->id);
            $this->environment->redirect($this->getSuccessUrl());

            return;
        }

        $this->paymentRecord->setAndSave([
                                             "status"         => $json->state,
                                             "transaction_id" => $json->id,
                                         ]);

        if ($json->state == "pending") {
            $this->environment->redirect($this->getWaitingUrl());

            return;
        }

        /**
         * Redirect on error.
         */
        return $this->environment->redirect($this->getErrorUrl());
    }

}