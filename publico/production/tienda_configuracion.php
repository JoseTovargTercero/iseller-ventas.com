<?php
require_once('includes/requires.php');
require_once('../../iseller_store/core/chat_compra.php');
require_once('../../iseller_store/core/eslogan.php');

// El formulario guarda texto utf8mb4 (eslogan, nombre): con la conexión en utf8
// (3 bytes) los emojis terminarían guardados como "????".
$conexion->set_charset('utf8mb4');

// Solo el dueño (nivel 1) puede ver/editar la configuración y los datos de cobro.
if ((string)($_SESSION['nivel'] ?? '') !== '1') {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();
$bss_id = $_SESSION["bss_id"];

// Activar / desactivar la tienda. Todo el front de la tienda comprueba
// tiene_tienda_virtual = 1, así que basta con apagar el flag: los clientes
// dejan de verla. No borra nada (productos, pedidos y configuración siguen
// ahí) y se puede reactivar desde esta misma pantalla.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['accion'] ?? '', ['desactivar_tienda', 'reactivar_tienda'], true)) {
    $nuevo_estado = $_POST['accion'] === 'reactivar_tienda' ? 1 : 0;
    $stmtOff = $conexion->prepare("UPDATE negocio SET tiene_tienda_virtual = ? WHERE id = ?");
    $stmtOff->bind_param("ii", $nuevo_estado, $bss_id);
    $stmtOff->execute();
    $stmtOff->close();

    header("Location: tienda_configuracion.php?success=1");
    exit;
}

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

$categorias_list = [
    "Restaurantes y Comida Rápida",
    "Ropa y Accesorios",
    "Supermercado y Víveres",
    "Salud y Belleza",
    "Tecnología y Electrónica",
    "Hogar y Decoración",
    "Juguetes y Niños",
    "Mascotas",
    "Servicios",
    "Otro"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $slug = slug_normalizar($_POST['slug'] ?? '');

    if ($slug === '' || slug_reservado($slug)) {
        header("Location: tienda_configuracion.php?error=slug");
        exit;
    }

    if (slug_duplicado($conexion, $slug, $bss_id)) {
        header("Location: tienda_configuracion.php?error=slug_existe");
        exit;
    }

    $delivery_tipo = in_array($_POST['delivery_tipo'] ?? 'gratis', ['gratis', 'pago'], true) ? $_POST['delivery_tipo'] : 'gratis';
    // Gratis desde un mínimo: por debajo se cobra el costo (por eso se guarda).
    // Pagado: se cobra siempre y el mínimo no aplica.
    $delivery_minimo = $delivery_tipo === 'gratis' ? floatval($_POST['delivery_minimo'] ?? 0) : 0;
    $delivery_costo = ($delivery_tipo === 'pago' || $delivery_minimo > 0) ? floatval($_POST['delivery_costo'] ?? 0) : 0;
    $delivery_zonas = ($_POST['delivery_zonas'] ?? 'municipio') === 'comunidad' ? 'comunidad' : 'municipio';
    $delivery = json_encode([
        'tipo' => $delivery_tipo,
        'costo' => $delivery_costo,
        'minimo' => $delivery_minimo,
        'zonas' => $delivery_zonas
    ], JSON_UNESCAPED_UNICODE);

    $bancos = [];
    if (isset($_POST['enable_pm']) && $_POST['enable_pm'] == '1') {
        $bancos['pago_movil'] = [
            'banco' => strip_tags(trim($_POST['pm_banco'] ?? '')),
            'telefono' => strip_tags(trim($_POST['pm_telefono'] ?? '')),
            'cedula' => ($_POST['pm_cedula_prefix'] ?? 'V') . '-' . strip_tags(trim($_POST['pm_cedula'] ?? ''))
        ];
    }

    if (isset($_POST['enable_tb']) && $_POST['enable_tb'] == '1') {
        $bancos['transferencia'] = [
            'banco' => strip_tags(trim($_POST['tb_banco'] ?? '')),
            'cuenta' => strip_tags(trim($_POST['tb_cuenta'] ?? '')),
            'titular' => strip_tags(trim($_POST['tb_titular'] ?? '')),
            'cedula' => ($_POST['tb_cedula_prefix'] ?? 'V') . '-' . strip_tags(trim($_POST['tb_cedula'] ?? ''))
        ];
    }

    $datos_bancarios = json_encode($bancos, JSON_UNESCAPED_UNICODE);
    $categoria = trim($_POST['categoria'] ?? '');
    $nombre_visible = strip_tags(trim($_POST['nombre_visible'] ?? ''));
    $eslogan = mb_substr(strip_tags(trim($_POST['eslogan'] ?? '')), 0, 160);
    $notificacion_email = trim($_POST['notificacion_email'] ?? '');

    // El select no lista usuarios desactivados. Si llega vacío, conservar el
    // valor actual en vez de borrarlo; para borrarlo hay una opción explícita (0).
    $stmtNotifActual = $conexion->prepare("SELECT notificacion_email FROM negocio WHERE id = ? LIMIT 1");
    $stmtNotifActual->bind_param("i", $bss_id);
    $stmtNotifActual->execute();
    $notifActual = (string)($stmtNotifActual->get_result()->fetch_assoc()['notificacion_email'] ?? '');
    $stmtNotifActual->close();

    if ($notificacion_email === '0') {
        $notificacion_email = '';
    } elseif ($notificacion_email === '') {
        $notificacion_email = $notifActual;
    }

    // Solo puede notificarse un usuario del propio negocio (evita fuga entre tiendas).
    if ($notificacion_email !== '') {
        $esValorActual = ($notificacion_email === $notifActual);
        if (!ctype_digit($notificacion_email)) {
            // Valor legado (email u otro): solo se conserva si no cambió.
            if (!$esValorActual) {
                header("Location: tienda_configuracion.php?error=notif");
                exit;
            }
        } else {
            $uidNotif = (int)$notificacion_email;
            $stmtNotif = $conexion->prepare("SELECT id FROM usuarios WHERE id = ? AND bss_id = ? LIMIT 1");
            $stmtNotif->bind_param("ii", $uidNotif, $bss_id);
            $stmtNotif->execute();
            $notifOk = $stmtNotif->get_result()->num_rows > 0;
            $stmtNotif->close();
            if (!$notifOk) {
                // Si era el valor ya guardado (usuario borrado o de otra tienda),
                // se limpia en vez de bloquear el guardado de todas las pestañas.
                if ($esValorActual) {
                    $notificacion_email = '';
                } else {
                    header("Location: tienda_configuracion.php?error=notif");
                    exit;
                }
            } else {
                $notificacion_email = (string)$uidNotif;
            }
        }
    }
    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? floatval($_POST['lat']) : null;
    $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? floatval($_POST['lng']) : null;
    $comunidad = strip_tags(trim($_POST['comunidad'] ?? ''));

    $horarios = null;
    if (isset($_POST['horarios'])) {
        $horarios_raw = $_POST['horarios'];
        $horarios = [];
        foreach (['tienda', 'delivery'] as $tipo) {
            $d = $horarios_raw[$tipo] ?? [];
            $dias = $d['dias'] ?? [];
            if (!empty($dias) && $dias !== ['']) {
                $horarios[$tipo] = [
                    'dias' => $dias,
                    'tipo' => $d['tipo'] ?? 'corrido',
                    'h1' => $d['h1'] ?? '',
                    'h2' => $d['h2'] ?? '',
                    'h3' => $d['h3'] ?? '',
                    'h4' => $d['h4'] ?? ''
                ];
            }
        }
        $horarios = json_encode($horarios, JSON_UNESCAPED_UNICODE);
    }

    $logo_url = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) && $file['size'] <= 2 * 1024 * 1024) {
            $dir = __DIR__ . '/../../iseller_store/assets/img/tiendas/' . $bss_id . '/';
            if (!is_dir($dir)) mkdir($dir, 0775, true);
            $filename = 'logo_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                $logo_url = "assets/img/tiendas/{$bss_id}/{$filename}";
            }
        }
    }

    if ($logo_url) {
        $stmt = $conexion->prepare("UPDATE negocio SET slug = ?, config_delivery = ?, datos_bancarios = ?, logo_url = ?, categoria = ?, lat = ?, lng = ?, comunidad = ?, horarios = ?, nombre_visible = ?, notificacion_email = ?, eslogan = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssssi", $slug, $delivery, $datos_bancarios, $logo_url, $categoria, $lat, $lng, $comunidad, $horarios, $nombre_visible, $notificacion_email, $eslogan, $bss_id);
    } else {
        $stmt = $conexion->prepare("UPDATE negocio SET slug = ?, config_delivery = ?, datos_bancarios = ?, categoria = ?, lat = ?, lng = ?, comunidad = ?, horarios = ?, nombre_visible = ?, notificacion_email = ?, eslogan = ? WHERE id = ?");
        $stmt->bind_param("sssssssssssi", $slug, $delivery, $datos_bancarios, $categoria, $lat, $lng, $comunidad, $horarios, $nombre_visible, $notificacion_email, $eslogan, $bss_id);
    }

    $stmt->execute();
    $stmt->close();

    // Mensaje de chat automático de compra (una fila por tienda)
    $mensaje_compra_chat = trim($_POST['mensaje_compra_chat'] ?? '');
    chat_compra_charset($conexion);
    $stmtChat = $conexion->prepare("INSERT INTO tienda_chat_mensaje_compra (bss_id, mensaje) VALUES (?, ?) ON DUPLICATE KEY UPDATE mensaje = VALUES(mensaje)");
    $stmtChat->bind_param("is", $bss_id, $mensaje_compra_chat);
    $stmtChat->execute();
    $stmtChat->close();

    header("Location: tienda_configuracion.php?success=1");
    exit;
}

