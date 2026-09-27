<?php

namespace Src\Helpers\Database\MySQL;

use PDO;
use PDOStatement;

abstract class Connection
{

    protected static ?PDO $connection = null;

    protected static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_SERVER_NAME'],
                $_ENV['DB_PORT'] ?? '3306',
                $_ENV['DB']
            );

            self::$connection = new PDO($dsn, $_ENV['USER_NAME'], $_ENV['PASSWORD'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$connection->exec("SET time_zone = '+00:00'");
        }

        return self::$connection;
    }

    // Todas las consultas usan sentencias preparadas: nunca se concatena la entrada del usuario al SQL
    protected static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::getConnection()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    protected static function lastInsertId(): int
    {
        return (int) self::getConnection()->lastInsertId();
    }

    // MySQL devuelve 'Y-m-d H:i:s' en UTC; el navegador necesita ISO 8601
    protected static function isoDate(?string $value): ?string
    {
        return $value === null ? null : str_replace(' ', 'T', $value) . 'Z';
    }

}
