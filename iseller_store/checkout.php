<?php

/**
 * Página de Checkout
 * Procesa la compra del carrito
 */

require_once('core/check_maintenance.php');
require_once('core/db.php');
require_once('core/_tasas_cambio.php');
require_once('core/session.php');
require_once('core/venezuela_geo.php');
// carga la calculadora de precios
require_once('core/_calculadrora_precios.php');
require_once('../publico/production/includes/notificaciones.php');
require_once('core/notificaciones_admin.php');

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$_tasas_cache = [];
function getCalculadora(int $bss_id, $recompensas = [])
{
    global $_tasas_cache, $conexion;
    if (!isset($_tasas_cache[$bss_id])) {
        $stmt = $conexion->prepare("SELECT * FROM cambio WHERE bss_id = ? LIMIT 1");
        $stmt->bind_param('i', $bss_id);
        $stmt->execute();
        $cambioRow = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt2 = $conexion->prepare("SELECT tasas FROM cambios_mostrados WHERE bss_id = ? LIMIT 1");
        $stmt2->bind_param('i', $bss_id);
        $stmt2->execute();
        $mostradosRow = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $data_monedas_neg = $mostradosRow ? json_decode($mostradosRow['tasas'], true) : [];

        $bcvRes = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
        $bcvRow = $bcvRes ? $bcvRes->fetch_assoc() : null;
        $bcv_val = $bcvRow ? (float)$bcvRow['valor'] : 0;

        $_tasas_cache[$bss_id] = [
            'pesoDolar'    => (float)($cambioRow['pesoDolar'] ?? 0),
            'peso_bolivar' => (float)($cambioRow['peso_bolivar'] ?? 0),
            'bolivar_peso' => (float)($cambioRow['bolivar_peso'] ?? 0),
            'dolarBolivar' => (float)($cambioRow['DolarBolivar'] ?? 0),
            'bcv'          => $bcv_val,
            'data_monedas' => $data_monedas_neg,
        ];
    }
    $t = $_tasas_cache[$bss_id];
    return new CalculadoraPrecios(
        $t['pesoDolar'],
        $t['peso_bolivar'],
        $t['dolarBolivar'],
        $t['bolivar_peso'],
        $t['bcv'],
        $t['data_monedas'],
        $recompensas
    );
}

// Tasa global para el frontend
$dolarBolivar = 0;
$bcvRes2 = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
if ($bcvRes2 && $row2 = $bcvRes2->fetch_assoc()) {
    $dolarBolivar = (float)$row2['valor'];
}

// nivel del usuario
$getUserLevel = getUserLevel();
$nivelUsuario = 'Bronce';
$puntosUsuario = 0;
$userFoto = null;
if (@$getUserLevel) {
    $nivelUsuario = $getUserLevel[0];
    $puntosUsuario = $getUserLevel[1];
    $stmtFoto = $conexion_store->prepare("SELECT foto FROM tienda_usuarios WHERE id = ?");
    $uid = getUserId();
    $stmtFoto->bind_param("i", $uid);
    $stmtFoto->execute();
    $resFoto = $stmtFoto->get_result();
    $userFotoRow = $resFoto->fetch_assoc();
    $userFoto = $userFotoRow['foto'] ?? null;
    $stmtFoto->close();
}

$nombreCompleto = htmlspecialchars(getUserName() ?? '');
$primerNombre = explode(" ", $nombreCompleto)[0];
$primerNombre = ucfirst(strtolower($primerNombre));

// --- FETCH STORE CONFIGURATION ---
$store_config = [
    'horario' => 'Lunes a Sábado, 9:00 AM - 9:00 PM',
    'horario_delivery' => '8:00 AM - 11:30 AM / 2:00 PM - 6:00 PM',
    'direccion' => 'Av. Principal'
];

$sqlConfig = "SELECT horario_atencion, horario_delivery, direccion FROM tienda_configuracion LIMIT 1";
$resConfig = $conexion_store->query($sqlConfig);
if ($resConfig && $rowConfig = $resConfig->fetch_assoc()) {
    if (!empty($rowConfig['horario_atencion'])) $store_config['horario'] = $rowConfig['horario_atencion'];
    if (!empty($rowConfig['horario_delivery'])) $store_config['horario_delivery'] = $rowConfig['horario_delivery'];
    if (!empty($rowConfig['direccion'])) $store_config['direccion'] = $rowConfig['direccion'];
}

/**
 * Envía notificación de nueva compra al email configurado en negocio.notificacion_email
 */
