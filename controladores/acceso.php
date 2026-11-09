<?php
session_start();
require_once __DIR__ . "/Respuesta.php";
require_once __DIR__ . "/AccesoController.php";

$operacion = $_GET["op"] ?? "";
$controlador = new AccesoController();

try {
    if ($operacion === "verificar" && $_SERVER["REQUEST_METHOD"] === "POST") {
        $usuario = $controlador->iniciarSesion((string)($_POST["login"] ?? ""), (string)($_POST["clave"] ?? ""));
        if ($usuario === null) {
            Respuesta::enviar(false, "Usuario y/o contraseña incorrectos.", null, 401);
        }
        Respuesta::enviar(true, "Bienvenido.", $usuario);
    } elseif ($operacion === "salir") {
        $controlador->cerrarSesion();
        header("Location: ../vistas/login.html");
        exit;
    }
    Respuesta::enviar(false, "Operación no válida.", null, 400);
} catch (Throwable $error) {
    error_log("TechInventory: " . $error->getMessage());
    Respuesta::enviar(false, "No se pudo conectar con la base de datos. Verificá que MySQL esté iniciado e importaste db/techinventory.sql.", null, 500);
}
