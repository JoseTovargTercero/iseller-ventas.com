<?php
require_once('includes/requires.php');

// Reglas para sugerir categoría al escribir el nombre (solo lectura, sin BD)
$reglasCatJs = [];
$archivoReglasCat = __DIR__ . '/../../configurar/_categorizar_reglas.php';
if (is_file($archivoReglasCat)) {
    require_once($archivoReglasCat);
    if (defined('CAT_REGLAS')) $reglasCatJs = CAT_REGLAS;
}


if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {


    $topnav = topnav();



?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Agregar Producto</title>
        <?php require_once('includes/headers.php'); ?>
        <link href='../vendors/select2/dist/css/select2.min.css' rel='stylesheet'>
    </head>

    <style>
        /* Modal Calcular Precio compacto sin scroll */
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

        .tabs section {
            display: none;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .tabs section.active {
            display: block;
            opacity: 1;
        }

        .x_title {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 15px;
            background: transparent;
            border-bottom: 1px solid var(--dash-border, #2e353e);
            border-radius: 4px 4px 0 0;
        }

        .x_title h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: var(--dash-text, #e8edf2);
        }

        .x_content {
            padding-top: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-group label,
        .col-form-label {
            margin-bottom: 4px;
            font-weight: 600;
            font-size: 13px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--dash-text, #e8edf2);
            padding-bottom: 8px;
            border-bottom: 2px solid #28a745;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title ion-icon {
            color: #28a745;
            font-size: 18px;
        }

        /* Sucursales: tarjetas seleccionables */
        .suc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .suc-card {
            position: relative;
        }

        .suc-card input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .suc-card label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            width: 100%;
            padding: 12px 14px;
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text-muted, #8892a0);
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 10px;
            cursor: pointer;
            transition: all .18s ease;
        }

        .suc-card label:hover {
            color: var(--dash-text, #e8edf2);
            border-color: rgba(45, 212, 160, 0.5);
        }

        .suc-card .suc-box {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
            border: 2px solid #3a424d;
            border-radius: 6px;
            transition: all .18s ease;
        }

        .suc-card .suc-box ion-icon {
            font-size: 14px;
            color: #0b1310;
            opacity: 0;
            transition: opacity .15s ease;
        }

        .suc-card input:checked+label {
            color: var(--dash-text, #e8edf2);
            border-color: #2dd4a0;
            background: rgba(45, 212, 160, 0.1);
            box-shadow: 0 0 0 1px rgba(45, 212, 160, 0.3);
        }

        .suc-card input:checked+label .suc-box {
            background: #2dd4a0;
            border-color: #2dd4a0;
        }

        .suc-card input:checked+label .suc-box ion-icon {
            opacity: 1;
        }

        .suc-card input.is-invalid+label {
            border-color: #ef5a6f;
        }

        /* Precios de venta: tarjetas de resumen */
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
            color: #2dd4a0;
            background: rgba(45, 212, 160, 0.15);
        }

        .price-card.highlight .pc-value {
            color: #2dd4a0;
        }

        .price-card.muted .pc-value {
            font-size: 16px;
            color: var(--dash-text-muted, #8892a0);
        }

        /* Botones de herramientas del producto */
        .np-toolbar {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .np-toolbar .btn {
            width: 100%;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .np-toolbar .btn ion-icon {
            font-size: 15px;
        }

        /* Modal para copiar datos de otro producto */
        #modalCopiarProducto {
            z-index: 10050;
        }

        .modal-backdrop {
            z-index: 10040;
        }

        #modalCopiarProducto .modal-content {
            background: var(--dash-card, #181b22);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 14px;
            color: var(--dash-text, #e8edf2);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
        }

        #modalCopiarProducto .modal-header {
            border-bottom: 1px solid var(--dash-border, #2e353e);
            padding: 16px 20px;
        }

        #modalCopiarProducto .modal-title {
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        #modalCopiarProducto .modal-title ion-icon {
            color: #2dd4a0;
            font-size: 18px;
        }

        #modalCopiarProducto .modal-body {
            padding: 16px 20px 20px;
        }

        #modalCopiarProducto .close {
            color: #8892a0;
            opacity: 1;
            text-shadow: none;
            font-size: 22px;
        }

        #modalCopiarProducto .close:hover {
            color: #e8edf2;
        }

        .np-copy-search {
            position: relative;
        }

        .np-copy-search ion-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #8892a0;
            font-size: 16px;
            pointer-events: none;
        }

        .np-copy-search input {
            width: 100%;
            background: var(--dash-bg, #1a1e24);
            border: 1px solid var(--dash-border, #2e353e);
            color: var(--dash-text, #e8edf2);
            border-radius: 9px;
            padding: 10px 12px 10px 38px;
            font-size: 13px;
        }

        .np-copy-search input:focus {
            outline: none;
            border-color: #2dd4a0;
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12);
        }

        .np-copy-results {
            display: flex;
            flex-direction: column;
            gap: 8px;
            max-height: 380px;
            overflow-y: auto;
            margin-top: 14px;
            padding-right: 2px;
        }

        .np-copy-item {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            text-align: left;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--dash-border, #2e353e);
            border-radius: 10px;
            padding: 10px 12px;
            color: var(--dash-text, #e8edf2);
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }

        .np-copy-item:hover {
            border-color: rgba(45, 212, 160, 0.5);
            background: rgba(45, 212, 160, 0.05);
        }

        .np-copy-item-main {
            flex: 1;
            min-width: 0;
        }

        .np-copy-item-name {
            font-size: 13px;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .np-copy-item-meta {
            font-size: 11px;
            color: #8892a0;
            margin-top: 2px;
        }

        .np-copy-item-price {
            flex-shrink: 0;
            font-size: 13px;
            font-weight: 700;
            color: #2dd4a0;
            text-align: right;
        }

        .np-copy-empty {
            text-align: center;
            color: #8892a0;
            font-size: 13px;
            padding: 28px 12px;
        }

        @media (max-width: 575px) {
            .np-toolbar {
                margin-top: 8px;
            }

            .np-toolbar .btn {
                white-space: normal;
            }
        }

        /* Loader de registro: bloquea la pantalla para evitar dobles envíos */
        .np-loader {
            position: fixed;
            inset: 0;
            z-index: 30000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: rgba(8, 13, 11, 0.78);
            backdrop-filter: blur(2px);
        }

        .np-loader.show {
            display: flex;
        }

        .np-loader-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            max-width: 320px;
            padding: 26px 30px;
            text-align: center;
            background: var(--dash-card, #181b22);
            border: 1px solid rgba(45, 212, 160, 0.35);
            border-radius: 14px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            color: var(--dash-text, #e8edf2);
        }

        .np-loader-box strong {
            font-size: 15px;
        }

        .np-loader-box span:last-child {
            font-size: 12px;
            color: var(--dash-text-muted, #8892a0);
        }

        .np-spinner {
            width: 34px;
            height: 34px;
            border: 3px solid rgba(45, 212, 160, 0.2);
            border-top-color: #2dd4a0;
            border-radius: 50%;
            animation: np-spin 0.8s linear infinite;
        }

        @keyframes np-spin {
            to { transform: rotate(360deg); }
        }
    </style>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>
                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->
                <!-- page content -->
                <div class='right_col' role='main'>
                    <div class=''>



                        <div class='clearfix'></div>



                        <script>
                            $(document).ready(function() {
                                ['precioMonedaOrigen', 'cantidad', 'porcentaje'].forEach(element => {
                                    const el = document.getElementById(element);
                                    if (!el) return;
                                    // Solo dígitos y un punto decimal (la coma se normaliza a punto)
                                    el.addEventListener('input', () => {
                                        const partes = el.value.replace(/,/g, '.').replace(/[^0-9.]/g, '').split('.');
                                        el.value = partes.length > 2 ? partes.shift() + '.' + partes.join('') : partes.join('.');
                                    });
                                    el.addEventListener('keyup', () => calcularPrecioVenta())
                                });

                                const origenEl = document.getElementById('origenProducto');
                                if (origenEl) origenEl.addEventListener('change', () => calcularPrecioVenta())
                            })






                            const base_url = '../../configurar/';



                            let precioVentaSeq = 0;

                            function mostrarPreciosVacios() {
                                document.getElementById('resultado').value = `$ 0`;
                                document.getElementById('resultado2').value = `$ 0`;
                                document.getElementById('resultado3').value = `${formatNumber(0)} COP`;
                                document.getElementById('resultado4').value = `${formatNumber(0)} BS`;
                            }

                            // La vista previa se calcula en el servidor para usar exactamente
                            // las tasas mostradas del negocio (cambios_mostrados del bss_id en
                            // sesión) y las mismas reglas que el listado de productos.
                            function calcularPrecioVenta() {
                                const precio = parseFloat(document.getElementById('precioMonedaOrigen').value) || 0;
                                const origen = document.getElementById('origenProducto').value || 'v';
                                const cantidad = parseInt(document.getElementById('cantidad').value) || 1;
                                const porcentaje = parseFloat(document.getElementById('porcentaje').value) || 0;
                                const precioCompra = (precio == 0 ? 0 : precio / cantidad);
                                const seq = ++precioVentaSeq;

                                // El precio unitario en dólares es el que se guarda en el producto
                                document.getElementById('precio').value = precio;

                                $.ajax({
                                        url: base_url + 'consultar_precio.php',
                                        type: 'POST',
                                        data: {
                                            precio: precio,
                                            origen: origen,
                                            cantidad: cantidad,
                                            porcentaje: porcentaje
                                        },
                                        dataType: 'text'
                                    })
                                    .done(function(result) {
                                        // Ignorar respuestas de peticiones anteriores
                                        if (seq !== precioVentaSeq) return;

                                        try {
                                            const data = JSON.parse(result);
                                            if (data.status === 'ok') {
                                                document.getElementById('resultado').value = `$ ${precioCompra.toFixed(2)}`;
                                                document.getElementById('resultado2').value = `$ ${data.data.dolar}`;
                                                document.getElementById('resultado3').value = `${formatNumber(data.data.peso)} COP`;
                                                document.getElementById('resultado4').value = `${formatNumber(recortarADosDecimales(data.data.bs))} BS`;
                                            } else {
                                                mostrarPreciosVacios();
                                            }
                                        } catch (e) {
                                            console.error('Error al procesar la respuesta:', e);
                                        }

                                    })
                                    .fail(function(xhr, status, error) {
                                        if (seq !== precioVentaSeq) return;
                                        console.error('Error al calcular la vista previa de precios:', error);
                                    });
                            }
















                            // Formatear número con separadores de miles
                            function formatNumber(valor) {
                                return valor.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
                            }
                        </script>
                        <form id='form-data' action='../../configurar/agregarProducto.php' method='post' class='form-horizontal form-label-left   fadeInUp animated' enctype='multipart/form-data'>
                            <div class='row'>
                                <div class='col-lg-6 '>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2><ion-icon name="create-outline" style="font-size:16px;vertical-align:middle;margin-right:4px;"></ion-icon> Datos del Producto</h2>
                                        </div>
                                        <div class='x_content'>
                                            <div class="tabs">
                                                <section>

                                                    <div class="mb-2">
                                                        <label class='col-form-label' for='nombre'>Nombre del producto</label>
                                                        <div class="row">

                                                            <div class='col-md-6 col-sm-6'>
                                                                <input type='text' id='nombre' required placeholder="Nombre del producto" name='nombre' class='form-control '>
                                                            </div>

                                                            <div class='col-md-6 col-sm-6'>
                                                                <div class="np-toolbar">
                                                                    <button type="button" class="btn btn-info" id="open-modal">
                                                                        <ion-icon name="calculator-outline"></ion-icon> Calcular precio
                                                                    </button>
                                                                    <button type="button" class="btn btn-outline-info" id="btn-copiar-producto">
                                                                        <ion-icon name="copy-outline"></ion-icon> Copiar de otro producto
                                                                    </button>
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </div>

                                                    <input type='text' id='precio' name='precio' hidden class='form-control '>

                                                    <div class="row mb-2">
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='precioMonedaOrigen'>Precio de Compra</label>
                                                            <input type='text' required placeholder="Precio del bulto" id='precioMonedaOrigen' name='precioMonedaOrigen' class='form-control '>
                                                        </div>
                                                        <div class='col-lg-6 form-group'>

                                                            <label class='col-form-label' for='origenProducto'>Origen del producto</label>
                                                            <select class="form-control" required name="origenProducto" id="origenProducto">
                                                                <option value="">Seleccione</option>
                                                                <option value="v">Venezolano</option>
                                                                <option value="c">Colombiano</option>
                                                            </select>
                                                        </div>
                                                    </div>


                                                    <div class="row  mb-2">

                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='cantidad'>Unidades por bulto</label>
                                                            <input type='text' required id='cantidad' placeholder="Unidades que contiene el bulto" name='cantidad' class='form-control '>
                                                        </div>

                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='porcentaje'>Porcentaje</label>
                                                            <input required type='text' id='porcentaje' name='porcentaje' placeholder="Porcentaje incrementado" class='form-control '>

                                                        </div>

                                                    </div>


                                                    <div id='tabla_resultado_codigo'>
                                                    </div>

                                                    <div class="row  mb-2">
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='proveedor'>Proveedor</label>
                                                            <input required type='text' id='proveedor' name='proveedor' placeholder="Nombre del proveedor" class='form-control '>
                                                        </div>
                                                        <div class='col-lg-6 form-group'>
                                                            <label class='col-form-label' for='c_barras'>Código de barras</label>
                                                            <input required type='text' id='c_barras' name='c_barras' placeholder="Código de barras del producto" class='form-control '>
                                                        </div>
                                                    </div>

                                                    <div class="row mb-2">
                                                        <div class='col-lg-12 form-group'>
                                                            <label class='col-form-label'>Categoría del producto</label>
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
                                                                            echo '<option value="' . (int)$cat['id'] . '" data-slug="' . htmlspecialchars($cat['slug']) . '">' . htmlspecialchars($cat['nombre']) . '</option>';
                                                                        }
                                                                        if ($grupoActual !== '') echo '</optgroup>';
                                                                    }
                                                                    $stmtCat->close();
                                                                }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>


                                                    <?php
                                                    $stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ? ORDER BY principal DESC");
                                                    $stmt->bind_param('i', $bss_id);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    $numSucursales = $result->num_rows;
                                                    ?>
                                                    <div class="mb-3" id="bloque-sucursales" <?php if ($numSucursales <= 1) echo 'style="display:none"'; ?>>
                                                        <div class="section-title">
                                                            <ion-icon name="storefront-outline"></ion-icon>
                                                            Sucursales donde se va a vender el producto
                                                        </div>
                                                        <?php
                                                        if ($numSucursales > 1) {
                                                            echo '<div class="suc-grid">';
                                                            while ($row = $result->fetch_assoc()) {
                                                                $checked = $row['principal'] == 1 ? 'checked' : '';

                                                                echo <<<HTML
                                                        <div class="suc-card">
                                                            <input {$checked} name="sucursales[]" data-nombre="{$row['nombre']}" data-id="{$row['id']}" type="checkbox" value="{$row['id']}" id="suc-{$row['id']}">
                                                            <label for="suc-{$row['id']}">
                                                                <span class="suc-box"><ion-icon name="checkmark-outline"></ion-icon></span>
                                                                <span class="suc-name">{$row['nombre']}</span>
                                                            </label>
                                                        </div>
                                                        HTML;
                                                            }
                                                            echo '</div>';
                                                        } elseif ($numSucursales == 1) {
                                                            // Una sola sucursal: checkbox oculto y premarcado (mantiene el flujo de stock)
                                                            $row = $result->fetch_assoc();
                                                            echo '<input type="checkbox" checked name="sucursales[]" data-id="' . $row['id'] . '" value="' . $row['id'] . '" id="suc-' . $row['id'] . '" style="display:none">';
                                                        }
                                                        $stmt->close();

                                                        ?>
                                                    </div>
                                                </section>

                                                <!-- Foto del producto -->
                                                <section>
                                                    <div class="mb-3">
                                                        <div class="section-title">
                                                            <ion-icon name="camera-outline"></ion-icon>
                                                            Foto del producto
                                                        </div>
                                                        <p style="font-size:12px;color:#8892a0;margin-bottom:12px;">Sube una imagen para que tu producto se vea mejor en la tienda.</p>
                                                        <div id="np-photo-dropzone" style="border:2px dashed #2e353e;border-radius:12px;padding:2rem;text-align:center;cursor:pointer;transition:all .2s;" onclick="document.getElementById('np-photo-input').click()">
                                                            <input type="file" id="np-photo-input" name="foto_producto" accept="image/jpeg,image/png,image/webp" hidden>
                                                            <div id="np-photo-placeholder">
                                                                <ion-icon name="camera-outline" style="font-size:2.5rem;color:#2e353e;display:block;margin-bottom:8px;"></ion-icon>
                                                                <p style="margin:0;font-size:13px;color:#8892a0;">Haz clic para subir una foto</p>
                                                                <p style="margin:4px 0 0;font-size:11px;color:#8892a0;">JPEG, PNG o WebP — Máx. 5 MB</p>
                                                            </div>
                                                            <div id="np-photo-preview" style="display:none;">
                                                                <img id="np-preview-img" src="" alt="Preview" style="max-width:100%;max-height:200px;border-radius:10px;border:1px solid #2e353e;margin-bottom:10px;">
                                                                <br>
                                                                <button type="button" onclick="event.stopPropagation();npClearPhoto()" style="background:rgba(239,90,111,0.12);border:1px solid rgba(239,90,111,0.25);color:#ef5a6f;border-radius:8px;padding:6px 14px;font-size:12px;font-weight:600;cursor:pointer;">Eliminar foto</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </section>

                                                <section>
                                                    <div id="listado_stock"></div>
                                                </section>
                                            </div>
                                        </div>

                                        <div class="w-100 d-flex justify-content-between">
                                            <button type="button" onclick="sectionsNav('anterior')" class="btn btn-info actualizar">Anterior</button>
                                            <button type="button" onclick="sectionsNav('siguiente')" class="btn btn-success actualizar">Siguiente</button>
                                        </div>
                                    </div>
                                </div>

                                <div class='col-lg-6 '>
                                    <div class='x_panel'>
                                        <div class='x_title'>
                                            <h2><ion-icon name="cash-outline" style="font-size:16px;vertical-align:middle;margin-right:4px;"></ion-icon> Precios de venta (Unidad)</h2>
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
                    </div>
                </div>
                </form>
            </div>
        </div>
        </div>
        </div>
        <!-- Copiar datos de otro producto -->
        <div class="modal fade" id="modalCopiarProducto" tabindex="-1" role="dialog" aria-labelledby="modalCopiarProductoTitulo" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCopiarProductoTitulo">
                            <ion-icon name="copy-outline"></ion-icon> Copiar de otro producto
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="np-copy-search">
                            <ion-icon name="search-outline"></ion-icon>
                            <input type="search" id="np-copy-search" placeholder="Buscar por nombre o código de barras..." autocomplete="off">
                        </div>
                        <div id="np-copy-results" class="np-copy-results">
                            <div class="np-copy-empty">Escribe para buscar entre tus productos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php require('../assets/templates/modal.html'); ?>

        <div id="np-loader" class="np-loader" aria-hidden="true">
            <div class="np-loader-box" role="status" aria-live="polite">
                <span class="np-spinner"></span>
                <strong>Registrando producto…</strong>
                <span>No cierres esta ventana.</span>
            </div>
        </div>

        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <script src='../vendors/select2/dist/js/select2.full.min.js'></script>
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <script src='../build/js/custom.js'></script>
        <script src="../build/js/modal.js"></script>
        <script src="js/nombre_pagina.js"></script>

        <script>
            // Tasas de cambio mostradas del negocio (bss_id en sesión)
            const tasas = {
                'cop': <?php echo json_encode((float) $pesoDolar); ?>,
                'bs': <?php echo json_encode((float) $bsDolar); ?>
            };

            let currentSectionIndex = 0;
            let sections = [];
            let npRegistrandoProducto = false;

            function npMostrarLoader(mostrar) {
                const loader = document.getElementById('np-loader');
                if (!loader) return;
                loader.classList.toggle('show', !!mostrar);
                loader.setAttribute('aria-hidden', mostrar ? 'false' : 'true');
            }

            function npFinalizarRegistro() {
                npRegistrandoProducto = false;
                npMostrarLoader(false);
                const btnGuardar = document.querySelector("button.btn-success.actualizar");
                if (btnGuardar) btnGuardar.disabled = false;
            }

            document.addEventListener('DOMContentLoaded', () => {
                // Recolectar todas las secciones automáticamente
                sections = Array.from(document.querySelectorAll('.tabs section'));

                if (sections.length === 0) return;

                // Mostrar la primera sección
                sections[currentSectionIndex].classList.add('active');
            });

            // Gestor de ventas Wizard
            function sectionsNav(direction) {
                if (sections.length === 0) return;

                const validado = validar_form()
                console.log(validado)
                if (!validado) {
                    Alerta.mostrar('error', 'Faltan datos')
                    return
                };





                const btnSiguiente = document.querySelector("button.btn-success.actualizar");

                if (direction === 'siguiente' && currentSectionIndex === sections.length - 1) {
                    // Si ya estamos en la última sección, enviar el formulario manualmente
                    const form = btnSiguiente.closest('form');
                    if (form) {
                        form.requestSubmit(); // Usa validación nativa y dispara el evento submit
                    }
                    return;

                } else {
                    // seccion personalizada
                    obtenerSucursalesSeleccionadas()
                }

                // Ocultar sección actual
                sections[currentSectionIndex].classList.remove('active');

                // Cambiar índice
                if (direction === 'siguiente' && currentSectionIndex < sections.length - 1) {
                    currentSectionIndex++;
                } else if (direction === 'anterior' && currentSectionIndex > 0) {
                    currentSectionIndex--;
                }

                // Mostrar nueva sección
                setTimeout(() => {
                    sections[currentSectionIndex].classList.add('active');
                }, 50);

                // Cambiar texto del botón, pero mantener type="button"
                if (currentSectionIndex === sections.length - 1) {
                    btnSiguiente.textContent = 'Guardar';
                } else {
                    btnSiguiente.textContent = 'Siguiente';
                }
            }

            // Validar que todos los campos esten seleccionados
            function validar_form() {
                const section = document.querySelector('.tabs section'); // solo la primera sección dentro de .tabs
                const requiredInputs = section.querySelectorAll('input[required], select[required]');
                let valido = true;

                // Validar inputs/selects requeridos
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('is-invalid');
                        valido = false;
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                // Validar que al menos un checkbox de sucursales esté marcado
                const checks = section.querySelectorAll('input[name="sucursales[]"]');
                const algunoMarcado = Array.from(checks).some(chk => chk.checked);

                if (!algunoMarcado) {
                    // Puedes marcar visualmente los checkboxes si quieres
                    checks.forEach(chk => chk.classList.add('is-invalid'));
                    valido = false;
                    alert("Debes seleccionar al menos una sucursal.");
                } else {
                    checks.forEach(chk => chk.classList.remove('is-invalid'));
                }

                return valido;
            }


            // Generar los inputs con el stock
            function obtenerSucursalesSeleccionadas() {
                const checks = document.querySelectorAll('input[name="sucursales[]"]:checked');
                const seleccionadas = [];
                const listado_stock = document.getElementById('listado_stock')
                let html = '';

                checks.forEach(check => {
                    const id = check.getAttribute('data-id');
                    const label = document.querySelector(`label[for="${check.id}"]`);
                    const nombre = label ? label.textContent.trim() : 'Desconocido';

                    html += `  <div class=' mb-2 form-group'>
                                <label class='col-form-label' for='stock_${id}'>STOCK DE <b>${nombre}</b></label>
                                <input type='number' id='stock_${id}' name='stock_${id}' placeholder="Ingrese el stock disponible en ${nombre}" class='form-control '>
                            </div>`

                    seleccionadas.push({
                        id,
                        nombre
                    });
                });

                listado_stock.innerHTML = html;

                return seleccionadas;
            }

            // Obtiene los valores del stock
            function obtenerStockPorSucursal() {
                const checks = document.querySelectorAll('input[name="sucursales[]"]:checked');
                const resultado = [];

                checks.forEach(check => {
                    const id = check.getAttribute('data-id');
                    const label = document.querySelector(`label[for="${check.id}"]`);
                    const nombre = label ? label.textContent.trim() : 'Desconocido';

                    const inputStock = document.getElementById(`stock_${id}`).value;
                    const stock = inputStock ? parseInt(inputStock) : 0;

                    resultado.push([id, stock]);
                });

                return resultado;
            }

            // este codigo me regresa: Uncaught TypeError: form.querySelectorAll is not a function

            // Enviar el producto al back
            document.getElementById('form-data').addEventListener('submit', function(e) {
                e.preventDefault();
                if (npRegistrandoProducto) return;

                npRegistrandoProducto = true;

                const formElement = this; // <- El formulario real
                const formData = new FormData(formElement); // <- El objeto FormData


                // Usar tu función para obtener sucursales seleccionadas con su stock
                let stockPorSucursal;
                try {
                    stockPorSucursal = obtenerStockPorSucursal();
                } catch (err) {
                    npFinalizarRegistro();
                    Alerta.mostrar('error', 'Completa el stock de las sucursales antes de guardar.');
                    return;
                }

                // Guardar en FormData como JSON
                formData.append('sucursales_stock', JSON.stringify(stockPorSucursal));

                // DEBUG
                const debug = false;
                if (debug) {
                    console.log("Formulario enviado con los siguientes datos:");
                    for (let [key, value] of formData.entries()) {
                        console.log(`${key}: ${value}`);
                    }

                    if (!confirm("¿Deseas enviar el formulario con estos datos?")) {
                        Alerta.toast('info', 'Envío cancelado por el usuario (modo debug activo).');
                        npFinalizarRegistro();
                        return;
                    }
                }

                npMostrarLoader(true);
                const btnGuardar = document.querySelector("button.btn-success.actualizar");
                if (btnGuardar) btnGuardar.disabled = true;

                // Envío por fetch
                fetch('../../configurar/agregarProducto.php', {
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

                        if (json.tipo === 'success') {
                            // Volver al paso 1 (datos)
                            sections.forEach(s => s.classList.remove('active'));
                            currentSectionIndex = 0;
                            sections[0].classList.add('active');
                            const btnSiguiente = document.querySelector("button.btn-success.actualizar");
                            if (btnSiguiente) btnSiguiente.textContent = 'Siguiente';

                            // Limpiar formulario y foto
                            this.reset();
                            npClearPhoto();
                            // Limpiar stock generado
                            document.getElementById('listado_stock').innerHTML = '';
                            // Limpiar Select2 de categoría
                            $('#categoria_select').val(null).trigger('change');
                        }
                        Alerta.toast(json.tipo, json.mensaje);

                    })
                    .catch(function(err) {
                        console.error('Error al registrar el producto:', err);
                        Alerta.mostrar('error', 'No se pudo registrar el producto. Intenta de nuevo.');
                    })
                    .finally(function() {
                        npFinalizarRegistro();
                    });

            });

            // asi se generan los inputs:check



            // MODIFICAR EL MODAL
            $(document).ready(function() {
                // Inicializar Select2 en el select de categorías
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
                            if (matches.length > 0) {
                                return {
                                    text: data.text,
                                    children: matches
                                };
                            }
                            return null;
                        }
                        if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) return data;
                        return null;
                    }
                });

                // Guardar categorías nuevas vía AJAX
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


                // Seccion del modal para calcular el precio
                // Cambiar la tasa automáticamente al elegir moneda
                document.getElementById('moneda').addEventListener('change', function() {
                    document.getElementById('tasa_proveedor').value = tasas[this.value] || '';
                    calcularPrecio();
                })
                // Calcular en tiempo real
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

                    // Ocultar resultado si los datos no son válidos
                    if (isNaN(tasaValor) || isNaN(precioValor) || tasaValor <= 0 || precioValor <= 0) {
                        previewCard.style.display = 'none';
                        n_precio = 0;
                        return;
                    }

                    // Cálculo del precio en USD
                    let precioFinal = precioValor / tasaValor;
                    if (impuesto === 'iva') {
                        precioFinal += precioFinal * 0.16;
                    }

                    const precioFinalRecortado = recortarADosDecimales(precioFinal);
                    n_precio = precioFinalRecortado;

                    // Mostrar resultado
                    resultadoEl.textContent = `$${formatNumber(precioFinalRecortado)} USD`;
                    previewCard.style.display = 'block';
                }

                document.getElementById('btn-actualizar').addEventListener('click', function() {
                    if (n_precio != 0) {
                        const inputPrecio = document.getElementById('precioMonedaOrigen');
                        inputPrecio.value = n_precio;
                        inputPrecio.dispatchEvent(new Event('keyup'));
                        if (typeof calcularPrecioVenta === 'function') {
                            calcularPrecioVenta();
                        }
                        document.getElementById('precio_proveedor').value = '';
                        const previewCard = document.getElementById('vista_previa_precio');
                        previewCard.style.display = 'none';
                        n_precio = 0;
                        modalContainer.classList.remove("active");
                    }
                })

            })

            // Photo upload preview
            document.getElementById('np-photo-input').addEventListener('change', function() {
                if (!this.files[0]) return;
                const file = this.files[0];
                if (file.size > 5 * 1024 * 1024) {
                    alert('La foto no debe superar 5 MB');
                    this.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = e => {
                    document.getElementById('np-preview-img').src = e.target.result;
                    document.getElementById('np-photo-placeholder').style.display = 'none';
                    document.getElementById('np-photo-preview').style.display = '';
                };
                reader.readAsDataURL(file);
            });

            function npClearPhoto() {
                document.getElementById('np-photo-input').value = '';
                document.getElementById('np-photo-placeholder').style.display = '';
                document.getElementById('np-photo-preview').style.display = 'none';
            }

            const npDropzone = document.getElementById('np-photo-dropzone');
            npDropzone.addEventListener('dragover', e => {
                e.preventDefault();
                npDropzone.style.borderColor = '#2dd4a0';
            });
            npDropzone.addEventListener('dragleave', () => {
                npDropzone.style.borderColor = '#2e353e';
            });
            npDropzone.addEventListener('drop', e => {
                e.preventDefault();
                npDropzone.style.borderColor = '#2e353e';
                if (e.dataTransfer.files.length) {
                    document.getElementById('np-photo-input').files = e.dataTransfer.files;
                    document.getElementById('np-photo-input').dispatchEvent(new Event('change'));
                }
            });
        </script>

        <script>
            // Sugerencia de categoría al cambiar el nombre del producto.
            // Usa las mismas reglas que configurar/_categorizar_reglas.php (sin llamadas al servidor).
            const CAT_REGLAS = <?= json_encode($reglasCatJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

            const catReglasCompiladas = CAT_REGLAS.map(function(regla) {
                try {
                    return [regla[0], new RegExp('\\b(?:' + regla[1] + ')\\b')];
                } catch (e) {
                    return null;
                }
            }).filter(Boolean);

            function catNormalizar(texto) {
                return texto.toUpperCase()
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^A-Z0-9]+/g, ' ')
                    .trim();
            }

            function catSugerir(nombre) {
                const texto = ' ' + catNormalizar(nombre) + ' ';
                for (let i = 0; i < catReglasCompiladas.length; i++) {
                    if (catReglasCompiladas[i][1].test(texto)) return catReglasCompiladas[i][0];
                }
                return null;
            }

            $(function() {
                const $cat = $('#categoria_select');

                // Si la categoría la eligió el usuario, no la pisamos al cambiar el nombre.
                $cat.on('select2:select select2:unselect', function() {
                    $(this).data('auto', false);
                });

                $('#nombre').on('change', function() {
                    const nombre = $(this).val().trim();
                    if (nombre === '') return;

                    // Solo reemplazamos una categoría puesta automáticamente.
                    if ($cat.val() && !$cat.data('auto')) return;

                    const slug = catSugerir(nombre);
                    if (!slug) return;

                    const $opcion = $cat.find('option').filter(function() {
                        return this.dataset.slug === slug;
                    }).first();

                    if ($opcion.length) {
                        $cat.data('auto', true);
                        $cat.val($opcion.val()).trigger('change');
                    }
                });
            });
        </script>

        <script>
            // Copiar datos de otro producto del mismo negocio.
            const NP_COPY_API = '../../configurar/buscar_productos_copiar.php';
            let npCopyResultados = [];
            let npCopyTimer = null;

            function npCopyEsc(texto) {
                return String(texto == null ? '' : texto).replace(/[&<>"']/g, function(c) {
                    return {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#39;'
                    } [c];
                });
            }

            function npCopyAbrir() {
                const input = document.getElementById('np-copy-search');
                input.value = '';
                npCopyResultados = [];
                document.getElementById('np-copy-results').innerHTML =
                    '<div class="np-copy-empty">Cargando productos...</div>';

                $('#modalCopiarProducto').modal('show');
                npCopyBuscar('');

                setTimeout(function() {
                    input.focus();
                }, 250);
            }

            function npCopyBuscar(query) {
                fetch(NP_COPY_API + '?q=' + encodeURIComponent(query), {
                        cache: 'no-store'
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (!data.ok) throw new Error(data.msg || 'Error al buscar productos');
                        npCopyResultados = data.data || [];
                        npCopyRender();
                    })
                    .catch(function(err) {
                        npCopyResultados = [];
                        document.getElementById('np-copy-results').innerHTML =
                            '<div class="np-copy-empty">' + npCopyEsc(err.message) + '</div>';
                    });
            }

            function npCopyRender() {
                const cont = document.getElementById('np-copy-results');

                if (!npCopyResultados.length) {
                    cont.innerHTML = '<div class="np-copy-empty">No se encontraron productos.</div>';
                    return;
                }

                cont.innerHTML = npCopyResultados.map(function(p, index) {
                    const meta = [];
                    if (p.codigo) meta.push('COD: ' + p.codigo);
                    if (p.proveedor) meta.push(p.proveedor);
                    if (p.categoria_nombre) meta.push(p.categoria_nombre);
                    meta.push('Und/bulto: ' + p.cantidad_unidades);

                    return '<button type="button" class="np-copy-item" data-index="' + index + '">' +
                        '<div class="np-copy-item-main">' +
                        '<div class="np-copy-item-name">' + npCopyEsc(p.nombre) + '</div>' +
                        '<div class="np-copy-item-meta">' + npCopyEsc(meta.join(' · ')) + '</div>' +
                        '</div>' +
                        '<div class="np-copy-item-price">' +
                        (p.precio_compra !== '' ? '$' + npCopyEsc(p.precio_compra) : '—') +
                        '</div>' +
                        '</button>';
                }).join('');
            }

            function npCopyAplicar(p) {
                if (!p) return;

                document.getElementById('precioMonedaOrigen').value = p.precio_compra || '';
                document.getElementById('origenProducto').value = p.origen || '';
                document.getElementById('cantidad').value = p.cantidad_unidades || '';
                document.getElementById('porcentaje').value = p.porcentaje || '';
                document.getElementById('proveedor').value = p.proveedor || '';

                const $cat = $('#categoria_select');
                if (p.categoria_id) {
                    const catId = String(p.categoria_id);
                    if ($cat.find('option[value="' + catId + '"]').length === 0) {
                        $cat.append(new Option(p.categoria_nombre || 'Categoría', catId, true, true));
                    }
                    $cat.val(catId).trigger('change');
                } else {
                    $cat.val(null).trigger('change');
                }

                ['precioMonedaOrigen', 'cantidad', 'porcentaje'].forEach(function(id) {
                    const el = document.getElementById(id);
                    if (!el) return;
                    el.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    el.dispatchEvent(new Event('keyup', {
                        bubbles: true
                    }));
                });
                document.getElementById('origenProducto').dispatchEvent(new Event('change', {
                    bubbles: true
                }));

                if (typeof calcularPrecioVenta === 'function') {
                    calcularPrecioVenta();
                }

                $('#modalCopiarProducto').modal('hide');

                if (typeof Alerta !== 'undefined' && typeof Alerta.toast === 'function') {
                    Alerta.toast('success', 'Datos copiados del producto');
                }
            }

            document.getElementById('btn-copiar-producto').addEventListener('click', npCopyAbrir);

            document.getElementById('np-copy-search').addEventListener('input', function() {
                clearTimeout(npCopyTimer);
                const q = this.value;
                npCopyTimer = setTimeout(function() {
                    npCopyBuscar(q);
                }, 250);
            });

            document.getElementById('np-copy-results').addEventListener('click', function(e) {
                const item = e.target.closest('.np-copy-item');
                if (!item) return;
                npCopyAplicar(npCopyResultados[Number(item.dataset.index)]);
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