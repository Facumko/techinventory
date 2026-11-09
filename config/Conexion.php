<?php
require_once __DIR__ . "/global.php";

/**
 * Conexión única a MySQL (mysqli) para toda la aplicación.
 * Los datos de conexión se definen en config/global.php.
 */
class Conexion
{
    private static ?mysqli $instancia = null;

    public static function obtener(): mysqli
    {
        if (self::$instancia === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            $conexion = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME, DB_PORT);
            $conexion->set_charset(DB_ENCODE);
            self::$instancia = $conexion;
        }
        return self::$instancia;
    }
}
