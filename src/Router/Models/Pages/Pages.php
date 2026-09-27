<?php

namespace Src\Router\Models\Pages;

use Src\Router\Base\BaseRouter;

class Pages extends BaseRouter
{

    use Methods\GET;

    protected $Methods = [
        'GET' => [
            'home', 'child'
        ]
    ];

}
