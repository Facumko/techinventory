<?php
require_once __DIR__ . "/ModeloBase.php";

// Usuarios que pueden ingresar al sistema
class Usuario extends ModeloBase
{
    // Devuelve el usuario si el login existe, está activo y la clave coincide con su hash
    public function verificar(string $login, string $clave): ?array
    {
        $usuario = $this->fila("SELECT id_usuario, nombre, login, clave_hash FROM usuarios WHERE login = ? AND activo = 1", "s", array($login));
        if (!$usuario || !password_verify($clave, $usuario["clave_hash"])) {
            return null;
        }
        unset($usuario["clave_hash"]);
        return $usuario;
    }
}
