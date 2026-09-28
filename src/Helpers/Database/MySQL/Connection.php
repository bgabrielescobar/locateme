<?php

namespace Src\Helpers\Database\MySQL;

use PDO;
use PDOStatement;

/**
 * Conexión a MySQL/MariaDB con PDO. Es la clase base de Query.
 *
 * - Se conecta una sola vez por petición y reutiliza la conexión.
 * - Los datos de conexión vienen del .env: DB_SERVER_NAME, DB_PORT, USER_NAME, PASSWORD y DB.
 * - La zona horaria de MySQL se fija en UTC: todas las fechas se guardan y
 *   comparan en UTC y el navegador las convierte a la hora local.
 */
abstract class Connection
{

    protected static ?PDO $connection = null;

    /**
     * Devuelve la conexión PDO, creándola la primera vez.
     *
     *   ERRMODE_EXCEPTION        cualquier error de SQL lanza una excepción (termina en un 500 con log)
     *   FETCH_ASSOC              cada fila llega como arreglo ['columna' => valor]
     *   EMULATE_PREPARES=false   sentencias preparadas reales, hechas por el servidor MySQL
     */
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

    /**
     * Ejecuta una consulta con parámetros y devuelve el PDOStatement.
     *
     * Los valores SIEMPRE van en $params y en el SQL se escriben como "?". Nunca
     * concatenes datos del usuario dentro del SQL: eso es lo que permite la
     * inyección SQL.
     *
     *   self::run('SELECT * FROM parents WHERE id = ?', [$id])->fetch();              // una fila o false
     *   self::run('SELECT * FROM safe_zones WHERE parent_id = ?', [$id])->fetchAll(); // todas las filas
     *   self::run('DELETE FROM safe_zones WHERE id = ?', [$id])->rowCount();          // filas afectadas
     */
    protected static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::getConnection()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /** Id (AUTO_INCREMENT) de la última fila insertada con esta conexión. */
    protected static function lastInsertId(): int
    {
        return (int) self::getConnection()->lastInsertId();
    }

    /**
     * Convierte una fecha de MySQL ('2026-09-27 18:00:00', en UTC) al formato ISO 8601
     * que entiende JavaScript ('2026-09-27T18:00:00Z'). La "Z" final significa UTC.
     */
    protected static function isoDate(?string $value): ?string
    {
        return $value === null ? null : str_replace(' ', 'T', $value) . 'Z';
    }

}
