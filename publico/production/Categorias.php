<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');



if($_SESSION['nivel']==1 || $_SESSION['nivel']==2){
$topnav = topnav();
if($_SESSION["validate"]!="ok"){
    define('PAGINA_INICIO','../../index.php');
	header('Location: '.PAGINA_INICIO);
}
if ( $_SESSION['nivel'] == '1' ) {
    $menu = MenuAdministrador();
} else {
    $menu = MenuStandar();
    if($Listado_Productos == 0){
    define( 'PAGINA_INICIO', '../../index.php' );
    header( 'Location: '.PAGINA_INICIO );
           }

}

$nivelUsuario = $_SESSION['nivel'];
$nombreUsuario = $_SESSION['nombre'];

$query = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumnos = $conexion->query( $query );
if ( $buscarAlumnos->num_rows > 0 ) {
    while( $filaAlumnos = $buscarAlumnos->fetch_assoc() )
    {

        $PesoDolar = $filaAlumnos['pesoDolar'];
        $Pesobolivar = $filaAlumnos['peso_bolivar'];
        $bolivarPesoTrans = $filaAlumnos['bolivarPesoTrans'];
  $dolarBolivar = $filaAlumnos['DolarBolivar'];
  $pesoBolivarPublicacion = $filaAlumnos['bolivarPesoVenta'];
    }
}
$query2 = "SELECT * FROM empresa";
$buscarAlumnos2 = $conexion->query( $query2 );
if ( $buscarAlumnos2->num_rows > 0 ) {
    while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() )
    {
        $nombreEmpresa = $filaAlumnos2['emp'];
        $stockCritico = $filaAlumnos2['stockCritico'];
    }
}



// initializ shopping cart class
include 'La-carta.php';
$cart = new Cart;

?>
<!DOCTYPE html>
<html lang="en">
  <head>
      <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='icon' href='images/favicon.ico' type='image/ico' />

    <title>Categorias</title>
 
        <!-- Bootstrap -->
        <link href='../vendors/bootstrap/dist/css/bootstrap.min.css' rel='stylesheet'>
        <!-- Font Awesome -->
        <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
        <!-- NProgress -->
        <link href='../vendors/nprogress/nprogress.css' rel='stylesheet'>
        <!-- iCheck -->
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">

        <!-- iCheck -->
        <link rel="stylesheet" href="../../iseller.es/css/animate.css">
        <!-- Icomoon Icon Fonts-->
        <link rel="stylesheet" href="../../iseller.es/css/icomoon.css">
        <!-- Simple Line Icons -->
        <link rel="stylesheet" href="../../iseller.es/css/simple-line-icons.css">
        <link href='../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css' rel='stylesheet'>
        <link href='../vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css' rel='stylesheet'>
        <!-- JQVMap -->
        <link href='../vendors/jqvmap/dist/jqvmap.min.css' rel='stylesheet' />
        <!-- bootstrap-daterangepicker -->
        <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
        <link href="js/jquerysctipttop.css" rel="stylesheet" type="text/css">
        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>
    <script src='js/jquery.min.js'></script>
      <script src='peticion.js'></script>
    <script src='peticion_producto.js'></script>
    <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
    <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
    <script src='..//assets/AlertifyJS/alertify.min.js'></script>
    <script src="ex/jquery.min.js"></script>
    <script src="ex/bootstrap.min.js"></script>
     
    <?php

@$accion = $_GET['accion'];

switch( $accion ) {
    case( 'agregada' ):
    echo '<script>
            function mensaje(){	
			alertify.success("Se agrego correctamente"); }
            </script>
            <body onload="mensaje()">
            </body>';

    break;

    case( 'editado' ):
    echo '<script>
            function mensaje(){	
			alertify.success("Producto Actualizado."); }
            </script>
            <body onload="mensaje()">
            </body>';
    break;

    case( 'favorito-SI' ):
    echo '<script>
            function mensaje(){	
			alertify.success("Agregado a favoritos.");}
            </script>
            <body onload="mensaje()">
            </body>';
    break;

    case( 'favorito-NO' ):
    echo '<script>
            function mensaje(){	
			alertify.success("Quitado de favoritos.");}
            </script>
            <body onload="mensaje()">
            </body>';
    break;
    case( 'editado' ):
    echo '<script>
            function mensaje(){	
			alertify.success("Editado correctamente.");}
            </script>
            <body onload="mensaje()">
            </body>';
    break;
    case( 'editado_error_letra' ):
    echo '<script>
            function mensaje(){	
			alertify.error("No puede cambiar la inicial del nombre del producto.");}
            </script>
            <body onload="mensaje()">
            </body>';
    break;


}

