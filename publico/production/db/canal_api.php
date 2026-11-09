<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../configurar/configuracion.php');
require_once(__DIR__ . '/../../../configurar/session.php');
require_once(__DIR__ . '/../../../configurar/_canal.php');

header('Content-Type: application/json; charset=utf-8');

function canal_respuesta(array $data, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id']) || empty($_SESSION['id'])) {
    canal_respuesta(['ok' => false, 'message' => 'Sesión no válida.'], 401);
}

$bssId = (int) $_SESSION['bss_id'];
$nivel = (int) ($_SESSION['nivel'] ?? 2);
$usuarioId = (int) $_SESSION['id'];

$sucursalParam = (int) ($_POST['sucursal'] ?? $_GET['sucursal'] ?? 0);
$sucursalId = $nivel === 2 ? (int) ($_SESSION['sucursal'] ?? 0) : $sucursalParam;
if ($sucursalId <= 0) {
    $fila = canal_consulta($conexion, "SELECT id FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, id ASC LIMIT 1", 'i', [$bssId]);
    $sucursalId = (int) ($fila[0]['id'] ?? 0);
}

if ($sucursalId <= 0) {
    canal_respuesta(['ok' => false, 'message' => 'No hay sucursal disponible.'], 400);
}
$validarSucursal = canal_consulta($conexion, "SELECT id FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1", 'ii', [$sucursalId, $bssId]);
if (!$validarSucursal) {
    canal_respuesta(['ok' => false, 'message' => 'Sucursal no válida.'], 403);
}

$accion = trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? ''));

