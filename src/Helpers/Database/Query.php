<?php

namespace Src\Helpers\Database;

use Src\Helpers\Database\MySQL\Connection;

/**
 * Punto único de acceso a la base de datos.
 *
 * Las consultas están repartidas en traits, uno por tabla (Models/Users.php,
 * Children.php, Locations.php y Zones.php), y aquí se juntan en una sola clase.
 * Desde cualquier ruta se usan igual:
 *
 *   Query::GetChildren($userId);
 *   Query::InsertZone($userId, 'Casa', 32.604, -115.480, 150);
 *
 * Para agregar una consulta, créala como "public static function" en el trait
 * de su tabla y usa self::run(...), que viene de Connection.
 */
class Query extends Connection
{

    use Models\Users;
    use Models\Children;
    use Models\Locations;
    use Models\Zones;

}
