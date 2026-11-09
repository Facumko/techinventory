<?php
require_once __DIR__ . "/ModeloBase.php";

/** Bienes de la empresa (tabla activos), ubicados en una sede. */
class Activo extends ModeloBase
{
    public const TIPOS = array("Notebook", "Monitor", "Teclado", "Mouse", "Celular", "Licencia", "Otro");

    public function listar(): array
    {
        $sql = "SELECT a.id_activo AS id, a.marca, a.tipo, a.modelo, a.numero_serie, a.estado, a.id_sede, s.nombre AS sede, s.planta,
                       CASE WHEN EXISTS (SELECT 1 FROM asignaciones x WHERE x.id_activo = a.id_activo AND x.fecha_devolucion IS NULL) THEN 0 ELSE 1 END AS disponible
                FROM activos a
                INNER JOIN sedes s ON s.id_sede = a.id_sede
                ORDER BY a.tipo, a.marca, a.modelo";
        return $this->filas($sql);
    }

    public function listarDisponibles(): array
    {
        $sql = "SELECT a.id_activo AS id, a.marca, a.tipo, a.modelo, a.numero_serie, a.estado
                FROM activos a
                WHERE NOT EXISTS (SELECT 1 FROM asignaciones x WHERE x.id_activo = a.id_activo AND x.fecha_devolucion IS NULL)
                ORDER BY a.tipo, a.marca";
        return $this->filas($sql);
    }

    public function guardar(array $datos): int
    {
        $marca = $this->textoObligatorio($datos, "marca", "Marca", 40);
        $tipo = $this->opcionValida($datos, "tipo", "el tipo de activo", self::TIPOS);
        $modelo = $this->textoOpcional($datos, "modelo", "Modelo", 40);
        $numeroSerie = $this->textoOpcional($datos, "numero_serie", "Número de serie", 100);
        $estado = $this->opcionValida($datos, "estado", "el estado", self::ESTADOS_FISICOS);
        $idSede = $this->idPositivo(isset($datos["id_sede"]) ? $datos["id_sede"] : 0, "la sede donde está el activo");
        $id = isset($datos["id"]) ? (int)$datos["id"] : 0;

        if ($id > 0) {
            $this->modificar("UPDATE activos SET marca = ?, tipo = ?, modelo = ?, numero_serie = ?, estado = ?, id_sede = ? WHERE id_activo = ?", "sssssii", array($marca, $tipo, $modelo, $numeroSerie, $estado, $idSede, $id));
            return $id;
        }
        $this->modificar("INSERT INTO activos (marca, tipo, modelo, numero_serie, estado, id_sede) VALUES (?, ?, ?, ?, ?, ?)", "sssssi", array($marca, $tipo, $modelo, $numeroSerie, $estado, $idSede));
        return (int)$this->conexion->insert_id;
    }

    /** Solo se elimina un activo que nunca fue asignado; si tiene historial se conserva. */
    public function eliminar(int $id): void
    {
        $fila = $this->fila("SELECT COUNT(*) AS total FROM asignaciones WHERE id_activo = ?", "i", array($id));
        if ((int)$fila["total"] > 0) {
            throw new ReglaNegocioException("No se puede eliminar un activo que tiene historial de asignaciones.");
        }
        $this->modificar("DELETE FROM activos WHERE id_activo = ?", "i", array($id));
    }
}
