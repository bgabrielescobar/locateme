<?php

namespace Src\Helpers\Database\Models;

/** Consultas de la tabla `locations` (historial de ubicaciones de cada niño). */
trait Locations
{

    /**
     * Recorrido de las últimas $hours horas, del más antiguo al más reciente.
     *
     * Se piden los $limit puntos MÁS RECIENTES (ORDER BY ... DESC LIMIT) y luego se
     * invierte el arreglo, para que la línea del mapa se dibuje en orden cronológico.
     * $limit se escribe directo en el SQL en vez de usar "?" porque execute() manda
     * todos los parámetros como texto y LIMIT necesita un número; el (int) garantiza
     * que es seguro.
     */
    public static function GetLocationHistory(int $childId, int $hours, int $limit = 1000): array
    {
        $rows = self::run(
            'SELECT latitude, longitude, accuracy, is_sos, recorded_at
             FROM locations
             WHERE child_id = ? AND recorded_at >= NOW() - INTERVAL ? HOUR
             ORDER BY recorded_at DESC, id DESC
             LIMIT ' . (int) $limit,
            [$childId, $hours]
        )->fetchAll();

        return array_reverse(array_map(fn ($row) => [
            'latitude'    => (float) $row['latitude'],
            'longitude'   => (float) $row['longitude'],
            'accuracy'    => $row['accuracy'] === null ? null : (int) $row['accuracy'],
            'is_sos'      => (bool) $row['is_sos'],
            'recorded_at' => self::isoDate($row['recorded_at']),
        ], $rows));
    }

    /** Guarda una ubicación. recorded_at lo pone MySQL con la hora actual (en UTC). */
    public static function InsertLocation(int $childId, float $latitude, float $longitude, ?int $accuracy, ?int $battery, bool $isSos): void
    {
        self::run(
            'INSERT INTO locations (child_id, latitude, longitude, accuracy, battery, is_sos) VALUES (?, ?, ?, ?, ?, ?)',
            [$childId, $latitude, $longitude, $accuracy, $battery, (int) $isSos]
        );
    }

    /**
     * Privacidad: no guardar la ubicación de los niños más tiempo del necesario.
     * POST /api/locations la llama de vez en cuando (1 de cada 100 envíos) con el
     * valor de LOCATION_RETENTION_DAYS del .env.
     */
    public static function DeleteLocationsOlderThan(int $days): void
    {
        self::run('DELETE FROM locations WHERE recorded_at < NOW() - INTERVAL ? DAY', [$days]);
    }

}