$stmt = $conexion->prepare("SELECT tiene_tienda_virtual, slug, config_delivery, datos_bancarios, logo_url, portada_url, categoria, lat, lng, comunidad, horarios, nombre_visible, eslogan, notificacion_email FROM negocio WHERE id = ?");
$stmt->bind_param("i", $bss_id);
$stmt->execute();
$result = $stmt->get_result();
$tienda = $result->fetch_assoc();
$stmt->close();

// Si nunca se creó la tienda (sin slug) se manda al onboarding. Si está
// desactivada se deja entrar, para poder reactivarla sin perder la configuración.
if (!$tienda || ((int)$tienda['tiene_tienda_virtual'] === 0 && trim((string)$tienda['slug']) === '')) {
    header("Location: tienda_onboarding.php");
    exit;
}

$tienda_activa = (int)$tienda['tiene_tienda_virtual'] === 1;

$conf_delivery = json_decode($tienda['config_delivery'] ?? '{}', true);
$conf_bancos   = json_decode($tienda['datos_bancarios'] ?? '{}', true);
$conf_horarios = json_decode($tienda['horarios'] ?? '{}', true);

chat_compra_charset($conexion);
$stmtChat = $conexion->prepare("SELECT mensaje FROM tienda_chat_mensaje_compra WHERE bss_id = ? LIMIT 1");
$stmtChat->bind_param("i", $bss_id);
$stmtChat->execute();
$mensaje_compra_chat = (string)($stmtChat->get_result()->fetch_assoc()['mensaje'] ?? '');
$stmtChat->close();

$pm = $conf_bancos['pago_movil'] ?? null;
$tb = $conf_bancos['transferencia'] ?? null;

$pm_pref = 'V';
$pm_num = '';
if ($pm && !empty($pm['cedula'])) {
    $parts = explode('-', $pm['cedula']);
    if (count($parts) == 2) {
        $pm_pref = $parts[0];
        $pm_num = $parts[1];
    } else {
        $pm_num = $pm['cedula'];
    }
}

$tb_pref = 'V';
$tb_num = '';
if ($tb && !empty($tb['cedula'])) {
    $parts = explode('-', $tb['cedula']);
    if (count($parts) == 2) {
        $tb_pref = $parts[0];
        $tb_num = $parts[1];
    } else {
        $tb_num = $tb['cedula'];
    }
}

$stmtPub = $conexion->prepare("SELECT COUNT(*) AS total FROM negocio_productos WHERE negocio_id = ? AND estado_publicacion = 1");
$stmtPub->bind_param("i", $bss_id);
$stmtPub->execute();
$total_pub = $stmtPub->get_result()->fetch_assoc()['total'];
$stmtPub->close();

$horarios_configured = !empty($conf_horarios['tienda']) || !empty($conf_horarios['delivery']);
?>
<!DOCTYPE html>
<html lang='es'>

