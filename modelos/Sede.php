<?php
require_once __DIR__ . "/ModeloBase.php";

/** Lugares físicos de la empresa (tabla sedes). */
class Sede extends ModeloBase
{
    public function listar(): array
    {
        return $this->filas("SELECT id_sede AS id, nombre, planta, descripcion, activo FROM sedes ORDER BY nombre, planta");
    }

    public function listarActivas(): array
    {
        return $this->filas("SELECT id_sede AS id, nombre, planta FROM sedes WHERE activo = 1 ORDER BY nombre, planta");
    }

    public function guardar(array $datos): int
    {
        $nombre = $this->textoObligatorio($datos, "nombre", "Nombre", 80);
        $planta = $this->textoObligatorio($datos, "planta", "Planta", 20);
        $descripcion = $this->textoObligatorio($datos, "descripcion", "Descripción", 1000);
        $id = isset($datos["id"]) ? (int)$datos["id"] : 0;

        if ($id > 0) {
            $this->modificar("UPDATE sedes SET nombre = ?, planta = ?, descripcion = ? WHERE id_sede = ?", "sssi", array($nombre, $planta, $descripcion, $id));
            return $id;
        }
        $this->modificar("INSERT INTO sedes (nombre, planta, descripcion) VALUES (?, ?, ?)", "sss", array($nombre, $planta, $descripcion));
        return (int)$this->conexion->insert_id;
    }

    public function cambiarEstado(int $id, int $activo): void
    {
        if ($activo === 0) {
            $fila = $this->fila("SELECT COUNT(*) AS total FROM empleados WHERE id_sede = ? AND activo = 1", "i", array($id));
            if ((int)$fila["total"] > 0) {
                throw new ReglaNegocioException("No se puede desactivar la sede: todavía tiene empleados activos. Reasignalos primero.");
            }
        }
        $this->modificar("UPDATE sedes SET activo = ? WHERE id_sede = ?", "ii", array($activo === 1 ? 1 : 0, $id));
    }
}
