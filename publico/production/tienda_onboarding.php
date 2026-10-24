<?php
require_once('includes/requires.php');
require_once('../../iseller_store/core/imagenes.php');
require_once('../../iseller_store/core/venezuela_geo.php');

if ($_SESSION['nivel'] != '1') {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
    exit;
}

$topnav = topnav();
$bss_id = $_SESSION["bss_id"];

$bancos_venezuela = [
    "0102" => "Banco de Venezuela",
    "0104" => "Venezolano de Credito",
    "0105" => "Mercantil",
    "0108" => "Provincial",
    "0114" => "Bancaribe",
    "0115" => "Exterior",
    "0128" => "Banco del Tesoro",
    "0134" => "Banesco",
    "0138" => "Banco Plaza",
    "0151" => "BFC Banco Fondo Comun",
    "0156" => "100% Banco",
    "0157" => "DelSur",
    "0163" => "Bancamiga",
    "0168" => "Bancrecer",
    "0169" => "Mi Banco",
    "0171" => "Banco Activo",
    "0174" => "Banplus",
    "0175" => "Bicentenario",
    "0191" => "BNC"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = slug_normalizar($_POST['slug'] ?? '');

    if ($slug === '' || slug_reservado($slug)) {
        header("Location: tienda_onboarding.php?error=slug");
        exit;
    }

    if (slug_duplicado($conexion, $slug, $bss_id)) {
        header("Location: tienda_onboarding.php?error=slug_existe");
        exit;
    }

    $delivery_tipo = in_array($_POST['delivery_tipo'] ?? 'gratis', ['gratis', 'pago'], true) ? $_POST['delivery_tipo'] : 'gratis';
    // El onboarding no edita el mínimo para envío gratis ni el costo que se cobra
    // por debajo de él: se conservan los ya guardados.
    $stmtDelPrev = $conexion->prepare("SELECT config_delivery FROM negocio WHERE id = ?");
    $stmtDelPrev->bind_param("i", $bss_id);
    $stmtDelPrev->execute();
    $delivery_prev = json_decode($stmtDelPrev->get_result()->fetch_assoc()['config_delivery'] ?? '{}', true);
    $stmtDelPrev->close();
    $min_prev = floatval($delivery_prev['minimo'] ?? 0);
    $delivery = json_encode([
        'tipo'  => $delivery_tipo,
        'costo' => $delivery_tipo === 'pago'
            ? floatval($_POST['delivery_costo'] ?? 0)
            : ($min_prev > 0 ? floatval($delivery_prev['costo'] ?? 0) : 0),
        'minimo' => $min_prev,
        'zonas' => ($_POST['delivery_zonas'] ?? 'municipio') === 'comunidad' ? 'comunidad' : 'municipio'
    ], JSON_UNESCAPED_UNICODE);

    $bancos = [];
    if (isset($_POST['enable_pm']) && $_POST['enable_pm'] == '1') {
        $bancos['pago_movil'] = [
            'banco'    => strip_tags(trim($_POST['pm_banco'] ?? '')),
            'telefono' => strip_tags(trim($_POST['pm_telefono'] ?? '')),
            'cedula'   => ($_POST['pm_cedula_prefix'] ?? 'V') . '-' . strip_tags(trim($_POST['pm_cedula'] ?? ''))
        ];
    }
    if (isset($_POST['enable_tb']) && $_POST['enable_tb'] == '1') {
        $bancos['transferencia'] = [
            'banco'   => strip_tags(trim($_POST['tb_banco'] ?? '')),
            'cuenta'  => strip_tags(trim($_POST['tb_cuenta'] ?? '')),
            'titular' => strip_tags(trim($_POST['tb_titular'] ?? '')),
            'cedula'  => ($_POST['tb_cedula_prefix'] ?? 'V') . '-' . strip_tags(trim($_POST['tb_cedula'] ?? ''))
        ];
    }
    $datos_bancarios = json_encode($bancos, JSON_UNESCAPED_UNICODE);

    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../../iseller_store/assets/img/tiendas/' . $bss_id . '/';
        $archivoLogo = imagen_guardar_webp($_FILES['logo'], $dir, 'logo', 400, 2 * 1024 * 1024);
        if ($archivoLogo) {
            $logo_url = "assets/img/tiendas/{$bss_id}/{$archivoLogo}";
        }
    }

    $categoria = trim($_POST['categoria'] ?? '');
    $nombre_visible = strip_tags(trim($_POST['nombre_visible'] ?? ''));
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $whatsapp = preg_match('/^\+\d{2} \d{3}-\d{7}$/', $whatsapp) ? $whatsapp : null;
    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? floatval($_POST['lng']) : null;
    $comunidad = strip_tags(trim($_POST['comunidad'] ?? ''));
    // Solo se aceptan combinaciones estado/municipio de la lista.
    $geo = venezuela_estados_municipios();
    $geo_estado = trim($_POST['geo_estado'] ?? '');
    $geo_municipio = trim($_POST['geo_municipio'] ?? '');
    if (!isset($geo[$geo_estado]) || !in_array($geo_municipio, $geo[$geo_estado], true)) {
        $geo_estado = $geo_municipio = null;
    }

    if ($logo_url) {
        $stmt = $conexion->prepare("UPDATE negocio SET tiene_tienda_virtual = 1, slug = ?, config_delivery = ?, datos_bancarios = ?, logo_url = ?, categoria = ?, lat = ?, lng = ?, comunidad = ?, geo_estado = ?, geo_municipio = ?, nombre_visible = ?, whatsapp = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssssi", $slug, $delivery, $datos_bancarios, $logo_url, $categoria, $lat, $lng, $comunidad, $geo_estado, $geo_municipio, $nombre_visible, $whatsapp, $bss_id);
    } else {
        $stmt = $conexion->prepare("UPDATE negocio SET tiene_tienda_virtual = 1, slug = ?, config_delivery = ?, datos_bancarios = ?, categoria = ?, lat = ?, lng = ?, comunidad = ?, geo_estado = ?, geo_municipio = ?, nombre_visible = ?, whatsapp = ? WHERE id = ?");
        $stmt->bind_param("sssssssssssi", $slug, $delivery, $datos_bancarios, $categoria, $lat, $lng, $comunidad, $geo_estado, $geo_municipio, $nombre_visible, $whatsapp, $bss_id);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: tienda_configuracion.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang='es'>

<head>
    <title>Crear Tienda Virtual</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css" crossorigin="">
    <link rel="stylesheet" href="theme.css">
    <style>
        :root {
            --dash-bg: #1a1e24;
            --dash-card: #181b22;
            --dash-border: #2e353e;
            --dash-mint: #2dd4a0;
            --dash-mint-dim: rgba(45, 212, 160, 0.12);
            --dash-text: #e8edf2;
            --dash-text-muted: #8892a0;
            --dash-danger: #ef5a6f;
            --dash-info: #5b9cf5;
        }

        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        /* ---- Section panels ---- */
        .ob-panel {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .ob-panel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 22px;
            border-bottom: 1px solid var(--dash-border);
        }

        .ob-panel-header .icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 9px;
            background: var(--dash-mint-dim);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .ob-panel-header .icon-wrap ion-icon {
            font-size: 17px;
            color: var(--dash-mint);
        }

        .ob-panel-header h6 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--dash-text);
        }

        .ob-panel-body {
            padding: 22px;
        }

        /* ---- Forms ---- */
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--dash-text-muted);
            margin-bottom: 7px;
        }

        .dash-input {
            display: block;
            width: 100%;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .dash-input:focus {
            outline: none;
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px var(--dash-mint-dim);
        }

        .dash-input::placeholder {
            color: var(--dash-text-muted);
            opacity: 0.7;
        }

        .input-prefix-group {
            display: flex;
        }

        .input-prefix {
            background: var(--dash-border);
            border: 1px solid var(--dash-border);
            border-right: none;
            color: var(--dash-text-muted);
            border-radius: 8px 0 0 8px;
            padding: 10px 12px;
            font-size: 12px;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }

        .input-prefix+.dash-input {
            border-radius: 0 8px 8px 0;
        }

        .prefix-select-wrap {
            display: flex;
        }

        .prefix-select {
            width: 64px;
            border-radius: 8px 0 0 8px;
            border-right: none;
            flex-shrink: 0;
        }

        .prefix-select+.dash-input {
            border-radius: 0 8px 8px 0;
        }

        .field-hint {
            font-size: 11px;
            color: var(--dash-text-muted);
            margin-top: 5px;
        }

        /* ---- File upload ---- */
        .file-drop {
            position: relative;
            border: 2px dashed var(--dash-border);
            border-radius: 10px;
            background: transparent;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            overflow: hidden;
        }

        .file-drop:hover {
            border-color: var(--dash-mint);
            background: rgba(45, 212, 160, 0.04);
        }

        .file-drop input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .file-drop-inner {
            text-align: center;
            pointer-events: none;
        }

        .file-drop-inner ion-icon {
            font-size: 26px;
            color: var(--dash-text-muted);
            display: block;
            margin-bottom: 6px;
        }

        .file-drop-inner span {
            font-size: 12px;
            color: var(--dash-text-muted);
        }

        /* ---- Payment method card ---- */
        .pay-card {
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
            transition: border-color 0.25s;
        }

        .pay-card.is-active {
            border-color: var(--dash-mint);
        }

        .pay-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 18px;
            cursor: pointer;
        }

        .pay-card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text);
        }

        .pay-card-title ion-icon {
            font-size: 18px;
            color: var(--dash-text-muted);
        }

        .pay-card.is-active .pay-card-title ion-icon {
            color: var(--dash-mint);
        }

        .pay-card-body {
            display: none;
            padding: 0 18px 18px;
            border-top: 1px solid var(--dash-border);
            padding-top: 16px;
        }

        /* ---- Toggle switch ---- */
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

        .slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: var(--dash-border);
            border-radius: 24px;
            transition: .3s;
        }

        .slider:before {
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

        input:checked+.slider {
            background: var(--dash-mint);
        }

        input:checked+.slider:before {
            transform: translateX(22px);
        }

        .error-msg {
            display: none;
            font-size: 12px;
            color: var(--dash-danger);
            margin-top: 8px;
        }

        .error-msg ion-icon {
            vertical-align: middle;
            margin-right: 4px;
        }

        /* ---- Page title ---- */
        .ob-page-title {
            font-size: 17px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0 0 4px;
        }

        .ob-page-subtitle {
            font-size: 13px;
            color: var(--dash-text-muted);
            margin-bottom: 24px;
        }

        /* ---- Costo delivery reveal ---- */
        #delivery-costo-wrap {
            display: none;
        }

        /* ---- Community search ---- */
        .search-container {
            position: relative;
        }

        .community-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 200px;
            overflow-y: auto;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-top: none;
            border-radius: 0 0 8px 8px;
            z-index: 1000;
            display: none;
        }

        .community-item {
            padding: 8px 14px;
            cursor: pointer;
            font-size: 13px;
            color: var(--dash-text);
            border-bottom: 1px solid var(--dash-border);
        }

        .community-item:last-child {
            border-bottom: none;
        }

        .community-item:hover {
            background: var(--dash-mint-dim);
        }

        /* ---- Wizard ---- */
        .wizard-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 28px;
            position: relative;
        }

        .wizard-steps::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 40px;
            right: 40px;
            height: 2px;
            background: var(--dash-border);
        }

        .wizard-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
            flex: 1;
        }

        .wizard-step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--dash-border);
            color: var(--dash-text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 6px;
            transition: all 0.3s;
        }

        .wizard-step.active .wizard-step-circle {
            background: var(--dash-mint);
            color: #fff;
            box-shadow: 0 0 12px rgba(45, 212, 160, 0.4);
        }

        .wizard-step.done .wizard-step-circle {
            background: var(--dash-mint);
            color: #fff;
        }

        .wizard-step-label {
            font-size: 10px;
            color: var(--dash-text-muted);
            text-align: center;
            font-weight: 500;
        }

        .wizard-step.active .wizard-step-label {
            color: var(--dash-mint);
        }

        .wizard-step.done .wizard-step-label {
            color: var(--dash-mint);
        }

        .wizard-panel {
            display: none;
        }

        .wizard-panel.active {
            display: block;
        }

        .wizard-nav {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            gap: 12px;
        }

        .wizard-nav > .btn {
            flex: 1;
        }
    </style>
