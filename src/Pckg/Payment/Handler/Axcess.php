<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
use Exception;
use Throwable;

class Axcess extends AbstractHandler implements Handler
{

    protected $axcessToken;

    public function validate($request)
    {
        return [
            'success' => true,
        ];
    }

    public function initHandler()
    {
        return $this;
    }

    /**
     * @return string
     *
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
     *
     * Prepare Access processor for payment.
     */
    public function startPartial()
    {
        $responseData = null;
        try {
            $url = $this->getEndpoint() . "v1/checkouts";
            $data = "authentication.userId=" . config('pckg.payment.provider.axcess.userId') .
                    "&authentication.password=" . config('pckg.payment.provider.axcess.password') .
                    "&authentication.entityId=" . config('pckg.payment.provider.axcess.entityId') .
                    "&amount=" . number_format($this->getTotalToPay(), 2) .
                    "&currency=" . $this->order->getCurrency() .
                    "&merchantTransactionId=" . $this->paymentRecord->hash .
                    "&customer.givenName=" . $this->order->getCustomer()->getFirstName() .
                    "&customer.surname=" . $this->order->getCustomer()->getLastName() .
                    "&customer.email=" . $this->order->getCustomer()->getEmail() .
                    "&customer.ip=" . server('REMOTE_ADDR') .
                    "&paymentType=DB";

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
        $responseData = null;
        try {
            $url = $this->getEndpoint() . "v1/checkouts/" .
                   $this->paymentRecord->transaction_id . "/payment";
            $url .= "?authentication.userId=" . config('pckg.payment.provider.axcess.userId');
            $url .= "&authentication.password=" . config('pckg.payment.provider.axcess.password');
            $url .= "&authentication.entityId=" . config('pckg.payment.provider.axcess.entityId');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, strpos($url, 'test.') === false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);

            $data = json_decode($responseData, true);

            if (in_array($data['result']['code'] ?? null,
                         ['000.100.110', '000.000.100', '000.000.000', '000.300.000', '000.600.000'])) {
                $transaction = $data['id'];
                $this->paymentRecord->addLog('approved', $responseData);
                $this->paymentRecord->setAndSave(
                    [
                        "status" => 'approved',
                    ]
                );

                $this->order->getBills()->each(
                    function(OrdersBill $ordersBill) use ($transaction) {
                        $ordersBill->confirm(
                            "Axcess #" . $transaction,
                            'axcess'
                        );
                    }
                );

                $this->environment->redirect(
                    $this->environment->url(
                        'derive.payment.success',
                        ['handler' => 'axcess', 'order' => $this->order->getOrder()]
                    )
                );
            } else {
                $this->paymentRecord->addLog('error', $responseData);
                $this->paymentRecord->setAndSave(
                    [
                        "status" => 'error',
                    ]
                );
            }

            $this->environment->redirect(
                $this->environment->url(
                    'derive.payment.error',
                    ['handler' => 'axcess', 'order' => $this->order->getOrder()]
                )
            );

            return $responseData;
        } catch (Throwable $e) {
            response()->unavailable('Axcess payments are not available at the moment: ' . $e->getMessage());
        }
    }

}