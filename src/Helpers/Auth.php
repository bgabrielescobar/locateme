<?php

namespace Src\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Src\Helpers\Database\Query;

class Auth
{

    private const SESSION_NAME = 'locateme_session';
    private const SESSION_LIFETIME = 60 * 60 * 24 * 30;

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string) self::SESSION_LIFETIME);
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => self::SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(int $userId): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        session_write_close();
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
        session_destroy();
    }

    // Middleware: sólo padres con sesión iniciada. Deja el id en el atributo 'user_id'.
    public static function requireUser(): callable
    {
        return function (Request $request, RequestHandler $handler): Response {
            $userId = null;

            // Sin cookie no hay sesión que abrir (así no se crea una sesión por cada visita anónima)
            if (isset($_COOKIE[self::SESSION_NAME])) {
                self::startSession();
                $userId = $_SESSION['user_id'] ?? null;
                // Liberar el bloqueo de la sesión para no frenar peticiones en paralelo
                session_write_close();
            }

            if ($userId === null) {
                return Http::error(new SlimResponse(), 'Inicia sesión para continuar.', 401);
            }

            return $handler->handle($request->withAttribute('user_id', (int) $userId));
        };
    }

    // Middleware: teléfono del niño identificado por su token. Deja el niño en el atributo 'child'.
    public static function requireDevice(): callable
    {
        return function (Request $request, RequestHandler $handler): Response {
            $child = null;

            if (preg_match('/^Bearer\s+([A-Za-z0-9_-]{20,100})$/', $request->getHeaderLine('Authorization'), $match)) {
                $child = Query::FindChildByToken(self::hashToken($match[1]));
            }

            if ($child === null) {
                return Http::error(new SlimResponse(), 'Este enlace ya no es válido. Pide uno nuevo a tu papá o mamá.', 401);
            }

            return $handler->handle($request->withAttribute('child', $child));
        };
    }

    public static function newDeviceToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

}
