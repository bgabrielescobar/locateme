<?php

namespace Src\Router\Models\Zones\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Database\Query;

trait DELETE {

    public function removeZone()
    {
        Bootstrap::getBootstrapApp()->delete('/api/zones/{id:[0-9]+}', function (Request $request, Response $response, $args) {

            if (!Query::DeleteZone((int) $args['id'], $request->getAttribute('user_id'))) {
                return Http::error($response, 'No encontramos esa zona.', 404);
            }

            return Http::json($response, ['ok' => true]);
        })->add(Auth::requireUser());
    }

}
