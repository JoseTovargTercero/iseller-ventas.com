<?php
/**
 * Servicio compartido: asignación de mercancía y deudas entre sucursales.
 *
 * Las funciones de este archivo no escriben respuestas HTTP. Devuelven arrays
 * con la forma ['status' => 'ok'|'error', 'msg' => string, 'data' => array].
 * Los endpoints son los encargados de autenticar y emitir el JSON final.
 */

function asig_result_ok(string $msg = '', array $data = []): array
{
    return ['status' => 'ok', 'msg' => $msg, 'data' => $data];
}

function asig_result_error(string $msg): array
{
    return ['status' => 'error', 'msg' => $msg];
}

function asig_bool($value): bool
{
    if (is_bool($value)) {
        return $value;
    }
    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
}

/**
 * bind_param por referencia para listados con filtros dinámicos.
 */
function asig_bind_params(mysqli_stmt $stmt, string $types, array $params): void
{
    $referencias = [];
    foreach ($params as $indice => $valor) {
        $referencias[$indice] = &$params[$indice];
    }
    array_unshift($referencias, $types);
    call_user_func_array([$stmt, 'bind_param'], $referencias);
}

function asig_fecha_valida($fecha): bool
{
    $fecha = trim((string) $fecha);
    if ($fecha === '') {
        return false;
    }
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    return $dt instanceof DateTime && $dt->format('Y-m-d') === $fecha;
}

function asig_numero($valor, int $decimales = 4): string
{
    $texto = number_format((float) $valor, $decimales, '.', '');
    if (strpos($texto, '.') !== false) {
        $texto = rtrim(rtrim($texto, '0'), '.');
    }
    return $texto === '' || $texto === '-' ? '0' : $texto;
}

function asig_dinero($valor): float
{
    return round((float) $valor, 2);
}

/**
 * Costo por unidad. En el POS, productos.precio_compra es el costo del empaque
 * y productos.cantidad_unidades indica cuántas unidades trae ese empaque.
 */
function asig_costo_unitario($precioCompra, $cantidadUnidades, bool $esMayor = false): float
{
    // En los productos al mayor la unidad vendible es la paca/caja: su costo
    // es precio_compra completo. En detal, precio_compra es el costo del
    // empaque y cantidad_unidades indica cuántas unidades trae.
    if ($esMayor) {
        return round((float) $precioCompra, 4);
    }
    $unidades = (float) $cantidadUnidades;
    if ($unidades <= 0) {
        $unidades = 1;
    }
    return round((float) $precioCompra / $unidades, 4);
}

function asig_lineas_input($raw): array
{
    if (!is_array($raw)) {
        return [];
    }

    $lineas = [];
    foreach ($raw as $item) {
        if (!is_array($item)) {
            continue;
        }
        $productoId = (int) ($item['producto_id'] ?? 0);
        $cantidad = round((float) ($item['cantidad'] ?? 0), 4);
        if ($productoId <= 0 || $cantidad <= 0) {
            continue;
        }
        if (isset($lineas[$productoId])) {
            $lineas[$productoId]['cantidad'] = round($lineas[$productoId]['cantidad'] + $cantidad, 4);
        } else {
            $lineas[$productoId] = [
                'producto_id' => $productoId,
                'cantidad' => $cantidad,
            ];
        }
    }

    ksort($lineas);
    return array_values($lineas);
}

function asig_sucursal_existe($conexion, int $bssId, int $sucursalId): bool
{
    $stmt = $conexion->prepare("SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $sucursalId, $bssId);
    $stmt->execute();
    $ok = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $ok;
}

function asig_listar_sucursales($conexion, int $bssId): array
{
    $stmt = $conexion->prepare("SELECT id, nombre, tipo, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $result = $stmt->get_result();
    $sucursales = [];
    while ($row = $result->fetch_assoc()) {
        $sucursales[] = [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'tipo' => $row['tipo'],
            'principal' => (int) $row['principal'],
        ];
    }
    $stmt->close();
    return $sucursales;
}

function asig_get_producto($conexion, int $bssId, int $productoId, bool $forUpdate = false)
{
    $sql = "SELECT id, nombre, precio_compra, cantidad_unidades, porcentaje, mayor, id_producto_relacionado
            FROM productos
            WHERE id = ? AND bss_id = ?
            LIMIT 1";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $productoId, $bssId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Bloquea filas de producto en orden ascendente. Así la rectificación toma
 * los mismos locks en el mismo orden que la aplicación de una asignación.
 */
function asig_bloquear_productos($conexion, int $bssId, array $productoIds): void
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $productoIds), static fn($id) => $id > 0)));
    sort($ids);
    if (count($ids) === 0) {
        return;
    }

    $stmt = $conexion->prepare("SELECT id FROM productos WHERE id = ? AND bss_id = ? FOR UPDATE");
    foreach ($ids as $productoId) {
        $stmt->bind_param('ii', $productoId, $bssId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $result->free();
        }
    }
    $stmt->close();
}

