<?php


function MenuAdministrador()
{

  global $conexion;

  $query2 = 'SELECT * FROM empresa WHERE id="1"';
  $buscarAlumnos2 = $conexion->query($query2);
  if ($buscarAlumnos2->num_rows > 0) {
    while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
      $distribuidor = $filaAlumnos2['distribuidor'];
      $factura = $filaAlumnos2['factura'];
    }
  }
  if ($factura == 1) {
    $compras = "nuevaCompraFacturas.php";
  } else {
    $compras = "nuevaCompra.php";
  }




  $menu =  '
 <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <ul class="nav side-menu">
                  
            
                  <li><a href="index.php"><i class="line icon-home"></i>Inicio</a></li>

                  <li><a href="registroCierre.php"><i class="line icon-note"></i>Ingresos Diarios</a></li>
               
                  <li>
                    <a href="sucursales.php">
                      <i class="line bx icon-smenu bx-store"></i>
                    Sucursales
                    </a>
                  </li>
              
              
                  <li><a href="gastos.php"><i class="line icon-briefcase"></i>Gestion de Gastos</a></li>
                   <li><a><i class="line icon-wallet"></i> Ventas <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                       <li><a href="listaVentas.php">Ventas del dia</a></li>
                      <li><a href="resumenSemana.php">Ventas de la semana</a></li>
                      <li><a href="resumenMes.php">Ventas del mes</a></li>
                    </ul>
                  </li>
                   <li><a><i class="line icon-notebook"></i> Stock <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="productos.php">Listado de Productos</a></li>
                      <li><a href="nuevoProducto.php">Nuevo Producto</a></li>
                      <li><a href="' . $compras . '">Nueva Compra</a></li>
                      <li><a href="creditos.php">Créditos</a></li>
                    </ul>
                  </li>
                   <li><a><i class="line icon-people"></i> Usuarios <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="users.php">Usuarios</a></li>
                      <li><a href="permisos.php">Permisos</a></li>
                    </ul>
                  </li>
                       <li><a href="ventas.php"><i class="line icon-basket-loaded"></i>Vender</a></li>
                        <li><a href="consultaHistorica.php"><i class="line icon-paper-clip"></i>Consultas</a></li>
                  </ul>    
              </div>
            </div>';

  return $menu;
}

