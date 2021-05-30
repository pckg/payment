<?php namespace Pckg\Payment\Handler;

use Carbon\Carbon;
use Derive\Basket\Service\Summary\Item\Item;
use GuzzleHttp\Client;
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

    public function getStart()
    {
        try {
            $client = new Client();
            $endpoint = $this->environment->config('leanpay.url');
            $billingAddress = $this->order->getBillingAddress();

            $response = $client->post($url . '/vendor/token', [
                'vendorApiKey' => $this->environment->config('leanpay.apiKey'),
                'vendorTransactionId' => $this->getPaymentRecord()->hash,
                'amount' => $this->getTotalToPay(),
                'successUrl' => $this->getSuccessUrl(),
                'errorUrl' => $this->getErrorUrl(),
                'vendorPhoneNumber' => $billingAddress->phone ?? null,
                'vendorFirstName' => $billingAddress->name ?? null,
                'vendorFirstName' => $billingAddress->surname ?? null,
                'vendorAddress' => $billingAddress->address_line1 ?? null,
                'vendorZip' => $billingAddress->postal ?? null,
                'vendorCity' => $billingAddress->city ?? null,
                'language' => localeManager()->getDefaultFrontendLanguage()->slug ?? 'sl',
                'CartItems' => collect($this->order->getOrder()->getEstimate()->getItems())->map(function (Item $item) {
                    return [
                        'name' => $item->getTitle(),
                        'sku' => $item->getSku() ?? $item->getGtin(),
                        'price' => $item->getPrice(),
                        'qty' => $item->getQuantity(),
                        'lpProdCode' => 1, // @T00D00
                    ];
                })->all(),
            ]);
            $json = json_decode($response->getBody()->getContents(), true);
            if (!isset($json['token'])) {
                throw new \Exception('Leanpay token is not set');
            }

            return [
                'token' => $json['token'],
                'url' => $endpoint . '/vendor/checkout',
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Leanpay payments are not available at the moment.',
            ];
        }
    }
}
