<?php

namespace Src\Router\Models\Children;

use Src\Router\Base\BaseRouter;

/**
 * Hijos de cada padre. Todas las rutas requieren sesión de padre y sólo
 * operan sobre los hijos de ESE padre.
 *
 *   GET    /api/children                  lista con la última ubicación de cada uno
 *   GET    /api/children/{id}/locations   recorrido (historial de ubicaciones)
 *   POST   /api/children                  agregar hijo (devuelve el token del teléfono)
 *   POST   /api/children/{id}/token       enlace nuevo para el teléfono
 *   POST   /api/children/{id}/sos/ack     marcar la alerta SOS como atendida
 *   DELETE /api/children/{id}             quitar al hijo y su historial
 */
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