?>


    
  </head>

  <body class="nav-md">
    <div class="container body">
      <div class="main_container">
        <div class="col-md-3 left_col">
          
           <div class='left_col scroll-view'>
                    <div class='navbar nav_title' style='border: 0;'>
                        <a href='index.php' class='site_title'><img src="images/logo1-inv-compact.png" style="max-width:45px" >  <span><img src='images/LETTER.png' style='max-width:140px'><span></a>
                    </div>

                    <div class='clearfix'></div>

                    <!-- menu profile quick info -->
                    <div class='profile clearfix'>
                        <div class='profile_pic'>
                            <img src='images/img.png' alt='...' class='img-circle profile_img'>
                        </div>
                        <div class='profile_info'>
                            <h2><?php echo $nombreUsuario ?></h2>
                            <span><?php if ( $nivelUsuario == '1' ) {
    echo 'Administrador';
} else {
    echo 'Empleado';
}
?></span>
                        </div>
                    </div>
                    <!-- /menu profile quick info -->

                    <br />

                    <?php echo $menu ?>

                </div>
        </div>

        <!-- top navigation -->
               <?php echo $topnav ?>
        <!-- /top navigation -->
<style>
  
 .gray {
                    color: rgba(52, 73, 94, 0.94);
                    font-size: 24px;

 
                }  
    
    .fav {
                    color: #1ABB9C;
                    font-size: 24px;

 
                }    
    
    .nofav {
                    color: lightgray;
                    font-size: 24px;

 
                }  
         
                    .favorite{
                        float: left
                    }
                    .actualizar {
                        float: right;
                    }

                    .texto {
                        position: absolute;
                        margin: auto;
                        transform: translate(-50%, 0px) scale(1);
                    }

    .fileti{
        font-size: 12px;
        
    }
       
                 

    
                </style>
          
        		
						
        <!-- page content -->
        <div class="right_col" >
            <div class='page-title'>
                        <div class='title_left'>
                            <div class='col-md-5 col-sm-5  form-group pull-right'>
                                <div class='input-group'>
                                   <h3>Categorias</h3>
                                </div>
                            </div>
                        </div>

                        
                    </div>
            <div class="clearfix"></div>
            <div class="row">
            
              
               
                
        
                                <?php
    
    @$accionE = $_GET['nueva'];
    @$codeEditar = $_GET['id'];
               
    if($accionE == "categoria"){
     
       $query7E = $conexion->query("SELECT * FROM productos WHERE codigo='$codeEditar' LIMIT 1");
        if($query7E->num_rows > 0){ 
           $tabla7E='';
            while($row7E = $query7E->fetch_assoc()){
   $nombrePro = $row7E["nombre"];
   $PrecioPro = $row7E["precio_compra"];
   $CantidadPro = $row7E["cantidad_unidades"];
   $porcentajePro = $row7E["porcentaje"];
                
    	
                
   }

  }  
         $visible = ""; 
    }else{
        $visible = "contain: strict"; 
    }
    ?>
        <style>
            .right_col{
                min-height: 940px !important;
            }
                </style>
        <div class="col-lg-12" style="<?php echo $visible ?>">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Nueva Categoria</h2>
                    <ul class="nav navbar-right panel_toolbox">

                    <div data-toggle="tooltip" data-placement="top" title="categoria">
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                      </div>
                      
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                      
                     
                        
                    <form name='calculadora' action='../../configurar/categorias.php' method='post' enctype="multipart/form-data" id="form-group" data-parsley-validate class='form-horizontal form-label-left'>
                        <div class='row' >
                            <div class='col-md-12 col-sm-12 '>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Datos de la categoria <small>* obligatorio</small></h2>
                                        <div class='clearfix'></div>
                                    </div>
                                    <div class='x_content'>
                                        <br />
                                        <div class='item form-group'>
                                            <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Nombre <span class='required'>*</span>
                                            </label>
                                           
                                            <div class='col-md-9 col-sm-9 '>
                                                <input type='text' id='nombre' name='nombreK' required='required' class='form-control ' placeholder='Nombre de la Categoria'>
                                            </div>
                                        </div>


                                        <div class='item form-group'>
                                        
                                            <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Descripcion <span class='required'>*</span>
                                            </label>
                                            <div class='col-md-9 col-sm-9 '>
                                                <input type='text'  name='desc' required='required' class='form-control ' placeholder='Descripcion de la categoria' >
                                            </div>
                                        </div>

                                        <div class='item form-group'>
                                            <label class='col-form-label col-md-3 col-sm-3 '  for='first-name'>Reglas <span class='required'>*</span>
                                            </label>
                                            <div class='col-md-9 col-sm-9 '>
                                                <input type='text' id='cantidad' name='cantidad' disabled class='form-control ' placeholder='Reglas aplicadas (muy pronto)'>
                                            </div>
                                        </div>
                                        <br>
                                        <button type='submit' class="btn btn-success actualizar">Agregar</button>

                                        </div>

                                     

                                    </div>
                                </div>
                            </div>





                            



                        </div>

                    </form>
 
                         
                         
                         
                         
                   
                         
                         
            </div>
                </div>
              </div> 
        
        
        
        
        
        
        
        
        
        
        
        <style>
        .icono2{
            margin-top: -3px;
            color: lightgray;
            font-size: 22px;
            margin-right: 6px;
        }
        </style>
        
         <div class="col-lg-12">
                <div class="x_panel">
                  <div class="x_title">
                    <h2>Categorias</h2>
                    <ul class="nav navbar-right panel_toolbox">
                   
                   
                   
               
                      <li><a data-toggle="tooltip" data-placement="left" title="Nueva Categoria" href="?nueva=categoria"><i class="icono2 fa fa-plus-circle"></i></a>
                      </li>
                   
                      <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                      </li>
                   
                      
                    </ul>
                    <div class="clearfix"></div>
                  </div>
                  <div class="x_content">
                      <div class="row">
                          
                           
                           
                           
                           
                           
                           <div class="col-lg-12">
                            <div class="card-box table-responsive">
                    <p class="text-muted font-13 m-b-30">
                      Lista general de los productos agregados al inventario.
                    </p>
                    <table id="datatable-responsive" class="table table-striped table-bordered" style="width:100%">
                      <thead>
                          <tr class="headings">
                            <th>#</th>
                            <th>Nombre </th>
                            <th>descripcion</th>
                            <th>Reglas</th>
                            </th>
                          </tr>
                      </thead>
                   <tbody>
          <?php
          $query6 = $conexion->query("SELECT * FROM categorias");
        if($query6->num_rows > 0){ 
           $contador = 1;
            while($row6 = $query6->fetch_assoc()){
    	$tabla6.='<tr class="even pointer">
                            <td class=" ">'.$contador++.'</td>
                            <td class=" ">'.$row6["nombre_categoria"].'</td>
                            <td class=" ">'.$row6["descripcion"].'</td>
                            <td class=" ">Ninguna</td>
                          </tr>';
   }echo $tabla6;
  }
