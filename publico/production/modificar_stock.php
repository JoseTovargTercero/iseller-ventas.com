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

    // Todos los productos con stock del negocio, con su sucursal. El filtro se
    // hace en el navegador con Fuse: no hay que esperar a un AJAX por cada tecla.
    $stockProductos = [];
    $stmtStock = $conexion->prepare("
        SELECT S.id_producto, S.id_sucursal, S.stock, P.nombre
        FROM stock AS S
        INNER JOIN productos AS P ON P.id = S.id_producto
        WHERE S.bss_id = ? AND P.activo = 0 AND P.mayor IS NULL
        ORDER BY P.nombre ASC
    ");
    if ($stmtStock) {
        $stmtStock->bind_param('i', $bss_id);
        $stmtStock->execute();
        $resStock = $stmtStock->get_result();
        if ($resStock) {
            while ($rowStock = $resStock->fetch_assoc()) {
                $claveStock = (int)$rowStock['id_sucursal'] . '-' . (int)$rowStock['id_producto'];
                $stockProductos[$claveStock] = [
                    'id' => (int)$rowStock['id_producto'],
                    'nombre' => $rowStock['nombre'],
                    'sucursal' => (int)$rowStock['id_sucursal'],
                    'stock' => (float)$rowStock['stock'],
                ];
            }
        }
        $stmtStock->close();
    }
    $stockProductos = array_values($stockProductos);
?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Compras</title>
        <?php require_once('includes/headers.php'); ?>

        <style>
            .table td,
            .table th {
                padding: .55rem;

            }
        </style>
    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>
                <?php echo $menu ?>
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->
                <!-- page content -->
                <div class='right_col' role='main'>


                    <div class="row">
                        <div class="col-lg-12">
                            <form id='data-form'>
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Datos del Producto <small>* obligatorio</small></h2>
                                    </div>
                                    <div class="x_content">
                                        <div class='x_content'>
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


                                            <div class='mb-3 form-group'>
                                                <div class='row'>
                                                    <div class="col-lg-4">
                                                        <label for='codigo' for='codigo'>Filtro</label>
                                                        <input type='text' required="required" class='form-control' name='codigo' placeholder="Nombre" id='codigo'>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        <label for='producto'>Seleccione el producto</label>
                                                        <select id="producto" name="producto" class="form-control" required>
                                                            <option value="">-- Indique un filtro --</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3 form-group">
                                                <label class="form-label" for="comprado">Unidades en stock</label>
                                                <input type="text" id="cantidad_stock" name="cantidad_stock" required="required" class="form-control" placeholder="Cantidad de unidades compradas">
                                            </div>
                                            <div class="ln_solid"></div>
                                            <div class='form-group mt-3 text-end'>
                                                <input type='submit' style="float: right;" class="btn btn-success actualizar" value="Actualizar">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php require('../assets/templates/modal.html'); ?>
        <!-- Bootstrap -->
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../build/js/modal.js"></script>
        <script src="js/nombre_pagina.js"></script>
        <script src="../vendors/fuse/fuse.min.js"></script>

        <script>
            const STOCK_PRODUCTOS = <?= json_encode($stockProductos, JSON_UNESCAPED_UNICODE) ?>;
            const SUCURSAL_FIJA = <?= json_encode($tipo_u == 2 ? (int)$_SESSION['sucursal'] : null) ?>;

            let productosSucursal = [];
            let fuseProductos = null;

            function sucursalSeleccionada() {
                const select = document.getElementById('sucursal');
                const valor = select ? select.value : '';
                return valor !== '' ? String(valor) : (SUCURSAL_FIJA !== null ? String(SUCURSAL_FIJA) : '');
            }

            // Índice Fuse con los productos que existen en la sucursal elegida.
            function cargarIndiceProductos() {
                const sucursal = sucursalSeleccionada();
                const porId = {};

                if (sucursal !== '') {
                    STOCK_PRODUCTOS.forEach(function(p) {
                        if (String(p.sucursal) !== sucursal) return;
                        porId[p.id] = {
                            id: p.id,
                            nombre: p.nombre,
                            stock: p.stock
                        };
                    });
                }

                productosSucursal = Object.values(porId);
                fuseProductos = new Fuse(productosSucursal, {
                    keys: ['nombre'],
                    threshold: 0.3,
                    ignoreLocation: true,
                    ignoreDiacritics: true,
                    // Extended search trata las palabras separadas como AND: así
                    // "hígado de pollo" no sugiere "pechuga de pollo", pero sigue
                    // tolerando errores de escritura en cada palabra.
                    useExtendedSearch: true
                });
            }

            function ponerOpcionUnica(texto) {
                const select = document.getElementById('producto');
                select.innerHTML = '';
                select.add(new Option(texto, ''));
                document.getElementById('cantidad_stock').value = '';
            }

            function filtrarProductos(texto) {
                const sucursal = sucursalSeleccionada();

                if (sucursal === '') {
                    ponerOpcionUnica('-- Seleccione una sucursal --');
                    return;
                }
                if (!texto || !fuseProductos) {
                    ponerOpcionUnica('-- Indique un filtro --');
                    return;
                }

                const resultados = fuseProductos.search(texto).slice(0, 100);
                if (resultados.length === 0) {
                    ponerOpcionUnica('-- Sin coincidencias --');
                    return;
                }

                const select = document.getElementById('producto');
                select.innerHTML = '';
                select.add(new Option('-- Seleccione --', ''));
                resultados.forEach(function(r) {
                    select.add(new Option(r.item.nombre, r.item.id));
                });
                document.getElementById('cantidad_stock').value = '';
            }

            // Resetear formulario conservando la sucursal elegida.
            function form_reset() {
                const form = document.getElementById('data-form');
                const select = document.getElementById('sucursal');

                const selectedValue = select ? select.value : '';
                form.reset();
                if (select) select.value = selectedValue;
                cargarIndiceProductos();
            }

            $(document).on('input', '#codigo', function() {
                filtrarProductos($(this).val().trim());
            });

            $(document).on('change', '#sucursal', function() {
                form_reset();
            });

            $(document).on('change', '#producto', function() {
                const id = $(this).val();
                if (id === '') {
                    document.getElementById('cantidad_stock').value = '';
                    return;
                }
                const prod = productosSucursal.find(function(p) {
                    return String(p.id) === String(id);
                });
                document.getElementById('cantidad_stock').value = prod ? prod.stock : '';
            });

            $(document).ready(function() {
                cargarIndiceProductos();
            });


            // ENVIAR LA NUEVA COMPRA AL BACK
            document.getElementById('data-form').addEventListener('submit', function(e) {
                e.preventDefault(); // Evitar envío tradicional

                const form = e.target;
                const formData = new FormData(form);

                console.log("Enviando datos del formulario:");
                for (let [key, value] of formData.entries()) {
                    if (value == '') {
                        console.log('Campo vacío: ' + key)
                        Alerta.toast('error', 'Error: Campo (a) vacío (s)')
                        return
                    }
                }

                fetch('../../configurar/modificar_stock.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.text())
                    .then(text => {
                        console.log("Respuesta cruda del servidor:", text);
                        let json;
                        try {
                            json = JSON.parse(text);
                        } catch (e) {
                            console.error("Error al parsear JSON:", e);

                            Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                            return;
                        }

                        if (json.success) {
                            Alerta.mostrar('success', json.message);

                            // Sin reset: la sucursal (y el filtro) se quedan como estaban.
                            // Se actualiza el stock en memoria para la próxima búsqueda.
                            const sucursal = sucursalSeleccionada();
                            const productoId = form.producto.value;
                            const nuevaCantidad = parseFloat(form.cantidad_stock.value);
                            const item = STOCK_PRODUCTOS.find(function(p) {
                                return String(p.sucursal) === sucursal && String(p.id) === String(productoId);
                            });
                            if (item && !isNaN(nuevaCantidad)) item.stock = nuevaCantidad;
                            cargarIndiceProductos();
                        } else {
                            Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                        }
                    })
                    .catch(err => {
                        console.error("Error en la solicitud:", err);
                        Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                    });
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