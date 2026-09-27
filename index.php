<?php

/*
 * Punto de entrada ("front controller") de toda la aplicación.
 *
 * Toda petición que no sea un archivo estático llega aquí: en Apache gracias a
 * las reglas del .htaccess y en desarrollo porque el servidor de PHP se arranca
 * con `php -S localhost:8000 index.php`. Este archivo sólo prepara el entorno y
 * le pasa el control a Bootstrap::run(), que decide qué ruta atiende la petición.
 */
// Los errores van al log del servidor; nunca se muestran al visitante
ini_set('display_errors', '0');

// Con el servidor embebido de PHP (php -S localhost:8000 index.php) servir directamente
// los archivos estáticos de src/public, igual que hace el .htaccess en Apache
if (PHP_SAPI === 'cli-server') {
    $path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');

    if (str_starts_with($path, '/src/public/') && !str_contains($path, '..') && is_file(__DIR__ . $path)) {
        return false;
    }
}

require_once "src/Bootstrap/Bootstrap.php";

Src\Bootstrap\Bootstrap::run();
