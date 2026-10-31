<?php

require_once('includes/requires.php');


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

    $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ? ORDER BY principal DESC");
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
    <html lang="es">

    <head>
        <title>Productos</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>

    </head>

    <body class="nav-md">
        <div class="container body">
            <div class="main_container">

                <?php echo $menu ?>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->


                <!-- page content -->
                <div class="right_col">

                    <div class="clearfix"></div>
                    <div class="row">
                        <script>
                            // Ejecutar actualización cada segundo
                            $(document).ready(function() {
                                ['precioMonedaOrigen', 'cantidad', 'porcentaje'].forEach(element => {
                                    const el = document.getElementById(element);
                                    // Solo dígitos y un punto decimal (la coma se normaliza a punto)
                                    el.addEventListener('input', () => {
                                        const partes = el.value.replace(/,/g, '.').replace(/[^0-9.]/g, '').split('.');
                                        el.value = partes.length > 2 ? partes.shift() + '.' + partes.join('') : partes.join('.');
                                    });
                                    el.addEventListener('keyup', () => realizarCalculos())
                                });

                                document.getElementById('origenProducto').addEventListener('change', () => realizarCalculos())
                            })

                            // Variables globales
                            let cambioDolar = parseFloat(<?php echo $bsDolar ?>);
                            const pesoDolar = parseFloat(<?php echo $pesoDolar ?>);
                            const pesoBolivar = parseFloat(<?php echo $peso_bolivar ?>);



                            // Realiza la conversión principal según la moneda seleccionada
                            function realizarCalculos() {
                                const precioInput = parseFloat(document.getElementById("precioMonedaOrigen").value) || 0;

                                let resultado = precioInput;

                                // Mostrar resultado convertido en input 'precio'
                                document.getElementById("precio").value = resultado;

                                // Calcular precios adicionales
                                calcularValoresExtras(precioInput);
                            }

                            // Cálculos adicionales como precio unitario, venta y conversiones
                            function calcularValoresExtras(precioCompra) {
                                const cantidad = parseInt(document.getElementById("cantidad").value) || 1;
                                const porcentaje = parseFloat(document.getElementById("porcentaje").value) || 0;
                                const origenProducto = document.getElementById("origenProducto").value;

                                try {
                                    // Calcular precio unitario
                                    const precioUnitario = (precioCompra / cantidad).toFixed(2);
                                    const precioDolarCompra = parseFloat(precioUnitario);

                                    // Precio de venta con porcentaje
                                    const precioDolarVenta = ((precioDolarCompra * porcentaje / 100) + precioDolarCompra).toFixed(2);

                                    // Convertir a pesos
                                    const pesoSalida = Math.round(precioDolarVenta * pesoDolar);

                                    // Convertir a bolívares dependiendo del tipo de origen
                                    let bolivarSalida;
                                    if (origenProducto === 'c') {
                                        bolivarSalida = ((pesoSalida / pesoBolivar) / 1000).toFixed(2);
                                    } else {
                                        bolivarSalida = (precioDolarVenta * cambioDolar).toFixed(2);
                                    }
                                    // Actualizar campos con resultados
                                    document.getElementById("resultado").value = `$ ${precioDolarCompra}`;
                                    document.getElementById("resultado2").value = `$ ${precioDolarVenta}`;
                                    document.getElementById("resultado3").value = `${formatNumber(pesoSalida)} COP`;
                                    document.getElementById("resultado4").value = `${formatNumber(bolivarSalida)} BS`;
                                } catch (error) {
                                    console.error("Error en los cálculos:", error);
                                }
                            }

                            // Formatear número con separadores de miles
                            function formatNumber(valor) {
                                return valor.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        </script>



                        <section id="section_edit" class="hide w-100">
                            <form id='form-data' class='form-horizontal form-label-left'>
                                <div class='row'>
                                    <div class='col-lg-6 '>
                                        <div class='x_panel'>
                                            <div class='x_title'>
                                                <h2>Datos del Producto <small>* obligatorio</small></h2>
                                                <div class='clearfix'></div>
                                            </div>
                                            <div class='x_content'>

                                                <input class="form-control" type="text" hidden name="codigoEditar" value="<?php echo $codeEditar ?>">


                                                <section id="sucursal_section" class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Sucursal </label>
                                                    <select class="form-control" id="sucursal_a_editar" name="sucursal_a_editar">
                                                        <?php if (count($sucursales) > 1): ?>
                                                            <option value="">Todas las sucursales</option>
                                                        <?php endif; ?>

                                                        <?php foreach ($sucursales as $row): ?>
                                                            <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($row['nombre']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </section>

                                                <div class='form-group mb-3'>
                                                    <label class='form-label' for='first-name'>Nombre del producto</label>
                                                    <div class="row">
                                                        <div class='col-md-7  col-sm-7'>
                                                            <input type='text' id='nombre' name='nombre' required='required' class='form-control ' placeholder='Nombre del Producto'>
                                                        </div>
                                                        <div class='col-md-5 col-sm-5'>
                                                            <button type="button" style="text-wrap: nowrap;" class="btn w-100 btn-info" id="open-modal">Calcular precio</button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class='col-lg-7 form-group'>
                                                        <label class='form-label ' for='first-name'>Precio de Compra </label>
                                                        <input type='text' id='precioMonedaOrigen' name='precioMonedaOrigen' required='required' class='form-control ' placeholder='Precio'>
                                                    </div>


                                                    <div class='col-lg-5 form-group'>
                                                        <label class='form-label ' for='first-name'>Origen </label>
                                                        <select class="form-control" required='required' name="origenProducto" id="origenProducto">
                                                            <option value="">Seleccione</option>
                                                            <option value="v">Venezolano</option>
                                                            <option value="c">Colombiano</option>
                                                        </select>

                                                    </div>

                                                </div>

                                                <input type='text' id='precio' name='precio' hidden required='required' class='form-control ' placeholder='Precio en dolares'>

                                                <div class="row mb-3">
                                                    <div class='col-lg-7 form-group'>
                                                        <label class='form-label ' for='first-name'>Unidades por bulto </label>
                                                        <input type='text' id='cantidad' name='cantidad' required='required' class='form-control ' placeholder='Unidades'>
                                                    </div>
                                                    <div class='col-lg-5 form-group'>

                                                        <label class='form-label ' for='first-name'>Porcentaje </label>
                                                        <input type='text' id='porcentaje' name='porcentaje' required='required' class='form-control ' placeholder='XXX'>
                                                        <?php

                                                        if (@$origen == "nuevo") {
                                                            echo " <input type='text' hidden name='origen' value='nuevo'>";
                                                        } else {
                                                        }
                                                        ?>
                                                    </div>
                                                </div>



                                                <div class="row">
                                                    <div class="col-lg-7">
                                                        <div class='form-group'>
                                                            <label class='form-label ' for='first-name'>Proveedor </label>
                                                            <input type='text' id='proveedor' name='proveedor' required='required' class='form-control ' placeholder='Proveedor'>
                                                        </div>


                                                    </div>
                                                    <div class="col-lg-5">
                                                        <div class='form-group'>
                                                            <label class='form-label ' for='first-name'>Código de barras </label>
                                                            <input type='text' id='codigo_barra' name='codigo_barra' required='required' class='form-control ' placeholder='Código de barras'>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class='form-group mb-3'>
                                                    <label class='form-label'>Categoría del producto</label>
                                                    <select name="categoria" id="categoria_select" class="form-select" style="width: 100%;">
                                                        <option value="">Seleccione una categoría</option>
                                                        <?php
                                                        $stmtCat = $conexion->prepare("SELECT id, nombre, slug, grupo FROM categorias_producto WHERE bss_id = 0 OR bss_id = ? ORDER BY grupo ASC, nombre ASC");
                                                        if ($stmtCat) {
                                                            $stmtCat->bind_param("i", $bss_id);
                                                            $stmtCat->execute();
                                                            $catResult = $stmtCat->get_result();
                                                            $grupoActual = '';
                                                            if ($catResult && $catResult->num_rows > 0) {
                                                                while ($cat = $catResult->fetch_assoc()) {
                                                                    $grupo = $cat['grupo'] ?? 'Otros';
                                                                    if ($grupo !== $grupoActual) {
                                                                        if ($grupoActual !== '') echo '</optgroup>';
                                                                        echo '<optgroup label="' . htmlspecialchars($grupo) . '">';
                                                                        $grupoActual = $grupo;
                                                                    }
                                                                    echo '<option value="' . (int)$cat['id'] . '">' . htmlspecialchars($cat['nombre']) . '</option>';
                                                                }
                                                                if ($grupoActual !== '') echo '</optgroup>';
                                                            }
                                                            $stmtCat->close();
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class='ln_solid'></div>

                                                <div class='d-flex justify-content-between'>
                                                    <button type='button' id="btn-cancelar" class="btn btn-danger">Cancelar</button>
                                                    <button type='submit' class="btn btn-success actualizar">Actualizar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class='col-lg-6 '>
                                        <div class='x_panel'>
                                            <div class='x_title'>
                                                <h2>Precios de venta (Unidad)</h2>

                                                <div class='clearfix'></div>
                                            </div>
                                            <div class='x_content'>
                                                <div class="price-card muted">
                                                    <div class="pc-icon"><ion-icon name="pricetag-outline"></ion-icon></div>
                                                    <div class="pc-info">
                                                        <span class="pc-label">Precio de Compra</span>
                                                        <span class="pc-sub">Costo unitario &middot; USD</span>
                                                    </div>
                                                    <input type='text' class='pc-value' readonly='readonly' name='resultado' id='resultado'>
                                                </div>

                                                <div class="price-card highlight">
                                                    <div class="pc-icon"><ion-icon name="cash-outline"></ion-icon></div>
                                                    <div class="pc-info">
                                                        <span class="pc-label">Precio de Venta</span>
                                                        <span class="pc-sub">D&oacute;lares &middot; USD</span>
                                                    </div>
                                                    <input type='text' class='pc-value' readonly='readonly' name='resultado2' id='resultado2'>
                                                </div>

                                                <div class="price-card">
                                                    <div class="pc-icon"><ion-icon name="logo-usd"></ion-icon></div>
                                                    <div class="pc-info">
                                                        <span class="pc-label">Precio de Venta</span>
                                                        <span class="pc-sub">Pesos colombianos &middot; COP</span>
                                                    </div>
                                                    <input type='text' class='pc-value' readonly='readonly' name='resultado3' id='resultado3'>
                                                </div>

                                                <div class="price-card">
                                                    <div class="pc-icon"><ion-icon name="cash-outline"></ion-icon></div>
                                                    <div class="pc-info">
                                                        <span class="pc-label">Precio de Venta</span>
                                                        <span class="pc-sub">Bol&iacute;vares &middot; BS</span>
                                                    </div>
                                                    <input type='text' class='pc-value' readonly='readonly' name='resultado4' id='resultado4'>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </section>
                    </div>


                    <div class="col-lg-12" id="section_lista">
                        <div class="x_panel   fadeInUp animated">
                            <div class="w-100 x_title productos-toolbar">
                                <h2>Productos</h2>
                                <div class="productos-toolbar-actions">
                                    <label class="productos-stock" title="Mostrar solo productos con stock positivo">
                                        <span class="switch">
                                            <input type="checkbox" id="switch-stock">
                                            <span class="slider"></span>
                                        </span>
                                        <span>Solo con stock</span>
                                    </label>
                                    <select class="form-control" style="max-width: 200px;" id="sucursal-selector" name="sucursal-selector">
                                        <?php if (count($sucursales) > 1): ?>
                                            <option value="">Todas las sucursales</option>
                                        <?php endif; ?>

                                        <?php foreach ($sucursales as $row): ?>
                                            <option value="<?= $row['id'] ?>" <?= count($sucursales) === 1 ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($row['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="form-control" style="max-width: 200px;" id="grupo-selector" name="grupo-selector" title="Filtrar por grupo" aria-label="Filtrar por grupo">
                                        <option value="">Todos los grupos</option>
                                    </select>
                                    <select class="form-control" style="max-width: 200px;" id="categoria-selector" name="categoria-selector" title="Filtrar por categoría" aria-label="Filtrar por categoría">
                                        <option value="">Todas las categorías</option>
                                    </select>
                                    <div class="productos-search">
                                        <ion-icon name="search-outline"></ion-icon>
                                        <input type="search" id="productos-buscar" placeholder="Buscar producto…" autocomplete="off">
                                    </div>
                                </div>

                            </div>
                            <div class="clearfix"></div>
                            <div class="x_content">
                                <div class="row">

                                    <div class="col-lg-12">
                                        <div class="card-box table-responsive">

                                            <table id="datatable-responsive" class="table " style="width:100%">
                                                <thead>
                                                    <tr class="headings">
                                                        <th></th>
                                                        <th>Nombre </th>
                                                        <th>Compra</small></th>
                                                        <th>Cant.</th>
                                                        <th>%</th>
                                                        <th>Stock</th>
                                                        <th>USD</th>
                                                        <th>COP</th>
                                                        <th>BS</th>
                                                        <th></th>
                                                        <th></th>
                                                        <th></th>
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
                </div>
            </div>


            <?php require('../assets/templates/modal.html'); ?>

            <!-- jQuery -->
            <script src="../vendors/jquery/dist/jquery.min.js"></script>
            <!-- Select2 -->
            <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
            <!-- Bootstrap -->
            <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
            <!-- FastClick -->
            <!-- DataTables core -->
            <link rel="stylesheet" href="../vendors/datatables/jquery.dataTables.min.css">
            <!-- Buttons extension -->
            <link rel="stylesheet" href="../vendors/datatables/buttons.dataTables.min.css">
            <script src="../vendors/datatables/jquery.dataTables.min.js"></script>
            <script src="../vendors/datatables/dataTables.buttons.min.js"></script>
            <!-- PDF export -->
            <script src="../vendors/pdfmake/build/pdfmake.min.js"></script>
            <script src="../vendors/pdfmake/build/vfs_fonts.js"></script>
            <script src="../vendors/datatables/buttons.html5.min.js"></script>
            <script src="../build/js/custom.js"></script>
            <script src="../build/js/modal.js"></script>
            <script src="js/nombre_pagina.js"></script>


            <script>
                // ── Modal Calcular Precio (mismo comportamiento y estilo que nuevoProducto.php) ──
                const tasas = {
                    'cop': "<?php echo $pesoDolar ?>",
                    'bs': "<?php echo $bcv ?>"
                };

                $(document).ready(function() {
                    const modal_body = document.getElementById('modal-body')
                    modal_body.style.cssText = 'overflow: visible !important; padding: 20px 22px 14px 22px !important; height: auto !important; max-height: none !important;'
                    modal_body.innerHTML = `
                        <div style="margin-bottom:12px;display:flex;align-items:flex-start;justify-content:space-between;">
                            <div>
                                <h5 style="margin:0 0 2px;font-weight:600;display:flex;align-items:center;gap:8px;font-size:15px;color:#e8edf2;">
                                    Calcular precio de compra
                                </h5>
                                <p style="margin:0;font-size:12px;color:#8a9ab5;">
                                    Convierte el precio del proveedor a d&oacute;lares
                                </p>
                            </div>
                            <button type="button" onclick="modalContainer.classList.remove('active');" style="background:none;border:none;color:#8a9ab5;font-size:22px;line-height:1;cursor:pointer;padding:0 4px;" title="Cerrar">&times;</button>
                        </div>
                        <hr style="border-color:#2e353e;margin:0 0 14px 0;">

                        <div style="margin-bottom:10px;">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#e8edf2;" for="moneda">Moneda del proveedor</label>
                            <select id="moneda" class="form-control" style="font-size:13px;height:38px;">
                                <option value="">— Seleccione la moneda —</option>
                                <option value="bs">Bolívares (Bs.)</option>
                                <option value="cop">Pesos colombianos (COP)</option>
                            </select>
                        </div>

                        <div style="margin-bottom:10px;">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#e8edf2;" for="tasa_proveedor">Tasa de cambio</label>
                            <input type="number" step="any" id="tasa_proveedor" class="form-control" style="font-size:13px;height:38px;" placeholder="Ej: 36.50">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#e8edf2;" for="precio_proveedor">Precio del proveedor</label>
                            <input type="number" step="any" id="precio_proveedor" class="form-control" style="font-size:13px;height:38px;" placeholder="Monto en la moneda seleccionada">
                        </div>

                        <div style="margin-bottom:10px;">
                            <label class="form-label fw-semibold mb-1" style="font-size:12px;color:#e8edf2;" for="impuesto">IVA / Impuesto</label>
                            <select id="impuesto" class="form-control" style="font-size:13px;height:38px;">
                                <option value="">— Sin impuesto —</option>
                                <option value="exento">Exento</option>
                                <option value="iva">Incluir IVA (16%)</option>
                            </select>
                        </div>

                        <div id="vista_previa_precio" style="display:none;margin-top:10px;padding:10px 14px;border-radius:8px;background:rgba(45,212,160,0.08);border:1px solid rgba(45,212,160,0.25);">
                            <div style="font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#2dd4a0;font-weight:600;">Precio de compra calculado</div>
                            <div id="resultado_calculo" style="font-size:20px;font-weight:700;color:#e8edf2;margin-top:2px;">— USD</div>
                        </div>`

                    const modalContent = document.getElementById('modal')
                    if (modalContent) {
                        modalContent.style.cssText = 'height: auto !important; max-height: none !important; overflow: visible !important;'
                    }

                    const modal_footer = document.getElementById('modal-footer')
                    modal_footer.classList.remove('hide')
                    modal_footer.style.cssText = 'display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:12px 22px 18px 22px;border-top:1px solid #2e353e;height:auto !important;'
                    modal_footer.innerHTML = `
                        <button class="btn btn-sm btn-secondary" onclick='modalContainer.classList.remove("active");'>Cancelar</button>
                        <button class="btn btn-sm btn-success" id="btn-actualizar" style="background:#2dd4a0;border-color:#2dd4a0;color:#0b1310;font-weight:600;">
                            <i class="bx bx-check"></i> Usar este precio
                        </button>`

                    const modal = document.getElementById('w-modal')
                    modal.classList.remove('w-50', 'h-60')
                    modal.classList.add('modal-calc-precio')
                    modal.style.cssText = 'width: 95% !important; max-width: 440px !important; height: auto !important; max-height: none !important; overflow: visible !important; padding: 0 !important; border-radius: 14px !important;'

                    var n_precio = 0;

                    // Cambiar la tasa automáticamente al elegir moneda
                    document.getElementById('moneda').addEventListener('change', function() {
                        document.getElementById('tasa_proveedor').value = tasas[this.value] || '';
                        calcularPrecio();
                    })
                    document.getElementById('precio_proveedor').addEventListener('input', function() {
                        calcularPrecio();
                    })
                    document.getElementById('tasa_proveedor').addEventListener('input', function() {
                        calcularPrecio();
                    })
                    document.getElementById('impuesto').addEventListener('change', function() {
                        calcularPrecio();
                    })

                    function calcularPrecio() {
                        const previewCard = document.getElementById('vista_previa_precio');
                        const resultadoEl = document.getElementById('resultado_calculo');
                        const tasaValor = parseFloat(document.getElementById('tasa_proveedor').value);
                        const precioValor = parseFloat(document.getElementById('precio_proveedor').value);
                        const impuesto = document.getElementById('impuesto').value;

                        if (isNaN(tasaValor) || isNaN(precioValor) || tasaValor <= 0 || precioValor <= 0) {
                            previewCard.style.display = 'none';
                            n_precio = 0;
                            return;
                        }

                        let precioFinal = precioValor / tasaValor;
                        if (impuesto === 'iva') {
                            precioFinal += precioFinal * 0.16;
                        }

                        const precioFinalRecortado = recortarADosDecimales(precioFinal);
                        n_precio = precioFinalRecortado;

                        resultadoEl.textContent = `$${formatNumber(precioFinalRecortado)} USD`;
                        previewCard.style.display = 'block';
                    }

                    document.getElementById('btn-actualizar').addEventListener('click', function() {
                        if (n_precio != 0) {
                            const inputPrecio = document.getElementById('precioMonedaOrigen');
                            inputPrecio.value = n_precio;
                            inputPrecio.dispatchEvent(new Event('keyup'));
                            if (typeof realizarCalculos === 'function') {
                                realizarCalculos();
                            }
                            document.getElementById('precio_proveedor').value = '';
                            const previewCard = document.getElementById('vista_previa_precio');
                            previewCard.style.display = 'none';
                            n_precio = 0;
                            modalContainer.classList.remove("active");
                        }
                    })
                })
            </script>


            <script>
                const tabla = $('#datatable-responsive').DataTable({
                    // El buscador y el largo de página salen del encabezado de DataTables:
                    // arriba solo queda la tabla; largo, información y paginación al pie.
                    dom: 't<"dt-bottom"lip>',
                    language: {
                        lengthMenu: 'Mostrar _MENU_ registros',
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                        infoFiltered: '(filtrado de _MAX_ registros en total)',
                        zeroRecords: 'No se encontraron resultados',
                        emptyTable: 'Ningún dato disponible en esta tabla',
                        loadingRecords: 'Cargando...',
                        processing: 'Procesando...',
                        search: 'Buscar:',
                        paginate: {
                            first: 'Primero',
                            last: 'Último',
                            next: 'Siguiente',
                            previous: 'Anterior'
                        }
                    }
                });

                // Buscador del encabezado (sustituye al que pinta DataTables)
                $('#productos-buscar').on('input', function() {
                    tabla.search(this.value).draw();
                });

                // Filtros "solo con stock", grupo y categoría: se aplican al dibujar,
                // sin recargar datos ni perder la página actual de la tabla.
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    if (settings.nTable.id !== 'datatable-responsive') return true;

                    const fila = settings.aoData[dataIndex] ? settings.aoData[dataIndex].nTr : null;
                    if (!fila) return true;

                    const grupo = document.getElementById('grupo-selector')?.value || '';
                    const categoria = document.getElementById('categoria-selector')?.value || '';
                    if (grupo && fila.dataset.grupo !== grupo) return false;
                    if (categoria && fila.dataset.categoria !== categoria) return false;

                    const switchStock = document.getElementById('switch-stock');
                    if (switchStock && switchStock.checked) {
                        return parseFloat(fila.dataset.stock || '0') > 0;
                    }
                    return true;
                });

                const switchSoloStock = document.getElementById('switch-stock');
                if (switchSoloStock) {
                    switchSoloStock.addEventListener('change', function() {
                        tabla.draw(false);
                    });
                }

                $('#grupo-selector').on('change', function() {
                    llenarCategoriasProductos(this.value, document.getElementById('categoria-selector').value);
                    tabla.draw(false);
                });

                $('#categoria-selector').on('change', function() {
                    tabla.draw(false);
                });

                let productos_editar
                let metodo_editar

                // Actualizar el producto
                document.getElementById('form-data').addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional

                    const form = e.target;
                    const formData = new FormData(form);

                    formData.append('accion', 'editar');
                    formData.append('metodo', metodo_editar);
                    formData.append('id', productos_editar);

                    // Obtener valores
                    const valores = {
                        nombre: form.nombre.value,
                        precio: form.precioMonedaOrigen.value,
                        cantidad: form.cantidad.value,
                        porcentaje: form.porcentaje.value,
                        origen: form.origenProducto.value,
                        proveedor: form.proveedor.value,
                        codigo_barra: form.codigo_barra.value,
                        categoria: form.categoria.value,
                        sucursal: document.getElementById('sucursal-selector').value,
                        sucursal_a_editar: document.getElementById('sucursal_a_editar').value
                    };

                    // Validación por método
                    switch (metodo_editar) {
                        case 'generales':
                            if (camposVacios([valores.nombre, valores.codigo_barra, valores.proveedor])) {
                                Alerta.mostrar('warning', 'Completa todos los campos: nombre, proveedor y código de barras.');
                                return;
                            }
                            break;

                        case 'precio':
                            if (camposVacios([valores.precio, valores.cantidad, valores.porcentaje, valores.origen])) {
                                Alerta.mostrar('warning', 'Completa todos los campos: precio, cantidad, porcentaje y origen.');
                                return;
                            }
                            break;

                        case 'porcentaje':
                            if (camposVacios([valores.porcentaje])) {
                                Alerta.mostrar('warning', 'El campo porcentaje no puede estar vacío.');
                                return;
                            }
                            if (!valores.sucursal && !valores.sucursal_a_editar) {
                                Alerta.mostrar('warning', 'Selecciona una sucursal.');
                                return;
                            }
                            break;
                    }


                    fetch('../../configurar/productos_gestor.php', {
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

                            if (json.success == true) {

                                Alerta.mostrar('success', json.message);
                                cancelarActualizacion()
                                const sucursal_filtro = document.getElementById('sucursal-selector').value
                                cargarProductos(sucursal_filtro)

                            } else {

                                Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                            }
                        })
                        .catch(err => {
                            modalContainer.classList.remove("active");

                            console.error("Error en la solicitud:", err);
                            Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                        });
                });




                document.addEventListener("DOMContentLoaded", function() {
                    let input = document.getElementById("codigo_barra");
                    let lastScan = 0; // Guarda el tiempo del último escaneo
                    let timeoutDuration = 6000; // Tiempo en milisegundos para bloquear (ajústalo según tu lector)

                    input.addEventListener("input", function() {});
                });


                let productos = []
                let categoriasPorGrupo = new Map();

                // Rellena el selector de categorías con las que pertenecen al grupo elegido.
                function llenarCategoriasProductos(grupo, seleccionada) {
                    const catSel = document.getElementById('categoria-selector');
                    if (!catSel) return;

                    const actual = seleccionada || '';
                    const categorias = [];
                    categoriasPorGrupo.forEach(function(g, nombre) {
                        if (!grupo || g === grupo) categorias.push(nombre);
                    });
                    categorias.sort(function(a, b) {
                        return a.localeCompare(b, 'es');
                    });

                    catSel.innerHTML = '';
                    catSel.add(new Option('Todas las categorías', ''));
                    categorias.forEach(function(nombre) {
                        catSel.add(new Option(nombre, nombre));
                    });
                    catSel.value = categorias.indexOf(actual) !== -1 ? actual : '';
                }

                // Reconstruye los filtros con los grupos/categorías del listado actual.
                function actualizarFiltrosProductos(rows) {
                    const grupoSel = document.getElementById('grupo-selector');
                    const catSel = document.getElementById('categoria-selector');
                    if (!grupoSel || !catSel) return;

                    const grupos = new Set();
                    categoriasPorGrupo = new Map();
                    rows.forEach(function(row) {
                        if (row.categoria_grupo) grupos.add(row.categoria_grupo);
                        if (row.categoria_nombre) {
                            categoriasPorGrupo.set(row.categoria_nombre, row.categoria_grupo || '');
                        }
                    });

                    const grupoActual = grupoSel.value;
                    const categoriaActual = catSel.value;

                    grupoSel.innerHTML = '';
                    grupoSel.add(new Option('Todos los grupos', ''));
                    Array.from(grupos).sort(function(a, b) {
                        return a.localeCompare(b, 'es');
                    }).forEach(function(grupo) {
                        grupoSel.add(new Option(grupo, grupo));
                    });
                    grupoSel.value = grupos.has(grupoActual) ? grupoActual : '';

                    llenarCategoriasProductos(grupoSel.value, categoriaActual);
                }

                // Cargar etabla de productos
                function cargarProductos(sucursal = '') {

                    fetch('../../configurar/productos_list.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                sucursal: sucursal
                            })
                        })
                        .then(response => response.text()) // Primero obtenemos el texto plano
                        .then(text => {
                            try {
                                const data = JSON.parse(text);

                                if (data.status === "success") {
                                    // Limpia la tabla antes de insertar nuevos datos
                                    tabla.clear();

                                    // Agrega fila por fila
                                    data.data.forEach(row => {
                                        productos[row.id] = row

                                        const rest = (row.mayor == '1' ? '<span style="margin: 5px;" class="fw-medium text-decoration-none me-2 badge badge-subtle-success">Mayor</span>' : row.stock)

                                        const hasFoto = !!row.imagen;

                                        const filaNueva = tabla.row.add([
                                            `<div class="foto-check ${hasFoto ? 'has-foto' : 'no-foto'}" data-producto-id="${row.id}" title="${hasFoto ? 'Tiene foto' : 'Sin foto'}"><ion-icon name="${hasFoto ? 'image-outline' : 'camera-outline'}"></ion-icon></div>`,
                                            row.nombre + `<button type="button" class="btn-foto-google" data-id="${row.id}" title="Buscar foto en Google Imágenes"><ion-icon name="logo-google"></ion-icon></button><br><small class="${row.categoria_grupo ? 'text-muted' : 'text-danger'}">${row.categoria_grupo || 'Sin grupo'}</small>`,
                                            row.precio_compra,
                                            row.cantidad_unidades,
                                            row.porcentaje,
                                            rest,
                                            formatNumber(row.precio_venta_dolar),
                                            formatNumber(formatPeso(row.precio_venta_peso)),
                                            formatNumber(recortarADosDecimales(row.precio_venta_bs)),
                                            `<a data-id="${row.id}" class="btn-edit"><i class="icon-gray line icon-pencil"></i></a>`,
                                            `<a href="ficha.php?id=${row.id}"><i style="color: #41c1af" class="icon-gray line icon-chart"></i></a>`,
                                            `<a class="c-pointer btn-delete" data-stock_id="${row.stock_id}" data-id="${row.id}"><i class="icon-gray line icon-trash"></i></a>`
                                        ]);
                                        $(filaNueva.node()).attr({
                                            'data-stock': row.stock,
                                            'data-grupo': row.categoria_grupo || '',
                                            'data-categoria': row.categoria_nombre || ''
                                        });
                                    });

                                    actualizarFiltrosProductos(data.data);
                                    tabla.draw(); // Redibuja la tabla

                                    if (sucursal == '') {
                                        tabla.column(4).visible(false);
                                    } else {
                                        tabla.column(4).visible(true);
                                    }

                                    // En teléfono la tabla de 12 columnas no cabe: se queda con
                                    // la foto, el nombre, el precio en dólares y las acciones.
                                    if (window.innerWidth <= 768) {
                                        const aOcultar = [2, 3, 4, 5, 7, 8];
                                        if (window.innerWidth <= 400) aOcultar.push(0); // ni la miniatura cabe
                                        aOcultar.forEach(i => tabla.column(i).visible(false));
                                    } else {
                                        [0, 2, 3, 5, 7, 8].forEach(i => tabla.column(i).visible(true));
                                    }

                                } else {
                                    console.error("Error:", data.message);
                                }

                            } catch (e) {
                                console.error("Error al parsear JSON:", e, "\nTexto recibido:", text);
                            }
                        })
                        .catch(error => {
                            console.error("Error en la solicitud:", error);
                        });

                }

                $(document).ready(function() {
                    cargarProductos($('#sucursal-selector').val());
                    $('#sucursal-selector').on('change', function() {
                        cargarProductos(this.value);
                    });

                    $('#categoria_select').select2({
                        tags: true,
                        placeholder: 'Seleccione o escriba una categoría',
                        allowClear: true,
                        createTag: function(params) {
                            var term = $.trim(params.term);
                            if (term === '') return null;
                            var slug = term.toLowerCase()
                                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                                .replace(/[^a-z0-9]+/g, '-')
                                .replace(/(^-|-$)/g, '');
                            return {
                                id: slug,
                                text: term,
                                newTag: true
                            };
                        },
                        templateResult: function(data) {
                            if (data.children) return null;
                            return data.text;
                        },
                        matcher: function(params, data) {
                            if ($.trim(params.term) === '') return data;
                            if (data.children) {
                                var matches = [];
                                data.children.forEach(function(child) {
                                    if (child.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                                        matches.push(child);
                                    }
                                });
                                if (matches.length > 0) return {
                                    text: data.text,
                                    children: matches
                                };
                                return null;
                            }
                            if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) return data;
                            return null;
                        }
                    });

                    $('#categoria_select').on('select2:select', function(e) {
                        if (e.params.data.newTag) {
                            var $select = $(this);
                            var tempId = e.params.data.id;
                            var text = e.params.data.text;
                            fetch('../../configurar/guardar_categoria.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: 'nombre=' + encodeURIComponent(text)
                                })
                                .then(function(r) {
                                    return r.json();
                                })
                                .then(function(data) {
                                    if (data.success) {
                                        $select.find('option[value="' + tempId + '"]').remove();
                                        var opt = new Option(text, data.id, true, true);
                                        $select.append(opt).trigger('change');
                                    }
                                });
                        }
                    });
                });


                // Escuchar el evento keydown en todo el documento
                document.addEventListener('keydown', function(event) {
                    // Si se presiona Enter y el foco no está en un textarea
                    if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
                        event.preventDefault(); // Evita que se dispare el submit
                        return false;
                    }
                });



                const nv = "<?php echo $_SESSION["nivel"] ?>"


                document.addEventListener('click', async (event) => {
                    // Icono junto al nombre: modal en la zona de subida + Google Imágenes
                    const btnGoogle = event.target.closest('.btn-foto-google');
                    if (btnGoogle) {
                        abrirFoto(parseInt(btnGoogle.getAttribute('data-id'), 10), true);
                        return;
                    }

                    if (event.target.closest('.btn-edit')) {
                        const id = event.target.closest('.btn-edit').getAttribute('data-id');


                        const sucursalSeleccionada = document.querySelector('#sucursal-selector')?.value;

                        if (sucursalSeleccionada !== '' && nv != 1) {
                            // Si hay una sucursal seleccionada, omitir el Swal y continuar con 'porcentaje'
                            const opcion = 'porcentaje';
                            cargarDatosForm(productos[id], opcion)

                            return;
                        }

                        const swalOptions = {
                            title: '¿Que desea hacer?',
                            icon: 'question',
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Cancelar',
                            html: `
                            <div style="text-align: center;">
                                <div class="swal-option" data-option="generales" style="margin-bottom: 15px; cursor: pointer;">
                                    <strong class="text-info">Modificar datos generales del producto</strong><br>
                                    <em>Establece valores globales del producto. (nombre, código de barras, proveedor, etc.)</em>
                                </div>
                                <div class="swal-option" data-option="precio" style="margin-bottom: 15px; cursor: pointer;">
                                    <strong class="text-info">Modificar precio general del producto</strong><br>
                                    <em>Define el precio base por defecto.</em>
                                </div>
                                <div class="swal-option" data-option="porcentaje" style="margin-bottom: 15px; cursor: pointer;">
                                    <strong class="text-info">Modificar porcentaje por sucursal</strong><br>
                                    <em>Define la ganancia por cada sucursal.</em>
                                </div>
                                <div class="swal-option" data-option="foto" style="cursor: pointer;">
                                    <strong class="text-info">Agregar/Modificar foto del producto</strong><br>
                                    <em>Sube o selecciona una imagen para el producto.</em>
                                </div>
                            </div>`,
                            didOpen: () => {
                                const opciones = Swal.getHtmlContainer().querySelectorAll('.swal-option');
                                opciones.forEach(div => {
                                    div.addEventListener('click', () => {
                                        const opcion = div.getAttribute('data-option');
                                        Swal.close(); // Cierra el SweetAlert
                                        if (opcion === 'foto') {
                                            abrirFoto(productos[id].id)
                                        } else {
                                            cargarDatosForm(productos[id], opcion)
                                        }
                                    });
                                });
                            }
                        };
                        await Swal.fire(swalOptions);
                    }

                    if (event.target.closest('.btn-delete')) {
                        const id = event.target.closest('.btn-delete').getAttribute('data-id')
                        const stock_id = event.target.closest('.btn-delete').getAttribute('data-stock_id')
                        confirmarDelete(id, stock_id)
                    }
                });



                // Eliminar producto
                function confirmarDelete(id, stock_id) {
                    Swal.fire({
                        title: 'Esta seguro?',
                        html: 'Se eliminara el producto ¿desea continuar?',
                        icon: 'question',
                        confirmButtonText: 'Eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#32d7c0',
                        showCancelButton: true,

                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteProduct(id, stock_id)
                        }
                    })
                }

                function deleteProduct(id, stock_id) {
                    const sucursal = document.getElementById('sucursal-selector').value
                    const modo = (sucursal == '' ? 'p' : 's')

                    $.ajax({
                        url: '../../configurar/deleteProAjax.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            id: id,
                            modo: modo,
                            stock_id: stock_id
                        },
                        success: function(response) {
                            if (response.success) {
                                Alerta.toast('success', response.message);
                                cargarProductos($('#sucursal-selector').val());
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX Error:', status, error);
                            alert('Ocurrió un error en la comunicación con el servidor.');
                        }
                    });
                }





                // Mostrar el formulario con las opciones indicadas
                function cargarDatosForm(datos, opcion) {
                    productos_editar = datos.id
                    metodo_editar = opcion


                    // Asignación de valores
                    document.getElementById('nombre').value = datos.nombre;
                    // El backend formatea los miles con coma: se quitan para dejar solo el punto decimal
                    document.getElementById('precioMonedaOrigen').value = String(datos.precio_compra).replace(/,/g, '');
                    document.getElementById('cantidad').value = datos.cantidad_unidades;

                    const porcentajeLimpio = typeof datos.porcentaje === 'string' ?
                        datos.porcentaje.replace('%', '').replace(/,/g, '').trim() :
                        datos.porcentaje;
                    document.getElementById('porcentaje').value = porcentajeLimpio;

                    let origen = datos.origen

                    if (origen == '') {
                        origen = 'v'
                    }

                    document.getElementById('origenProducto').value = datos.origen;
                    document.getElementById('proveedor').value = datos.proveedor;
                    document.getElementById('codigo_barra').value = datos.codigo_barras;
                    document.getElementById('categoria_select').value = datos.categoria || '';
                    $('#categoria_select').trigger('change');

                    // Mostrar formulario de edición
                    document.getElementById('section_edit').classList.remove('hide');

                    // Acciones según opción
                    const campos = {
                        nombre: document.getElementById('nombre'),
                        precio: document.getElementById('precioMonedaOrigen'),
                        cantidad: document.getElementById('cantidad'),
                        porcentaje: document.getElementById('porcentaje'),
                        origen: document.getElementById('origenProducto'),
                        proveedor: document.getElementById('proveedor'),
                        codigo_barra: document.getElementById('codigo_barra'),
                        categoria: document.getElementById('categoria_select')
                    };

                    const sucursalSection = document.getElementById('sucursal_section');
                    const sucursalSelector = document.getElementById('sucursal-selector');
                    const sucursalEditar = document.getElementById('sucursal_a_editar');

                    // Habilitar todos los campos primero
                    for (let key in campos) campos[key].disabled = false;

                    if (opcion === 'precio') {
                        // Desactivar todos excepto los permitidos
                        campos.nombre.disabled = true;
                        campos.proveedor.disabled = true;
                        campos.codigo_barra.disabled = true;
                        campos.categoria.disabled = true;

                    } else if (opcion === 'porcentaje') {
                        // Desactivar todos excepto porcentaje
                        campos.nombre.disabled = true;
                        campos.precio.disabled = true;
                        campos.cantidad.disabled = true;
                        campos.origen.disabled = true;
                        campos.proveedor.disabled = true;
                        campos.categoria.disabled = true;

                        // Manejo de sucursales
                        if (sucursalSelector.value === '') {
                            sucursalSection.classList.remove('hide');
                        } else {
                            sucursalSection.classList.add('hide');
                            sucursalEditar.value = sucursalSelector.value;
                        }
                    } else if (opcion === 'generales') {
                        campos.precio.disabled = true;
                        campos.cantidad.disabled = true;
                        campos.origen.disabled = true;
                        campos.porcentaje.disabled = true;
                    }

                    document.getElementById('section_lista').classList.add('hide');

                    realizarCalculos();
                }

                function cancelarActualizacion() {
                    document.getElementById('section_edit').classList.add('hide');
                    document.getElementById('section_lista').classList.remove('hide');

                    // Resetear el formulario
                    document.getElementById('form-data').reset();

                    // Reactivar todos los campos
                    const fields = document.querySelectorAll('#form-data input, #form-data select, #form-data textarea');
                    fields.forEach(field => {
                        field.disabled = false;
                    });

                    // Ocultar sección de sucursal (si se mostró)
                    const sucursalSection = document.getElementById('sucursal_section');
                    if (sucursalSection) {
                        sucursalSection.classList.add('hide');
                    }
                }

                // Restaurar el formulario al hacer clic en cancelar
                document.getElementById('btn-cancelar').addEventListener('click', cancelarActualizacion);

                // Al seleccionar una sucursal
                document.getElementById('sucursal_a_editar').addEventListener('change', (event) => {
                    actualizarSucursal(event.target.value);
                });


                function actualizarSucursal(sucursal) {
                    const idProducto = productos_editar;



                    fetch('../../configurar/porcentaje_producto_especifico.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                producto_id: idProducto,
                                sucursal: sucursal
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            //   Swal.close(); // Cerrar loader

                            if (data.success) {
                                const porcentajeInput = document.getElementById("porcentaje");
                                porcentajeInput.value = data.data;

                                realizarCalculos()
                            } else {
                                Alerta.mostrar('warning', data.mensaje);
                            }
                        })
                        .catch(error => {
                            //    Swal.close(); // Cerrar loader en caso de error
                            console.error('Error en la petición:', error);
                            Alerta.mostrar('error', 'Error de red o del servidor.');
                        });
                }
                //   
            </script>

            <style>
                /* Indicador de foto en la tabla */
                .foto-check {
                    width: 30px;
                    height: 30px;
                    border-radius: 8px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 1rem;
                }

                .foto-check.has-foto {
                    background: rgba(45, 212, 160, 0.12);
                    color: var(--dash-mint);
                }

                .foto-check.no-foto {
                    background: var(--dash-bg);
                    color: var(--dash-border);
                    border: 1px dashed var(--dash-border);
                }

                /* Icono junto al nombre: abre el modal de foto en la zona de subida */
                .btn-foto-google {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 26px;
                    height: 26px;
                    margin-left: 6px;
                    padding: 0;
                    vertical-align: middle;
                    font-size: 14px;
                    line-height: 1;
                    color: var(--dash-text-muted);
                    background: transparent;
                    border: 1px solid var(--dash-border);
                    border-radius: 7px;
                    cursor: pointer;
                    transition: color .2s, border-color .2s, background .2s;
                }

                .btn-foto-google:hover {
                    color: var(--dash-mint);
                    border-color: var(--dash-mint);
                    background: rgba(45, 212, 160, .08);
                }

                /* Interruptor "solo con stock" */
                .switch {
                    position: relative;
                    display: inline-block;
                    width: 46px;
                    height: 24px;
                    flex-shrink: 0;
                }

                .switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }

                .switch .slider {
                    position: absolute;
                    cursor: pointer;
                    inset: 0;
                    background: var(--dash-border);
                    border-radius: 24px;
                    transition: .3s;
                }

                .switch .slider:before {
                    content: "";
                    position: absolute;
                    height: 16px;
                    width: 16px;
                    left: 4px;
                    bottom: 4px;
                    background: #fff;
                    border-radius: 50%;
                    transition: .3s;
                }

                .switch input:checked + .slider {
                    background: var(--dash-mint);
                }

                .switch input:checked + .slider:before {
                    transform: translateX(22px);
                }

                /* Barra de herramientas de la tabla */
                .productos-toolbar {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .productos-toolbar-actions {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    flex-wrap: wrap;
                    margin-left: auto;
                }

                /* El tema deja el h2 flotado y con ancho fijo: en flex eso lo empuja a otra línea */
                .productos-toolbar h2 {
                    float: none;
                    width: auto;
                    margin: 0;
                }

                .productos-search {
                    position: relative;
                }

                .productos-search ion-icon {
                    position: absolute;
                    left: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: 15px;
                    color: var(--dash-text-muted);
                    pointer-events: none;
                }

                .productos-search input {
                    width: 240px;
                    max-width: 100%;
                    padding: 8px 12px 8px 32px;
                    background: var(--dash-bg);
                    border: 1px solid var(--dash-border);
                    border-radius: 8px;
                    color: var(--dash-text);
                    font-size: 13px;
                }

                .productos-search input:focus {
                    outline: none;
                    border-color: var(--dash-mint);
                    box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.1);
                }

                .productos-stock {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin: 0;
                    font-size: 12px;
                    font-weight: 400;
                    white-space: nowrap;
                    cursor: pointer;
                }

                /* Controles propios de DataTables: solo al pie, alineados */
                .dataTables_wrapper .dt-bottom {
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                    gap: 12px;
                    flex-wrap: wrap;
                    padding-top: 12px;
                }

                /* El largo de página queda a la izquierda; info y paginación a la derecha */
                .dataTables_wrapper .dt-bottom .dataTables_length {
                    margin-right: auto;
                }

                .dataTables_wrapper .dt-bottom .dataTables_info {
                    width: auto;
                    padding-top: 0;
                }

                .dataTables_wrapper .dt-bottom .dataTables_paginate {
                    width: auto;
                    float: none;
                    margin: 0;
                }

                @media (max-width: 900px) {
                    .productos-toolbar-actions {
                        width: 100%;
                        margin-left: 0;
                        flex-wrap: wrap;
                    }
                }

                @media (max-width: 768px) {
                    .productos-search {
                        flex: 1 1 100%;
                    }

                    .productos-search input {
                        width: 100%;
                    }
                }

                /* ── Precios de venta: tarjetas de resumen ── */
                .price-card {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 14px 16px;
                    margin-bottom: 12px;
                    background: rgba(255, 255, 255, 0.02);
                    border: 1px solid var(--dash-border, #2e353e);
                    border-radius: 12px;
                }

                .price-card .pc-icon {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 40px;
                    height: 40px;
                    flex-shrink: 0;
                    font-size: 20px;
                    color: var(--dash-text-muted, #8892a0);
                    background: rgba(255, 255, 255, 0.04);
                    border-radius: 10px;
                }

                .price-card .pc-info {
                    display: flex;
                    flex-direction: column;
                    gap: 1px;
                    min-width: 0;
                }

                .price-card .pc-label {
                    font-size: 13px;
                    font-weight: 600;
                    color: var(--dash-text, #e8edf2);
                }

                .price-card .pc-sub {
                    font-size: 11px;
                    letter-spacing: .04em;
                    text-transform: uppercase;
                    color: var(--dash-text-muted, #8892a0);
                }

                .price-card .pc-value {
                    margin-left: auto;
                    min-width: 0;
                    width: 45%;
                    padding: 0;
                    font-size: 18px;
                    font-weight: 700;
                    text-align: right;
                    color: var(--dash-text, #e8edf2);
                    background: transparent;
                    border: none;
                }

                .price-card .pc-value:focus {
                    outline: none;
                }

                .price-card.highlight {
                    border-color: rgba(45, 212, 160, 0.45);
                    background: linear-gradient(135deg, rgba(45, 212, 160, 0.12), rgba(45, 212, 160, 0.03));
                }

                .price-card.highlight .pc-icon {
                    color: var(--dash-mint, #2dd4a0);
                    background: rgba(45, 212, 160, 0.15);
                }

                .price-card.highlight .pc-value {
                    color: var(--dash-mint, #2dd4a0);
                }

                .price-card.muted .pc-value {
                    font-size: 16px;
                    color: var(--dash-text-muted, #8892a0);
                }

                /* ── Modal Calcular Precio compacto sin scroll ── */
                #w-modal.modal-calc-precio {
                    width: 95% !important;
                    max-width: 440px !important;
                    height: auto !important;
                    max-height: none !important;
                    padding: 0 !important;
                    overflow: visible !important;
                    border-radius: 14px !important;
                    background: #181b22 !important;
                    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.08) !important;
                }

                #w-modal.modal-calc-precio #modal {
                    height: auto !important;
                    max-height: none !important;
                    overflow: visible !important;
                }

                #w-modal.modal-calc-precio #modal-body {
                    height: auto !important;
                    max-height: none !important;
                    overflow: visible !important;
                }

                /* ── Foto Modal ────────────────────────────────────────────── */
                .foto-modal {
                    position: relative;
                    z-index: 1051;
                    pointer-events: auto;
                    background: var(--dash-card);
                    border: 1px solid var(--dash-border);
                    border-radius: 20px;
                    overflow: hidden;
                    box-shadow: 0 25px 80px rgba(0, 0, 0, .35), 0 0 0 1px rgba(255, 255, 255, .04) inset;
                }

                .fm-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 1.5rem 1.75rem 1rem;
                }

                .fm-header-left {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                }

                .fm-product-icon {
                    width: 48px;
                    height: 48px;
                    border-radius: 14px;
                    background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #1a1e24;
                    font-size: 1.3rem;
                    box-shadow: 0 4px 20px rgba(45, 212, 160, .3);
                }

                .fm-title {
                    font-size: 1.1rem;
                    font-weight: 700;
                    color: var(--dash-text);
                }

                .fm-subtitle {
                    font-size: .8rem;
                    color: var(--dash-text-muted);
                    margin-top: 2px;
                }

                .fm-close {
                    width: 36px;
                    height: 36px;
                    border-radius: 10px;
                    border: 1px solid var(--dash-border);
                    background: transparent;
                    color: var(--dash-text-muted);
                    font-size: 1.1rem;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: all .2s;
                }

                .fm-close:hover {
                    background: rgba(239, 90, 111, .1);
                    border-color: var(--dash-danger);
                    color: var(--dash-danger);
                }

                .fm-tabs {
                    display: flex;
                    gap: .25rem;
                    padding: 0 1.75rem;
                    border-bottom: 1px solid var(--dash-border);
                }

                .fm-tab {
                    padding: .7rem 1.2rem;
                    font-size: .8rem;
                    font-weight: 600;
                    color: var(--dash-text-muted);
                    background: transparent;
                    border: none;
                    border-bottom: 2px solid transparent;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    transition: all .2s;
                    position: relative;
                    bottom: -1px;
                }

                .fm-tab:hover {
                    color: var(--dash-text);
                }

                .fm-tab.active {
                    color: var(--dash-mint);
                    border-bottom-color: var(--dash-mint);
                }

                .fm-tab ion-icon {
                    font-size: 1rem;
                }

                .fm-body {
                    padding: 1.25rem 1.75rem 1.75rem;
                    min-height: 380px;
                }

                .fm-panel {
                    display: none;
                }

                .fm-panel.active {
                    display: block;
                    animation: fmFadeIn .25s ease;
                }

                @keyframes fmFadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(6px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .fm-search-wrap {
                    position: relative;
                    margin-bottom: 1rem;
                }

                .fm-search-icon {
                    position: absolute;
                    left: 14px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: 1rem;
                    color: var(--dash-text-muted);
                    pointer-events: none;
                }

                .fm-search-input {
                    width: 100%;
                    padding: 12px 80px 12px 42px;
                    background: var(--dash-bg);
                    border: 1px solid var(--dash-border);
                    border-radius: 12px;
                    font-size: .85rem;
                    color: var(--dash-text);
                    transition: all .25s;
                }

                .fm-search-input:focus {
                    outline: none;
                    border-color: var(--dash-mint);
                    box-shadow: 0 0 0 3px rgba(45, 212, 160, .1);
                }

                .fm-search-count {
                    position: absolute;
                    right: 14px;
                    top: 50%;
                    transform: translateY(-50%);
                    font-size: .7rem;
                    color: var(--dash-text-muted);
                    font-weight: 600;
                    background: var(--dash-card);
                    padding: 2px 8px;
                    border-radius: 6px;
                    border: 1px solid var(--dash-border);
                }

                .fm-results {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                    gap: .6rem;
                    max-height: 300px;
                    overflow-y: auto;
                    scrollbar-width: thin;
                    scrollbar-color: var(--dash-border) transparent;
                }

                .fm-results::-webkit-scrollbar {
                    width: 5px;
                }

                .fm-results::-webkit-scrollbar-track {
                    background: transparent;
                }

                .fm-results::-webkit-scrollbar-thumb {
                    background: var(--dash-border);
                    border-radius: 10px;
                }

                .fm-result-card {
                    border-radius: 12px;
                    overflow: hidden;
                    cursor: pointer;
                    border: 2px solid transparent;
                    transition: all .25s cubic-bezier(.4, 0, .2, 1);
                    position: relative;
                    background: var(--dash-bg);
                }

                .fm-result-card:hover {
                    border-color: var(--dash-mint);
                    transform: translateY(-3px);
                    box-shadow: 0 8px 25px rgba(45, 212, 160, .15);
                }

                .fm-result-card img {
                    width: 100%;
                    aspect-ratio: 1;
                    object-fit: cover;
                    display: block;
                }

                .fm-result-card .fm-result-name {
                    padding: 6px 8px;
                    font-size: .65rem;
                    font-weight: 600;
                    color: var(--dash-text);
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    text-align: center;
                }

                .fm-result-card.selected {
                    border-color: var(--dash-mint);
                    box-shadow: 0 0 0 3px rgba(45, 212, 160, .2);
                }

                .fm-result-card.selected::after {
                    content: '';
                    position: absolute;
                    top: 6px;
                    right: 6px;
                    width: 22px;
                    height: 22px;
                    border-radius: 50%;
                    background: var(--dash-mint);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%231a1e24' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='20 6 9 17 4 12'/%3E%3C/svg%3E");
                    background-repeat: no-repeat;
                    background-position: center;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
                }

                .fm-empty {
                    grid-column: 1 / -1;
                    text-align: center;
                    padding: 3rem 1rem;
                    color: var(--dash-text-muted);
                    display: flex;
                    flex-direction: column;
                }

                .fm-empty ion-icon {
                    font-size: 2.5rem;
                    margin: 0.75rem auto;
                    display: block;
                    opacity: .4;
                }

                .fm-empty p {
                    font-size: .85rem;
                }

                .fm-upload-zone {
                    border: 2px dashed var(--dash-border);
                    border-radius: 16px;
                    padding: 3rem 2rem;
                    text-align: center;
                    cursor: pointer;
                    transition: all .3s;
                }

                .fm-upload-zone:hover,
                .fm-upload-zone.dragover {
                    border-color: var(--dash-mint);
                    background: rgba(45, 212, 160, .04);
                }

                .fm-upload-icon {
                    font-size: 2.8rem;
                    color: var(--dash-border);
                    margin-bottom: .75rem;
                    transition: color .3s;
                }

                .fm-upload-zone:hover .fm-upload-icon {
                    color: var(--dash-mint);
                }

                .fm-upload-title {
                    font-size: .95rem;
                    font-weight: 700;
                    color: var(--dash-text);
                    margin-bottom: .25rem;
                }

                .fm-upload-hint {
                    font-size: .8rem;
                    color: var(--dash-text-muted);
                    margin-bottom: 1rem;
                }

                .fm-upload-btn {
                    padding: 10px 24px;
                    border-radius: 10px;
                    border: none;
                    cursor: pointer;
                    font-size: .8rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
                    color: #1a1e24;
                    transition: all .2s;
                    box-shadow: 0 4px 15px rgba(45, 212, 160, .25);
                }

                .fm-upload-btn:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
                }

                .fm-upload-formats {
                    font-size: .7rem;
                    color: var(--dash-text-muted);
                    margin-top: .75rem;
                }

                .fm-upload-preview {
                    text-align: center;
                }

                .fm-google-link {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    margin-top: 1rem;
                    font-size: .8rem;
                    font-weight: 600;
                    color: var(--dash-mint);
                    text-decoration: none;
                }

                .fm-google-link:hover {
                    color: var(--dash-text);
                    text-decoration: underline;
                }

                .fm-upload-preview img {
                    max-width: 100%;
                    max-height: 260px;
                    border-radius: 12px;
                    border: 1px solid var(--dash-border);
                    margin-bottom: 1rem;
                    box-shadow: 0 4px 20px rgba(0, 0, 0, .15);
                }

                .fm-preview-actions {
                    display: flex;
                    gap: .5rem;
                    justify-content: center;
                }

                .fm-btn-primary {
                    padding: 10px 24px;
                    border-radius: 10px;
                    border: none;
                    cursor: pointer;
                    font-size: .8rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
                    color: #1a1e24;
                    transition: all .2s;
                }

                .fm-btn-primary:hover {
                    box-shadow: 0 4px 15px rgba(45, 212, 160, .3);
                }

                .fm-btn-secondary {
                    padding: 10px 24px;
                    border-radius: 10px;
                    cursor: pointer;
                    font-size: .8rem;
                    font-weight: 600;
                    background: transparent;
                    border: 1px solid var(--dash-border);
                    color: var(--dash-text-muted);
                    transition: all .2s;
                }

                .fm-btn-secondary:hover {
                    border-color: var(--dash-text);
                    color: var(--dash-text);
                }

                .fm-selected-bar {
                    display: none;
                    align-items: center;
                    justify-content: space-between;
                    padding: .75rem 1.75rem;
                    border-top: 1px solid var(--dash-border);
                    background: rgba(45, 212, 160, .04);
                }

                .fm-selected-bar.show {
                    display: flex;
                }

                .fm-selected-info {
                    font-size: .8rem;
                    color: var(--dash-text);
                    font-weight: 600;
                }

                .fm-selected-info span {
                    color: var(--dash-mint);
                }

                .fm-apply-btn {
                    padding: 10px 28px;
                    border-radius: 10px;
                    border: none;
                    cursor: pointer;
                    font-size: .8rem;
                    font-weight: 700;
                    background: linear-gradient(135deg, var(--dash-mint), #3bb8a0);
                    color: #1a1e24;
                    transition: all .2s;
                    box-shadow: 0 4px 15px rgba(45, 212, 160, .25);
                }

                .fm-apply-btn:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
                }

                /* Fix z-index above #navbar (9999) */
                #modalFoto {
                    z-index: 10000;
                }

                #modalFoto .modal-backdrop {
                    z-index: 9999;
                }

                #modalFoto .modal-dialog {
                    margin-left: auto;
                    margin-right: auto;
                }

                /* ── Móvil: la tabla se queda con las columnas útiles (ver cargarProductos) ── */
                @media (max-width: 768px) {

                    #datatable-responsive thead th,
                    #datatable-responsive tbody td {
                        padding: 10px 8px;
                    }

                    /* Espacio de dedo para editar / ver / borrar */
                    #datatable-responsive td a {
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        width: 36px;
                        height: 36px;
                    }

                    .foto-check {
                        width: 34px;
                        height: 34px;
                    }

                    .btn-foto-google {
                        width: 34px;
                        height: 34px;
                        margin-left: 4px;
                    }
                }
            </style>

            <!-- Modal: Selector de Foto -->
            <div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="foto-modal">
                        <div class="fm-header">
                            <div class="fm-header-left">
                                <div class="fm-product-icon">
                                    <ion-icon name="camera"></ion-icon>
                                </div>
                                <div>
                                    <div class="fm-title" id="fm-title">Seleccionar imagen</div>
                                    <div class="fm-subtitle" id="fm-subtitle">Busca, sube o selecciona una foto para tu producto</div>
                                </div>
                            </div>
                            <button type="button" class="fm-close" data-dismiss="modal">
                                <ion-icon name="close"></ion-icon>
                            </button>
                        </div>

                        <div class="fm-tabs">
                            <button class="fm-tab active" data-tab="buscar" onclick="fmSwitchTab('buscar', this)">
                                <ion-icon name="search"></ion-icon> Buscar imagen
                            </button>
                            <button class="fm-tab" data-tab="subir" onclick="fmSwitchTab('subir', this)">
                                <ion-icon name="cloud-upload"></ion-icon> Subir archivo
                            </button>
                        </div>

                        <div class="fm-body">
                            <div class="fm-panel active" id="fm-panel-buscar">
                                <div class="fm-search-wrap">
                                    <ion-icon name="search" class="fm-search-icon"></ion-icon>
                                    <input type="text" id="fm-search" class="fm-search-input" placeholder="Escribe el nombre del producto...">
                                    <span class="fm-search-count" id="fm-count"></span>
                                </div>
                                <div class="fm-results" id="fm-results">
                                    <div class="fm-empty">
                                        <ion-icon name="images-outline"></ion-icon>
                                        <p>Escribe para buscar imágenes de productos</p>
                                    </div>
                                </div>
                            </div>

                            <div class="fm-panel" id="fm-panel-subir">
                                <div class="fm-upload-zone" id="fm-dropzone">
                                    <input type="file" id="fm-file-input" accept="image/jpeg,image/png,image/webp" hidden>
                                    <div class="fm-upload-icon">
                                        <ion-icon name="cloud-upload-outline"></ion-icon>
                                    </div>
                                    <p class="fm-upload-title">Arrastra una imagen aquí</p>
                                    <p class="fm-upload-hint">o haz clic para seleccionar &middot; tambi&eacute;n puedes pegar con Ctrl+V o arrastrar la imagen desde Google</p>
                                    <button class="fm-upload-btn" onclick="document.getElementById('fm-file-input').click()">
                                        Seleccionar archivo
                                    </button>
                                    <p class="fm-upload-formats">JPEG, PNG o WebP — Máx. 1.5MB</p>
                                </div>
                                <div class="fm-upload-preview" id="fm-upload-preview" style="display:none;">
                                    <img id="fm-preview-img" src="" alt="Preview">
                                    <div class="fm-preview-actions">
                                        <button class="fm-btn-secondary" onclick="fmClearPreview()">Cambiar</button>
                                        <button class="fm-btn-primary" onclick="fmUploadPhoto()">Usar esta foto</button>
                                    </div>
                                </div>
                                <div style="text-align:center;">
                                    <a id="fm-google-link" class="fm-google-link" href="https://www.google.com/search?tbm=isch" target="_blank" rel="noopener">
                                        <ion-icon name="logo-google"></ion-icon> Buscar esta foto en Google Imágenes
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="fm-selected-bar" id="fm-selected-bar">
                            <div class="fm-selected-info"></div>
                            <button class="fm-apply-btn" onclick="fmApplySelected()">Aplicar imagen</button>
                        </div>
                    </div>
                </div>
            </div>

            <script src="../vendors/fuse/fuse.min.js"></script>
            <script>
                /* ── Selector de foto (mismo componente que tienda_productos.php) ── */
                const FOTO_API = 'db/tienda_productos_api.php';
                let fmSelectedImage = null;
                let fmProductos = [];
                let fmFuse = null;
                let fmProductoActual = null;

                fetch('../../iseller_store/img_sitema/productos.json')
                    .then(r => r.json())
                    .then(data => {
                        fmProductos = data;
                        fmFuse = new Fuse(data, {
                            threshold: 0.35,
                            distance: 50,
                            includeScore: true
                        });
                    })
                    .catch(() => {});

                function abrirFoto(productoId, irAGoogle) {
                    fmProductoActual = productoId;
                    fmSelectedImage = null;
                    fmClearPreview();

                    const nombre = (productos[productoId] && productos[productoId].nombre) || '';
                    document.getElementById('fm-title').textContent = nombre || 'Seleccionar imagen';
                    document.getElementById('fm-subtitle').textContent = 'Busca, sube o selecciona una foto para tu producto';

                    const searchInput = document.getElementById('fm-search');
                    searchInput.value = nombre;
                    fmDoSearch(nombre);

                    // Enlace directo a la búsqueda de imágenes en Google con el nombre del producto
                    document.getElementById('fm-google-link').href =
                        'https://www.google.com/search?tbm=isch&q=' + encodeURIComponent(nombre);

                    $('#modalFoto').modal('show');

                    if (irAGoogle) {
                        // Botón de la tabla: abre directo la zona de subida y lanza Google Imágenes
                        fmSwitchTab('subir', document.querySelector('.fm-tab[data-tab="subir"]'));
                        document.getElementById('fm-google-link').click();
                        return;
                    }

                    fmSwitchTab('buscar', document.querySelector('.fm-tab[data-tab="buscar"]'));
                }

                // Refresca solo el icono de foto de la fila; no recarga la tabla ni pierde la página
                function actualizarIconoFoto(productoId) {
                    const icono = document.querySelector('#datatable-responsive .foto-check[data-producto-id="' + productoId + '"]');
                    if (!icono) return;
                    icono.classList.remove('no-foto');
                    icono.classList.add('has-foto');
                    icono.title = 'Tiene foto';
                    icono.innerHTML = '<ion-icon name="image-outline"></ion-icon>';
                    if (productos[productoId]) productos[productoId].imagen = '1';
                }

                function fmSwitchTab(tab, btn) {
                    document.querySelectorAll('.fm-tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.fm-panel').forEach(p => p.classList.remove('active'));
                    btn.classList.add('active');
                    document.getElementById(`fm-panel-${tab}`).classList.add('active');
                }

                function fmDoSearch(query) {
                    const container = document.getElementById('fm-results');
                    const countEl = document.getElementById('fm-count');

                    if (!query || !fmFuse) {
                        container.innerHTML = `<div class="fm-empty text-center">
                            <ion-icon name="images-outline"></ion-icon>
                            <p>Escribe para buscar imágenes de productos</p>
                        </div>`;
                        countEl.textContent = '';
                        return;
                    }

                    const results = fmFuse.search(query);
                    countEl.textContent = `${results.length} resultado${results.length !== 1 ? 's' : ''}`;

                    if (results.length === 0) {
                        container.innerHTML = `<div class="fm-empty">
                            <ion-icon name="search-outline"></ion-icon>
                            <p>No se encontraron imágenes para "<strong>${query}</strong>"</p>
                        </div>`;
                        return;
                    }

                    container.innerHTML = results.map(r => {
                        const nombre = r.item;
                        const fileName = nombre.replace(/\s+/g, '-').toLowerCase();
                        return `<div class="fm-result-card" onclick="fmSelectImage('${fileName}', this)" title="${nombre}">
                            <img src="../../iseller_store/img_sitema/${fileName}.webp" alt="${nombre}"
                                 onerror="this.parentElement.style.display='none'">
                            <div class="fm-result-name">${nombre}</div>
                        </div>`;
                    }).join('');
                }

                function fmSelectImage(fileName, el) {
                    document.querySelectorAll('.fm-result-card').forEach(c => c.classList.remove('selected'));
                    el.classList.add('selected');
                    fmSelectedImage = fileName;

                    const bar = document.querySelector('.fm-selected-bar');
                    bar.classList.add('show');
                    bar.querySelector('.fm-selected-info').innerHTML = `Seleccionado: <span>${fileName.replace(/-/g, ' ')}</span>`;
                }

                function fmApplySelected() {
                    if (!fmSelectedImage || !fmProductoActual) return;
                    Alerta.toast('info', 'Aplicando imagen…');

                    fetch(`../../iseller_store/img_sitema/${fmSelectedImage}.webp`)
                        .then(r => {
                            if (!r.ok) throw new Error('Imagen no encontrada');
                            return r.blob();
                        })
                        .then(blob => {
                            const file = new File([blob], `${fmSelectedImage}.webp`, {
                                type: 'image/webp'
                            });
                            const fd = new FormData();
                            fd.append('action', 'upload_foto');
                            fd.append('producto_id', fmProductoActual);
                            fd.append('foto', file);

                            return fetch(FOTO_API, {
                                method: 'POST',
                                body: fd
                            });
                        })
                        .then(r => r.json())
                        .then(r => {
                            if (!r.ok) {
                                Alerta.toast('error', 'Error: ' + r.msg);
                                return;
                            }
                            Alerta.toast('success', 'Foto aplicada con éxito');
                            $('#modalFoto').modal('hide');
                            actualizarIconoFoto(fmProductoActual);
                        })
                        .catch(() => Alerta.toast('error', 'Error al aplicar imagen'));
                }

                function fmClearPreview() {
                    document.getElementById('fm-upload-preview').style.display = 'none';
                    document.getElementById('fm-dropzone').style.display = '';
                    document.getElementById('fm-file-input').value = '';
                }

                document.getElementById('fm-search')?.addEventListener('input', function() {
                    clearTimeout(this._debounce);
                    this._debounce = setTimeout(() => fmDoSearch(this.value), 200);
                });

                document.getElementById('fm-file-input')?.addEventListener('change', function() {
                    if (!this.files[0]) return;
                    const file = this.files[0];
                    const reader = new FileReader();
                    reader.onload = e => {
                        document.getElementById('fm-preview-img').src = e.target.result;
                        document.getElementById('fm-dropzone').style.display = 'none';
                        document.getElementById('fm-upload-preview').style.display = '';
                    };
                    reader.readAsDataURL(file);
                });

                function fmUploadPhoto() {
                    const input = document.getElementById('fm-file-input');
                    if (!input.files[0] || !fmProductoActual) return;

                    const fd = new FormData();
                    fd.append('action', 'upload_foto');
                    fd.append('producto_id', fmProductoActual);
                    fd.append('foto', input.files[0]);
                    Alerta.toast('info', 'Subiendo foto…');

                    fetch(FOTO_API, {
                            method: 'POST',
                            body: fd
                        })
                        .then(r => r.json())
                        .then(r => {
                            if (!r.ok) {
                                Alerta.toast('error', 'Error: ' + r.msg);
                                return;
                            }
                            Alerta.toast('success', 'Foto guardada con éxito');
                            $('#modalFoto').modal('hide');
                            actualizarIconoFoto(fmProductoActual);
                        });
                }

                const fmDropzone = document.getElementById('fm-dropzone');
                const fmPanelSubir = document.getElementById('fm-panel-subir');

                // Deja un archivo (subido o pegado) en el input para que salga la vista previa
                function fmMostrarPreview(file) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    const input = document.getElementById('fm-file-input');
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change'));
                }

                // Arrastrar desde Google no entrega el archivo, solo su URL: la descarga el servidor
                function fmDescargarDeUrl(url) {
                    if (!fmProductoActual || !url) return;
                    Alerta.toast('info', 'Descargando imagen…');

                    const fd = new FormData();
                    fd.append('action', 'descargar_foto');
                    fd.append('producto_id', fmProductoActual);
                    fd.append('url', url);

                    fetch(FOTO_API, {
                            method: 'POST',
                            body: fd
                        })
                        .then(r => r.json())
                        .then(r => {
                            if (!r.ok) {
                                Alerta.toast('error', 'Error: ' + r.msg);
                                return;
                            }
                            Alerta.toast('success', 'Foto guardada con éxito');
                            $('#modalFoto').modal('hide');
                            actualizarIconoFoto(fmProductoActual);
                        })
                        .catch(() => Alerta.toast('error', 'Error al descargar la imagen'));
                }

                // Ctrl+V dentro del modal: la imagen copiada en Google queda lista para "Usar esta foto"
                document.addEventListener('paste', e => {
                    if (!document.getElementById('modalFoto').classList.contains('show')) return;
                    const item = [...(e.clipboardData?.items || [])].find(i => i.type.startsWith('image/'));
                    if (!item) return;
                    e.preventDefault();
                    fmSwitchTab('subir', document.querySelector('.fm-tab[data-tab="subir"]'));
                    fmMostrarPreview(item.getAsFile());
                });

                if (fmPanelSubir) {
                    fmPanelSubir.addEventListener('dragover', e => {
                        e.preventDefault();
                        fmDropzone.classList.add('dragover');
                    });
                    fmPanelSubir.addEventListener('dragleave', () => fmDropzone.classList.remove('dragover'));
                    fmPanelSubir.addEventListener('drop', e => {
                        e.preventDefault();
                        fmDropzone.classList.remove('dragover');

                        // Archivo real (Explorador de Windows): se envía al soltarlo
                        if (e.dataTransfer.files.length) {
                            fmMostrarPreview(e.dataTransfer.files[0]);
                            fmUploadPhoto();
                            return;
                        }
                        // Imagen web (Google/Bing): solo llega la URL, no los bytes
                        const html = e.dataTransfer.getData('text/html') || '';
                        const url = (html.match(/<img[^>]+src=["']([^"']+)["']/i) || [])[1]
                            || (e.dataTransfer.getData('text/uri-list') || '').trim();
                        fmDescargarDeUrl(url);
                    });
                }
            </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>