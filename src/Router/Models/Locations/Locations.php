<?php

namespace Src\Router\Models\Locations;

use Src\Router\Base\BaseRouter;

// Rutas que usa el teléfono del niño (autenticado con su token)
class Locations extends BaseRouter
{

    use Methods\GET;
    use Methods\POST;

    protected $Methods = [
        'GET'  => [
            'device'
        ],
        'POST' => [
            'locations'
        ]
    ];

}
