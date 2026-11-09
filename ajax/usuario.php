<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../modelos/Usuario.php";

$usuario = new Usuario();
$operacion = isset($_GET["op"]) ? $_GET["op"] : "";

function iniciarSesion($datos, $permisos)
{
    $_SESSION["idusuario"] = $datos["idusuario"];
    $_SESSION["nombre"] = $datos["nombre"];
    $_SESSION["imagen"] = $datos["imagen"];
    $_SESSION["login"] = $datos["login"];
    $_SESSION["escritorio"] = in_array(1, $permisos) ? 1 : 0;
    $_SESSION["almacen"] = in_array(2, $permisos) ? 1 : 0;
}

switch ($operacion) {
    case "verificar":
        $login = isset($_POST["logina"]) ? trim($_POST["logina"]) : "";
        $clave = isset($_POST["clavea"]) ? $_POST["clavea"] : "";

        // Si la base solo tiene las 4 tablas del parcial, se usa el acceso inicial admin/admin.
        if (!$usuario->existeTablaUsuario()) {
            if ($login === "admin" && $clave === "admin") {
                iniciarSesion(array("idusuario" => 0, "nombre" => "Administrador", "imagen" => "user2-160x160.jpg", "login" => "admin"), array(1, 2));
                echo json_encode(array("nombre" => "Administrador", "login" => "admin"));
            } else {
                echo "null";
            }
            break;
        }

        $datos = $usuario->verificar($login, hash("SHA256", $clave));
        if ($datos) {
            iniciarSesion($datos, $usuario->listarPermisos($datos["idusuario"]));
        }
        echo json_encode($datos);
        break;

    case "salir":
        session_unset();
        session_destroy();
        header("Location: ../index.php");
        break;

    default:
        http_response_code(400);
        echo json_encode(array("success" => false, "message" => "Operación no válida."));
}
?>
