<?php
require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');

if($_SESSION['nivel']==1 || $_SESSION['nivel']==2){
if ( $_SESSION['nivel'] == '1' ) {
    $menu = MenuAdministrador();
} else {
    $menu = MenuStandar();
    if($Nueva_Compra == 0){
    define( 'PAGINA_INICIO', '../../index.php' );
    header( 'Location: '.PAGINA_INICIO );
    }
}
$topnav = topnav();
$nivelUsuario = $_SESSION['nivel'];
$nombreUsuario = $_SESSION['nombre'];

$query2255 = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumnos225 = $conexion->query($query2255);
if ($buscarAlumnos225->num_rows > 0) {
    while ($filaAlumnos225 = $buscarAlumnos225->fetch_assoc()) {
        $pesoDolar = $filaAlumnos225['pesoDolar'];
        $bsDolar = $filaAlumnos225['DolarBolivar'];
    }
}
$query2 = "SELECT * FROM empresa";
$buscarAlumnos2 = $conexion->query( $query2 );
if ( $buscarAlumnos2->num_rows > 0 ) {
    while( $filaAlumnos2 = $buscarAlumnos2->fetch_assoc() )
    {
        $nombreEmpresa = $filaAlumnos2['emp'];
         $deshacerCompra = $filaAlumnos2['deshacerCompra'];
    }
}
    if($_SESSION["validate"]!="ok"){
    define('PAGINA_INICIO','../../index.php');
	header('Location: '.PAGINA_INICIO);
}
    
?>
<!DOCTYPE html>
<html lang='es'>

<head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='icon' href='images/favicon.ico' type='image/ico' />

    <title>Compras</title>

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
    <!-- bootstrap-wysiwyg -->
    <link href='../vendors/google-code-prettify/bin/prettify.min.css' rel='stylesheet'>
    <!-- Select2 -->
    <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
    <!-- Switchery -->
    <link href='../vendors/switchery/dist/switchery.min.css' rel='stylesheet'>
    <!-- starrr -->
    <link href='../vendors/starrr/dist/starrr.css' rel='stylesheet'>
    <!-- bootstrap-daterangepicker -->
    <link href='../vendors/bootstrap-daterangepicker/daterangepicker.css' rel='stylesheet'>
    <link href="../vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
    <link href="../vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href='../build/css/custom.min.css' rel='stylesheet'>

    <script src='js/jquery.min.js'></script>
    <script src='peticion.js'></script>
    <script src='peticion_codigo_producto.js'></script>

    
    <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
    <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
    <script src='..//assets/AlertifyJS/alertify.min.js'></script>


    <?php
@$registrado = $_GET['agregado'];
$cantidadNueva = $_GET['m1'];
$mensajePrecio = $_GET['m2'];
$mensajePorcentaje = $_GET['m3'];

switch( $registrado ) {
    case( 'correcto' ):
     echo '<script>
function mensaje(){	
			alertify.success("Se han agregado productos al stock");    
		}
                </script>
                <body onload="mensaje()">
                </body>';
    break; 
}

    

    
?>
</head>
<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>
            <div class='col-md-3 left_col'>
            <div class='left_col scroll-view'>
                        <div class='navbar nav_title' style='border: 0;'>
                            <a href='index.php' class='site_title'>
                                <img src='images/logo1-inv-compact.png' style='max-width:45px; opacity: 0.8'> <span>
                                    <img style='max-width:140px'><span> </a>
                        </div>
                        <div class='clearfix'></div>
                        <!-- /menu profile quick info -->
                        <br />
                        <?php echo $menu ?>
                    </div>
                </div>
            </div>
            <!-- top navigation -->
            <?php echo $topnav ?>
            <!-- /top navigation -->
            <!-- page content -->
            <div class='right_col' role='main'>
                <div class=''>
                 
                 
               <div class="col-lg-12">
               <h4>Compras</h4>
                <p style="margin-top: -10px;">Nuevas compras realizadas</p>

          
               </div>
                    <div class='clearfix'></div>
                    <script>
                        $(obtener_registros_precio());
                        function obtener_registros_precio(rep_precio) {
                            $.ajax({
                                    url: 'precio_producto.php',
                                    type: 'POST',
                                    dataType: 'html',
                                    data: {
                                        rep_precio: rep_precio
                                    },
                                })
                                .done(function(resultadoPrecio) {
                                    $("#precio_producto").html(resultadoPrecio);
                                })
                        }
                        $(document).on('change', '#producto', function() {
                            var valorprecio = $(this).val();
                            if (valorprecio != "") {
                                obtener_registros_precio(valorprecio);
                            } else {
                                obtener_registros_precio();
                            }
                        });
                    </script>
                    <?php
    
    @$accionE = $_GET['accion'];
    @$codeEditar = $_GET['id'];
               
    if($accionE == "editar"){
     
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
    }else{
        $visible = "contain: strict"; 
    }
    ?>


                   




<div class="col-lg-6">
                        <form name='calculadora' action='../../configurar/nuevaCompra.php' method='post' id='demo-form2' data-parsley-validate class='form-horizontal form-label-left' enctype="multipart/form-data">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Datos del Producto <small>* obligatorio</small></h2>
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
                                        </li>
                                    </ul>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class='x_content'>
                                        <br />
                                        <div class='item form-group'>
                                            <label class='col-form-label col-md-3 col-sm-3 ' for='first-name'>Producto <span class='required'>*</span>
                                            </label>
                                            <div class='col-md-9 col-sm-9 '>
                                                <input type='text' required="required" class='form-control col-md-3' name='codigo' placeholder="Nombre" id='codigo'>
                                                <span class='form-control  col-md-1 minus'><i class="fa fa-minus"></i></span>
                                                <section id='tabla_resultado_codigo_producto'>
                                                </section>
                                            </div>
                                        </div>
                                        <section id='precio_producto'>
                                        </section>
                                        <div class='ln_solid'></div>
                                        <div class='item form-group'>
                                            <div class='col-md-12 col-sm-12 '>
                                                <input type='submit' style="float: right;" class="btn btn-success actualizar" value="Agregar">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>




                        </div>
                        <div class="col-lg-6">
                            <div class="x_panel">
                                <div class="x_title">
                                    <h2>Compras</h2>
                                    <ul class="nav navbar-right panel_toolbox">
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
                                                            <th class="column-title">#</th>
                                                            <th class="column-title">Fecha </th>
                                                            <th class="column-title">Producto</th>
                                                            <th class="column-title">Cantidad</th>
                                                            <th class="column-title"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                               
                                                                       $query6 = $conexion->query("SELECT * FROM compras  ORDER BY id DESC LIMIT 150");
                                                                        if($query6->num_rows > 0){ 
                                                                           $tabla6='';
                                                                           $contador = 1;
                                                                            while($row6 = $query6->fetch_assoc()){



                                                                                  if($contador <= $deshacerCompra){
                                                                        $deshacer = '<a  class="btn2 btn-secondary" href="../../configurar/vaciarFactura.php?idDeshacer='.$row6["id"].'&origen=simple">Deshacer</a>';
                                                                    }else{
                                                                        $deshacer = '';
                                                                    }


                                                                    	$tabla6.='
                                                                          <tr class="even pointer">
                                                                                            <td class=" ">'.$contador++.'</td>
                                                                                            <td class=" ">'.$row6["fecha"].'</td>
                                                                                            <td class=" ">'.$row6["producto"].'</td>
                                                                                            <td class=" ">'.$row6["cantidad"].'</td>

                                                                                            <td class="a-right a-right ">'.$deshacer.'</td>

                                                                                          </tr>







                                                                       ';

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
                <style>
                    .icono{
                        font-size: 25px;
                    }
                    .btn2 {

    font-weight: 400;
    color: #797979;
    background-color: lightgray;
    text-align: center;
    vertical-align: middle;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-color: transparent;
    border: 1px solid transparent;
    padding: .375rem .75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: .25rem;
    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}
                    

                    .gray {
                        color: rgba(52, 73, 94, 0.94);
                        font-size: 26px;


                    }

                        .minus {
                            color: lightgray !important;
                            border: none !important;
                            margin-top: 3px;
                        }

                        .fotoProducto {
                            width: 99.4%;
                            height: 298px;
                            margin-left: 0.3%;
                            margin-top: 1px;

                        }

                        .bordeFoto {
                            border: 2px solid #909090;
                            margin-left: 10%;
                            height: 304px;
                            background-color: lightgray;


                        }

                    </style>

            </div>
            <!-- /page content -->

            <!-- footer content -->
            <footer>
                <div class='pull-right'>
                    i-SELLER - by <a href="#">Jose Ricardo Tovarg III</a>
                </div>
                <div class='clearfix'></div>
            </footer>
            <!-- /footer content -->
        </div>
    </div>

    <!-- jQuery -->
    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FastClick -->
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="../vendors/nprogress/nprogress.js"></script>
    <!-- iCheck -->
    <script src="../vendors/iCheck/icheck.min.js"></script>
    <!-- Datatables -->
    <script src="../vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="../vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="../vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="../vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="../vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="../vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="../vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="../vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
    <script src="../vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
    <script src="../vendors/jszip/dist/jszip.min.js"></script>
    <script src="../vendors/pdfmake/build/pdfmake.min.js"></script>
    <script src="../vendors/pdfmake/build/vfs_fonts.js"></script>

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
