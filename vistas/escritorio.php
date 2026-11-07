<?php
ob_start();
session_start();
if (!isset($_SESSION["nombre"])) {
    header("Location: login.html");
    exit;
}
require "header.php";
if (!empty($_SESSION["escritorio"])) {
    require_once "../modelos/ControlActivos.php";
    $resumen = (new ControlActivos())->resumen();
?>
<div class="content-wrapper">
  <section class="content-header"><h1>Panel de control <small>Inventario interno de GlobalTech</small></h1></section>
  <section class="content">
    <div class="row">
      <div class="col-lg-3 col-xs-6"><div class="small-box bg-aqua"><div class="inner"><h3><?php echo (int)$resumen["sedes"]; ?></h3><p>Sedes activas</p></div><div class="icon"><i class="fa fa-map-marker"></i></div><a href="controlActivos.php?modulo=sedes" class="small-box-footer">Ver sedes <i class="fa fa-arrow-circle-right"></i></a></div></div>
      <div class="col-lg-3 col-xs-6"><div class="small-box bg-green"><div class="inner"><h3><?php echo (int)$resumen["empleados"]; ?></h3><p>Empleados activos</p></div><div class="icon"><i class="fa fa-users"></i></div><a href="controlActivos.php?modulo=empleados" class="small-box-footer">Ver empleados <i class="fa fa-arrow-circle-right"></i></a></div></div>
      <div class="col-lg-3 col-xs-6"><div class="small-box bg-yellow"><div class="inner"><h3><?php echo (int)$resumen["activos"]; ?></h3><p>Activos registrados</p></div><div class="icon"><i class="fa fa-laptop"></i></div><a href="controlActivos.php?modulo=activos" class="small-box-footer">Ver activos <i class="fa fa-arrow-circle-right"></i></a></div></div>
      <div class="col-lg-3 col-xs-6"><div class="small-box bg-red"><div class="inner"><h3><?php echo (int)$resumen["asignados"]; ?></h3><p>Asignaciones vigentes</p></div><div class="icon"><i class="fa fa-exchange"></i></div><a href="controlActivos.php?modulo=asignaciones" class="small-box-footer">Ver historial <i class="fa fa-arrow-circle-right"></i></a></div></div>
    </div>
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">Seguimiento de activos</h3></div><div class="box-body"><p>Desde el menú <strong>Inventario interno</strong> podés administrar las sedes, el personal y los activos de la empresa. Cada entrega queda registrada en el historial y una devolución libera el equipo para una nueva asignación.</p></div></div>
  </section>
</div>
<?php
} else {
    require "noacceso.php";
}
require "footer.php";
ob_end_flush();
?>
