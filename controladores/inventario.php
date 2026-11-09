<?php
// Punto de entrada JSON del inventario. Usa solo POST (las lecturas también) para no exponer datos por URL.
session_start();
require_once __DIR__ . "/Respuesta.php";
require_once __DIR__ . "/InventarioController.php";

if (empty($_SESSION["id_usuario"])) {
    Respuesta::enviar(false, "Iniciá sesión para continuar.", null, 401);
}
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    Respuesta::enviar(false, "Método no permitido.", null, 405);
}
// Protección CSRF: el token de la sesión debe viajar en la cabecera.
$tokenRecibido = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "";
if (empty($_SESSION["csrf"]) || !hash_equals($_SESSION["csrf"], $tokenRecibido)) {
    Respuesta::enviar(false, "Sesión inválida. Recargá la página.", null, 403);
}

try {
    $controlador = new InventarioController();
    $resultado = $controlador->ejecutar((string)($_POST["op"] ?? ""), (string)($_POST["entidad"] ?? ""), $_POST);
    Respuesta::enviar(true, $resultado["mensaje"], $resultado["datos"]);
} catch (ReglaNegocioException $error) {
    Respuesta::enviar(false, $error->getMessage(), null, 400);
} catch (Throwable $error) {
    error_log("TechInventory: " . $error->getMessage());
    Respuesta::enviar(false, "Ocurrió un error interno. Intentá nuevamente.", null, 500);
}
