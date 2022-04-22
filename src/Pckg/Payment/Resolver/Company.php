<?php

namespace Pckg\Payment\Resolver;

use Pckg\Framework\Provider\Helper\EntityResolver;
use Pckg\Framework\Provider\RouteResolver;
use Pckg\Payment\Entity\Companies;

class Company implements RouteResolver
{
    use EntityResolver;

    protected $entity = Companies::class;
}
