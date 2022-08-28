<?php 

namespace Src\Router\Models\Users;

use Src\Router\Base\BaseRouter;

class Users implements BaseRouter 
{
    use Methods\GET;
    use Methods\POST;

    private $Methods = [
        'GET'  => [
            'users'
        ],
        'POST' => [
            'user'
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