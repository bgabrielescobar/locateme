<?php

namespace Src\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Src\Helpers\Database\Query;

/**
 * Autenticación. La aplicación tiene DOS tipos de "usuario":
 *
 * 1. Padres → sesión de PHP guardada en la cookie "locateme_session".
 *    - login() y logout() la abren y la cierran.
 *    - requireUser() es el middleware que protege sus rutas.
 *
 * 2. Teléfono del niño → token secreto en la cabecera "Authorization: Bearer <token>".
 *    - newDeviceToken() lo genera; en la base de datos sólo se guarda hashToken($token).
 *    - requireDevice() es el middleware que protege las rutas del teléfono.
 *
 * Los middleware se agregan a cada ruta con ->add(Auth::requireUser()).
 */
class Auth
{

    // La sesión de los padres dura 30 días para no pedirles la contraseña a cada rato.
    private const SESSION_NAME = 'locateme_session';
    private const SESSION_LIFETIME = 60 * 60 * 24 * 30;

    /**
     * Abre la sesión de PHP con una cookie segura:
     *   - httponly: JavaScript no puede leerla (si hubiera un XSS, no se la roba).
     *   - samesite=Lax: el navegador no la envía en POST que vengan de otros sitios (CSRF).
     *   - secure: sólo viaja por HTTPS cuando la página se sirve por HTTPS.
     *   - use_strict_mode: PHP rechaza ids de sesión inventados por un atacante.
     */
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

    /**
     * Inicia la sesión del padre. Se regenera el id de sesión para que un id que
     * alguien conociera antes del login no sirva después ("session fixation").
     */
    public static function login(int $userId): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        session_write_close();
    }

    /** Borra los datos de la sesión, la cookie del navegador y la sesión guardada en el servidor. */
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

    /**
     * Middleware para las rutas de padres.
     *
     * Si hay sesión, pasa la petición a la ruta con el atributo 'user_id' (léelo con
     * $request->getAttribute('user_id')). Si no, responde 401 sin ejecutar la ruta.
     *
     * Las consultas de esas rutas SIEMPRE deben filtrar por este user_id para que
     * un padre no pueda ver ni modificar los datos de otra familia.
     */
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

    /**
     * Middleware para las rutas del teléfono del niño.
     *
     * Lee "Authorization: Bearer <token>", busca al niño por el hash del token y lo
     * deja en el atributo 'child' (arreglo con id, name y color). Si el token no
     * existe o ya se reemplazó por uno nuevo, responde 401.
     */
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

    /** Token aleatorio de 32 caracteres que se puede poner en una URL (letras, números, "-" y "_"). */
    public static function newDeviceToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    /**
     * SHA-256 del token: es lo único que se guarda en la base de datos. Así, aunque
     * alguien robara la base de datos, no podría hacerse pasar por el teléfono.
     * (Para tokens aleatorios largos basta SHA-256; las contraseñas de personas se
     * guardan con password_hash, que es lento a propósito para frenar ataques.)
     */
    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

}
