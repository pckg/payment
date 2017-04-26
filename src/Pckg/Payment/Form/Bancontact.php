<?php namespace Pckg\Payment\Form;

use Pckg\Htmlbuilder\Element\Form\Bootstrap;
use Pckg\Htmlbuilder\Element\Form\ResolvesOnRequest;
use Pckg\Payment\Service\Payment;

class Bancontact extends Bootstrap implements ResolvesOnRequest
{

    public function initFields()
    {
        $this->setDecoratorClasses([
                                       'fullField' => 'col-md-12',
                                       'label'     => 'col-md-12',
                                       'field'     => 'col-md-12',
                                   ]);

        $this->addSelect('country')
             ->setLabel('Country');

        $this->addSubmit('submit')
             ->setId('pay_btn')
             ->setValue('Pay ' . price(context(Payment::class)->getTotal()))
             ->addClass('button');

        return $this;
    }

}