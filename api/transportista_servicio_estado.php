<?php
/**
 * API — Aceptar, rechazar o entregar un servicio  →  POST /api/transportista_servicio_estado.php
 *
 * Entrada: id (servicio), accion=aceptar|rechazar|entregar
 * - rechazar: el pedido vuelve a "En preparación" para que la tienda lo reasigne.
 * - entregar: el pedido pasa a "Entregado" y se avisa al administrador y al cliente.
 */

require_once __DIR__ . '/transportista_comun.php';

exigirMetodo('POST');
limitar('servicio_estado', 60, 60);
exigirApiKey();
asegurarTablas($conexion);

$transportista = transportistaDesdeToken($conexion);
if (!$transportista) {
    salirJson(401, ['ok' => false, 'error' => 'Sesión no válida o expirada']);
}

$in = entrada();
$servicioId = (int)($in['id'] ?? 0);
$accion = trim((string)($in['accion'] ?? ''));

if ($servicioId <= 0 || !in_array($accion, ['aceptar', 'rechazar', 'entregar'], true)) {
    salirJson(400, ['ok' => false, 'error' => 'Datos inválidos']);
}

$transportistaId = (int)$transportista['id'];

$stmt = $conexion->prepare(
    "SELECT id, bss_id, numero_pedido, estado
       FROM transportista_servicios
      WHERE id = ? AND transportista_id = ?
      LIMIT 1"
);
if (!$stmt) {
    salirJson(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('ii', $servicioId, $transportistaId);
$stmt->execute();
$servicio = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$servicio) {
    salirJson(404, ['ok' => false, 'error' => 'Servicio no encontrado']);
}

$estadoActual = (string)$servicio['estado'];

// ── Entregado ─────────────────────────────────────────────────────────────────
if ($accion === 'entregar') {
    if (!in_array($estadoActual, ['aceptado', 'en_camino'], true)) {
        salirJson(409, ['ok' => false, 'error' => 'Acepta el servicio antes de marcarlo como entregado']);
    }

    $ordenId = (int)($servicio['numero_pedido'] ?? 0);
    $bssId = (int)($servicio['bss_id'] ?? 0);
    if ($ordenId <= 0 || $bssId <= 0) {
        salirJson(409, ['ok' => false, 'error' => 'Este servicio no está vinculado a un pedido']);
    }

    $stmt = $conexion->prepare(
        "SELECT id, customer_id, status FROM tienda_ordenes WHERE id = ? AND bss_id = ? LIMIT 1"
    );
    if (!$stmt) {
        salirJson(500, ['ok' => false, 'error' => 'Error interno']);
    }
    $stmt->bind_param('ii', $ordenId, $bssId);
    $stmt->execute();
    $orden = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$orden) {
        salirJson(404, ['ok' => false, 'error' => 'Pedido no encontrado']);
    }

    $estadoOrden = (int)$orden['status'];
    if ($estadoOrden === 5) {
        salirJson(409, ['ok' => false, 'error' => 'El pedido está rechazado']);
    }
    if ($estadoOrden !== 3 && $estadoOrden !== 4) {
        salirJson(409, ['ok' => false, 'error' => 'El pedido aún no está en envío']);
    }

    $stmt = $conexion->prepare("UPDATE transportista_servicios SET estado = 'completado' WHERE id = ?");
    $stmt->bind_param('i', $servicioId);
    $stmt->execute();
    $stmt->close();

    if ($estadoOrden === 3) {
        $stmt = $conexion->prepare("UPDATE tienda_ordenes SET status = 4 WHERE id = ? AND bss_id = ?");
        $stmt->bind_param('ii', $ordenId, $bssId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare(
            "UPDATE tienda_compras_por_usuarios SET estatus = 'entregada', estado = 'entregada' WHERE compra_id = ?"
        );
        if ($stmt) {
            $stmt->bind_param('i', $ordenId);
            $stmt->execute();
            $stmt->close();
        }

        $archivoEstados = __DIR__ . '/../iseller_store/core/estados_orden.php';
        if (file_exists($archivoEstados)) {
            require_once $archivoEstados;
            if (function_exists('registrar_estado_orden')) {
                try {
                    registrar_estado_orden($conexion, $ordenId, $bssId, 4, 'transportista');
                } catch (Throwable $e) {
                    error_log('transportista entregado historial: ' . $e->getMessage());
                }
            }
        }
    }

    // Avisos: cliente (in-app + push) y administrador de la tienda (in-app + push)
    $customerId = (int)($orden['customer_id'] ?? 0);
    $titulo = "Pedido #$ordenId entregado";
    $cuerpoCliente = 'Tu pedido fue entregado. ¡Gracias por tu compra!';

    $archivoAvisos = __DIR__ . '/../iseller_store/api/chat/crear_notificacion_cliente.php';
    if ($customerId > 0 && file_exists($archivoAvisos)) {
        require_once $archivoAvisos;
        if (function_exists('notificar_cliente')) {
            notificar_cliente($conexion, $customerId, $titulo, $cuerpoCliente, 'compra', 'perfil.php?tab=orders');
        }
    }

    $archivoAdmin = __DIR__ . '/../iseller_store/core/notificaciones_admin.php';
    if (file_exists($archivoAdmin)) {
        require_once $archivoAdmin;
        if (function_exists('enviar_push_usuario')) {
            if ($customerId > 0) {
                enviar_push_usuario($conexion, 'cliente', $customerId, $titulo, $cuerpoCliente,
                        ['tipo' => 'compra', 'pedido' => (string)$ordenId]);
            }
            if (function_exists('notificar_admin_tienda')) {
                notificar_admin_tienda($conexion, $bssId, $titulo,
                        'El transportista marcó el pedido como entregado.', 'compra');
            }
        }
    }

    salirJson(200, ['ok' => true, 'estado' => 'completado']);
}

// ── Aceptar / rechazar ────────────────────────────────────────────────────────
if ($accion === 'aceptar' && $estadoActual !== 'asignado') {
    salirJson(409, ['ok' => false, 'error' => 'Este servicio ya fue atendido']);
}
if ($accion === 'rechazar' && !in_array($estadoActual, ['asignado', 'aceptado'], true)) {
    salirJson(409, ['ok' => false, 'error' => 'Este servicio ya no se puede rechazar']);
}

$nuevoEstado = $accion === 'aceptar' ? 'aceptado' : 'rechazado';

$stmt = $conexion->prepare("UPDATE transportista_servicios SET estado = ? WHERE id = ?");
$stmt->bind_param('si', $nuevoEstado, $servicioId);
$stmt->execute();
$stmt->close();

// Rechazo: devolver el pedido a preparación para que la tienda pueda reasignarlo.
if ($accion === 'rechazar' && $servicio['numero_pedido'] !== null && $servicio['numero_pedido'] !== '') {
    $ordenId = (int)$servicio['numero_pedido'];
    $bssId = (int)($servicio['bss_id'] ?? 0);

    $stmt = $conexion->prepare("UPDATE tienda_ordenes SET status = 2 WHERE id = ? AND status = 3");
    if ($stmt) {
        $stmt->bind_param('i', $ordenId);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conexion->prepare(
        "UPDATE tienda_compras_por_usuarios SET estatus = 'en_revision', estado = 'en_revision' WHERE compra_id = ?"
    );
    if ($stmt) {
        $stmt->bind_param('i', $ordenId);
        $stmt->execute();
        $stmt->close();
    }

    $archivoEstados = __DIR__ . '/../iseller_store/core/estados_orden.php';
    if ($bssId > 0 && file_exists($archivoEstados)) {
        require_once $archivoEstados;
        if (function_exists('registrar_estado_orden')) {
            try {
                registrar_estado_orden($conexion, $ordenId, $bssId, 2, 'transportista');
            } catch (Throwable $e) {
                error_log('transportista rechazo historial: ' . $e->getMessage());
            }
        }
    }
}

salirJson(200, ['ok' => true, 'estado' => $nuevoEstado]);
