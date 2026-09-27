<?php

namespace Src\Router\Models\Children;

use Src\Router\Base\BaseRouter;

// Hijos de cada padre (requiere sesión)
class Children extends BaseRouter
{
    use Methods\GET;
    use Methods\POST;
    use Methods\DELETE;

    protected $Methods = [
        'GET'    => [
            'children', 'history'
        ],
        'POST'   => [
            'addChild', 'newDeviceLink', 'acknowledgeSos'
        ],
        'DELETE' => [
            'removeChild'
        ]
    ];
}