</head>

<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class='right_col' role='main'>
                <div class='row justify-content-center'>
                    <div class='col-lg-7 col-md-9'>

                        <p class="ob-page-title">Crear Tienda Virtual</p>
                        <p class="ob-page-subtitle">Configura tu canal de ventas online y empieza a recibir pedidos web.</p>

                        <?php if (in_array($_GET['error'] ?? '', ['slug', 'slug_existe'], true)): ?>
                            <div style="background:rgba(239,90,111,0.1);border:1px solid rgba(239,90,111,0.3);color:var(--dash-danger);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;">
                                <ion-icon name="alert-circle-outline" style="font-size:20px;"></ion-icon>
                                <?= $_GET['error'] === 'slug_existe' ? 'Esa URL ya está en uso por otra tienda. Elige otra.' : 'Esa URL no está disponible. Elige otra.' ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data" id="onboardingForm">

                            <!-- WIZARD STEPS INDICATOR -->
                            <div class="wizard-steps">
                                <div class="wizard-step active" data-step="1">
                                    <div class="wizard-step-circle">1</div>
                                    <span class="wizard-step-label">Identidad</span>
                                </div>
                                <div class="wizard-step" data-step="2">
                                    <div class="wizard-step-circle">2</div>
                                    <span class="wizard-step-label">Ubicación</span>
                                </div>
                                <div class="wizard-step" data-step="3">
                                    <div class="wizard-step-circle">3</div>
                                    <span class="wizard-step-label">Envíos</span>
                                </div>
                                <div class="wizard-step" data-step="4">
                                    <div class="wizard-step-circle">4</div>
                                    <span class="wizard-step-label">Pagos</span>
                                </div>
                            </div>

                            <!-- STEP 1: Identidad -->
                            <div class="wizard-panel active" id="step-1">
                                <div class="ob-panel">
                                    <div class="ob-panel-header">
                                        <div class="icon-wrap"><ion-icon name="storefront-outline"></ion-icon></div>
                                        <h6>Identidad de la Tienda</h6>
                                    </div>
                                    <div class="ob-panel-body">

                                        <div class="mb-4">
                                            <label class="field-label">URL de la tienda</label>
                                            <div class="input-prefix-group">
                                                <span class="input-prefix">iseller-tiendas.com/</span>
                                                <input type="text" name="slug" class="dash-input" required placeholder="mi-negocio">
                                            </div>
                                            <p class="field-hint">Solo minusculas, numeros y guiones.</p>
                                        </div>

                                        <div class="mb-4">
                                            <label class="field-label">Nombre visible en la tienda</label>
                                            <input type="text" name="nombre_visible" class="dash-input" placeholder="Ej: Mi Negocio Store">
                                            <p class="field-hint">Nombre que verán tus clientes en la tienda.</p>
                                        </div>

                                        <div class="mb-4">
                                            <label class="field-label">WhatsApp de contacto</label>
                                            <input type="tel" name="whatsapp" id="whatsapp-input" class="dash-input" maxlength="16" placeholder="Ej: +58 412-1234567">
                                            <p class="field-hint">Formato: +00 000-0000000 (código de país, espacio y número).</p>
                                        </div>

                                        <div class="mb-4">
                                            <label class="field-label">Categoría de la Tienda</label>
                                            <select name="categoria" class="dash-input" required>
                                                <option value="">Seleccione una categoría...</option>
                                                <option value="Restaurantes y Comida Rápida">Restaurantes y Comida Rápida</option>
                                                <option value="Panadería y Pastelería">Panadería y Pastelería</option>
                                                <option value="Carnicerías y Víveres">Carnicerías y Víveres</option>
                                                <option value="Frutas y Verduras">Frutas y Verduras</option>
                                                <option value="Supermercado y Víveres">Supermercado y Víveres</option>
                                                <option value="Bebidas y Licores">Bebidas y Licores</option>
                                                <option value="Ropa y Accesorios">Ropa y Accesorios</option>
                                                <option value="Calzado">Calzado</option>
                                                <option value="Joyería y Relojería">Joyería y Relojería</option>
                                                <option value="Salud y Belleza">Salud y Belleza</option>
                                                <option value="Farmacia">Farmacia</option>
                                                <option value="Tecnología y Electrónica">Tecnología y Electrónica</option>
                                                <option value="Electrodomésticos">Electrodomésticos</option>
                                                <option value="Hogar y Decoración">Hogar y Decoración</option>
                                                <option value="Muebles">Muebles</option>
                                                <option value="Ferretería y Construcción">Ferretería y Construcción</option>
                                                <option value="Repuestos para Vehículos">Repuestos para Vehículos</option>
                                                <option value="Juguetes y Niños">Juguetes y Niños</option>
                                                <option value="Papelería y Librería">Papelería y Librería</option>
                                                <option value="Deportes y Fitness">Deportes y Fitness</option>
                                                <option value="Mascotas">Mascotas</option>
                                                <option value="Flores y Regalos">Flores y Regalos</option>
                                                <option value="Distribuidora y Mayorista">Distribuidora y Mayorista</option>
                                                <option value="Servicios">Servicios</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="field-label">Logotipo <span style="color:var(--dash-text-muted);text-transform:none;font-weight:400;">(PNG · JPG · WEBP, max 2 MB)</span></label>
                                            <div class="file-drop" id="file-drop">
                                                <input type="file" name="logo" id="logo-input" accept="image/jpeg,image/png,image/webp">
                                                <div class="file-drop-inner" id="drop-inner">
                                                    <ion-icon name="cloud-upload-outline"></ion-icon>
                                                    <span>Arrastra o haz clic para subir</span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="wizard-nav">
                                    <button type="button" class="btn btn-success" onclick="wizardNext(1)">Siguiente <ion-icon name="arrow-forward-outline"></ion-icon></button>
                                </div>
                            </div>

                            <!-- STEP 2: Ubicacion -->
                            <div class="wizard-panel" id="step-2">
                                <div class="ob-panel">
                                    <div class="ob-panel-header">
                                        <div class="icon-wrap"><ion-icon name="location-outline"></ion-icon></div>
                                        <h6>Ubicación de la Tienda</h6>
                                    </div>
                                    <div class="ob-panel-body">
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="field-label">Estado</label>
                                                <select name="geo_estado" id="geo-estado" class="dash-input" required>
                                                    <option value="">Seleccione un estado...</option>
                                                    <?php foreach (venezuela_estados_municipios() as $estado => $municipios): ?>
                                                        <option value="<?= htmlspecialchars($estado) ?>"><?= htmlspecialchars($estado) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="field-label">Municipio</label>
                                                <select name="geo_municipio" id="geo-municipio" class="dash-input" required>
                                                    <option value="">Seleccione un municipio...</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="field-label">Comunidad / Barrio</label>
                                            <div class="search-container">
                                                <input type="text" name="comunidad" id="comunidad" class="dash-input" placeholder="Escribe tu comunidad..." autocomplete="off" required>
                                                <div id="community-results" class="community-results"></div>
                                            </div>
                                            <p class="field-hint">Escribe el nombre de tu comunidad para centrar el mapa.</p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="field-label">Ubica tu tienda en el mapa</label>
                                            <div id="map-picker" style="height:300px;border-radius:10px;border:1px solid var(--dash-border);z-index:1;"></div>
                                            <p class="field-hint">Haz clic en el mapa para marcar la ubicación exacta de tu tienda.</p>
                                        </div>
                                        <input type="hidden" name="lat" id="lat-input">
                                        <input type="hidden" name="lng" id="lng-input">
                                    </div>
                                </div>
                                <div class="wizard-nav">
                                    <button type="button" class="btn btn-secondary" onclick="wizardPrev(2)"><ion-icon name="arrow-back-outline"></ion-icon> Atrás</button>
                                    <button type="button" class="btn btn-success" onclick="wizardNext(2)">Siguiente <ion-icon name="arrow-forward-outline"></ion-icon></button>
                                </div>
                            </div>

                            <!-- STEP 3: Logistica -->
                            <div class="wizard-panel" id="step-3">
                                <div class="ob-panel">
                                    <div class="ob-panel-header">
                                        <div class="icon-wrap"><ion-icon name="car-outline"></ion-icon></div>
                                        <h6>Logistica y Envios</h6>
                                    </div>
                                    <div class="ob-panel-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="field-label">Tipo de delivery</label>
                                                <select name="delivery_tipo" id="delivery-tipo" class="dash-input">
                                                    <option value="gratis">Gratis</option>
                                                    <option value="pago">Pagado</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3" id="delivery-costo-wrap">
                                                <label class="field-label">Costo (USD)</label>
                                                <input type="number" step="0.01" min="0" name="delivery_costo" id="delivery-costo" class="dash-input" placeholder="0.00">
                                            </div>
                                            <div class="col-md-6 mb-0">
                                                <label class="field-label">Alcance de entrega</label>
                                                <select name="delivery_zonas" class="dash-input">
                                                    <option value="comunidad">Mi comunidad</option>
                                                    <option value="municipio" selected>Todo el municipio</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="wizard-nav">
                                    <button type="button" class="btn btn-secondary" onclick="wizardPrev(3)"><ion-icon name="arrow-back-outline"></ion-icon> Atrás</button>
                                    <button type="button" class="btn btn-success" onclick="wizardNext(3)">Siguiente <ion-icon name="arrow-forward-outline"></ion-icon></button>
                                </div>
                            </div>

                            <!-- STEP 4: Metodos de pago -->
                            <div class="wizard-panel" id="step-4">
                                <div class="ob-panel">
                                    <div class="ob-panel-header">
                                        <div class="icon-wrap"><ion-icon name="card-outline"></ion-icon></div>
                                        <h6>Metodos de Pago</h6>
                                    </div>
                                    <div class="ob-panel-body">
                                        <p class="field-hint mb-3" style="margin-top:0;">Activa al menos un metodo de pago para que tus clientes puedan finalizar el pedido.</p>

                                        <!-- Pago Movil -->
                                        <div class="pay-card" id="card-pm">
                                            <div class="pay-card-header">
                                                <div class="pay-card-title">
                                                    <ion-icon name="phone-portrait-outline"></ion-icon>
                                                    Pago Movil
                                                </div>
                                                <label class="switch" onclick="event.stopPropagation()">
                                                    <input type="checkbox" name="enable_pm" value="1" id="switch-pm">
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                            <div class="pay-card-body" id="body-pm">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="field-label">Banco</label>
                                                        <select name="pm_banco" class="dash-input pm-req">
                                                            <option value="">Seleccione el banco...</option>
                                                            <?php foreach ($bancos_venezuela as $code => $name): ?>
                                                                <option value="<?= htmlspecialchars($code . ' - ' . $name) ?>"><?= htmlspecialchars($code . ' - ' . $name) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Telefono</label>
                                                        <input type="text" name="pm_telefono" id="pm-telefono" class="dash-input pm-req" maxlength="12" placeholder="0414-1234567">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Cedula / RIF</label>
                                                        <div class="prefix-select-wrap">
                                                            <select name="pm_cedula_prefix" class="dash-input prefix-select">
                                                                <option>V</option>
                                                                <option>E</option>
                                                                <option>J</option>
                                                                <option>P</option>
                                                                <option>G</option>
                                                            </select>
                                                            <input type="text" name="pm_cedula" class="dash-input pm-req" placeholder="12345678">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Transferencia -->
                                        <div class="pay-card" id="card-tb">
                                            <div class="pay-card-header">
                                                <div class="pay-card-title">
                                                    <ion-icon name="business-outline"></ion-icon>
                                                    Transferencia Bancaria
                                                </div>
                                                <label class="switch" onclick="event.stopPropagation()">
                                                    <input type="checkbox" name="enable_tb" value="1" id="switch-tb">
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                            <div class="pay-card-body" id="body-tb">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="field-label">Banco</label>
                                                        <select name="tb_banco" class="dash-input tb-req">
                                                            <option value="">Seleccione el banco...</option>
                                                            <?php foreach ($bancos_venezuela as $code => $name): ?>
                                                                <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label class="field-label">Numero de cuenta (20 digitos)</label>
                                                        <input type="text" name="tb_cuenta" class="dash-input tb-req" maxlength="20" placeholder="01020000000000000000">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Titular</label>
                                                        <input type="text" name="tb_titular" class="dash-input tb-req" placeholder="Juan Perez">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Cedula / RIF</label>
                                                        <div class="prefix-select-wrap">
                                                            <select name="tb_cedula_prefix" class="dash-input prefix-select">
                                                                <option>V</option>
                                                                <option>E</option>
                                                                <option>J</option>
                                                                <option>P</option>
                                                                <option>G</option>
                                                            </select>
                                                            <input type="text" name="tb_cedula" class="dash-input tb-req" placeholder="12345678">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="error-msg" id="pay-error">
                                            <ion-icon name="alert-circle-outline"></ion-icon>
                                            Debes activar al menos un metodo de pago.
                                        </p>
                                    </div>
                                </div>
                                <div class="wizard-nav">
                                    <button type="button" class="btn btn-secondary" onclick="wizardPrev(4)"><ion-icon name="arrow-back-outline"></ion-icon> Atrás</button>
                                    <button type="submit" class="btn btn-success btn-block">
                                        <ion-icon name="rocket-outline"></ion-icon> Activar Tienda Virtual
                                    </button>
                                </div>
                                <div style="text-align:center;margin-top:16px;padding:14px;background:var(--dash-mint-dim);border:1px solid rgba(45,212,160,0.25);border-radius:10px;">
                                    <p style="margin:0;font-size:12px;color:var(--dash-mint);font-weight:600;">
                                        <ion-icon name="gift-outline" style="vertical-align:middle;margin-right:4px;"></ion-icon>
                                        Prueba gratuita de 2 meses al activar tu tienda en línea.
                                    </p>
                                    <p style="margin:4px 0 0;font-size:11px;color:var(--dash-text-muted);">
                                        Esta promoción es independiente de la suscripción del sistema.
                                    </p>
                                </div>
                            </div>

                        </form>

                    </div>
                </div>
            </div>

            <?php if (file_exists('includes/footer.php')) require_once('includes/footer.php'); ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // --- Wizard Navigation ---
            let currentStep = 1;
            const totalSteps = 4;

            window.wizardNext = function(from) {
                // Validar campos del paso actual (requeridos + formato/pattern)
                const panel = document.getElementById('step-' + from);
                let valid = true;
                panel.querySelectorAll('input, select').forEach(input => {
                    if (!input.checkValidity()) {
                        input.style.borderColor = 'var(--dash-danger)';
                        valid = false;
                    } else {
                        input.style.borderColor = '';
                    }
                });
                if (!valid) return;

                if (from >= totalSteps) return;
                document.getElementById('step-' + from).classList.remove('active');
                document.getElementById('step-' + (from + 1)).classList.add('active');
                updateWizardIndicator(from + 1);
                currentStep = from + 1;
                if (window.mapPicker) setTimeout(() => window.mapPicker.invalidateSize(), 100);
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            };

            window.wizardPrev = function(from) {
                if (from <= 1) return;
                document.getElementById('step-' + from).classList.remove('active');
                document.getElementById('step-' + (from - 1)).classList.add('active');
                updateWizardIndicator(from - 1);
                currentStep = from - 1;
                if (window.mapPicker) setTimeout(() => window.mapPicker.invalidateSize(), 100);
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            };

            function updateWizardIndicator(step) {
                document.querySelectorAll('.wizard-step').forEach(el => {
                    const s = parseInt(el.dataset.step);
                    el.classList.remove('active', 'done');
                    if (s === step) el.classList.add('active');
                    else if (s < step) el.classList.add('done');
                });
            }

            // --- Logo drop UX ---
            document.getElementById('logo-input').addEventListener('change', function() {
                const inner = document.getElementById('drop-inner');
                if (this.files && this.files[0]) {
                    inner.innerHTML = `<ion-icon name="checkmark-circle-outline" style="color:var(--dash-mint)"></ion-icon><span>${this.files[0].name}</span>`;
                }
            });

            // --- WhatsApp: máscara + validación de formato (+00 000-0000000) ---
            const WA_RE = /^\+\d{2} \d{3}-\d{7}$/;
            const waInput = document.getElementById('whatsapp-input');
            waInput.addEventListener('input', function() {
                const d = this.value.replace(/\D/g, '').slice(0, 12);
                let out = '';
                if (d.length) out = '+' + d.slice(0, 2);
                if (d.length > 2) out += ' ' + d.slice(2, 5);
                if (d.length > 5) out += '-' + d.slice(5);
                this.value = out;
                this.setCustomValidity(out === '' || WA_RE.test(out) ? '' : 'Formato requerido: +00 000-0000000');
            });

            // --- Slug reservado o ya usado por otra tienda ---
            const SLUGS_RESERVADOS = <?= json_encode(slugs_reservados()) ?>;
            const slugInput = document.querySelector('input[name="slug"]');
            // Misma normalización que slug_normalizar() en PHP.
            const normalizarSlug = v => v.trim().toLowerCase().replace(/[^a-z0-9-]+/g, '-').replace(/^-+|-+$/g, '');
            let slugTomado = false;
            function validarSlug() {
                const v = normalizarSlug(slugInput.value);
                slugInput.setCustomValidity(slugTomado ? 'Esa URL ya está en uso por otra tienda, elige otra.' : (SLUGS_RESERVADOS.includes(v) ? 'Esa URL no está disponible, elige otra.' : ''));
            }
            let slugTimer;
            slugInput.addEventListener('input', function() {
                slugTomado = false;
                validarSlug();
                clearTimeout(slugTimer);
                const v = normalizarSlug(slugInput.value);
                if (v === '' || SLUGS_RESERVADOS.includes(v)) return;
                slugTimer = setTimeout(function() {
                    fetch('api/tienda_slug_check.php?slug=' + encodeURIComponent(v))
                        .then(r => r.json())
                        .then(d => {
                            if (normalizarSlug(slugInput.value) === v) {
                                slugTomado = !d.ok;
                                validarSlug();
                            }
                        })
                        .catch(() => {});
                }, 400);
            });
            validarSlug();

            // --- Pago móvil teléfono: máscara (0000-0000000) ---
            const PM_TEL_RE = /^\d{4}-\d{7}$/;
            const pmTel = document.getElementById('pm-telefono');
            pmTel.addEventListener('input', function() {
                const d = this.value.replace(/\D/g, '').slice(0, 11);
                let out = '';
                if (d.length) out = d.slice(0, 4);
                if (d.length > 4) out += '-' + d.slice(4);
                this.value = out;
                this.setCustomValidity(out === '' || PM_TEL_RE.test(out) ? '' : 'Formato requerido: 0000-0000000');
            });

            // --- Estado / Municipio ---
            const GEO_VENEZUELA = <?= json_encode(venezuela_estados_municipios(), JSON_UNESCAPED_UNICODE) ?>;
            const geoEstado = document.getElementById('geo-estado');
            const geoMunicipio = document.getElementById('geo-municipio');
            function cargarMunicipios() {
                geoMunicipio.innerHTML = '<option value="">Seleccione un municipio...</option>';
                (GEO_VENEZUELA[geoEstado.value] || []).forEach(function(m) {
                    const opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    geoMunicipio.appendChild(opt);
                });
            }
            geoEstado.addEventListener('change', cargarMunicipios);
            cargarMunicipios();

            // --- Delivery cost reveal ---
            const deliveryTipo = document.getElementById('delivery-tipo');
            const costoWrap = document.getElementById('delivery-costo-wrap');
            const costoInput = document.getElementById('delivery-costo');

            deliveryTipo.addEventListener('change', function() {
                const paid = this.value === 'pago';
                costoWrap.style.display = paid ? 'block' : 'none';
                costoInput.required = paid;
            });

            // --- Payment toggles ---
            function togglePayCard(switchEl, bodyEl, cardEl, reqClass) {
                const inputs = document.querySelectorAll('.' + reqClass);
                if (switchEl.checked) {
                    bodyEl.style.display = 'block';
                    cardEl.classList.add('is-active');
                    inputs.forEach(i => i.required = true);
                } else {
                    bodyEl.style.display = 'none';
                    cardEl.classList.remove('is-active');
                    inputs.forEach(i => {
                        i.required = false;
                        i.value = '';
                    });
                }
            }

            const switchPm = document.getElementById('switch-pm');
            const switchTb = document.getElementById('switch-tb');

            switchPm.addEventListener('change', () => togglePayCard(switchPm, document.getElementById('body-pm'), document.getElementById('card-pm'), 'pm-req'));
            switchTb.addEventListener('change', () => togglePayCard(switchTb, document.getElementById('body-tb'), document.getElementById('card-tb'), 'tb-req'));

            // --- Form validation on submit ---
            document.getElementById('onboardingForm').addEventListener('submit', function(e) {
                const err = document.getElementById('pay-error');
                if (!switchPm.checked && !switchTb.checked) {
                    e.preventDefault();
                    err.style.display = 'block';
                } else {
                    err.style.display = 'none';
                }
            });

        });
    </script>

    <script type="module" src="../vendors/ionicons/ionicons.esm.js"></script>
    <script nomodule src="../vendors/ionicons/ionicons.js"></script>
    <script src="../vendors/leaflet/leaflet.js" crossorigin=""></script>
    <script>
        (function() {
            const defaultLat = 10.4806;
            const defaultLng = -66.9036;
            const latInput = document.getElementById('lat-input');
            const lngInput = document.getElementById('lng-input');
            const comunidadInput = document.getElementById('comunidad');
            const resultsDiv = document.getElementById('community-results');

            window.mapPicker = L.map('map-picker').setView([defaultLat, defaultLng], 12);
            L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
            }).addTo(window.mapPicker);

            let marker = null;
            let geojsonLayer = null;
            let allCommunitiesNames = [];

            function setMarker(lat, lng) {
                if (marker) window.mapPicker.removeLayer(marker);
                marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(window.mapPicker);
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    latInput.value = pos.lat.toFixed(7);
                    lngInput.value = pos.lng.toFixed(7);
                });
            }

            window.mapPicker.on('click', function(e) {
                setMarker(e.latlng.lat, e.latlng.lng);
            });

            // Cargar GeoJSON de comunidades
            fetch('../../iseller_store/assets/js/comunidades.geojson')
                .then(r => r.json())
                .then(geoData => {
                    allCommunitiesNames = geoData.features.map(f => f.properties.NAME).sort();

                    geojsonLayer = L.geoJSON(geoData, {
                        style: {
                            opacity: 0,
                            fillOpacity: 0,
                            weight: 0
                        }
                    }).addTo(window.mapPicker);
                })
                .catch(() => {
                    // Si no existe el GeoJSON, solo funciona el mapa básico
                });

            // Palabras genéricas a ignorar en la búsqueda (tanto en lo que escribe el usuario como en los nombres de la lista)
            const STOP_WORDS = /\b(urb|urbanizacion|urbanización|barrio|bario|comunidad|sector|parroquia|av|avenida|calle|cll|blvd|boulevard)\b/gi;

            // Normaliza un texto para búsqueda: sin acentos, minúsculas, sin palabras genéricas, sin espacios dobles
            function normalizeForSearch(text) {
                return text
                    .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // quitar acentos
                    .toLowerCase()
                    .replace(STOP_WORDS, ' ')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            // Centra el mapa en una comunidad por nombre exacto (NAME del feature)
            function zoomToCommunity(name) {
                if (!geojsonLayer) return;
                geojsonLayer.eachLayer(layer => {
                    if (layer.feature.properties.NAME.toUpperCase() === name.toUpperCase()) {
                        window.mapPicker.fitBounds(layer.getBounds(), {
                            padding: [20, 20]
                        });
                        layer.setStyle({
                            color: '#2dd4a0',
                            weight: 3,
                            opacity: 1,
                            fillOpacity: 0,
                            dashArray: '8, 6'
                        });
                        setTimeout(() => layer.setStyle({
                            opacity: 0,
                            fillOpacity: 0,
                            weight: 0
                        }), 2000);
                    }
                });
            }

            // Búsqueda flexible de comunidades
            comunidadInput.addEventListener('input', function(e) {
                const raw = e.target.value;
                const normalizedQuery = normalizeForSearch(raw);

                if (normalizedQuery.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                // Filtrar: comparar query normalizada contra nombre normalizado
                const matches = allCommunitiesNames.filter(c => {
                    const normalizedName = normalizeForSearch(c);
                    // Cada palabra significativa del query debe aparecer en el nombre normalizado
                    const words = normalizedQuery.split(' ').filter(w => w.length > 1);
                    return words.every(w => normalizedName.includes(w));
                });

                if (matches.length > 0) {
                    resultsDiv.innerHTML = matches.map(m => `<div class="community-item">${m}</div>`).join('');
                    resultsDiv.style.display = 'block';

                    // Zoom automático si hay una coincidencia exacta con el texto normalizado
                    const exactMatch = matches.find(m => normalizeForSearch(m) === normalizedQuery);
                    if (exactMatch) {
                        zoomToCommunity(exactMatch);
                    }
                } else {
                    resultsDiv.style.display = 'none';
                }
            });

            // Zoom al presionar Enter o al perder el foco (blur) — busca la mejor coincidencia
            function tryZoomOnCommit() {
                const raw = comunidadInput.value;
                const normalizedQuery = normalizeForSearch(raw);
                if (normalizedQuery.length < 2) return;

                // Buscar coincidencia exacta primero, luego única
                const matches = allCommunitiesNames.filter(c => {
                    const normalizedName = normalizeForSearch(c);
                    const words = normalizedQuery.split(' ').filter(w => w.length > 1);
                    return words.every(w => normalizedName.includes(w));
                });

                const exactMatch = matches.find(m => normalizeForSearch(m) === normalizedQuery);
                const best = exactMatch || (matches.length === 1 ? matches[0] : null);

                if (best) {
                    comunidadInput.value = best;
                    resultsDiv.style.display = 'none';
                    zoomToCommunity(best);
                }
            }

            comunidadInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    tryZoomOnCommit();
                }
            });
            comunidadInput.addEventListener('blur', function() {
                // Pequeño delay para permitir que el click en un item del dropdown se procese antes
                setTimeout(tryZoomOnCommit, 200);
            });

            // Selección de comunidad desde el dropdown
            resultsDiv.addEventListener('click', function(e) {
                const item = e.target.closest('.community-item');
                if (!item) return;
                const name = item.textContent;
                comunidadInput.value = name;
                resultsDiv.style.display = 'none';
                zoomToCommunity(name);
            });

            // Cerrar dropdown al hacer clic fuera
            document.addEventListener('click', function(e) {
                if (!comunidadInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });

            // Geolocalización del navegador
            const PUERTO_AYACUCHO = [5.6667, -67.6167];
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    // Validar que esté en Amazonas, Venezuela (~0.6 a 6.5 lat, ~-67.9 a -64.0 lng)
                    if (lat >= 0.6 && lat <= 6.5 && lng >= -67.9 && lng <= -64.0) {
                        window.mapPicker.setView([lat, lng], 15);
                        setMarker(lat, lng);
                    } else {
                        window.mapPicker.setView(PUERTO_AYACUCHO, 12);
                    }
                }, function() {
                    window.mapPicker.setView(PUERTO_AYACUCHO, 12);
                });
            } else {
                window.mapPicker.setView(PUERTO_AYACUCHO, 12);
            }
        })();
    </script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>

</html>