function asig_get_stock($conexion, int $bssId, int $productoId, int $sucursalId, bool $forUpdate = false)
{
    $sql = "SELECT id, stock FROM stock
            WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?
            ORDER BY id ASC
            LIMIT 1";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('iii', $productoId, $sucursalId, $bssId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function asig_buscar_productos($conexion, int $bssId, int $sucursalId, string $busqueda, string $tipo = ''): array
{
    $busqueda = trim($busqueda);
    if ($busqueda === '') {
        return [];
    }

    $tipoSql = '';
    if ($tipo === 'detal') {
        $tipoSql = " AND (p.mayor IS NULL OR p.mayor = 0)";
    } elseif ($tipo === 'mayor') {
        $tipoSql = " AND p.mayor = 1";
    }

    $sql = "SELECT p.id, p.nombre, p.precio_compra, p.cantidad_unidades, p.porcentaje, p.mayor,
                   p.id_producto_relacionado, sb.stock AS stock_base
            FROM productos p
            LEFT JOIN stock sb
              ON sb.id_producto = COALESCE(NULLIF(p.id_producto_relacionado, 0), p.id)
             AND sb.id_sucursal = ?
             AND sb.bss_id = ?
            WHERE p.bss_id = ?
              AND p.activo = 0
              AND (p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ?)
              $tipoSql
            ORDER BY p.nombre ASC
            LIMIT 30";

    $like = '%' . $busqueda . '%';
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('iissss', $sucursalId, $bssId, $bssId, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $esMayor = (int) $row['mayor'] === 1;
        $factor = $esMayor ? max(1, (float) $row['cantidad_unidades']) : 1;
        $stockRegistrado = $row['stock_base'] !== null;
        $stockBase = (float) ($row['stock_base'] ?? 0);
        $stockMostrado = $esMayor ? floor($stockBase / $factor) : $stockBase;

        $productos[] = [
            'id' => (int) $row['id'],
            'nombre' => $row['nombre'],
            'precio_compra' => (float) $row['precio_compra'],
            'cantidad_unidades' => (float) $row['cantidad_unidades'],
            'costo_unitario' => asig_costo_unitario($row['precio_compra'], $row['cantidad_unidades'], $esMayor),
            'mayor' => $esMayor,
            'stock' => $stockRegistrado ? $stockMostrado : 0,
            'stock_registrado' => $stockRegistrado,
        ];
    }
    $stmt->close();
    return $productos;
}

/**
 * Valida productos y existencias. Si $lockStock es true, bloquea las filas
 * de producto y de stock de origen para que no cambien dentro de la transacción.
 */
function asig_validar_lineas($conexion, int $bssId, int $sucursalOrigen, array $detalles, bool $descuentaOrigen, bool $lockStock = false): array
{
    $detalles = asig_lineas_input($detalles);
    if (count($detalles) === 0) {
        return asig_result_error('Agregue al menos un producto a la asignación.');
    }
    if (count($detalles) > 500) {
        return asig_result_error('La asignación supera el máximo de 500 productos.');
    }

    $lineas = [];
    $requeridos = [];
    foreach ($detalles as $detalle) {
        $producto = asig_get_producto($conexion, $bssId, $detalle['producto_id'], $lockStock);
        if (!$producto) {
            return asig_result_error('Uno de los productos ya no existe en el catálogo.');
        }

        $esMayor = (int) $producto['mayor'] === 1;
        $factorStock = $esMayor ? max(1, (float) $producto['cantidad_unidades']) : 1;
        $stockProductoId = $esMayor ? (int) ($producto['id_producto_relacionado'] ?? 0) : (int) $producto['id'];
        if ($stockProductoId <= 0) {
            $stockProductoId = (int) $producto['id'];
        }

        $stockId = null;
        $stockActual = 0.0;
        $stockRegistrado = false;
        $stockMostrado = 0.0;
        $cantidadStock = $detalle['cantidad'] * $factorStock;

        if ($descuentaOrigen) {
            $stock = asig_get_stock($conexion, $bssId, $stockProductoId, $sucursalOrigen, $lockStock);
            if (!$stock) {
                return asig_result_error('El producto "' . $producto['nombre'] . '" no tiene stock registrado en la sucursal de origen.');
            }
            $stockId = (int) $stock['id'];
            $stockActual = (float) $stock['stock'];
            $stockRegistrado = true;
            $stockMostrado = $esMayor ? floor($stockActual / $factorStock) : $stockActual;
            $requeridos[$stockProductoId] = ($requeridos[$stockProductoId] ?? 0) + $cantidadStock;
            if ($stockActual + 0.0001 < $cantidadStock) {
                return asig_result_error('No hay existencia suficiente de "' . $producto['nombre'] . '" en la sucursal de origen. Disponible: ' . asig_numero($stockMostrado) . '.');
            }
        }

        $costoUnitario = asig_costo_unitario($producto['precio_compra'], $producto['cantidad_unidades'], $esMayor);
        $subtotal = asig_dinero($detalle['cantidad'] * $costoUnitario);

        $lineas[] = [
            'producto_id' => (int) $producto['id'],
            'producto_nombre' => $producto['nombre'],
            'cantidad' => $detalle['cantidad'],
            'costo_unitario' => $costoUnitario,
            'subtotal' => $subtotal,
            'porcentaje' => (string) ($producto['porcentaje'] ?? '0'),
            'es_mayor' => $esMayor,
            'factor_stock' => $factorStock,
            'stock_producto_id' => $stockProductoId,
            'stock_id' => $stockId,
            'stock_actual' => $stockActual,
            'stock_mostrado' => $stockMostrado,
            'stock_registrado' => $stockRegistrado,
        ];
    }

    // Dos líneas pueden compartir el mismo stock base (por ejemplo detal +
    // mayor del mismo producto). La suma debe caber en la existencia.
    foreach ($requeridos as $stockProductoId => $totalRequerido) {
        $stock = asig_get_stock($conexion, $bssId, (int) $stockProductoId, $sucursalOrigen, $lockStock);
        if (!$stock || (float) $stock['stock'] + 0.0001 < $totalRequerido) {
            return asig_result_error('No hay existencia suficiente en la sucursal de origen para completar la asignación.');
        }
    }

    return asig_result_ok('', ['lineas' => $lineas]);
}

function asig_get_asignacion($conexion, int $asignacionId, int $bssId, bool $forUpdate = false)
{
    $sql = "SELECT * FROM asignaciones_sucursales WHERE id = ? AND bss_id = ? LIMIT 1";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $asignacionId, $bssId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function asig_get_detalles($conexion, int $asignacionId): array
{
    $stmt = $conexion->prepare("SELECT * FROM asignaciones_sucursales_detalles WHERE asignacion_id = ? ORDER BY producto_id ASC");
    $stmt->bind_param('i', $asignacionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $detalles = [];
    while ($row = $result->fetch_assoc()) {
        $detalles[] = $row;
    }
    $stmt->close();
    return $detalles;
}

function asig_get_cuenta_por_asignacion($conexion, int $asignacionId, int $bssId, bool $forUpdate = false)
{
    $sql = "SELECT * FROM cuentas_sucursales WHERE asignacion_id = ? AND bss_id = ? LIMIT 1";
    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $asignacionId, $bssId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function asig_bitacora($conexion, int $asignacionId, int $usuarioId, string $accion, string $motivo = '', $antes = null, $despues = null): void
{
    $antesJson = $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null;
    $despuesJson = $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null;

    $stmt = $conexion->prepare(
        "INSERT INTO asignaciones_sucursales_bitacora
         (asignacion_id, usuario_id, accion, motivo, datos_anteriores, datos_nuevos)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iissss', $asignacionId, $usuarioId, $accion, $motivo, $antesJson, $despuesJson);
    $stmt->execute();
    $stmt->close();
}

function asig_insertar_detalles($conexion, int $asignacionId, array $lineas): void
{
    $stmt = $conexion->prepare(
        "INSERT INTO asignaciones_sucursales_detalles
         (asignacion_id, producto_id, producto_nombre, cantidad, costo_unitario, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach ($lineas as $linea) {
        $productoId = (int) $linea['producto_id'];
        $productoNombre = (string) $linea['producto_nombre'];
        $cantidad = $linea['cantidad'];
        $costo = $linea['costo_unitario'];
        $subtotal = $linea['subtotal'];
        $stmt->bind_param(
            'iisddd',
            $asignacionId,
            $productoId,
            $productoNombre,
            $cantidad,
            $costo,
            $subtotal
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo guardar el detalle de la asignación.');
        }
    }
    $stmt->close();
}

function asig_movimiento($conexion, int $bssId, int $asignacionId, int $productoId, int $sucursalId, string $tipo, float $cantidad, float $antes, float $despues): void
{
    $stmt = $conexion->prepare(
        "INSERT INTO asignaciones_sucursales_movimientos
         (bss_id, asignacion_id, producto_id, sucursal_id, tipo, cantidad, stock_antes, stock_despues)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('iiiisddd', $bssId, $asignacionId, $productoId, $sucursalId, $tipo, $cantidad, $antes, $despues);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('No se pudo registrar el movimiento de inventario.');
    }
    $stmt->close();
}

function asig_aplicar_stock($conexion, int $bssId, int $asignacionId, array $asignacion, array $lineas): void
{
    $sucursalOrigen = (int) $asignacion['sucursal_origen'];
    $sucursalDestino = (int) $asignacion['sucursal_destino'];
    $descuentaOrigen = (bool) $asignacion['descuenta_origen'];
    $sumaDestino = (bool) $asignacion['suma_destino'];

    foreach ($lineas as $linea) {
        $productoId = (int) $linea['producto_id'];
        $stockProductoId = (int) ($linea['stock_producto_id'] ?? $productoId);
        $factorStock = (float) ($linea['factor_stock'] ?? 1);
        if ($factorStock <= 0) {
            $factorStock = 1;
        }
        $cantidadStock = (float) $linea['cantidad'] * $factorStock;

        if ($descuentaOrigen) {
            // Se vuelve a leer la fila bloqueada: dos líneas de la misma
            // asignación (por ejemplo detal + mayor) pueden compartir el
            // mismo stock base y deben descontar de forma acumulativa.
            $stockOrigenActual = asig_get_stock($conexion, $bssId, $stockProductoId, $sucursalOrigen, true);
            if (!$stockOrigenActual) {
                throw new Exception('No se pudo bloquear el stock de origen.');
            }
            $stockId = (int) $stockOrigenActual['id'];
            $stockAntes = (float) $stockOrigenActual['stock'];
            $stockDespues = $stockAntes - $cantidadStock;
            if ($stockDespues < -0.0001) {
                throw new Exception('No hay existencia suficiente para "' . $linea['producto_nombre'] . '".');
            }
            $stockDespues = max(0, $stockDespues);
            $stockTexto = asig_numero($stockDespues);

            $stmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ?");
            $stmt->bind_param('si', $stockTexto, $stockId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('No se pudo descontar el stock de origen.');
            }
            $stmt->close();

            asig_movimiento($conexion, $bssId, $asignacionId, $productoId, $sucursalOrigen, 'ORIGEN_DESCUENTO', $cantidadStock, $stockAntes, $stockDespues);
        }

        if ($sumaDestino) {
            $stockDestino = asig_get_stock($conexion, $bssId, $stockProductoId, $sucursalDestino, true);
            if ($stockDestino) {
                $stockAntes = (float) $stockDestino['stock'];
                $stockDespues = $stockAntes + $cantidadStock;
                $stockTexto = asig_numero($stockDespues);
                $stockDestinoId = (int) $stockDestino['id'];

                $stmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ?");
                $stmt->bind_param('si', $stockTexto, $stockDestinoId);
                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Exception('No se pudo sumar el stock en la sucursal destino.');
                }
                $stmt->close();
            } else {
                $stockAntes = 0.0;
                $stockDespues = $cantidadStock;
                $stockTexto = asig_numero($stockDespues);
                $porcentaje = (string) ($linea['porcentaje'] ?? '0');

                $stmt = $conexion->prepare(
                    "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->bind_param('issii', $stockProductoId, $porcentaje, $stockTexto, $sucursalDestino, $bssId);
                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Exception('No se pudo crear el producto en la sucursal destino.');
                }
                $stockDestinoId = (int) $stmt->insert_id;
                $stmt->close();
            }

            // Al mayor, la fila de la paca es solo un puntero a la fila base.
            // Se crea en destino para que el producto quede vendible allí.
            if (!empty($linea['es_mayor']) && $stockDestinoId > 0) {
                $stockPaca = asig_get_stock($conexion, $bssId, $productoId, $sucursalDestino, true);
                if (!$stockPaca) {
                    $porcentajePaca = (string) ($linea['porcentaje'] ?? '0');
                    $stmt = $conexion->prepare(
                        "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, id_stock)
                         VALUES (?, ?, '0', ?, ?, 1, ?)"
                    );
                    $stmt->bind_param('isiii', $productoId, $porcentajePaca, $sucursalDestino, $bssId, $stockDestinoId);
                    if (!$stmt->execute()) {
                        $stmt->close();
                        throw new Exception('No se pudo enlazar el producto al mayor en la sucursal destino.');
                    }
                    $stmt->close();
                }
            }

            asig_movimiento($conexion, $bssId, $asignacionId, $productoId, $sucursalDestino, 'DESTINO_INCREMENTO', $cantidadStock, $stockAntes, $stockDespues);
        }
    }
}

function asig_guardar_asignacion($conexion, int $bssId, int $usuarioId, array $input): array
{
    $id = (int) ($input['id'] ?? 0);
    $sucursalOrigen = (int) ($input['sucursal_origen'] ?? 0);
    $sucursalDestino = (int) ($input['sucursal_destino'] ?? 0);
    $fecha = trim((string) ($input['fecha'] ?? ''));
    $observaciones = trim((string) ($input['observaciones'] ?? ''));
    $descuentaOrigen = asig_bool($input['descuenta_origen'] ?? true);
    $sumaDestino = asig_bool($input['suma_destino'] ?? true);
    $detalles = $input['detalles'] ?? [];

    if (!asig_sucursal_existe($conexion, $bssId, $sucursalOrigen) || !asig_sucursal_existe($conexion, $bssId, $sucursalDestino)) {
        return asig_result_error('La sucursal de origen o destino no pertenece a este negocio.');
    }
    if ($sucursalOrigen === $sucursalDestino) {
        return asig_result_error('La sucursal de origen y destino no pueden ser la misma.');
    }
    if (!asig_fecha_valida($fecha)) {
        return asig_result_error('La fecha de la asignación no es válida.');
    }

    $validacion = asig_validar_lineas($conexion, $bssId, $sucursalOrigen, $detalles, $descuentaOrigen, false);
    if ($validacion['status'] !== 'ok') {
        return $validacion;
    }
    $lineas = $validacion['data']['lineas'];
    $total = asig_dinero(array_sum(array_column($lineas, 'subtotal')));

    try {
        $conexion->begin_transaction();

        if ($id > 0) {
            $asignacion = asig_get_asignacion($conexion, $id, $bssId, true);
            if (!$asignacion) {
                throw new Exception('La asignación no existe.');
            }
            if ((int) $asignacion['usuario_id'] !== $usuarioId) {
                throw new Exception('Solo el usuario que creó la asignación puede modificarla.');
            }
            if ($asignacion['estado'] !== 'BORRADOR') {
                throw new Exception('La asignación ya fue aplicada. Use la rectificación con motivo.');
            }

            $stmt = $conexion->prepare(
                "UPDATE asignaciones_sucursales
                 SET sucursal_origen = ?, sucursal_destino = ?, fecha = ?, descuenta_origen = ?, suma_destino = ?, total = ?, observaciones = ?
                 WHERE id = ? AND bss_id = ? AND estado = 'BORRADOR'"
            );
            $descuentaInt = $descuentaOrigen ? 1 : 0;
            $sumaInt = $sumaDestino ? 1 : 0;
            $stmt->bind_param('iisiidsii', $sucursalOrigen, $sucursalDestino, $fecha, $descuentaInt, $sumaInt, $total, $observaciones, $id, $bssId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('No se pudo actualizar la asignación.');
            }
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM asignaciones_sucursales_detalles WHERE asignacion_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            asig_insertar_detalles($conexion, $id, $lineas);
            asig_bitacora($conexion, $id, $usuarioId, 'EDITADA', '', null, ['lineas' => $lineas, 'total' => $total]);

            $conexion->commit();
            return asig_result_ok('Asignación actualizada.', [
                'id' => $id,
                'numero_documento' => $asignacion['numero_documento'],
                'total' => $total,
                'estado' => 'BORRADOR',
            ]);
        }

        $stmt = $conexion->prepare(
            "INSERT INTO asignaciones_sucursales
             (bss_id, numero_documento, sucursal_origen, sucursal_destino, fecha, usuario_id, estado, descuenta_origen, suma_destino, total, observaciones)
             VALUES (?, NULL, ?, ?, ?, ?, 'BORRADOR', ?, ?, ?, ?)"
        );
        $descuentaInt = $descuentaOrigen ? 1 : 0;
        $sumaInt = $sumaDestino ? 1 : 0;
        $stmt->bind_param('iiisiiids', $bssId, $sucursalOrigen, $sucursalDestino, $fecha, $usuarioId, $descuentaInt, $sumaInt, $total, $observaciones);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo crear la asignación.');
        }
        $nuevoId = (int) $stmt->insert_id;
        $stmt->close();

        $numero = 'ASG-' . str_pad((string) $nuevoId, 6, '0', STR_PAD_LEFT);
        $stmt = $conexion->prepare("UPDATE asignaciones_sucursales SET numero_documento = ? WHERE id = ? AND bss_id = ?");
        $stmt->bind_param('sii', $numero, $nuevoId, $bssId);
        $stmt->execute();
        $stmt->close();

        asig_insertar_detalles($conexion, $nuevoId, $lineas);
        asig_bitacora($conexion, $nuevoId, $usuarioId, 'CREADA', '', null, ['lineas' => $lineas, 'total' => $total]);

        $conexion->commit();
        return asig_result_ok('Asignación guardada como borrador.', [
            'id' => $nuevoId,
            'numero_documento' => $numero,
            'total' => $total,
            'estado' => 'BORRADOR',
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        return asig_result_error($e->getMessage());
    }
}

function asig_aplicar_asignacion($conexion, int $bssId, int $usuarioId, int $asignacionId): array
{
    try {
        $conexion->begin_transaction();

        $asignacion = asig_get_asignacion($conexion, $asignacionId, $bssId, true);
        if (!$asignacion) {
            throw new Exception('La asignación no existe.');
        }
        if ((int) $asignacion['usuario_id'] !== $usuarioId) {
            throw new Exception('Solo el usuario que creó la asignación puede aplicarla.');
        }
        if ($asignacion['estado'] !== 'BORRADOR') {
            throw new Exception('Esta asignación ya fue aplicada. No puede procesarse dos veces.');
        }

        $detalles = asig_get_detalles($conexion, $asignacionId);
        if (count($detalles) === 0) {
            throw new Exception('La asignación no tiene productos.');
        }

        $rawDetalles = [];
        foreach ($detalles as $detalle) {
            $rawDetalles[] = [
                'producto_id' => (int) $detalle['producto_id'],
                'cantidad' => (float) $detalle['cantidad'],
            ];
        }

        $validacion = asig_validar_lineas(
            $conexion,
            $bssId,
            (int) $asignacion['sucursal_origen'],
            $rawDetalles,
            (bool) $asignacion['descuenta_origen'],
            true
        );
        if ($validacion['status'] !== 'ok') {
            throw new Exception($validacion['msg']);
        }
        $lineas = $validacion['data']['lineas'];
        $total = asig_dinero(array_sum(array_column($lineas, 'subtotal')));

        // El costo histórico se fija al momento de aplicar, no al crear el borrador.
        $stmt = $conexion->prepare(
            "UPDATE asignaciones_sucursales_detalles
             SET producto_nombre = ?, costo_unitario = ?, subtotal = ?
             WHERE asignacion_id = ? AND producto_id = ?"
        );
        foreach ($lineas as $linea) {
            $productoNombre = (string) $linea['producto_nombre'];
            $costoUnitario = (float) $linea['costo_unitario'];
            $subtotalLinea = (float) $linea['subtotal'];
            $productoId = (int) $linea['producto_id'];
            $stmt->bind_param(
                'sddii',
                $productoNombre,
                $costoUnitario,
                $subtotalLinea,
                $asignacionId,
                $productoId
            );
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('No se pudo actualizar el costo histórico del detalle.');
            }
        }
        $stmt->close();

        asig_aplicar_stock($conexion, $bssId, $asignacionId, $asignacion, $lineas);

        $stmt = $conexion->prepare(
            "INSERT INTO cuentas_sucursales
             (bss_id, asignacion_id, sucursal_acreedora, sucursal_deudora, monto_original)
             VALUES (?, ?, ?, ?, ?)"
        );
        $sucursalAcreedora = (int) $asignacion['sucursal_origen'];
        $sucursalDeudora = (int) $asignacion['sucursal_destino'];
        $stmt->bind_param(
            'iiiid',
            $bssId,
            $asignacionId,
            $sucursalAcreedora,
            $sucursalDeudora,
            $total
        );
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo generar la obligación económica. Verifique que la asignación no tenga una cuenta previa.');
        }
        $cuentaId = (int) $stmt->insert_id;
        $stmt->close();

        $stmt = $conexion->prepare(
            "UPDATE asignaciones_sucursales
             SET total = ?, estado = 'APLICADO', aplicado_at = NOW()
             WHERE id = ? AND bss_id = ? AND estado = 'BORRADOR'"
        );
        $stmt->bind_param('dii', $total, $asignacionId, $bssId);
        $stmt->execute();
        $afectadas = $stmt->affected_rows;
        $stmt->close();
        if ($afectadas !== 1) {
            throw new Exception('La asignación cambió de estado durante el proceso. Intente nuevamente.');
        }

        asig_bitacora($conexion, $asignacionId, $usuarioId, 'APLICADA', '', null, ['total' => $total, 'cuenta_id' => $cuentaId]);

        $conexion->commit();
        return asig_result_ok('Asignación aplicada correctamente.', [
            'id' => $asignacionId,
            'cuenta_id' => $cuentaId,
            'total' => $total,
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        return asig_result_error($e->getMessage());
    }
}

function asig_reversar_stock($conexion, int $bssId, int $asignacionId, array $asignacion): void
{
    $sucursalOrigen = (int) $asignacion['sucursal_origen'];
    $sucursalDestino = (int) $asignacion['sucursal_destino'];
    $descuentaOrigen = (bool) $asignacion['descuenta_origen'];
    $sumaDestino = (bool) $asignacion['suma_destino'];
    $detalles = asig_get_detalles($conexion, $asignacionId);

    foreach ($detalles as $detalle) {
        $productoId = (int) $detalle['producto_id'];
        $producto = asig_get_producto($conexion, $bssId, $productoId);
        if (!$producto) {
            throw new Exception('No se puede rectificar: uno de los productos ya no existe en el catálogo.');
        }

        $esMayor = (int) $producto['mayor'] === 1;
        $factorStock = $esMayor ? max(1, (float) $producto['cantidad_unidades']) : 1;
        $stockProductoId = $esMayor ? (int) ($producto['id_producto_relacionado'] ?? 0) : $productoId;
        if ($stockProductoId <= 0) {
            $stockProductoId = $productoId;
        }
        $cantidadStock = (float) $detalle['cantidad'] * $factorStock;

        if ($descuentaOrigen) {
            $stock = asig_get_stock($conexion, $bssId, $stockProductoId, $sucursalOrigen, true);
            if (!$stock) {
                throw new Exception('No se puede revertir: el producto "' . $detalle['producto_nombre'] . '" ya no tiene stock en la sucursal de origen.');
            }
            $stockAntes = (float) $stock['stock'];
            $stockDespues = $stockAntes + $cantidadStock;
            $stockTexto = asig_numero($stockDespues);
            $stockId = (int) $stock['id'];

            $stmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ?");
            $stmt->bind_param('si', $stockTexto, $stockId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('No se pudo revertir el stock de origen.');
            }
            $stmt->close();

            asig_movimiento($conexion, $bssId, $asignacionId, $productoId, $sucursalOrigen, 'REVERSION_ORIGEN', $cantidadStock, $stockAntes, $stockDespues);
        }

        if ($sumaDestino) {
            $stock = asig_get_stock($conexion, $bssId, $stockProductoId, $sucursalDestino, true);
            if (!$stock || (float) $stock['stock'] + 0.0001 < $cantidadStock) {
                throw new Exception('No se puede revertir: en la sucursal destino ya no está disponible la cantidad de "' . $detalle['producto_nombre'] . '".');
            }
            $stockAntes = (float) $stock['stock'];
            $stockDespues = $stockAntes - $cantidadStock;
            $stockTexto = asig_numero($stockDespues);
            $stockId = (int) $stock['id'];

            $stmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ?");
            $stmt->bind_param('si', $stockTexto, $stockId);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('No se pudo revertir el stock de destino.');
            }
            $stmt->close();

            asig_movimiento($conexion, $bssId, $asignacionId, $productoId, $sucursalDestino, 'REVERSION_DESTINO', $cantidadStock, $stockAntes, $stockDespues);
        }
    }
}

function asig_rectificar_asignacion($conexion, int $bssId, int $usuarioId, int $asignacionId, array $input, string $motivo): array
{
    $motivo = trim($motivo);
    if ($motivo === '') {
        return asig_result_error('Debe indicar la razón de la rectificación.');
    }

    $sucursalOrigen = (int) ($input['sucursal_origen'] ?? 0);
    $sucursalDestino = (int) ($input['sucursal_destino'] ?? 0);
    $fecha = trim((string) ($input['fecha'] ?? ''));
    $observaciones = trim((string) ($input['observaciones'] ?? ''));
    $descuentaOrigen = asig_bool($input['descuenta_origen'] ?? true);
    $sumaDestino = asig_bool($input['suma_destino'] ?? true);
    $detalles = $input['detalles'] ?? [];

    if (!asig_sucursal_existe($conexion, $bssId, $sucursalOrigen) || !asig_sucursal_existe($conexion, $bssId, $sucursalDestino)) {
        return asig_result_error('La sucursal de origen o destino no pertenece a este negocio.');
    }
    if ($sucursalOrigen === $sucursalDestino) {
        return asig_result_error('La sucursal de origen y destino no pueden ser la misma.');
    }
    if (!asig_fecha_valida($fecha)) {
        return asig_result_error('La fecha de la asignación no es válida.');
    }

    try {
        $conexion->begin_transaction();

        $asignacion = asig_get_asignacion($conexion, $asignacionId, $bssId, true);
        if (!$asignacion) {
            throw new Exception('La asignación no existe.');
        }
        if ((int) $asignacion['usuario_id'] !== $usuarioId) {
            throw new Exception('Solo el usuario que creó la asignación puede rectificarla.');
        }
        if ($asignacion['estado'] !== 'APLICADO') {
            throw new Exception('Solo las asignaciones aplicadas requieren rectificación.');
        }

        $cuenta = asig_get_cuenta_por_asignacion($conexion, $asignacionId, $bssId, true);
        if (!$cuenta) {
            throw new Exception('La asignación no tiene una obligación económica asociada.');
        }

        $cuentaId = (int) $cuenta['id'];
        $stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM pagos_sucursales WHERE cuenta_sucursal_id = ?");
        $stmt->bind_param('i', $cuentaId);
        $stmt->execute();
        $pagos = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();
        if ($pagos > 0) {
            throw new Exception('No se puede rectificar una asignación que ya tiene abonos registrados.');
        }

        $snapshotAnterior = [
            'sucursal_origen' => (int) $asignacion['sucursal_origen'],
            'sucursal_destino' => (int) $asignacion['sucursal_destino'],
            'fecha' => $asignacion['fecha'],
            'descuenta_origen' => (int) $asignacion['descuenta_origen'],
            'suma_destino' => (int) $asignacion['suma_destino'],
            'total' => (float) $asignacion['total'],
            'detalles' => asig_get_detalles($conexion, $asignacionId),
        ];

        $productosABloquear = [];
        foreach ($snapshotAnterior['detalles'] as $detalleAnterior) {
            $productosABloquear[] = (int) $detalleAnterior['producto_id'];
        }
        foreach (asig_lineas_input($detalles) as $detalleNuevo) {
            $productosABloquear[] = (int) $detalleNuevo['producto_id'];
        }
        asig_bloquear_productos($conexion, $bssId, $productosABloquear);

        asig_reversar_stock($conexion, $bssId, $asignacionId, $asignacion);

        $validacion = asig_validar_lineas($conexion, $bssId, $sucursalOrigen, $detalles, $descuentaOrigen, true);
        if ($validacion['status'] !== 'ok') {
            throw new Exception($validacion['msg']);
        }
        $lineas = $validacion['data']['lineas'];
        $total = asig_dinero(array_sum(array_column($lineas, 'subtotal')));

        $stmt = $conexion->prepare(
            "UPDATE asignaciones_sucursales
             SET sucursal_origen = ?, sucursal_destino = ?, fecha = ?, descuenta_origen = ?, suma_destino = ?, total = ?, observaciones = ?
             WHERE id = ? AND bss_id = ?"
        );
        $descuentaInt = $descuentaOrigen ? 1 : 0;
        $sumaInt = $sumaDestino ? 1 : 0;
        $stmt->bind_param('iisiidsii', $sucursalOrigen, $sucursalDestino, $fecha, $descuentaInt, $sumaInt, $total, $observaciones, $asignacionId, $bssId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo actualizar la asignación.');
        }
        $stmt->close();

        $stmt = $conexion->prepare("DELETE FROM asignaciones_sucursales_detalles WHERE asignacion_id = ?");
        $stmt->bind_param('i', $asignacionId);
        $stmt->execute();
        $stmt->close();

        asig_insertar_detalles($conexion, $asignacionId, $lineas);

        $nuevaAsignacion = [
            'sucursal_origen' => $sucursalOrigen,
            'sucursal_destino' => $sucursalDestino,
            'descuenta_origen' => $descuentaInt,
            'suma_destino' => $sumaInt,
        ];
        asig_aplicar_stock($conexion, $bssId, $asignacionId, $nuevaAsignacion, $lineas);

        $stmt = $conexion->prepare(
            "UPDATE cuentas_sucursales
             SET sucursal_acreedora = ?, sucursal_deudora = ?, monto_original = ?
             WHERE id = ? AND bss_id = ?"
        );
        $cuentaId = (int) $cuenta['id'];
        $stmt->bind_param('iidii', $sucursalOrigen, $sucursalDestino, $total, $cuentaId, $bssId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo actualizar la obligación económica.');
        }
        $stmt->close();

        asig_bitacora($conexion, $asignacionId, $usuarioId, 'RECTIFICADA', $motivo, $snapshotAnterior, [
            'sucursal_origen' => $sucursalOrigen,
            'sucursal_destino' => $sucursalDestino,
            'fecha' => $fecha,
            'descuenta_origen' => $descuentaInt,
            'suma_destino' => $sumaInt,
            'total' => $total,
            'detalles' => $lineas,
        ]);

        $conexion->commit();
        return asig_result_ok('Asignación rectificada correctamente.', [
            'id' => $asignacionId,
            'total' => $total,
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        return asig_result_error($e->getMessage());
    }
}

function asig_eliminar_asignacion($conexion, int $bssId, int $usuarioId, int $asignacionId): array
{
    try {
        $conexion->begin_transaction();

        $asignacion = asig_get_asignacion($conexion, $asignacionId, $bssId, true);
        if (!$asignacion) {
            throw new Exception('La asignación no existe.');
        }
        if ((int) $asignacion['usuario_id'] !== $usuarioId) {
            throw new Exception('Solo el usuario que creó y aplicó la asignación puede eliminarla.');
        }
        if (!in_array($asignacion['estado'], ['BORRADOR', 'APLICADO'], true)) {
            throw new Exception('El estado de la asignación no permite eliminarla.');
        }

        $cuenta = null;
        if ($asignacion['estado'] === 'APLICADO') {
            // Mismo orden de bloqueo que la rectificación: cuenta, productos y stock.
            $cuenta = asig_get_cuenta_por_asignacion($conexion, $asignacionId, $bssId, true);

            $detalles = asig_get_detalles($conexion, $asignacionId);
            $productos = [];
            foreach ($detalles as $detalle) {
                $productos[] = (int) $detalle['producto_id'];
            }
            asig_bloquear_productos($conexion, $bssId, $productos);

            // Revierte el inventario antes de borrar los movimientos.
            asig_reversar_stock($conexion, $bssId, $asignacionId, $asignacion);

            if ($cuenta) {
                $cuentaId = (int) $cuenta['id'];

                $stmt = $conexion->prepare("DELETE FROM pagos_sucursales WHERE cuenta_sucursal_id = ?");
                $stmt->bind_param('i', $cuentaId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexion->prepare("DELETE FROM cuentas_sucursales WHERE id = ? AND bss_id = ?");
                $stmt->bind_param('ii', $cuentaId, $bssId);
                $stmt->execute();
                $stmt->close();
            }
        }

        $stmt = $conexion->prepare("DELETE FROM asignaciones_sucursales_movimientos WHERE asignacion_id = ? AND bss_id = ?");
        $stmt->bind_param('ii', $asignacionId, $bssId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare("DELETE FROM asignaciones_sucursales_bitacora WHERE asignacion_id = ?");
        $stmt->bind_param('i', $asignacionId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare("DELETE FROM asignaciones_sucursales_detalles WHERE asignacion_id = ?");
        $stmt->bind_param('i', $asignacionId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conexion->prepare("DELETE FROM asignaciones_sucursales WHERE id = ? AND bss_id = ?");
        $stmt->bind_param('ii', $asignacionId, $bssId);
        if (!$stmt->execute() || $stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception('No se pudo eliminar la asignación.');
        }
        $stmt->close();

        $conexion->commit();
        return asig_result_ok(
            $asignacion['estado'] === 'APLICADO'
                ? 'Asignación eliminada. Se revirtió el inventario y se borraron la deuda, los abonos y la bitácora.'
                : 'Borrador eliminado.'
        );
    } catch (Throwable $e) {
        $conexion->rollback();
        return asig_result_error($e->getMessage());
    }
}

function asig_listar_asignaciones($conexion, int $bssId, int $usuarioId, bool $esAdmin, int $sucursalSesion, array $filtros = []): array
{
    $where = ["a.bss_id = ?"];
    $types = 'i';
    $params = [$bssId];

    if (!$esAdmin) {
        $where[] = "(a.sucursal_origen = ? OR a.sucursal_destino = ?)";
        $types .= 'ii';
        $params[] = $sucursalSesion;
        $params[] = $sucursalSesion;
    }

    $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));
    if (in_array($estado, ['BORRADOR', 'APLICADO'], true)) {
        $where[] = "a.estado = ?";
        $types .= 's';
        $params[] = $estado;
    }

    $sucursal = (int) ($filtros['sucursal'] ?? 0);
    if ($sucursal > 0) {
        $where[] = "(a.sucursal_origen = ? OR a.sucursal_destino = ?)";
        $types .= 'ii';
        $params[] = $sucursal;
        $params[] = $sucursal;
    }

    $desde = trim((string) ($filtros['desde'] ?? ''));
    if (asig_fecha_valida($desde)) {
        $where[] = "a.fecha >= ?";
        $types .= 's';
        $params[] = $desde;
    }

    $hasta = trim((string) ($filtros['hasta'] ?? ''));
    if (asig_fecha_valida($hasta)) {
        $where[] = "a.fecha <= ?";
        $types .= 's';
        $params[] = $hasta;
    }

    $sql = "SELECT a.*, so.nombre AS origen_nombre, sd.nombre AS destino_nombre,
                   u.nombre AS usuario_nombre, c.id AS cuenta_id,
                   (SELECT COUNT(*) FROM pagos_sucursales ps WHERE ps.cuenta_sucursal_id = c.id) AS pagos_count
            FROM asignaciones_sucursales a
            LEFT JOIN sucursales so ON so.id = a.sucursal_origen
            LEFT JOIN sucursales sd ON sd.id = a.sucursal_destino
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            LEFT JOIN cuentas_sucursales c ON c.asignacion_id = a.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY a.fecha DESC, a.id DESC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    asig_bind_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $esCreador = (int) $row['usuario_id'] === $usuarioId;
        $esDeudora = $esAdmin || (int) $row['sucursal_destino'] === $sucursalSesion;
        $pagosCount = $esDeudora ? (int) $row['pagos_count'] : 0;
        $cuentaId = $esDeudora && $row['cuenta_id'] !== null ? (int) $row['cuenta_id'] : null;
        $items[] = [
            'id' => (int) $row['id'],
            'numero_documento' => $row['numero_documento'],
            'fecha' => $row['fecha'],
            'sucursal_origen' => (int) $row['sucursal_origen'],
            'sucursal_destino' => (int) $row['sucursal_destino'],
            'origen_nombre' => $row['origen_nombre'],
            'destino_nombre' => $row['destino_nombre'],
            'usuario_id' => (int) $row['usuario_id'],
            'usuario_nombre' => $row['usuario_nombre'],
            'estado' => $row['estado'],
            'descuenta_origen' => (int) $row['descuenta_origen'],
            'suma_destino' => (int) $row['suma_destino'],
            'total' => (float) $row['total'],
            'observaciones' => $row['observaciones'],
            'aplicado_at' => $row['aplicado_at'],
            'cuenta_id' => $cuentaId,
            'pagos_count' => $pagosCount,
            'puede_editar' => $esAdmin && $esCreador && $row['estado'] === 'BORRADOR',
            'puede_aplicar' => $esAdmin && $esCreador && $row['estado'] === 'BORRADOR',
            'puede_rectificar' => $esAdmin && $esCreador && $row['estado'] === 'APLICADO' && $pagosCount === 0,
            'puede_eliminar' => $esAdmin && $esCreador && in_array($row['estado'], ['BORRADOR', 'APLICADO'], true),
        ];
    }
    $stmt->close();
    return $items;
}

function asig_detalle_asignacion($conexion, int $bssId, int $usuarioId, bool $esAdmin, int $sucursalSesion, int $asignacionId): array
{
    $sql = "SELECT a.*, so.nombre AS origen_nombre, sd.nombre AS destino_nombre, u.nombre AS usuario_nombre
            FROM asignaciones_sucursales a
            LEFT JOIN sucursales so ON so.id = a.sucursal_origen
            LEFT JOIN sucursales sd ON sd.id = a.sucursal_destino
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            WHERE a.id = ? AND a.bss_id = ?
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $asignacionId, $bssId);
    $stmt->execute();
    $asignacion = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$asignacion) {
        return asig_result_error('La asignación no existe.');
    }
    if (!$esAdmin && (int) $asignacion['sucursal_origen'] !== $sucursalSesion && (int) $asignacion['sucursal_destino'] !== $sucursalSesion) {
        return asig_result_error('No tiene acceso a esta asignación.');
    }

    $detalles = asig_get_detalles($conexion, $asignacionId);
    foreach ($detalles as &$detalle) {
        $producto = asig_get_producto($conexion, $bssId, (int) $detalle['producto_id']);
        $esMayor = $producto && (int) $producto['mayor'] === 1;
        $factor = $esMayor ? max(1, (float) $producto['cantidad_unidades']) : 1;
        $stockProductoId = $esMayor ? (int) ($producto['id_producto_relacionado'] ?? 0) : (int) $detalle['producto_id'];
        if ($stockProductoId <= 0) {
            $stockProductoId = (int) $detalle['producto_id'];
        }
        $stockOrigen = asig_get_stock($conexion, $bssId, $stockProductoId, (int) $asignacion['sucursal_origen']);
        $stockBase = $stockOrigen ? (float) $stockOrigen['stock'] : null;
        $detalle['es_mayor'] = $esMayor;
        $detalle['stock_origen'] = $stockBase === null ? null : ($esMayor ? floor($stockBase / $factor) : $stockBase);
    }
    unset($detalle);

    // La información financiera solo se expone a la sucursal deudora
    // (o al administrador). La acreedora puede ver el documento y su detalle
    // de productos, pero no la cuenta ni los abonos.
    $esDeudora = $esAdmin || (int) $asignacion['sucursal_destino'] === $sucursalSesion;
    $cuenta = $esDeudora ? asig_get_cuenta_por_asignacion($conexion, $asignacionId, $bssId) : null;
    $pagos = [];
    if ($cuenta) {
        $stmt = $conexion->prepare(
            "SELECT p.*, u.nombre AS usuario_nombre
             FROM pagos_sucursales p
             LEFT JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.cuenta_sucursal_id = ?
             ORDER BY p.fecha ASC, p.id ASC"
        );
        $cuentaId = (int) $cuenta['id'];
        $stmt->bind_param('i', $cuentaId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $pagos[] = $row;
        }
        $stmt->close();
    }

    $stmt = $conexion->prepare(
        "SELECT * FROM asignaciones_sucursales_movimientos
         WHERE asignacion_id = ?
         ORDER BY id ASC"
    );
    $stmt->bind_param('i', $asignacionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $movimientos = [];
    while ($row = $result->fetch_assoc()) {
        $movimientos[] = $row;
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT b.*, u.nombre AS usuario_nombre
         FROM asignaciones_sucursales_bitacora b
         LEFT JOIN usuarios u ON u.id = b.usuario_id
         WHERE b.asignacion_id = ?
         ORDER BY b.id ASC"
    );
    $stmt->bind_param('i', $asignacionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bitacora = [];
    while ($row = $result->fetch_assoc()) {
        unset($row['datos_anteriores'], $row['datos_nuevos']);
        $bitacora[] = $row;
    }
    $stmt->close();

    return asig_result_ok('', [
        'asignacion' => $asignacion,
        'detalles' => $detalles,
        'cuenta' => $cuenta,
        'pagos' => $pagos,
        'movimientos' => $movimientos,
        'bitacora' => $bitacora,
        'es_creador' => (int) $asignacion['usuario_id'] === $usuarioId,
    ]);
}

function asig_listar_cuentas($conexion, int $bssId, int $usuarioId, bool $esAdmin, int $sucursalSesion, array $filtros = []): array
{
    $where = ["c.bss_id = ?"];
    $types = 'i';
    $params = [$bssId];

    if (!$esAdmin) {
        $where[] = "c.sucursal_deudora = ?";
        $types .= 'i';
        $params[] = $sucursalSesion;
    }

    $deudora = (int) ($filtros['sucursal_deudora'] ?? 0);
    if ($deudora > 0) {
        $where[] = "c.sucursal_deudora = ?";
        $types .= 'i';
        $params[] = $deudora;
    }

    $acreedora = (int) ($filtros['sucursal_acreedora'] ?? 0);
    if ($acreedora > 0) {
        $where[] = "c.sucursal_acreedora = ?";
        $types .= 'i';
        $params[] = $acreedora;
    }

    $sql = "SELECT * FROM (
                SELECT c.id, c.bss_id, c.asignacion_id, c.sucursal_acreedora, c.sucursal_deudora,
                       c.monto_original, c.created_at, a.numero_documento, a.fecha, a.estado AS estado_asignacion,
                       a.usuario_id, a.sucursal_origen, a.sucursal_destino,
                       sa.nombre AS acreedora_nombre, sd.nombre AS deudora_nombre,
                       COALESCE(p.total_abonado, 0) AS total_abonado,
                       GREATEST(c.monto_original - COALESCE(p.total_abonado, 0), 0) AS saldo,
                       GREATEST(COALESCE(p.total_abonado, 0) - c.monto_original, 0) AS excedente,
                       CASE
                         WHEN COALESCE(p.total_abonado, 0) <= 0 THEN 'PENDIENTE'
                         WHEN COALESCE(p.total_abonado, 0) < c.monto_original THEN 'PARCIAL'
                         ELSE 'PAGADA'
                       END AS estado_deuda
                FROM cuentas_sucursales c
                INNER JOIN asignaciones_sucursales a ON a.id = c.asignacion_id
                LEFT JOIN sucursales sa ON sa.id = c.sucursal_acreedora
                LEFT JOIN sucursales sd ON sd.id = c.sucursal_deudora
                LEFT JOIN (
                    SELECT cuenta_sucursal_id, SUM(monto_usd) AS total_abonado
                    FROM pagos_sucursales
                    GROUP BY cuenta_sucursal_id
                ) p ON p.cuenta_sucursal_id = c.id
                WHERE " . implode(' AND ', $where) . "
            ) x
            WHERE 1 = 1";

    $estado = strtoupper(trim((string) ($filtros['estado'] ?? '')));
    if (in_array($estado, ['PENDIENTE', 'PARCIAL', 'PAGADA'], true)) {
        $sql .= " AND x.estado_deuda = ?";
        $types .= 's';
        $params[] = $estado;
    }

    $sql .= " ORDER BY x.fecha DESC, x.id DESC";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        return [];
    }
    asig_bind_params($stmt, $types, $params);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $esCreador = (int) $row['usuario_id'] === $usuarioId;
        $items[] = [
            'id' => (int) $row['id'],
            'asignacion_id' => (int) $row['asignacion_id'],
            'numero_documento' => $row['numero_documento'],
            'fecha' => $row['fecha'],
            'sucursal_acreedora' => (int) $row['sucursal_acreedora'],
            'sucursal_deudora' => (int) $row['sucursal_deudora'],
            'acreedora_nombre' => $row['acreedora_nombre'],
            'deudora_nombre' => $row['deudora_nombre'],
            'usuario_id' => (int) $row['usuario_id'],
            'monto_original' => (float) $row['monto_original'],
            'total_abonado' => (float) $row['total_abonado'],
            'saldo' => (float) $row['saldo'],
            'excedente' => (float) $row['excedente'],
            'estado_deuda' => $row['estado_deuda'],
            'estado_asignacion' => $row['estado_asignacion'],
            'puede_abonar' => $esAdmin && $esCreador,
            'puede_eliminar' => $esAdmin && $esCreador,
        ];
    }
    $stmt->close();
    return $items;
}

function asig_resumen_cuentas(array $cuentas): array
{
    $resumen = [
        'total_original' => 0.0,
        'total_abonado' => 0.0,
        'saldo' => 0.0,
        'excedente' => 0.0,
        'obligaciones' => count($cuentas),
        'pendientes' => 0,
        'parciales' => 0,
        'pagadas' => 0,
        'pares' => [],
    ];

    foreach ($cuentas as $cuenta) {
        $resumen['total_original'] += (float) $cuenta['monto_original'];
        $resumen['total_abonado'] += (float) $cuenta['total_abonado'];
        $resumen['saldo'] += (float) $cuenta['saldo'];
        $resumen['excedente'] += (float) $cuenta['excedente'];

        if ($cuenta['estado_deuda'] === 'PENDIENTE') {
            $resumen['pendientes']++;
        } elseif ($cuenta['estado_deuda'] === 'PARCIAL') {
            $resumen['parciales']++;
        } else {
            $resumen['pagadas']++;
        }

        $llave = $cuenta['sucursal_acreedora'] . '-' . $cuenta['sucursal_deudora'];
        if (!isset($resumen['pares'][$llave])) {
            $resumen['pares'][$llave] = [
                'sucursal_acreedora' => $cuenta['sucursal_acreedora'],
                'sucursal_deudora' => $cuenta['sucursal_deudora'],
                'acreedora_nombre' => $cuenta['acreedora_nombre'],
                'deudora_nombre' => $cuenta['deudora_nombre'],
                'obligaciones' => 0,
                'total_original' => 0.0,
                'total_abonado' => 0.0,
                'saldo' => 0.0,
                'excedente' => 0.0,
            ];
        }
        $resumen['pares'][$llave]['obligaciones']++;
        $resumen['pares'][$llave]['total_original'] += (float) $cuenta['monto_original'];
        $resumen['pares'][$llave]['total_abonado'] += (float) $cuenta['total_abonado'];
        $resumen['pares'][$llave]['saldo'] += (float) $cuenta['saldo'];
        $resumen['pares'][$llave]['excedente'] += (float) $cuenta['excedente'];
    }

    $resumen['pares'] = array_values($resumen['pares']);
    $resumen['total_original'] = asig_dinero($resumen['total_original']);
    $resumen['total_abonado'] = asig_dinero($resumen['total_abonado']);
    $resumen['saldo'] = asig_dinero($resumen['saldo']);
    $resumen['excedente'] = asig_dinero($resumen['excedente']);
    foreach ($resumen['pares'] as &$par) {
        $par['total_original'] = asig_dinero($par['total_original']);
        $par['total_abonado'] = asig_dinero($par['total_abonado']);
        $par['saldo'] = asig_dinero($par['saldo']);
        $par['excedente'] = asig_dinero($par['excedente']);
    }
    unset($par);

    return $resumen;
}

function asig_detalle_cuenta($conexion, int $bssId, bool $esAdmin, int $sucursalSesion, int $cuentaId): array
{
    $sql = "SELECT c.*, a.numero_documento, a.fecha, a.estado AS estado_asignacion, a.usuario_id,
                   a.sucursal_origen, a.sucursal_destino, a.observaciones,
                   sa.nombre AS acreedora_nombre, sd.nombre AS deudora_nombre,
                   COALESCE(p.total_abonado, 0) AS total_abonado,
                   GREATEST(c.monto_original - COALESCE(p.total_abonado, 0), 0) AS saldo,
                   GREATEST(COALESCE(p.total_abonado, 0) - c.monto_original, 0) AS excedente,
                   CASE
                     WHEN COALESCE(p.total_abonado, 0) <= 0 THEN 'PENDIENTE'
                     WHEN COALESCE(p.total_abonado, 0) < c.monto_original THEN 'PARCIAL'
                     ELSE 'PAGADA'
                   END AS estado_deuda
            FROM cuentas_sucursales c
            INNER JOIN asignaciones_sucursales a ON a.id = c.asignacion_id
            LEFT JOIN sucursales sa ON sa.id = c.sucursal_acreedora
            LEFT JOIN sucursales sd ON sd.id = c.sucursal_deudora
            LEFT JOIN (
                SELECT cuenta_sucursal_id, SUM(monto_usd) AS total_abonado
                FROM pagos_sucursales
                GROUP BY cuenta_sucursal_id
            ) p ON p.cuenta_sucursal_id = c.id
            WHERE c.id = ? AND c.bss_id = ?
            LIMIT 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param('ii', $cuentaId, $bssId);
    $stmt->execute();
    $cuenta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cuenta) {
        return asig_result_error('La obligación económica no existe.');
    }
    if (!$esAdmin && (int) $cuenta['sucursal_deudora'] !== $sucursalSesion) {
        return asig_result_error('No tiene acceso a esta obligación económica.');
    }

    $stmt = $conexion->prepare(
        "SELECT d.* FROM asignaciones_sucursales_detalles d WHERE d.asignacion_id = ? ORDER BY d.producto_id ASC"
    );
    $cuentaAsignacionId = (int) $cuenta['asignacion_id'];
    $stmt->bind_param('i', $cuentaAsignacionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $detalles = [];
    while ($row = $result->fetch_assoc()) {
        $detalles[] = $row;
    }
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT p.*, u.nombre AS usuario_nombre
         FROM pagos_sucursales p
         LEFT JOIN usuarios u ON u.id = p.usuario_id
         WHERE p.cuenta_sucursal_id = ?
         ORDER BY p.fecha ASC, p.id ASC"
    );
    $cuentaId = (int) $cuenta['id'];
    $stmt->bind_param('i', $cuentaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pagos = [];
    while ($row = $result->fetch_assoc()) {
        $pagos[] = $row;
    }
    $stmt->close();

    return asig_result_ok('', [
        'cuenta' => $cuenta,
        'detalles' => $detalles,
        'pagos' => $pagos,
        'es_creador' => (int) $cuenta['usuario_id'] === (int) ($_SESSION['id'] ?? 0),
    ]);
}

function asig_registrar_pago($conexion, int $bssId, int $usuarioId, array $input): array
{
    $cuentaId = (int) ($input['cuenta_id'] ?? 0);
    $monto = asig_dinero($input['monto_usd'] ?? 0);
    $fecha = trim((string) ($input['fecha'] ?? ''));
    $tasaBcv = round((float) ($input['tasa_bcv'] ?? 0), 4);
    $tasaPeso = round((float) ($input['tasa_peso_dolar'] ?? 0), 4);
    $metodo = trim((string) ($input['metodo_pago'] ?? ''));
    $referencia = trim((string) ($input['referencia'] ?? ''));
    $observaciones = trim((string) ($input['observaciones'] ?? ''));

    if ($cuentaId <= 0) {
        return asig_result_error('La obligación económica no es válida.');
    }
    if ($monto <= 0) {
        return asig_result_error('El monto del abono debe ser mayor que cero.');
    }
    if (!asig_fecha_valida($fecha)) {
        return asig_result_error('La fecha del abono no es válida.');
    }
    if ($tasaBcv < 0 || $tasaPeso < 0) {
        return asig_result_error('Las tasas de cambio no pueden ser negativas.');
    }

    try {
        $conexion->begin_transaction();

        // Se bloquea primero la asignación y luego la cuenta, en el mismo
        // orden que la rectificación, para evitar deadlocks entre ambos flujos.
        $stmt = $conexion->prepare("SELECT id, asignacion_id FROM cuentas_sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
        $stmt->bind_param('ii', $cuentaId, $bssId);
        $stmt->execute();
        $cuentaBase = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cuentaBase) {
            throw new Exception('La obligación económica no existe.');
        }

        $asignacionPago = asig_get_asignacion($conexion, (int) $cuentaBase['asignacion_id'], $bssId, true);
        if (!$asignacionPago) {
            throw new Exception('La asignación asociada no existe.');
        }
        if ((int) $asignacionPago['usuario_id'] !== $usuarioId) {
            throw new Exception('Solo el usuario que creó la asignación puede registrar abonos sobre ella.');
        }

        $stmt = $conexion->prepare("SELECT * FROM cuentas_sucursales WHERE id = ? AND bss_id = ? LIMIT 1 FOR UPDATE");
        $stmt->bind_param('ii', $cuentaId, $bssId);
        $stmt->execute();
        $cuenta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$cuenta) {
            throw new Exception('La obligación económica no existe.');
        }

        $stmt = $conexion->prepare(
            "INSERT INTO pagos_sucursales
             (bss_id, cuenta_sucursal_id, monto_usd, fecha, tasa_bcv, tasa_peso_dolar, metodo_pago, referencia, observaciones, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('iidsddsssi', $bssId, $cuentaId, $monto, $fecha, $tasaBcv, $tasaPeso, $metodo, $referencia, $observaciones, $usuarioId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception('No se pudo registrar el abono.');
        }
        $pagoId = (int) $stmt->insert_id;
        $stmt->close();

        $stmt = $conexion->prepare("SELECT COALESCE(SUM(monto_usd), 0) AS total FROM pagos_sucursales WHERE cuenta_sucursal_id = ?");
        $stmt->bind_param('i', $cuentaId);
        $stmt->execute();
        $totalAbonado = asig_dinero($stmt->get_result()->fetch_assoc()['total'] ?? 0);
        $stmt->close();

        $conexion->commit();

        $saldo = max(0, asig_dinero((float) $cuenta['monto_original'] - $totalAbonado));
        $excedente = max(0, asig_dinero($totalAbonado - (float) $cuenta['monto_original']));
        $estado = $totalAbonado <= 0 ? 'PENDIENTE' : ($totalAbonado < (float) $cuenta['monto_original'] ? 'PARCIAL' : 'PAGADA');

        return asig_result_ok('Abono registrado correctamente.', [
            'pago_id' => $pagoId,
            'total_abonado' => $totalAbonado,
            'saldo' => $saldo,
            'excedente' => $excedente,
            'estado_deuda' => $estado,
        ]);
    } catch (Throwable $e) {
        $conexion->rollback();
        return asig_result_error($e->getMessage());
    }
}
