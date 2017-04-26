<?php namespace Pckg\Payment\Form;

use Pckg\Htmlbuilder\Element\Form\Bootstrap;
use Pckg\Htmlbuilder\Element\Form\ResolvesOnRequest;

class CreditCard extends Bootstrap implements ResolvesOnRequest
{

    public function initFields()
    {
        $this->setDecoratorClasses([
                                       'fullField' => 'col-md-12',
                                       'label'     => 'col-md-12',
                                       'field'     => 'col-md-12',
                                   ]);

        $row = $this->addRow();

        $row->addColumn('col-md-6')
            ->addText('name')
            ->setLabel('Name');

        $row->addColumn('col-md-6')
            ->addText('surname')
            ->setLabel('Surname');

        $row = $this->addRow();

        $row->addColumn('col-md-12')
            ->addText('card')
            ->setLabel('Card number');

        $row = $this->addRow();

        $row->addColumn('col-md-4')
            ->addNumber('exp_month')
            ->setLabel('Expiration month');

        $row->addColumn('col-md-4')
            ->addNumber('exp_year')
            ->setLabel('Expiration year');

        $row->addColumn('col-md-4')
            ->addNumber('cvv')
            ->setLabel('CVV');

        return $this;
    }

}