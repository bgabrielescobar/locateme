<?php

namespace Src\Router\Models\Children\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Database\Query;

trait GET {

    public function children()
    {
        Bootstrap::getBootstrapApp()->get('/api/children', function (Request $request, Response $response, $args) {

            return Http::json($response, ['children' => Query::GetChildren($request->getAttribute('user_id'))]);
        })->add(Auth::requireUser());
    }

    // Recorrido del niño: /api/children/{id}/locations?hours=24
    public function history()
    {
        Bootstrap::getBootstrapApp()->get('/api/children/{id:[0-9]+}/locations', function (Request $request, Response $response, $args) {

            $child = Query::FindChild((int) $args['id'], $request->getAttribute('user_id'));

            if ($child === null) {
                return Http::error($response, 'No encontramos a ese niño.', 404);
            }

            $hours = (int) ($request->getQueryParams()['hours'] ?? 24);
            $hours = max(1, min($hours, 24 * 7));

            return Http::json($response, ['locations' => Query::GetLocationHistory($child['id'], $hours)]);
        })->add(Auth::requireUser());
    }

}