function MenuStandar()
{
  global $conexion;
  $query2 = 'SELECT * FROM empresa WHERE id="1"';
  $buscarAlumnos2 = $conexion->query($query2);
  if ($buscarAlumnos2->num_rows > 0) {
    while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
      $distribuidor = $filaAlumnos2['distribuidor'];
      $factura = $filaAlumnos2['factura'];
    }
  }

  $query222 = 'SELECT * FROM acces WHERE id="1"';
  $buscarAlumnos222 = $conexion->query($query222);
  if ($buscarAlumnos222->num_rows > 0) {
    while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
      $Inicio = $filaAlumnos222['Inicio'];
      $Ventas = $filaAlumnos222['Ventas'];
      $VentasSemana = $filaAlumnos222['ventasSemana'];
      $VentasMes = $filaAlumnos222['ventasMes'];
      $Clientes = $filaAlumnos222['Clientes'];
      $Nueva_Compra = $filaAlumnos222['Nueva_Compra'];
      $Nuevo_Producto = $filaAlumnos222['Nuevo_Producto'];
      $Creditos = $filaAlumnos222['Creditos'];
      $Dejado_Ganar = $filaAlumnos222['Dejado_Ganar'];
      $Listado_Productos = $filaAlumnos222['Listado_Productos'];
      $DolarToday = $filaAlumnos222['DolarToday'];
      $Vender = $filaAlumnos222['Vender'];
      $Consultas = $filaAlumnos222['Consultas'];
    }
  }

  if ($factura == 1) {
    $compras = "nuevaCompraFacturas.php";
  } else {
    $compras = "nuevaCompra.php";
  }


  $query222 = 'SELECT * FROM acces WHERE id="1"';
  $buscarAlumnos222 = $conexion->query($query222);
  if ($buscarAlumnos222->num_rows > 0) {
    while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
      $Inicio = $filaAlumnos222['Inicio'];
      $Ventas = $filaAlumnos222['Ventas'];
      $VentasSemana = $filaAlumnos222['ventasSemana'];
      $VentasMes = $filaAlumnos222['ventasMes'];
      $Clientes = $filaAlumnos222['Clientes'];
      $Nueva_Compra = $filaAlumnos222['Nueva_Compra'];
      $Nuevo_Producto = $filaAlumnos222['Nuevo_Producto'];
      $Creditos = $filaAlumnos222['Creditos'];
      $Dejado_Ganar = $filaAlumnos222['Dejado_Ganar'];
      $Listado_Productos = $filaAlumnos222['Listado_Productos'];
      $DolarToday = $filaAlumnos222['DolarToday'];
      $Vender = $filaAlumnos222['Vender'];
      $Consultas = $filaAlumnos222['Consultas'];
    }
  }

  if ($Inicio == 1) {
    $Inicio = '<li><a href="index.php">Inicio</a></li>';
  } else {
    $Inicio = "";
  }

  if ($Clientes == 1) {
    $Clientes = '<li><a href="clientes.php">Clientes</a></li>';
  } else {
    $Clientes = "";
  }


  if ($Ventas == 1) {
    $Ventas = '<li><a href="listaVentas.php">Ventas del dia</a></li>';
  } else {
    $Ventas = "";
  }

  if ($VentasSemana == 1) {
    $VentasSemana = '<li><a href="resumenSemana.php">Ventas de la semana</a></li>';
  } else {
    $VentasSemana = "";
  }


  if ($VentasMes == 1) {
    $VentasMes = '<li><a href="resumenMes.php">Ventas del mes</a></li>';
  } else {
    $VentasMes = "";
  }


  if ($Nueva_Compra == 1) {
    $Nueva_Compra = '<li><a href="' . $compras . '">Nueva Compra</a></li>';
  } else {
    $Nueva_Compra = "";
  }
  if ($Nuevo_Producto == 1) {
    $Nuevo_Producto = ' <li><a href="nuevoProducto.php">Nuevo Producto</a></li> ';
  } else {
    $Nuevo_Producto = "";
  }


  if ($Creditos == 1) {
    $Creditos = '<li><a href="creditos.php">Creditos</a></li>';
  } else {
    $Creditos = "";
  }
  if ($Dejado_Ganar == 1) {
    $Dejado_Ganar = '<li><a href="descontado.php">Dejado de Ganar</a></li>';
  } else {
    $Dejado_Ganar = "";
  }
  if ($Listado_Productos == 1) {
    $Listado_Productos = '<li><a href="productos.php">Listado de Productos</a></li>';
  } else {
    $Listado_Productos = "";
  }


  if ($DolarToday == 1) {
    $DolarToday = '<li><a href="dolartoday.php"> <i class="fa fa-dollar"></i>DolarToday</a></li>';
  } else {
    $DolarToday = "";
  }
  if ($Vender == 1) {
    $Vender = '<li><a href="ventas.php"><i class="line icon-basket-loaded"></i>Vender</a></li>';
  } else {
    $Vender = "";
  }
  if ($Consultas == 1) {
    $Consultas = '<li><a href="consultaHistorica.php"><i class="line icon-paper-clip"></i>Consultas</a></li>';
  } else {
    $Consultas = "";
  }



  if ($Inicio != "" || $Clientes != "") {
    $init = '  <li><a><i class="line icon-home"></i> Inicio <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                     ' . $Inicio . '

                     ' . $Clientes . '
                     
                    </ul>
                  </li>';
  } else {
    $init = '';
  }


  if ($Ventas != "" || $VentasSemana != "" || $VentasMes != "") {
    $vent = '  <li><a><i class="line icon-wallet"></i> Ventas <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                     ' . $Ventas . '
                     ' . $VentasSemana . '
                     ' . $VentasMes . '
                    </ul>
                  </li>';
  } else {
    $vent = '';
  }






  if ($Nueva_Compra != "" || $Nuevo_Producto != "") {
    $nuev = '  <li><a><i class="line icon-share-alt"></i> Agregar producto <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                       ' . $Nueva_Compra . '    
                       ' . $Nuevo_Producto . '    
                    </ul>
                  </li>
       
       ';
  } else {
    $nuev = '';
  }
  if ($Creditos != "" || $Dejado_Ganar != "" || $Listado_Productos != "") {
    $ctrl = ' 
                   <li><a><i class="line icon-notebook"></i> Control de Stock <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                    ' . $Creditos . '
                    ' . $Dejado_Ganar . '
                    ' . $Listado_Productos . '
                    </ul>
                  </li>';
  } else {
    $ctrl = '';
  }




  $menu =  '
    
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
   
                <ul class="nav side-menu">
             
                   
              ' . $init . '
              ' . $vent . '
              ' . $nuev . '
              ' . $ctrl . '
              ' . $DolarToday . '
              ' . $Vender . '
              ' . $Consultas . '
                   
                  </ul>    
              </div>
            </div>

    



    ';





  global $Inicio;
  global $Ventas;
  global $VentasSemana;
  global $VentasMes;
  global $Clientes;
  global $Nueva_Compra;
  global $Nuevo_Producto;
  global $Creditos;
  global $Dejado_Ganar;
  global $Listado_Productos;
  global $DolarToday;
  global $Vender;
  global $Consultas;




  return $menu;
}





$query222 = 'SELECT * FROM acces WHERE id="1"';
$buscarAlumnos222 = $conexion->query($query222);
if ($buscarAlumnos222->num_rows > 0) {
  while ($filaAlumnos222 = $buscarAlumnos222->fetch_assoc()) {
    $Inicio = $filaAlumnos222['Inicio'];
    $Ventas = $filaAlumnos222['Ventas'];
    $VentasSemana = $filaAlumnos222['ventasSemana'];
    $VentasMes = $filaAlumnos222['ventasMes'];
    $Clientes = $filaAlumnos222['Clientes'];
    $Nueva_Compra = $filaAlumnos222['Nueva_Compra'];
    $Nuevo_Producto = $filaAlumnos222['Nuevo_Producto'];
    $Creditos = $filaAlumnos222['Creditos'];
    $Dejado_Ganar = $filaAlumnos222['Dejado_Ganar'];
    $Listado_Productos = $filaAlumnos222['Listado_Productos'];
    $DolarToday = $filaAlumnos222['DolarToday'];
    $Vender = $filaAlumnos222['Vender'];
    $Consultas = $filaAlumnos222['Consultas'];
  }
}
