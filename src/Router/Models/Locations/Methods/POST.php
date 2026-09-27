<?php

namespace Src\Router\Models\Locations\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Validate;
use Src\Helpers\Database\Query;

trait POST {

    // { latitude, longitude, accuracy?, battery?, sos? }
    // Con sos=true las coordenadas son opcionales: la alerta se manda aunque no haya GPS.
    public function locations()
    {
        Bootstrap::getBootstrapApp()->post('/api/locations', function (Request $request, Response $response, $args) {

            $child = $request->getAttribute('child');
            $body = Http::body($request);

            if ($body === null) {
                return Http::error($response, 'Se esperaba un cuerpo JSON.');
            }

            $isSos = ($body['sos'] ?? false) === true;
            $latitude = Validate::number($body['latitude'] ?? null, -90, 90);
            $longitude = Validate::number($body['longitude'] ?? null, -180, 180);
            $accuracy = Validate::number($body['accuracy'] ?? null, 0, 1000000);
            $battery = Validate::number($body['battery'] ?? null, 0, 100);
            $hasPosition = $latitude !== null && $longitude !== null;

            if (!$hasPosition && !$isSos) {
                return Http::error($response, 'Latitud o longitud inválida.');
            }

            if ($hasPosition) {
                Query::InsertLocation(
                    $child['id'],
                    $latitude,
                    $longitude,
                    $accuracy === null ? null : (int) round($accuracy),
                    $battery === null ? null : (int) round($battery),
                    $isSos
                );
            }

            if ($isSos) {
                Query::SetChildSos($child['id']);
            }

            // De vez en cuando, borrar ubicaciones viejas
            if (random_int(1, 100) === 1) {
                Query::DeleteLocationsOlderThan(max(1, (int) ($_ENV['LOCATION_RETENTION_DAYS'] ?? 30)));
            }

            return Http::json($response, ['ok' => true], 201);
        })->add(Auth::requireDevice());
    }

}
