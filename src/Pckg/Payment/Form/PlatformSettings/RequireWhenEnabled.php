<?php namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Element\Form;
use Pckg\Htmlbuilder\Validator\AbstractValidator;

class RequireWhenEnabled extends AbstractValidator
{

    protected $form;

    public function __construct(Form $form)
    {
        parent::__construct();

        $this->form = $form;
    }

    public function validate($value)
    {
        $data = $this->form->getData();

        return !isset($data['enabled']) || !$data['enabled'] || $value;
    }

}