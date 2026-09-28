<?php

namespace Src\Router\Models\Users\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Database\Query;

trait GET {

    /**
     * GET /api/me — datos del padre con sesión: { "user": { id, name, username } }.
     *
     * El panel la llama al cargar para decidir si muestra el inicio de sesión
     * (responde 401) o directamente el mapa.
     */
    public function me()
    {
        Bootstrap::getBootstrapApp()->get('/api/me', function (Request $request, Response $response, $args) {

            $user = Query::FindUserById($request->getAttribute('user_id'));

            if ($user === null) {
                return Http::error($response, 'Inicia sesión para continuar.', 401);
            }

            return Http::json($response, ['user' => $user]);
        })->add(Auth::requireUser());
    }

}
