<?php namespace Pckg\Payment\Form\PlatformSettings;

/**
 * Class BankTransfer
 *
 * @package Pckg\Payment\Form\PlatformSettings
 */
class BankTransfer extends Upn
{

    /**
     * @return $this|\Pckg\Htmlbuilder\Element\Form|Upn
     */
    public function initFields()
    {
        /**
         * Init default UPN fields.
         */
        parent::initFields();

        $this->addText('iban')->setLabel('IBAN');
        $this->addText('swiftbic')->setLabel('SWIFT/BIC');

        return $this;
    }

}