<?php

namespace Src\Helpers\MySql;

abstract class Connection
{

    protected static $connection;

    protected static function getConnection()
    {
        Connection::$connection = Connection::$connection ?? mysqli_connect($_ENV['DB_SERVER_NAME'], $_ENV['USER_NAME'], $_ENV['PASSWORD'], $_ENV['DB']);

        return Connection::$connection;
    }

}