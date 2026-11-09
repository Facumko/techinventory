<?php
require_once __DIR__ . "/../config/global.php";

/** Usuario que puede ingresar al sistema (definido en config/global.php, sin tabla en la base). */
class Usuario
{
    /** Devuelve los datos del usuario si el login y la clave coinciden con los configurados. */
    public function verificar(string $login, string $clave): ?array
    {
        $loginValido = hash_equals(ADMIN_LOGIN, $login);
        $claveValida = password_verify($clave, ADMIN_CLAVE_HASH);
        if (!$loginValido || !$claveValida) {
            return null;
        }
        return array("id_usuario" => 1, "nombre" => ADMIN_NOMBRE, "login" => ADMIN_LOGIN);
    }
}
