<?php

namespace Src\Router\Models\Children\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Database\Query;

trait DELETE {

    // Borra al niño y todo su historial de ubicaciones
    public function removeChild()
    {
        Bootstrap::getBootstrapApp()->delete('/api/children/{id:[0-9]+}', function (Request $request, Response $response, $args) {

            if (!Query::DeleteChild((int) $args['id'], $request->getAttribute('user_id'))) {
                return Http::error($response, 'No encontramos a ese niño.', 404);
            }

            return Http::json($response, ['ok' => true]);
        })->add(Auth::requireUser());
    }

}
