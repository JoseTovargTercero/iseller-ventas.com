<?php

require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
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

    <title>I-SELLER </title>
    <link href='../assets/css/bootstrap.min.css' rel='stylesheet'>
    <link href='../assets/css/custom.min.css' rel='stylesheet'>
    <!-- Font Awesome -->
    <link href='../vendors/font-awesome/css/font-awesome.min.css' rel='stylesheet'>
    <script src='js/jquery.min.js'></script>
    <link rel='stylesheet' href='..//assets/AlertifyJS/css/alertify.min.css' />
    <link rel='stylesheet' href='..//assets/AlertifyJS/css/themes/semantic.min.css' />
    <script src='..//assets/AlertifyJS/alertify.min.js'></script>
</head>

<body class='login'>
    <div>
        <a class='hiddenanchor' id='signup'></a>
        <a class='hiddenanchor' id='signin'></a>

        <div class='login_wrapper'>

            <div class='row'>
                <div class='col-md-12 col-sm12  '>
                    <div class='x_panel' style='height:550px;'>
                        <div class='x_title'>
                            <h2>USUARIO </h2>

                            <div class='clearfix'></div>
                        </div>

                        <div class='x_content'>
                            <div class='row'>


                                <!-- price element -->
                                <div class='col-md-12 col-sm-12  '>
                                    <div class='pricing  ui-ribbon-container'>

                                        <div class='title'>
                                            <h2>PASO 3/3 - REGISTRO DE USUARIO</h2>
                                            <h1>'I-SELLER '</h1>
                                            <span>LITE</span>

                                        </div>
                                        <div class='x_content'>
                                            <div class=''>
                                                <div class='pricing_features'>
                                                    <div class='col-md-12 col-sm12'>

                                                        <div class='x_content altoScroll'>
                                                            <form class='' action='../../configurar/agguser2.php' method='post' novalidate>
                                                                <div class='field item form-group'>
                                                                    <label class='col-form-label col-md-3 col-sm-3  label-align'>Nombre<span class='required'>*</span></label>
                                                                    <div class='col-md-9 col-sm-9'>
                                                                        <input class='form-control' data-validate-length-range='6' data-validate-words='2' name='name' placeholder='Jhon Doe' required='required' />
                                                                    </div>
                                                                </div>


                                                                <div class='field item form-group'>
                                                                    <label class='col-form-label col-md-3 col-sm-3  label-align'>Nombre de Usuario<span class='required'>*</span></label>
                                                                    <div class='col-md-9 col-sm-9'>
                                                                        <input class='form-control' name='user' placeholder='' required='required' />
                                                                    </div>
                                                                </div>



                                                                <div class='field item form-group'>
                                                                    <label class='col-form-label col-md-3 col-sm-3  label-align'>Nivel<span class='required'>*</span></label>
                                                                    <div class='col-md-9 col-sm-9'>
                                                                        <select name="nivel" required='required' class='form-control' id="nivel">
                                                                            <option value="1">Administrador</option>
                                                                        </select>
                                                                    </div>
                                                                </div>


                                                                <div class='field item form-group'>
                                                                    <label class='col-form-label col-md-3 col-sm-3  label-align'>Contraseña<span class='required'>*</span></label>
                                                                    <div class='col-md-9 col-sm-9'>
                                                                        <input class='form-control' type='password' name='password' data-validate-length='6,7,8,9,10,11,12' placeholder="De 6 a 12 caracteres" required='required' />
                                                                    </div>
                                                                </div>
                                                                <div class='field item form-group'>
                                                                    <label class='col-form-label col-md-3 col-sm-3  label-align'>Repetir Contraseña<span class='required'>*</span></label>
                                                                    <div class='col-md-9 col-sm-9'>
                                                                        <input class='form-control' type='password' name='password2' data-validate-linked='password' required='required' />
                                                                    </div>
                                                                </div> <br>

                                                                <button class='btn btn-success right'>Registrar</button>
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
            </div>



            <div class='center'>
                <h1>
                    <img class='center' src='images/logo1-inv-MI-NO-FONDO.png' height='115px'>

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







    <script src='https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js'></script>
    <script src='../vendors/validator/multifield.js'></script>
    <script src='../vendors/validator/validator.js'></script>

    <script>
        // initialize a validator instance from the 'FormValidator' constructor.
        // A '<form>' element is optionally passed as an argument, but is not a must
        var validator = new FormValidator({
            'events': ['blur', 'input', 'change']
        }, document.forms[0]);
        // on form 'submit' event
        document.forms[0].onsubmit = function(e) {
            var submit = true,
                validatorResult = validator.checkAll(this);
            console.log(validatorResult);
            return !!validatorResult.valid;
        };
        // on form 'reset' event
        document.forms[0].onreset = function(e) {
            validator.reset();
        };
        // stuff related ONLY for this demo page:
        $('.toggleValidationTooltips').change(function() {
            validator.settings.alerts = !this.checked;
            if (this.checked)
                $('form .alert').remove();
        }).prop('checked', false);
    </script>

    <!-- jQuery -->
    <script src='../vendors/jquery/dist/jquery.min.js'></script>
    <!-- Bootstrap -->
    <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
    <!-- FastClick -->
    <script src='../vendors/fastclick/lib/fastclick.js'></script>
    <!-- NProgress -->
    <script src='../vendors/nprogress/nprogress.js'></script>
    <!-- validator -->
    <!-- <script src = '../vendors/validator/validator.js'></script> -->

    <!-- Custom Theme Scripts -->
    <script src='../build/js/custom.min.js'></script>

</body>


















</html>