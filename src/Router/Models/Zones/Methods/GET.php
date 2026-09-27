<?php

namespace Src\Router\Models\Zones\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Database\Query;

trait GET {

    /** GET /api/zones — { "zones": [{ id, name, latitude, longitude, radius }] }, ordenadas por nombre. */
    public function zones()
    {
        Bootstrap::getBootstrapApp()->get('/api/zones', function (Request $request, Response $response, $args) {

            return Http::json($response, ['zones' => Query::GetZones($request->getAttribute('user_id'))]);
        })->add(Auth::requireUser());
    }

}