?> 
                        </tbody>
                    </table>
                  </div>
                  </div>
              </div>
            </div>
                </div>
              </div>
            </div>
        </div>
        <footer>
          <div class="pull-right">
            i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
          </div>
          <div class="clearfix"></div>
        </footer>
        <!-- /footer content -->
      </div>
    </div>

    <!-- jQuery -->
    <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <!-- Chart.js -->
        <script src='../vendors/Chart.js/dist/Chart.min.js'></script>
        <!-- gauge.js -->
        <script src='../vendors/gauge.js/dist/gauge.min.js'></script>
        <!-- bootstrap-progressbar -->

        <!-- Flot -->

        <script src='../vendors/flot.orderbars/js/jquery.flot.orderBars.js'></script>
        <script src='../vendors/flot-spline/js/jquery.flot.spline.min.js'></script>
        <script src='../vendors/flot.curvedlines/curvedLines.js'></script>
        <!-- DateJS -->
        <script src='../vendors/DateJS/build/date.js'></script>
        <!-- JQVMap -->
        <script src='../vendors/jqvmap/dist/jquery.vmap.js'></script>
        <script src='../vendors/jqvmap/dist/maps/jquery.vmap.world.js'></script>
        <script src='../vendors/jqvmap/examples/js/jquery.vmap.sampledata.js'></script>
        <!-- bootstrap-daterangepicker -->
        <script src='../vendors/moment/min/moment.min.js'></script>
        <script src='../vendors/bootstrap-daterangepicker/daterangepicker.js'></script>



        <script src='../vendors/datatables.net/js/jquery.dataTables.min.js'></script>
        <script src='../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/dataTables.buttons.min.js'></script>
        <script src='../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.flash.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.html5.min.js'></script>
        <script src='../vendors/datatables.net-buttons/js/buttons.print.min.js'></script>
        <script src='../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js'></script>
        <script src='../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js'></script>
        <script src='../vendors/datatables.net-responsive/js/dataTables.responsive.min.js'></script>
        <script src='../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js'></script>
        <script src='../vendors/datatables.net-scroller/js/dataTables.scroller.min.js'></script>
        <script src='../vendors/jszip/dist/jszip.min.js'></script>
        <script src='../vendors/pdfmake/build/pdfmake.min.js'></script>
        <script src='../vendors/pdfmake/build/vfs_fonts.js'></script>

    <!-- Custom Theme Scripts -->
    <script src="../build/js/custom.min.js"></script>
  </body>
</html>
<?php
}else{
    define('PAGINA_INICIO','../../index.php');
    header('Location: '.PAGINA_INICIO);
}
?>