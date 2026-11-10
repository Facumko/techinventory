<?php
require_once __DIR__ . "/../modelos/Sede.php";
require_once __DIR__ . "/../modelos/Empleado.php";
require_once __DIR__ . "/../modelos/Activo.php";
require_once __DIR__ . "/../modelos/Asignacion.php";

// Controlador del inventario: recibe la operación pedida, delega en los modelos y devuelve datos estructurados
class InventarioController
{
    private Sede $sedes;
    private Empleado $empleados;
    private Activo $activos;
    private Asignacion $asignaciones;

    public function __construct()
    {
        $this->sedes = new Sede();
        $this->empleados = new Empleado();
        $this->activos = new Activo();
        $this->asignaciones = new Asignacion();
    }

    // Ejecuta una operación y devuelve array
    public function ejecutar(string $operacion, string $entidad, array $datos): array
    {
        switch ($operacion) {
            case "listar":
                return array("mensaje" => "Registros cargados.", "datos" => $this->listar($entidad));
            case "catalogos":
                return array("mensaje" => "Opciones cargadas.", "datos" => $this->catalogos());
            case "guardar":
                return array("mensaje" => "Registro guardado correctamente.", "datos" => array("id" => $this->guardar($entidad, $datos)));
            case "estado":
                $this->cambiarEstado($entidad, $datos);
                return array("mensaje" => "Estado actualizado.", "datos" => null);
            case "eliminarActivo":
                $this->activos->eliminar((int)($datos["id"] ?? 0));
                return array("mensaje" => "Activo eliminado.", "datos" => null);
            case "asignar":
                return array("mensaje" => "Entrega registrada.", "datos" => array("id" => $this->asignaciones->asignar($datos)));
            case "devolver":
                $this->asignaciones->devolver($datos);
                return array("mensaje" => "Devolución registrada.", "datos" => null);
            case "pendientesEmpleado":
                return array("mensaje" => "Activos a cargo cargados.", "datos" => $this->empleados->activosPendientes((int)($datos["id"] ?? 0)));
            default:
                throw new ReglaNegocioException("Operación no válida.");
        }
    }

    private function listar(string $entidad): array
    {
        switch ($entidad) {
            case "sedes":
                return $this->sedes->listar();
            case "empleados":
                return $this->empleados->listar();
            case "activos":
                return $this->activos->listar();
            case "asignaciones":
                return $this->asignaciones->listar();
            default:
                throw new ReglaNegocioException("Entidad no válida.");
        }
    }

    private function catalogos(): array
    {
        return array(
            "sedes" => $this->sedes->listarActivas(),
            "empleados" => $this->empleados->listarActivos(),
            "activos" => $this->activos->listarDisponibles(),
            "tipos" => Activo::TIPOS,
            "estados" => ModeloBase::ESTADOS_FISICOS
        );
    }

    private function guardar(string $entidad, array $datos): int
    {
        switch ($entidad) {
            case "sedes":
                return $this->sedes->guardar($datos);
            case "empleados":
                return $this->empleados->guardar($datos);
            case "activos":
                return $this->activos->guardar($datos);
            default:
                throw new ReglaNegocioException("Esta entidad se modifica desde sus acciones específicas.");
        }
    }

    private function cambiarEstado(string $entidad, array $datos): void
    {
        $id = (int)($datos["id"] ?? 0);
        $activo = (int)($datos["activo"] ?? 0);
        if ($entidad === "sedes") {
            $this->sedes->cambiarEstado($id, $activo);
        } elseif ($entidad === "empleados") {
            $this->empleados->cambiarEstado($id, $activo);
        } else {
            throw new ReglaNegocioException("No se puede cambiar el estado de esta entidad.");
        }
    }
}
