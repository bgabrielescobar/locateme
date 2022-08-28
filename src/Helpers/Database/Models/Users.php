<?php

namespace Src\Helpers\Models;

trait Users
{
    public static function Authentication($username, $password)
    {
        $mysqlConnection = parent::getConnection();

        $query = "SELECT secret_key FROM users WHERE username = $username AND password = $password";

        $result = mysqli_query($mysqlConnection, $query);

        $user = $result->fetch_all(MYSQLI_ASSOC);

        $mysqlConnection->close();

        return $user;
    }

    public static function InsertUser()
    {
        $mysqlConnection = parent::getConnection();

        $query = "INSERT INTO users(user_id, longitude, latitude) values (1, 2, 2)";

        mysqli_query($mysqlConnection, $query);

        $mysqlConnection->close();
    }
}