<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Bankart extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('apiKey')->setLabel('Api Key')->addValidator(new RequireWhenEnabled($this));
        $this->addText('sharedSecret')->setLabel('Shared secret')->addValidator(new RequireWhenEnabled($this));
        $this->addText('apiUsername')->setLabel('API username')->addValidator(new RequireWhenEnabled($this));
        $this->addText('apiPassword')->setLabel('API password')->addValidator(new RequireWhenEnabled($this));
        $this->addText('publicIntegrationKey')->setLabel('Public integration key')->addValidator(new RequireWhenEnabled($this));
        $this->addText('maxInstalments')->setLabel('Max instalments')->addValidator(new RequireWhenEnabled($this));
        $this->addText('url')->setLabel('API URL')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
