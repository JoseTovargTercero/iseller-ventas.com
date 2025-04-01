<?php

require_once('../../configurar/configuracion.php');
require_once('includes/header.php');
require_once('includes/menu.php');




if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    if ($_SESSION['nivel'] == '1') {
        $menu = MenuAdministrador();
    } else {
        $menu = MenuStandar();
        if ($Inicio == 0) {
            define('PAGINA_INICIO', '../../index.php');
            header('Location: ' . PAGINA_INICIO);
        }
    }
    $topnav = topnav();
    $nivelUsuario = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }


?>

    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Sucursales </title>

        <?php
        require_once('includes/headers.php');
        ?>

    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <div class='col-md-3 left_col'>

                    <div class='left_col scroll-view'>
                        <div class='navbar nav_title' style='border: 0;'>
                            <a href='index.php' class='site_title'>
                                <img src='images/logo1-inv-compact.png' style='max-width:147px; opacity: 0.8'> <span>
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
                <div class='right_col'>



                    <h4>Sucursales</h4>
                    <p style="margin-top: -10px;">Gestión de sucursales</p>

                    <div class='row to-animate fadeInRight animated'>
                        <div class='col-lg-12'>


                            <div class='x_panel tile'>

                                <div class='x_title d-flex justify-content-between'>
                                    <h2>Historial de cambios en las tasas</h2>
                                    <button class="btn btn-sm btn-success" id="btn-add-sucursal"> <i class="bx bx-plus"></i> Agregar</button>
                                </div>
                                <div class='x_content'>

                                    <div class='card-box table-responsive'>

                                        <table id='datatable-responsive' class='table table-striped' style='width:100%'>
                                            <thead>
                                                <tr class='headings'>
                                                    <th style='width:10%;' class='column-title'>Usuario</th>
                                                    <th style='width:10%;' class='column-title'>Fecha</th>
                                                    <th style='width:10%;' class='column-title'>Hora</th>
                                                </tr>
                                            </thead>

                                            <tbody>

                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /page content -->
                <?php require('../assets/templates/modal.html'); ?>

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

        <script src="../vendors/nprogress/nprogress.js"></script>

        <script src="../build/js/custom.min.js"></script>
        <script src="../build/js/modal.js"></script>



        <script>
            const formSucursal = `
                <h5 class="mb-3">Registro de sucursales</h5>
                <hr>
                <form id="sucursal-form" class="p-3">
                    <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de sucursal:</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                    <label for="tipo" class="form-label">Tipo de comercio:</label>
                    <input list="tipos-comercio" id="tipo" name="tipo" class="form-control" required>
                    <datalist id="tipos-comercio">
                        <option value="Panadería"></option>
                        <option value="Carnicería"></option>
                        <option value="Heladería"></option>
                        <option value="Frigorífico"></option>
                        <option value="Supermercado"></option>
                        <option value="Verdulería"></option>
                        <option value="Pescadería"></option>
                        <option value="Tienda de Ropa"></option>
                        <option value="Joyería"></option>
                        <option value="Farmacia"></option>
                        <option value="Ferretería"></option>
                        <option value="Papelería"></option>
                        <option value="Librería"></option>
                        <option value="Tienda de Electrónica"></option>
                        <option value="Tienda de Mascotas"></option>
                        <option value="Floristería"></option>
                        <option value="Barbería"></option>
                        <option value="Peluquería"></option>
                        <option value="Restaurante"></option>
                        <option value="Cafetería"></option>
                        <option value="Tienda de Deportes"></option>
                        <option value="Juguetería"></option>
                        <option value="Boutique"></option>
                        <option value="Auto Lavado"></option>
                        <option value="Gimnasio"></option>
                        <option value="Tienda de Muebles"></option>
                        <option value="Centro de Estética"></option>
                        <option value="Tienda de Telefonía"></option>
                    </datalist>
                    </div>

                    <div class="mb-3">
                    <label for="color" class="form-label">Color asociado:</label>
                    <input type="color" id="color" name="color" class="form-control form-control-color">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>`;



            $(document).ready(function() {
                document.getElementById('modal').innerHTML = formSucursal;
                document.getElementById('w-modal').classList.add('modal-w50');




                document.getElementById('btn-add-sucursal').addEventListener('click', function() {
                    showModal()
                })




                const submitForm = (event) => {
                    event.preventDefault(); // Evitar que el formulario recargue la página

                    const formData = new FormData(event.target); // Captura los datos del formulario

                    fetch("procesar_sucursal.php", {
                            method: "POST",
                            body: formData,
                        })
                        .then(response => response.json()) // Suponiendo que el backend responde en JSON
                        .then(data => {
                            if (data.success) {
                                alert("Sucursal guardada correctamente.");
                                modalContainer.classList.remove("active"); // Cierra el modal
                            } else {
                                alert("Hubo un error al guardar la sucursal.");
                            }
                        })
                        .catch(error => console.error("Error en la solicitud:", error));
                };



            })
        </script>



    <?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
    ?>