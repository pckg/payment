<?php namespace Pckg\Payment\Handler;

use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PaypalRest extends AbstractHandler implements Handler
{

    const ACK_SUCCESS = 'Success';
    const CHECKOUTSTATUS_PAYMENT_ACTION_NOT_INITIATED = 'PaymentActionNotInitiated';
    const PAYMENTACTION = 'Sale';

    protected $paypal;

    public function initHandler()
    {
        $this->config = [
            'id'          => config('payment.paypalRest.id'),
            'secret'      => config('payment.paypalRest.secret'),
            'mode'        => config('payment.paypalRest.mode'),
            'log_enabled' => config('payment.paypalRest.log.enabled'),
            'log_level'   => config('payment.paypalRest.log.level'),
            'url_return'  => config('payment.paypalRest.url_return'),
            'url_cancel'  => config('payment.paypalRest.url_cancel'),
        ];

        $this->paypal = new ApiContext(
            new OAuthTokenCredential(
                $this->config['id'],
                $this->config['secret']
            )
        );

        $this->paypal->setConfig(
            array(
                'mode'           => $this->config['mode'],
                'log.LogEnabled' => $this->config['log_enabled'],
                'log.LogLevel'   => $this->config['log_level'],
                'cache.enabled'  => true,
            )
        );

        return $this;
    }

    public function start()
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $itemList = new ItemList();
        $productsSum = 0.0;
        foreach ($this->order->getProducts() as $product) {
            $item = new Item();
            $item->setName($product->getName())
                ->setCurrency($this->order->getCurrency())
                ->setQuantity($product->getQuantity())
                ->setSku($product->getSku())
                ->setPrice($product->getPrice());
            $itemList->addItem($item);
            $productsSum += $product->getTotal();
        }

        $details = new Details();
        $details->setSubtotal($productsSum);
        $total = $productsSum;
        if ($delivery = $this->order->getDelivery()) {
            $details->setShipping($delivery);
            $total += $delivery;
        }

        if ($vat = $this->order->getVat()) {
            $details->setTax($vat);
            $total += $vat;
        }

        $amount = new Amount();
        $amount->setCurrency($this->order->getCurrency())
            ->setTotal($total)
            ->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setItemList($itemList)
            ->setDescription($this->order->getDescription())
            ->setInvoiceNumber(uniqid());

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(env('DOMAIN') . url($this->config['url_return'],
                ['paypalRest', $this->order->getOrder()]))
            ->setCancelUrl(env('DOMAIN') . url($this->config['url_cancel'], ['paypalRest', $this->order->getOrder()]));

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions([$transaction]);

        try {
            $this->log($payment);
            $payment->create($this->paypal);
            $this->log($payment);
        } catch (\Exception $e) {
            $this->log($e);
            throw $e;
        } finally {
            redirect()->away($payment->getApprovalLink())->send();
        }
    }

    public function success()
    {
        $paymentId = request('paymentId');
        $payment = Payment::get($paymentId, $this->paypal);

        $execution = new PaymentExecution();
        $execution->setPayerId(request('PayerID'));

        $transaction = new Transaction();
        $amount = new Amount();
        $details = new Details();

        $productsSum = 0.0;
        foreach ($this->order->getProducts() as $product) {
            $productsSum += $product->getTotal();
        }

        $details->setSubtotal($productsSum);
        $total = $productsSum;
        if ($delivery = $this->order->getDelivery()) {
            $details->setShipping($delivery);
            $total += $delivery;
        }

        if ($vat = $this->order->getVat()) {
            $details->setTax($vat);
            $total += $vat;
        }

        $amount->setCurrency($this->order->getCurrency())
            ->setTotal($total)
            ->setDetails($details);

        $transaction->setAmount($amount);

        $execution->addTransaction($transaction);

        try {
            $payment->execute($execution, $this->paypal);
        } catch (\Exception $e) {
            $this->log($e);
            throw $e;
        } finally {
            Payment::get($paymentId, $this->paypal);
        }
    }


}