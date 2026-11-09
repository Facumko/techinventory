<?php
require_once __DIR__ . "/../config/Conexion.php";
require_once __DIR__ . "/ReglaNegocioException.php";

/** Base de los modelos: ejecuta consultas preparadas y valida datos comunes. */
abstract class ModeloBase
{
    public const ESTADOS_FISICOS = array("Nuevo", "Bueno", "Regular", "Requiere reparación");

    protected mysqli $conexion;

    public function __construct(?mysqli $conexion = null)
    {
        $this->conexion = $conexion ?? Conexion::obtener();
    }

    /** Ejecuta una consulta preparada y devuelve el statement ya ejecutado. */
    protected function ejecutar(string $sql, string $tipos = "", array $valores = array()): mysqli_stmt
    {
        try {
            $sentencia = $this->conexion->prepare($sql);
            if ($tipos !== "") {
                $sentencia->bind_param($tipos, ...$valores);
            }
            $sentencia->execute();
            return $sentencia;
        } catch (mysqli_sql_exception $error) {
            if ((int)$error->getCode() === 1062) {
                throw new ReglaNegocioException("Ya existe un registro con esos datos (DNI, número de serie o sede repetidos).");
            }
            throw $error;
        }
    }

    /** Devuelve todas las filas de un SELECT como arreglos asociativos. */
    protected function filas(string $sql, string $tipos = "", array $valores = array()): array
    {
        $sentencia = $this->ejecutar($sql, $tipos, $valores);
        $filas = $sentencia->get_result()->fetch_all(MYSQLI_ASSOC);
        $sentencia->close();
        return $filas;
    }

    /** Devuelve la primera fila de un SELECT, o null si no hay resultados. */
    protected function fila(string $sql, string $tipos = "", array $valores = array()): ?array
    {
        $filas = $this->filas($sql, $tipos, $valores);
        return $filas ? $filas[0] : null;
    }

    /** Ejecuta INSERT/UPDATE/DELETE y devuelve la cantidad de filas afectadas. */
    protected function modificar(string $sql, string $tipos = "", array $valores = array()): int
    {
        $sentencia = $this->ejecutar($sql, $tipos, $valores);
        $afectadas = $sentencia->affected_rows;
        $sentencia->close();
        return $afectadas;
    }

    protected function textoObligatorio(array $datos, string $campo, string $etiqueta, int $maximo): string
    {
        $valor = isset($datos[$campo]) ? trim((string)$datos[$campo]) : "";
        if ($valor === "") {
            throw new ReglaNegocioException("Completá el campo " . $etiqueta . ".");
        }
        if (mb_strlen($valor) > $maximo) {
            throw new ReglaNegocioException("El campo " . $etiqueta . " admite hasta " . $maximo . " caracteres.");
        }
        return $valor;
    }

    /** Devuelve el texto recortado, o null si quedó vacío (útil para columnas opcionales y UNIQUE). */
    protected function textoOpcional(array $datos, string $campo, string $etiqueta, int $maximo): ?string
    {
        $valor = isset($datos[$campo]) ? trim((string)$datos[$campo]) : "";
        if ($valor === "") {
            return null;
        }
        if (mb_strlen($valor) > $maximo) {
            throw new ReglaNegocioException("El campo " . $etiqueta . " admite hasta " . $maximo . " caracteres.");
        }
        return $valor;
    }

    protected function opcionValida(array $datos, string $campo, string $etiqueta, array $permitidas): string
    {
        $valor = isset($datos[$campo]) ? trim((string)$datos[$campo]) : "";
        if (!in_array($valor, $permitidas, true)) {
            throw new ReglaNegocioException("Elegí un valor válido para " . $etiqueta . ".");
        }
        return $valor;
    }

    protected function fechaValida($valor, string $etiqueta): string
    {
        $texto = trim((string)$valor);
        $fecha = DateTime::createFromFormat("Y-m-d", $texto);
        if (!$fecha || $fecha->format("Y-m-d") !== $texto) {
            throw new ReglaNegocioException("La " . $etiqueta . " no es una fecha válida.");
        }
        return $texto;
    }

    protected function idPositivo($valor, string $etiqueta): int
    {
        $id = (int)$valor;
        if ($id <= 0) {
            throw new ReglaNegocioException("Seleccioná " . $etiqueta . ".");
        }
        return $id;
    }
}
