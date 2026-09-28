<?php

namespace Src\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Utilidades para responder JSON y leer el cuerpo de las peticiones.
 *
 * Todas las rutas de la API responden con Http::json() o Http::error(), así el
 * formato es siempre el mismo:
 *   éxito:  { ...datos... }
 *   error:  { "error": "Mensaje para mostrar al usuario" }
 */
class Http
{

    /**
     * Escribe $data como JSON en la respuesta. "Cache-Control: no-store" evita que
     * el navegador o un proxy guarden en caché datos de ubicación.
     */
    public static function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store')
            ->withStatus($status);
    }

    /**
     * Respuesta de error { "error": $message }. El panel muestra el mensaje tal
     * cual, así que debe estar en español y entenderse sin saber programar.
     */
    public static function error(Response $response, string $message, int $status = 400): Response
    {
        return self::json($response, ['error' => $message], $status);
    }

    /**
     * Devuelve el cuerpo JSON de la petición como arreglo, o null si no es JSON.
     *
     * Sólo acepta Content-Type: application/json. Un formulario de otro sitio no
     * puede mandar ese tipo sin permiso de CORS, así que esto también protege
     * contra ataques CSRF (que otra página haga acciones con la sesión del padre).
     */
    public static function body(Request $request): ?array
    {
        if (stripos($request->getHeaderLine('Content-Type'), 'application/json') !== 0) {
            return null;
        }

        $data = json_decode((string) $request->getBody(), true);

        return is_array($data) ? $data : null;
    }

}
