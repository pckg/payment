<?php

namespace Pckg\Payment\Handler;

use Carbon\Carbon;
use Derive\Basket\Service\Summary\Item\Item;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Throwable;

/**
 * Class Leanpay
 * @package Pckg\Payment\Handler
 */
class Leanpay extends AbstractHandler implements Handler
{
    /**
     * @var string
     */
    protected $handler = 'leanpay';

    private function makeAmount($price)
    {
        if ((int)$price == $price) {
            return number_format((int)$price, 0, '', '');
        }

        return number_format((float)$price, 2, '.', '');
    }

    public function initPayment()
    {
        try {
            $client = new Client();
            $endpoint = $this->environment->config('leanpay.url');
            $billingAddress = $this->order->getBillingAddress();

            $postData = [
                'vendorApiKey' => $this->environment->config('leanpay.apiKey'),
                'vendorTransactionId' => $this->getPaymentRecord()->hash,
                'amount' => $this->makeAmount($this->getTotalToPay()),
                'successUrl' => $this->getSuccessUrl(),
                'errorUrl' => $this->getErrorUrl(),
                // set notification url in Leanpay Dashboard > Company > Development > API URL
                'vendorPhoneNumber' => $billingAddress->phone ?? null,
                'vendorFirstName' => $billingAddress->name ?? null,
                'vendorLastName' => $billingAddress->surname ?? null,
                'vendorAddress' => $billingAddress->address_line1 ?? null,
                'vendorZip' => $billingAddress->postal ?? null,
                'vendorCity' => $billingAddress->city ?? null,
                'language' => localeManager()->getDefaultFrontendLanguage()->slug ?? 'sl',
                'vendorProductCode' => '',
                'CartItems' => collect($this->order->getBills()[0]->order->getEstimate()->getItems())->map(function (Item $item) {
                    return [
                        'name' => $item->getTitle(),
                        'sku' => $item->getSku() ?? ($item->getGtin() ?? $item->getTitle()),
                        'price' => $this->makeAmount($item->getPrice()),
                        'qty' => $item->getQuantity(),
                        'lpProdCode' => '',
                    ];
                })->all(),
            ];
            $response = $client->post($endpoint . '/vendor/token', [RequestOptions::JSON => $postData]);
            $json = json_decode($response->getBody()->getContents(), true);
            if (!isset($json['token'])) {
                throw new \Exception('Leanpay token is not returned in response');
            }

            return [
                'token' => $json['token'],
                'url' => $endpoint . '/vendor/checkout',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Leanpay payments are not available at the moment.' . exception($e),
            ];
        }
    }

    public function validateSignature(array $data)
    {
        $md5Signature = md5(
            implode(
                [
                    $data['leanPayTransactionId'],
                    $data['vendorTransactionId'],
                    md5($this->environment->config('leanpay.apiSecret')),
                    $data['amount'],
                    $data['status']
                ]
            )
        );

        if ($md5Signature === $data['md5Signature']) {
            return true;
        }

        throw new \Exception('Leanpay signature missmatch!');
    }

    public function postNotification()
    {
        $data = collect(post()->all())->only(['leanPayTransactionId', 'vendorTransactionId', 'amount', 'status', 'md5Signature'])->all();
        $this->validateSignature($data);

        if ($data['status'] === 'SUCCESS') {
            $tid = $data['leanPayTransactionId'];
            $this->approvePayment('Leanpay #' . $tid, $data, $tid);

            return [
                'success' => true,
            ];
        }

        if ($data['status'] === 'CANCELED') {
            $this->cancelPayment($data);

            return [
                'success' => false,
                'message' => 'Payment canceled',
            ];
        }

        $this->errorPayment($data);
    }
}
