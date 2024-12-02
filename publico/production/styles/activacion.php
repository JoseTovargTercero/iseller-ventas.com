<?php
require_once( '../../../configurar/configuracion.php' );


?>
<!DOCTYPE html>
<html lang='es'>

<head>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <link rel='icon' href='../publico/production/images/favicon.ico' type='image/ico' />

    <title>I-SELLER </title>

    <!-- Font Awesome -->
    <link href='../../../publico/vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
    <!-- NProgress -->
    <script src='../../../publico/production/js/jquery.min.js'></script>

    <link href='../../../publico/assets/css/bootstrap.min.css' rel='stylesheet'>
    <link href='../../../publico/assets/css/custom.min.css' rel='stylesheet'>


    <link rel='stylesheet' href='../../../publico/assets/AlertifyJS/css/alertify.min.css' />
    <link rel='stylesheet' href='../../../publico/assets/AlertifyJS/css/themes/semantic.min.css' />
    <script src='../../../publico/assets/AlertifyJS/alertify.min.js'></script>

<?php
    if($_GET['accion']){
          echo '<script>
            function mensajeExito(){
            alertify.error("Clave de Activacion Incorrecta");}
            </script>
            <body onload="mensajeExito()"></body>';
}

?>





</head>

<body class='login'>
    <div>
        <a class='hiddenanchor' id='signup'></a>
        <a class='hiddenanchor' id='signin'></a>

        <div class='login_wrapper'>

            <div class='row'>
                <div class='col-md-12 col-sm12  '>
                    <div class='x_panel' style='height:500px;'>
                        <div class='x_title'>
                      

                            <div class='clearfix'></div>
                        </div>

                        <div class='x_content'>
                            <div class='row'>

                                <div class='col-md-12'>

                                    <!-- price element -->
                                    <div class='col-md-12 col-sm-12  '>
                                        <div class='pricing  ui-ribbon-container'>

                                            <div class='title'>
                                                <h2>ACTIVACION</h2>
                                                <h1>'I-SELLER  - VERSION UNLIMITED'</h1>
                                                <span>LITE</span>

                                            </div>
                                            <div class='x_content'>
                                                <div class=''>
                                                    <div class='pricing_features'>
                                                        <p style='text-align:center; font-size: 16px;'>
                                                            Codigo del sistema: <br> <br>
                                                            <strong><?php echo $code ?></strong>
                                             

                                                            <br>
                                                            <br>
                                                        </p>

                                                        <form action='validate.php' method='post'>
                                                            <label for='clave' class='col-lg-4' style='margin-top: 10px;'>Clave de Producto:</label>
                                                            <input type='text' hidden placeholder='Clave de producto suministrada por soporte tecnico.' name='div' id='div'  value="<?php echo $div ?>" class='form-control col-lg-8'>
                                                            <input type='text' placeholder='Clave de producto suministrada por soporte tecnico.' name='clave' id='clave' class='form-control col-lg-8'>
                                                            <input type="submit" class="btn btn-success siguinete" value="Validar">
                                                        </form>

                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           

            <div class='center'>
                <h1>
                    <img class='center' src='../../../publico/production/images/logo1-inv-MI-NO-FONDO.png' height='115px'>

                </h1>
                <p> © GLOSTER III C.A. </p>

            </div>

        </div>
    </div>
    <style>
        .login_wrapper {
            max-width: 50% !important;
        }

        .nombre {
            font-weight: 800
        }

        .center {
            text-align: center;
        }

        .siguinete {
            float: right;
            margin-top: 25px;
        }

        .x_panel {
            background-color: rgba(255, 255, 255, .2) !important;
        }

        .text-g {
            font-size: 15px !important;
        }

        .cerrar {
            float: left;
            margin-top: 25px;
        }

    </style>

</body>

</html>
