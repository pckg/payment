<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Mollie extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('apiKey')->setLabel('Api Key')->addValidator(new RequireWhenEnabled($this));
        $this->addText('methods')->setLabel('Methods')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
