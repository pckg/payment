<?php namespace Pckg\Payment\Handler\Icepay;

use Pckg\Payment\Form\Ideal as IdealForm;
use Pckg\Payment\Handler\Icepay;

class Ideal extends Icepay
{

    protected $paymentMethod = 'IDEAL';

    protected $issuer = 'DEFAULT';

    protected function getIcepayData()
    {
        return [
            'Country' => 'NL',
            'Issuer'  => post('issuer', null),
        ];
    }

    public function startPartialData()
    {
        $ideal = resolve(IdealForm::class)->initFields();
        $ideal->setAction(url('derive.payment.postStartPartial', [
            'handler' => 'icepay-ideal',
            'order'   => $this->order->getOrder(),
            'payment' => $this->paymentRecord,
        ]));
        $idealConfig = $this->getPaymentMethod('IDEAL');

        foreach ($idealConfig->Issuers as $issuer) {
            $ideal->issuer->addOption($issuer->IssuerKeyword, $issuer->Description);
        }

        vueManager()->addView(
            'Derive/Basket:payment/_start_icepay-ideal',
            [
                'ideal'     => $idealConfig,
                'idealForm' => $ideal,
            ]
        );

        return [];
    }

}