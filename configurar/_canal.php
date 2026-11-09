<?php
/**
 * Núcleo del modo CANAL y del seguimiento estricto.
 *
 * Regla central: en modo CANAL el stock real es el lote de la res. Los cortes
 * no descuentan su propio stock; cada venta descuenta kg del lote y del padre.
 */

function canal_numero($valor): string
{
    $texto = number_format((float) $valor, 4, '.', '');
    if (strpos($texto, '.') !== false) {
        $texto = rtrim(rtrim($texto, '0'), '.');
    }
    return $texto === '' || $texto === '-' ? '0' : $texto;
}

function canal_consulta($conexion, string $sql, string $tipos = '', array $params = []): array
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta de inventario.');
    }
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Error al ejecutar la consulta de inventario.');
    }
    $resultado = $stmt->get_result();
    $filas = [];
    if ($resultado) {
        while ($fila = $resultado->fetch_assoc()) {
            $filas[] = $fila;
        }
    }
    $stmt->close();
    return $filas;
}

function canal_info_producto($conexion, int $productoId, int $bssId): ?array
{
    $filas = canal_consulta(
        $conexion,
        "SELECT id, nombre, precio_compra, cantidad_unidades, porcentaje, origen, proveedor,
                categoria, id_producto_relacionado, mayor, unidad, rendimiento, modo_control,
                seguimiento_estricto
         FROM productos
         WHERE id = ? AND bss_id = ? AND activo = 0
         LIMIT 1",
        'ii',
        [$productoId, $bssId]
    );
    return $filas[0] ?? null;
}

function canal_negocio_estricto($conexion, int $bssId): bool
{
    $filas = canal_consulta(
        $conexion,
        "SELECT seguimiento_estricto FROM negocio WHERE id = ? LIMIT 1",
        'i',
        [$bssId]
    );
    return (int) ($filas[0]['seguimiento_estricto'] ?? 0) === 1;
}

function canal_producto_estricto($conexion, int $productoId, int $bssId): bool
{
    if (!canal_negocio_estricto($conexion, $bssId)) {
        return false;
    }
    $filas = canal_consulta(
        $conexion,
        "SELECT seguimiento_estricto FROM productos WHERE id = ? AND bss_id = ? LIMIT 1",
        'ii',
        [$productoId, $bssId]
    );
    return (int) ($filas[0]['seguimiento_estricto'] ?? 0) === 1;
}

function canal_lote_abierto($conexion, int $bssId, int $sucursalId, int $padreId, bool $bloquear = false): ?array
{
    $sql = "SELECT * FROM lotes
            WHERE bss_id = ? AND id_sucursal = ? AND padre_id = ? AND estado = 'ABIERTO'
            ORDER BY id ASC LIMIT 1";
    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }
    $filas = canal_consulta($conexion, $sql, 'iii', [$bssId, $sucursalId, $padreId]);
    return $filas[0] ?? null;
}

/**
 * Contexto que se muestra antes de una compra estricta.
 */
