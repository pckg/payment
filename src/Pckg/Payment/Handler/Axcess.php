<?php namespace Pckg\Payment\Handler;

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
     * @return string|null
     */
    public function getAuthorizationBearer()
    {
        return config('pckg.payment.provider.axcess.authorizationBearer', null);
    }

    /**
     * @return string
     * Prepare Access processor for payment.
     */
    public function initPayment()
    {
        $responseData = null;
        try {
            $url = $this->getEndpoint() . "v1/checkouts";
            $data = "authentication.userId=" . config('pckg.payment.provider.axcess.userId')
                . "&authentication.password=" . config('pckg.payment.provider.axcess.password')
                . "&authentication.entityId=" . config('pckg.payment.provider.axcess.entityId')
                . "&amount=" . $this->getTotalToPay()
                . "&currency=" . $this->order->getCurrency()
                . "&merchantTransactionId=" . $this->paymentRecord->hash
                . "&descriptor=" . urlencode($this->order->getDescription())
                . "&customer.givenName=" . $this->order->getCustomer()->getFirstName()
                . "&customer.surname=" . $this->order->getCustomer()->getLastName()
                . "&customer.email=" . $this->order->getCustomer()->getEmail()
                . "&customer.ip=" . server('REMOTE_ADDR')
                . "&paymentType=DB";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, strpos($url, 'test.') === false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            /**
             * Axcess update.
             */
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization:Bearer ' . $this->getAuthorizationBearer()]);

            $responseData = curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);
            if ($errno) {
                return [
                    'success' => false,
                    'message' => 'CURL error - ' . curl_error($ch),
                ];
            }

            $data = json_decode($responseData, true);

            if (trim($data['result']['code'] ?? null) !== '000.200.100') {
                return [
                    'success' => false,
                    'message' => $data['result']['description'] ?? 'Unknown Axcess error',
                ];
            }

            $this->axcessToken = $data['id'];
            $this->paymentRecord->setAndSave([
                                                 'transaction_id' => $data['id'],
                                             ]);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Axcess payments are not available at the moment: ' . $e->getMessage(),
            ];
        }

        $this->paymentRecord->addLog('created', $responseData);

        return [
            'axcessToken' => $this->getAxcessToken(),
            'brands'      => $this->getBrands(),
            'endpoint'    => $this->getEndpoint(),
        ];
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

            /**
             * Axcess update.
             */
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization:Bearer ' . $this->getAuthorizationBearer()]);

            $responseData = curl_exec($ch);
            curl_close($ch);

            if (curl_errno($ch)) {
                $this->errorPayment('CURL error - ' . curl_error($ch) . ' ' . $responseData);

                /*return [
                    'success' => false,
                    'message' => 'CURL error - ' . curl_error($ch),
                    'modal'   => 'error',
                ];*/
                return $this->environment->redirect($this->getErrorUrl());
            }

            $data = json_decode($responseData, true);

            /**
             * Check for correct Axcess response.
             */
            $resultCode = $data['result']['code'] ?? null;
            $okCodes = ['000.100.110', '000.000.100', '000.000.000', '000.300.000', '000.600.000'];
            if (in_array($resultCode, $okCodes)) {
                $this->approvePayment("Axcess #" . $data['id'], $responseData, $data['id']);

                /*return [
                    'success' => true,
                    'modal'   => 'success',
                ];*/

                return $this->environment->redirect($this->getSuccessUrl());
            }

            /**
             * Notify system about payment error.
             */
            $this->errorPayment($responseData);

            /*return [
                'success' => false,
                'message' => 'Unknown error',
                'modal'   => 'error',
            ];*/
            return $this->environment->redirect($this->getErrorUrl());
        } catch (Throwable $e) {
            /*return [
                'success' => false,
                'message' => 'Axcess payments are not available at the moment: ' . $e->getMessage(),
                'modal'   => 'error',
            ];*/
            return $this->environment->redirect($this->getErrorUrl());
        }
    }

}