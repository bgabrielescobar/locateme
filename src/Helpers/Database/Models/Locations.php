<?php

namespace Src\Helpers\Database\Models;

trait Locations
{

    public static function GetAllLocations()
    {
        $mysqlConnection = parent::getConnection();

        $query = "SELECT * FROM user_location";
        $result = mysqli_query($mysqlConnection, $query);

        $locations = $result->fetch_all(MYSQLI_ASSOC);

        $mysqlConnection->close();

        return $locations;
    }

    public static function InsertLocation($longitude, $latitude)
    {
        $mysqlConnection = parent::getConnection();

        $query = "INSERT INTO user_location(user_id, longitude, latitude) values (1, 2, 2)";

        mysqli_query($mysqlConnection, $query);

        $mysqlConnection->close();
    }

}