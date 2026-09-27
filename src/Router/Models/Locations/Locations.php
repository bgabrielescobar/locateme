<?php

namespace Src\Router\Models\Locations;

use Src\Router\Base\BaseRouter;

/**
 * Rutas que usa el teléfono del niño (src/public/child.js).
 *
 *   GET  /api/device      comprobar que el enlace sigue siendo válido
 *   POST /api/locations   enviar la ubicación y/o una alerta SOS
 *
 * No usan la sesión de los padres: el teléfono se identifica con su token en la
 * cabecera "Authorization: Bearer <token>" (ver Auth::requireDevice()).
 */
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
