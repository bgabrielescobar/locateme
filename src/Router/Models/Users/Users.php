<?php

namespace Src\Router\Models\Users;

use Src\Router\Base\BaseRouter;

// Cuentas de los padres: registro, inicio y cierre de sesión
class Users extends BaseRouter
{
    use Methods\GET;
    use Methods\POST;

    protected $Methods = [
        'GET'  => [
            'me'
        ],
        'POST' => [
            'register', 'login', 'logout'
        ]
    ];
}
