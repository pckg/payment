<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Valu extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('tarifficationId')->setLabel('Tariffication ID')->addValidator(new RequireWhenEnabled($this));
        $this->addText('url')->setLabel('URL')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
