<?php

namespace Src\Router\Models\Locations\Methods;

use Src\Bootstrap\Bootstrap;
use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Database\Query;

trait GET {

    public function home() 
    {
      Bootstrap::getBootstrapApp()->get('/', function (Request $request, Response $response, $args) {

        $indexHtml = file_get_contents("./src/index.html");
        $response->getBody()->write($indexHtml);

        return $response;
      });
    }

    public function mapping()
    {
        Bootstrap::getBootstrapApp()->get('/mapping', function (Request $request, Response $response, $args) {

            $locations = Query::GetAllLocations();

            $response->getBody()->write(json_encode($locations, JSON_FORCE_OBJECT));

             return $response->withHeader('Content-Type', 'application/json');
          });
    }
    
}