<?php
require_once __DIR__ . "/requiere_sesion.php";

// Cada módulo: título, ícono, texto del botón "agregar" (null = sin botón) y encabezados de la tabla.
$modulos = array(
    "sedes" => array("Sedes", "fa-map-marker", "Agregar sede", array("Nombre", "Planta", "Descripción", "Estado")),
    "empleados" => array("Empleados", "fa-users", "Agregar empleado", array("Nombre", "Apellido", "DNI", "Sede", "Activos a cargo", "Estado")),
    "activos" => array("Activos", "fa-laptop", "Agregar activo", array("Marca", "Tipo", "Modelo", "Nº de serie", "Ubicación", "Estado físico", "Disponibilidad")),
    "asignaciones" => array("Historial de asignaciones", "fa-exchange", "Registrar entrega", array("Empleado", "Sede", "Activo", "Entrega", "Devolución", "Estado al entregar", "Estado al devolver", "Observaciones")),
    "enuso" => array("Activos en uso", "fa-hand-grab-o", null, array("Empleado", "Sede", "Activo", "Entrega", "Devolución", "Estado al entregar", "Estado al devolver", "Observaciones"))
);
$nombreModulo = isset($_GET["modulo"]) && isset($modulos[$_GET["modulo"]]) ? $_GET["modulo"] : "activos";
list($titulo, $icono, $textoBotonAgregar, $encabezados) = $modulos[$nombreModulo];
$esc = function ($texto) {
    return htmlspecialchars($texto, ENT_QUOTES, "UTF-8");
};
require "header.php";
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1><i class="fa <?php echo $esc($icono); ?>"></i> <?php echo $esc($titulo); ?> <small>Control interno de activos</small></h1>
  </section>
  <section class="content">
    <div class="row"><div class="col-xs-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title"><?php echo $esc($titulo); ?></h3>
          <?php if ($textoBotonAgregar !== null) { ?>
            <div class="box-tools pull-right">
              <button type="button" class="btn btn-success btn-sm" id="botonAgregar"><i class="fa fa-plus-circle"></i> <?php echo $esc($textoBotonAgregar); ?></button>
            </div>
          <?php } ?>
        </div>
        <div class="box-body table-responsive">
          <table id="tablaInventario" class="table table-striped table-bordered table-hover">
            <thead>
              <tr>
                <th>Acciones</th>
                <?php foreach ($encabezados as $encabezado) { ?><th><?php echo $esc($encabezado); ?></th><?php } ?>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div></div>
  </section>
</div>

<div class="modal fade" id="modalRegistro" tabindex="-1" role="dialog" aria-labelledby="tituloModal">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="formularioRegistro">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="tituloModal">Nuevo registro</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="idRegistro">
        <div id="camposFormulario" class="row"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="botonGuardar"><i class="fa fa-save"></i> Guardar</button>
      </div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="modalDevolucion" tabindex="-1" role="dialog" aria-labelledby="tituloDevolucion">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="formularioDevolucion">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="tituloDevolucion">Registrar devolución</h4>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="idAsignacionDevolucion">
        <p id="detalleDevolucion" class="text-muted"></p>
        <div class="form-group">
          <label for="fechaDevolucion">Fecha de devolución <span class="text-red">*</span></label>
          <input type="date" class="form-control" id="fechaDevolucion" name="fecha_devolucion" required>
        </div>
        <div class="form-group">
          <label for="estadoDevolucion">Estado en que vuelve el equipo <span class="text-red">*</span></label>
          <select class="form-control" id="estadoDevolucion" name="estado_devolucion" required></select>
        </div>
        <div class="form-group">
          <label for="observacionesDevolucion">Observaciones</label>
          <textarea class="form-control" id="observacionesDevolucion" name="observaciones" rows="2" maxlength="1000"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fa fa-undo"></i> Confirmar devolución</button>
      </div>
    </form>
  </div></div>
</div>
<?php require "footer.php"; ?>
<script>window.moduloInventario = <?php echo json_encode($nombreModulo); ?>;</script>
<script src="scripts/controlActivos.js?v=<?php echo (int)@filemtime(__DIR__ . "/scripts/controlActivos.js"); ?>"></script>
