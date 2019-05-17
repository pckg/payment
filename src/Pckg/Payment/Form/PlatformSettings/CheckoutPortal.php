<?php namespace Pckg\Payment\Form\PlatformSettings;

use Pckg\Htmlbuilder\Decorator\Method\VueJS;
use Pckg\Htmlbuilder\Element\Form;

class CheckoutPortal extends Form implements Form\ResolvesOnRequest
{

    public function initFields()
    {
        $this->addDecorator($this->decoratorFactory->create(VueJS::class));

        $this->addCheckbox('enabled')->setLabel('Enabled');
        $this->addText('mode')->setLabel('Mode');
        $this->addText('maid')->setLabel('MAID');
        $this->addText('secret')->setLabel('Secret ID');
        $this->addText('username')->setLabel('Username');
        $this->addText('password')->setLabel('Password');
        $this->addText('endpoint')->setLabel('Endpoint');

        return $this;
    }

}