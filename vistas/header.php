<?php require_once __DIR__ . "/requiere_sesion.php"; ?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>TechInventory | Control de activos</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION["csrf"], ENT_QUOTES, "UTF-8"); ?>">
    <link rel="stylesheet" href="../public/css/bootstrap.min.css">
    <link rel="stylesheet" href="../public/css/font-awesome.css">
    <link rel="stylesheet" href="../public/css/AdminLTE.min.css">
    <link rel="stylesheet" href="../public/css/_all-skins.min.css">
    <link rel="stylesheet" href="../public/css/techinventory.css">
    <link rel="shortcut icon" href="../public/img/favicon.ico">
    <link rel="stylesheet" type="text/css" href="../public/datatables/jquery.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="../public/datatables/buttons.dataTables.min.css">
    <link rel="stylesheet" type="text/css" href="../public/datatables/responsive.dataTables.min.css">
  </head>
  <body class="hold-transition skin-blue-light sidebar-mini">
    <div class="wrapper">
      <header class="main-header">
        <a href="escritorio.php" class="logo">
          <span class="logo-mini"><b>TI</b></span>
          <span class="logo-lg"><b>TechInventory</b></span>
        </a>
        <nav class="navbar navbar-static-top" role="navigation">
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Navegación</span>
          </a>
          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
              <li class="dropdown user user-menu">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  <i class="fa fa-user-circle"></i>
                  <span class="hidden-xs"><?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?></span>
                </a>
                <ul class="dropdown-menu">
                  <li class="user-header ti-user-header">
                    <i class="fa fa-user-circle fa-4x"></i>
                    <p>
                      <?php echo htmlspecialchars($_SESSION["nombre"], ENT_QUOTES, "UTF-8"); ?>
                      <small>GlobalTech S.A. · Control de activos internos</small>
                    </p>
                  </li>
                  <li class="user-footer">
                    <div class="pull-right">
                      <a href="../controladores/acceso.php?op=salir" class="btn btn-default btn-flat">Cerrar sesión</a>
                    </div>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <aside class="main-sidebar">
        <section class="sidebar">
          <ul class="sidebar-menu">
            <li class="header">MENÚ</li>
            <li><a href="escritorio.php"><i class="fa fa-tasks"></i> <span>Escritorio</span></a></li>
            <li class="treeview active">
              <a href="#">
                <i class="fa fa-laptop"></i> <span>Inventario interno</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li><a href="controlActivos.php?modulo=sedes"><i class="fa fa-map-marker"></i> Sedes</a></li>
                <li><a href="controlActivos.php?modulo=empleados"><i class="fa fa-users"></i> Empleados</a></li>
                <li><a href="controlActivos.php?modulo=activos"><i class="fa fa-laptop"></i> Activos</a></li>
                <li><a href="controlActivos.php?modulo=asignaciones"><i class="fa fa-exchange"></i> Historial de asignaciones</a></li>
                <li><a href="controlActivos.php?modulo=enuso"><i class="fa fa-hand-grab-o"></i> Activos en uso</a></li>
              </ul>
            </li>
          </ul>
        </section>
      </aside>
