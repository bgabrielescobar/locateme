<?php

namespace Src\Router\Models\Pages;

use Src\Router\Base\BaseRouter;

/**
 * Páginas HTML de la aplicación.
 *
 *   GET /      → src/index.html  (panel de los padres)
 *   GET /nino  → src/child.html  (página del teléfono del niño)
 *
 * Los archivos JS y CSS que usan esas páginas (carpeta src/public) no pasan por
 * aquí: los entrega directamente Apache o el servidor de PHP.
 */
class Pages extends BaseRouter
{

    use Methods\GET;

    protected $Methods = [
        'GET' => [
            'home', 'child'
        ]
    ];

}
