<?php

namespace Src\Router\Models\Children\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Validate;
use Src\Helpers\Database\Query;

trait POST {

    // Crea al niño y devuelve el token para vincular su teléfono (sólo se muestra esta vez)
    public function addChild()
    {
        Bootstrap::getBootstrapApp()->post('/api/children', function (Request $request, Response $response, $args) {

            $body = Http::body($request) ?? [];
            $name = Validate::text($body['name'] ?? null, 1, 40);
            $color = Validate::color($body['color'] ?? null) ?? '#6366f1';

            if ($name === null) {
                return Http::error($response, 'Escribe el nombre (máximo 40 letras).');
            }

            $token = Auth::newDeviceToken();
            $childId = Query::InsertChild($request->getAttribute('user_id'), $name, $color, Auth::hashToken($token));

            return Http::json($response, [
                'child'        => Query::FindChild($childId, $request->getAttribute('user_id')),
                'device_token' => $token,
            ], 201);
        })->add(Auth::requireUser());
    }

    // Genera un enlace nuevo; el teléfono vinculado antes deja de funcionar
    public function newDeviceLink()
    {
        Bootstrap::getBootstrapApp()->post('/api/children/{id:[0-9]+}/token', function (Request $request, Response $response, $args) {

            $token = Auth::newDeviceToken();

            if (!Query::UpdateChildToken((int) $args['id'], $request->getAttribute('user_id'), Auth::hashToken($token))) {
                return Http::error($response, 'No encontramos a ese niño.', 404);
            }

            return Http::json($response, ['device_token' => $token]);
        })->add(Auth::requireUser());
    }

    // El padre confirma que ya atendió la alerta SOS
    public function acknowledgeSos()
    {
        Bootstrap::getBootstrapApp()->post('/api/children/{id:[0-9]+}/sos/ack', function (Request $request, Response $response, $args) {

            if (Query::FindChild((int) $args['id'], $request->getAttribute('user_id')) === null) {
                return Http::error($response, 'No encontramos a ese niño.', 404);
            }

            Query::ClearChildSos((int) $args['id'], $request->getAttribute('user_id'));

            return Http::json($response, ['ok' => true]);
        })->add(Auth::requireUser());
    }

}
