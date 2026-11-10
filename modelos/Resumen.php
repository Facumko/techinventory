<?php
require_once __DIR__ . "/ModeloBase.php";

// Totales para el panel de control
class Resumen extends ModeloBase
{
    public function obtener(): array
    {
        $sql = "SELECT (SELECT COUNT(*) FROM sedes WHERE activo = 1) AS sedes,
                       (SELECT COUNT(*) FROM empleados WHERE activo = 1) AS empleados,
                       (SELECT COUNT(*) FROM activos) AS activos,
                       (SELECT COUNT(*) FROM asignaciones WHERE fecha_devolucion IS NULL) AS asignados";
        return $this->fila($sql);
    }
}
