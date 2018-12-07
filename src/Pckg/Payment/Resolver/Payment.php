<?php namespace Pckg\Payment\Resolver;

use Pckg\Framework\Provider\Helper\EntityResolver;
use Pckg\Framework\Provider\RouteResolver;
use Pckg\Payment\Entity\Payments;

class Payment implements RouteResolver
{

    use EntityResolver;

    protected $entity = Payments::class;

}