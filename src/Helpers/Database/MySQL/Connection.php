<?php

namespace Src\Helpers\MySql;

abstract class Connection
{

    protected static $connection;

    protected static function getConnection()
    {
        self::$connection = self::$connection ?? mysqli_connect($_ENV['DB_SERVER_NAME'], $_ENV['USER_NAME'], $_ENV['PASSWORD'], $_ENV['DB']);

        return self::$connection;
    }

}