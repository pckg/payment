<?php namespace Pckg\Payment\Handler;

use Exception;
use Throwable;

class Axcess extends AbstractHandler implements Handler
{

    protected $axcessToken;

    protected $handler = 'axcess';

    /**
     * @return string
     * Used on frontend form.
     */
    public function getAxcessToken()
    {
        return $this->axcessToken;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return config('pckg.payment.provider.axcess.endpoint', '');
    }

    /**
     * @return string
     */
    public function getBrands()
    {
        return config('pckg.payment.provider.axcess.brands', '');
    }

    /**
     * @return string
     * Prepare Access processor for payment.
     */
    public function postStart()
    {
        $responseData = null;
        try {
            $url = $this->getEndpoint() . "v1/checkouts";
            $data = "authentication.userId=" . config('pckg.payment.provider.axcess.userId') . "&authentication.password=" . config('pckg.payment.provider.axcess.password') . "&authentication.entityId=" . config('pckg.payment.provider.axcess.entityId') . "&amount=" . $this->getTotalToPay() . "&currency=" . $this->order->getCurrency() . "&merchantTransactionId=" . $this->paymentRecord->hash . "&descriptor=" . urlencode(__('order_payment') . " #" . $this->order->getId() . ' (' . $this->order->getNum() . ' - ' . $this->order->getBills()
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               ->map('id')
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               ->implode(',') . ')') . "&customer.givenName=" . $this->order->getCustomer()
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            ->getFirstName() . "&customer.surname=" . $this->order->getCustomer()
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  ->getLastName() . "&customer.email=" . $this->order->getCustomer()
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     ->getEmail() . "&customer.ip=" . server('REMOTE_ADDR') . "&paymentType=DB";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, strpos($url, 'test.') === false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new Exception("Curl error: " . curl_error($ch));
            }
            curl_close($ch);

            $data = json_decode($responseData, true);

            if (trim($data['result']['code'] ?? null) !== '000.200.100') {
                throw new Exception($data['result']['description'] ?? 'Unknown Axcess error');
            }

            $this->axcessToken = $data['id'];
            $this->paymentRecord->setAndSave([
                                                 'transaction_id' => $data['id'],
                                             ]);
        } catch (Throwable $e) {
            response()->unavailable('Axcess payments are not available at the moment: ' . $e->getMessage());
        }

        $this->paymentRecord->addLog('created', $responseData);
    }

    public function check()
    {
        try {
            $url = $this->getEndpoint() . "v1/checkouts/" . $this->paymentRecord->transaction_id . "/payment";
            $url .= "?authentication.userId=" . config('pckg.payment.provider.axcess.userId');
            $url .= "&authentication.password=" . config('pckg.payment.provider.axcess.password');
            $url .= "&authentication.entityId=" . config('pckg.payment.provider.axcess.entityId');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, strpos($url, 'test.') === false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            curl_close($ch);
            if (curl_errno($ch)) {
                $this->errorPayment(curl_error($ch) . ' ' . $responseData);
                $this->environment->redirect($this->getErrorUrl());
            }

            $data = json_decode($responseData, true);

            /**
             * Check for correct Axcess response.
             */
            $resultCode = $data['result']['code'] ?? null;
            $okCodes = ['000.100.110', '000.000.100', '000.000.000', '000.300.000', '000.600.000'];
            if (in_array($resultCode, $okCodes)) {
                $this->approvePayment("Axcess #" . $data['id'], $responseData, $data['id']);

                $this->environment->redirect($this->getSuccessUrl());

                return;
            }

            /**
             * Notify system about payment error.
             */
            $this->errorPayment($responseData);
            $this->environment->redirect($this->getErrorUrl());

            return $responseData;
        } catch (Throwable $e) {
            response()->unavailable('Axcess payments are not available at the moment: ' . $e->getMessage());
        }
    }

}