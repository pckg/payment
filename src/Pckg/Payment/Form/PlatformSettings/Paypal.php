<?php namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Paypal extends Form implements Form\ResolvesOnRequest
{

    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('endpoint')->setLabel('Endpoint')->required()->addValidator(new RequireWhenEnabled($this));
        $this->addText('client')->setLabel('Client')->required()->addValidator(new RequireWhenEnabled($this));
        $this->addText('secret')->setLabel('Secret')->required()->addValidator(new RequireWhenEnabled($this));

        return $this;
    }

}