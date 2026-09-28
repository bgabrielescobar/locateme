<?php

namespace Src\Router\Models\Zones;

use Src\Router\Base\BaseRouter;

/**
 * Zonas seguras (casa, escuela...) del padre con sesión iniciada.
 *
 *   GET    /api/zones        listar
 *   POST   /api/zones        crear
 *   DELETE /api/zones/{id}   borrar
 *
 * El servidor sólo las guarda. Calcular si un niño está dentro o fuera de una
 * zona se hace en el navegador (statusOf() en src/public/index.js).
 */
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