function canal_contexto_estricto($conexion, int $productoId, int $sucursalId, int $bssId): array
{
    $producto = canal_info_producto($conexion, $productoId, $bssId);
    if (!$producto) {
        throw new Exception('Producto no encontrado.');
    }

    $stockFilas = canal_consulta(
        $conexion,
        "SELECT stock, visible_venta, ultima_compra_at, ultima_compra_kg
         FROM stock
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
         LIMIT 1",
        'iii',
        [$productoId, $sucursalId, $bssId]
    );
    $stock = $stockFilas[0] ?? [];
    $stockActual = (float) ($stock['stock'] ?? 0);
    $ultimaCompraAt = $stock['ultima_compra_at'] ?? null;

    $lote = null;
    if (($producto['modo_control'] ?? 'NORMAL') === 'CANAL') {
        // Para el contexto sirve el último lote, esté abierto o cerrado: es el
        // que explica de dónde viene el stock que se está confirmando.
        $filasLote = canal_consulta(
            $conexion,
            "SELECT * FROM lotes
             WHERE bss_id = ? AND id_sucursal = ? AND padre_id = ?
             ORDER BY id DESC LIMIT 1",
            'iii',
            [$bssId, $sucursalId, $productoId]
        );
        $lote = $filasLote[0] ?? null;
    }

    $vendidoDesde = 0.0;
    if ($lote) {
        $sql = "SELECT COALESCE(SUM(cantidad), 0) AS total
                FROM lotes_detalle
                WHERE lote_id = ? AND origen = 'VENTA'";
        $params = [(int) $lote['id']];
        $tipos = 'i';
        if ($ultimaCompraAt) {
            $sql .= ' AND fecha >= ?';
            $params[] = $ultimaCompraAt;
            $tipos .= 's';
        }
        $fila = canal_consulta($conexion, $sql, $tipos, $params);
        $vendidoDesde = (float) ($fila[0]['total'] ?? 0);
    } elseif ($ultimaCompraAt) {
        $fila = canal_consulta(
            $conexion,
            "SELECT COALESCE(SUM(CAST(oa.quantity AS DECIMAL(15,3))), 0) AS total
             FROM orden_articulos oa
             INNER JOIN orden o ON o.id = oa.order_id
             WHERE oa.product_id = ? AND oa.id_sucursal = ? AND oa.bss_id = ?
               AND o.status IN ('1','4') AND o.created >= ?",
            'iiis',
            [$productoId, $sucursalId, $bssId, $ultimaCompraAt]
        );
        $vendidoDesde = (float) ($fila[0]['total'] ?? 0);
    }

    return [
        'id' => (int) $producto['id'],
        'nombre' => (string) $producto['nombre'],
        'unidad' => (string) ($producto['unidad'] ?? 'UND'),
        'modo_control' => (string) ($producto['modo_control'] ?? 'NORMAL'),
        'stock_actual' => $stockActual,
        'visible_venta' => (int) ($stock['visible_venta'] ?? 1),
        'ultima_compra_at' => $ultimaCompraAt,
        'ultima_compra_kg' => (float) ($stock['ultima_compra_kg'] ?? 0),
        'vendido_desde' => $vendidoDesde,
        'lote' => $lote,
    ];
}

/**
 * Consume kg del lote CANAL. Devuelve modo=NORMAL cuando el producto no usa canal.
 * No descuenta el stock del corte: el stock real está en el padre/lote.
 */
