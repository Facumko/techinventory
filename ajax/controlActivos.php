<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once "../modelos/ControlActivos.php";

function responder($correcto, $mensaje, $datos = null)
{
    echo json_encode(array("success" => $correcto, "message" => $mensaje, "data" => $datos), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION["nombre"]) || empty($_SESSION["almacen"])) {
    http_response_code(403);
    responder(false, "No tenés permiso para acceder a esta sección.");
}

$modelo = new ControlActivos();
$operacion = isset($_REQUEST["op"]) ? $_REQUEST["op"] : "";
$entidad = isset($_REQUEST["entidad"]) ? $_REQUEST["entidad"] : "";

try {
    switch ($operacion) {
        case "listar":
            responder(true, "Registros cargados.", $modelo->listar($entidad));
        case "catalogos":
            responder(true, "Opciones cargadas.", $modelo->catalogos());
        case "guardar":
            $datos = $_POST;
            unset($datos["op"], $datos["entidad"]);
            $id = $modelo->guardar($entidad, $datos);
            responder(true, "Registro guardado correctamente.", array("id" => $id));
        case "estado":
            $modelo->cambiarEstado($entidad, isset($_POST["id"]) ? $_POST["id"] : 0, isset($_POST["activo"]) ? $_POST["activo"] : 0);
            responder(true, "Estado actualizado.");
        case "eliminarActivo":
            $modelo->eliminarActivo(isset($_POST["id"]) ? $_POST["id"] : 0);
            responder(true, "Activo eliminado.");
        case "asignar":
            foreach (array("id_empleado", "id_activo", "fecha_entrega", "estado_equipo") as $campo) {
                if (!isset($_POST[$campo]) || trim($_POST[$campo]) === "") {
                    throw new Exception("Completá todos los campos obligatorios.");
                }
            }
            $id = $modelo->asignar($_POST);
            responder(true, "Asignación registrada.", array("id" => $id));
        case "devolver":
            $modelo->devolver(isset($_POST["id"]) ? $_POST["id"] : 0, isset($_POST["fecha_devolucion"]) ? $_POST["fecha_devolucion"] : date("Y-m-d"));
            responder(true, "Devolución registrada.");
        default:
            http_response_code(400);
            responder(false, "Operación no válida.");
    }
} catch (Throwable $error) {
    http_response_code(400);
    responder(false, $error->getMessage());
}
?>
