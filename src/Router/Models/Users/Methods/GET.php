<?php

namespace Src\Router\Models\Users\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Database\Query;

trait GET {

    public function users()
    {
        Bootstrap::getBootstrapApp()->get('/users', function (Request $request, Response $response, $args) {

            $credentials = json_decode( (string) $request->getBody());

            $secret_key = Query::Authentication($credentials->username, $credentials->password);

            $response->getBody()->write(json_encode($secret_key, JSON_FORCE_OBJECT));

            return $response->withHeader('Content-Type', 'application/json');
        });
    }

}