<?php

namespace Src\Helpers\Database\Models;

/**
 * Consultas de la tabla `parents` (cuentas de los padres).
 *
 * El trait se llama Users porque son los usuarios del panel, pero la tabla se
 * llama parents.
 */
trait Users
{
    /**
     * Busca por usuario (ya en minúsculas). Incluye password_hash: úsalo sólo para
     * verificar la contraseña y nunca lo mandes al navegador.
     */
    public static function FindUserByUsername(string $username): ?array
    {
        $user = self::run(
            'SELECT id, name, username, password_hash FROM parents WHERE username = ?',
            [$username]
        )->fetch();

        return $user ?: null;
    }

    /** Datos públicos del padre (sin el hash de la contraseña), listos para responder en JSON. */
    public static function FindUserById(int $userId): ?array
    {
        $user = self::run('SELECT id, name, username FROM parents WHERE id = ?', [$userId])->fetch();

        return $user ? ['id' => (int) $user['id'], 'name' => $user['name'], 'username' => $user['username']] : null;
    }

    /** Crea la cuenta y devuelve su id. $passwordHash debe venir de password_hash(), nunca la contraseña en claro. */
    public static function InsertUser(string $name, string $username, string $passwordHash): int
    {
        self::run(
            'INSERT INTO parents (name, username, password_hash) VALUES (?, ?, ?)',
            [$name, $username, $passwordHash]
        );

        return self::lastInsertId();
    }

    /** Reemplaza el hash de la contraseña (se usa en el login cuando PHP recomienda un algoritmo más nuevo). */
    public static function UpdateUserPassword(int $userId, string $passwordHash): void
    {
        self::run('UPDATE parents SET password_hash = ? WHERE id = ?', [$passwordHash, $userId]);
    }
}