<head>
    <title>Configuración Tienda Virtual</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css" crossorigin="">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 0 !important;
        }

        /* ---- Layout ---- */
        .config-layout {
            display: flex;
            min-height: 100vh;
        }

        /* ---- Sidebar tabs ---- */
        .config-sidebar {
            width: 260px;
            background: var(--dash-card);
            border-right: 1px solid var(--dash-border);
            padding: 24px 14px;
            flex-shrink: 0;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: var(--dash-text-muted);
            padding: 0 12px;
            margin-bottom: 10px;
        }

        .sidebar-tab {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            border-radius: 8px;
            color: var(--dash-text-muted);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-bottom: 2px;
        }

        .sidebar-tab:hover {
            color: var(--dash-text);
            background: rgba(45, 212, 160, 0.05);
        }

        .sidebar-tab.active {
            color: var(--dash-text);
            background: rgba(45, 212, 160, 0.1);
            border: 1px solid rgba(45, 212, 160, 0.15);
        }

        .sidebar-tab ion-icon {
            font-size: 18px;
        }

        .sidebar-tab.active ion-icon {
            color: var(--dash-mint);
        }

        .sidebar-badge {
            margin-left: auto;
            background: rgba(239, 90, 111, 0.15);
            color: var(--dash-danger);
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 10px;
            line-height: 1.3;
        }

        .sidebar-divider {
            height: 1px;
            background: var(--dash-border);
            margin: 14px 12px;
        }

        .sidebar-links {
            padding: 0;
            list-style: none;
        }

        .sidebar-links li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            color: var(--dash-text-muted);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .sidebar-links li a:hover {
            color: var(--dash-text);
            background: rgba(255, 255, 255, 0.03);
        }

        .sidebar-links li a ion-icon {
            font-size: 18px;
        }

        /* ---- Main content ---- */
        .config-main {
            flex: 1;
            padding: 28px 32px;
            min-width: 0;
        }

        /* ---- Tab panels ---- */
        .config-tab-panel {
            display: none;
        }

        .config-tab-panel.active {
            display: block;
            animation: tabFadeIn 0.2s ease;
        }

        @keyframes tabFadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ---- Panels (reuse from index.php) ---- */
        .dash-panel {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 18px 22px;
            border-bottom: 1px solid var(--dash-border);
        }

        .panel-header h6 {
            display: flex;
            align-items: center;
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: var(--dash-text);
        }

        .panel-header h6 ion-icon {
            margin-right: 8px;
            font-size: 16px;
            color: var(--dash-mint);
        }

        .panel-body {
            padding: 22px;
        }

        /* ---- Forms ---- */
        .field-label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
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

        /* ---- Hero preview ---- */
        .hero-preview {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid var(--dash-border);
        }

        .hero-banner {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            background: linear-gradient(135deg, var(--dash-bg), var(--dash-border));
        }

        .hero-banner-placeholder {
            width: 100%;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--dash-bg), var(--dash-border));
        }

        .hero-banner-placeholder ion-icon {
            font-size: 40px;
            color: var(--dash-border);
        }

        .hero-banner-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .hero-profile {
            position: absolute;
            left: 22px;
            bottom: -36px;
            width: 72px;
            height: 72px;
            border-radius: 50%;
            border: 3px solid var(--dash-card);
            background: var(--dash-bg);
            overflow: hidden;
            cursor: pointer;
        }

        .hero-profile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-profile-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-profile-placeholder ion-icon {
            font-size: 30px;
            color: var(--dash-text-muted);
        }

        .hero-info {
            padding: 44px 22px 18px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
        }

        .hero-store-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
        }

        .hero-hint {
            font-size: 11px;
            color: var(--dash-text-muted);
            margin: 2px 0 0;
        }

        /* ---- Img upload button ---- */
        .img-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(45, 212, 160, 0.12);
            border: 1px solid rgba(45, 212, 160, 0.25);
            color: var(--dash-mint);
            border-radius: 7px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .img-upload-btn:hover {
            background: rgba(45, 212, 160, 0.2);
        }

        .img-upload-btn ion-icon {
            font-size: 14px;
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

        /* ---- Payment cards ---- */
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

        /* ---- Alert ---- */
        .alert-success {
            background: rgba(45, 212, 160, 0.1);
            border: 1px solid rgba(45, 212, 160, 0.2);
            color: var(--dash-mint);
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
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

        /* ---- Community results ---- */
        #community-results .community-item {
            padding: 10px 14px;
            font-size: 13px;
            color: var(--dash-text);
            cursor: pointer;
            border-bottom: 1px solid var(--dash-border);
        }

        #community-results .community-item:last-child {
            border-bottom: none;
        }

        #community-results .community-item:hover {
            background: rgba(45, 212, 160, 0.08);
            color: var(--dash-mint);
        }

        /* ---- Upload toast ---- */
        .upload-toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 13px;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
            min-width: 220px;
        }

        .upload-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .upload-toast.success {
            border-color: rgba(45, 212, 160, 0.4);
        }

        .upload-toast.error {
            border-color: rgba(239, 90, 111, 0.4);
        }

        .upload-toast ion-icon.success {
            color: var(--dash-mint);
            font-size: 18px;
        }

        .upload-toast ion-icon.error {
            color: var(--dash-danger);
            font-size: 18px;
        }

        /* ---- Grid helpers ---- */
        .row {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .col-md-6 {
            flex: 1;
            min-width: 200px;
        }

        .col-md-12 {
            width: 100%;
        }

        .mb-3 {
            margin-bottom: 16px;
        }

        .mb-4 {
            margin-bottom: 20px;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        /* ---- Schedule ---- */
        .schedule-dias-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .schedule-dia-check {
            cursor: pointer;
        }

        .schedule-dia-check input {
            display: none;
        }

        .schedule-dia-box {
            display: block;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 500;
            color: var(--dash-text-muted);
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            border-radius: 6px;
            transition: all 0.15s;
            user-select: none;
        }

        .schedule-dia-check input:checked+.schedule-dia-box {
            color: var(--dash-mint);
            background: rgba(45, 212, 160, 0.12);
            border-color: rgba(45, 212, 160, 0.35);
        }

        .schedule-dia-box:hover {
            border-color: var(--dash-mint);
        }

        .schedule-times {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .schedule-time {
            width: 130px;
            padding: 7px 10px !important;
            font-size: 12px !important;
        }

        .schedule-sep {
            color: var(--dash-text-muted);
            font-size: 13px;
            font-weight: 600;
        }

        /* ---- Responsive ---- */
        @media (max-width: 900px) {
            /* La barra de tabs pasa a fija abajo: el 100vh del layout sobraba y
               dejaba una pantalla y media de scroll vacío. */
            .config-layout {
                flex-direction: column;
                min-height: auto;
            }

            .config-sidebar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                top: auto;
                width: 100%;
                height: auto;
                border-right: none;
                border-top: 1px solid var(--dash-border);
                display: flex;
                padding: 8px 10px;
                overflow-x: auto;
                gap: 4px;
                z-index: 100;
                scrollbar-width: none;
            }

            .config-sidebar::-webkit-scrollbar {
                display: none;
            }

            .sidebar-title,
            .sidebar-divider,
            .sidebar-links {
                display: none;
            }

            .sidebar-tab {
                flex-direction: column;
                gap: 3px;
                padding: 8px 12px;
                font-size: 10px;
                white-space: nowrap;
                margin-bottom: 0;
            }

            .config-main {
                padding: 20px 16px 100px;
            }

            .row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>

<body class='nav-md'>
    <div class='container body'>
        <div class='main_container'>
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class='right_col' role='main'>
                <div class="config-layout">

                    <!-- SIDEBAR -->
                    <aside class="config-sidebar">
                        <p class="sidebar-title">Configuración</p>
                        <div class="sidebar-tab active" data-tab="tab-identidad" onclick="switchTab(this)">
                            <ion-icon name="storefront-outline"></ion-icon>
                            Identidad
                        </div>
                        <div class="sidebar-tab" data-tab="tab-imagenes" onclick="switchTab(this)">
                            <ion-icon name="image-outline"></ion-icon>
                            Imágenes
                        </div>
                        <div class="sidebar-tab" data-tab="tab-envios" onclick="switchTab(this)">
                            <ion-icon name="car-outline"></ion-icon>
                            Envíos
                        </div>
                        <div class="sidebar-tab" data-tab="tab-ubicacion" onclick="switchTab(this)">
                            <ion-icon name="location-outline"></ion-icon>
                            Ubicación
                        </div>
                        <div class="sidebar-tab" data-tab="tab-pagos" onclick="switchTab(this)">
                            <ion-icon name="card-outline"></ion-icon>
                            Pagos
                        </div>
                        <div class="sidebar-tab" data-tab="tab-horarios" onclick="switchTab(this)">
                            <ion-icon name="time-outline"></ion-icon>
                            Horarios
                            <?php if (!$horarios_configured): ?>
                                <span class="sidebar-badge">!</span>
                            <?php endif; ?>
                        </div>
                        <div class="sidebar-tab" data-tab="tab-notificaciones" onclick="switchTab(this)">
                            <ion-icon name="notifications-outline"></ion-icon>
                            Notificaciones
                            <?php if (empty($tienda['notificacion_email'])): ?>
                                <span class="sidebar-badge">!</span>
                            <?php endif; ?>
                        </div>
                        <div class="sidebar-tab" data-tab="tab-chat" onclick="switchTab(this)">
                            <ion-icon name="chatbubbles-outline"></ion-icon>
                            Chat de Compra
                        </div>

                        <?php if ($tienda_activa): ?>
                        <div class="sidebar-divider"></div>
                        <p class="sidebar-title">Accesos</p>
                        <ul class="sidebar-links">
                            <li>
                                <a href="../../<?= htmlspecialchars($tienda['slug']) ?>" target="_blank">
                                    <ion-icon name="open-outline"></ion-icon>
                                    Ver Tienda
                                </a>
                            </li>
                            <li>
                                <a href="tienda_productos.php">
                                    <ion-icon name="pricetags-outline"></ion-icon>
                                    Productos
                                </a>
                            </li>
                            <li>
                                <a href="tienda_pedidos.php">
                                    <ion-icon name="receipt-outline"></ion-icon>
                                    Pedidos
                                </a>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </aside>

                    <!-- MAIN CONTENT -->
                    <main class="config-main">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert-success">
                                <ion-icon name="checkmark-circle-outline" style="font-size:20px;"></ion-icon>
                                Los cambios se guardaron correctamente.
                            </div>
                        <?php endif; ?>

                        <?php if (in_array($_GET['error'] ?? '', ['slug', 'slug_existe', 'notif'], true)): ?>
                            <div style="background:rgba(239,90,111,0.1);border:1px solid rgba(239,90,111,0.3);color:var(--dash-danger);border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:13px;font-weight:600;display:flex;align-items:center;gap:8px;">
                                <ion-icon name="alert-circle-outline" style="font-size:20px;"></ion-icon>
                                <?= $_GET['error'] === 'slug_existe' ? 'Esa URL ya está en uso por otra tienda. Elige otra.' : ($_GET['error'] === 'notif' ? 'El usuario de notificaciones debe pertenecer a tu negocio.' : 'Esa URL no está disponible. Elige otra.') ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($total_pub == 0): ?>
                            <div style="background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.25);border-radius:10px;padding:14px 18px;display:flex;align-items:center;gap:14px;margin-bottom:24px;">
                                <ion-icon name="warning-outline" style="font-size:24px;color:#ffc107;flex-shrink:0;"></ion-icon>
                                <div style="flex:1;">
                                    <strong style="color:var(--dash-text);">No tienes productos publicados.</strong>
                                    <span style="color:var(--dash-text-muted);font-size:13px;"> Tu tienda no se mostrará a los clientes hasta que publiques al menos un producto.</span>
                                </div>
                                <a href="tienda_productos.php" style="background:rgba(255,193,7,0.15);border:1px solid rgba(255,193,7,0.3);color:var(--dash-text);border-radius:8px;padding:8px 16px;font-size:13px;font-weight:600;text-decoration:none;white-space:nowrap;display:flex;align-items:center;gap:6px;">
                                    <ion-icon name="add-circle-outline" style="font-size:16px;"></ion-icon>
                                    Ir a Productos
                                </a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data" id="configForm">

                            <!-- TAB: Identidad -->
                            <div class="config-tab-panel active" id="tab-identidad">
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="storefront-outline"></ion-icon>Identidad de la Tienda</h6>
                                    </div>
                                    <div class="panel-body">
                                        <div class="mb-4">
                                            <label class="field-label">Nombre visible en la tienda</label>
                                            <input type="text" name="nombre_visible" class="dash-input" placeholder="Ej: Mi Tienda Online" value="<?= htmlspecialchars($tienda['nombre_visible'] ?? '') ?>">
                                            <p class="field-hint">Nombre que verán los clientes en la tienda virtual.</p>
                                        </div>
                                        <div class="mb-4">
                                            <label class="field-label">Eslogan de la tienda</label>
                                            <textarea name="eslogan" id="eslogan-input" class="dash-input" rows="2" maxlength="160" placeholder="<?= htmlspecialchars(eslogan_categoria_default($tienda['categoria'] ?? '')) ?>"><?= htmlspecialchars($tienda['eslogan'] ?? '') ?></textarea>
                                            <p class="field-hint">Aparece debajo del nombre de tu tienda. Si lo dejas vacío se usa el texto por defecto de tu categoría (el del ejemplo).</p>
                                        </div>
                                        <div class="mb-4">
                                            <label class="field-label">URL de la tienda (Slug)</label>
                                            <div class="input-prefix-group">
                                                <span class="input-prefix">iseller-tiendas.com/</span>
                                                <input type="text" name="slug" class="dash-input" required placeholder="mi-negocio" value="<?= htmlspecialchars($tienda['slug']) ?>">
                                            </div>
                                            <p class="field-hint">Solo minúsculas, números y guiones.</p>
                                        </div>
                                        <div>
                                            <label class="field-label">Categoría de la Tienda</label>
                                            <select name="categoria" class="dash-input" required>
                                                <option value="">Seleccione una categoría...</option>
                                                <?php
                                                $current_cat = $tienda['categoria'] ?? '';
                                                foreach ($categorias_list as $cat) {
                                                    $selected = ($cat === $current_cat) ? 'selected' : '';
                                                    echo "<option value=\"$cat\" $selected>$cat</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Identidad
                                </button>
                            </div>

                            <!-- TAB: Imágenes -->
                            <div class="config-tab-panel" id="tab-imagenes">
                                <div class="hero-preview">
                                    <div style="position:relative;">
                                        <?php if (!empty($tienda['portada_url'])): ?>
                                            <img id="hero-banner-img" class="hero-banner" src="../../iseller_store/<?= htmlspecialchars($tienda['portada_url']) ?>" alt="Portada">
                                        <?php else: ?>
                                            <div class="hero-banner-placeholder" id="hero-banner-placeholder">
                                                <ion-icon name="image-outline"></ion-icon>
                                            </div>
                                            <img id="hero-banner-img" class="hero-banner" src="" alt="Portada" style="display:none;">
                                        <?php endif; ?>
                                        <div class="hero-banner-overlay">
                                            <label class="img-upload-btn" for="input-portada">
                                                <ion-icon name="camera-outline"></ion-icon>
                                                Cambiar portada
                                                <input type="file" id="input-portada" accept="image/jpeg,image/png,image/webp" style="display:none;" data-tipo="portada">
                                            </label>
                                        </div>
                                        <div class="hero-profile" onclick="document.getElementById('input-logo').click()">
                                            <?php if (!empty($tienda['logo_url'])): ?>
                                                <img id="hero-logo-img" src="../../iseller_store/<?= htmlspecialchars($tienda['logo_url']) ?>" alt="Logo">
                                            <?php else: ?>
                                                <div class="hero-profile-placeholder">
                                                    <ion-icon name="storefront-outline"></ion-icon>
                                                </div>
                                                <img id="hero-logo-img" src="" alt="Logo" style="display:none;">
                                            <?php endif; ?>
                                            <input type="file" id="input-logo" accept="image/jpeg,image/png,image/webp" style="display:none;" data-tipo="logo">
                                        </div>
                                    </div>
                                    <div class="hero-info">
                                        <div>
                                            <p class="hero-store-name"><?= htmlspecialchars($tienda['nombre_visible'] ?: $tienda['nombre'] ?? '') ?></p>
                                            <p class="hero-hint">Haz clic en tu foto de perfil o en "Cambiar portada" para actualizar las imágenes</p>
                                        </div>
                                        <label class="img-upload-btn" for="input-logo" style="flex-shrink:0;">
                                            <ion-icon name="person-circle-outline"></ion-icon>
                                            Cambiar logo
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Imágenes
                                </button>
                            </div>

                            <!-- TAB: Envíos -->
                            <div class="config-tab-panel" id="tab-envios">
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="car-outline"></ion-icon>Logística y Envíos</h6>
                                    </div>
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="field-label">Tipo de delivery</label>
                                                <select name="delivery_tipo" id="delivery-tipo" class="dash-input">
                                                    <option value="gratis" <?= ($conf_delivery['tipo'] ?? '') == 'gratis' ? 'selected' : '' ?>>Gratis</option>
                                                    <option value="pago" <?= ($conf_delivery['tipo'] ?? '') == 'pago' ? 'selected' : '' ?>>Pagado</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3" id="delivery-costo-wrap" style="display: <?= (($conf_delivery['tipo'] ?? 'gratis') === 'pago' || floatval($conf_delivery['minimo'] ?? 0) > 0) ? 'block' : 'none' ?>;">
                                                <label class="field-label">Costo (USD)</label>
                                                <input type="number" step="0.01" min="0" name="delivery_costo" id="delivery-costo" class="dash-input" placeholder="0.00" value="<?= htmlspecialchars($conf_delivery['costo'] ?? '') ?>">
                                                <p class="field-hint" id="delivery-costo-hint">Se cobra cuando el pedido no llega al mínimo.</p>
                                            </div>
                                            <div class="col-md-6 mb-3" id="delivery-minimo-wrap" style="display: <?= (($conf_delivery['tipo'] ?? 'gratis') === 'pago') ? 'none' : 'block' ?>;">
                                                <label class="field-label">Mínimo para envío gratis (USD)</label>
                                                <input type="number" step="0.01" min="0" name="delivery_minimo" id="delivery-minimo" class="dash-input" placeholder="0.00" value="<?= htmlspecialchars($conf_delivery['minimo'] ?? '') ?>">
                                                <p class="field-hint">Envío gratis a partir de este monto; por debajo se cobra el costo. Vacío o 0 = siempre gratis.</p>
                                            </div>
                                            <div class="col-md-6 mb-0">
                                                <label class="field-label">Alcance de entrega</label>
                                                <select name="delivery_zonas" class="dash-input">
                                                    <option value="comunidad" <?= ($conf_delivery['zonas'] ?? '') == 'comunidad' ? 'selected' : '' ?>>Mi comunidad</option>
                                                    <option value="municipio" <?= ($conf_delivery['zonas'] ?? '') == 'municipio' ? 'selected' : '' ?>>Todo el municipio</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Envíos
                                </button>
                            </div>

                            <!-- TAB: Ubicación -->
                            <div class="config-tab-panel" id="tab-ubicacion">
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="location-outline"></ion-icon>Ubicación de la Tienda</h6>
                                    </div>
                                    <div class="panel-body">
                                        <p class="field-hint mb-3" style="margin-top:0;">Haz clic en el mapa para colocar tu tienda o busca tu comunidad para centrar el mapa.</p>
                                        <div style="position:relative;" class="mb-3">
                                            <input type="text" id="comunidad" class="dash-input" placeholder="Escribe tu comunidad..." autocomplete="off">
                                            <div id="community-results" style="display:none;position:absolute;top:100%;left:0;right:0;background:var(--dash-bg);border:1px solid var(--dash-border);border-radius:0 0 8px 8px;max-height:160px;overflow-y:auto;z-index:50;"></div>
                                        </div>
                                        <div id="map-picker" style="width:100%;height:280px;border-radius:8px;border:1px solid var(--dash-border);margin-bottom:14px;"></div>
                                        <div class="row mb-0">
                                            <div class="col-md-4 mb-3">
                                                <label class="field-label">Latitud</label>
                                                <input type="text" name="lat" id="lat-input" class="dash-input" placeholder="0.0000000" value="<?= htmlspecialchars($tienda['lat'] ?? '') ?>" readonly>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="field-label">Longitud</label>
                                                <input type="text" name="lng" id="lng-input" class="dash-input" placeholder="0.0000000" value="<?= htmlspecialchars($tienda['lng'] ?? '') ?>" readonly>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="field-label">Comunidad</label>
                                                <input type="text" name="comunidad" id="comunidad-hidden" class="dash-input" value="<?= htmlspecialchars($tienda['comunidad'] ?? '') ?>" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Ubicación
                                </button>
                            </div>

                            <!-- TAB: Pagos -->
                            <div class="config-tab-panel" id="tab-pagos">
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="card-outline"></ion-icon>Métodos de Pago</h6>
                                    </div>
                                    <div class="panel-body">
                                        <p class="field-hint mb-3" style="margin-top:0;">Activa al menos un método de pago para que tus clientes puedan finalizar el pedido.</p>

                                        <!-- Pago Móvil -->
                                        <div class="pay-card <?= $pm ? 'is-active' : '' ?>" id="card-pm">
                                            <div class="pay-card-header">
                                                <div class="pay-card-title">
                                                    <ion-icon name="phone-portrait-outline"></ion-icon>
                                                    Pago Móvil
                                                </div>
                                                <label class="switch" onclick="event.stopPropagation()">
                                                    <input type="checkbox" name="enable_pm" value="1" id="switch-pm" <?= $pm ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                            <div class="pay-card-body" id="body-pm" style="display: <?= $pm ? 'block' : 'none' ?>;">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="field-label">Banco</label>
                                                        <select name="pm_banco" class="dash-input pm-req">
                                                            <option value="">Seleccione el banco...</option>
                                                            <?php foreach ($bancos_venezuela as $code => $name): ?>
                                                                <?php $val = $code . ' - ' . $name; ?>
                                                                <option value="<?= htmlspecialchars($val) ?>" <?= ($pm['banco'] ?? '') == $val ? 'selected' : '' ?>><?= htmlspecialchars($val) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Teléfono</label>
                                                        <input type="text" name="pm_telefono" class="dash-input pm-req" placeholder="04141234567" value="<?= htmlspecialchars($pm['telefono'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Cédula / RIF</label>
                                                        <div class="prefix-select-wrap">
                                                            <select name="pm_cedula_prefix" class="dash-input prefix-select">
                                                                <option <?= $pm_pref == 'V' ? 'selected' : '' ?>>V</option>
                                                                <option <?= $pm_pref == 'E' ? 'selected' : '' ?>>E</option>
                                                                <option <?= $pm_pref == 'J' ? 'selected' : '' ?>>J</option>
                                                                <option <?= $pm_pref == 'P' ? 'selected' : '' ?>>P</option>
                                                                <option <?= $pm_pref == 'G' ? 'selected' : '' ?>>G</option>
                                                            </select>
                                                            <input type="text" name="pm_cedula" class="dash-input pm-req" placeholder="12345678" value="<?= htmlspecialchars($pm_num) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Transferencia -->
                                        <div class="pay-card <?= $tb ? 'is-active' : '' ?>" id="card-tb">
                                            <div class="pay-card-header">
                                                <div class="pay-card-title">
                                                    <ion-icon name="business-outline"></ion-icon>
                                                    Transferencia Bancaria
                                                </div>
                                                <label class="switch" onclick="event.stopPropagation()">
                                                    <input type="checkbox" name="enable_tb" value="1" id="switch-tb" <?= $tb ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </div>
                                            <div class="pay-card-body" id="body-tb" style="display: <?= $tb ? 'block' : 'none' ?>;">
                                                <div class="row">
                                                    <div class="col-md-12 mb-3">
                                                        <label class="field-label">Banco</label>
                                                        <select name="tb_banco" class="dash-input tb-req">
                                                            <option value="">Seleccione el banco...</option>
                                                            <?php foreach ($bancos_venezuela as $code => $name): ?>
                                                                <option value="<?= htmlspecialchars($name) ?>" <?= ($tb['banco'] ?? '') == $name ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12 mb-3">
                                                        <label class="field-label">Número de cuenta (20 dígitos)</label>
                                                        <input type="text" name="tb_cuenta" class="dash-input tb-req" maxlength="20" placeholder="01020000000000000000" value="<?= htmlspecialchars($tb['cuenta'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Titular</label>
                                                        <input type="text" name="tb_titular" class="dash-input tb-req" placeholder="Juan Perez" value="<?= htmlspecialchars($tb['titular'] ?? '') ?>">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="field-label">Cédula / RIF</label>
                                                        <div class="prefix-select-wrap">
                                                            <select name="tb_cedula_prefix" class="dash-input prefix-select">
                                                                <option <?= $tb_pref == 'V' ? 'selected' : '' ?>>V</option>
                                                                <option <?= $tb_pref == 'E' ? 'selected' : '' ?>>E</option>
                                                                <option <?= $tb_pref == 'J' ? 'selected' : '' ?>>J</option>
                                                                <option <?= $tb_pref == 'P' ? 'selected' : '' ?>>P</option>
                                                                <option <?= $tb_pref == 'G' ? 'selected' : '' ?>>G</option>
                                                            </select>
                                                            <input type="text" name="tb_cedula" class="dash-input tb-req" placeholder="12345678" value="<?= htmlspecialchars($tb_num) ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="error-msg" id="pay-error">
                                            <ion-icon name="alert-circle-outline"></ion-icon>
                                            Debes activar al menos un método de pago.
                                        </p>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Pagos
                                </button>
                            </div>

                            <!-- TAB: Horarios -->
                            <div class="config-tab-panel" id="tab-horarios">
                                <?php
                                $dias_options = [
                                    'lunes' => 'Lunes',
                                    'martes' => 'Martes',
                                    'miercoles' => 'Miércoles',
                                    'jueves' => 'Jueves',
                                    'viernes' => 'Viernes',
                                    'sabado' => 'Sábado',
                                    'domingo' => 'Domingo'
                                ];
                                $dias_keys = array_keys($dias_options);
                                ?>
                                <!-- Tienda Física -->
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="storefront-outline"></ion-icon>Horario Tienda Física</h6>
                                    </div>
                                    <div class="panel-body">
                                        <div class="mb-3">
                                            <label class="field-label">Días de atención</label>
                                            <div class="schedule-dias-grid">
                                                <?php
                                                $tienda_dias = $conf_horarios['tienda']['dias'] ?? [];
                                                foreach ($dias_options as $k => $v):
                                                ?>
                                                    <label class="schedule-dia-check">
                                                        <input type="checkbox" name="horarios[tienda][dias][]" value="<?= $k ?>" <?= in_array($k, $tienda_dias) ? 'checked' : '' ?>>
                                                        <span class="schedule-dia-box"><?= $v ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="field-label">Tipo de turno</label>
                                            <select id="tienda-tipo" class="dash-input schedule-tipo" data-target="tienda">
                                                <?php $current_tipo = $conf_horarios['tienda']['tipo'] ?? 'corrido'; ?>
                                                <option value="corrido" <?= $current_tipo == 'corrido' ? 'selected' : '' ?>>Corrido (un solo bloque)</option>
                                                <option value="doble" <?= $current_tipo == 'doble' ? 'selected' : '' ?>>Doble (mañana y tarde)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="tienda-range-1" style="display:<?= $current_tipo == 'doble' ? 'block' : 'none' ?>;">
                                            <label class="field-label">Turno mañana</label>
                                            <div class="schedule-times">
                                                <input type="time" name="horarios[tienda][h1]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['tienda']['h1'] ?? '') ?>">
                                                <span class="schedule-sep">-</span>
                                                <input type="time" name="horarios[tienda][h2]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['tienda']['h2'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="field-label" id="tienda-range-2-label"><?= $current_tipo == 'doble' ? 'Turno tarde' : 'Horario de atención' ?></label>
                                            <div class="schedule-times">
                                                <input type="time" name="horarios[tienda][h3]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['tienda']['h3'] ?? '') ?>">
                                                <span class="schedule-sep">-</span>
                                                <input type="time" name="horarios[tienda][h4]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['tienda']['h4'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delivery -->
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="car-outline"></ion-icon>Horario Delivery</h6>
                                    </div>
                                    <div class="panel-body">
                                        <div class="mb-3">
                                            <label class="field-label">Días de atención</label>
                                            <div class="schedule-dias-grid">
                                                <?php
                                                $delivery_dias = $conf_horarios['delivery']['dias'] ?? [];
                                                foreach ($dias_options as $k => $v):
                                                ?>
                                                    <label class="schedule-dia-check">
                                                        <input type="checkbox" name="horarios[delivery][dias][]" value="<?= $k ?>" <?= in_array($k, $delivery_dias) ? 'checked' : '' ?>>
                                                        <span class="schedule-dia-box"><?= $v ?></span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="field-label">Tipo de turno</label>
                                            <select id="delivery-tipo" class="dash-input schedule-tipo" data-target="delivery">
                                                <?php $current_tipo_d = $conf_horarios['delivery']['tipo'] ?? 'corrido'; ?>
                                                <option value="corrido" <?= $current_tipo_d == 'corrido' ? 'selected' : '' ?>>Corrido (un solo bloque)</option>
                                                <option value="doble" <?= $current_tipo_d == 'doble' ? 'selected' : '' ?>>Doble (mañana y tarde)</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="delivery-range-1" style="display:<?= $current_tipo_d == 'doble' ? 'block' : 'none' ?>;">
                                            <label class="field-label">Turno mañana</label>
                                            <div class="schedule-times">
                                                <input type="time" name="horarios[delivery][h1]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['delivery']['h1'] ?? '') ?>">
                                                <span class="schedule-sep">-</span>
                                                <input type="time" name="horarios[delivery][h2]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['delivery']['h2'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="field-label" id="delivery-range-2-label"><?= $current_tipo_d == 'doble' ? 'Turno tarde' : 'Horario de atención' ?></label>
                                            <div class="schedule-times">
                                                <input type="time" name="horarios[delivery][h3]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['delivery']['h3'] ?? '') ?>">
                                                <span class="schedule-sep">-</span>
                                                <input type="time" name="horarios[delivery][h4]" class="dash-input schedule-time" value="<?= htmlspecialchars($conf_horarios['delivery']['h4'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Horarios
                                </button>
                            </div>

                            <!-- TAB: Notificaciones -->
                            <div class="config-tab-panel" id="tab-notificaciones">
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="notifications-outline"></ion-icon>Notificaciones de Pedidos</h6>
                                    </div>
                                    <div class="panel-body">
                                        <p class="field-hint mb-3" style="margin-top:0;">Selecciona el usuario que recibirá las notificaciones cuando se realice un nuevo pedido en tu tienda.</p>
                                        <div class="mb-0">
                                            <label class="field-label">Usuario notificado</label>
                                            <select name="notificacion_email" class="dash-input">
                                                <option value="">Seleccione un usuario...</option>
                                                <option value="0" <?= strval($tienda['notificacion_email'] ?? '') === '' ? 'selected' : '' ?>>— Sin notificaciones —</option>
                                                <?php
                                                $stmtUsers = $conexion->prepare("SELECT u.id, u.nombre, s.nombre AS sucursal FROM usuarios u LEFT JOIN sucursales s ON u.id_sucursal = s.id WHERE u.bss_id = ? AND u.status = 0 ORDER BY u.nombre");
                                                $stmtUsers->bind_param("i", $bss_id);
                                                $stmtUsers->execute();
                                                $resUsers = $stmtUsers->get_result();
                                                $currentNotify = (string)($tienda['notificacion_email'] ?? '');
                                                $currentUserId = $_SESSION['id'] ?? 0;
                                                $notifEncontrado = false;
                                                while ($u = $resUsers->fetch_assoc()):
                                                    $isMe = (int)$u['id'] === (int)$currentUserId;
                                                    if ((string)$u['id'] === $currentNotify) $notifEncontrado = true;
                                                ?>
                                                    <option value="<?= $u['id'] ?>" <?= strval($u['id']) === strval($currentNotify) ? 'selected' : '' ?>><?= htmlspecialchars($u['nombre']) ?><?= $isMe ? ' (tú)' : '' ?> <?= htmlspecialchars($u['sucursal'] ? '- ' . $u['sucursal'] : '') ?></option>
                                                <?php endwhile;
                                                $stmtUsers->close();

                                                // Si el usuario configurado ya no aparece (desactivado/borrado),
                                                // se muestra igual para no borrarlo al guardar otra pestaña.
                                                if ($currentNotify !== '' && !$notifEncontrado) {
                                                    $etiquetaNotif = 'Usuario configurado (no disponible)';
                                                    if (ctype_digit($currentNotify)) {
                                                        $stmtCur = $conexion->prepare("SELECT nombre FROM usuarios WHERE id = ? AND bss_id = ? LIMIT 1");
                                                        $uidCur = (int)$currentNotify;
                                                        $stmtCur->bind_param("ii", $uidCur, $bss_id);
                                                        $stmtCur->execute();
                                                        $rowCur = $stmtCur->get_result()->fetch_assoc();
                                                        $stmtCur->close();
                                                        if ($rowCur) $etiquetaNotif = $rowCur['nombre'] . ' (no disponible)';
                                                    }
                                                    echo '<option value="' . htmlspecialchars($currentNotify) . '" selected>' . htmlspecialchars($etiquetaNotif) . '</option>';
                                                }
                                                ?>
                                            </select>
                                            <p class="field-hint">El usuario seleccionado recibirá un correo con los detalles de cada pedido. <strong class="text-danger">Se mostrara una alerta en las cajas abiertas siempre que exista un pedido sin atender.</strong></p>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Notificaciones
                                </button>
                            </div>

                            <!-- TAB: Chat de compra -->
                            <div class="config-tab-panel" id="tab-chat">
                                <div class="dash-panel">
                                    <div class="panel-header">
                                        <h6><ion-icon name="chatbubbles-outline"></ion-icon>Chat de Compra</h6>
                                    </div>
                                    <div class="panel-body">
                                        <p class="field-hint mb-3" style="margin-top:0;">Al confirmar una compra se abre un chat automático con el cliente usando este mensaje.</p>
                                        <div class="mb-0">
                                            <label class="field-label">Mensaje automático de compra</label>
                                            <textarea name="mensaje_compra_chat" class="dash-input" rows="4" placeholder="<?= htmlspecialchars(chat_compra_mensaje_default()) ?>"><?= htmlspecialchars($mensaje_compra_chat) ?></textarea>
                                            <p class="field-hint">Si lo dejas vacío se usará el mensaje por defecto (el del ejemplo).</p>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <ion-icon name="save-outline"></ion-icon>
                                    Guardar Chat
                                </button>
                            </div>

                        </form>

                        <!-- Zona de peligro: activar / desactivar la tienda -->
                        <div class="dash-panel" style="border-color: <?= $tienda_activa ? 'rgba(239,90,111,0.25)' : 'rgba(45,212,160,0.25)' ?>;">
                            <div class="panel-header">
                                <h6 style="color:<?= $tienda_activa ? 'var(--dash-danger)' : 'var(--dash-mint)' ?>;">
                                    <ion-icon name="power-outline" style="color:inherit;"></ion-icon>
                                    <?= $tienda_activa ? 'Desactivar tienda' : 'Reactivar tienda' ?>
                                </h6>
                            </div>
                            <div class="panel-body">
                                <p class="field-hint" style="margin-top:0;">
                                    <?php if ($tienda_activa): ?>
                                        Tu tienda dejará de ser visible para los clientes: el enlace
                                        <b>iseller-tiendas.com/<?= htmlspecialchars($tienda['slug']) ?></b> mostrará
                                        "Tienda no encontrada". No se borra nada (productos, pedidos y configuración
                                        se conservan) y puedes reactivarla aquí mismo.
                                    <?php else: ?>
                                        Tu tienda está <b>desactivada</b>: los clientes no pueden verla ni hacer pedidos.
                                        Al reactivarla vuelve a estar disponible en
                                        <b>iseller-tiendas.com/<?= htmlspecialchars($tienda['slug']) ?></b> con la misma
                                        configuración y productos.
                                    <?php endif; ?>
                                </p>
                                <form method="POST" id="formFlagTienda">
                                    <input type="hidden" name="accion" value="<?= $tienda_activa ? 'desactivar_tienda' : 'reactivar_tienda' ?>">
                                    <button type="submit" class="btn <?= $tienda_activa ? 'btn-danger' : 'btn-success' ?>">
                                        <ion-icon name="power-outline"></ion-icon>
                                        <?= $tienda_activa ? 'Desactivar mi tienda' : 'Reactivar mi tienda' ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </main>

                </div>
            </div>

            <?php if (file_exists('includes/footer.php')) require_once('includes/footer.php'); ?>
        </div>
    </div>

    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
    <script>
        function switchTab(el) {
            document.querySelectorAll('.sidebar-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.config-tab-panel').forEach(p => p.classList.remove('active'));
            el.classList.add('active');
            const panel = document.getElementById(el.dataset.tab);
            if (panel) panel.classList.add('active');
            if (el.dataset.tab === 'tab-ubicacion' && window.configMap) {
                setTimeout(function() {
                    window.configMap.invalidateSize();
                }, 100);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.createElement('div');
            toast.className = 'upload-toast';
            document.body.appendChild(toast);

            function showToast(msg, type) {
                const icon = type === 'success' ? 'checkmark-circle-outline' : 'alert-circle-outline';
                toast.innerHTML = `<ion-icon name="${icon}" class="${type}"></ion-icon><span>${msg}</span>`;
                toast.className = `upload-toast ${type} show`;
                clearTimeout(toast._timer);
                toast._timer = setTimeout(() => toast.classList.remove('show'), 3500);
            }

            function setupImageUpload(inputId, previewImgId, placeholderId) {
                const input = document.getElementById(inputId);
                if (!input) return;
                input.addEventListener('change', function() {
                    if (!this.files || !this.files[0]) return;
                    const file = this.files[0];
                    const tipo = this.dataset.tipo;
                    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!allowed.includes(file.type)) {
                        showToast('Solo JPG, PNG o WEBP.', 'error');
                        this.value = '';
                        return;
                    }
                    const maxMB = tipo === 'portada' ? 5 : 2;
                    if (file.size > maxMB * 1024 * 1024) {
                        showToast(`Supera ${maxMB} MB.`, 'error');
                        this.value = '';
                        return;
                    }

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const img = document.getElementById(previewImgId);
                        if (img) {
                            img.src = e.target.result;
                            img.style.display = 'block';
                        }
                        if (placeholderId) {
                            const ph = document.getElementById(placeholderId);
                            if (ph) ph.style.display = 'none';
                        }
                    };
                    reader.readAsDataURL(file);

                    const fd = new FormData();
                    fd.append('tipo', tipo);
                    fd.append('imagen', file);
                    showToast('Subiendo...', 'success');

                    fetch('api/tienda_upload_imagen.php', {
                            method: 'POST',
                            body: fd
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.ok) showToast('Imagen actualizada.', 'success');
                            else showToast(data.error || 'Error.', 'error');
                        })
                        .catch(err => showToast('Error: ' + err.message, 'error'));
                });
            }

            setupImageUpload('input-logo', 'hero-logo-img', null);
            setupImageUpload('input-portada', 'hero-banner-img', 'hero-banner-placeholder');

            const deliveryTipo = document.getElementById('delivery-tipo');
            const costoWrap = document.getElementById('delivery-costo-wrap');
            const costoInput = document.getElementById('delivery-costo');
            const minimoWrap = document.getElementById('delivery-minimo-wrap');
            const minimoInput = document.getElementById('delivery-minimo');
            const costoHint = document.getElementById('delivery-costo-hint');

            // Gratis: el mínimo define desde cuánto no se cobra y el costo lo que se
            // cobra por debajo. Pagado: el mínimo no aplica y se cobra siempre.
            function actualizarCamposDelivery() {
                const gratis = deliveryTipo.value !== 'pago';
                const hayMinimo = (parseFloat(minimoInput.value) || 0) > 0;
                minimoWrap.style.display = gratis ? 'block' : 'none';
                const mostrarCosto = !gratis || hayMinimo;
                costoWrap.style.display = mostrarCosto ? 'block' : 'none';
                costoInput.required = mostrarCosto;
                costoHint.textContent = gratis ? 'Se cobra cuando el pedido no llega al mínimo.' : 'Se cobra siempre en cada pedido.';
            }
            deliveryTipo.addEventListener('change', actualizarCamposDelivery);
            minimoInput.addEventListener('input', actualizarCamposDelivery);
            actualizarCamposDelivery();

            function togglePayCard(switchEl, bodyEl, cardEl, reqClass) {
                const inputs = document.querySelectorAll('.' + reqClass);
                if (switchEl.checked) {
                    bodyEl.style.display = 'block';
                    cardEl.classList.add('is-active');
                    inputs.forEach(i => i.required = true);
                } else {
                    bodyEl.style.display = 'none';
                    cardEl.classList.remove('is-active');
                    inputs.forEach(i => i.required = false);
                }
            }

            const switchPm = document.getElementById('switch-pm');
            const switchTb = document.getElementById('switch-tb');
            switchPm.addEventListener('change', () => togglePayCard(switchPm, document.getElementById('body-pm'), document.getElementById('card-pm'), 'pm-req'));
            switchTb.addEventListener('change', () => togglePayCard(switchTb, document.getElementById('body-tb'), document.getElementById('card-tb'), 'tb-req'));

            // Slugs reservados o ya usados por otra tienda
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

            // El ejemplo del eslogan cambia con la categoría elegida
            const SLOGAN_DEFAULTS = <?= json_encode(eslogan_defaults(), JSON_UNESCAPED_UNICODE) ?>;
            const esloganInput = document.getElementById('eslogan-input');
            const categoriaSelect = document.querySelector('select[name="categoria"]');
            if (esloganInput && categoriaSelect) {
                categoriaSelect.addEventListener('change', function() {
                    esloganInput.placeholder = SLOGAN_DEFAULTS[this.value] || <?= json_encode(eslogan_categoria_default(''), JSON_UNESCAPED_UNICODE) ?>;
                });
            }

            document.getElementById('configForm').addEventListener('submit', function(e) {
                if (!switchPm.checked && !switchTb.checked) {
                    e.preventDefault();
                    document.getElementById('pay-error').style.display = 'block';
                    const payTab = document.querySelector('[data-tab="tab-pagos"]');
                    if (payTab) switchTab(payTab);
                }
            });

            // Confirmación antes de activar/desactivar la tienda
            const formFlagTienda = document.getElementById('formFlagTienda');
            if (formFlagTienda) {
                formFlagTienda.addEventListener('submit', function(e) {
                    if (this.dataset.confirmado === '1') return;
                    e.preventDefault();
                    const form = this;
                    const desactivar = form.querySelector('input[name="accion"]').value === 'desactivar_tienda';
                    const enviar = () => {
                        form.dataset.confirmado = '1';
                        form.submit();
                    };
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: desactivar ? 'warning' : 'question',
                            title: desactivar ? '¿Desactivar la tienda?' : '¿Reactivar la tienda?',
                            text: desactivar ?
                                'Dejará de ser visible para tus clientes hasta que la reactives.' :
                                'Volverá a estar visible para tus clientes.',
                            showCancelButton: true,
                            confirmButtonText: desactivar ? 'Sí, desactivar' : 'Sí, reactivar',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: desactivar ? '#ef5a6f' : '#2dd4a0',
                            background: '#181b22',
                            color: '#e8edf2'
                        }).then(function(r) {
                            if (r.isConfirmed) enviar();
                        });
                    } else if (confirm(desactivar ?
                            '¿Desactivar la tienda? Dejará de ser visible para tus clientes.' :
                            '¿Reactivar la tienda? Volverá a estar visible para tus clientes.')) {
                        enviar();
                    }
                });
            }

            // Schedule tipo turno toggle
            document.querySelectorAll('.schedule-tipo').forEach(select => {
                select.addEventListener('change', function() {
                    const target = this.dataset.target;
                    const isDoble = this.value === 'doble';
                    document.getElementById(target + '-range-1').style.display = isDoble ? 'block' : 'none';
                    document.getElementById(target + '-range-2-label').textContent = isDoble ? 'Turno tarde' : 'Horario de atención';
                });
            });

            // ---- Leaflet Map ----
            const latInput = document.getElementById('lat-input');
            const lngInput = document.getElementById('lng-input');
            const comunidadInput = document.getElementById('comunidad');
            const comunidadHidden = document.getElementById('comunidad-hidden');
            const resultsDiv = document.getElementById('community-results');

            const hasCoords = latInput.value && lngInput.value;
            const initLat = hasCoords ? parseFloat(latInput.value) : 5.6667;
            const initLng = hasCoords ? parseFloat(lngInput.value) : -67.6167;
            const initZoom = hasCoords ? 15 : 12;

            window.configMap = L.map('map-picker').setView([initLat, initLng], initZoom);
            L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
            }).addTo(window.configMap);

            let marker = null;
            let geojsonLayer = null;
            let allCommunitiesNames = [];

            function setMarker(lat, lng) {
                if (marker) window.configMap.removeLayer(marker);
                marker = L.marker([lat, lng], {
                    draggable: true
                }).addTo(window.configMap);
                latInput.value = lat.toFixed(7);
                lngInput.value = lng.toFixed(7);
                marker.on('dragend', function(e) {
                    const pos = e.target.getLatLng();
                    latInput.value = pos.lat.toFixed(7);
                    lngInput.value = pos.lng.toFixed(7);
                });
            }

            if (hasCoords) {
                setMarker(initLat, initLng);
            }

            window.configMap.on('click', function(e) {
                setMarker(e.latlng.lat, e.latlng.lng);
            });

            fetch('/iseller_store/assets/js/comunidades.geojson')
                .then(r => r.json())
                .then(geoData => {
                    allCommunitiesNames = geoData.features.map(f => f.properties.NAME).sort();
                    geojsonLayer = L.geoJSON(geoData, {
                        style: {
                            opacity: 0,
                            fillOpacity: 0,
                            weight: 0
                        }
                    }).addTo(window.configMap);
                })
                .catch(() => {});

            comunidadInput.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase();
                if (query.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }
                const matches = allCommunitiesNames.filter(c => c.toLowerCase().includes(query));
                if (matches.length > 0) {
                    resultsDiv.innerHTML = matches.map(m => `<div class="community-item">${m}</div>`).join('');
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.style.display = 'none';
                }
            });

            resultsDiv.addEventListener('click', function(e) {
                const item = e.target.closest('.community-item');
                if (!item) return;
                const name = item.textContent;
                comunidadInput.value = name;
                comunidadHidden.value = name;
                resultsDiv.style.display = 'none';
                if (geojsonLayer) {
                    geojsonLayer.eachLayer(layer => {
                        if (layer.feature.properties.NAME.toUpperCase() === name.toUpperCase()) {
                            window.configMap.fitBounds(layer.getBounds(), {
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
            });

            document.addEventListener('click', function(e) {
                if (!comunidadInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });

            // Geolocalización (solo si no hay coordenadas guardadas)
            if (!hasCoords && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(pos) {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    if (lat >= 0.6 && lat <= 6.5 && lng >= -67.9 && lng <= -64.0) {
                        window.configMap.setView([lat, lng], 15);
                        setMarker(lat, lng);
                    }
                }, function() {});
            }
        });
    </script>

    <script src="../vendors/leaflet/leaflet.js" crossorigin=""></script>
    <script type="module" src="../vendors/ionicons/ionicons.esm.js"></script>
    <script nomodule src="../vendors/ionicons/ionicons.js"></script>
</body>

</html>