function canal_consumir(
    $conexion,
    int $productoId,
    int $sucursalId,
    int $bssId,
    float $cantidad,
    ?int $ordenId = null,
    int $usuarioId = 0,
    bool $validarVisible = true
): array {
    if (!is_finite($cantidad) || $cantidad <= 0) {
        return ['modo' => 'NORMAL'];
    }

    $producto = canal_info_producto($conexion, $productoId, $bssId);
    if (!$producto) {
        return ['modo' => 'NORMAL'];
    }

    $stockFilas = canal_consulta(
        $conexion,
        "SELECT stock, visible_venta FROM stock
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1",
        'iii',
        [$productoId, $sucursalId, $bssId]
    );
    if (!$stockFilas) {
        return ['error' => 'El producto no está asignado a esta sucursal.'];
    }
    if ($validarVisible && (int) ($stockFilas[0]['visible_venta'] ?? 1) === 0) {
        return ['error' => 'El producto está pausado para la venta.'];
    }

    $esMayor = (string) ($producto['mayor'] ?? '0') === '1';
    $factor = $esMayor ? max(1.0, (float) $producto['cantidad_unidades']) : 1.0;
    $kg = $cantidad * $factor;

    $baseId = $esMayor ? (int) $producto['id_producto_relacionado'] : (int) $producto['id'];
    $base = $esMayor ? canal_info_producto($conexion, $baseId, $bssId) : $producto;
    if (!$base) {
        return ['error' => 'El producto base de la venta no existe.'];
    }

    $padreId = 0;
    $movimientoId = $baseId;
    if ((string) ($base['modo_control'] ?? 'NORMAL') === 'CANAL') {
        $padreId = (int) $base['id'];
        $movimientoId = (int) $base['id'];
    } elseif ((string) ($base['mayor'] ?? '0') !== '1' && (int) $base['id_producto_relacionado'] > 0) {
        $padreId = (int) $base['id_producto_relacionado'];
        $movimientoId = (int) $base['id'];
    } elseif ((string) ($producto['modo_control'] ?? 'NORMAL') === 'CANAL') {
        $padreId = (int) $producto['id'];
        $movimientoId = (int) $producto['id'];
    }

    if ($padreId <= 0) {
        return ['modo' => 'NORMAL'];
    }

    $padre = canal_info_producto($conexion, $padreId, $bssId);
    if (!$padre || (string) ($padre['modo_control'] ?? 'NORMAL') !== 'CANAL') {
        return ['modo' => 'NORMAL'];
    }

    $lote = canal_lote_abierto($conexion, $bssId, $sucursalId, $padreId, true);
    if (!$lote) {
        return ['error' => 'No hay res abierta para «' . $padre['nombre'] . '».'];
    }

    $disponible = (float) $lote['kg_restante'];
    if ($disponible + 0.0001 < $kg) {
        return ['error' => 'La res solo tiene ' . canal_numero($disponible) . ' kg disponibles.'];
    }

    $stmt = $conexion->prepare("UPDATE lotes SET kg_restante = kg_restante - ? WHERE id = ?");
    if (!$stmt) {
        return ['error' => 'No se pudo descontar el lote.'];
    }
    $loteId = (int) $lote['id'];
    $stmt->bind_param('di', $kg, $loteId);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['error' => 'No se pudo descontar el lote.'];
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE stock SET stock = GREATEST(0, CAST(stock AS DECIMAL(15,4)) - ?)
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
    );
    if (!$stmt) {
        return ['error' => 'No se pudo descontar el stock del padre.'];
    }
    $stmt->bind_param('diii', $kg, $padreId, $sucursalId, $bssId);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['error' => 'No se pudo descontar el stock del padre.'];
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "INSERT INTO lotes_detalle (lote_id, producto_id, cantidad, origen, orden_id, usuario_id)
         VALUES (?, ?, ?, 'VENTA', ?, ?)"
    );
    if ($stmt) {
        $ordenRef = $ordenId ?? 0;
        $stmt->bind_param('iidii', $loteId, $movimientoId, $kg, $ordenRef, $usuarioId);
        $stmt->execute();
        $stmt->close();
    }

    return [
        'modo' => 'CANAL',
        'kg' => $kg,
        'lote_id' => $loteId,
        'padre_id' => $padreId,
    ];
}

/**
 * Devuelve kg al lote. Se usa al anular una venta.
 */
