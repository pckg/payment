<?php namespace Pckg\Payment\Handler;

use Derive\Orders\Record\OrdersBill;
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

    public function getAxcessToken()
    {
        return $this->axcessToken;
    }

    public function startPartial()
    {
        $responseData = null;
        try {
            $url = "https://test.oppwa.com/v1/checkouts";
            $data = "authentication.userId=8a8294184e736012014e78c4c4e417e0" .
                    "&authentication.password=4tJCmj2Bt3" .
                    "&authentication.entityId=8a8294184e736012014e78c4c4cb17dc" .
                    "&amount=92.00" .
                    "&currency=EUR" .
                    "&paymentType=DB";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);

            $data = json_decode($responseData, true);

            $this->axcessToken = $data['id'];
            $this->paymentRecord->setAndSave([
                                                 'transaction_id' => $this->axcessToken,
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
            $url = "https://test.oppwa.com/v1/checkouts/" . $this->paymentRecord->transaction_id . "/payment";
            $url .= "?authentication.userId=8a8294184e736012014e78c4c4e417e0";
            $url .= "&authentication.password=4tJCmj2Bt3";
            $url .= "&authentication.entityId=8a8294184e736012014e78c4c4cb17dc";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);// this should be set to true in production
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $responseData = curl_exec($ch);
            if (curl_errno($ch)) {
                return curl_error($ch);
            }
            curl_close($ch);

            $data = json_decode($responseData, true);

            if ($data['result']['code'] == '000.100.110') {
                $transaction = $data['id'];
                $this->order->getBills()->each(
                    function(OrdersBill $ordersBill) use ($transaction) {
                        $ordersBill->confirm(
                            "Axcess #" . $transaction->id,
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