<?php

namespace Src\Router\Models\Users;

use Src\Router\Base\BaseRouter;

/**
 * Cuentas de los padres: registro, inicio y cierre de sesión.
 *
 *   POST /api/register   crear cuenta (y dejarla con la sesión iniciada)
 *   POST /api/login      iniciar sesión
 *   POST /api/logout     cerrar sesión
 *   GET  /api/me         datos del padre con sesión iniciada
 *
 * La sesión se guarda en una cookie; ver Src\Helpers\Auth.
 */
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
