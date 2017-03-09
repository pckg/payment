<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Exception;
use Pckg\Payment\Entity\Paypal;
use Pckg\Payment\Record\Paypal as PaypalRecord;

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

        $ppPaymentHash = sha1(microtime() . $this->order->getIdString());
        $accessToken = $this->getAccessToken();

        $arrData = [
            "intent"        => "sale",
            "redirect_urls" => [
                "return_url" => url(
                    'derive.payment.check',
                    [
                        'handler' => 'paypal',
                        'payment' => $ppPaymentHash,
                        'order'   => $this->order->getOrder(),
                    ],
                    true
                ),
                "cancel_url" => url(
                    'derive.payment.cancel',
                    ['handler' => 'paypal', 'payment' => $ppPaymentHash, 'order' => $this->order->getOrder()],
                    true
                ),
            ],
            "payer"         => [
                "payment_method" => "paypal",
            ],
            "transactions"  => [
                [
                    "amount"      => [
                        "total"    => $price,
                        "currency" => "EUR",
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

        if (empty($result)) {
            return "Empty result" . ($result);
        } else {
            $json = json_decode($result);

            if (isset($json->state) && $json->state == "created") {
                PaypalRecord::create(
                    [
                        "order_id"    => $this->order->getId(),
                        "user_id"     => auth('frontend')->getUser()->id,
                        "order_hash"  => $this->order->getOrder()->hash,
                        "paypal_hash" => $ppPaymentHash,
                        "paypal_id"   => $json->id,
                        "status"      => "started",
                        "price"       => $price,
                        "data"        => json_encode(
                            [
                                'billIds' => $this->order->getBills()->map('id'),
                            ]
                        ),
                    ]
                );

                response()->redirect($json->links[1]->href);
            } else {
                echo '<pre>';
                print_r($json);
                echo '</pre>';
                die();
            }
        }
    }

    function check()
    {
        $paypal = (new Paypal())->where('paypal_hash', router()->get('payment'))->oneOrFail();
        $accessToken = $this->getAccessToken();

        $arrData = [
            "payer_id" => $_GET['PayerID'],
        ];

        $ch = curl_init();

        curl_setopt(
            $ch,
            CURLOPT_URL,
            "https://" . $this->config['endpoint'] . "/v1/payments/payment/" . $paypal->paypal_id . "/execute/"
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

        if (empty($result)) {
            throw new Exception(__('error_title_cannot_execute_order'));
        } else {
            $json = json_decode($result);

            if (isset($json->state)) { // unknown error
                $paypal->set(
                    [
                        "status"          => $json->state,
                        "paypal_payer_id" => $_GET['PayerID'],
                    ]
                )->save();

                /**
                 * Handle successful payment.
                 */
                if ($json->state == "approved") {
                    $this->order->getBills()->each(
                        function(OrdersBill $ordersBill) use ($paypal, $result) {
                            $ordersBill->confirm(
                                "Paypal " . $paypal->paypal_id . ' ' . $result,
                                'paypal'
                            );
                        }
                    );
                }

                if ($json->state == "pending") {
                    response()->redirect(
                        url('derive.payment.waiting', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
                    );
                    /**
                     * Debug::addWarning(
                     * "Status naročila je <i>$json->state</i>. Ko bo naročilo potrjeno, vas bomo obvestili preko email naslova."
                     * );*/
                } else if ($json->state == "approved") {
                    response()->redirect(
                        url('derive.payment.success', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
                    );
                } else {
                    response()->redirect(
                        url('derive.payment.error', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
                    );
                    /**
                     * Debug::addError(__('error_title_unknown_payment_status'));
                     *
                     */
                }
            } else {
                /*var_dump($json);
                echo '<pre>';
                print_r($json);
                echo '</pre>';
                echo $result;
                die("failed");*/
                response()->redirect(
                    url('derive.payment.error', ['handler' => 'paypal', 'order' => $this->order->getOrder()])
                );
                /*Debug::addError(__('error_title_order_confirmation_failed'));*/
            }
        }
    }

    public function success()
    {

    }

    public function error()
    {
        
    }

}