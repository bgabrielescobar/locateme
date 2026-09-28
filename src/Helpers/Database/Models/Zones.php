<?php

namespace Src\Helpers\Database\Models;

/** Consultas de la tabla `safe_zones` (zonas seguras de cada padre). */
trait Zones
{
    /** Zonas del padre ordenadas por nombre; latitud/longitud como float y radio en metros. */
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

    /** Crea una zona y devuelve su id. */
    public static function InsertZone(int $parentId, string $name, float $latitude, float $longitude, int $radius): int
    {
        self::run(
            'INSERT INTO safe_zones (parent_id, name, latitude, longitude, radius) VALUES (?, ?, ?, ?, ?)',
            [$parentId, $name, $latitude, $longitude, $radius]
        );

        return self::lastInsertId();
    }

    /** Borra una zona del padre. Devuelve false si no existe o es de otra familia. */
    public static function DeleteZone(int $zoneId, int $parentId): bool
    {
        return self::run(
            'DELETE FROM safe_zones WHERE id = ? AND parent_id = ?',
            [$zoneId, $parentId]
        )->rowCount() > 0;
    }
}
