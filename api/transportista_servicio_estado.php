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
    // Si el reporte ya se había enviado (reintento de la cola offline), es idempotente.
    if ($estadoActual === 'completado') {
        salirJson(200, ['ok' => true, 'estado' => 'completado']);
    }
    if (!in_array($estadoActual, ['aceptado', 'en_camino'], true)) {
        salirJson(409, ['ok' => false, 'error' => 'Acepta el servicio antes de marcarlo como entregado']);
    }

    // Hora real de la entrega capturada en el teléfono (puede llegar mucho después).
    $entregadoMs = (int)($in['entregado_ms'] ?? 0);
    $ahoraMs = (int)floor(microtime(true) * 1000);
    if ($entregadoMs <= 0 || $entregadoMs > $ahoraMs + 5 * 60 * 1000
        || $entregadoMs < $ahoraMs - 60 * 24 * 60 * 60 * 1000) {
        $entregadoMs = $ahoraMs;
    }
    $fechaEntrega = date('Y-m-d H:i:s', (int)floor($entregadoMs / 1000));
    $horaEntrega = date('H:i', (int)floor($entregadoMs / 1000));

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

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare(
            "UPDATE transportista_servicios SET estado = 'completado', entregado_en = ? WHERE id = ?"
        );
        $stmt->bind_param('si', $fechaEntrega, $servicioId);
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
            $stmt->bind_param('i', $ordenId);
            $stmt->execute();
            $stmt->close();

            $archivoEstados = __DIR__ . '/../iseller_store/core/estados_orden.php';
            if (file_exists($archivoEstados)) {
                require_once $archivoEstados;
                if (function_exists('registrar_estado_orden')) {
                    registrar_estado_orden($conexion, $ordenId, $bssId, 4, 'transportista', $fechaEntrega);
                }
            }
        }

        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        error_log('transportista entregado: ' . $e->getMessage());
        salirJson(500, ['ok' => false, 'error' => 'No se pudo guardar la entrega']);
    }

    // Avisos: cliente (in-app + push) y administrador de la tienda (in-app + push).
    // Solo si esta misma petición cambió el pedido a Entregado.
    if ($estadoOrden === 3) {
        $customerId = (int)($orden['customer_id'] ?? 0);
        $titulo = "Pedido #$ordenId entregado";
        $cuerpoCliente = "Tu pedido fue entregado a las $horaEntrega. ¡Gracias por tu compra!";

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
                            "El transportista marcó el pedido como entregado a las $horaEntrega.", 'compra');
                }
            }
        }
    }

    salirJson(200, ['ok' => true, 'estado' => 'completado', 'entregado_en' => $fechaEntrega]);
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

    // También desde 6 (transportista asignado) el pedido vuelve a preparación.
    $stmt = $conexion->prepare("UPDATE tienda_ordenes SET status = 2 WHERE id = ? AND status IN (3, 6)");
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
