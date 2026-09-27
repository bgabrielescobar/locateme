<?php

namespace Src\Router\Models\Zones\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Validate;
use Src\Helpers\Database\Query;

trait POST {

    // { name, latitude, longitude, radius } con el radio en metros
    public function addZone()
    {
        Bootstrap::getBootstrapApp()->post('/api/zones', function (Request $request, Response $response, $args) {

            $body = Http::body($request) ?? [];
            $name = Validate::text($body['name'] ?? null, 1, 40);
            $latitude = Validate::number($body['latitude'] ?? null, -90, 90);
            $longitude = Validate::number($body['longitude'] ?? null, -180, 180);
            $radius = Validate::number($body['radius'] ?? null, 30, 5000);

            if ($name === null) {
                return Http::error($response, 'Ponle un nombre a la zona (máximo 40 letras).');
            }
            if ($latitude === null || $longitude === null) {
                return Http::error($response, 'Ubicación de la zona inválida.');
            }
            if ($radius === null) {
                return Http::error($response, 'El radio debe estar entre 30 y 5000 metros.');
            }

            $userId = $request->getAttribute('user_id');
            $zoneId = Query::InsertZone($userId, $name, $latitude, $longitude, (int) round($radius));

            return Http::json($response, [
                'zone' => [
                    'id'        => $zoneId,
                    'name'      => $name,
                    'latitude'  => $latitude,
                    'longitude' => $longitude,
                    'radius'    => (int) round($radius),
                ],
            ], 201);
        })->add(Auth::requireUser());
    }

}
