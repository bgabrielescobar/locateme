<?php

namespace Src\Router\Models\Locations\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Query;

trait POST {

    public function locations()
    {
        Bootstrap::getBootstrapApp()->post('/locations', function (Request $request, Response $response, $args) {
          
            $postLocation = json_decode( (string) $request->getBody());

            Query::InsertLocation($postLocation->longitude, $postLocation->latitude);
          
            $response->getBody()->write(json_encode([
                'status' => 200,
                'result' => 'ok',
                $postLocation->longitude
            ]));
            return $response->withHeader('Content-Type', 'application/json');;
          });
    }

}