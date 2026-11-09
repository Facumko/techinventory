<?php
require_once __DIR__ . "/../config/Conexion.php";

/** Acceso de usuarios al sistema (solo consultas de lectura). */
class Usuario
{
    /** Devuelve los datos del usuario si el login y la clave (hash) coinciden. */
    public function verificar($login, $claveHash)
    {
        global $conexion;
        $consulta = $conexion->prepare("SELECT idusuario, nombre, tipo_documento, num_documento, telefono, email, cargo, imagen, login FROM usuario WHERE login=? AND clave=? AND condicion='1'");
        $consulta->bind_param("ss", $login, $claveHash);
        $consulta->execute();
        $usuario = $consulta->get_result()->fetch_assoc();
        $consulta->close();
        return $usuario;
    }

    /** Devuelve los ids de permisos asignados al usuario. */
    public function listarPermisos($idUsuario)
    {
        global $conexion;
        $consulta = $conexion->prepare("SELECT idpermiso FROM usuario_permiso WHERE idusuario=?");
        $consulta->bind_param("i", $idUsuario);
        $consulta->execute();
        $filas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
        $consulta->close();
        return array_map("intval", array_column($filas, "idpermiso"));
    }

    /** Indica si existe la tabla usuario en la base de datos. */
    public function existeTablaUsuario()
    {
        global $conexion;
        $resultado = $conexion->query("SHOW TABLES LIKE 'usuario'");
        return $resultado && $resultado->num_rows > 0;
    }
}
?>
