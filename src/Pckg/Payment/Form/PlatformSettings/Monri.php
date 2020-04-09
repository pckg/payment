<?php namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Monri extends Form implements Form\ResolvesOnRequest
{

    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addText('url')->setLabel('URL')->addValidator(new RequireWhenEnabled($this));
        $this->addText('apiKey')->setLabel('API key')->addValidator(new RequireWhenEnabled($this));
        $this->addText('authenticityToken')->setLabel('Authenticity token')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }

}