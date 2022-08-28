<?php 

namespace Src\Router\Models\Locations;

use Src\Router\Models\Locations\Methods\POST;
use Src\Router\Base\BaseRouter;
use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Locations implements BaseRouter 
{

    use Methods\GET;
    use Methods\POST;

    private $Methods = [
        'GET'  => [
            'home', 'mapping'
        ],
        'POST' => [
            'locations'
            ]
    ];

    public function addRoutes()
    {
        foreach($this->Methods as $method) {
            foreach($method as $route) {
                $this->$route();
            }
        }
    }

}