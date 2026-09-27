<?php

namespace Src\Router\Models\Pages\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait GET {

    /** GET / — panel de los padres. Si no hay sesión, index.js muestra el inicio de sesión. */
    public function home()
    {
        Bootstrap::getBootstrapApp()->get('/', function (Request $request, Response $response, $args) {
            return self::page($response, 'index.html');
        });
    }

    /** GET /nino — página que se abre en el teléfono del niño para compartir su ubicación. */
    public function child()
    {
        Bootstrap::getBootstrapApp()->get('/nino', function (Request $request, Response $response, $args) {
            return self::page($response, 'child.html');
        });
    }

    /**
     * Devuelve un archivo HTML de src/ junto con su Content-Security-Policy (CSP).
     *
     * La CSP le dice al navegador de qué dominios puede cargar scripts, estilos,
     * fuentes e imágenes; cualquier otro se bloquea. Si agregas una librería de
     * otro CDN o cambias el proveedor de mapas, agrega aquí su dominio o el
     * navegador la bloqueará (lo verás como error en la consola).
     */
    private static function page(Response $response, string $file): Response
    {
        $response->getBody()->write(file_get_contents(dirname(__DIR__, 4) . '/' . $file));

        return $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' https://unpkg.com https://cdn.jsdelivr.net",
                "style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com",
                "font-src https://fonts.gstatic.com",
                "img-src 'self' data: https://tile.openstreetmap.org",
                "connect-src 'self'",
                "frame-ancestors 'none'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));
    }

}
