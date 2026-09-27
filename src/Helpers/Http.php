<?php

namespace Src\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Http
{

    public static function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withStatus($status);
    }

    public static function error(Response $response, string $message, int $status = 400): Response
    {
        return self::json($response, ['error' => $message], $status);
    }

    // Sólo acepta cuerpos JSON. Un formulario de otro sitio no puede mandar
    // application/json sin permiso de CORS, así que esto también frena ataques CSRF.
    public static function body(Request $request): ?array
    {
        if (stripos($request->getHeaderLine('Content-Type'), 'application/json') !== 0) {
            return null;
        }

        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : null;
    }

}
