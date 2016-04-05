# About project
Project provides abstract implementation for payment providers:
 - paypal (classic API, REST API)
 - paymill (credit cards, sepa, paypal)
 - proforma
 
Currently waiting for implementation:
 - braintree
 - paywiser
 - activa
 - megapos
 - moneta
 
# Instalation
You can install package via composer.
```sh
$ composer require schtr4jh/payment
```
## Paymill
### Credit cards
```php
'enabled'     => true,
'private_key' => env('PAYMILL_PRIVATE_KEY'),
'public_key'  => env('PAYMILL_PUBLIC_KEY'),
```
### Paypal
```php
'enabled'     => true,
'private_key' => env('PAYMILL_PRIVATE_KEY'),
'public_key'  => env('PAYMILL_PUBLIC_KEY'),
'url_return'  => 'payment.success',
'url_cancel'  => 'payment.error',
```
### Sepa
```php
'enabled'     => true,
'private_key' => env('PAYMILL_PRIVATE_KEY'),
'public_key'  => env('PAYMILL_PUBLIC_KEY'),
```
## Paypal
### Classic API
```php
'enabled'    => true,
'username'   => 'schtr4jh-facilitator_api1.schtr4jh.net',
'password'   => '1390585136',
'signature'  => 'AOZR6pqlRlwo0Ex9.oQbP2uvOalsAHQdlhdfczB0.B699lqJXv8pigFj',
'url'        => 'https://api-3t.sandbox.paypal.com/nvp',
'url_token'  => 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=[token]',
'url_return' => 'payment.success',
'url_cancel' => 'payment.error',
```
### REST API
```php
'enabled'    => false,
'id'         => 'AYlzupNqmg7177ZI57MfI26mgkzN-n8QQewuicaHmi7OOEf5LNy5FIi7ooFIbwo-t9_LQR9NOqtLslF_',
'secret'     => 'ECQLZ6zyH8b6JgG3hGN8Qg1u6ezDiT0HpfCK7MnKcQyyQoYtghnAndYJLu5p1g0UJmLMj7_IV1Qdbsv3',
'mode'       => 'sandbox',
'url_return' => 'payment.success',
'url_cancel' => 'payment.error',
'log'        => [
    'enabled' => true,
    'level'   => 'DEBUG', // 'FINE' for production
],
```
## Proforma
### Config
```php
'enabled'     => false,
'url_waiting' => 'payment.waiting',
```
# Implementation
Each project needs to have implemented \Pckg\Payment\Adapter\Order|Product|Customer|Log|Environment (called ProjectOrder, ProjectProduct, ProjectCustomer and ProductLog) which provides proper mappers.
For usage in Laravel project you can simply use Pckg\Payment\Service\LaravelPayment trait which creates payment service with Laravel environment for handling request, responses, url generation and redirects.
For usage in other project simply implement Payment\Adapter\Environment.
## Routes
```php
// list payments
'payment'          => route('PaymentController@payment', 'payment/{listing}', 'GET'),

// apply promo code
'payment.promo'    => route('PaymentController@promo', 'payment/{handler}/{listing}/promo', 'POST'),

// validate payment request
'payment.validate' => route('PaymentController@validate', 'payment/{handler}/{listing}/validate', 'POST'),

// start payment process
'payment.start'    => route('PaymentController@start', 'payment/{handler}/{listing}/start', 'POST|GET'),

// payment valid
'payment.success'  => route('PaymentController@success', 'payment/{handler}/{listing}/success', 'GET'),

// payment invalid
'payment.error'    => route('PaymentController@error', 'payment/{handler}/{listing}/error', 'GET'),

// payment not processed yet
'payment.waiting'  => route('PaymentController@waiting', 'payment/{handler}/{listing}/waiting', 'GET'),
```
## Controller
Example of full Laravel implementation
```php
<?php namespace Net\Http;

use Illuminate\Http\Request;
use Net\Http\Payment\ZoneLog;
use Net\Http\Payment\ZoneOrder;
use Net\Http\Requests\PromoCodeRequest;
use Pckg\Payment\Service\LaravelPayment;
use Zone\Content\SelectItem;
use Zone\Listing\Listing;
use Zone\Listing\PaymentMethod;
use Zone\Listing\PromoCode;

class PaymentController extends BaseController
{

    use LaravelPayment;

    protected $paymentService;

    public function __construct()
    {
        $this->paymentService = $this->createPaymentService();
    }

    private function preparePaymentService($handler, Listing $listing)
    {
        $listing->abortIfNotMine();

        $this->paymentService->prepare(new ZoneOrder($listing), $handler, new ZoneLog($listing));
    }

    public function getPayment(Listing $listing)
    {
        $listing->abortIfNotMine();

        $this->paymentService->setOrder(new ZoneOrder($listing));

        $this->vendorAsset('schtr4jh/payment/src/Pckg/Payment/public/payment.js', 'footer');

        return view('payment.index', [
            'listing'        => $listing,
            'paymentMethods' => PaymentMethod::where('enabled', 1)->get(),
            'paymentService' => $this->paymentService,
            'expYears'       => range(date('Y') + 0, date('Y') + 16),
            'expMonths'      => SelectItem::cardExpirationMonths()->get(),
        ]);
    }

    public function postStart($handler, Listing $listing)
    {
        $this->preparePaymentService($handler, $listing);
        $success = $this->paymentService->getHandler()->start();

        return [
            'success' => true,
            'modal'   => $success
                ? '#dialog-payment-success'
                : '#dialog-payment-error',
        ];
    }

    public function getStart($handler, Listing $listing)
    {
        return $this->getStatus($handler, $listing, 'start');
    }

    public function getSuccess($handler, Listing $listing)
    {
        return $this->getStatus($handler, $listing, 'success');
    }

    public function getError($handler, Listing $listing)
    {
        return $this->getStatus($handler, $listing, 'error');
    }

    public function getWaiting($handler, Listing $listing)
    {
        return $this->getStatus($handler, $listing, 'waiting');
    }

    public function postValidate($handler, Listing $listing, Request $request)
    {
        $this->preparePaymentService($handler, $listing);

        return $this->paymentService->getHandler()->validate($request);
    }

    public function postPromo($handler, Listing $listing, PromoCodeRequest $request)
    {
        $this->preparePaymentService($handler, $listing);

        $canApply = false;
        $code = PromoCode::where('code', $request->promo_code)->first();
        if ($code && (($code->discount_value && $code->discount_value < $this->paymentService->getTotal()) || $code->discount_percentage)) {
            $listing->forceFill([
                'promo_code_id' => $code->id,
            ])->save();

            $canApply = true;
        } else {
            $listing->forceFill([
                'promo_code_id' => null,
            ])->save();
        }

        return [
            'success'           => true,
            'promo_notice'      => $request->promo_code
                ? ($canApply
                    ? trans(
                        'payment.label.promo_code_valid',
                        ['discount' => $listing->promoCode->getDiscount($this->paymentService->getCurrency())]
                    ) : trans('payment.label.promo_code_invalid')
                ) : '',
            'new_handler_price' => $this->paymentService->getHandler()->getTotalToPay(),
            'new_button'        => trans('payment.paymill.btn.pay') . ' ' . $this->paymentService->getTotalToPayWithCurrency(),
        ];
    }

    protected function getStatus($handler, Listing $listing, $action)
    {
        $this->preparePaymentService($handler, $listing);
        $this->paymentService->getHandler()->{$action}();

        return view('payment.' . $action, [
            'listing' => $listing,
        ]);
    }

}

```
