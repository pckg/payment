<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Icepay extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('merchant')->setLabel('Merchant')->addValidator(new RequireWhenEnabled($this));
        $this->addText('secret')->setLabel('Secret')->addValidator(new RequireWhenEnabled($this));
        $this->addText('methods')->setLabel('Methods')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
