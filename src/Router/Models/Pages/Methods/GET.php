<?php

namespace Src\Router\Models\Pages\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

trait GET {

    // Panel de los padres
    public function home()
    {
        Bootstrap::getBootstrapApp()->get('/', function (Request $request, Response $response, $args) {
            return self::page($response, 'index.html');
        });
    }

    // Página que se abre en el teléfono del niño para compartir su ubicación
    public function child()
    {
        Bootstrap::getBootstrapApp()->get('/nino', function (Request $request, Response $response, $args) {
            return self::page($response, 'child.html');
        });
    }

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
