<?php

namespace Src\Router\Models\Users\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Validate;
use Src\Helpers\Database\Query;

trait POST {

    /**
     * POST /api/register — crea la cuenta de un padre e inicia su sesión.
     *
     * Cuerpo JSON: { "name": "Ana", "username": "ana.lopez", "password": "mínimo 8 caracteres" }
     *
     * Respuestas:
     *   201 { "user": {...} }   cuenta creada, ya con la sesión iniciada
     *   400 { "error": ... }    algún dato no es válido
     *   403 { "error": ... }    ALLOW_REGISTRATION=false en el .env
     *   409 { "error": ... }    ese usuario ya existe
     */
    public function register()
    {
        Bootstrap::getBootstrapApp()->post('/api/register', function (Request $request, Response $response, $args) {

            if (filter_var($_ENV['ALLOW_REGISTRATION'] ?? 'true', FILTER_VALIDATE_BOOLEAN) === false) {
                return Http::error($response, 'El registro de cuentas nuevas está desactivado.', 403);
            }

            $body = Http::body($request) ?? [];
            $name = Validate::text($body['name'] ?? null, 1, 60);
            $username = Validate::username($body['username'] ?? null);
            $password = $body['password'] ?? null;

            if ($name === null) {
                return Http::error($response, 'Escribe tu nombre.');
            }
            if ($username === null) {
                return Http::error($response, 'El usuario debe tener de 3 a 40 letras, números, puntos o guiones.');
            }
            if (!is_string($password) || strlen($password) < 8 || strlen($password) > 200) {
                return Http::error($response, 'La contraseña debe tener al menos 8 caracteres.');
            }
            if (Query::FindUserByUsername($username) !== null) {
                return Http::error($response, 'Ese usuario ya existe, elige otro.', 409);
            }

            $userId = Query::InsertUser($name, $username, password_hash($password, PASSWORD_DEFAULT));
            Auth::login($userId);

            return Http::json($response, ['user' => Query::FindUserById($userId)], 201);
        });
    }

    /**
     * POST /api/login — inicia la sesión de un padre.
     *
     * Cuerpo JSON: { "username": "ana.lopez", "password": "..." }
     *
     * Respuestas: 200 { "user": {...} } o 401 si el usuario o la contraseña están
     * mal. El mensaje es el mismo en ambos casos para no revelar qué usuarios existen.
     */
    public function login()
    {
        Bootstrap::getBootstrapApp()->post('/api/login', function (Request $request, Response $response, $args) {

            $body = Http::body($request) ?? [];
            $username = Validate::username($body['username'] ?? null);
            $password = $body['password'] ?? null;

            $password = is_string($password) ? $password : '';
            $user = $username === null ? null : Query::FindUserByUsername($username);

            if ($user === null) {
                // Mismo costo que password_verify, para que el tiempo de respuesta no revele si el usuario existe
                password_hash($password, PASSWORD_DEFAULT);
                return Http::error($response, 'Usuario o contraseña incorrectos.', 401);
            }

            if (!password_verify($password, $user['password_hash'])) {
                return Http::error($response, 'Usuario o contraseña incorrectos.', 401);
            }

            // Si PHP cambió el algoritmo recomendado desde que se guardó la contraseña,
            // se actualiza el hash aprovechando que en este momento la tenemos en claro.
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                Query::UpdateUserPassword((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
            }

            Auth::login((int) $user['id']);

            return Http::json($response, ['user' => Query::FindUserById((int) $user['id'])]);
        });
    }

    /** POST /api/logout — cierra la sesión del padre. Siempre responde { "ok": true }. */
    public function logout()
    {
        Bootstrap::getBootstrapApp()->post('/api/logout', function (Request $request, Response $response, $args) {

            Auth::logout();

            return Http::json($response, ['ok' => true]);
        });
    }

}
