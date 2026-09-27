<?php

namespace Src\Helpers\Database\Models;

// Zonas seguras (casa, escuela...) de cada padre
trait Zones
{
    public static function GetZones(int $parentId): array
    {
        $rows = self::run(
            'SELECT id, name, latitude, longitude, radius FROM safe_zones WHERE parent_id = ? ORDER BY name',
            [$parentId]
        )->fetchAll();

        return array_map(fn ($row) => [
            'id'        => (int) $row['id'],
            'name'      => $row['name'],
            'latitude'  => (float) $row['latitude'],
            'longitude' => (float) $row['longitude'],
            'radius'    => (int) $row['radius'],
        ], $rows);
    }

    public static function InsertZone(int $parentId, string $name, float $latitude, float $longitude, int $radius): int
    {
        self::run(
            'INSERT INTO safe_zones (parent_id, name, latitude, longitude, radius) VALUES (?, ?, ?, ?, ?)',
            [$parentId, $name, $latitude, $longitude, $radius]
        );

        return self::lastInsertId();
    }

    public static function DeleteZone(int $zoneId, int $parentId): bool
    {
        return self::run(
            'DELETE FROM safe_zones WHERE id = ? AND parent_id = ?',
            [$zoneId, $parentId]
        )->rowCount() > 0;
    }
}
