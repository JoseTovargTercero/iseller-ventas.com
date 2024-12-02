<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');
require_once('includes/darkModeAct.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    
    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
   

    $topnav = topnav();


    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Vender == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }

    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    $query = "SELECT * FROM cambio WHERE id='1'";
    $buscarAlumnos = $conexion->query($query);
    if ($buscarAlumnos->num_rows > 0) {
        while ($filaAlumnos = $buscarAlumnos->fetch_assoc()) {
            $dolarBolivar = $filaAlumnos['DolarBolivar'];
            $pesoBolivarPublicacion = $filaAlumnos['bolivarPesoVenta'];
        }
    }

    $query3 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query3);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }

    $query2 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
        }
    }




    include 'La-carta.php';
    $cart = new Cart;
    /*
if(isset($_GET['id'])){
    $rere = $_GET['id'];
    header( 'Location: detalle.php?id='.$rere.'' );
}*/


$_SESSION["ventas"] = "activa";
if($_SESSION["dist_ventas"] == "activa"){
    unset($_SESSION["dist_ventas"]);
    $cart->destroy();

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

        <title>Ventas </title>

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

        <!-- Custom Theme Style -->
        <link href='../build/css/custom.min.css' rel='stylesheet'>


        <script src='js/jquery.min.js'></script>
        <script src='peticion.js'></script>
        <script src='peticion_producto.js'></script>

        <link href="publico/assets/css/bootstrap.min.css" rel="stylesheet">
        <link href="publico/assets/css/custom.min.css" rel="stylesheet">
        <link rel='stylesheet' href='../assets/AlertifyJS/css/alertify.min.css' />
        <link rel='stylesheet' href='../assets/AlertifyJS/css/themes/semantic.min.css' />
        <script src='..//assets/AlertifyJS/alertify.min.js'></script>

        <script src="ex/jquery.min.js"></script>
        <script src="ex/bootstrap.min.js"></script>

        <?php
        switch ($_GET['accion']) {
            case ('vendido'):
                echo '<script>
          function mensajeVenta(){	
          alertify.success("Exito al vender");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
                break;
            case ('credito'):
                echo '<script>
          function mensajeVenta(){	
          alertify.warning("Se acreditaron productos");  }
                </script>
                <body onload="mensajeVenta()">
                </body>';
                break;
            case ('descuento'):
                echo '<script>
          function mensajeVenta(){	
          alertify.warning("Se descontaron productos");  }
                </script>
                <body onload="mensajeVenta()">
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
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->
                <!-- page content -->





                <div class='right_col' role='main'>
                    <div class=''>
                        
                    <h4>Ventas</h4>
                         <p style="margin-top: -10px;">Caja de despacho</p>







                        <div class="row   fadeInUp animated">
                            <div class="col-lg-12">
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2 style="font-size: 15px; font-weight: bold">Escanear codigo </h2>
                                        <div class="clearfix"></div>
                                    </div>
                                        <div class="col-lg-12" style="display: grid; place-items: center;">
                                        <?php
                                            
                                         $endCode = $_SESSION['qrcode'];

                                            echo $endCode;
                                        ?>

                                            

                                            <br>
                                            <img alt="Código QR" id="codigo">
                                            <br>
                                            <br>
                                            Lorem ipsum dolor, sit amet consectetur adipisicing elit. Reiciendis aut distinctio suscipit illo ipsam! Natus sequi ipsam vero accusamus a consequatur aliquam, pariatur eligendi in! Voluptas non inventore fuga similique?
                                            <br>
                                            <br>

                                            

                                        </div>
                                </div>
                            </div>
                        </div>


                    </div>


                    <script>
		const $imagen = document.querySelector("#codigo");
        var valor = "<?php echo $endCode ?>";
		new QRious({
			element: $imagen,
			value: valor, // La URL o el texto
			size: 400,
			backgroundAlpha: 0, // 0 para fondo transparente
			foreground: "#000", // Color del QR
			level: "H", // Puede ser L,M,Q y H (L es el de menor nivel, H el mayor)
		});
		
	</script>

                </div>
                <!-- /page content -->
                <style>
                    .btn-soap {
                        color: #fff !important;
                        background-color: #6c757d !important;
                        border-color: #6c757d !important;
                        cursor: pointer;
                    }

                    .btn-soap:hover {

                        background-color: #2A4058 !important;


                    }


                    .timeline {
                        display: flex;

                    }

                    .card {
                        border-radius: 10px;
                        position: relative;
                        min-width: 220px;
                        height: 295px;
                        background: #f9f9f9;
                        opacity: 0.8;
                        overflow: hidden;
                        border: 1px solid #ced4da;
                        float: left;
                        margin-right: 15px;

                    }

                    .card::before {
                        content: '';
                        position: absolute;
                        top: -50%;
                        width: 100%;
                        height: 100%;
                        opacity: 0.8;
                        background: #40c1af !important;
                        transform: skewY(345deg);
                        transition: 0.5s;
                    }

                    .card:hover::before {
                        top: -70%;
                        transform: skewY(390deg);
                    }

                    .card::after {
                        content: '';
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        font-weight: 600;
                        font-size: 5em;
                        color: rgba(0, 0, 90, 0.1);
                    }

                    .card .imgBx {
                        position: relative;
                        width: 100%;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        padding-top: 20px;
                        z-index: 1;
                    }

                    .card .imgBx img {
                        max-width: 70%;
                        transition: 0.5s;

                    }

                    .card:hover .imgBx img {
                        max-width: 60%;


                    }

                    .card .contentBx {
                        position: relative;
                        padding: 20px;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        flex-direction: column;
                        z-index: 1;
                    }

                    .card .contentBx h3 {
                        font-size: 18px;
                        color: white;
                        font-weight: 500;
                        text-transform: uppercase;
                        letter-spacing: 1px;

                    }

                    .card .contentBx h2 {
                        text-align: -webkit-center;
                        color: white;

                    }

                    .card .contentBx .price {
                        font-size: 24px;
                        color: #6e6e6e;
                        font-weight: 500;
                        letter-spacing: 1px;
                    }

                    .card .contentBx .buy {
                        position: relative;
                        top: 100;

                        text-decoration: none;
                        border-radius: 30px;
                        letter-spacing: 1px;
                        transition: 0.5s;
                    }

                    .card:hover .contentBx .buy {
                        color: #6e6e6e;
                        top: 0;
                        opacity: 1:
                    }
                    .card:hover .contentBx h2 {
                        color: #6e6e6e !important;
                      
                    }
                    .card:hover .contentBx h3 {
                        color: #6e6e6e !important;
                      
                    }

                    .divisas {
                        display: none;
                    }

                    .card:hover .divisas {
                        display: contents;

                    }

                    .card:hover .pesos {
                        display: none;

                    }

                    .col-lg-2 {
                        padding-right: 0 !important;
                        padding-left: 0 !important;
                    }

                    .col-lg-66 {
                        position: relative;
                        min-height: 1px;
                        float: left;
                        padding-right: 10px;
                        padding-left: 10px;
                        -ms-flex: 0 0 50%;
                        flex: 0 0 50%;
                        max-width: 50%;
                    }
                </style>



                <style>
                    .tile-stats {
                        box-shadow: none !important;
                        border-right: 1px solid #f6f6f6
                    }

                    .btn-secondary {
                        color: #909090 !important;
                        background-color: lightgray !important;
                        border-color: lightgray !important;
                    }

                    .vender {
                        bottom: 15px;
                        margin-left: 10px;
                        position: absolute
                    }

                    .contenedoe {
                        overflow-y: scroll;
                    }

                    .contenedoe::-webkit-scrollbar {
                        width: 7px;
                    }

                    .contenedoe::-webkit-scrollbar-thumb {
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 600%);
                        border-radius: 5px;
                    }


                    .formulario {
                        max-height: 30px !important;
                    }

                    .fijo {
                        height: 350px;
                    }

                    .responsi {
                        height: 300px;
                        overflow-y: auto;
                    }

                    .responsi::-webkit-scrollbar {
                        height: 7px;
                        width: 7px;
                        background: #FFF;
                        margin-bottom: 15px;
                    }

                    .responsi::-webkit-scrollbar-thumb {
                        background: -webkit-repeating-linear-gradient(top left, #52d3aa 0%, #3f95ea 600%);
                        ;
                        border-radius: 5px;
                    }






                    .col-car-2 {
                        width: 210px;
                        position: relative;
                        min-height: 1px;
                        float: left;
                        padding-right: 10px;
                        padding-left: 10px;
                    }

                    .tile-stats {
                        height: 160px !important;
                        max-width: 100%;
                    }

                    .tile-stats .icon i {
                        margin: 0;
                        font-size: 40px;
                        line-height: 0;
                        vertical-align: bottom;
                        padding: 0;
                        color: #1ABB9C;
                    }

                    .htres {
                        margin-top: 15px !important;
                        color: dimgray !important;
                    }

                    .htres {
                        font-size: 18px;
                        margin-bottom: 10px !important;
                    }



                    .htress {
                        font-size: 15px;
                    }

                    .tile-stats .icon {
                        width: 100px;
                        height: 70px;
                        color: #BAB8B8;
                        position: absolute;
                        right: 5px;
                        top: 0px !important;
                        z-index: 1 !important;
                    }



                    .fotoProducto {
                        height: 70px;
                        width: 80px;
                    }


                    .right {
                        float: right;
                    }

                    .table {
                        width: 100%;
                       
                        color: #909090 !important;
                    }

                    .table td,
                    .table th {
                        padding: 0.1px !important;
                        vertical-align: initial;
                        border-top: none !important;
                    }

                    .table thead th {
                        vertical-align: bottom;
                        border-bottom: none !important;
                    }

                    .cant {
                        text-align: center;
                        max-width: 60px;
                        min-height: 25px;
                        border: 1px solid #909090 !important;
                        color: #909090 !important;
                        padding-left: 10px;
                        border-radius: 5px;
                        margin-right: 5px
                    }

                    .table td,
                    .table th {
                        padding: .1rem !important;
                    }
                </style>
                <!-- footer content -->
                <footer>
                    <div class='pull-right'>
                        i-SELLER - by <a href=''>Jose Ricardo Tovarg III</a>
                    </div>
                    <div class='clearfix'></div>
                </footer>
                <!-- /footer content -->
            </div>
        </div>

        <script>
            function updateCartItem(obj, id) {
                $.get("cartAction.php", {
                    action: "updateCartItem",
                    id: id,
                    qty: obj.value
                }, function(data) {
                    if (data == 'ok') {
                        location.reload();
                    } else {
                        alert('Cart update failed, please try again.');
                    }
                });
            }

            function confirmarDescuento() {
                var confirm = alertify.confirm('Descontar del almacen', 'Esta apunto de  descontar productos del almacen ¿desea continuar?', null, null).set('labels', {
                    ok: 'Confirmar',
                    cancel: 'Cancelar'
                });
                //callbak al pulsar botón positivo
                confirm.set('onok', function() {
                    window.open("AccionCarta.php?action=placeOrder&statusV=3&valorFinalBs=<?php echo $todoBolivar ?>&valorFinalCop=<?php echo $todoPeso ?>", "_self");
                });
                //callbak al pulsar botón negativo
                confirm.set('oncancel', function() {
                    alertify.error('Cancelado');
                })
            }
        </script>
        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <!-- bootstrap-progressbar -->
        <script src='../vendors/bootstrap-progressbar/bootstrap-progressbar.min.js'></script>
        <!-- Dropzone.js -->
        <script src='../vendors/dropzone/dist/min/dropzone.min.js'></script>

        <!-- iCheck -->
        <script src='../vendors/iCheck/icheck.min.js'></script>
        <!-- bootstrap-daterangepicker -->
        <script src='../vendors/moment/min/moment.min.js'></script>
        <script src='../vendors/bootstrap-daterangepicker/daterangepicker.js'></script>
        <!-- bootstrap-wysiwyg -->
        <script src='../vendors/bootstrap-wysiwyg/js/bootstrap-wysiwyg.min.js'></script>
        <script src='../vendors/jquery.hotkeys/jquery.hotkeys.js'></script>
        <script src='../vendors/google-code-prettify/src/prettify.js'></script>
        <!-- jQuery Tags Input -->
        <script src='../vendors/jquery.tagsinput/src/jquery.tagsinput.js'></script>
        <!-- Switchery -->
        <script src='../vendors/switchery/dist/switchery.min.js'></script>
        <!-- Select2 -->
        <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
        <!-- Parsley -->
        <script src='../vendors/parsleyjs/dist/parsley.min.js'></script>
        <!-- Autosize -->
        <script src='../vendors/autosize/dist/autosize.min.js'></script>
        <!-- jQuery autocomplete -->
        <script src='../vendors/devbridge-autocomplete/dist/jquery.autocomplete.min.js'></script>
        <!-- starrr -->
        <script src='../vendors/starrr/dist/starrr.js'></script>
        <!-- Custom Theme Scripts -->
        <script src='../build/js/custom.min.js'></script>

    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>