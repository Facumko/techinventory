<?php
require_once __DIR__ . "/requiere_sesion.php";
require_once __DIR__ . "/../modelos/Resumen.php";
$totales = (new Resumen())->obtener();
require "header.php";
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Panel de control <small>Inventario interno de GlobalTech</small></h1>
  </section>
  <section class="content">
    <div class="row">
      <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-aqua">
          <div class="inner"><h3><?php echo (int)$totales["sedes"]; ?></h3><p>Sedes activas</p></div>
          <div class="icon"><i class="fa fa-map-marker"></i></div>
          <a href="controlActivos.php?modulo=sedes" class="small-box-footer">Ver sedes <i class="fa fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-green">
          <div class="inner"><h3><?php echo (int)$totales["empleados"]; ?></h3><p>Empleados activos</p></div>
          <div class="icon"><i class="fa fa-users"></i></div>
          <a href="controlActivos.php?modulo=empleados" class="small-box-footer">Ver empleados <i class="fa fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-yellow">
          <div class="inner"><h3><?php echo (int)$totales["activos"]; ?></h3><p>Activos registrados</p></div>
          <div class="icon"><i class="fa fa-laptop"></i></div>
          <a href="controlActivos.php?modulo=activos" class="small-box-footer">Ver activos <i class="fa fa-arrow-circle-right"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-xs-6">
        <div class="small-box bg-red">
          <div class="inner"><h3><?php echo (int)$totales["asignados"]; ?></h3><p>Activos en uso</p></div>
          <div class="icon"><i class="fa fa-exchange"></i></div>
          <a href="controlActivos.php?modulo=enuso" class="small-box-footer">Ver quién los tiene <i class="fa fa-arrow-circle-right"></i></a>
        </div>
      </div>
    </div>
    <div class="box box-primary">
      <div class="box-header with-border"><h3 class="box-title">Seguimiento de activos</h3></div>
      <div class="box-body">
        <p>Desde <strong>Inventario interno</strong> podés administrar sedes, personal y activos. Cada entrega queda en el historial; una devolución registra el estado del equipo y lo libera para una nueva asignación.</p>
        <p>Antes de desvincular a un empleado, la columna <strong>Activos a cargo</strong> de la sección Empleados muestra qué debe devolver. El sistema no permite desactivarlo mientras tenga equipos sin devolver.</p>
      </div>
    </div>
  </section>
</div>
<?php require "footer.php"; ?>