function canal_devolver(
    $conexion,
    int $productoId,
    int $sucursalId,
    int $bssId,
    float $cantidad,
    ?int $ordenId = null,
    int $usuarioId = 0
): array {
    if (!is_finite($cantidad) || $cantidad <= 0) {
        return ['modo' => 'NORMAL'];
    }

    $producto = canal_info_producto($conexion, $productoId, $bssId);
    if (!$producto) {
        return ['modo' => 'NORMAL'];
    }

    $esMayor = (string) ($producto['mayor'] ?? '0') === '1';
    $factor = $esMayor ? max(1.0, (float) $producto['cantidad_unidades']) : 1.0;
    $kg = $cantidad * $factor;

    $baseId = $esMayor ? (int) $producto['id_producto_relacionado'] : (int) $producto['id'];
    $base = $esMayor ? canal_info_producto($conexion, $baseId, $bssId) : $producto;
    if (!$base) {
        return ['modo' => 'NORMAL'];
    }

    $padreId = 0;
    $movimientoId = $baseId;
    if ((string) ($base['modo_control'] ?? 'NORMAL') === 'CANAL') {
        $padreId = (int) $base['id'];
        $movimientoId = (int) $base['id'];
    } elseif ((string) ($base['mayor'] ?? '0') !== '1' && (int) $base['id_producto_relacionado'] > 0) {
        $padreId = (int) $base['id_producto_relacionado'];
        $movimientoId = (int) $base['id'];
    } elseif ((string) ($producto['modo_control'] ?? 'NORMAL') === 'CANAL') {
        $padreId = (int) $producto['id'];
        $movimientoId = (int) $producto['id'];
    }
    if ($padreId <= 0) {
        return ['modo' => 'NORMAL'];
    }
    $padre = canal_info_producto($conexion, $padreId, $bssId);
    if (!$padre || (string) ($padre['modo_control'] ?? 'NORMAL') !== 'CANAL') {
        return ['modo' => 'NORMAL'];
    }

    $lote = null;
    $detalleId = 0;
    $kgDevolver = $kg;
    if ($ordenId !== null && $ordenId > 0) {
        $filas = canal_consulta(
            $conexion,
            "SELECT id, lote_id, cantidad FROM lotes_detalle
             WHERE orden_id = ? AND producto_id = ? AND origen = 'VENTA'
             ORDER BY id DESC LIMIT 1",
            'ii',
            [$ordenId, $movimientoId]
        );
        if ($filas) {
            $detalleId = (int) $filas[0]['id'];
            $kgDevolver = (float) $filas[0]['cantidad'];
            $lote = canal_consulta($conexion, "SELECT * FROM lotes WHERE id = ? LIMIT 1", 'i', [(int) $filas[0]['lote_id']])[0] ?? null;
        }
    }
    if (!$lote) {
        $lote = canal_lote_abierto($conexion, $bssId, $sucursalId, $padreId, true);
    }
    if (!$lote) {
        return ['error' => 'No hay lote para devolver el stock. Ajuste el inventario manualmente.'];
    }
    if ((string) $lote['estado'] !== 'ABIERTO') {
        $stmt = $conexion->prepare("UPDATE lotes SET estado = 'ABIERTO', fecha_cierre = NULL, conciliado = 0 WHERE id = ?");
        if ($stmt) {
            $lid = (int) $lote['id'];
            $stmt->bind_param('i', $lid);
            $stmt->execute();
            $stmt->close();
        }
    }

    $loteId = (int) $lote['id'];
    $stmt = $conexion->prepare("UPDATE lotes SET kg_restante = kg_restante + ? WHERE id = ?");
    if (!$stmt) {
        return ['error' => 'No se pudo devolver el lote.'];
    }
    $stmt->bind_param('di', $kgDevolver, $loteId);
    if (!$stmt->execute()) {
        $stmt->close();
        return ['error' => 'No se pudo devolver el lote.'];
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "UPDATE stock SET stock = CAST(stock AS DECIMAL(15,4)) + ?
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
    );
    if ($stmt) {
        $stmt->bind_param('diii', $kgDevolver, $padreId, $sucursalId, $bssId);
        $stmt->execute();
        $stmt->close();
    }

    if ($detalleId > 0) {
        $stmt = $conexion->prepare("DELETE FROM lotes_detalle WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $detalleId);
            $stmt->execute();
            $stmt->close();
        }
    }

    return ['modo' => 'CANAL', 'kg' => $kgDevolver, 'lote_id' => $loteId];
}

/**
 * Suma una compra al stock y, si el producto es padre CANAL, al lote abierto.
 * Debe llamarse dentro de una transacción.
 */
function canal_sumar_compra(
    $conexion,
    int $productoId,
    int $sucursalId,
    int $bssId,
    float $cantidad,
    int $usuarioId = 0
): array {
    if (!is_finite($cantidad) || $cantidad <= 0) {
        throw new Exception('La cantidad de compra no es válida.');
    }

    $filas = canal_consulta(
        $conexion,
        "SELECT stock FROM stock
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
         LIMIT 1 FOR UPDATE",
        'iii',
        [$productoId, $sucursalId, $bssId]
    );
    if (!$filas) {
        throw new Exception('El producto no está asignado a esta sucursal.');
    }
    $stockBase = (float) $filas[0]['stock'];
    $stockNuevo = $stockBase + $cantidad;

    $producto = canal_info_producto($conexion, $productoId, $bssId);
    if (!$producto) {
        throw new Exception('Producto no encontrado.');
    }

    $loteId = 0;
    if ((string) ($producto['modo_control'] ?? 'NORMAL') === 'CANAL') {
        $lote = canal_lote_abierto($conexion, $bssId, $sucursalId, $productoId, false);
        if ($lote) {
            $loteId = (int) $lote['id'];
            $stmt = $conexion->prepare("UPDATE lotes SET kg_inicial = kg_inicial + ?, kg_restante = kg_restante + ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('ddi', $cantidad, $cantidad, $loteId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conexion->prepare(
                "INSERT INTO lotes (bss_id, id_sucursal, padre_id, modo, kg_inicial, kg_restante, usuario_id)
                 VALUES (?, ?, ?, 'CANAL', ?, ?, ?)"
            );
            if ($stmt) {
                $total = $stockBase + $cantidad;
                $stmt->bind_param('iiiddi', $bssId, $sucursalId, $productoId, $total, $total, $usuarioId);
                $stmt->execute();
                $loteId = (int) $stmt->insert_id;
                $stmt->close();
            }
        }
    }

    $stmt = $conexion->prepare(
        "UPDATE stock
         SET stock = ?, ultima_compra_at = NOW(), ultima_compra_kg = ?
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
    );
    if (!$stmt) {
        throw new Exception('No se pudo actualizar el stock de la compra.');
    }
    $stockTexto = canal_numero($stockNuevo);
    $stmt->bind_param('sdiii', $stockTexto, $cantidad, $productoId, $sucursalId, $bssId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('No se pudo actualizar el stock de la compra.');
    }
    $stmt->close();

    return [
        'stock_base' => $stockBase,
        'stock' => $stockNuevo,
        'lote_id' => $loteId,
    ];
}

