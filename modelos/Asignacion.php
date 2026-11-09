<?php
require_once __DIR__ . "/ModeloBase.php";

/** Historial de entregas y devoluciones (tabla asignaciones: empleados N:M activos). */
class Asignacion extends ModeloBase
{
    public function listar(): array
    {
        $sql = "SELECT x.id_asignacion AS id, x.id_empleado, CONCAT(e.nombre, ' ', e.apellido) AS empleado, e.dni, s.nombre AS sede, s.planta,
                       x.id_activo, CONCAT(a.tipo, ' ', a.marca, IFNULL(CONCAT(' ', a.modelo), '')) AS activo, a.numero_serie,
                       x.fecha_entrega, x.fecha_devolucion, x.estado_equipo, x.estado_devolucion, x.observaciones
                FROM asignaciones x
                INNER JOIN empleados e ON e.id_empleado = x.id_empleado
                INNER JOIN sedes s ON s.id_sede = e.id_sede
                INNER JOIN activos a ON a.id_activo = x.id_activo
                ORDER BY x.fecha_entrega DESC, x.id_asignacion DESC";
        return $this->filas($sql);
    }

    /** Registra la entrega de un activo libre a un empleado activo, de forma transaccional. */
    public function asignar(array $datos): int
    {
        $idEmpleado = $this->idPositivo(isset($datos["id_empleado"]) ? $datos["id_empleado"] : 0, "un empleado");
        $idActivo = $this->idPositivo(isset($datos["id_activo"]) ? $datos["id_activo"] : 0, "un activo");
        $fechaEntrega = $this->fechaValida(isset($datos["fecha_entrega"]) ? $datos["fecha_entrega"] : "", "fecha de entrega");
        $estadoEquipo = $this->opcionValida($datos, "estado_equipo", "el estado del equipo", self::ESTADOS_FISICOS);
        $observaciones = $this->textoOpcional($datos, "observaciones", "Observaciones", 1000);

        $this->conexion->begin_transaction();
        try {
            if (!$this->fila("SELECT id_empleado FROM empleados WHERE id_empleado = ? AND activo = 1", "i", array($idEmpleado))) {
                throw new ReglaNegocioException("Seleccioná un empleado activo.");
            }
            if (!$this->fila("SELECT id_activo FROM activos WHERE id_activo = ? FOR UPDATE", "i", array($idActivo))) {
                throw new ReglaNegocioException("El activo seleccionado no existe.");
            }
            if ($this->fila("SELECT id_asignacion FROM asignaciones WHERE id_activo = ? AND fecha_devolucion IS NULL LIMIT 1", "i", array($idActivo))) {
                throw new ReglaNegocioException("El activo ya tiene una asignación vigente.");
            }
            $this->modificar(
                "INSERT INTO asignaciones (id_empleado, id_activo, fecha_entrega, estado_equipo, observaciones) VALUES (?, ?, ?, ?, ?)",
                "iisss",
                array($idEmpleado, $idActivo, $fechaEntrega, $estadoEquipo, $observaciones)
            );
            $id = (int)$this->conexion->insert_id;
            $this->conexion->commit();
            return $id;
        } catch (Throwable $error) {
            $this->conexion->rollback();
            throw $error;
        }
    }

    /** Cierra la asignación, guarda en qué estado vuelve el equipo y actualiza el estado físico del activo. */
    public function devolver(array $datos): void
    {
        $idAsignacion = $this->idPositivo(isset($datos["id"]) ? $datos["id"] : 0, "una asignación");
        $fechaDevolucion = $this->fechaValida(isset($datos["fecha_devolucion"]) ? $datos["fecha_devolucion"] : date("Y-m-d"), "fecha de devolución");
        $estadoDevolucion = $this->opcionValida($datos, "estado_devolucion", "el estado de devolución", self::ESTADOS_FISICOS);
        $observaciones = $this->textoOpcional($datos, "observaciones", "Observaciones", 1000);

        $this->conexion->begin_transaction();
        try {
            $asignacion = $this->fila("SELECT id_activo, fecha_entrega, fecha_devolucion FROM asignaciones WHERE id_asignacion = ? FOR UPDATE", "i", array($idAsignacion));
            if (!$asignacion || $asignacion["fecha_devolucion"] !== null) {
                throw new ReglaNegocioException("La asignación ya fue devuelta o no existe.");
            }
            if ($fechaDevolucion < $asignacion["fecha_entrega"]) {
                throw new ReglaNegocioException("La devolución no puede ser anterior a la fecha de entrega (" . $asignacion["fecha_entrega"] . ").");
            }
            $this->modificar(
                "UPDATE asignaciones SET fecha_devolucion = ?, estado_devolucion = ?, observaciones = IFNULL(?, observaciones) WHERE id_asignacion = ?",
                "sssi",
                array($fechaDevolucion, $estadoDevolucion, $observaciones, $idAsignacion)
            );
            $this->modificar("UPDATE activos SET estado = ? WHERE id_activo = ?", "si", array($estadoDevolucion, (int)$asignacion["id_activo"]));
            $this->conexion->commit();
        } catch (Throwable $error) {
            $this->conexion->rollback();
            throw $error;
        }
    }
}