function enviarNotificacionCompra($orden_id, $user_id, $total_dolares, $num_operacion, $hora_operacion, $tipo_entrega, $bss_id)
{
    global $conexion;

    // Obtener el email del usuario configurado para notificaciones en este negocio
    $to = null;
    $stmtEmail = $conexion->prepare(
        "SELECT u.email FROM negocio n
         INNER JOIN usuarios u ON u.id = CAST(n.notificacion_email AS UNSIGNED) AND u.bss_id = n.id
         WHERE n.id = ?
           AND n.notificacion_email IS NOT NULL AND n.notificacion_email != ''
           AND u.email IS NOT NULL AND u.email != ''
         LIMIT 1"
    );
    $stmtEmail->bind_param("i", $bss_id);
    $stmtEmail->execute();
    $resEmail = $stmtEmail->get_result();
    if ($rowEmail = $resEmail->fetch_assoc()) {
        $to = $rowEmail['email'];
    }
    $stmtEmail->close();

    if (!$to) return; // Sin destinatario configurado, no se envía

    // Datos del cliente que realizó la compra
    global $conexion_store;
    $stmt = $conexion_store->prepare("SELECT nombre, email, telefono FROM tienda_usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $nombre_cliente   = htmlspecialchars($usuario['nombre']   ?? 'N/A');
    $email_cliente    = htmlspecialchars($usuario['email']    ?? 'N/A');
    $telefono_cliente = htmlspecialchars($usuario['telefono'] ?? 'N/A');
    $total_fmt        = '$' . number_format($total_dolares, 2);
    $entrega_label    = ($tipo_entrega === 'delivery') ? '🚚 Delivery' : '🏪 Retiro en Tienda';
    $panel_url        = 'https://iseller-tiendas.com/publico/production/tienda_pedidos.php';

    $from    = 'nuevas-compras@iseller-tiendas.com';
    $subject = "🛒 Nueva Compra - Orden #$orden_id | iSeller Tiendas";

    $body = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva Compra - Orden #$orden_id</title>
</head>
<body style="margin:0;padding:0;background:#f4f6f8;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:30px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.07);">

          <!-- Encabezado -->
          <tr>
            <td style="background:linear-gradient(135deg,#1a7f4b 0%,#145a35 100%);padding:28px 32px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.5px;">iSeller Tiendas</h1>
              <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">Panel Administrativo · Notificación de Compra</p>
            </td>
          </tr>

          <!-- Cuerpo -->
          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 6px;font-size:15px;color:#111827;font-weight:600;">Se ha registrado una nueva compra 🎉</p>
              <p style="margin:0 0 24px;font-size:13px;color:#6b7280;">Entra al panel y verifica la orden para procesarla a tiempo.</p>

              <!-- Badge orden -->
              <div style="background:#f0f7f3;border:1px solid #c8ddd4;border-radius:8px;padding:14px 18px;margin-bottom:22px;display:inline-block;width:100%;box-sizing:border-box;">
                <span style="font-size:12px;color:#1a7f4b;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;">Orden</span>
                <p style="margin:4px 0 0;font-size:22px;font-weight:800;color:#145a35;">#$orden_id</p>
              </div>

              <!-- Tabla de datos -->
              <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:22px;">
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;width:45%;">Cliente</td>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;font-weight:500;">$nombre_cliente</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Email</td>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;">$email_cliente</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Teléfono</td>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;">$telefono_cliente</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Total</td>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:15px;color:#1a7f4b;font-weight:700;">$total_fmt</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Ref. Pago</td>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;font-family:monospace;">$num_operacion</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Fecha/Hora</td>
                  <td style="padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:13px;color:#111827;">$hora_operacion</td>
                </tr>
                <tr>
                  <td style="padding:10px 0;font-size:12px;color:#9ca3af;font-weight:600;text-transform:uppercase;">Entrega</td>
                  <td style="padding:10px 0;font-size:13px;color:#111827;font-weight:600;">$entrega_label</td>
                </tr>
              </table>

              <!-- Botón al panel -->
              <div style="text-align:center;margin-top:8px;">
                <a href="$panel_url" style="display:inline-block;padding:13px 32px;background:linear-gradient(135deg,#1a7f4b 0%,#145a35 100%);color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:700;letter-spacing:-0.2px;">
                  Ir al Panel → Ver Orden #$orden_id
                </a>
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#f9fafb;border-top:1px solid #f3f4f6;padding:18px 32px;text-align:center;">
              <p style="margin:0;font-size:11px;color:#9ca3af;">Este correo fue generado automáticamente por <strong>iSeller Tiendas</strong>.<br>No responder directamente a este mensaje.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: iSeller Tiendas <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    @mail($to, $subject, $body, $headers);
}

/**
 * Asegura las columnas de ubicación de las direcciones (migración 009).
 * El proyecto no tiene runner de migraciones: cada entorno las obtiene en el
 * primer uso. Aditivo y nullable, no toca datos existentes.
 */
function asegurarColumnasDirecciones($conexion): void
{
    static $listo = false;
    if ($listo) return;

    $res = $conexion->query("SHOW COLUMNS FROM tienda_usuarios_direcciones");
    if (!$res) return; // La tabla no existe: el INSERT fallará y se informa abajo.

    $existentes = [];
    while ($col = $res->fetch_assoc()) {
        $existentes[strtolower($col['Field'])] = true;
    }
    $faltantes = [
        'comunidad' => 'VARCHAR(100) NULL',
        'geo_estado' => 'VARCHAR(100) NULL',
        'geo_municipio' => 'VARCHAR(150) NULL',
        'delivery_gratis_confirmado' => 'TINYINT(1) NOT NULL DEFAULT 0',
    ];
    foreach ($faltantes as $nombre => $definicion) {
        if (!isset($existentes[$nombre])) {
            $conexion->query("ALTER TABLE tienda_usuarios_direcciones ADD COLUMN `$nombre` $definicion");
        }
    }
    $listo = true;
}

// Procesar solicitudes POST (API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión']);
        exit;
    }

    $user_id = getUserId();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Error de validación CSRF']);
        exit;
    }

    $action = $input['action'] ?? '';

    // Cualquier fallo de BD/PDO debe responder JSON, nunca un 500 con body vacío.
    try {

    // 1. Obtener direcciones
    if ($action === 'get_addresses') {
        asegurarColumnasDirecciones($conexion_store);
        $stmt = $conexion_store->prepare("SELECT * FROM tienda_usuarios_direcciones WHERE usuario_id = ? ORDER BY es_principal DESC, creado_en DESC");
        if (!$stmt) {
            error_log('checkout get_addresses: ' . $conexion_store->error);
            echo json_encode(['success' => false, 'message' => 'No se pudieron cargar las direcciones']);
            exit;
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $direcciones = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'addresses' => $direcciones], JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    // 2. Guardar nueva dirección
    if ($action === 'save_address') {
        asegurarColumnasDirecciones($conexion_store);
        $nombre    = trim($input['nombre_receptor'] ?? '');
        $telefono  = trim($input['telefono'] ?? '');
        $direccion = trim($input['direccion'] ?? '');
        $lat  = $input['lat'] ?? null;
        $lng  = $input['lng'] ?? null;
        $comunidad = $input['comunidad'] ?? null;

        // Solo se aceptan combinaciones estado/municipio de la lista.
        $geo = venezuela_estados_municipios();
        $geo_estado = trim($input['geo_estado'] ?? '');
        $geo_municipio = trim($input['geo_municipio'] ?? '');
        if (!isset($geo[$geo_estado]) || !in_array($geo_municipio, $geo[$geo_estado], true)) {
            echo json_encode(['success' => false, 'message' => 'Selecciona estado y municipio']);
            exit;
        }

        if (empty($nombre) || empty($telefono) || empty($direccion)) {
            echo json_encode(['success' => false, 'message' => 'Complete los campos obligatorios']);
            exit;
        }

        $stmt = $conexion_store->prepare("UPDATE tienda_usuarios_direcciones SET es_principal = 0 WHERE usuario_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $delivery_gratis_confirmado = 0;

        $sql = "INSERT INTO tienda_usuarios_direcciones (usuario_id, nombre_receptor, telefono, direccion, geo_estado, geo_municipio, lat, lng, comunidad, delivery_gratis_confirmado, es_principal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conexion_store->prepare($sql);
        if (!$stmt) {
            // Normalmente falta la migración 009 (columnas geo_*): sin el chequeo,
            // bind_param() sobre false terminaba en un 500 sin cuerpo.
            error_log('checkout save_address: ' . $conexion_store->error);
            echo json_encode(['success' => false, 'message' => 'No se pudo guardar la dirección. Revisa la migración 009_direcciones_ubicacion.sql']);
            exit;
        }
        $stmt->bind_param("isssssddsi", $user_id, $nombre, $telefono, $direccion, $geo_estado, $geo_municipio, $lat, $lng, $comunidad, $delivery_gratis_confirmado);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'Dirección guardada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conexion_store->error]);
        }
        exit;
    }

    // 3. Procesar Compra
    if (isset($input['carrito'])) {
        $carrito = $input['carrito'];
        if (!is_array($carrito) || count($carrito) === 0) {
            echo json_encode(['success' => false, 'message' => 'El carrito está vacío']);
            exit;
        }
        $address_id = (int)($input['address_id'] ?? 0);
        if (($input['tipo_entrega'] ?? '') === 'delivery') {
            if (!$address_id) {
                echo json_encode(['success' => false, 'message' => 'Debe seleccionar una dirección de entrega']);
                exit;
            }
            // La dirección debe ser del cliente que compra (evita IDOR).
            $stmtAddr = $conexion_store->prepare("SELECT id FROM tienda_usuarios_direcciones WHERE id = ? AND usuario_id = ? LIMIT 1");
            $stmtAddr->bind_param("ii", $address_id, $user_id);
            $stmtAddr->execute();
            $addrOk = $stmtAddr->get_result()->num_rows > 0;
            $stmtAddr->close();
            if (!$addrOk) {
                echo json_encode(['success' => false, 'message' => 'Dirección de entrega inválida']);
                exit;
            }
        } else {
            $address_id = 0; // Retiro en tienda: no se guarda dirección
        }
        if (empty($input['payment_ref'])) {
            echo json_encode(['success' => false, 'message' => 'Faltan datos del pago']);
            exit;
        }

        $payment_raw = $input['payment_ref'];
        $payment_method = (int)($input['payment_method'] ?? 0);

        $parts = explode('|', $payment_raw);
        $num_operacion  = mb_substr(trim($parts[0] ?? ''), 0, 100);
        $hora_operacion = mb_substr(trim($parts[1] ?? date('Y-m-d H:i:s')), 0, 50);
        if ($num_operacion === '') {
            echo json_encode(['success' => false, 'message' => 'Falta la referencia del pago']);
            exit;
        }

        // Precios, stock y tienda se recalculan SIEMPRE en el servidor: el navegador
        // solo aporta qué producto y cuánta cantidad. Nunca se confía en el precio
        // que llega del carrito.
        $bss_id = (int)($carrito[0]['bss_id'] ?? 0);
        if ($bss_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Tienda inválida']);
            exit;
        }
        $calculadora = getCalculadora($bss_id);

        $total_dolares  = 0;
        $total_pesos    = 0;
        $total_bolivares = 0;
        $items = [];

        $stmtProd = $conexion->prepare(
            "SELECT p.id, p.nombre, p.precio_compra, p.cantidad_unidades, p.origen, p.mayor,
                    s.stock, s.porcentaje, s.bss_id, s.id_sucursal
             FROM productos p
             INNER JOIN negocio_productos np ON p.id = np.producto_id AND np.estado_publicacion = 1
             INNER JOIN negocio n ON np.negocio_id = n.id
             INNER JOIN stock s ON p.id = s.id_producto AND s.bss_id = n.id
                  AND (np.sucursal_id IS NULL OR s.id_sucursal = np.sucursal_id)
             WHERE p.id = ? AND n.id = ? AND s.id_sucursal = ?
               AND p.activo = 0 AND p.origen != 'c'
               AND n.tiene_tienda_virtual = 1 AND n.estado = 'activo'
             LIMIT 1"
        );
        if (!$stmtProd) {
            echo json_encode(['success' => false, 'message' => 'No se pudo validar el carrito']);
            exit;
        }

        foreach ($carrito as $item) {
            $pid = (int)($item['id'] ?? 0);
            $qty = (float)($item['qty'] ?? 0);
            $sid = (int)($item['id_sucursal'] ?? 0);
            $clave = $pid . '-' . $sid;

            if ($pid <= 0 || $sid <= 0 || !is_finite($qty) || $qty <= 0) {
                echo json_encode(['success' => false, 'message' => 'Producto o cantidad inválida']);
                exit;
            }
            if (isset($items[$clave])) {
                echo json_encode(['success' => false, 'message' => 'Producto duplicado en el carrito']);
                exit;
            }

            $stmtProd->bind_param("iii", $pid, $bss_id, $sid);
            $stmtProd->execute();
            $prod = $stmtProd->get_result()->fetch_assoc();
            if (!$prod) {
                echo json_encode(['success' => false, 'message' => 'Uno de los productos ya no está disponible']);
                exit;
            }
            if ((float)$prod['stock'] < $qty) {
                echo json_encode(['success' => false, 'message' => 'No hay stock suficiente de ' . $prod['nombre']]);
                exit;
            }

            $precios = $calculadora->calcularPrecios($prod);
            $items[$clave] = [
                'id'        => $pid,
                'qty'       => $qty,
                'sucursal'  => $sid,
                'precio'    => (float)$precios['precio_venta_dolar'],
                'precio_bs' => (float)$precios['precio_venta_bs'],
                'precio_cop' => (float)$precios['precio_venta_peso'],
            ];

            $total_dolares   += floor(round($precios['precio_venta_dolar'] * $qty * 1e6) / 1e4) / 100;
            $total_pesos     += floor(round($precios['precio_venta_peso'] * $qty * 1e6) / 1e4) / 100;
            $total_bolivares += floor(round($precios['precio_venta_bs'] * $qty * 1e6) / 1e4) / 100;
        }
        $stmtProd->close();

        // Todas las líneas quedan asociadas a la misma tienda validada arriba.
        $items = array_values($items);
        $id_sucursal = (int)$items[0]['sucursal'];

        // --- Calcular costo de envío según config del negocio ---
        $tipo_entrega       = $input['tipo_entrega'] ?? '';
        $costo_envio_dolares = 0;
        $costo_envio_bs     = 0;
        $importe_envio      = 0;
        $importe_envio_bs   = 0;

        if ($tipo_entrega !== 'retiro_tienda') {
            $stmtDel = $conexion->prepare("SELECT config_delivery FROM negocio WHERE id = ?");
            $stmtDel->bind_param("i", $bss_id);
            $stmtDel->execute();
            $resDel = $stmtDel->get_result();
            if ($rowDel = $resDel->fetch_assoc()) {
                $config_delivery = json_decode($rowDel['config_delivery'] ?? '{}', true);
                if (isset($config_delivery['tipo'])) {
                    // Gratis desde un mínimo: si el pedido no lo alcanza se cobra el costo.
                    // Pagado: se cobra siempre (el mínimo no aplica).
                    $minimo_gratis = floatval($config_delivery['minimo'] ?? 0);
                    $cobra_envio = $config_delivery['tipo'] === 'pago'
                        || ($config_delivery['tipo'] === 'gratis' && $minimo_gratis > 0 && $total_dolares < $minimo_gratis);
                    if ($cobra_envio) {
                        $costo_envio_dolares = floatval($config_delivery['costo'] ?? 0);
                        $costo_envio_bs = $costo_envio_dolares * $calculadora->getTasa('bcv');
                        $total_dolares   += $costo_envio_dolares;
                        $total_bolivares += $costo_envio_bs;
                        $total_pesos     += $costo_envio_dolares * $calculadora->getTasa('dolarPeso');
                    }
                }
            }
            $stmtDel->close();
            $importe_envio = $costo_envio_dolares;
            $importe_envio_bs = $costo_envio_bs;
        }

        // 4. Insertar orden
        $status     = 1;
        $fecha      = date('m-Y');
        $created    = date('Y-m-d H:i:s');
        $semana     = date('Y-W');
        $ano        = date('Y');
        $dia        = date('d');
        $tipoPago_id = $payment_method > 0 ? $payment_method : 2;
        $descontado  = "No";

        $tot_usd_str = (string)(floor(round($total_dolares * 1e6) / 1e4) / 100);
        $tot_bs_str  = (string)(floor(round($total_bolivares * 1e6) / 1e4) / 100);
        $tot_cop_str = (string)(floor(round($total_pesos * 1e6) / 1e4) / 100);

        $nombreCompleto2 = htmlspecialchars(getUserName());

        $sql  = "INSERT INTO tienda_ordenes (negocio_id, status, customer_id, total_price, created, modified, fecha, semana, ano, total_price_bs, total_price_cop, cliente, tipoPago, numero_operacion_bancaria, hora_operacion_bancaria, tipo_entrega, dia, descontado, id_sucursal, bss_id, direccion_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $conexion_store->begin_transaction();

        // Una misma referencia de pago no puede generar varias órdenes en la tienda.
        $stmtDup = $conexion_store->prepare("SELECT id FROM tienda_ordenes WHERE bss_id = ? AND numero_operacion_bancaria = ? LIMIT 1 FOR UPDATE");
        if (!$stmtDup) {
            $conexion_store->rollback();
            echo json_encode(['success' => false, 'message' => 'No se pudo validar el pago']);
            exit;
        }
        $stmtDup->bind_param("is", $bss_id, $num_operacion);
        $stmtDup->execute();
        $pagoDuplicado = $stmtDup->get_result()->num_rows > 0;
        $stmtDup->close();
        if ($pagoDuplicado) {
            $conexion_store->rollback();
            echo json_encode(['success' => false, 'message' => 'Esa referencia de pago ya fue usada en otro pedido']);
            exit;
        }

        // Reservar stock de forma atómica (descuenta ya, no al enviar).
        $stmtStock = $conexion_store->prepare("UPDATE stock SET stock = stock - ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? AND stock >= ?");
        if (!$stmtStock) {
            $conexion_store->rollback();
            echo json_encode(['success' => false, 'message' => 'No se pudo reservar el stock']);
            exit;
        }
        foreach ($items as $it) {
            $qtyStock = (float)$it['qty'];
            $pidStock = (int)$it['id'];
            $sidStock = (int)$it['sucursal'];
            $stmtStock->bind_param("diiid", $qtyStock, $pidStock, $sidStock, $bss_id, $qtyStock);
            $stmtStock->execute();
            if ($stmtStock->affected_rows !== 1) {
                $stmtStock->close();
                $conexion_store->rollback();
                echo json_encode(['success' => false, 'message' => 'No hay stock suficiente para completar el pedido']);
                exit;
            }
        }
        $stmtStock->close();
        $descontado = "Si";

        $stmt = $conexion_store->prepare($sql);
        $stmt->bind_param("iiisssssssssssssssssi", $bss_id, $status, $user_id, $tot_usd_str, $created, $created, $fecha, $semana, $ano, $tot_bs_str, $tot_cop_str, $nombreCompleto2, $tipoPago_id, $num_operacion, $hora_operacion, $tipo_entrega, $dia, $descontado, $id_sucursal, $bss_id, $address_id);

        if ($stmt->execute()) {
            $orden_id = $conexion_store->insert_id;
            $stmt->close();

            // FASE 1: espejo en tienda_compras_por_usuarios (historial del perfil del cliente)
            $estatus_map     = ['pendiente', 'en_revision', 'enviada', 'entregada', 'rechazada'];
            $estatus_cliente = $estatus_map[$status - 1] ?? 'pendiente';
            $direccionId     = (int)$address_id ?: null;
            $ganancia_gen    = 0.0;
            $cero_dec        = 0.0;
            $nivel_res       = 0;

            $sql_compra = "INSERT INTO tienda_compras_por_usuarios (
                usuario_id, compra_id, direccion_id, valor_compra, valor_compra_bs,
                numero_operacion_bancaria, hora_operacion_bancaria, ganancia_generada,
                fecha, tipo_entrega, importe_envio, importe_envio_bs, estatus,
                puntos_generados, nivel_resultante, ahorrado, ahorrado_bs, estado
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_compra = $conexion_store->prepare($sql_compra);
            $stmt_compra->bind_param(
                "iiiddssdssddsdidds",
                $user_id,
                $orden_id,
                $direccionId,
                $tot_usd_str,
                $tot_bs_str,
                $num_operacion,
                $hora_operacion,
                $ganancia_gen,
                $created,
                $tipo_entrega,
                $importe_envio,
                $importe_envio_bs,
                $estatus_cliente,
                $cero_dec,
                $nivel_res,
                $cero_dec,
                $cero_dec,
                $estatus_cliente
            );
            if (!$stmt_compra->execute()) {
                $errCompra = $conexion_store->error;
                $stmt_compra->close();
                $conexion_store->rollback();
                echo json_encode(['success' => false, 'message' => 'Error al guardar el historial: ' . $errCompra]);
                exit;
            }
            $stmt_compra->close();

            // 5. Insertar artículos
            $sql_item  = "INSERT INTO tienda_orden_articulos (order_id, product_id, quantity, precio, bolivar, peso, precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_item = $conexion_store->prepare($sql_item);
            if (!$stmt_item) {
                $conexion_store->rollback();
                echo json_encode(['success' => false, 'message' => 'Error al guardar los artículos']);
                exit;
            }

            foreach ($items as $item) {
                $pid    = $item['id'];
                $qty    = (string)$item['qty'];
                $pr_usd = (string)$item['precio'];
                $pr_bs  = (string)$item['precio_bs'];
                $pr_cop = (string)$item['precio_cop'];
                $stmt_item->bind_param("iisssssssii", $orden_id, $pid, $qty, $pr_usd, $pr_bs, $pr_cop, $pr_usd, $pr_bs, $pr_cop, $item['sucursal'], $bss_id);
                if (!$stmt_item->execute()) {
                    $stmt_item->close();
                    $conexion_store->rollback();
                    echo json_encode(['success' => false, 'message' => 'Error al guardar los artículos']);
                    exit;
                }
            }
            $stmt_item->close();
            $conexion_store->commit();

            // Chat automático para el cliente con el mensaje de compra de la tienda
            require_once('core/chat_compra.php');
            chat_compra_crear_conversacion($conexion_store, (int)$user_id, (int)$bss_id, (int)$orden_id);

            enviarNotificacionCompra($orden_id, $user_id, $total_dolares, $num_operacion, $hora_operacion, $tipo_entrega, $bss_id);

            // Notificación in-app al comercio (usuario configurado o, si no hay, sus administradores)
            $notifTitulo = "Nueva compra #$orden_id";
            $notifCuerpo = "Cliente: " . htmlspecialchars(getUserName()) . " | Total: $" . number_format($total_dolares, 2) . " | " . ($tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro en Tienda');
            notificar_admin_tienda($conexion, (int)$bss_id, $notifTitulo, $notifCuerpo, 'compra');

            echo json_encode([
                'success'       => true,
                'orden_id'      => $orden_id,
                'message'       => 'Orden generada con éxito',
                'puntos_ganados' => 0,
                'puntos_totales' => 0,
                'subio_nivel'   => false,
                'nivel_actual'  => 'Bronce'
            ]);
            exit;
        } else {
            $errOrden = $conexion_store->error;
            $conexion_store->rollback();
            $msgOrden = (stripos($errOrden, 'Duplicate entry') !== false)
                ? 'Esa referencia de pago ya fue usada en otro pedido'
                : 'Error al guardar la orden';
            echo json_encode(['success' => false, 'message' => $msgOrden]);
            exit;
        }
    } // Fin if isset carrito

    echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
    } catch (Throwable $e) {
        error_log('checkout POST: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'No se pudo procesar la solicitud. Intenta de nuevo.']);
    }
    exit;
} // Fin if POST

// GET: Verificar sesión
if (!isLoggedIn()) {
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Obtener CSRF token
$csrf_token = generateCSRFToken();

// Tasa de cambio global (fallback)
if ($dolarBolivar == 0) {
    $dolarBolivar = 40;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars(getCSRFToken()); ?>">
    <title>Checkout — iSeller Tiendas</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">
    <link rel="stylesheet" href="../publico/vendors/bootstrap5/css/bootstrap.min.css">
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.8.min.css">
    <link rel="stylesheet" href="../publico/vendors/leaflet/leaflet.css">
    <style>
        @import url('../publico/vendors/fonts/fonts.css');

        :root {
            --brand: #1a7f4b;
            --brand-dark: #145a35;
            --brand-light: #e8f5ee;
            --brand-grad: linear-gradient(135deg, #1a7f4b 0%, #145a35 100%);
            --surface: #f8f9fa;
            --card-bg: #ffffff;
            --border: #e5e7eb;
            --border-focus: #1a7f4b;
            --text-primary: #111827;
            --text-secondary: #374151;
            --text-muted: #6b7280;
            --text-placeholder: #9ca3af;
            --radius-sm: 10px;
            --radius-md: 14px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 1px 3px rgba(0, 0, 0, 0.04);
            --shadow-lg: 0 2px 6px rgba(0, 0, 0, 0.05);
            --ease: cubic-bezier(0.22, 1, 0.36, 1);
            --transition: 0.28s var(--ease);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--surface);
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        /* ══ NAVBAR ══════════════════════════════ */
        .checkout-navbar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 13px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            position: sticky;
            top: 0;
            z-index: 200;
        }

        .checkout-navbar .brand {
            font-weight: 700;
            font-size: 1rem;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .checkout-navbar .brand span {
            color: var(--brand);
        }

        .btn-back {
            width: 36px;
            height: 36px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .btn-back:hover {
            background: var(--brand-light);
            border-color: var(--brand);
            color: var(--brand);
        }

        /* ══ STEP PROGRESS BAR ════════════════════ */
        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 32px 12px;
            gap: 0;
        }

        .step-dot {
            position: relative;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--text-muted);
            transition: var(--transition);
            z-index: 1;
            flex-shrink: 0;
        }

        .step-dot.active {
            background: var(--brand-grad);
            border-color: transparent;
            color: #fff;
            box-shadow: none;
        }

        .step-dot.done {
            background: var(--brand-light);
            border-color: var(--brand);
            color: var(--brand);
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: var(--border);
            max-width: 64px;
            transition: background 0.5s var(--ease);
        }

        .step-line.done {
            background: var(--brand);
        }

        .step-label {
            display: none;
            /* Hidden, dots speak for themselves */
        }

        /* ══ CARDS ════════════════════════════════ */
        .checkout-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            border: 1px solid var(--border);
            padding: 20px;
            margin-bottom: 14px;
        }

        /* ══ CART SELECT CARDS ════════════════════ */
        .cart-select-card {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            background: #fff;
        }

        .cart-select-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(26, 127, 75, 0.05) 0%, transparent 60%);
            opacity: 0;
            transition: opacity var(--transition);
        }

        .cart-select-card:hover {
            border-color: var(--brand);
            transform: translateY(-1px);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .cart-select-card:hover::before {
            opacity: 1;
        }

        .cart-select-card:active {
            transform: translateY(0);
        }

        .store-icon {
            width: 48px;
            height: 48px;
            background: var(--brand-light);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: var(--brand);
            flex-shrink: 0;
        }

        .store-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-primary);
            letter-spacing: -0.01em;
        }

        .store-items-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .cart-total {
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--brand-dark);
        }

        .btn-pay {
            background: var(--brand-grad);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 9px 20px;
            font-weight: 600;
            font-size: 0.88rem;
            cursor: pointer;
            transition: var(--transition);
            white-space: nowrap;
        }

        .btn-pay:hover {
            opacity: 0.92;
        }

        .btn-pay:active {
            transform: translateY(0);
        }

        /* ══ QUICK STORE HEADER ═══════════════════ */
        .store-header-card {
            background: linear-gradient(135deg, #f0f7f3 0%, #e8f5ee 100%);
            border: 1px solid #c8ddd4;
        }

        /* ══ DELIVERY OPTIONS ═════════════════════ */
        .delivery-option {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .delivery-option .check-icon {
            font-size: 1.1rem;
            color: var(--brand);
            transition: var(--transition);
        }

        .delivery-option:hover {
            border-color: var(--brand);
            background: #f0f7f3;
        }

        .delivery-option.selected {
            border-color: var(--brand);
            background: #f0f7f3;
            box-shadow: none;
        }

        .delivery-option .label {
            font-weight: 700;
            font-size: 0.92rem;
        }

        .delivery-option .sublabel {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        /* ══ ADDRESS CARDS ════════════════════════ */
        .address-card {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .address-card:hover {
            border-color: var(--brand);
            background: #f9fffe;
        }

        .address-card.selected {
            border-color: var(--brand);
            background: #f0f7f3;
            box-shadow: none;
        }

        .address-avatar {
            width: 38px;
            height: 38px;
            background: var(--brand-grad);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
        }

        .address-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .address-detail {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .address-check {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--transition);
            margin-left: auto;
        }

        .address-card.selected .address-check {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        /* ══ MAP ══════════════════════════════════ */
        #map {
            height: 220px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 1px solid var(--border);
        }

        /* ══ FORM INPUTS ══════════════════════════ */
        .form-group {
            position: relative;
            margin-bottom: 16px;
        }

        .form-input {
            width: 100%;
            padding: 13px 14px;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 0.9rem;
            font-family: inherit;
            color: var(--text-primary);
            background: #fff;
            transition: var(--transition);
            outline: none;
        }

        .form-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3px rgba(26, 127, 75, 0.15);
        }

        .form-input::placeholder {
            color: var(--text-placeholder);
        }

        .form-label-text {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 6px;
            letter-spacing: 0.01em;
            text-transform: uppercase;
        }

        .form-input-suffix {
            font-size: 0.75rem;
            color: var(--brand);
            font-weight: 500;
            margin-top: 4px;
        }

        /* ══ SUMMARY LINES ════════════════════════ */
        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
            font-size: 0.88rem;
            border-bottom: 1px solid #f3f4f6;
            color: var(--text-secondary);
        }

        .summary-line:last-child {
            border-bottom: none;
        }

        .summary-line.total {
            padding-top: 14px;
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--text-primary);
            border-top: 2px solid var(--border);
            border-bottom: none;
            margin-top: 4px;
        }

        .summary-line.total .total-usd {
            color: var(--brand-dark);
        }

        .shipping-free {
            color: var(--brand);
            font-weight: 600;
        }

        .shipping-note {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .shipping-paid {
            color: #ef4444;
            font-weight: 600;
        }

        .bs-amount {
            font-size: 0.78rem;
            color: var(--text-muted);
            text-align: right;
            margin-top: 4px;
        }

        /* ══ BANK INFO CARDS ══════════════════════ */
        .bank-block {
            background: linear-gradient(135deg, #f8faf9 0%, #f0f7f3 100%);
            border: 1px solid #c8ddd4;
            border-radius: var(--radius-md);
            padding: 18px;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
        }

        .bank-block::after {
            display: none;
        }

        .bank-block-title {
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--brand-dark);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .bank-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .bank-row:last-child {
            margin-bottom: 0;
        }

        .bank-key {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .bank-val {
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .copy-btn {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 7px;
            padding: 3px 9px;
            font-size: 0.72rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
            font-weight: 500;
        }

        .copy-btn:hover {
            background: var(--brand);
            color: #fff;
            border-color: var(--brand);
        }

        /* ══ PICKUP INFO ══════════════════════════ */
        .pickup-info-box {
            background: linear-gradient(135deg, #f0f7f3 0%, #e8f5ee 100%);
            border: 1px solid #c8ddd4;
            border-radius: var(--radius-md);
            padding: 18px;
        }

        .pickup-row {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 0.88rem;
        }

        .pickup-row:last-child {
            margin-bottom: 0;
        }

        .pickup-row i {
            color: var(--brand);
            margin-top: 1px;
            flex-shrink: 0;
        }

        /* ══ BUTTONS ══════════════════════════════ */
        .btn-next {
            background: var(--brand-grad);
            color: #fff;
            border: none;
            border-radius: var(--radius-sm);
            padding: 15px 24px;
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: -0.01em;
        }

        .btn-next:hover:not(:disabled) {
            opacity: 0.92;
        }

        .btn-next:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-next:disabled {
            background: linear-gradient(135deg, #d1d5db, #9ca3af);
            box-shadow: none;
            cursor: not-allowed;
        }

        .btn-back-step {
            width: 48px;
            height: 48px;
            background: #fff;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .btn-back-step:hover {
            border-color: var(--brand);
            color: var(--brand);
            background: var(--brand-light);
        }

        .btn-outline-add {
            background: transparent;
            border: 1.5px dashed var(--brand);
            color: var(--brand);
            border-radius: var(--radius-sm);
            padding: 7px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-outline-add:hover {
            background: var(--brand-light);
        }

        /* ══ SUCCESS STATE ════════════════════════ */
        .success-card {
            text-align: center;
            padding: 40px 24px;
        }

        .success-anim {
            width: 88px;
            height: 88px;
            background: var(--brand-grad);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2.4rem;
            color: #fff;
            box-shadow: none;
            animation: pulse-success 2s ease-in-out infinite;
        }

        @keyframes pulse-success {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.04);
            }
        }

        .success-order-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--brand-light);
            color: var(--brand-dark);
            font-weight: 700;
            font-size: 0.9rem;
            padding: 8px 18px;
            border-radius: 99px;
            border: 1px solid #c8ddd4;
            margin-bottom: 20px;
        }

        .btn-continue {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--brand-grad);
            color: #fff;
            border: none;
            border-radius: 99px;
            padding: 12px 28px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
        }

        .btn-continue:hover {
            opacity: 0.92;
            color: #fff;
        }

        /* ══ SPINNER / LOADING ════════════════════ */
        .checkout-spinner {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
            padding: 24px;
            color: var(--text-muted);
            font-size: 0.88rem;
        }

        .spinner-dot {
            width: 8px;
            height: 8px;
            background: var(--brand);
            border-radius: 50%;
            animation: bounce-dot 1.2s ease-in-out infinite;
        }

        .spinner-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .spinner-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes bounce-dot {

            0%,
            80%,
            100% {
                transform: scale(0.7);
                opacity: 0.4;
            }

            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* ══ WIZARD ═══════════════════════════════ */
        .wizard-step {
            display: none;
            animation: fadeSlideIn 0.32s var(--ease);
        }

        .wizard-step.active {
            display: block;
        }

        @keyframes fadeSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ══ SECTION HEADERS ══════════════════════ */
        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            margin: 0 0 4px;
        }

        .section-subtitle {
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        /* ══ DIVIDER ══════════════════════════════ */
        .divider {
            height: 1px;
            background: var(--border);
            margin: 14px 0;
        }

        /* ══ AVISO DE UBICACIÓN ═══════════════════ */
        .geo-warning {
            display: none;
            position: sticky;
            top: 63px;
            z-index: 150;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 14px;
            padding: 12px 14px;
            border: 1px solid #fde68a;
            border-radius: 12px;
            background: #fffbeb;
            color: #92400e;
            font-size: 0.82rem;
            line-height: 1.45;
            box-shadow: 0 6px 18px -12px rgba(0, 0, 0, 0.35);
        }

        .geo-warning.show {
            display: flex;
        }

        .geo-warning i {
            flex-shrink: 0;
            margin-top: 1px;
            font-size: 1rem;
        }

        .geo-warning strong {
            display: block;
        }

        /* ══ RESPONSIVE ═══════════════════════════ */
        .checkout-wrap {
            max-width: 620px;
            margin: 0 auto;
            padding: 12px 16px 100px;
        }

        @media (max-width: 480px) {
            .checkout-card {
                padding: 16px;
                border-radius: var(--radius-md);
            }

            .step-line {
                max-width: 40px;
            }
        }

        /* ══ PAYMENT METHOD SELECTOR ═══════════════ */
        .payment-method-option {
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .payment-method-option:hover {
            border-color: var(--brand);
            background: #f9fffe;
        }

        .payment-method-option.selected {
            border-color: var(--brand);
            background: #f0f7f3;
            box-shadow: none;
        }

        .payment-method-option .pm-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
            background: var(--brand-light);
            color: var(--brand);
            transition: var(--transition);
        }

        .payment-method-option.selected .pm-icon {
            background: var(--brand-grad);
            color: #fff;
        }

        .payment-method-option .pm-info {
            flex: 1;
            min-width: 0;
        }

        .payment-method-option .pm-name {
            font-weight: 700;
            font-size: 0.92rem;
            color: var(--text-primary);
        }

        .payment-method-option .pm-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .payment-method-option .pm-check {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: var(--transition);
            margin-left: auto;
            align-self: center;
        }

        .payment-method-option.selected .pm-check {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .payment-details-panel {
            display: none;
            animation: fadeSlideIn 0.28s var(--ease);
        }

        .payment-details-panel.active {
            display: block;
        }

        /* ══ COMMUNITY AUTOCOMPLETE ══════════════ */
        .community-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: var(--radius-sm);
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            max-height: 220px;
            overflow-y: auto;
            margin-top: 4px;
        }

        .community-item {
            padding: 9px 14px;
            font-size: 0.85rem;
            color: var(--text-primary);
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s, color 0.15s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .community-item:last-child {
            border-bottom: none;
        }

        .community-item:hover {
            background: #f0f7f3;
            color: var(--brand);
            font-weight: 600;
        }

        .community-item i {
            color: var(--brand);
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="checkout-navbar">
        <div class="container d-flex align-items-center justify-content-between p-0" style="max-width:640px;">
            <div class="d-flex align-items-center gap-3">
                <a href="javascript:history.back()" class="btn-back" title="Volver"><i class="bi bi-chevron-left fs-6"></i></a>
                <div>
                    <div class="brand">iSeller <span>Proceso de pago</span></div>
                    <div class="small text-muted" style="font-size:0.75rem; margin-top:-2px;">Compra 100% segura</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-light text-dark border px-3 py-2 fw-semibold" style="font-size:0.75rem;">
                    <i class="bi bi-shield-check text-success me-1"></i> Protegido
                </span>
            </div>
        </div>
    </nav>

    <!-- Step Indicator -->
    <div class="step-indicator" id="step-indicator-container">
        <div class="step-dot active" id="dot-0" title="Seleccionar Carrito"><i class="bi bi-shop"></i></div>
        <div class="step-line" id="line-01"></div>
        <div class="step-dot" id="dot-1" title="Método de Entrega"><i class="bi bi-geo-alt"></i></div>
        <div class="step-line" id="line-12"></div>
        <div class="step-dot" id="dot-2" title="Pago y Confirmación"><i class="bi bi-credit-card"></i></div>
        <div class="step-line" id="line-23"></div>
        <div class="step-dot" id="dot-3" title="Completado"><i class="bi bi-check2"></i></div>
    </div>

    <div class="checkout-wrap">

        <!-- Aviso fijo: la dirección seleccionada está fuera de la ubicación de la tienda -->
        <div class="geo-warning" id="geo-warning" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
                <strong>Esta tienda no está en esa ubicación.</strong>
                <span id="geo-warning-text"></span>
                Puedes continuar con tu compra, pero la tienda podría no entregar en esa zona.
            </div>
        </div>

        <!-- ── STEP 0: Seleccionar Carrito ── -->
        <div class="wizard-step active" id="step-0">
            <div class="checkout-card">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">¿De qué tienda deseas comprar?</h2>
                        <p class="section-subtitle m-0">Tienes productos de distintas tiendas. Elige una para procesar su entrega y pago.</p>
                    </div>
                </div>
                <div class="row g-3" id="multi-cart-selection">
                    <div class="text-center py-4"><span class="spinner-border text-success"></span></div>
                </div>
            </div>
        </div>

        <!-- ── STEP 1: Entrega y Dirección ── -->
        <div class="wizard-step" id="step-1">

            <!-- Resumen rápido tienda -->
            <div class="checkout-card store-header-card">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="store-icon"><i class="bi bi-shop"></i></div>
                        <div>
                            <div class="store-name" id="header-store-name">Cargando tienda...</div>
                            <div class="store-items-count" id="header-items-count">0 productos en este pedido</div>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted" style="font-size:0.75rem;">Total pedido</div>
                        <div class="cart-total" id="header-total">$0.00</div>
                    </div>
                </div>
            </div>

            <!-- Delivery o Retiro -->
            <div class="checkout-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Método de entrega</h3>
                        <p class="section-subtitle m-0">Selecciona si prefieres recibirlo o pasar a retirarlo</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="delivery-option selected" id="opt-delivery" onclick="setDeliveryType('delivery')">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="store-icon" style="width:38px; height:38px; font-size:1.1rem;"><i class="bi bi-truck"></i></div>
                                <i class="bi bi-check-circle-fill check-icon"></i>
                            </div>
                            <div class="label">Envío a domicilio</div>
                            <div class="sublabel">Te lo llevamos directo a tu puerta</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="delivery-option" id="opt-pickup" onclick="setDeliveryType('retiro_tienda')">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="store-icon" style="width:38px; height:38px; font-size:1.1rem; background:#f1f5f9; color:#64748b;"><i class="bi bi-bag-check"></i></div>
                                <i class="bi bi-check-circle-fill check-icon d-none"></i>
                            </div>
                            <div class="label">Retiro en local</div>
                            <div class="sublabel">Pasa a buscarlo sin costo de envío</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dirección de entrega -->
            <div id="delivery-address-section">
                <div class="checkout-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Dirección de entrega</h3>
                            <p class="section-subtitle m-0">Selecciona o registra dónde te entregamos</p>
                        </div>
                        <button class="btn-outline-add" onclick="mostrarFormDireccion()">
                            <i class="bi bi-plus-lg me-1"></i> Nueva dirección
                        </button>
                    </div>
                    <div id="addresses-container">
                        <div class="checkout-spinner">
                            <div class="spinner-dot"></div>
                            <div class="spinner-dot"></div>
                            <div class="spinner-dot"></div>
                            <span class="ms-2">Cargando tus direcciones...</span>
                        </div>
                    </div>
                </div>

                <!-- Formulario nueva dirección -->
                <div class="checkout-card d-none" id="new-address-form" style="border: 2px solid #c8ddd4; background:#f8faf9;">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title"><i class="bi bi-geo-alt text-success me-1"></i> Registrar nueva dirección</h3>
                            <p class="section-subtitle m-0">Completa los datos de entrega de tu pedido</p>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="form-label-text">¿Quién recibe el pedido? *</label>
                                <input type="text" class="form-input" id="addr-name" placeholder="Nombre y Apellido">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="form-label-text">Teléfono de contacto *</label>
                                <input type="tel" class="form-input" id="addr-phone" placeholder="0414-1234567">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label-text">Dirección exacta / Calle / Casa / Edificio *</label>
                                <input type="text" class="form-input" id="addr-address" placeholder="Ej. Calle 5 con Av. 2, Casa #42">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="form-label-text">Estado *</label>
                                <select class="form-input" id="addr-estado">
                                    <option value="">Seleccione un estado...</option>
                                    <?php foreach (venezuela_estados_municipios() as $estado => $municipios): ?>
                                        <option value="<?= htmlspecialchars($estado) ?>"><?= htmlspecialchars($estado) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label class="form-label-text">Municipio *</label>
                                <select class="form-input" id="addr-municipio">
                                    <option value="">Seleccione un municipio...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group position-relative">
                                <label class="form-label-text">Sector o Urbanización</label>
                                <div class="position-relative">
                                    <input type="text" class="form-input" id="addr-comunidad" placeholder="Escribe tu sector o urbanización..." autocomplete="off">
                                    <span class="position-absolute end-0 top-50 translate-middle-y me-3 text-muted" style="pointer-events:none;">
                                        <i class="bi bi-search" style="font-size:0.85rem;"></i>
                                    </span>
                                </div>
                                <div id="addr-comunidad-results" class="community-results-dropdown" style="display:none;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Mapa -->
                    <div class="mt-2 mb-3">
                        <label class="form-label-text d-flex align-items-center gap-1">
                            <i class="bi bi-pin-map text-success"></i> Ubicación en mapa (Haz clic para colocar el pin)
                        </label>
                        <div id="map"></div>
                        <input type="hidden" id="addr-lat">
                        <input type="hidden" id="addr-lng">
                    </div>

                    <div class="d-flex gap-2 pt-2">
                        <button class="btn-back-step" style="width:auto; padding:12px 20px;" onclick="cancelarFormDireccion()">Cancelar</button>
                        <button class="btn-next flex-fill" onclick="guardarDireccion()">
                            <i class="bi bi-check2-circle"></i> <span id="btn-save-addr-text">Guardar y Usar Dirección</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Retiro en tienda -->
            <div class="checkout-card d-none" id="pickup-info-section">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Punto de retiro</h3>
                        <p class="section-subtitle m-0">Visítanos directamente en nuestro establecimiento</p>
                    </div>
                </div>
                <div class="pickup-info-box">
                    <div class="pickup-row">
                        <i class="bi bi-geo-alt-fill fs-5"></i>
                        <div>
                            <div class="fw-bold">Dirección del local</div>
                            <div class="text-secondary small"><?= htmlspecialchars($store_config['direccion']) ?></div>
                        </div>
                    </div>
                    <div class="divider my-2"></div>
                    <div class="pickup-row">
                        <i class="bi bi-clock-fill fs-5"></i>
                        <div>
                            <div class="fw-bold">Horario de atención al público</div>
                            <div class="text-secondary small"><?= htmlspecialchars($store_config['horario']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen de costos -->
            <div class="checkout-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Resumen del pedido</h3>
                        <p class="section-subtitle m-0">Detalle de productos y cálculo final</p>
                    </div>
                </div>
                <div id="summary-items-list" class="mb-2"></div>
                <div class="summary-line">
                    <span>Subtotal productos</span>
                    <span id="sum-subtotal" class="fw-semibold">$0.00</span>
                </div>
                <div class="summary-line">
                    <span>Costo de entrega</span>
                    <span id="sum-shipping" class="shipping-free">GRATIS</span>
                </div>
                <div class="shipping-note" id="sum-shipping-note"></div>
                <div class="summary-line total">
                    <span>Total a pagar</span>
                    <span id="sum-total" class="total-usd">$0.00</span>
                </div>
                <div class="bs-amount" id="sum-total-bs">0.00 Bs</div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn-back-step" onclick="goToStep(0)"><i class="bi bi-arrow-left"></i></button>
                <button class="btn-next" id="btn-next-1" onclick="goToStep(2)" disabled>
                    Continuar al pago <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ── STEP 2: Datos de pago ── -->
        <div class="wizard-step" id="step-2">

            <!-- Selector de método de pago -->
            <div class="checkout-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">¿Cómo deseas pagar?</h3>
                        <p class="section-subtitle m-0">Selecciona tu método de pago preferido</p>
                    </div>
                </div>
                <div class="row g-3" id="payment-methods-container">
                    <div class="text-center py-4"><span class="spinner-border text-success"></span></div>
                </div>
            </div>

            <!-- Detalle del método seleccionado (cuentas bancarias) -->
            <div class="payment-details-panel" id="payment-details-panel">
                <div class="checkout-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title" id="bank-section-title">Datos bancarios</h3>
                            <p class="section-subtitle m-0" id="bank-section-subtitle">Realiza tu pago con los datos mostrados</p>
                        </div>
                    </div>
                    <div id="bank-info-container">
                    </div>
                </div>
            </div>

            <!-- Referencia de pago -->
            <div class="payment-details-panel" id="payment-ref-panel">
                <div class="checkout-card">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Reportar comprobante</h3>
                            <p class="section-subtitle m-0">Ingresa el número de referencia para verificar tu pago</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-7">
                            <div class="form-group m-0">
                                <label class="form-label-text">N° Referencia / Confirmación *</label>
                                <input type="text" class="form-input" id="payment-ref" placeholder="Ej. 654321 / Últimos 6 u 8 dígitos">
                            </div>
                        </div>
                        <div class="col-12 col-md-5">
                            <div class="form-group m-0">
                                <label class="form-label-text">Fecha de la transacción *</label>
                                <input type="date" class="form-input" id="payment-date">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen final -->
            <div class="checkout-card">
                <div class="section-header">
                    <div>
                        <h3 class="section-title">Total a transferir</h3>
                        <p class="section-subtitle m-0">Revisa el monto exacto antes de confirmar</p>
                    </div>
                </div>
                <div id="cart-items-payment" class="mb-2"></div>
                <div class="summary-line">
                    <span>Subtotal</span>
                    <span id="pay-subtotal" class="fw-semibold">$0.00</span>
                </div>
                <div class="summary-line">
                    <span>Costo de entrega</span>
                    <span id="pay-shipping" class="shipping-free">GRATIS</span>
                </div>
                <div class="shipping-note" id="pay-shipping-note"></div>
                <div class="summary-line total">
                    <span>Total en Dólares</span>
                    <span id="pay-total-usd" class="total-usd">$0.00</span>
                </div>
                <div class="bs-amount" id="pay-total-bs">0.00 Bs</div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn-back-step" onclick="goToStep(1)"><i class="bi bi-chevron-left"></i></button>
                <button class="btn-next flex-fill" id="btn-finish" onclick="finalizarCompra()">
                    <i class="bi bi-shield-lock-fill"></i> Confirmar Pedido
                </button>
            </div>
        </div>

        <!-- ── STEP 3: Éxito ── -->
        <div class="wizard-step" id="step-success">
            <div class="checkout-card success-card">
                <div class="success-anim">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2 class="fw-bold mb-2" style="font-size:1.6rem; letter-spacing:-0.02em;">¡Pedido recibido con éxito!</h2>
                <p class="text-muted mb-3" style="max-width:380px; margin:0 auto; font-size:0.92rem;">
                    Tu orden ha sido enviada a la tienda para su preparación. Te contactarán en breve.
                </p>
                <div>
                    <div class="success-order-badge">
                        <i class="bi bi-receipt-cutoff"></i> Orden de compra #<span id="success-order-id"></span>
                    </div>
                </div>
                <div id="estimated-time-container" class="d-none mb-4">
                    <div class="pickup-info-box text-start">
                        <div class="pickup-row">
                            <i class="bi bi-clock-history fs-5 text-success"></i>
                            <div>
                                <div class="fw-bold">Estimación de entrega</div>
                                <div id="estimated-time-msg" class="text-secondary small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Acción para procesar otros carritos pendientes si los hay -->
                <div id="pending-carts-action" class="d-none mb-3">
                    <div class="alert alert-warning text-start d-flex align-items-center gap-3 border-0 rounded-3 mb-3" style="background:#fffbeb; color:#92400e; border: 1px solid #fef3c7;">
                        <i class="bi bi-bag-plus fs-2 text-warning"></i>
                        <div class="small">
                            <strong>¡Aún tienes pedidos pendientes!</strong><br>
                            Tienes <span id="remaining-carts-text" class="fw-bold">1 carrito</span> de otra tienda pendiente por procesar.
                        </div>
                    </div>
                    <button type="button" class="btn-next w-100 mb-2" onclick="verOtrosCarritos()">
                        <i class="bi bi-cart3"></i> Procesar siguiente carrito pendiente
                    </button>
                </div>
                <div>
                    <a href="../" class="btn-continue" id="btn-back-to-store">
                        <i class="bi bi-arrow-left"></i> Volver a la tienda
                    </a>
                </div>
            </div>
        </div>

    </div><!-- /checkout-wrap -->

    <!-- Scripts -->
    <script src="../publico/vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-3.2.8.min.js"></script>
    <script src="../publico/vendors/dexie/dexie.min.js"></script>
    <script src="../publico/vendors/leaflet/leaflet.js"></script>
    <script src="../publico/vendors/leaflet/leaflet-pip.js"></script>
    <script>
        // ── IndexedDB (Dexie) ──────────────────────────────────────────
        // Must match EXACTLY what index.php uses: POS_DB version 2
        const db = new Dexie('POS_DB');
        db.version(2).stores({
            carritoActivo: 'id',
            carritosVenta: 'id',
            carritosReservados: 'id',
            cart_meta: 'id'
        });

        // Mismo motivo que en index.php: un POS_DB en versión mayor rompe la lectura.
        async function abrirDbCarrito() {
            try {
                await db.open();
            } catch (err) {
                if (err.name !== 'VersionError') throw err;
                await Dexie.delete('POS_DB');
                await db.open();
            }
        }

        // ── Estado Global ──────────────────────────────────────────────
        let allCartItems = [];
        let cartItems = [];
        let cartGroups = {};
        let currentBssId = null;
        let selectedAddressId = null;
        let deliveryType = 'delivery';
        let tiendaConfig = null;
        let mapInstance = null;
        let markerInstance = null;
        let selectedPaymentMethod = null;
        let addressesById = {};
        const GEO_VENEZUELA = <?= json_encode(venezuela_estados_municipios(), JSON_UNESCAPED_UNICODE) ?>;

        // ── Utilidades ────────────────────────────────────────────────
        function formatNumber(n) {
            if (n === null || n === undefined || isNaN(n)) return '0.00';
            return Number(n).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function recortarADosDecimales(n) {
            return Math.floor(Math.round(parseFloat(n) * 1e6) / 1e4) / 100;
        }

        // Escapa texto antes de meterlo con innerHTML (nombres, datos bancarios, etc.).
        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function(c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        // ── Envío ─────────────────────────────────────────────────────
        // Gratis desde un mínimo: por debajo se cobra el costo. Pagado: siempre se
        // cobra. Misma regla que el servidor.
        function calcularEnvio(subtotalUsd) {
            const dc = tiendaConfig?.config_delivery;
            if (deliveryType !== 'delivery' || !dc) {
                return { usd: 0, bs: 0 };
            }
            const minimo = parseFloat(dc.minimo || 0);
            const cobra = dc.tipo === 'pago' || (dc.tipo === 'gratis' && minimo > 0 && subtotalUsd < minimo);
            if (!cobra) {
                return { usd: 0, bs: 0 };
            }
            const usd = parseFloat(dc.costo || 0);
            return { usd: usd, bs: usd * <?php echo $bcv; ?> };
        }

        // Aviso de cuánto falta para el envío gratis (vacío si no aplica).
        function notaEnvio(subtotalUsd) {
            const dc = tiendaConfig?.config_delivery;
            if (deliveryType !== 'delivery' || dc?.tipo !== 'gratis') return '';
            const minimo = parseFloat(dc.minimo || 0);
            if (minimo <= 0 || subtotalUsd >= minimo) return '';
            return 'Te faltan $' + formatNumber(minimo - subtotalUsd) + ' para envío gratis';
        }

        // ── Wizard ────────────────────────────────────────────────────
        function goToStep(n) {
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + (n === 'success' ? 'success' : n)).classList.add('active');
            updateStepDots(n);
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

            if (n === 0 || n === 'success') {
                document.getElementById('geo-warning')?.classList.remove('show');
            }
            if (n === 1) {
                cargarDirecciones();
                actualizarResumen();
                actualizarAdvertenciaUbicacion();
            }
            if (n === 2) {
                renderPaymentInfo();
                renderPaymentSummary();
            }
        }

        function updateStepDots(current) {
            const nums = [0, 1, 2, 3];
            nums.forEach(n => {
                const dot = document.getElementById('dot-' + n);
                if (!dot) return;
                dot.classList.remove('active', 'done');
                if (n < current) dot.classList.add('done');
                else if (n === current) dot.classList.add('active');
            });
            ['01', '12', '23'].forEach((pair, i) => {
                const line = document.getElementById('line-' + pair);
                if (line) line.classList.toggle('done', i < current);
            });
        }

        // ── Carga del Carrito ─────────────────────────────────────────
        async function cargarCarrito() {
            const container = document.getElementById('multi-cart-selection');

            try {
                await abrirDbCarrito();
                allCartItems = await db.carritoActivo.toArray();
            } catch (e) {
                console.error('[Checkout] DB read error:', e);
                allCartItems = [];
                // Try wiping corrupt DB
                // DB error - could not read cart
            }

            console.log('[Checkout] Items loaded from DB:', allCartItems.length, allCartItems);

            if (!allCartItems || allCartItems.length === 0) {
                container.innerHTML = `<div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-cart-x fs-2 mb-2 d-block"></i>
            Tu carrito está vacío<br>
            <a href="../" class="btn btn-sm btn-outline-success mt-3">Ir a la tienda</a>
        </div>`;
                return;
            }

            // Agrupar por bss_id — normalizar a String para evitar mismatch int/string
            cartGroups = {};
            for (let item of allCartItems) {
                const key = String(item.bss_id ?? 0);
                if (!cartGroups[key]) {
                    cartGroups[key] = {
                        bss_id: item.bss_id,
                        tienda_nombre: item.tienda_nombre || item.storeName || ('Tienda #' + key),
                        items: [],
                        total_dolares: 0
                    };
                }
                cartGroups[key].items.push(item);
                cartGroups[key].total_dolares = recortarADosDecimales(cartGroups[key].total_dolares + recortarADosDecimales((parseFloat(item.price) || 0) * (parseFloat(item.qty) || 1)));
            }

            renderMultiCartSelection(cartGroups);
        }

        function renderMultiCartSelection(grouped) {
            const container = document.getElementById('multi-cart-selection');
            const keys = Object.keys(grouped);

            if (keys.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-5 text-muted"><i class="bi bi-cart-x fs-2 d-block mb-2"></i>Carrito vacío</div>';
                return;
            }

            // Si solo hay una tienda, ir directo al paso 1
            if (keys.length === 1) {
                seleccionarCarrito(keys[0]);
                return;
            }

            // Múltiples tiendas → mostrar tarjetas
            let html = '';
            for (let bss in grouped) {
                const group = grouped[bss];
                html += `
            <div class="col-12">
                <div class="checkout-card p-3 cart-select-card" onclick="seleccionarCarrito('${bss}')">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="store-icon"><i class="bi bi-shop"></i></div>
                            <div>
                                <div class="store-name">${escapeHtml(group.tienda_nombre)}</div>
                                <div class="store-items-count">${group.items.length} producto(s)</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="cart-total">$${formatNumber(recortarADosDecimales(group.total_dolares))}</div>
                            <button class="btn-pay" onclick="event.stopPropagation();seleccionarCarrito('${bss}')">
                                Pagar <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
            }
            container.innerHTML = html;
        }

        async function seleccionarCarrito(bss_id) {
            currentBssId = String(bss_id);
            // Filtrar items del carrito seleccionado (de la copia en memoria)
            const bssNum = parseInt(bss_id);
            cartItems = allCartItems.filter(i => parseInt(i.bss_id) === bssNum || String(i.bss_id) === String(bss_id));
            console.log('[Checkout] Seleccionado bss_id=' + bss_id + ', items:', cartItems.length);

            // Obtener configuración de la tienda (delivery + datos bancarios)
            try {
                const resp = await fetch(`api/tienda_info.php?negocio_id=${bss_id}`);
                tiendaConfig = await resp.json();
            } catch (e) {
                tiendaConfig = {};
            }

            // Actualizar encabezado del paso 1
            const group = cartGroups[bss_id] || {};
            document.getElementById('header-store-name').textContent = group.tienda_nombre || 'Tienda';
            document.getElementById('header-items-count').textContent = cartItems.length + ' producto(s)';

            goToStep(1);
        }

        // ── Direcciones ───────────────────────────────────────────────
        async function leerRespuestaJson(res) {
            const texto = await res.text();
            try {
                return JSON.parse(texto);
            } catch (e) {
                console.error('[Checkout] Respuesta no JSON:', res.status, texto);
                return {
                    success: false,
                    message: 'El servidor no respondió correctamente (' + res.status + ')'
                };
            }
        }

        async function cargarDirecciones() {
            const container = document.getElementById('addresses-container');
            addressesById = {};
            container.innerHTML = `<div class="checkout-spinner">
        <div class="spinner-dot"></div><div class="spinner-dot"></div><div class="spinner-dot"></div>
        <span class="ms-2">Cargando direcciones...</span>
    </div>`;

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch('checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_addresses',
                    csrf_token: csrf
                })
            });
            const data = await leerRespuestaJson(res);

            if (!data.success) {
                container.innerHTML = `<div class="text-center py-4 text-danger">
            <i class="bi bi-exclamation-triangle fs-2 mb-2 d-block"></i>
            <div class="fw-semibold small">${escapeHtml(data.message || 'No se pudieron cargar las direcciones')}</div>
        </div>`;
                document.getElementById('btn-next-1').disabled = (deliveryType === 'delivery');
                return;
            }

            if (!Array.isArray(data.addresses) || data.addresses.length === 0) {
                container.innerHTML = `<div class="text-center py-4 text-muted">
            <i class="bi bi-geo-alt fs-2 mb-2 d-block text-secondary opacity-50"></i>
            <div class="fw-semibold small">No tienes direcciones guardadas</div>
            <div class="text-muted small mb-3">Agrega una para poder recibir tu pedido por delivery</div>
            <button class="btn-outline-add" onclick="mostrarFormDireccion()"><i class="bi bi-plus-lg me-1"></i> Agregar dirección</button>
        </div>`;
                document.getElementById('btn-next-1').disabled = (deliveryType === 'delivery');
                document.getElementById('geo-warning')?.classList.remove('show');
                return;
            }

            let html = '';
            for (let addr of data.addresses) {
                addressesById[String(addr.id)] = addr;
                const initial = (addr.nombre_receptor || 'D').charAt(0).toUpperCase();
                html += `
            <div class="address-card ${addr.es_principal ? 'selected' : ''}"
                 onclick="seleccionarDireccion(${addr.id}, this)">
                <div class="address-avatar">${escapeHtml(initial)}</div>
                <div class="flex-grow-1">
                    <div class="address-name">${escapeHtml(addr.nombre_receptor)}</div>
                    <div class="address-detail">${escapeHtml(addr.direccion)}</div>
                    ${addr.geo_estado || addr.geo_municipio ? `<div class="address-detail text-muted small"><i class="bi bi-geo-alt me-1"></i>${escapeHtml([addr.geo_estado, addr.geo_municipio].filter(Boolean).join(', '))}</div>` : ''}
                    <div class="address-detail mt-1"><i class="bi bi-telephone me-1"></i>${escapeHtml(addr.telefono)}</div>
                </div>
                <div class="address-check">
                    <i class="bi bi-check fs-6 ${addr.es_principal ? '' : 'd-none'}"></i>
                </div>
            </div>`;
                if (addr.es_principal) {
                    selectedAddressId = addr.id;
                    document.getElementById('btn-next-1').disabled = false;
                }
            }
            container.innerHTML = html;
            actualizarAdvertenciaUbicacion();
        }

        function seleccionarDireccion(id, el) {
            selectedAddressId = id;
            document.querySelectorAll('.address-card').forEach(c => {
                c.classList.remove('selected');
                c.querySelector('.address-check i')?.classList.add('d-none');
            });
            el.classList.add('selected');
            el.querySelector('.address-check i')?.classList.remove('d-none');
            document.getElementById('btn-next-1').disabled = false;

            const addr = addressesById[String(id)];
            if (addr && mostrarAdvertenciaUbicacion(addr.geo_estado || '', addr.geo_municipio || '')) {
                Notiflix.Notify.warning('La tienda no está en ese estado/municipio');
            }
        }

        function mostrarFormDireccion() {
            document.getElementById('new-address-form').classList.remove('d-none');
            iniciarMapa();
            inicializarSelectsUbicacion();
            actualizarAdvertenciaUbicacion();
        }

        function cancelarFormDireccion() {
            document.getElementById('new-address-form').classList.add('d-none');
            actualizarAdvertenciaUbicacion();
        }

        // ── Ubicación: estado / municipio y aviso de zona ─────────────
        function llenarMunicipios(estado, municipioSeleccionado) {
            const sel = document.getElementById('addr-municipio');
            if (!sel) return;
            sel.innerHTML = '<option value="">Seleccione un municipio...</option>';
            (GEO_VENEZUELA[estado] || []).forEach(function(m) {
                const opt = document.createElement('option');
                opt.value = m;
                opt.textContent = m;
                if (m === municipioSeleccionado) opt.selected = true;
                sel.appendChild(opt);
            });
        }

        function inicializarSelectsUbicacion() {
            const estadoSel = document.getElementById('addr-estado');
            if (!estadoSel) return;
            // La dirección nace en la ubicación de la tienda; el usuario puede cambiarla.
            const estado = estadoSel.value || tiendaConfig?.geo_estado || '';
            estadoSel.value = estado;
            const municipio = estado === (tiendaConfig?.geo_estado || '') ? (tiendaConfig?.geo_municipio || '') : '';
            llenarMunicipios(estado, municipio);
        }

        function hayDesajusteUbicacion(estado, municipio) {
            const te = (tiendaConfig?.geo_estado || '').trim();
            const tm = (tiendaConfig?.geo_municipio || '').trim();
            if (!te || !tm || !estado || !municipio) return false;
            return estado !== te || municipio !== tm;
        }

        // Muestra u oculta el aviso fijo. Devuelve true si hay desajuste.
        function mostrarAdvertenciaUbicacion(estado, municipio) {
            const box = document.getElementById('geo-warning');
            const txt = document.getElementById('geo-warning-text');
            if (!box || !txt) return false;

            if (deliveryType !== 'delivery' || !hayDesajusteUbicacion(estado, municipio)) {
                box.classList.remove('show');
                return false;
            }

            txt.textContent = ' La tienda está en ' + tiendaConfig.geo_estado + ', ' + tiendaConfig.geo_municipio +
                ' y la ubicación seleccionada es ' + estado + ', ' + municipio + '.';
            box.classList.add('show');
            return true;
        }

        function actualizarAdvertenciaUbicacion() {
            const box = document.getElementById('geo-warning');
            if (!box) return;

            if (deliveryType !== 'delivery') {
                box.classList.remove('show');
                return;
            }

            const form = document.getElementById('new-address-form');
            if (form && !form.classList.contains('d-none')) {
                mostrarAdvertenciaUbicacion(
                    document.getElementById('addr-estado').value,
                    document.getElementById('addr-municipio').value
                );
                return;
            }

            const addr = addressesById[String(selectedAddressId)];
            mostrarAdvertenciaUbicacion(addr?.geo_estado || '', addr?.geo_municipio || '');
        }

        let allCommunitiesNames = [];
        let geojsonLayer = null;
        const MAP_DEFAULT_CENTER = [5.653802, -67.605304]; // Puerto Ayacucho

        function iniciarMapa() {
            if (mapInstance) {
                setTimeout(() => mapInstance.invalidateSize(), 200);
                return;
            }

            mapInstance = L.map('map').setView(MAP_DEFAULT_CENTER, 13);

            // Google Hybrid Map tiles
            L.tileLayer('https://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}', {
                subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
                maxZoom: 20
            }).addTo(mapInstance);

            // Cargar polígonos de sectores / comunidades desde GeoJSON
            fetch('assets/js/comunidades.geojson')
                .then(r => r.json())
                .then(geoData => {
                    allCommunitiesNames = geoData.features.map(f => f.properties.NAME).sort();

                    geojsonLayer = L.geoJSON(geoData, {
                        style: function() {
                            return {
                                color: "#1a7f4b",
                                weight: 1.5,
                                fillColor: "#1a7f4b",
                                fillOpacity: 0.08
                            };
                        }
                    }).addTo(mapInstance);
                })
                .catch(err => console.warn("No se pudo cargar comunidades.geojson:", err));

            // Clic en el mapa para ubicar pin y detectar sector automáticamente
            mapInstance.on('click', function(e) {
                const {
                    lat,
                    lng
                } = e.latlng;
                document.getElementById('addr-lat').value = lat;
                document.getElementById('addr-lng').value = lng;

                if (markerInstance) markerInstance.setLatLng([lat, lng]);
                else markerInstance = L.marker([lat, lng]).addTo(mapInstance);

                // Detectar si el punto está dentro de un sector registrado con leaflet-pip
                if (typeof leafletPip !== 'undefined' && geojsonLayer) {
                    const results = leafletPip.pointInLayer(e.latlng, geojsonLayer);
                    if (results && results.length > 0) {
                        const comName = results[0].feature.properties.NAME;
                        const inputCom = document.getElementById('addr-comunidad');
                        if (inputCom) {
                            inputCom.value = comName;
                        }
                        Notiflix.Notify.success('Sector detectado: ' + comName);
                    }
                }
            });

            initComunidadSearch();
            setTimeout(() => mapInstance.invalidateSize(), 250);
        }

        function initComunidadSearch() {
            const inputCom = document.getElementById('addr-comunidad');
            const resultsDiv = document.getElementById('addr-comunidad-results');
            if (!inputCom || !resultsDiv) return;

            inputCom.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                if (query.length < 2) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                const matches = allCommunitiesNames.filter(c => c.toLowerCase().includes(query));
                if (matches.length > 0) {
                    resultsDiv.innerHTML = matches.slice(0, 12).map(m =>
                        `<div class="community-item" onclick="seleccionarComunidad('${m.replace(/'/g, "\\'")}')">
                    <i class="bi bi-geo-alt"></i> <span>${m}</span>
                </div>`
                    ).join('');
                    resultsDiv.style.display = 'block';
                } else {
                    resultsDiv.style.display = 'none';
                }
            });

            // Zoom al terminar de escribir o presionar Enter
            inputCom.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const firstItem = resultsDiv.querySelector('.community-item span');
                    if (firstItem && resultsDiv.style.display !== 'none') {
                        seleccionarComunidad(firstItem.textContent);
                    } else if (inputCom.value.trim()) {
                        hacerZoomAComunidad(inputCom.value.trim());
                    }
                }
            });

            inputCom.addEventListener('change', function() {
                if (inputCom.value.trim()) {
                    hacerZoomAComunidad(inputCom.value.trim());
                }
            });

            document.addEventListener('click', function(e) {
                if (!inputCom.contains(e.target) && !resultsDiv.contains(e.target)) {
                    resultsDiv.style.display = 'none';
                }
            });
        }

        function seleccionarComunidad(name) {
            const inputCom = document.getElementById('addr-comunidad');
            const resultsDiv = document.getElementById('addr-comunidad-results');
            if (inputCom) inputCom.value = name;
            if (resultsDiv) resultsDiv.style.display = 'none';
            hacerZoomAComunidad(name);
        }

        function hacerZoomAComunidad(name) {
            if (!geojsonLayer || !mapInstance || !name) return;
            const cleanName = name.trim().toUpperCase();
            let targetLayer = null;

            geojsonLayer.eachLayer(layer => {
                if (layer.feature && layer.feature.properties && layer.feature.properties.NAME) {
                    const featName = layer.feature.properties.NAME.toUpperCase();
                    if (featName === cleanName) {
                        targetLayer = layer;
                    } else if (!targetLayer && featName.includes(cleanName)) {
                        targetLayer = layer;
                    }
                }
            });

            if (targetLayer) {
                mapInstance.fitBounds(targetLayer.getBounds(), {
                    padding: [30, 30],
                    maxZoom: 17
                });

                targetLayer.setStyle({
                    color: '#1a7f4b',
                    weight: 3,
                    fillColor: '#1a7f4b',
                    fillOpacity: 0.25,
                    dashArray: '6, 6'
                });

                setTimeout(() => {
                    if (geojsonLayer) {
                        geojsonLayer.resetStyle(targetLayer);
                    }
                }, 3000);

                const center = targetLayer.getBounds().getCenter();
                document.getElementById('addr-lat').value = center.lat;
                document.getElementById('addr-lng').value = center.lng;

                if (markerInstance) markerInstance.setLatLng(center);
                else markerInstance = L.marker(center).addTo(mapInstance);

                Notiflix.Notify.success('Ubicación centrada en: ' + targetLayer.feature.properties.NAME);
            }
        }

        async function guardarDireccion() {
            const nombre = document.getElementById('addr-name').value.trim();
            const telefono = document.getElementById('addr-phone').value.trim();
            const direccion = document.getElementById('addr-address').value.trim();
            const comunidad = document.getElementById('addr-comunidad').value.trim();
            const geoEstado = document.getElementById('addr-estado').value;
            const geoMunicipio = document.getElementById('addr-municipio').value;
            const lat = document.getElementById('addr-lat').value || null;
            const lng = document.getElementById('addr-lng').value || null;

            if (!nombre || !telefono || !direccion) {
                Notiflix.Notify.warning('Completa nombre, teléfono y dirección');
                return;
            }
            if (!geoEstado || !geoMunicipio) {
                Notiflix.Notify.warning('Selecciona estado y municipio');
                return;
            }

            const btn = document.getElementById('btn-save-addr-text');
            btn.textContent = 'Guardando...';

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            let data;
            try {
                const res = await fetch('checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'save_address',
                        nombre_receptor: nombre,
                        telefono,
                        direccion,
                        comunidad,
                        geo_estado: geoEstado,
                        geo_municipio: geoMunicipio,
                        lat,
                        lng,
                        csrf_token: csrf
                    })
                });
                data = await leerRespuestaJson(res);
            } catch (e) {
                data = {
                    success: false,
                    message: 'No se pudo conectar con el servidor'
                };
            }
            btn.textContent = 'Guardar dirección';

            if (data.success) {
                Notiflix.Notify.success('Dirección guardada');
                if (hayDesajusteUbicacion(geoEstado, geoMunicipio)) {
                    Notiflix.Notify.warning('La tienda no está en ese estado/municipio. Puedes continuar con tu compra.');
                }
                selectedAddressId = data.id;
                document.getElementById('new-address-form').classList.add('d-none');
                document.getElementById('btn-next-1').disabled = false;
                cargarDirecciones();
            } else {
                Notiflix.Notify.failure(data.message || 'No se pudo guardar la dirección');
            }
        }

        // ── Delivery Type ────────────────────────────────────────────
        function setDeliveryType(type) {
            deliveryType = type;
            document.querySelectorAll('.delivery-option').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('.check-icon').classList.add('d-none');
            });
            const sel = type === 'delivery' ? document.getElementById('opt-delivery') : document.getElementById('opt-pickup');
            sel.classList.add('selected');
            sel.querySelector('.check-icon').classList.remove('d-none');

            if (type === 'delivery') {
                document.getElementById('delivery-address-section').classList.remove('d-none');
                document.getElementById('pickup-info-section').classList.add('d-none');
                document.getElementById('btn-next-1').disabled = !selectedAddressId;
            } else {
                document.getElementById('delivery-address-section').classList.add('d-none');
                document.getElementById('pickup-info-section').classList.remove('d-none');
                document.getElementById('btn-next-1').disabled = false;
            }
            actualizarResumen();
            actualizarAdvertenciaUbicacion();
        }

        // ── Resumen ───────────────────────────────────────────────────
        function actualizarResumen() {
            let subtotalUsd = 0,
                subtotalBs = 0;
            let itemsHtml = '';

            cartItems.forEach(item => {
                const lineUsd = recortarADosDecimales((item.price || 0) * (item.qty || 1));
                const lineBs = recortarADosDecimales((item.priceBolivar || 0) * (item.qty || 1));
                subtotalUsd += lineUsd;
                subtotalBs += lineBs;
                itemsHtml += `<div class="summary-line">
            <span>${escapeHtml(item.name)} <small class="text-muted">x${item.qty}</small></span>
            <span>$${formatNumber(lineUsd)}</span>
        </div>`;
            });

            document.getElementById('summary-items-list').innerHTML = itemsHtml;
            document.getElementById('sum-subtotal').textContent = '$' + formatNumber(subtotalUsd);

            // Costo de envío según tiendaConfig (mínimo para envío gratis incluido)
            const envio = calcularEnvio(subtotalUsd);
            const shippingUsd = envio.usd,
                shippingBs = envio.bs;

            const totalUsd = recortarADosDecimales(subtotalUsd + shippingUsd);
            const totalBs = recortarADosDecimales(subtotalBs + shippingBs);

            document.getElementById('sum-shipping').textContent = shippingUsd === 0 ? 'GRATIS' : '$' + formatNumber(shippingUsd);
            document.getElementById('sum-shipping').className = shippingUsd === 0 ? 'text-success fw-semibold' : 'fw-semibold text-danger';
            document.getElementById('sum-shipping-note').textContent = notaEnvio(subtotalUsd);
            document.getElementById('sum-total').textContent = '$' + formatNumber(totalUsd);
            document.getElementById('sum-total-bs').textContent = formatNumber(totalBs) + ' Bs';
            document.getElementById('header-total').textContent = '$' + formatNumber(totalUsd);
        }

        // ── Datos Bancarios ────────────────────────────────────────────
        function renderPaymentInfo() {
            const container = document.getElementById('payment-methods-container');
            const detailsPanel = document.getElementById('payment-details-panel');
            const refPanel = document.getElementById('payment-ref-panel');
            const bankContainer = document.getElementById('bank-info-container');
            const bankTitle = document.getElementById('bank-section-title');
            const bankSubtitle = document.getElementById('bank-section-subtitle');

            // Reset state
            selectedPaymentMethod = null;
            detailsPanel.classList.remove('active');
            refPanel.classList.remove('active');

            if (!tiendaConfig || !tiendaConfig.datos_bancarios) {
                container.innerHTML = '<div class="col-12 text-center py-4 text-muted small"><i class="bi bi-info-circle me-1"></i>No hay métodos de pago configurados para esta tienda.</div>';
                return;
            }

            const db_data = tiendaConfig.datos_bancarios;
            let methods = [];

            if (db_data.pago_movil && (db_data.pago_movil.telefono || db_data.pago_movil.banco)) {
                methods.push({
                    id: 'pago_movil',
                    name: 'Pago Móvil',
                    desc: 'Transferencia desde tu banco móvil',
                    icon: 'bi-phone-fill',
                    tipoPago: 6
                });
            }
            if (db_data.transferencia && (db_data.transferencia.cuenta || db_data.transferencia.banco)) {
                methods.push({
                    id: 'transferencia',
                    name: 'Transferencia Bancaria',
                    desc: 'Depósito o transferencia directa',
                    icon: 'bi-bank2',
                    tipoPago: 2
                });
            }

            if (methods.length === 0) {
                container.innerHTML = '<div class="col-12 text-center py-4 text-muted small">Sin métodos de pago registrados.</div>';
                return;
            }

            let html = '';
            methods.forEach(m => {
                html += `
                <div class="col-12 col-sm-6">
                    <div class="payment-method-option" id="pm-opt-${m.id}" onclick="selectPaymentMethod('${m.id}', ${m.tipoPago})">
                        <div class="pm-icon"><i class="bi ${m.icon}"></i></div>
                        <div class="pm-info">
                            <div class="pm-name">${m.name}</div>
                            <div class="pm-desc">${m.desc}</div>
                        </div>
                        <div class="pm-check"><i class="bi bi-check fs-6 d-none"></i></div>
                    </div>
                </div>`;
            });
            container.innerHTML = html;
        }

        function selectPaymentMethod(methodId, tipoPago) {
            selectedPaymentMethod = {
                id: methodId,
                tipoPago: tipoPago
            };

            // Update UI selection
            document.querySelectorAll('.payment-method-option').forEach(el => {
                el.classList.remove('selected');
                el.querySelector('.pm-check i')?.classList.add('d-none');
            });
            const sel = document.getElementById('pm-opt-' + methodId);
            sel.classList.add('selected');
            sel.querySelector('.pm-check i')?.classList.remove('d-none');

            // Show bank details panel
            const detailsPanel = document.getElementById('payment-details-panel');
            const refPanel = document.getElementById('payment-ref-panel');
            const bankContainer = document.getElementById('bank-info-container');
            const bankTitle = document.getElementById('bank-section-title');
            const bankSubtitle = document.getElementById('bank-section-subtitle');

            const db_data = tiendaConfig.datos_bancarios;
            let html = '';

            if (methodId === 'pago_movil') {
                const pm = db_data.pago_movil;
                bankTitle.textContent = 'Pago Móvil Interbancario';
                bankSubtitle.textContent = 'Realiza tu pago móvil con los datos mostrados';
                html = `<div class="bank-block">
                    <div class="bank-block-title"><i class="bi bi-phone-fill fs-5"></i><span>Pago Móvil Interbancario</span></div>
                    <div class="bank-row"><span class="bank-key">Banco Destino</span><span class="bank-val">${escapeHtml(pm.banco || '')}</span></div>
                    <div class="bank-row"><span class="bank-key">Teléfono</span><div class="bank-val"><span>${escapeHtml(pm.telefono || '')}</span><button class="copy-btn" data-copy="${escapeHtml(pm.telefono || '')}" onclick="copyToClipboard(this.dataset.copy, 'Teléfono')"><i class="bi bi-copy"></i> Copiar</button></div></div>
                    <div class="bank-row"><span class="bank-key">Cédula / RIF</span><div class="bank-val"><span>${escapeHtml(pm.cedula || '')}</span><button class="copy-btn" data-copy="${escapeHtml(pm.cedula || '')}" onclick="copyToClipboard(this.dataset.copy, 'Cédula')"><i class="bi bi-copy"></i> Copiar</button></div></div>
                </div>`;
            } else if (methodId === 'transferencia') {
                const tr = db_data.transferencia;
                bankTitle.textContent = 'Transferencia Bancaria';
                bankSubtitle.textContent = 'Realiza tu transferencia con los datos mostrados';
                html = `<div class="bank-block">
                    <div class="bank-block-title"><i class="bi bi-bank2 fs-5"></i><span>Transferencia Bancaria</span></div>
                    <div class="bank-row"><span class="bank-key">Banco</span><span class="bank-val">${escapeHtml(tr.banco || '')}</span></div>
                    <div class="bank-row"><span class="bank-key">Número de Cuenta</span><div class="bank-val"><span style="font-family: monospace; font-size: 0.85rem;">${escapeHtml(tr.cuenta || '')}</span><button class="copy-btn" data-copy="${escapeHtml(tr.cuenta || '')}" onclick="copyToClipboard(this.dataset.copy, 'Cuenta')"><i class="bi bi-copy"></i> Copiar</button></div></div>
                    <div class="bank-row"><span class="bank-key">Titular</span><span class="bank-val">${escapeHtml(tr.titular || '')}</span></div>
                    <div class="bank-row"><span class="bank-key">Cédula / RIF</span><div class="bank-val"><span>${escapeHtml(tr.cedula || '')}</span><button class="copy-btn" data-copy="${escapeHtml(tr.cedula || '')}" onclick="copyToClipboard(this.dataset.copy, 'Cédula')"><i class="bi bi-copy"></i> Copiar</button></div></div>
                </div>`;
            }

            bankContainer.innerHTML = html;
            detailsPanel.classList.add('active');
            refPanel.classList.add('active');
        }

        function renderPaymentSummary() {
            let subtotalUsd = 0,
                totalBs = 0;
            let itemsHtml = '';

            cartItems.forEach(item => {
                const lineUsd = recortarADosDecimales((item.price || 0) * (item.qty || 1));
                const lineBs = recortarADosDecimales((item.priceBolivar || 0) * (item.qty || 1));
                subtotalUsd += lineUsd;
                totalBs += lineBs;
                itemsHtml += `<div class="summary-line">
            <div><span class="fw-semibold">${escapeHtml(item.name)}</span> <small class="text-muted">×${item.qty}</small></div>
            <span class="fw-semibold">$${formatNumber(lineUsd)}</span>
        </div>`;
            });

            const envio = calcularEnvio(subtotalUsd);
            const shippingUsd = envio.usd;
            const shippingBs = envio.bs;

            const totalUsd = recortarADosDecimales(subtotalUsd + shippingUsd);
            totalBs = recortarADosDecimales(totalBs + shippingBs);

            document.getElementById('cart-items-payment').innerHTML = itemsHtml;
            document.getElementById('pay-subtotal').textContent = '$' + formatNumber(subtotalUsd);
            document.getElementById('pay-shipping').textContent = shippingUsd === 0 ? 'GRATIS' : '$' + formatNumber(shippingUsd);
            document.getElementById('pay-shipping-note').textContent = notaEnvio(subtotalUsd);
            document.getElementById('pay-total-usd').textContent = '$' + formatNumber(totalUsd);
            document.getElementById('pay-total-bs').textContent = formatNumber(totalBs) + ' Bs';
        }

        // ── Finalizar ─────────────────────────────────────────────────
        function finalizarCompra() {
            if (!selectedPaymentMethod) {
                Notiflix.Notify.failure('Selecciona un método de pago');
                return;
            }

            const ref = document.getElementById('payment-ref').value.trim();
            const date = document.getElementById('payment-date').value;
            if (!ref || !date) {
                Notiflix.Notify.failure('Completa la referencia y la fecha del pago');
                return;
            }

            const btn = document.getElementById('btn-finish');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';

            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const payload = {
                carrito: cartItems,
                address_id: deliveryType === 'delivery' ? selectedAddressId : null,
                payment_ref: ref + ' | ' + date,
                payment_method: selectedPaymentMethod.tipoPago,
                tipo_entrega: deliveryType,
                csrf_token: csrf
            };

            fetch('checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(async r => {
                    const txt = await r.text();
                    try {
                        return JSON.parse(txt);
                    } catch (e) {
                        throw new Error('Respuesta inesperada:\n' + txt);
                    }
                })
                .then(async data => {
                    if (data.success) {
                        // Eliminar ÚNICAMENTE los productos del carrito de la tienda procesada
                        for (const item of cartItems) {
                            try {
                                await db.carritoActivo.delete(item.id);
                            } catch (e) {
                                console.warn("No se pudo eliminar item de Dexie:", item.id, e);
                            }
                        }

                        document.getElementById('success-order-id').textContent = data.orden_id;

                        if (deliveryType === 'delivery') {
                            document.getElementById('estimated-time-msg').textContent =
                                'Recibirás tu pedido según el horario de delivery de la tienda.';
                            document.getElementById('estimated-time-container').classList.remove('d-none');
                        } else {
                            document.getElementById('estimated-time-container').classList.add('d-none');
                        }

                        // Comprobar si quedan otros carritos en Dexie de otras tiendas
                        allCartItems = await db.carritoActivo.toArray();

                        const remainingGroups = {};
                        for (let item of allCartItems) {
                            const key = String(item.bss_id ?? 0);
                            if (!remainingGroups[key]) remainingGroups[key] = [];
                            remainingGroups[key].push(item);
                        }
                        const countRemainingStores = Object.keys(remainingGroups).length;

                        const pendingAction = document.getElementById('pending-carts-action');
                        if (countRemainingStores > 0) {
                            document.getElementById('remaining-carts-text').textContent =
                                `${countRemainingStores} carrito${countRemainingStores > 1 ? 's' : ''}`;
                            pendingAction.classList.remove('d-none');
                        } else {
                            pendingAction.classList.add('d-none');
                        }

                        document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
                        document.getElementById('step-success').classList.add('active');
                        document.getElementById('geo-warning')?.classList.remove('show');
                        document.getElementById('step-indicator-container').style.display = 'none';
                        window.scrollTo({
                            top: 0,
                            behavior: 'smooth'
                        });
                    } else {
                        Notiflix.Notify.failure(data.message || 'Error al procesar');
                        btn.disabled = false;
                        btn.innerHTML = 'Confirmar Pedido <i class="bi bi-lock-fill ms-1"></i>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    Notiflix.Notify.failure('Error: ' + (err.message || 'Intenta de nuevo'));
                    btn.disabled = false;
                    btn.innerHTML = 'Confirmar Pedido <i class="bi bi-lock-fill ms-1"></i>';
                });
        }

        // ── Volver a Carritos Pendientes ──────────────────────────────
        async function verOtrosCarritos() {
            document.getElementById('step-indicator-container').style.display = 'flex';
            selectedAddressId = null;
            tiendaConfig = null;
            currentBssId = null;
            cartItems = [];

            // Limpiar campos de pago
            document.getElementById('payment-ref').value = '';
            document.getElementById('payment-date').value = '';
            selectedPaymentMethod = null;

            await cargarCarrito();
            goToStep(0);
        }

        // ── Portapapeles ──────────────────────────────────────────────
        function copyToClipboard(text, label) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text)
                    .then(() => Notiflix.Notify.success(label + ' copiado'))
                    .catch(() => Notiflix.Notify.failure('Error al copiar'));
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                try {
                    document.execCommand('copy');
                    Notiflix.Notify.success(label + ' copiado');
                } catch (e) {
                    Notiflix.Notify.failure('No se pudo copiar');
                }
                document.body.removeChild(ta);
            }
        }

        // ── Init ──────────────────────────────────────────────────────
        document.addEventListener('DOMContentLoaded', cargarCarrito);

        document.addEventListener('DOMContentLoaded', function() {
            const estadoSel = document.getElementById('addr-estado');
            const municipioSel = document.getElementById('addr-municipio');
            if (!estadoSel || !municipioSel) return;

            estadoSel.addEventListener('change', function() {
                llenarMunicipios(estadoSel.value, '');
                actualizarAdvertenciaUbicacion();
            });
            municipioSel.addEventListener('change', actualizarAdvertenciaUbicacion);
        });
    </script>

    <!-- Chat Component -->
    <?php include 'assets/components/chat-button.php'; ?>
    <script src="assets/js/chat-simple.js"></script>
</body>

</html>