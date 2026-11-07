<?php
ob_start();
session_start();
if (!isset($_SESSION["nombre"])) {
    header("Location: login.html");
    exit;
}
$modulos = array(
    "sedes" => array("Sedes", "fa-map-marker", array("Nombre", "Planta", "Descripción", "Estado")),
    "empleados" => array("Empleados", "fa-users", array("Nombre", "Apellido", "DNI", "Sede", "Estado")),
    "activos" => array("Activos", "fa-laptop", array("Marca", "Tipo", "Modelo", "Nº de serie", "Estado físico", "Disponibilidad")),
    "asignaciones" => array("Asignaciones", "fa-exchange", array("Empleado", "Sede", "Activo", "Entrega", "Devolución", "Estado del equipo", "Observaciones"))
);
$entidad = isset($_GET["modulo"]) && isset($modulos[$_GET["modulo"]]) ? $_GET["modulo"] : "activos";
if (empty($_SESSION["almacen"])) {
    require "header.php";
    require "noacceso.php";
    require "footer.php";
    ob_end_flush();
    exit;
}
require "header.php";
list($titulo, $icono, $columnas) = $modulos[$entidad];
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1><i class="fa <?php echo $icono; ?>"></i> <?php echo htmlspecialchars($titulo, ENT_QUOTES, "UTF-8"); ?> <small>Control interno de activos</small></h1>
  </section>
  <section class="content">
    <div class="row"><div class="col-xs-12">
      <div class="box box-primary">
        <div class="box-header with-border">
          <h3 class="box-title">Administración de <?php echo strtolower(htmlspecialchars($titulo, ENT_QUOTES, "UTF-8")); ?></h3>
          <div class="box-tools pull-right">
            <?php if ($entidad !== "asignaciones") { ?>
              <button type="button" class="btn btn-success btn-sm" id="btnAgregar"><i class="fa fa-plus-circle"></i> Agregar <?php echo rtrim(strtolower($titulo), "s"); ?></button>
            <?php } else { ?>
              <button type="button" class="btn btn-success btn-sm" id="btnAgregar"><i class="fa fa-plus-circle"></i> Registrar entrega</button>
            <?php } ?>
          </div>
        </div>
        <div class="box-body table-responsive">
          <table id="tablaInventario" class="table table-striped table-bordered table-hover">
            <thead><tr><th>Acciones</th><?php foreach ($columnas as $columna) { ?><th><?php echo htmlspecialchars($columna, ENT_QUOTES, "UTF-8"); ?></th><?php } ?></tr></thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div></div>
  </section>
</div>
<div class="modal fade" id="modalRegistro" tabindex="-1" role="dialog" aria-labelledby="tituloModal">
  <div class="modal-dialog" role="document"><div class="modal-content">
    <form id="formRegistro">
      <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button><h4 class="modal-title" id="tituloModal">Nuevo registro</h4></div>
      <div class="modal-body"><input type="hidden" name="id" id="registroId"><div id="camposRegistro" class="row"></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary" id="btnGuardar"><i class="fa fa-save"></i> Guardar</button></div>
    </form>
  </div></div>
</div>
<?php require "footer.php"; ?>
<script>window.moduloInventario = <?php echo json_encode($entidad); ?>;</script>
<script src="scripts/controlActivos.js?v=<?php echo @filemtime('scripts/controlActivos.js'); ?>"></script>
<?php ob_end_flush(); ?>
