<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Exception;

class PaypalGnp extends AbstractHandler implements Handler
{

    const ACK_SUCCESS = 'Success';

    const CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED = 'PaymentActionNotInitiated';

    const PAYMENTACTION = 'Sale';

    public function initHandler()
    {
        $this->config = [
            'endpoint' => $this->environment->config('paypal.endpoint'),
            'client'   => $this->environment->config('paypal.client'),
            'secret'   => $this->environment->config('paypal.secret'),
        ];

        return $this;
    }

    function getAccessToken()
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

    public function startPartial()
    {
        $price = $this->order->getTotal();
        $accessToken = $this->getAccessToken();

        $arrData = [
            "intent"        => "sale",
            "redirect_urls" => [
                "return_url" => url(
                    'derive.payment.check',
                    [
                        'handler' => 'paypal',
                        'payment' => $this->paymentRecord,
                        'order'   => $this->order->getOrder(),
                    ],
                    true
                ),
                "cancel_url" => url(
                    'derive.payment.cancel',
                    ['handler' => 'paypal', 'payment' => $this->paymentRecord, 'order' => $this->order->getOrder()],
                    true
                ),
                // 'notify_url' => [],
            ],
            "payer"         => [
                "payment_method" => "paypal",
            ],
            "transactions"  => [
                [
                    "amount"      => [
                        "total"    => $price,
                        "currency" => config('pckg.payment.currency'),
                    ],
                    "description" => __('order_payment') . " #" . $this->order->getId() .
                                     ' (' . $this->order->getNum() . ' - ' .
                                     $this->order->getBills()->map('id')->implode(',') . ')',
                ],
            ],
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://" . $this->config['endpoint'] . "/v1/payments/payment");
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json",
            ]
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            throw new Exception('Paypal payments are not available at the moment.');
        }

        $json = json_decode($result);

        if (isset($json->state) && $json->state == "created") {
            $this->paymentRecord->setAndSave(['payment_id' => $json->id]);

            response()->redirect($json->links[1]->href);
        } else {
            echo '<pre>';
            print_r($json);
            echo '</pre>';
            die();
        }
    }

    function check()
    {
        $accessToken = $this->getAccessToken();

        $arrData = [
            "payer_id" => get('PayerID'),
        ];

        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://" . $this->config['endpoint'] . "/v1/payments/payment/" . $this->paymentRecord->payment_id .
            "/execute/"
        );
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                "Authorization: Bearer " . $accessToken,
                "Content-Type: application/json",
            ]
        );
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

            response()->redirect(
                url('derive.payment.error', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
            );
        }

        $this->paymentRecord->setAndSave(
            [
                "status"         => $json->state,
                "transaction_id" => $json->id,
            ]
        );

        /**
         * Handle successful payment.
         */
        if ($json->state == "approved") {
            $paypal = $this->paymentRecord;
            $this->order->getBills()->each(
                function(OrdersBill $ordersBill) use ($paypal, $result) {
                    $ordersBill->confirm(
                        "Paypal " . $paypal->payment_id,
                        'paypal'
                    );
                }
            );
        }

        /**
         * Handle other payments.
         */
        if ($json->state == "pending") {
            response()->redirect(
                url('derive.payment.waiting', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
            );
        } else if ($json->state == "approved") {
            response()->redirect(
                url('derive.payment.success', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
            );
        }

        /**
         * Redirect on error.
         */
        response()->redirect(
            url('derive.payment.error', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
        );
    }

    public function success()
    {
    }

    public function error()
    {
    }

}