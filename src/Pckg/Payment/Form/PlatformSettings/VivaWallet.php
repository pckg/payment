<?php

namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class VivaWallet extends Form implements Form\ResolvesOnRequest
{
    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('url')->setLabel('URL')->addValidator(new RequireWhenEnabled($this));
        $this->addText('merchantId')->setLabel('Merchant ID')->addValidator(new RequireWhenEnabled($this));
        $this->addText('apiKey')->setLabel('API key')->addValidator(new RequireWhenEnabled($this));
        $this->addText('apiCode')->setLabel('API code')->addValidator(new RequireWhenEnabled($this));
        $this->addText('clientId')->setLabel('Client ID')->addValidator(new RequireWhenEnabled($this));
        $this->addText('clientSecret')->setLabel('Client secret')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }
}
