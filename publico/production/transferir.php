<?php
require_once('includes/requires.php');
if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {

    $topnav = topnav();

    $tipo_u =  $_SESSION['nivel'];
    $query = "SELECT * FROM `sucursales` WHERE bss_id = ?";

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

    // Productos del negocio para el filtro de movimientos
    $productos_filtro = [];
    $stmtProductos = mysqli_prepare($conexion, "SELECT id, nombre FROM productos WHERE bss_id = ? AND activo = 0 ORDER BY nombre");
    if ($stmtProductos) {
        $stmtProductos->bind_param('i', $bss_id);
        $stmtProductos->execute();
        $resultProductos = $stmtProductos->get_result();
        while ($rowProducto = $resultProductos->fetch_assoc()) {
            $productos_filtro[] = $rowProducto;
        }
        $stmtProductos->close();
    }
?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>

        <title>Transferencia de stock</title>
        <?php require_once('includes/headers.php'); ?>

        <style>
            .table td,
            .table th {
                padding: .55rem;

            }

            .panel_toolbox .btn {
                margin-top: 2px;
                padding: 6px 12px;
                font-size: 12px;
                border-radius: 6px;
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
                            <div class="x_panel" id="card-movimientos">
                                <div class="x_title">
                                    <h2>Movimientos de stock</h2>
                                    <ul class="nav navbar-right panel_toolbox">
                                        <li>
                                            <button type="button" id="btn-transferencia" class="btn btn-success btn-sm">
                                                <i class="fa fa-exchange"></i> Nuevo traslado
                                            </button>
                                        </li>
                                    </ul>
                                    <div class="clearfix"></div>
                                </div>
                                <div class="x_content">
                                    <div class="row mb-3">
                                        <div class="col-lg-4">
                                            <label class="form-label" for="mov-filtro-producto">Producto</label>
                                            <select class="form-control" id="mov-filtro-producto">
                                                <option value="">-- Todos --</option>
                                                <?php foreach ($productos_filtro as $productoFiltro): ?>
                                                    <option value="<?= (int) $productoFiltro['id'] ?>">
                                                        <?= htmlspecialchars($productoFiltro['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="mov-filtro-origen">Sucursal origen</label>
                                            <select class="form-control" id="mov-filtro-origen">
                                                <option value="">-- Todas --</option>
                                                <?php foreach ($sucursales as $row): ?>
                                                    <option value="<?= (int) $row['id'] ?>">
                                                        <?= htmlspecialchars($row['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="mov-filtro-destino">Sucursal destino</label>
                                            <select class="form-control" id="mov-filtro-destino">
                                                <option value="">-- Todas --</option>
                                                <?php foreach ($sucursales as $row): ?>
                                                    <option value="<?= (int) $row['id'] ?>">
                                                        <?= htmlspecialchars($row['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="table-responsive" id="movimientos-tabla-wrap">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Tipo</th>
                                                    <th>Cantidad</th>
                                                    <th>Sucursal origen</th>
                                                    <th>Sucursal destino</th>
                                                    <th>Cargamento</th>
                                                    <th>Motivo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="movimientos-body">
                                                <tr><td colspan="7" class="text-center">Cargando movimientos...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="panel-transferencia" style="display: none;">
                        <div class="col-lg-12">
                            <form id='data-form'>
                                <div class="x_panel">
                                    <div class="x_title">
                                        <h2>Datos del Producto <small>* obligatorio</small></h2>
                                        <ul class="nav navbar-right panel_toolbox">
                                            <li>
                                                <button type="button" id="btn-volver-movimientos" class="btn btn-default btn-sm">
                                                    <i class="fa fa-list"></i> Ver movimientos
                                                </button>
                                            </li>
                                        </ul>
                                        <div class="clearfix"></div>
                                    </div>
                                    <div class="x_content">
                                        <div class='x_content'>

                                            <div class="row">
                                                <div class='col-lg-6 form-group mb-3'>
                                                    <label class='form-label' for='sucursal'>Sucursal origen</label>
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

                                                <div class='col-lg-6 form-group mb-3'>
                                                    <label class='form-label' for='sucursal_2'>Sucursal destino</label>
                                                    <select class="form-control" id="sucursal_2" name="sucursal_2">
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


                                            </div>

                                            <div class="mb-3">

                                                <div class=' form-group'>
                                                    <div class='row'>
                                                        <div class="col-lg-4">
                                                            <label for='codigo'>Filtro</label>
                                                            <input type='text' class='form-control' name='codigo' placeholder="Nombre" id='codigo'>
                                                        </div>
                                                        <div class="col-lg-8">
                                                            <label for='producto'>Seleccione el producto</label>
                                                            <select id="producto" name="producto" class="form-control">
                                                                <option>-- Indique un filtro --</option>
                                                            </select>
                                                        </div>

                                                    </div>
                                                </div>

                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-lg-3">
                                                    <label for='disponible'>Disponibilidad</label>
                                                    <input type='text' readonly class='form-control' name='disponible' placeholder="Stock disponible" id='disponible'>
                                                </div>
                                                <div class="col-lg-6">
                                                    <label class="form-label" for="unidades_transferir">Unidades a transferir</label>
                                                    <input type="number" step="any" min="0.0001" id="unidades_transferir" name="unidades_transferir" class="form-control" placeholder="Cantidad de unidades a transferir">
                                                </div>
                                                <div class="col-lg-3 d-flex align-items-end">
                                                    <button type="button" id="btn-agregar-articulo" class="btn btn-default" style="width:100%;">
                                                        <i class="fa fa-plus"></i> Agregar artículo
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Producto</th>
                                                            <th class="text-right">Disponible</th>
                                                            <th class="text-right">Cantidad</th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="cargamento-items-body">
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">Sin artículos agregados.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="form-group mt-3">
                                                <label class="form-label" for="motivo-cargamento">Motivo del cargamento *</label>
                                                <textarea id="motivo-cargamento" name="motivo" class="form-control" rows="2" required placeholder="Indique el motivo del traslado"></textarea>
                                            </div>

                                            <div class="ln_solid"></div>
                                            <div class='form-group mt-3 text-end'>
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fa fa-truck"></i> Guardar cargamento
                                                </button>
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

        <script>
            function verificarSucursales() {
                const sucursalOrigen = document.getElementById('sucursal').value;
                const sucursalDestino = document.getElementById('sucursal_2').value;

                // Si ambos tienen valor y son iguales, no es válido
                if (sucursalOrigen && sucursalDestino && sucursalOrigen === sucursalDestino) {
                    // Puedes mostrar un mensaje si lo deseas
                    Alerta.toast('error', 'La sucursal de origen y destino no pueden ser la misma')
                    document.getElementById('sucursal_2').value = ''
                    return false;
                }

                return true;
            }

            // Detecta cambios en los selects y verifica automáticamente
            document.getElementById('sucursal').addEventListener('change', verificarSucursales);
            document.getElementById('sucursal_2').addEventListener('change', verificarSucursales);


            let precio_consultado
            let producto_consultado
            let producto_estricto = false
            let productoActual = null
            let cargamentoItems = []

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
                    url: "../../configurar/consulta_codigo_producto.php",
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
            $(document).on("keyup", "#codigo", function() {
                if ($(this).val().trim() !== "") {
                    obtener_registros($(this).val());
                } else {
                    $("#producto").html('<option value="">-- Indique un filtro --</option>');
                }
            });


            // Resetear formulario
            function form_reset() {
                const form = document.getElementById('data-form');
                const select = document.getElementById('sucursal'); // Asegúrate de que el select tenga este ID

                const selectedValue = select.value; // Guarda la opción seleccionada
                form.reset(); // Resetea el formulario
                select.value = selectedValue; // Restablece la opción seleccionada
            }

            $(document).on('change', '#sucursal', function() {
                form_reset()
                producto_estricto = false;
                productoActual = null;
                cargamentoItems = [];
                renderCargamentoItems();
            }); // resetear form al cambiar el select


            $(document).on('change', '#producto', function() {
                var producto = $(this).val();
                productoActual = null;
                document.getElementById('disponible').value = '';
                if (producto != "") {
                    buscarProducto(producto);
                } else {
                    producto_estricto = false;
                }
            });


            //Consultar informacion del producto
            function buscarProducto(codigo) {

                var $sucursal = $('#sucursal');
                var sucursal = $sucursal.length ? $sucursal.val() : '';

                if ($('#sucursal').length && $('#sucursal').val() == '') {
                    $('#codigo').val('')
                    Alerta.toast('error', 'Debe seleccionar una sucursal antes de seleccionar el producto')
                    return
                }

                fetch('precio_producto.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            producto: codigo,
                            sucursal: sucursal
                        })
                    })
                    .then(res => res.text())
                    .then(text => {
                        console.log('respuesta crudda ' + text)
                        let res;
                        try {
                            res = JSON.parse(text);
                        } catch (e) {
                            console.error("Error al parsear JSON:", e);
                            Swal.fire({
                                icon: 'error',
                                title: 'Respuesta no válida',
                                text: 'El servidor no devolvió un JSON válido.'
                            });
                            return;
                        }

                        if (res.success) {
                            precio_consultado = res.data.precio_compra;
                            producto_consultado = res.data.nombre
                            producto_estricto = res.data.seguimiento_estricto == 1;
                            productoActual = {
                                id: parseInt(codigo, 10),
                                nombre: res.data.nombre,
                                stock: Number(res.data.stock) || 0,
                                unidad: res.data.unidad || 'UND',
                                estricto: producto_estricto
                            };

                            document.getElementById('disponible').value = res.data.stock;
                        } else {
                            producto_estricto = false;
                            productoActual = null;
                            document.getElementById('disponible').value = '';
                            Swal.fire({
                                icon: 'warning',
                                title: 'Producto no encontrado',
                                text: 'Verifica el código ingresado'
                            });
                        }

                    })
                    .catch(err => {
                        console.error(err);
                        productoActual = null;
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo obtener los datos del producto'
                        });
                    });
            }

            function escHtml(valor) {
                return String(valor == null ? '' : valor).replace(/[&<>"']/g, function(c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [c];
                });
            }

            function formatoCantidad(valor) {
                return Number(valor || 0).toLocaleString('es-VE', {
                    maximumFractionDigits: 4
                });
            }

            function renderCargamentoItems() {
                const tbody = document.getElementById('cargamento-items-body');
                if (!cargamentoItems.length) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Sin artículos agregados.</td></tr>';
                    return;
                }

                tbody.innerHTML = cargamentoItems.map(function(item, index) {
                    return `<tr>
                        <td>${escHtml(item.nombre)}</td>
                        <td class="text-right">${formatoCantidad(item.stock)} ${escHtml(item.unidad)}</td>
                        <td class="text-right">${formatoCantidad(item.cantidad)} ${escHtml(item.unidad)}</td>
                        <td class="text-right">
                            <button type="button" class="btn btn-danger btn-xs btn-quitar-item" data-index="${index}" title="Quitar artículo">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                }).join('');
            }

            function agregarArticuloCargamento() {
                if (!productoActual) {
                    Alerta.toast('error', 'Seleccione un producto');
                    return;
                }

                const cantidad = parseFloat(document.getElementById('unidades_transferir').value);
                if (!(cantidad > 0)) {
                    Alerta.toast('error', 'Indique una cantidad mayor que cero');
                    return;
                }

                const existente = cargamentoItems.find(function(item) {
                    return item.producto === productoActual.id;
                });
                const cantidadTotal = (existente ? existente.cantidad : 0) + cantidad;

                if (!productoActual.estricto && cantidadTotal > productoActual.stock + 0.0001) {
                    Alerta.toast('error', 'No hay suficientes productos en stock. Disponible: ' + formatoCantidad(productoActual.stock));
                    return;
                }

                if (existente) {
                    existente.cantidad = cantidadTotal;
                } else {
                    cargamentoItems.push({
                        producto: productoActual.id,
                        nombre: productoActual.nombre,
                        stock: productoActual.stock,
                        unidad: productoActual.unidad,
                        estricto: productoActual.estricto,
                        cantidad: cantidad
                    });
                }

                renderCargamentoItems();

                document.getElementById('unidades_transferir').value = '';
                document.getElementById('producto').value = '';
                document.getElementById('disponible').value = '';
                productoActual = null;
                producto_estricto = false;
            }

            document.getElementById('btn-agregar-articulo').addEventListener('click', agregarArticuloCargamento);

            document.getElementById('cargamento-items-body').addEventListener('click', function(evento) {
                const boton = evento.target.closest('.btn-quitar-item');
                if (!boton) return;
                const indice = parseInt(boton.dataset.index, 10);
                if (isNaN(indice)) return;
                cargamentoItems.splice(indice, 1);
                renderCargamentoItems();
            });

            // Movimientos de stock con filtros por producto y sucursales
            function cargarMovimientos() {
                const tbody = document.getElementById('movimientos-body');
                const params = new URLSearchParams({
                    producto: document.getElementById('mov-filtro-producto').value,
                    origen: document.getElementById('mov-filtro-origen').value,
                    destino: document.getElementById('mov-filtro-destino').value
                });
                fetch('../../configurar/movimientos_stock.php?' + params.toString())
                    .then(res => res.json())
                    .then(json => {
                        if (!json.ok) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se pudieron cargar los movimientos</td></tr>';
                            return;
                        }
                        if (!json.movimientos.length) {
                            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No hay movimientos con los filtros seleccionados</td></tr>';
                            return;
                        }
                        tbody.innerHTML = json.movimientos.map(m => `<tr>
                            <td>${m.fecha}</td>
                            <td>${m.tipo}</td>
                            <td>${m.cantidad}</td>
                            <td>${m.origen || '—'}</td>
                            <td>${m.destino || '—'}</td>
                            <td>${m.cargamento_id ? '#' + m.cargamento_id : '—'}</td>
                            <td>${escHtml(m.motivo || '—')}</td>
                        </tr>`).join('');
                    })
                    .catch(err => {
                        console.error("Error al cargar movimientos:", err);
                        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No se pudieron cargar los movimientos</td></tr>';
                    });
            }

            ['mov-filtro-producto', 'mov-filtro-origen', 'mov-filtro-destino'].forEach(function(id) {
                document.getElementById(id).addEventListener('change', cargarMovimientos);
            });
            cargarMovimientos();

            // Mostrar el formulario y ocultar la card completa de movimientos
            const cardMovimientos = document.getElementById('card-movimientos');
            const panelTransferencia = document.getElementById('panel-transferencia');
            const btnTransferencia = document.getElementById('btn-transferencia');
            const btnVolverMovimientos = document.getElementById('btn-volver-movimientos');

            function mostrarTransferencia(mostrar) {
                cardMovimientos.style.display = mostrar ? 'none' : '';
                panelTransferencia.style.display = mostrar ? '' : 'none';

                if (mostrar) {
                    panelTransferencia.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            btnTransferencia.addEventListener('click', function() {
                mostrarTransferencia(true);
            });

            btnVolverMovimientos.addEventListener('click', function() {
                mostrarTransferencia(false);
            });

            // Confirmación de stock para productos con seguimiento estricto
            // (mismo flujo que las nuevas compras)
            function textoContexto(ctx) {
                const n = (v) => Number(v || 0).toLocaleString('es-VE', {maximumFractionDigits: 3});
                return `<div style="text-align:left;font-size:13px">
                    <p><b>${ctx.nombre || ''}</b></p>
                    <p>Stock actual en sistema (sucursal destino): <b>${n(ctx.stock_actual)} ${ctx.unidad || ''}</b></p>
                    <p>Última compra: ${ctx.ultima_compra_at ? ctx.ultima_compra_at + ' (' + n(ctx.ultima_compra_kg) + ')' : 'sin registro'}</p>
                    <p>Vendido desde la última compra: <b>${n(ctx.vendido_desde)}</b></p>
                    <p style="color:#888">Confirme el monto actual si el stock es correcto, o vaya al módulo de ajuste de stock para registrar la diferencia.</p>
                </div>`;
            }

            async function mostrarConciliacion(ctx) {
                if (!ctx) {
                    Alerta.toast('error', 'No se pudo obtener la cantidad anterior.');
                    return false;
                }
                const r = await Swal.fire({
                    title: 'Confirmar stock actual',
                    html: textoContexto(ctx),
                    showDenyButton: true,
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar monto actual',
                    denyButtonText: 'Ir a ajuste de stock',
                    cancelButtonText: 'Cancelar'
                });
                if (r.isConfirmed) return true;
                if (r.isDenied) window.open('ajustes_stock.php', '_blank');
                return false;
            }

            async function enviarCargamento(payload) {
                const res = await fetch('../../configurar/modificar_stock_transferir.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const text = await res.text();
                let json;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    console.error("Error al parsear JSON:", e);
                    Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                    return null;
                }

                if (json.requiere_conciliacion) {
                    const productos = json.productos || (json.contexto ? [json.contexto] : []);
                    if (!productos.length) {
                        Alerta.toast('error', 'No se pudo obtener la cantidad anterior.');
                        return null;
                    }

                    const conciliaciones = Object.assign({}, payload.conciliaciones || {});
                    for (const contexto of productos) {
                        const confirmado = await mostrarConciliacion(contexto);
                        if (!confirmado) return null;
                        conciliaciones[contexto.id] = {
                            confirmado: 1,
                            stock_visto: contexto.stock_actual
                        };
                    }

                    payload.conciliaciones = conciliaciones;
                    return enviarCargamento(payload);
                }

                return json;
            }

            // ENVIAR EL CARGAMENTO AL BACKEND
            document.getElementById('data-form').addEventListener('submit', async function(e) {
                e.preventDefault();

                const form = e.target;
                const sucursal = document.getElementById('sucursal').value;
                const sucursalDestino = document.getElementById('sucursal_2').value;
                const motivo = document.getElementById('motivo-cargamento').value.trim();

                if (!sucursal || !sucursalDestino) {
                    Alerta.toast('error', 'Seleccione la sucursal de origen y destino');
                    return;
                }
                if (!verificarSucursales()) {
                    return;
                }
                if (!motivo) {
                    Alerta.toast('error', 'Indique el motivo del cargamento');
                    document.getElementById('motivo-cargamento').focus();
                    return;
                }
                if (!cargamentoItems.length) {
                    Alerta.toast('error', 'Agregue al menos un artículo al cargamento');
                    return;
                }

                for (const item of cargamentoItems) {
                    if (!item.estricto && item.cantidad > item.stock + 0.0001) {
                        Alerta.toast('error', 'No hay suficientes productos en stock para "' + item.nombre + '". Disponible: ' + formatoCantidad(item.stock));
                        return;
                    }
                }

                const payload = {
                    accion: 'guardar_cargamento',
                    sucursal: sucursal,
                    sucursal_2: sucursalDestino,
                    motivo: motivo,
                    items: cargamentoItems.map(function(item) {
                        return {
                            producto: item.producto,
                            cantidad: item.cantidad
                        };
                    }),
                    conciliaciones: {}
                };

                const boton = form.querySelector('button[type="submit"]');
                boton.disabled = true;

                try {
                    const json = await enviarCargamento(payload);
                    if (!json) return;

                    if (json.success) {
                        Alerta.mostrar('success', json.message);
                        form.reset();
                        cargamentoItems = [];
                        renderCargamentoItems();
                        productoActual = null;
                        producto_estricto = false;
                        document.getElementById('disponible').value = '';
                        cargarMovimientos();
                        mostrarTransferencia(false);
                    } else {
                        Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                    }
                } finally {
                    boton.disabled = false;
                }
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