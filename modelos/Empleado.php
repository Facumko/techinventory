<?php
require_once __DIR__ . "/ModeloBase.php";

// Personal de la empresa siempre unido a su sede
class Empleado extends ModeloBase
{
    public function listar(): array
    {
        $sql = "SELECT e.id_empleado AS id, e.nombre, e.apellido, e.dni, e.id_sede, s.nombre AS sede, s.planta, e.activo,
                       (SELECT COUNT(*) FROM asignaciones a WHERE a.id_empleado = e.id_empleado AND a.fecha_devolucion IS NULL) AS activos_a_cargo
                FROM empleados e
                INNER JOIN sedes s ON s.id_sede = e.id_sede
                ORDER BY e.apellido, e.nombre";
        return $this->filas($sql);
    }

    public function listarActivos(): array
    {
        return $this->filas("SELECT id_empleado AS id, nombre, apellido, dni FROM empleados WHERE activo = 1 ORDER BY apellido, nombre");
    }

    // Activos que el empleado tiene, para controlar devoluciones
    public function activosPendientes(int $idEmpleado): array
    {
        $sql = "SELECT x.id_asignacion AS id, a.tipo, a.marca, a.modelo, a.numero_serie, x.fecha_entrega, x.estado_equipo
                FROM asignaciones x
                INNER JOIN activos a ON a.id_activo = x.id_activo
                WHERE x.id_empleado = ? AND x.fecha_devolucion IS NULL
                ORDER BY x.fecha_entrega, a.tipo";
        return $this->filas($sql, "i", array($idEmpleado));
    }

    public function guardar(array $datos): int
    {
        $nombre = $this->textoObligatorio($datos, "nombre", "Nombre", 40);
        $apellido = $this->textoObligatorio($datos, "apellido", "Apellido", 40);
        $dni = $this->textoObligatorio($datos, "dni", "DNI", 8);
        if (!preg_match('/^\d{7,8}$/', $dni)) {
            throw new ReglaNegocioException("El DNI debe tener 7 u 8 dígitos, sin puntos.");
        }
        $idSede = $this->idPositivo(isset($datos["id_sede"]) ? $datos["id_sede"] : 0, "una sede");
        $id = isset($datos["id"]) ? (int)$datos["id"] : 0;

        if ($id > 0) {
            $this->modificar("UPDATE empleados SET nombre = ?, apellido = ?, dni = ?, id_sede = ? WHERE id_empleado = ?", "sssii", array($nombre, $apellido, $dni, $idSede, $id));
            return $id;
        }
        $this->modificar("INSERT INTO empleados (nombre, apellido, dni, id_sede) VALUES (?, ?, ?, ?)", "sssi", array($nombre, $apellido, $dni, $idSede));
        return (int)$this->conexion->insert_id;
    }

    // Un empleado con activos sin devolver no se puede desactivar - caso de desvinculación
    public function cambiarEstado(int $id, int $activo): void
    {
        if ($activo === 0) {
            $pendientes = $this->activosPendientes($id);
            if ($pendientes) {
                $detalle = array_map(function ($activoPendiente) {
                    return $activoPendiente["tipo"] . " " . $activoPendiente["marca"] . ($activoPendiente["numero_serie"] ? " (" . $activoPendiente["numero_serie"] . ")" : "");
                }, $pendientes);
                throw new ReglaNegocioException("No se puede desactivar al empleado: debe devolver " . count($pendientes) . " activo(s): " . implode(", ", $detalle) . ".");
            }
        }
        $this->modificar("UPDATE empleados SET activo = ? WHERE id_empleado = ?", "ii", array($activo === 1 ? 1 : 0, $id));
    }
}
