<?php namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Paypal extends Form implements Form\ResolvesOnRequest
{

    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addCheckbox('enabled')->setLabel('Enabled');
        $this->addText('endpoint')->setLabel('Enabled');
        $this->addText('client')->setLabel('Client');
        $this->addText('secret')->setLabel('Secret');

        return $this;
    }

}