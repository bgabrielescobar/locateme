<?php

namespace Src\Helpers\Database\Models;

trait Locations
{

    // Recorrido de las últimas $hours horas, del más antiguo al más reciente
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

    public static function InsertLocation(int $childId, float $latitude, float $longitude, ?int $accuracy, ?int $battery, bool $isSos): void
    {
        self::run(
            'INSERT INTO locations (child_id, latitude, longitude, accuracy, battery, is_sos) VALUES (?, ?, ?, ?, ?, ?)',
            [$childId, $latitude, $longitude, $accuracy, $battery, (int) $isSos]
        );
    }

    // Privacidad: no guardar la ubicación de los niños más tiempo del necesario
    public static function DeleteLocationsOlderThan(int $days): void
    {
        self::run('DELETE FROM locations WHERE recorded_at < NOW() - INTERVAL ? DAY', [$days]);
    }

}
