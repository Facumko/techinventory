# TechInventory

Sistema web interno de **GlobalTech S.A.** para controlar los activos tecnológicos (notebooks, monitores,
teclados, celulares, licencias) que se entregan a los empleados: qué activo tiene cada persona, en qué sede
está y con qué historial de entregas y devoluciones.

Arquitectura **MVC** con PHP (POO + mysqli con consultas preparadas), MySQL/MariaDB y una interfaz con
HTML5, CSS3 y JavaScript (jQuery, Bootstrap/AdminLTE, DataTables).

## Instalación (XAMPP)

1. Copiar esta carpeta como `C:\xampp\htdocs\techinventory`.
2. Iniciar **Apache** y **MySQL** desde el panel de XAMPP.
3. En phpMyAdmin → *Importar* → elegir `db/techinventory.sql` (crea la base `techinventory`, las tablas y datos de prueba).
4. Abrir <http://localhost/techinventory/>.

**Acceso inicial:** usuario `admin`, contraseña `admin` (cambiarla en producción).

Los datos de conexión están en `config/global.php` (por defecto `localhost`, puerto **3307**, usuario `root` sin contraseña). Si tu MySQL usa el puerto 3306 u otra clave, cambiá ese archivo o definí las variables de entorno
`TI_DB_HOST`, `TI_DB_PORT`, `TI_DB_USER`, `TI_DB_PASS` y `TI_DB_NAME`.

## Estructura

```
config/                      global.php (datos de conexión) y Conexion.php (conexión única)
modelos/                     Capa de datos (POO): Sede, Empleado, Activo, Asignacion, Usuario, Resumen
controladores/               Clases controladoras + puntos de entrada JSON (inventario.php, acceso.php)
vistas/                      HTML/PHP de las pantallas y scripts JS (vistas/scripts)
db/techinventory.sql         Esquema, restricciones y datos de prueba
```

El controlador no contiene HTML ni SQL: recibe la operación, delega en los modelos y responde siempre
JSON con la forma `{ "success": bool, "message": string, "data": ... }`.

## Base de datos

| Tabla | Propósito | Relaciones |
|---|---|---|
| `sedes` | Lugares físicos | Catálogo base |
| `empleados` | Personal | `id_sede` → `sedes` |
| `activos` | Bienes individuales | `id_sede` → `sedes` (ubicación) |
| `asignaciones` | Historial entrega/devolución | `id_empleado` → `empleados`, `id_activo` → `activos` (N:M) |
| `usuarios` | Acceso al sistema (clave con `password_hash`) | — |

Restricciones relevantes: claves foráneas con `ON DELETE RESTRICT`, `UNIQUE` en DNI y número de serie,
`ENUM` para tipos y estados, y `CHECK` para que la devolución no sea anterior a la entrega.

## Reglas de negocio

- Un activo solo puede tener **una asignación vigente** (sin fecha de devolución).
- No se puede **desactivar un empleado** que tiene activos sin devolver: el mensaje indica cuáles son.
- No se puede desactivar una sede con empleados activos ni eliminar un activo con historial.
- Al devolver se registra la fecha, el **estado en que vuelve el equipo** y se actualiza el estado del activo.
- Las operaciones usan POST con **token CSRF**, validación en el servidor y consultas preparadas.

## Pantallas

Escritorio (totales) · Sedes · Empleados (con *activos a cargo*) · Activos · Historial de asignaciones · Activos en uso.
