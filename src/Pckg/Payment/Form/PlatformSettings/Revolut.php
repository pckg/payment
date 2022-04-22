<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Revolut extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('endpoint')->setLabel('URL')->addValidator(new RequireWhenEnabled($this));
        $this->addText('accessToken')->setLabel('Access token')->addValidator(new RequireWhenEnabled($this));
        $this->addText('accountId')->setLabel('Account ID')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
