<?php namespace Pckg\Payment\Provider;

use Derive\Platform\Resolver\Company;
use Pckg\Framework\Provider;

class Payment extends Provider
{

    public function routes()
    {
        return [
            routeGroup([
                           'controller' => \Pckg\Payment\Controller\Payment::class,
                           'urlPrefix'  => '/api/payment',
                           'namePrefix' => 'api.payment',
                           'tags'       => ['group:admin'],
                       ], [
                           '.refund' => route('/[payment]/refund', 'refund')->resolvers([
                                                                                            'payment' => \Pckg\Payment\Resolver\Payment::class,
                                                                                        ]),
                       ]),
            routeGroup([
                           'controller' => \Pckg\Payment\Controller\Payment::class,
                           'urlPrefix'  => '/api/payment-methods',
                           'namePrefix' => 'api.paymentMethods',
                           'tags'       => ['group:admin'],
                       ], [
                           '.company' => route('/[paymentMethod]/companies/[company]/settings',
                                              'companySettings')->resolvers([
                                                                                'company' => Company::class,
                                                                            ]),
                       ]),
        ];
    }

}