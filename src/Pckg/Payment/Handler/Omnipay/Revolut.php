<?php namespace Pckg\Payment\Handler\Omnipay;

use Omnipay\Revolut\Gateway;
use Pckg\Payment\Handler\ManagesWebhooks;
use Stripe\WebhookEndpoint;

/**
 * @property Gateway $client
 */
class Revolut extends AbstractOmnipay implements ManagesWebhooks
{

    /**
     * @var string
     */
    protected $gateway = Gateway::class;

    /**
     * @var string
     */
    protected $handler = 'revolut';

    /**
     * @return string[]
     */
    public function getOmnipayConfigKeys()
    {
        return ['accessToken', 'accountId'];
    }

    /**
     * @return array
     */
    public function getOmnipayConfig()
    {
        $config = parent::getOmnipayConfig();
        $config['language'] = strtoupper(localeManager()->getDefaultFrontendLanguage()->slug ?? 'EN');

        return $config;
    }

    /**
     * @return bool
     */
    public function isTestMode()
    {
        return $this->environment->config('revolut.endpoint') === 'https://sandbox-merchant.revolut.com/api/1.0';
    }

    public function getOmnipayOrderDetails()
    {
        $delivery = $this->order->getDeliveryAddress();

        return [
                'email' => $this->getOmnipayCustomer()['email'] ?? null,
                'merchantOrderReference' => $this->paymentRecord->hash,
                'customData' => [
                    'shipping_address' => [
                        'country_code' => $delivery->country->getISO2(),
                        'postcode' => $delivery->postal,
                        'street_line_1' => $delivery->address_line1,
                        'street_line_2' => $delivery->address_line2,
                        'street_line_3' => $delivery->region,
                        'city' => $delivery->city,
                    ],
                ],
            ] + parent::getOmnipayOrderDetails();
    }

    public function postSuccess()
    {
        //
        // return $this->completePurchase();
    }

    public function initPayment()
    {
        $data = parent::initPayment();

        if (!($data['success'] ?? true)) {
            return $data;
        }

        return [
            'public_id' => $data['public_id'],
            'environment' => $this->isTestMode() ? 'sandbox' : 'prod',
            'formData' => $this->getRevolutFormData(),
        ];
    }

    public function getRevolutFormData()
    {
        $address = $this->order->getBillingAddress() ?? $this->order->getDeliveryAddress();

        return [
            'name' => $address->name,
            'email' => $this->order->getCustomer()->getEmail(),
            'billingAddress' => [
                'countryCode' => $address->country->getISO2(),
                'postcode' => $address->postal,
                'streetLine1' => $address->address_line1,
                'streetLine2' => $address->address_line2,
                'streetLine3' => $address->region,
                'city' => $address->city,
            ]
        ];
    }

    public function getWebhooks(): array
    {
        return $this->client->getWebhooks()->sendData()->getData();
    }

    public function postWebhook(): bool
    {
        $url = "https://" . config('identifier') . ".id.startcomms.com/payment/revolut/notification";
        $events = ["ORDER_COMPLETED", "ORDER_AUTHORISED", "ORDER_PAYMENT_AUTHENTICATED", "ORDER_PAYMENT_DECLINED", "ORDER_PAYMENT_FAILED"];

        return $this->client->postWebhook()->sendData([
            "url" => $url,
            "events" => $events,
        ])->isSuccessful();
    }
}