/**
 * Abre un lote CANAL para el producto padre.
 */
function canal_abrir_lote(
    $conexion,
    int $productoId,
    int $sucursalId,
    int $bssId,
    float $kg,
    float $costoTotal,
    string $proveedor,
    string $notas,
    int $usuarioId,
    bool $sumarStock,
    bool $reactivarCortes
): array {
    if (!is_finite($kg) || $kg <= 0) {
        throw new Exception('El kilaje debe ser mayor que cero.');
    }
    $producto = canal_info_producto($conexion, $productoId, $bssId);
    if (!$producto) {
        throw new Exception('Producto no encontrado.');
    }
    if ((string) ($producto['modo_control'] ?? 'NORMAL') !== 'CANAL') {
        throw new Exception('El producto no está configurado como res en canal.');
    }
    if (canal_lote_abierto($conexion, $bssId, $sucursalId, $productoId, false)) {
        throw new Exception('Ya hay una res abierta para este producto en la sucursal.');
    }

    $filasStock = canal_consulta(
        $conexion,
        "SELECT stock FROM stock
         WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
         LIMIT 1 FOR UPDATE",
        'iii',
        [$productoId, $sucursalId, $bssId]
    );
    if (!$filasStock) {
        throw new Exception('El producto no está asignado a esta sucursal.');
    }
    $stockActual = (float) $filasStock[0]['stock'];
    // Si la res ya estaba en stock, el lote representa el stock confirmado;
    // si se suma, el lote incluye lo que ya había más lo nuevo.
    $kgLote = $sumarStock ? ($stockActual + $kg) : $stockActual;

    $stmt = $conexion->prepare(
        "INSERT INTO lotes (bss_id, id_sucursal, padre_id, modo, kg_inicial, kg_restante,
                            costo_total, proveedor, notas, usuario_id)
         VALUES (?, ?, ?, 'CANAL', ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        throw new Exception('No se pudo registrar la res.');
    }
    $stmt->bind_param('iiidddssi', $bssId, $sucursalId, $productoId, $kgLote, $kgLote, $costoTotal, $proveedor, $notas, $usuarioId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('No se pudo registrar la res.');
    }
    $loteId = (int) $stmt->insert_id;
    $stmt->close();

    if ($costoTotal > 0 && $kg > 0) {
        $costoKg = $costoTotal / $kg;
        $stmtCosto = $conexion->prepare("UPDATE productos SET precio_compra = ?, cantidad_unidades = 1 WHERE id = ? AND bss_id = ?");
        if ($stmtCosto) {
            $stmtCosto->bind_param('dii', $costoKg, $productoId, $bssId);
            $stmtCosto->execute();
            $stmtCosto->close();
        }
    }

    if ($sumarStock) {
        $stmt = $conexion->prepare(
            "UPDATE stock
             SET stock = CAST(stock AS DECIMAL(15,4)) + ?,
                 ultima_compra_at = NOW(), ultima_compra_kg = ?
             WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
        );
        if (!$stmt) {
            throw new Exception('No se pudo sumar el stock de la res.');
        }
        $stmt->bind_param('ddiii', $kg, $kg, $productoId, $sucursalId, $bssId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo sumar el stock de la res.');
        }
        $stmt->close();
    }

    if ($reactivarCortes) {
        $stmt = $conexion->prepare(
            "UPDATE stock s
             INNER JOIN productos p ON p.id = s.id_producto
             LEFT JOIN productos base ON base.id = p.id_producto_relacionado
             SET s.visible_venta = 1
             WHERE (
                 (p.id_producto_relacionado = ? AND p.mayor = 0)
                 OR (p.mayor = 1 AND base.id_producto_relacionado = ?)
             )
             AND s.id_sucursal = ? AND s.bss_id = ?"
        );
        if ($stmt) {
            $stmt->bind_param('iiii', $productoId, $productoId, $sucursalId, $bssId);
            $stmt->execute();
            $stmt->close();
        }
    }

    return ['id' => $loteId];
}

