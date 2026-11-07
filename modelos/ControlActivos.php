<?php
require_once "../config/Conexion.php";

/** Operaciones del inventario interno de TechInventory. */
class ControlActivos
{
    private function consulta($sql, $tipos = "", $valores = array())
    {
        global $conexion;
        $stmt = $conexion->prepare($sql);
        if (!$stmt) {
            throw new Exception("No se pudo preparar la consulta.");
        }
        if ($tipos !== "") {
            $referencias = array($tipos);
            foreach ($valores as $indice => $valor) {
                $referencias[] = &$valores[$indice];
            }
            call_user_func_array(array($stmt, "bind_param"), $referencias);
        }
        if (!$stmt->execute()) {
            $mensaje = $stmt->error;
            $stmt->close();
            throw new Exception($mensaje);
        }
        return $stmt;
    }

    private function filas($stmt)
    {
        $resultado = $stmt->get_result();
        $filas = $resultado ? $resultado->fetch_all(MYSQLI_ASSOC) : array();
        $stmt->close();
        return $filas;
    }

    public function listar($entidad)
    {
        $consultas = array(
            "sedes" => "SELECT Id_sede AS id, nombre, planta, descripcion, activo FROM sede ORDER BY nombre",
            "empleados" => "SELECT e.id_empleado AS id, e.nombre, e.apellido, e.DNI, e.foto, e.id_sede, s.nombre AS sede, s.planta, e.activo FROM empleado e INNER JOIN sede s ON s.Id_sede=e.id_sede ORDER BY e.apellido, e.nombre",
            "activos" => "SELECT a.id_activo AS id, a.marca, a.tipo, a.modelo, a.numero_serie, a.estado, CASE WHEN EXISTS (SELECT 1 FROM asignacion x WHERE x.id_activo=a.id_activo AND x.fecha_devolucion IS NULL) THEN 0 ELSE 1 END AS disponible FROM activo a ORDER BY a.tipo, a.marca",
            "asignaciones" => "SELECT x.id_asignacion AS id, x.id_empleado, CONCAT(e.nombre, ' ', e.apellido) AS empleado, e.DNI, s.nombre AS sede, x.id_activo, CONCAT(a.marca, ' ', a.tipo, IFNULL(CONCAT(' ', a.modelo), '')) AS activo, a.numero_serie, x.fecha_entrega, x.fecha_devolucion, x.estado_equipo, x.observaciones FROM asignacion x INNER JOIN empleado e ON e.id_empleado=x.id_empleado INNER JOIN sede s ON s.Id_sede=e.id_sede INNER JOIN activo a ON a.id_activo=x.id_activo ORDER BY x.fecha_entrega DESC, x.id_asignacion DESC"
        );
        if (!isset($consultas[$entidad])) {
            throw new Exception("Entidad no válida.");
        }
        return $this->filas($this->consulta($consultas[$entidad]));
    }

    public function catalogos()
    {
        return array(
            "sedes" => $this->filas($this->consulta("SELECT Id_sede AS id, nombre, planta FROM sede WHERE activo=1 ORDER BY nombre")),
            "empleados" => $this->filas($this->consulta("SELECT id_empleado AS id, nombre, apellido, DNI FROM empleado WHERE activo=1 ORDER BY apellido, nombre")),
            "activos" => $this->filas($this->consulta("SELECT a.id_activo AS id, a.marca, a.tipo, a.modelo, a.numero_serie FROM activo a WHERE NOT EXISTS (SELECT 1 FROM asignacion x WHERE x.id_activo=a.id_activo AND x.fecha_devolucion IS NULL) ORDER BY a.tipo, a.marca"))
        );
    }

    public function resumen()
    {
        $sql = "SELECT (SELECT COUNT(*) FROM sede WHERE activo=1) AS sedes, (SELECT COUNT(*) FROM empleado WHERE activo=1) AS empleados, (SELECT COUNT(*) FROM activo) AS activos, (SELECT COUNT(*) FROM asignacion WHERE fecha_devolucion IS NULL) AS asignados";
        $filas = $this->filas($this->consulta($sql));
        return $filas[0];
    }

