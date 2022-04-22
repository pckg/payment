<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class CorvusPay extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('storeId')->setLabel('Store ID')->addValidator(new RequireWhenEnabled($this));
        $this->addText('apiKey')->setLabel('API key')->addValidator(new RequireWhenEnabled($this));
        $this->addText('url')->setLabel('API URL')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
