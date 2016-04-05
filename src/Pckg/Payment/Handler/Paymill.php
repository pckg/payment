<?php namespace Pckg\Payment\Handler;

use Exception;
use Paymill\Models\Request\Payment;
use Paymill\Models\Request\Transaction;
use Paymill\Request;

class Paymill extends AbstractHandler implements Handler
{

    protected $paymill;

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
        $this->config = [
            'private_key' => $this->environment->config('paymill.private_key'),
            'public_key'  => $this->environment->config('paymill.public_key'),
        ];

        $this->paymill = new Request($this->config['private_key']);

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

    public function getPublicKey()
    {
        return $this->config['public_key'];
    }

    public function start()
    {
        $payment = new Payment();
        $payment->setToken($this->environment->request('token'));
        $payment->setClient($this->order->getCustomer());

        $response = null;
        try {
            $this->log($payment);
            $response = $this->paymill->create($payment);
            $this->log($response);

        } catch (Exception $e) {
            $this->log($e);
            throw $e;

        } finally {
            if ($paymentId = $response->getId()) {
                return $this->makeTransaction($paymentId);
            }
        }
    }

    protected function makeTransaction($paymentId)
    {
        $transaction = new Transaction();
        $transaction->setAmount($this->getTotalToPay())
            ->setCurrency($this->order->getCurrency())
            ->setPayment($paymentId)
            ->setDescription($this->order->getDescription());

        $response = null;
        try {
            $this->log($transaction);
            $response = $this->paymill->create($transaction);
            $this->log($response);
        } catch (Exception $e) {
            $this->log($e);
            throw $e;

        } finally {
            if ($response->getStatus() == 'closed') {
                $this->order->setPaid();
                return true;
            }

        }
    }

    protected function handleTransactionResponse($response)
    {
        if ($response->getStatus() == 'closed') {
            $this->order->setPaid();
        }
    }

    public function getValidateUrl()
    {
        return $this->environment->url('payment.validate', ['paymill', $this->order->getOrder()]);
    }

    public function getStartUrl()
    {
        return $this->environment->url('payment.start', ['paymill', $this->order->getOrder()]);
    }

}