    public function guardar($entidad, $datos)
    {
        $configuracion = array(
            "sedes" => array("sede", "Id_sede", array("nombre", "planta", "descripcion"), "sss"),
            "empleados" => array("empleado", "id_empleado", array("nombre", "apellido", "DNI", "foto", "id_sede"), "ssssi"),
            "activos" => array("activo", "id_activo", array("marca", "tipo", "modelo", "numero_serie", "estado"), "sssss")
        );
        if (!isset($configuracion[$entidad])) {
            throw new Exception("Esta entidad se modifica desde sus acciones específicas.");
        }
        list($tabla, $idColumna, $campos, $tipos) = $configuracion[$entidad];
        $valores = array();
        foreach ($campos as $campo) {
            if (!array_key_exists($campo, $datos)) {
                if ($campo === "foto") {
                    $valores[] = "";
                    continue;
                }
                throw new Exception("Falta completar el campo " . $campo . ".");
            }
            $valores[] = trim((string)$datos[$campo]);
        }
        if ($entidad === "empleados") {
            $valores[4] = (int)$valores[4];
        }
        $id = isset($datos["id"]) ? (int)$datos["id"] : 0;
        if ($id > 0) {
            $asignaciones = array();
            foreach ($campos as $campo) {
                $asignaciones[] = "`$campo`=?";
            }
            $valores[] = $id;
            $this->consulta("UPDATE `$tabla` SET " . implode(", ", $asignaciones) . " WHERE `$idColumna`=?", $tipos . "i", $valores)->close();
            return $id;
        }
        $marcas = implode(",", array_fill(0, count($campos), "?"));
        $this->consulta("INSERT INTO `$tabla` (" . implode(",", $campos) . ") VALUES ($marcas)", $tipos, $valores)->close();
        global $conexion;
        return (int)$conexion->insert_id;
    }

    public function cambiarEstado($entidad, $id, $activo)
    {
        $tablas = array("sedes" => array("sede", "Id_sede"), "empleados" => array("empleado", "id_empleado"));
        if (!isset($tablas[$entidad])) {
            throw new Exception("No se puede cambiar el estado de esta entidad.");
        }
        list($tabla, $columna) = $tablas[$entidad];
        $this->consulta("UPDATE `$tabla` SET activo=? WHERE `$columna`=?", "ii", array((int)$activo, (int)$id))->close();
        return true;
    }

    public function eliminarActivo($id)
    {
        $this->consulta("DELETE FROM activo WHERE id_activo=?", "i", array((int)$id))->close();
        return true;
    }

    public function asignar($datos)
    {
        global $conexion;
        $idEmpleado = (int)$datos["id_empleado"];
        $idActivo = (int)$datos["id_activo"];
        $fecha = $datos["fecha_entrega"];
        $estado = trim($datos["estado_equipo"]);
        $observaciones = trim($datos["observaciones"]);
        $conexion->begin_transaction();
        try {
            $stmt = $this->consulta("SELECT id_empleado FROM empleado WHERE id_empleado=? AND activo=1", "i", array($idEmpleado));
            $empleado = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$empleado) {
                throw new Exception("Seleccioná un empleado activo.");
            }
            $stmt = $this->consulta("SELECT id_activo FROM activo WHERE id_activo=? FOR UPDATE", "i", array($idActivo));
            $activo = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$activo) {
                throw new Exception("El activo seleccionado no existe.");
            }
            $stmt = $this->consulta("SELECT id_asignacion FROM asignacion WHERE id_activo=? AND fecha_devolucion IS NULL LIMIT 1", "i", array($idActivo));
            $asignado = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($asignado) {
                throw new Exception("El activo ya tiene una asignación vigente.");
            }
            $stmt = $this->consulta("INSERT INTO asignacion (id_empleado, id_activo, fecha_entrega, estado_equipo, observaciones) VALUES (?, ?, ?, ?, ?)", "iisss", array($idEmpleado, $idActivo, $fecha, $estado, $observaciones));
            $stmt->close();
            $id = (int)$conexion->insert_id;
            $conexion->commit();
            return $id;
        } catch (Throwable $error) {
            $conexion->rollback();
            throw $error;
        }
    }

    public function devolver($id, $fecha)
    {
        $stmt = $this->consulta("UPDATE asignacion SET fecha_devolucion=? WHERE id_asignacion=? AND fecha_devolucion IS NULL", "si", array($fecha, (int)$id));
        $actualizadas = $stmt->affected_rows;
        $stmt->close();
        if ($actualizadas !== 1) {
            throw new Exception("La asignación ya fue devuelta o no existe.");
        }
        return true;
    }
}
?>
