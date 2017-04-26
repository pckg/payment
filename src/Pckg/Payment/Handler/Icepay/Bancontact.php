<?php namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Form\Bancontact as BancontactForm;
use Pckg\Payment\Handler\Icepay;

class Bancontact extends Icepay
{

    protected $paymentMethod = 'MISTERCASH';

    protected $issuer = 'MISTERCASH';

    public function getIcepayData()
    {
        return [
            'Country' => post('country', null),
        ];
    }

    public function startPartialData()
    {
        $bancontact = resolve(BancontactForm::class)->initFields();
        $bancontact->setAction(url('derive.payment.postStartPartial', [
            'handler' => 'icepay-bancontact',
            'order'   => $this->order->getOrder(),
            'payment' => $this->paymentRecord,
        ]));
        $bancontactConfig = $this->getPaymentMethod('MISTERCASH');

        foreach ($bancontactConfig->Issuers[0]->Countries as $country) {
            $bancontact->country->addOption($country->CountryCode, $country->CountryCode);
        }

        vueManager()->addView(
            'Derive/Basket:payment/_start_icepay-bancontact',
            [
                'bancontact'     => $bancontactConfig,
                'bancontactForm' => $bancontact,
            ]
        );

        return [];
    }

}