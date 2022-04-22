<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Braintree extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('environment')->setLabel('Environment')->addValidator(new RequireWhenEnabled($this));
        $this->addText('merchant')->setLabel('Merchant')->addValidator(new RequireWhenEnabled($this));
        $this->addText('public')->setLabel('Public')->addValidator(new RequireWhenEnabled($this));
        $this->addText('private')->setLabel('Private')->addValidator(new RequireWhenEnabled($this));
        $this->addText('cse')->setLabel('CSE')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
