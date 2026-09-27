<?php

namespace Src\Router\Models\Locations\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;

trait GET {

    /**
     * GET /api/device — el teléfono comprueba que su enlace sigue siendo válido.
     *
     * Respuesta: { "child": { name, color } }, con lo que la página saluda al niño
     * por su nombre. 401 si el token ya no sirve (por ejemplo, se generó uno nuevo).
     */
    public function device()
    {
        Bootstrap::getBootstrapApp()->get('/api/device', function (Request $request, Response $response, $args) {

            $child = $request->getAttribute('child');

            return Http::json($response, ['child' => ['name' => $child['name'], 'color' => $child['color']]]);
        })->add(Auth::requireDevice());
    }

}
