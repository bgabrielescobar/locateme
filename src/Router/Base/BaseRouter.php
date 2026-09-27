<?php

namespace Src\Router\Base;

/**
 * Clase base de los "Router Models" (Pages, Users, Children, Locations, Zones).
 *
 * Cada Router Model agrupa las rutas de un recurso. Las rutas se escriben en
 * traits separados por verbo HTTP (Methods/GET.php, Methods/POST.php,
 * Methods/DELETE.php) y cada método de esos traits registra UNA ruta en Slim.
 *
 * El Router Model lista en $Methods los nombres de esos métodos y addRoutes()
 * los llama uno por uno. Si agregas un método a un trait pero olvidas ponerlo
 * en $Methods, la ruta no existirá y Slim responderá 404.
 */
abstract class BaseRouter
{

    // Nombre de los métodos que registran rutas, agrupados por verbo HTTP
    protected $Methods = [];

    /** Registra en Slim todas las rutas listadas en $Methods. Lo llama Bootstrap::LoadRoutes(). */
    public function addRoutes()
    {
        foreach($this->Methods as $method) {
            foreach($method as $route) {
                $this->$route();
            }
        }
    }

}