/**
 * Cierra el lote. No toca stock ni crea merma.
 */
function canal_cerrar_lote($conexion, int $loteId, int $bssId, bool $confirmar): array
{
    $filas = canal_consulta(
        $conexion,
        "SELECT * FROM lotes WHERE id = ? AND bss_id = ? LIMIT 1 FOR UPDATE",
        'ii',
        [$loteId, $bssId]
    );
    $lote = $filas[0] ?? null;
    if (!$lote) {
        throw new Exception('Lote no encontrado.');
    }
    if ((string) $lote['estado'] !== 'ABIERTO') {
        throw new Exception('El lote ya está cerrado.');
    }

    $diferencia = (float) $lote['kg_restante'];
    $conciliado = ($diferencia <= 0.0001 || $confirmar) ? 1 : 0;
    $stmt = $conexion->prepare(
        "UPDATE lotes
         SET diferencia_pendiente = ?, conciliado = ?, estado = 'CERRADO', fecha_cierre = NOW()
         WHERE id = ?"
    );
    if (!$stmt) {
        throw new Exception('No se pudo cerrar el lote.');
    }
    $stmt->bind_param('dii', $diferencia, $conciliado, $loteId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('No se pudo cerrar el lote.');
    }
    $stmt->close();

    $padreId = (int) $lote['padre_id'];
    $sucursalId = (int) $lote['id_sucursal'];
    $stmt = $conexion->prepare(
        "UPDATE stock s
         INNER JOIN productos p ON p.id = s.id_producto
         LEFT JOIN productos base ON base.id = p.id_producto_relacionado
         SET s.visible_venta = 0
         WHERE (
             (p.id_producto_relacionado = ? AND p.mayor = 0)
             OR (p.mayor = 1 AND base.id_producto_relacionado = ?)
         )
         AND s.id_sucursal = ? AND s.bss_id = ?"
    );
    if ($stmt) {
        $stmt->bind_param('iiii', $padreId, $padreId, $sucursalId, $bssId);
        $stmt->execute();
        $stmt->close();
    }

    return [
        'diferencia' => $diferencia,
        'conciliado' => $conciliado,
    ];
}

/**
 * Marca como conciliado un lote cerrado con diferencia pendiente.
 */
function canal_marcar_conciliado($conexion, int $loteId, int $bssId, bool $aceptarMontoActual): array
{
    $filas = canal_consulta(
        $conexion,
        "SELECT * FROM lotes WHERE id = ? AND bss_id = ? LIMIT 1 FOR UPDATE",
        'ii',
        [$loteId, $bssId]
    );
    $lote = $filas[0] ?? null;
    if (!$lote) {
        throw new Exception('Lote no encontrado.');
    }
    if ((string) $lote['estado'] !== 'CERRADO') {
        throw new Exception('El lote no está cerrado.');
    }

    $diferencia = (float) $lote['diferencia_pendiente'];
    $stmt = $conexion->prepare(
        "UPDATE lotes SET conciliado = 1, diferencia_pendiente = ? WHERE id = ?"
    );
    if (!$stmt) {
        throw new Exception('No se pudo marcar el lote.');
    }
    $nuevaDiferencia = $aceptarMontoActual ? $diferencia : 0;
    $stmt->bind_param('di', $nuevaDiferencia, $loteId);
    $stmt->execute();
    $stmt->close();

    return ['diferencia' => $nuevaDiferencia, 'conciliado' => 1];
}
