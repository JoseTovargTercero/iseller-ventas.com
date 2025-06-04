<?php
require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {


    $topnav = topnav();


    $tipo_u =  $_SESSION['nivel'];

    $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

    if ($tipo_u == 2) {
        // Solo para los usuarios tipo 2
        $id_sucursal = $_SESSION['sucursal'];
        $query .= " AND id='$id_sucursal'";
    }

    $query .= "  ORDER BY principal DESC";

    $stmt = mysqli_prepare($conexion, $query);
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sucursales = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $sucursales[] = $row;
        }
    }
    $stmt->close();

?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Agregar Producto</title>
        <?php require_once('includes/headers.php'); ?>
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
    </head>

    <style>
        .tabs section {
            display: none;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .tabs section.active {
            display: block;
            opacity: 1;
        }
    </style>

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
                <div class='right_col ' role='main'>
                    <div class='fadeInRight animated'>
                        <h4>Nuevo producto</h4>
                        <p style="margin-top: -10px;">Agregar un producto nuevo al stock</p>
                        <div class='clearfix'></div>
                        <div class='row '>
                            <div style="width: 70%;" class=' m-auto'>
                                <div class='x_panel'>
                                    <div class='x_title'>
                                        <h2>Datos del Producto</h2>
                                    </div>
                                    <hr class="mt-2 mb-2">
                                    <div class='x_content pt-3'>
                                        <form id='form-data' action='../../configurar/agregarProducto.php' method='post' class='form-horizontal form-label-left'>


                                            <?php if ($tipo_u == 1): ?>
                                                <div class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Sucursal </label>
                                                    <select class="form-control" id="sucursal" name="sucursal">
                                                        <?php if (count($sucursales) > 1): ?>
                                                            <option value="">-- Seleccione --</option>
                                                        <?php endif; ?>

                                                        <?php foreach ($sucursales as $row): ?>
                                                            <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php endif; ?>


                                            <div class="mb-3">
                                                <div class="row ">

                                                    <div class='col-md-5  col-sm-5'>
                                                        <label class='col-form-label' for='filtro'>Filtro</label>

                                                        <input type='text' id='filtro' required placeholder="Nombre del producto" class='form-control '>
                                                    </div>


                                                    <div class='col-md-7  col-sm-7'>
                                                        <label class='col-form-label' for='first-name'>Selecciones el producto</label>
                                                        <select type='text' id='producto' required name='producto' class='form-control '>
                                                            <option value="">-- Indique un filtro --</option>
                                                        </select>
                                                    </div>

                                                </div>

                                            </div>
                                            <div class='form-group mb-3'>

                                                <label class='col-form-label' for='filtro'>Cantidad en stock</label>

                                                <input type='number' min="0" id='stock' name="stock" required placeholder="Cantidad de productos en stock" class='form-control '>
                                            </div>

                                    </div>

                                    <div class='ln_solid'></div>
                                    <button type="submit" class="btn btn-success actualizar">Siguiente</button>
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
        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <script src='../build/js/custom.js'></script>

        <script>
            // tasas de cambio




            // Consultar productos
            function obtener_registros(producto) {
                var $sucursal = $('#sucursal');
                var sucursal = $sucursal.length ? $sucursal.val() : '';

                if ($('#sucursal').length && $('#sucursal').val() == '') {
                    $('#codigo').val('')
                    Alerta.toast('error', 'Debe seleccionar una sucursal antes de buscar el producto')
                    return
                }


                $.ajax({
                    url: "../../configurar/consulta_codigo_producto _inexistente.php",
                    type: "POST",
                    dataType: "json", // <-- importante: estamos esperando JSON ahora
                    data: {
                        producto: producto,
                        sucursal: sucursal
                    },
                }).done(function(resultado2) {
                    const $select = $("#producto");
                    $select.empty(); // Limpia opciones anteriores

                    if (resultado2.length > 0) {
                        // Agrega las opciones recibidas
                        $select.append(`<option value="">-- Seleccione --</option>`);
                        resultado2.forEach(function(item) {
                            $select.append(`<option value="${item.id}">${item.nombre}</option>`);
                        });
                    } else {
                        // Indique un filtro
                        $select.append('<option value="">-- Indique un filtro --</option>');
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error("Error en la petición AJAX:", textStatus, errorThrown);
                    $("#producto").html('<option >-- Error al cargar --</option>');
                });
            }

            // Evento al escribir en #codigo
            $(document).on("keyup", "#filtro", function() {
                if ($(this).val().trim() !== "") {
                    obtener_registros($(this).val());
                } else {
                    $("#producto").html('<option value="">-- Indique un filtro --</option>');
                }
            });



            if (document.getElementById('sucursal')) {
                document.getElementById('sucursal').addEventListener('click', function() {
                    document.getElementById('filtro').value = ''
                    document.getElementById('producto').value = ''
                })
            }


            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();

                const formElement = this; // <- El formulario real
                const formData = new FormData(formElement); // <- El objeto FormData

                // Envío por fetch
                fetch('../../configurar/agregarProducto_sucursal.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(text => {
                        let json;
                        try {
                            json = JSON.parse(text);
                        } catch (e) {
                            console.error("Error al parsear JSON:", e);

                            Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                            return;
                        }

                        if (json.tipo === 'success') {
                            this.reset();
                        }
                        Alerta.toast(json.tipo, json.mensaje);
                    })

            });
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>