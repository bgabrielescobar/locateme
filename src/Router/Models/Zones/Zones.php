<?php

namespace Src\Router\Models\Zones;

use Src\Router\Base\BaseRouter;

// Zonas seguras del padre (requiere sesión)
class Zones extends BaseRouter
{
    use Methods\GET;
    use Methods\POST;
    use Methods\DELETE;

    protected $Methods = [
        'GET'    => [
            'zones'
        ],
        'POST'   => [
            'addZone'
        ],
        'DELETE' => [
            'removeZone'
        ]
    ];
}