try {
    switch ($accion) {
        case 'productos':
            $filas = canal_consulta(
                $conexion,
                "SELECT p.id, p.nombre, p.unidad, p.precio_compra, p.cantidad_unidades,
                        p.porcentaje, p.categoria, p.id_producto_relacionado, p.modo_control,
                        p.rendimiento, p.seguimiento_estricto,
                        COALESCE(cp.nombre, 'Sin categoría') AS categoria_nombre,
                        COALESCE(s.stock, 0) AS stock,
                        l.kg_restante AS kg_lote,
                        (SELECT COUNT(*) FROM productos c
                          WHERE c.bss_id = p.bss_id AND c.id_producto_relacionado = p.id
                            AND (c.mayor IS NULL OR c.mayor = 0)) AS cortes
                 FROM productos p
                 LEFT JOIN categorias_producto cp ON cp.id = p.categoria
                 LEFT JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
                 LEFT JOIN lotes l ON l.padre_id = p.id AND l.id_sucursal = ? AND l.bss_id = ?
                                  AND l.estado = 'ABIERTO'
                 WHERE p.bss_id = ? AND p.activo = 0 AND (p.mayor IS NULL OR p.mayor = 0)
                 ORDER BY p.nombre ASC",
                'iiiii',
                [$sucursalId, $bssId, $sucursalId, $bssId, $bssId]
            );
            canal_respuesta(['ok' => true, 'data' => $filas]);
            break;

        case 'categorias':
            $filas = canal_consulta(
                $conexion,
                "SELECT id, nombre FROM categorias_producto
                 WHERE bss_id = 0 OR bss_id = ?
                 ORDER BY nombre ASC",
                'i',
                [$bssId]
            );
            canal_respuesta(['ok' => true, 'data' => $filas]);
            break;

        case 'marcar_padre':
            $productoId = (int) ($_POST['producto_id'] ?? 0);
            $producto = canal_info_producto($conexion, $productoId, $bssId);
            if (!$producto || (string) ($producto['mayor'] ?? '0') === '1') {
                canal_respuesta(['ok' => false, 'message' => 'Producto no válido.'], 400);
            }
            $stmt = $conexion->prepare("UPDATE productos SET modo_control = 'CANAL', unidad = 'KG', cantidad_unidades = 1, id_producto_relacionado = NULL, rendimiento = 0 WHERE id = ? AND bss_id = ?");
            $stmt->bind_param('ii', $productoId, $bssId);
            $stmt->execute();
            $stmt->close();
            canal_respuesta(['ok' => true, 'message' => 'Producto configurado como res en canal.']);
            break;

        case 'cortes':
            $padreId = (int) ($_POST['padre_id'] ?? $_GET['padre_id'] ?? 0);
            if ($padreId <= 0) {
                canal_respuesta(['ok' => false, 'message' => 'Seleccione una res.'], 400);
            }
            $filas = canal_consulta(
                $conexion,
                "SELECT p.id, p.nombre, p.rendimiento, p.unidad, p.precio_compra, p.porcentaje,
                        p.mayor, s.stock, s.visible_venta
                 FROM productos p
                 LEFT JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
                 WHERE p.bss_id = ? AND p.activo = 0
                   AND p.id_producto_relacionado = ? AND (p.mayor IS NULL OR p.mayor = 0)
                 ORDER BY p.nombre ASC",
                'iiii',
                [$sucursalId, $bssId, $bssId, $padreId]
            );
            canal_respuesta(['ok' => true, 'data' => $filas]);
            break;

        case 'crear_padre':
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $precio = (float) ($_POST['precio'] ?? 0);
            $porcentaje = (float) ($_POST['porcentaje'] ?? 0);
            $categoria = (int) ($_POST['categoria'] ?? 0);
            $codigoBarras = trim((string) ($_POST['codigo_barras'] ?? ''));
            $origen = trim((string) ($_POST['origen'] ?? 'v'));
            $proveedor = trim((string) ($_POST['proveedor'] ?? ''));

            if ($nombre === '' || strlen($nombre) > 255) {
                canal_respuesta(['ok' => false, 'message' => 'Indique el nombre de la res.'], 400);
            }
            if ($precio < 0) {
                canal_respuesta(['ok' => false, 'message' => 'El costo por kg no es válido.'], 400);
            }

            $conexion->begin_transaction();
            try {
                $cat = $categoria > 0 ? $categoria : null;
                $precioTexto = (string) $precio;
                $stmt = $conexion->prepare(
                    "INSERT INTO productos
                     (nombre, unidad, precio_compra, cantidad_unidades, porcentaje, codigo_barras,
                      origen, proveedor, categoria, bss_id, activo, mayor, modo_control)
                     VALUES (?, 'KG', ?, 1, ?, ?, ?, ?, ?, ?, 0, 0, 'CANAL')"
                );
                $stmt->bind_param('ssdsssii', $nombre, $precioTexto, $porcentaje, $codigoBarras, $origen, $proveedor, $cat, $bssId);
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo crear la res: ' . $stmt->error);
                }
                $padreId = (int) $stmt->insert_id;
                $stmt->close();

                $stmtStock = $conexion->prepare(
                    "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, visible_venta)
                     SELECT ?, ?, '0', id, ?, 0, 1 FROM sucursales WHERE bss_id = ?"
                );
                $stmtStock->bind_param('idii', $padreId, $porcentaje, $bssId, $bssId);
                $stmtStock->execute();
                $stmtStock->close();

                $stmtRegla = $conexion->prepare(
                    "INSERT INTO producto_reglas (producto_id, bss_id, minimo_cantidad, paso_cantidad)
                     VALUES (?, ?, 0.01, 0.01)
                     ON DUPLICATE KEY UPDATE minimo_cantidad = 0.01, paso_cantidad = 0.01"
                );
                if ($stmtRegla) {
                    $stmtRegla->bind_param('ii', $padreId, $bssId);
                    $stmtRegla->execute();
                    $stmtRegla->close();
                }

                $conexion->commit();
                canal_respuesta(['ok' => true, 'message' => 'Res creada correctamente.', 'id' => $padreId]);
            } catch (Throwable $e) {
                $conexion->rollback();
                canal_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'crear_corte':
            $padreId = (int) ($_POST['padre_id'] ?? 0);
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $precio = (float) ($_POST['precio'] ?? 0);
            $porcentaje = (float) ($_POST['porcentaje'] ?? 0);
            $rendimiento = (float) ($_POST['rendimiento'] ?? 0);
            $categoria = (int) ($_POST['categoria'] ?? 0);
            $origen = trim((string) ($_POST['origen'] ?? 'v'));
            $proveedor = trim((string) ($_POST['proveedor'] ?? ''));
            $codigoBarras = trim((string) ($_POST['codigo_barras'] ?? ''));
            $crearMayor = (int) ($_POST['crear_mayor'] ?? 0) === 1;
            $mayorPrecio = (float) ($_POST['mayor_precio'] ?? 0);
            $mayorPorcentaje = (float) ($_POST['mayor_porcentaje'] ?? 0);
            $mayorEmpaque = (float) ($_POST['mayor_empaque'] ?? 1);

            $padre = canal_info_producto($conexion, $padreId, $bssId);
            if (!$padre || (string) ($padre['modo_control'] ?? 'NORMAL') !== 'CANAL') {
                canal_respuesta(['ok' => false, 'message' => 'La res seleccionada no es válida.'], 400);
            }
            if ($nombre === '' || strlen($nombre) > 255) {
                canal_respuesta(['ok' => false, 'message' => 'Indique el nombre del corte.'], 400);
            }
            if ($precio < 0 || $rendimiento < 0 || $rendimiento > 1.5) {
                canal_respuesta(['ok' => false, 'message' => 'Precio o rendimiento no válidos.'], 400);
            }
            if ($crearMayor && ($mayorPrecio <= 0 || $mayorEmpaque <= 0)) {
                canal_respuesta(['ok' => false, 'message' => 'Complete el precio y empaque al mayor.'], 400);
            }

            $conexion->begin_transaction();
            try {
                $cat = $categoria > 0 ? $categoria : null;
                $stmt = $conexion->prepare(
                    "INSERT INTO productos
                     (nombre, unidad, precio_compra, cantidad_unidades, porcentaje, codigo_barras,
                      origen, proveedor, categoria, bss_id, activo, mayor, id_producto_relacionado,
                      rendimiento, modo_control)
                     VALUES (?, 'KG', ?, 1, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 'NORMAL')"
                );
                $precioTexto = (string) $precio;
                $stmt->bind_param('ssdsssiiid', $nombre, $precioTexto, $porcentaje, $codigoBarras, $origen, $proveedor, $cat, $bssId, $padreId, $rendimiento);
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo crear el corte: ' . $stmt->error);
                }
                $corteId = (int) $stmt->insert_id;
                $stmt->close();

                $stmtStock = $conexion->prepare(
                    "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, visible_venta)
                     SELECT ?, ?, '0', id, ?, 0, 1 FROM sucursales WHERE bss_id = ?"
                );
                $stmtStock->bind_param('idii', $corteId, $porcentaje, $bssId, $bssId);
                $stmtStock->execute();
                $stmtStock->close();

                $stmtRegla = $conexion->prepare(
                    "INSERT INTO producto_reglas (producto_id, bss_id, minimo_cantidad, paso_cantidad)
                     VALUES (?, ?, 0.01, 0.01)
                     ON DUPLICATE KEY UPDATE minimo_cantidad = 0.01, paso_cantidad = 0.01"
                );
                if ($stmtRegla) {
                    $stmtRegla->bind_param('ii', $corteId, $bssId);
                    $stmtRegla->execute();
                    $stmtRegla->close();
                }

                if ($crearMayor) {
                    $nombreMayor = 'AL MAYOR - ' . $nombre;
                    $stmt = $conexion->prepare(
                        "INSERT INTO productos
                         (nombre, unidad, precio_compra, cantidad_unidades, porcentaje, codigo_barras,
                          origen, proveedor, categoria, bss_id, activo, mayor, id_producto_relacionado,
                          rendimiento, modo_control)
                         VALUES (?, 'UND', ?, ?, ?, '', ?, ?, ?, ?, 0, 1, ?, 0, 'NORMAL')"
                    );
                    $mayorPrecioTexto = (string) $mayorPrecio;
                    $stmt->bind_param('ssddssiii', $nombreMayor, $mayorPrecioTexto, $mayorEmpaque, $mayorPorcentaje, $origen, $proveedor, $cat, $bssId, $corteId);
                    if (!$stmt->execute()) {
                        throw new Exception('No se pudo crear la variante al mayor: ' . $stmt->error);
                    }
                    $mayorId = (int) $stmt->insert_id;
                    $stmt->close();

                    $stmtStockMayor = $conexion->prepare(
                        "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, id_stock, visible_venta)
                         SELECT ?, ?, '0', sc.id_sucursal, ?, 1, sc.id, 1
                         FROM stock sc
                         INNER JOIN sucursales su ON su.id = sc.id_sucursal AND su.bss_id = ?
                         WHERE sc.id_producto = ? AND sc.bss_id = ?"
                    );
                    $stmtStockMayor->bind_param('idiiii', $mayorId, $mayorPorcentaje, $bssId, $bssId, $corteId, $bssId);
                    $stmtStockMayor->execute();
                    $stmtStockMayor->close();
                }

                $conexion->commit();
                canal_respuesta(['ok' => true, 'message' => 'Corte creado correctamente.', 'id' => $corteId]);
            } catch (Throwable $e) {
                $conexion->rollback();
                canal_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'vincular_corte':
            $padreId = (int) ($_POST['padre_id'] ?? 0);
            $productoId = (int) ($_POST['producto_id'] ?? 0);
            $rendimiento = (float) ($_POST['rendimiento'] ?? 0);
            $padre = canal_info_producto($conexion, $padreId, $bssId);
            $producto = canal_info_producto($conexion, $productoId, $bssId);
            if (!$padre || !$producto || $productoId === $padreId || (string) ($producto['mayor'] ?? '0') === '1'
                || (string) ($padre['modo_control'] ?? 'NORMAL') !== 'CANAL') {
                canal_respuesta(['ok' => false, 'message' => 'Producto no válido.'], 400);
            }
            $hijos = canal_consulta($conexion, "SELECT id FROM productos WHERE bss_id = ? AND id_producto_relacionado = ? AND (mayor IS NULL OR mayor = 0) LIMIT 1", 'ii', [$bssId, $productoId]);
            if ($hijos) {
                canal_respuesta(['ok' => false, 'message' => 'Ese producto ya es una res con cortes; no puede ser subproducto.'], 400);
            }
            $stmt = $conexion->prepare("UPDATE productos SET id_producto_relacionado = ?, rendimiento = ?, unidad = 'KG', modo_control = 'NORMAL' WHERE id = ? AND bss_id = ?");
            $stmt->bind_param('idii', $padreId, $rendimiento, $productoId, $bssId);
            $stmt->execute();
            $stmt->close();
            $stmt = $conexion->prepare(
                "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, visible_venta)
                 SELECT ?, COALESCE(p.porcentaje, 0), '0', su.id, ?, 0, 1
                 FROM sucursales su
                 INNER JOIN productos p ON p.id = ?
                 LEFT JOIN stock st ON st.id_producto = p.id AND st.id_sucursal = su.id AND st.bss_id = su.bss_id
                 WHERE su.bss_id = ? AND st.id IS NULL"
            );
            $stmt->bind_param('iiii', $productoId, $bssId, $productoId, $bssId);
            $stmt->execute();
            $stmt->close();

            $stmtRegla = $conexion->prepare(
                "INSERT INTO producto_reglas (producto_id, bss_id, minimo_cantidad, paso_cantidad)
                 VALUES (?, ?, 0.01, 0.01)
                 ON DUPLICATE KEY UPDATE minimo_cantidad = 0.01, paso_cantidad = 0.01"
            );
            if ($stmtRegla) {
                $stmtRegla->bind_param('ii', $productoId, $bssId);
                $stmtRegla->execute();
                $stmtRegla->close();
            }
            canal_respuesta(['ok' => true, 'message' => 'Producto vinculado como corte.']);
            break;

        case 'desvincular_corte':
            $productoId = (int) ($_POST['producto_id'] ?? 0);
            $stmt = $conexion->prepare("UPDATE productos SET id_producto_relacionado = NULL, rendimiento = 0 WHERE id = ? AND bss_id = ?");
            $stmt->bind_param('ii', $productoId, $bssId);
            $stmt->execute();
            $stmt->close();

            $stmt = $conexion->prepare("DELETE FROM producto_reglas WHERE producto_id = ? AND bss_id = ?");
            $stmt->bind_param('ii', $productoId, $bssId);
            $stmt->execute();
            $stmt->close();
            canal_respuesta(['ok' => true, 'message' => 'Corte desvinculado.']);
            break;

        case 'abrir':
            $padreId = (int) ($_POST['padre_id'] ?? 0);
            $kg = (float) ($_POST['kg'] ?? 0);
            $costo = (float) ($_POST['costo_total'] ?? 0);
            $proveedor = trim((string) ($_POST['proveedor'] ?? ''));
            $notas = trim((string) ($_POST['notas'] ?? ''));
            $sumarStock = (int) ($_POST['sumar_stock'] ?? 1) === 1;
            $reactivar = (int) ($_POST['reactivar'] ?? 1) === 1;

            $conexion->begin_transaction();
            try {
                $resultado = canal_abrir_lote($conexion, $padreId, $sucursalId, $bssId, $kg, $costo, $proveedor, $notas, $usuarioId, $sumarStock, $reactivar);
                $conexion->commit();
                canal_respuesta(['ok' => true, 'message' => 'Res registrada. Lote #' . $resultado['id'], 'lote_id' => $resultado['id']]);
            } catch (Throwable $e) {
                $conexion->rollback();
                canal_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'cerrar':
            $loteId = (int) ($_POST['lote_id'] ?? 0);
            $confirmar = (int) ($_POST['confirmar'] ?? 0) === 1;
            $conexion->begin_transaction();
            try {
                $resultado = canal_cerrar_lote($conexion, $loteId, $bssId, $confirmar);
                $conexion->commit();
                if ($resultado['diferencia'] <= 0) {
                    $mensaje = 'Res cerrada sin diferencia.';
                } elseif ((int) $resultado['conciliado'] === 1) {
                    $mensaje = 'Res cerrada. Quedan ' . canal_numero($resultado['diferencia']) . ' kg confirmados en inventario.';
                } else {
                    $mensaje = 'Res cerrada. Diferencia pendiente: ' . canal_numero($resultado['diferencia']) . ' kg.';
                }
                canal_respuesta(['ok' => true, 'message' => $mensaje, 'data' => $resultado]);
            } catch (Throwable $e) {
                $conexion->rollback();
                canal_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'conciliar':
            $loteId = (int) ($_POST['lote_id'] ?? 0);
            $aceptar = (int) ($_POST['aceptar'] ?? 0) === 1;
            $conexion->begin_transaction();
            try {
                $resultado = canal_marcar_conciliado($conexion, $loteId, $bssId, $aceptar);
                $conexion->commit();
                canal_respuesta(['ok' => true, 'message' => 'Lote conciliado.', 'data' => $resultado]);
            } catch (Throwable $e) {
                $conexion->rollback();
                canal_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'lotes':
            $padreId = (int) ($_POST['padre_id'] ?? $_GET['padre_id'] ?? 0);
            $sql = "SELECT l.*, p.nombre AS padre_nombre, su.nombre AS sucursal_nombre
                    FROM lotes l
                    INNER JOIN productos p ON p.id = l.padre_id
                    LEFT JOIN sucursales su ON su.id = l.id_sucursal
                    WHERE l.bss_id = ? AND l.id_sucursal = ?";
            $tipos = 'ii';
            $params = [$bssId, $sucursalId];
            if ($padreId > 0) {
                $sql .= ' AND l.padre_id = ?';
                $tipos .= 'i';
                $params[] = $padreId;
            }
            $sql .= ' ORDER BY l.fecha_apertura DESC, l.id DESC LIMIT 100';
            $filas = canal_consulta($conexion, $sql, $tipos, $params);
            canal_respuesta(['ok' => true, 'data' => $filas]);
            break;

        case 'reporte':
            $loteId = (int) ($_POST['lote_id'] ?? $_GET['lote_id'] ?? 0);
            $filas = canal_consulta(
                $conexion,
                "SELECT l.*, p.nombre AS padre_nombre
                 FROM lotes l
                 INNER JOIN productos p ON p.id = l.padre_id
                 WHERE l.id = ? AND l.bss_id = ?
                 LIMIT 1",
                'ii',
                [$loteId, $bssId]
            );
            $lote = $filas[0] ?? null;
            if (!$lote) {
                canal_respuesta(['ok' => false, 'message' => 'Lote no encontrado.'], 404);
            }
            $cortes = canal_consulta(
                $conexion,
                "SELECT p.id, p.nombre, p.rendimiento,
                        COALESCE(SUM(CASE WHEN d.origen = 'VENTA' THEN d.cantidad ELSE 0 END), 0) AS vendido
                 FROM productos p
                 LEFT JOIN lotes_detalle d ON d.producto_id = p.id AND d.lote_id = ?
                 WHERE p.bss_id = ? AND p.id_producto_relacionado = ? AND (p.mayor IS NULL OR p.mayor = 0)
                 GROUP BY p.id, p.nombre, p.rendimiento
                 ORDER BY p.nombre ASC",
                'iii',
                [$loteId, $bssId, (int) $lote['padre_id']]
            );
            foreach ($cortes as &$corte) {
                $corte['vendido'] = (float) $corte['vendido'];
                $corte['esperado'] = round((float) $corte['rendimiento'] * (float) $lote['kg_inicial'], 4);
                $corte['diferencia'] = round($corte['vendido'] - $corte['esperado'], 4);
            }
            unset($corte);

            $directo = canal_consulta(
                $conexion,
                "SELECT COALESCE(SUM(cantidad), 0) AS vendido
                 FROM lotes_detalle
                 WHERE lote_id = ? AND producto_id = ? AND origen = 'VENTA'",
                'ii',
                [$loteId, (int) $lote['padre_id']]
            );
            $ventaDirecta = (float) ($directo[0]['vendido'] ?? 0);
            if ($ventaDirecta > 0) {
                $cortes[] = [
                    'id' => 0,
                    'nombre' => 'Venta directa de res',
                    'rendimiento' => 0,
                    'vendido' => $ventaDirecta,
                    'esperado' => 0,
                    'diferencia' => $ventaDirecta,
                ];
            }

            canal_respuesta(['ok' => true, 'lote' => $lote, 'cortes' => $cortes]);
            break;

        default:
            canal_respuesta(['ok' => false, 'message' => 'Acción no reconocida.'], 400);
    }
} catch (Throwable $e) {
    canal_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
}
