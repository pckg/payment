<?php namespace Pckg\Payment\Handler;

use Braintree\Transaction;
use Derive\Orders\Record\OrdersBill;
use Derive\Orders\Record\OrdersUser;
use Exception;
use Icepay\API\Client;
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

        return $this;
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

    public function startPartial()
    {
        $order = $this->order->getOrder();
        $price = $this->getTotal();

        $billIds = $this->order->getBills()->map('id');
        $record = null;

        if (!$order->getIsConfirmedAttribute()) {
            $order->ordersUsers->each(
                function(OrdersUser $ordersUser) {
                    if (!$ordersUser->packet->stock || $ordersUser->packet->stock <= 0) {
                        response()->bad('Sold out!');
                    }
                }
            );
        }

        $paymentRecord = Payment::createForOrderAndMethod(
            $this->order,
            'icepay',
            'ideal',
            [
                'billIds' => $billIds,
            ]
        );

        try {
            $this->icepay->setCompletedURL(
                url('derive.payment.success', ['handler' => 'icepay', 'order' => $order], true)
            );
            $this->icepay->setErrorURL(
                url('derive.payment.error', ['handler' => 'icepay', 'order' => $order], true)
            );

            $payment = $this->icepay->payment->checkOut(
                [
                    'Amount'        => $price,
                    'Currency'      => 'EUR',
                    'Paymentmethod' => 'IDEAL',
                    'Issuer'        => 'ABNAMRO',
                    'Country'       => 'NL',
                    'Language'      => 'EN',
                    'Description'   => 'This is a example description',
                    'OrderID'       => $order->id . '-' . $paymentRecord->id,
                    'Reference'     => $paymentRecord->id,
                    'EndUserIP'     => server('REMOTE_ADDR'),
                ]
            );

            $paymentRecord->addLog('started', $payment);

            if (!isset($payment->ProviderTransactionID) || !isset($payment->PaymentID)) {
                throw new Exception("Icepay error - " . ($payment->Message ?? 'unknown error'));
            }

            $paymentRecord->setAndSave(
                [
                    'transaction_id' => $payment->ProviderTransactionID,
                    'payment_id'     => $payment->PaymentID,
                ]
            );

            $this->environment->redirect($payment->PaymentScreenURL);

        } catch (Throwable $e) {
            response()->unavailable('Icepay payments are not available at the moment: ' . $e->getMessage());

        }

        return $record;
    }

    public function success()
    {

    }

    public function error()
    {

    }

    public function waiting()
    {

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

}