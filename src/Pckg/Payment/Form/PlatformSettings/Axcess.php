<?php namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class Axcess extends Form implements Form\ResolvesOnRequest
{

    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addCheckbox('enabled')->setLabel('Enabled');
        $this->addText('endpoint')->setLabel('Endpoint')->addValidator(new RequireWhenEnabled($this));
        $this->addText('userId')->setLabel('User ID')->addValidator(new RequireWhenEnabled($this));
        $this->addText('password')->setLabel('Password')->addValidator(new RequireWhenEnabled($this));
        $this->addText('entityId')->setLabel('Entity ID')->addValidator(new RequireWhenEnabled($this));
        $this->addText('brands')->setLabel('Brands')->addValidator(new RequireWhenEnabled($this));

        return $this;
    }

}