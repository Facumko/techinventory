<?php
require_once __DIR__ . "/../modelos/Usuario.php";

/** Inicio y cierre de sesión. */
class AccesoController
{
    public function iniciarSesion(string $login, string $clave): ?array
    {
        $usuario = (new Usuario())->verificar(trim($login), $clave);
        if ($usuario === null) {
            return null;
        }
        session_regenerate_id(true);
        $_SESSION["id_usuario"] = (int)$usuario["id_usuario"];
        $_SESSION["nombre"] = $usuario["nombre"];
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        return array("nombre" => $usuario["nombre"], "login" => $usuario["login"]);
    }

    public function cerrarSesion(): void
    {
        $_SESSION = array();
        session_destroy();
    }
}
