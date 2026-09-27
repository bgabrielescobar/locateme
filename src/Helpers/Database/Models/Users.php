<?php

namespace Src\Helpers\Database\Models;

// Cuentas de los padres
trait Users
{
    public static function FindUserByUsername(string $username): ?array
    {
        $user = self::run(
            'SELECT id, name, username, password_hash FROM parents WHERE username = ?',
            [$username]
        )->fetch();

        return $user ?: null;
    }

    public static function FindUserById(int $userId): ?array
    {
        $user = self::run('SELECT id, name, username FROM parents WHERE id = ?', [$userId])->fetch();

        return $user ? ['id' => (int) $user['id'], 'name' => $user['name'], 'username' => $user['username']] : null;
    }

    public static function InsertUser(string $name, string $username, string $passwordHash): int
    {
        self::run(
            'INSERT INTO parents (name, username, password_hash) VALUES (?, ?, ?)',
            [$name, $username, $passwordHash]
        );

        return self::lastInsertId();
    }

    public static function UpdateUserPassword(int $userId, string $passwordHash): void
    {
        self::run('UPDATE parents SET password_hash = ? WHERE id = ?', [$passwordHash, $userId]);
    }
}
