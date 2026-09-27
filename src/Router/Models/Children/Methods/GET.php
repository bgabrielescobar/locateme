<?php

namespace Src\Router\Models\Children\Methods;

use Src\Bootstrap\Bootstrap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Src\Helpers\Auth;
use Src\Helpers\Http;
use Src\Helpers\Database\Query;

trait GET {

    /**
     * GET /api/children — hijos del padre con su última ubicación.
     *
     * Respuesta 200:
     *   {
     *     "children": [{
     *       "id": 1, "name": "Sofía", "color": "#ec4899",
     *       "sos_at": null,                  // o la fecha de una alerta SOS sin atender
     *       "last_location": null | {
     *         "latitude": 32.604, "longitude": -115.480,
     *         "accuracy": 12,                // metros (puede ser null)
     *         "battery": 80,                 // % (puede ser null)
     *         "recorded_at": "2026-09-27T18:00:00Z"
     *       }
     *     }]
     *   }
     *
     * El panel la llama cada 15 segundos.
     */
    public function children()
    {
        Bootstrap::getBootstrapApp()->get('/api/children', function (Request $request, Response $response, $args) {

            return Http::json($response, ['children' => Query::GetChildren($request->getAttribute('user_id'))]);
        })->add(Auth::requireUser());
    }

    /**
     * GET /api/children/{id}/locations?hours=24 — recorrido del niño.
     *
     * `hours` se limita a 1..168 (7 días). Devuelve hasta 1000 puntos, del más
     * antiguo al más reciente:
     *   { "locations": [{ latitude, longitude, accuracy, is_sos, recorded_at }] }
     *
     * 404 si el niño no existe o es de otra familia (no se revela cuál de las dos).
     */
    public function history()
    {
        Bootstrap::getBootstrapApp()->get('/api/children/{id:[0-9]+}/locations', function (Request $request, Response $response, $args) {

            $child = Query::FindChild((int) $args['id'], $request->getAttribute('user_id'));

            if ($child === null) {
                return Http::error($response, 'No encontramos a ese niño.', 404);
            }

            $hours = (int) ($request->getQueryParams()['hours'] ?? 24);
            $hours = max(1, min($hours, 24 * 7));

            return Http::json($response, ['locations' => Query::GetLocationHistory($child['id'], $hours)]);
        })->add(Auth::requireUser());
    }

}
