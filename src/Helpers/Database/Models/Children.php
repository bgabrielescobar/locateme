<?php

namespace Src\Helpers\Database\Models;

trait Children
{
    // Hijos del padre con su última ubicación conocida
    public static function GetChildren(int $parentId): array
    {
        $rows = self::run(
            'SELECT c.id, c.name, c.color, c.sos_at,
                    l.latitude, l.longitude, l.accuracy, l.battery, l.recorded_at
             FROM children c
             LEFT JOIN locations l ON l.id = (
                 SELECT l2.id FROM locations l2
                 WHERE l2.child_id = c.id
                 ORDER BY l2.recorded_at DESC, l2.id DESC
                 LIMIT 1
             )
             WHERE c.parent_id = ?
             ORDER BY c.created_at, c.id',
            [$parentId]
        )->fetchAll();

        return array_map(fn ($row) => [
            'id'            => (int) $row['id'],
            'name'          => $row['name'],
            'color'         => $row['color'],
            'sos_at'        => self::isoDate($row['sos_at']),
            'last_location' => $row['recorded_at'] === null ? null : [
                'latitude'    => (float) $row['latitude'],
                'longitude'   => (float) $row['longitude'],
                'accuracy'    => $row['accuracy'] === null ? null : (int) $row['accuracy'],
                'battery'     => $row['battery'] === null ? null : (int) $row['battery'],
                'recorded_at' => self::isoDate($row['recorded_at']),
            ],
        ], $rows);
    }

    public static function FindChild(int $childId, int $parentId): ?array
    {
        $child = self::run(
            'SELECT id, name, color FROM children WHERE id = ? AND parent_id = ?',
            [$childId, $parentId]
        )->fetch();

        return $child ? ['id' => (int) $child['id'], 'name' => $child['name'], 'color' => $child['color']] : null;
    }

    public static function FindChildByToken(string $tokenHash): ?array
    {
        $child = self::run(
            'SELECT id, name, color FROM children WHERE device_token_hash = ?',
            [$tokenHash]
        )->fetch();

        return $child ? ['id' => (int) $child['id'], 'name' => $child['name'], 'color' => $child['color']] : null;
    }

    public static function InsertChild(int $parentId, string $name, string $color, string $tokenHash): int
    {
        self::run(
            'INSERT INTO children (parent_id, name, color, device_token_hash) VALUES (?, ?, ?, ?)',
            [$parentId, $name, $color, $tokenHash]
        );

        return self::lastInsertId();
    }

    public static function UpdateChildToken(int $childId, int $parentId, string $tokenHash): bool
    {
        return self::run(
            'UPDATE children SET device_token_hash = ? WHERE id = ? AND parent_id = ?',
            [$tokenHash, $childId, $parentId]
        )->rowCount() > 0;
    }

    public static function DeleteChild(int $childId, int $parentId): bool
    {
        return self::run(
            'DELETE FROM children WHERE id = ? AND parent_id = ?',
            [$childId, $parentId]
        )->rowCount() > 0;
    }

    public static function SetChildSos(int $childId): void
    {
        self::run('UPDATE children SET sos_at = CURRENT_TIMESTAMP WHERE id = ?', [$childId]);
    }

    public static function ClearChildSos(int $childId, int $parentId): bool
    {
        return self::run(
            'UPDATE children SET sos_at = NULL WHERE id = ? AND parent_id = ?',
            [$childId, $parentId]
        )->rowCount() > 0;
    }
}
