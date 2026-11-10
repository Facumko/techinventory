<?php
// Formato JSON: { success, message, data }
class Respuesta
{
    public static function enviar(bool $correcto, string $mensaje, $datos = null, int $codigoHttp = 200): void
    {
        http_response_code($codigoHttp);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array("success" => $correcto, "message" => $mensaje, "data" => $datos), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
