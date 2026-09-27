<?php

namespace Src\Router\Base;

abstract class BaseRouter
{

    // Nombre de los métodos que registran rutas, agrupados por verbo HTTP
    protected $Methods = [];

    public function addRoutes()
    {
        foreach($this->Methods as $method) {
            foreach($method as $route) {
                $this->$route();
            }
        }
    }

}
