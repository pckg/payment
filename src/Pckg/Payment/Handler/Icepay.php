<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Exception;
use Icepay\API\Client;
use Pckg\Collection;
use Pckg\Payment\Record\Icepay as IcepayRecord;
use Pckg\Payment\Record\Payment;
use Throwable;

class Icepay extends AbstractHandler implements Handler
{

    protected $braintreeClientToken;

    /**
     * @var Client
     */
    protected $icepay;

    protected $paymentMethod = null;

    protected $issuer = null;

    public function validate($request)
    {
        $rules = [
            'holder'     => 'required',
            'number'     => 'required',
            'exp_month'  => 'required',
            'exp_year'   => 'required',
            'cvc'        => 'required',
            'amount_int' => 'required',
        ];

        if (!$this->environment->validates($request, $rules)) {
            return $this->environment->errorJsonResponse();
        }

        return [
            'success' => true,
        ];
    }

    public function initHandler()
    {
        $this->icepay = new Client();
        $this->icepay->setApiKey($this->environment->config('icepay.merchant'));
        $this->icepay->setApiSecret($this->environment->config('icepay.secret'));

        $this->icepay->setCompletedURL(
            url('derive.payment.success', ['handler' => 'icepay', 'order' => null], true)
        );
        $this->icepay->setErrorURL(
            url('derive.payment.error', ['handler' => 'icepay', 'order' => null], true)
        );

        return $this;
    }

    protected function setUrls()
    {
        $order = $this->order->getOrder();

        $this->icepay->setCompletedURL(
            url('derive.payment.success', ['handler' => 'icepay', 'order' => $order], true)
        );
        $this->icepay->setErrorURL(
            url('derive.payment.error', ['handler' => 'icepay', 'order' => $order], true)
        );
    }

    private function getIcepayDefaultsData()
    {
        $order = $this->order->getOrder();
        $price = $this->getTotal();

        return [
            'Amount'        => $price,
            'Currency'      => 'EUR',
            'Paymentmethod' => $this->paymentMethod,
            'Issuer'        => $this->issuer,
            'Language'      => 'EN',
            'Country'       => '00',
            'Description'   => $this->order->getDescription(),
            'OrderID'       => $order->id . '-' . $this->paymentRecord->id,
            'Reference'     => $this->paymentRecord->id,
            'EndUserIP'     => server('REMOTE_ADDR'),
        ];
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
        $calculatedChecksum = sha1(
            sprintf(
                "%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s|%s",
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
                $this->environment->post('ConsumerIPAddress')
            )
        );

        $checksum = $this->environment->post('Checksum');

        if ($checksum !== $calculatedChecksum) {
            throw new Exception('Checksum missmatch!');
        }
    }

    public function getPaymentMethods()
    {
        return $this->icepay->payment->getMyPaymentMethods();
    }

    public function getPaymentMethod($method)
    {
        return (new Collection($this->getPaymentMethods()->PaymentMethods))->first(
            function($paymentMethod) use ($method) {
                return $paymentMethod->PaymentMethodCode == $method;
            }
        );
    }

    public function postNotification()
    {
        $this->validatePostbackChecksum();

        $status = $this->environment->post('Status');
        $reference = $this->environment->post('Reference');

        $bodyData = (array)$this->environment->post(null);

        $payment = Payment::getOrFail(
            [
                'id' => $reference,
            ]
        );

        $payment->addLog($status == 'OK' ? 'payed' : $status, (array)$bodyData);

        if ($status == 'OK') {
            $this->order->getBills()->each(
                function(OrdersBill $ordersBill) use ($reference) {
                    $ordersBill->confirm(
                        "Ideal #" . $reference,
                        'ideal'
                    );
                }
            );
        }
    }

    public function getValidateUrl()
    {
        return $this->environment->url(
            'payment.validate',
            ['handler' => 'icepay', 'order' => $this->order->getOrder()]
        );
    }

    public function getStartUrl()
    {
        return $this->environment->url(
            'payment.start',
            ['handler' => 'icepay', 'order' => $this->order->getOrder()]
        );
    }

    public function postStartPartial()
    {
        try {
            /**
             * Set completed and error url.
             */
            $this->setUrls();

            /**
             * Create payment request.
             */
            $data = array_merge($this->getIcepayDefaultsData(), $this->getIcepayData());
            $payment = $this->icepay->payment->checkOut($data);

            /**
             * Log payment started.
             */
            $this->paymentRecord->addLog('started', $payment);

            /**
             * Validate response.
             */
            if (!isset($payment->ProviderTransactionID) || !isset($payment->PaymentID)) {
                throw new Exception("Icepay error - " . ($payment->Message ?? 'unknown error'));
            }

            /**
             * Set ids.
             */
            $this->paymentRecord->setAndSave(
                [
                    'transaction_id' => $payment->ProviderTransactionID,
                    'payment_id'     => $payment->PaymentID,
                ]
            );

            /**
             * Redirect to payment page.
             */
            dd($payment->PaymentScreenURL);
            $this->environment->redirect($payment->PaymentScreenURL);
        } catch (Throwable $e) {
            response()->unavailable('Icepay payments are not available at the moment: ' . $e->getMessage());
        }
    }

    public function getIcepayData()
    {
        return [];
    }

}