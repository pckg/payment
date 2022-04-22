<?php namespace Pckg\Payment\Handler;

use Exception;
use Icepay\API\Client;
use Pckg\Collection;
use Pckg\Payment\Record\Payment;
use Throwable;

class Icepay extends AbstractHandler implements Handler
{

    /**
     * @var Client
     */
    protected $icepay;

    protected $paymentMethod = null;

    protected $issuer = null;

    protected $handler = 'icepay';

    public function initHandler()
    {
        $this->icepay = new Client();
        $this->icepay->setApiKey($this->environment->config('icepay.merchant'));
        $this->icepay->setApiSecret($this->environment->config('icepay.secret'));

        $this->icepay->setCompletedURL(url('derive.payment.success',
                                           ['handler' => $this->handler, 'order' => null],
                                           true));
        $this->icepay->setErrorURL(url('derive.payment.error', ['handler' => $this->handler, 'order' => null], true));

        return $this;
    }

    protected function setUrls()
    {
        $this->icepay->setCompletedURL($this->getSuccessUrl());
        $this->icepay->setErrorURL($this->getErrorUrl());
    }

    private function getIcepayDefaultsData()
    {
        $order = $this->order->getOrder();
        $price = $this->getTotal();

        $data = [
            'Amount'        => $price,
            'Currency'      => config('pckg.payment.currency'),
            'Paymentmethod' => $this->paymentMethod,
            'Issuer'        => $this->issuer,
            'Language'      => 'EN',
            'Country'       => '00',
            'Description'   => $this->order->getDescription(),
            'OrderID'       => $this->paymentRecord->id,
            'Reference'     => $this->paymentRecord->id,
            'EndUserIP'     => request()->clientIp(),
        ];

        return $data;
    }

    public function getTotal()
    {
        return round($this->order->getTotal() * 100);
    }

    public function getTotalToPay()
    {
        return round($this->order->getTotalToPay() * 100);
    }

    protected function validatePostbackChecksum()
    {
        $calculatedChecksum = sha1(sprintf("%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
                                           $this->icepay->api_secret,
                                           $this->icepay->api_key,
                                           $this->environment->post('Status'),
                                           $this->environment->post('StatusCode'),
                                           $this->environment->post('OrderID'),
                                           $this->environment->post('PaymentID'),
                                           $this->environment->post('Reference'),
                                           $this->environment->post('TransactionID'),
                                           $this->environment->post('Amount'),
                                           $this->environment->post('Currency'),
                                           $this->environment->post('Duration'),
                                           $this->environment->post('ConsumerIPAddress')));

        $checksum = $this->environment->post('Checksum');

        if ($checksum !== $calculatedChecksum) {
            throw new Exception('Checksum missmatch!');
        }
    }

    public function getPaymentMethods()
    {
        $response = $this->icepay->payment->getMyPaymentMethods();

        return $response;
    }

    public function getPaymentMethod($method)
    {
        return (new Collection($this->getPaymentMethods()->PaymentMethods))->first(function($paymentMethod) use ($method
        ) {
            return $paymentMethod->PaymentMethodCode == $method;
        });
    }

    public function postNotification()
    {
        $this->validatePostbackChecksum();

        $status = $this->environment->post('Status');
        $reference = $this->environment->post('Reference');

        $bodyData = (array)$this->environment->post(null);

        $payment = Payment::getOrFail([
                                          'id' => $reference,
                                      ]);

        $this->setPaymentRecord($payment);

        if ($status == 'OK') {
            $this->approvePayment('Icepay #' . $reference, $bodyData, $this->environment->post('TransactionID'));

            return [
                'success' => true,
            ];
        }

        $this->errorPayment($bodyData, $status);

        return [
            'success' => false,
            'message' => 'Payment error',
        ];
    }

    public function initPayment()
    {
        $config = $this->getPaymentMethod($this->paymentMethod);

        $countries = collect($config->Issuers[0]->Countries ?? [])->keyBy('CountryCode')->map('CountryCode');
        $issuers = collect($config->Issuers ?? [])->keyBy('IssuerKeyword')->map('Description');

        return [
            'countries' => $countries->all(),
            'issuers'   => $issuers->all(),
            'formData'  => [
                'country' => $countries->keys()[0] ?? null,
                'issuer'  => $issuers->keys()[0] ?? null,
            ],
        ];
    }

    public function postStart()
    {
        try {
            /**
             * Set completed and error url.
             */
            $this->setUrls();

            /**
             * Log payment started.
             */
            $this->paymentRecord->addLog('started');

            /**
             * Create payment request.
             */
            $data = array_merge($this->getIcepayDefaultsData(), $this->getIcepayData());
            $payment = $this->icepay->payment->checkOut($data);

            /**
             * Log payment submitted.
             */
            $this->paymentRecord->addLog('submitted', $payment);

            /**
             * Validate response.
             */
            if (!isset($payment->ProviderTransactionID) || !isset($payment->PaymentID)) {
                return [
                    'success' => false,
                    'message' => 'Icepay payment provider error - ' . ($payment->Message ?? 'unknown error'),
                ];
            }

            /**
             * Set ids.
             */
            $this->paymentRecord->setAndSave([
                                                 'transaction_id' => $payment->ProviderTransactionID,
                                                 'payment_id'     => $payment->PaymentID,
                                             ]);

            /**
             * Redirect to payment page.
             */
            $this->paymentRecord->addLog('redirected', $payment->PaymentScreenURL);

            return [
                'success'  => true,
                'redirect' => $payment->PaymentScreenURL,
            ];
        } catch (Throwable $e) {
            $this->paymentRecord->addLog('error');

            return [
                'success' => false,
                'message' => 'Icepay payments are not available at the moment: ' . $e->getMessage(),
            ];
        }
    }

    public function getIcepayData()
    {
        return [];
    }

    public function startIcepayPartialData($formClass, $handler, $fetch = [])
    {
        $form = resolve($formClass);
        $form->setAction(url('derive.payment.postStartPartial',
                             [
                                 'handler' => $handler,
                                 'order'   => $this->order->getOrder(),
                                 'payment' => $this->paymentRecord,
                             ]));

        $config = $this->getPaymentMethod($this->paymentMethod);
        if ($config) {
            if (in_array('country', $fetch)) {
                foreach ($config->Issuers[0]->Countries as $country) {
                    $form->country->addOption($country->CountryCode, $country->CountryCode);
                }
            }

            if (in_array('issuer', $fetch)) {
                foreach ($config->Issuers as $issuer) {
                    $form->issuer->addOption($issuer->IssuerKeyword, $issuer->Description);
                }
            }
        }
    }

}
