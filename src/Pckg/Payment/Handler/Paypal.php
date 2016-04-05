<?php namespace Pckg\Payment\Handler;

use Exception;

class Paypal extends AbstractHandler implements Handler
{

    const ACK_SUCCESS = 'Success';
    const CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED = 'PaymentActionNotInitiated';
    const PAYMENTACTION = 'Sale';

    public function initHandler()
    {
        $this->config = [
            'username'   => $this->environment->config('paypal.username'),
            'password'   => $this->environment->config('paypal.password'),
            'signature'  => $this->environment->config('paypal.signature'),
            'url'        => $this->environment->config('paypal.url'),
            'url_token'  => $this->environment->config('paypal.url_token'),
            'url_return' => $this->environment->config('paypal.url_return'),
            'url_cancel' => $this->environment->config('paypal.url_cancel'),
        ];

        return $this;
    }

    public function start()
    {
        $fields = [
            'METHOD'       => 'SetExpressCheckout',
            'RETURNURL'    => $this->environment->url($this->config['url_return'],
                ['paypal', $this->order->getOrder()]),
            'CANCELURL'    => $this->environment->url($this->config['url_cancel'],
                ['paypal', $this->order->getOrder()]),
            'NOSHIPPING'   => '1',
            'ALLOWNOTE'    => '0',
            'ADDROVERRIDE' => '0',
        ];

        $fields = array_merge($fields, $this->fetchOrderData());

        $response = $this->makeRequest($fields);

        if ($response['ACK'] == static::ACK_SUCCESS) {
            $url = str_replace('[token]', $response['TOKEN'], $this->config['url_token']);
            $this->environment->redirect($url);
        }

        return $response;
    }

    public function makeRequest($fields)
    {
        $fields = array_merge($fields, $this->getApiCredentials());
        $postFields = $this->stringifyFields($fields);

        $options = array(
            CURLOPT_URL            => $this->config['url'],
            CURLOPT_HEADER         => false,
            CURLOPT_VERBOSE        => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FAILONERROR    => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postFields,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 30,
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if (!$response) {
            throw new Exception("Request has failed! ($error)");
        }

        $responseArray = explode('&', $response);
        $response = array();
        foreach ($responseArray as $val) {
            list($key, $val) = explode('=', $val, 2);
            $response[$key] = urldecode($val);
        }

        return $response;
    }

    public function stringifyFields($fields)
    {
        $postFields = [];
        foreach ($fields as $key => $val) {
            $postFields[] = $key . '=' . urlencode($val);
        }
        return implode('&', $postFields);
    }

    public function getApiCredentials()
    {
        return [
            'VERSION'   => '64.0',
            'USER'      => $this->config['username'],
            'PWD'       => $this->config['password'],
            'SIGNATURE' => $this->config['signature'],
        ];
    }

    public function fetchOrderData()
    {
        $total = $this->order->getTotal();
        $productsSum = 0.0;
        $products = [];
        foreach ($this->order->getProducts() as $product) {
            $productsSum += $product->getTotal();
            $products[] = [
                'NAME' => $product->getName(),
                'AMT'  => $product->getPrice(),
                'QTY'  => $product->getQuantity(),
            ];
        }

        if ($deliveryPrice = $this->order->getDelivery()) {
            $products[] = [
                'NAME' => 'Delivery',
                'AMT'  => $deliveryPrice,
                'QTY'  => 1,
            ];
            $productsSum += $deliveryPrice;
        }

        if ($discount = round($productsSum - $total)) {
            $products[] = [
                'NAME' => 'Discount',
                'AMT'  => 0 - $discount,
                'QTY'  => 1,
            ];
            $productsSum -= $discount;
        }

        $fields = [
            'PAYMENTREQUEST_0_PAYMENTACTION' => static::PAYMENTACTION,
            'PAYMENTREQUEST_0_AMT'           => $total,
            'PAYMENTREQEUST_0_CURRENCYCODE'  => $this->order->getCurrency(),
            'PAYMENTREQEUST_0_ITEMAMT'       => $productsSum,
        ];

        foreach ($products as $i => $product) {
            $fields['L_PAYMENTREQUEST_0_NAME' . $i] = $product['NAME'];
            $fields['L_PAYMENTREQUEST_0_AMT' . $i] = $product['AMT'];
            $fields['L_PAYMENTREQUEST_0_QTY' . $i] = $product['QTY'];
        }

        return $fields;
    }

    public function success()
    {
        $token = $this->environment->request('token');
        $fields = [
            'METHOD' => 'GetExpressCheckoutDetails',
            'TOKEN'  => $token,
        ];

        $response = $this->makeRequest($fields);

        if ($response['CHECKOUTSTATUS'] == static::CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED && isset($response['PAYERID'])) {
            $fields = array(
                'METHOD'        => 'DoExpressCheckoutPayment',
                'TOKEN'         => $token,
                'PAYMENTACTION' => static::PAYMENTACTION,
                'PAYERID'       => $response['PAYERID'],
                'AMT'           => $response['AMT'],
                'CURRENCYCODE'  => $response['CURRENCYCODE'],
            );

            $response = $this->makeRequest($fields);

            if ($response['ACK'] == static::ACK_SUCCESS) {
                $this->order->setPaid();
            }
        }
    }

    public function error()
    {

    }

}