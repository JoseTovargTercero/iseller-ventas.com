<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');

require_once(__DIR__ . '/../../../configurar/configuracion.php');
require_once(__DIR__ . '/../../../configurar/session.php');

header('Content-Type: application/json; charset=utf-8');

function var_respuesta(array $data, int $codigo = 200): void
{
    http_response_code($codigo);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function var_consulta($conexion, string $sql, string $tipos = '', array $params = []): array
{
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('No se pudo preparar la consulta.');
    }
    if ($tipos !== '') {
        $stmt->bind_param($tipos, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('No se pudo ejecutar la consulta.');
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

function var_info_producto($conexion, int $productoId, int $bssId): ?array
{
    $filas = var_consulta(
        $conexion,
        "SELECT id, nombre, unidad, precio_compra, cantidad_unidades, porcentaje, codigo_barras,
                origen, proveedor, categoria, id_producto_relacionado, mayor, rendimiento,
                modo_control, seguimiento_estricto
         FROM productos
         WHERE id = ? AND bss_id = ? AND activo = 0
         LIMIT 1",
        'ii',
        [$productoId, $bssId]
    );
    return $filas[0] ?? null;
}

function var_sucursales_negocio($conexion, int $bssId): array
{
    return var_consulta(
        $conexion,
        "SELECT id, nombre, principal FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC",
        'i',
        [$bssId]
    );
}

function var_texto($valor, int $max = 255): string
{
    $texto = trim((string) $valor);
    if (function_exists('mb_substr')) {
        return mb_substr($texto, 0, $max, 'UTF-8');
    }
    return substr($texto, 0, $max);
}

function var_numero($valor): float
{
    if (is_string($valor)) {
        $valor = str_replace(',', '.', trim($valor));
    }
    $numero = (float) $valor;
    return is_finite($numero) ? $numero : 0.0;
}

function var_unidad(string $unidad): string
{
    $unidad = strtoupper(trim($unidad));
    if ($unidad === '') {
        return 'UND';
    }
    return substr($unidad, 0, 8);
}

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id']) || empty($_SESSION['id'])) {
    var_respuesta(['ok' => false, 'message' => 'Sesión no válida.'], 401);
}

$bssId = (int) $_SESSION['bss_id'];
$nivel = (int) ($_SESSION['nivel'] ?? 2);

$sucursalParam = (int) ($_POST['sucursal'] ?? $_GET['sucursal'] ?? 0);
$sucursalId = $nivel === 2 ? (int) ($_SESSION['sucursal'] ?? 0) : $sucursalParam;
$sucursales = var_sucursales_negocio($conexion, $bssId);

if ($sucursalId <= 0) {
    $sucursalId = (int) ($sucursales[0]['id'] ?? 0);
}

$sucursalesValidas = [];
foreach ($sucursales as $sucursal) {
    $sucursalesValidas[(int) $sucursal['id']] = true;
}
if ($sucursalId <= 0 || !isset($sucursalesValidas[$sucursalId])) {
    var_respuesta(['ok' => false, 'message' => 'Seleccione una sucursal válida.'], 400);
}

$accion = trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? ''));

try {
    switch ($accion) {
        case 'productos':
            $filas = var_consulta(
                $conexion,
                "SELECT p.id, p.nombre, p.unidad, p.precio_compra, p.cantidad_unidades, p.porcentaje,
                        p.codigo_barras, p.proveedor, p.origen, p.categoria, p.modo_control, p.rendimiento,
                        COALESCE(cp.nombre, 'Sin categoría') AS categoria_nombre,
                        COALESCE(s.stock, 0) AS stock,
                        (SELECT COUNT(*) FROM productos v
                          WHERE v.bss_id = p.bss_id AND v.id_producto_relacionado = p.id
                            AND v.activo = 0 AND (v.mayor IS NULL OR v.mayor = 0)) AS variaciones
                 FROM productos p
                 LEFT JOIN categorias_producto cp ON cp.id = p.categoria
                 LEFT JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
                 WHERE p.bss_id = ? AND p.activo = 0
                   AND (p.mayor IS NULL OR p.mayor = 0)
                   AND (p.id_producto_relacionado IS NULL OR p.id_producto_relacionado = 0)
                 ORDER BY p.nombre ASC",
                'iii',
                [$sucursalId, $bssId, $bssId]
            );
            var_respuesta(['ok' => true, 'data' => $filas]);
            break;

        case 'categorias':
            $filas = var_consulta(
                $conexion,
                "SELECT id, nombre FROM categorias_producto
                 WHERE bss_id = 0 OR bss_id = ?
                 ORDER BY nombre ASC",
                'i',
                [$bssId]
            );
            var_respuesta(['ok' => true, 'data' => $filas]);
            break;

        case 'variaciones':
            $padreId = (int) ($_POST['padre_id'] ?? $_GET['padre_id'] ?? 0);
            $padre = var_info_producto($conexion, $padreId, $bssId);
            if (!$padre || (int) ($padre['id_producto_relacionado'] ?? 0) > 0) {
                var_respuesta(['ok' => false, 'message' => 'Seleccione un producto base válido.'], 400);
            }
            $filas = var_consulta(
                $conexion,
                "SELECT v.id, v.nombre, v.unidad, v.precio_compra, v.cantidad_unidades, v.porcentaje,
                        v.codigo_barras, v.proveedor, v.origen, v.categoria, v.rendimiento,
                        v.modo_control, v.seguimiento_estricto,
                        COALESCE(cp.nombre, 'Sin categoría') AS categoria_nombre,
                        COALESCE(s.stock, 0) AS stock,
                        COALESCE(s.visible_venta, 1) AS visible_venta,
                        s.id AS stock_id
                 FROM productos v
                 LEFT JOIN categorias_producto cp ON cp.id = v.categoria
                 LEFT JOIN stock s ON s.id_producto = v.id AND s.id_sucursal = ? AND s.bss_id = ?
                 WHERE v.bss_id = ? AND v.id_producto_relacionado = ?
                   AND (v.mayor IS NULL OR v.mayor = 0) AND v.activo = 0
                 ORDER BY v.nombre ASC",
                'iiii',
                [$sucursalId, $bssId, $bssId, $padreId]
            );
            var_respuesta([
                'ok' => true,
                'padre' => $padre,
                'data' => $filas,
            ]);
            break;

        case 'crear':
            $padreId = (int) ($_POST['padre_id'] ?? 0);
            $nombre = var_texto($_POST['nombre'] ?? '', 255);
            $unidad = var_unidad((string) ($_POST['unidad'] ?? 'UND'));
            $precioCompra = var_numero($_POST['precio_compra'] ?? 0);
            $cantidadUnidades = var_numero($_POST['cantidad_unidades'] ?? 1);
            $porcentaje = var_numero($_POST['porcentaje'] ?? 0);
            $codigoBarras = var_texto($_POST['codigo_barras'] ?? '', 100);
            $origen = strtolower(var_texto($_POST['origen'] ?? 'v', 2));
            $proveedor = var_texto($_POST['proveedor'] ?? '', 150);
            $categoria = (int) ($_POST['categoria'] ?? 0);
            $rendimiento = var_numero($_POST['rendimiento'] ?? 0);
            $seguimiento = (int) ($_POST['seguimiento_estricto'] ?? 0) === 1 ? 1 : 0;
            $stocksJson = (string) ($_POST['stocks'] ?? '');

            $padre = var_info_producto($conexion, $padreId, $bssId);
            if (!$padre || (int) ($padre['id_producto_relacionado'] ?? 0) > 0 || (string) ($padre['mayor'] ?? '0') === '1') {
                var_respuesta(['ok' => false, 'message' => 'El producto base no es válido.'], 400);
            }
            if ($nombre === '') {
                var_respuesta(['ok' => false, 'message' => 'Indique el nombre de la variación.'], 400);
            }
            if ($precioCompra < 0) {
                var_respuesta(['ok' => false, 'message' => 'El precio de compra no es válido.'], 400);
            }
            if ($cantidadUnidades <= 0) {
                var_respuesta(['ok' => false, 'message' => 'Las unidades por bulto deben ser mayores que cero.'], 400);
            }
            if ($porcentaje < -100 || $porcentaje > 10000) {
                var_respuesta(['ok' => false, 'message' => 'El porcentaje de ganancia no es válido.'], 400);
            }
            if ($rendimiento < 0 || $rendimiento > 1.5) {
                var_respuesta(['ok' => false, 'message' => 'El rendimiento debe estar entre 0 y 1.5.'], 400);
            }
            if (!in_array($origen, ['v', 'c', 'm'], true)) {
                $origen = 'v';
            }
            if ($categoria > 0) {
                $catValida = var_consulta(
                    $conexion,
                    "SELECT id FROM categorias_producto WHERE id = ? AND (bss_id = 0 OR bss_id = ?) LIMIT 1",
                    'ii',
                    [$categoria, $bssId]
                );
                if (!$catValida) {
                    $categoria = 0;
                }
            }
            if ($codigoBarras !== '') {
                $existeCodigo = var_consulta(
                    $conexion,
                    "SELECT id FROM productos WHERE bss_id = ? AND codigo_barras = ? AND activo = 0 LIMIT 1",
                    'is',
                    [$bssId, $codigoBarras]
                );
                if ($existeCodigo) {
                    var_respuesta(['ok' => false, 'message' => 'Ya existe un producto con ese código de barras.'], 400);
                }
            }

            $stocksSolicitados = [];
            if ($stocksJson !== '') {
                $decodificado = json_decode($stocksJson, true);
                if (is_array($decodificado)) {
                    foreach ($decodificado as $item) {
                        $sid = (int) ($item['sucursal'] ?? 0);
                        if ($sid > 0 && isset($sucursalesValidas[$sid])) {
                            $stocksSolicitados[$sid] = [
                                'stock' => max(0, var_numero($item['stock'] ?? 0)),
                                'visible' => (int) ($item['visible'] ?? 1) === 1 ? 1 : 0,
                            ];
                        }
                    }
                }
            }

            $conexion->begin_transaction();
            try {
                // La nueva variación usa inventario propio. Si el producto base venía
                // del antiguo modo canal, se normaliza para que el stock se controle
                // por producto y no por lote.
                if ((string) ($padre['modo_control'] ?? 'NORMAL') === 'CANAL') {
                    $stmtPadre = $conexion->prepare("UPDATE productos SET modo_control = 'NORMAL', rendimiento = 0 WHERE id = ? AND bss_id = ?");
                    $stmtPadre->bind_param('ii', $padreId, $bssId);
                    $stmtPadre->execute();
                    $stmtPadre->close();
                }

                $cat = $categoria > 0 ? $categoria : null;
                $stmt = $conexion->prepare(
                    "INSERT INTO productos
                     (nombre, unidad, precio_compra, cantidad_unidades, porcentaje, codigo_barras,
                      origen, proveedor, categoria, bss_id, activo, mayor, id_producto_relacionado,
                      rendimiento, modo_control, seguimiento_estricto)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, ?, ?, 'NORMAL', ?)"
                );
                if (!$stmt) {
                    throw new Exception('No se pudo preparar el registro de la variación.');
                }
                $precioTexto = (string) $precioCompra;
                $cantidadTexto = (string) $cantidadUnidades;
                $stmt->bind_param(
                    'ssdsdsssiiidi',
                    $nombre,
                    $unidad,
                    $precioTexto,
                    $cantidadTexto,
                    $porcentaje,
                    $codigoBarras,
                    $origen,
                    $proveedor,
                    $cat,
                    $bssId,
                    $padreId,
                    $rendimiento,
                    $seguimiento
                );
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo crear la variación: ' . $stmt->error);
                }
                $variacionId = (int) $stmt->insert_id;
                $stmt->close();

                $stmtStock = $conexion->prepare(
                    "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, visible_venta)
                     VALUES (?, ?, ?, ?, ?, 0, ?)"
                );
                if (!$stmtStock) {
                    throw new Exception('No se pudo preparar el stock de la variación.');
                }
                foreach ($sucursales as $sucursal) {
                    $sid = (int) $sucursal['id'];
                    $stockInicial = $stocksSolicitados[$sid]['stock'] ?? 0;
                    $visible = $stocksSolicitados[$sid]['visible'] ?? 1;
                    $stockTexto = (string) $stockInicial;
                    $stmtStock->bind_param('idsiii', $variacionId, $porcentaje, $stockTexto, $sid, $bssId, $visible);
                    if (!$stmtStock->execute()) {
                        throw new Exception('No se pudo registrar el stock en la sucursal ' . $sucursal['nombre'] . '.');
                    }
                }
                $stmtStock->close();

                $reglaMin = $unidad === 'KG' ? '0.01' : '1.00';
                $reglaPaso = $reglaMin;
                $stmtRegla = $conexion->prepare(
                    "INSERT INTO producto_reglas (producto_id, bss_id, minimo_cantidad, paso_cantidad)
                     VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE minimo_cantidad = VALUES(minimo_cantidad), paso_cantidad = VALUES(paso_cantidad)"
                );
                if ($stmtRegla) {
                    $stmtRegla->bind_param('iiss', $variacionId, $bssId, $reglaMin, $reglaPaso);
                    $stmtRegla->execute();
                    $stmtRegla->close();
                }

                $conexion->commit();
                var_respuesta([
                    'ok' => true,
                    'message' => 'Variación creada correctamente.',
                    'id' => $variacionId,
                    'padre_normalizado' => (string) ($padre['modo_control'] ?? 'NORMAL') === 'CANAL',
                ]);
            } catch (Throwable $e) {
                $conexion->rollback();
                var_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'actualizar':
            $variacionId = (int) ($_POST['variacion_id'] ?? 0);
            $nombre = var_texto($_POST['nombre'] ?? '', 255);
            $unidad = var_unidad((string) ($_POST['unidad'] ?? 'UND'));
            $precioCompra = var_numero($_POST['precio_compra'] ?? 0);
            $cantidadUnidades = var_numero($_POST['cantidad_unidades'] ?? 1);
            $porcentaje = var_numero($_POST['porcentaje'] ?? 0);
            $codigoBarras = var_texto($_POST['codigo_barras'] ?? '', 100);
            $origen = strtolower(var_texto($_POST['origen'] ?? 'v', 2));
            $proveedor = var_texto($_POST['proveedor'] ?? '', 150);
            $categoria = (int) ($_POST['categoria'] ?? 0);
            $rendimiento = var_numero($_POST['rendimiento'] ?? 0);
            $seguimiento = (int) ($_POST['seguimiento_estricto'] ?? 0) === 1 ? 1 : 0;

            $variacion = var_info_producto($conexion, $variacionId, $bssId);
            if (!$variacion || (int) ($variacion['id_producto_relacionado'] ?? 0) <= 0) {
                var_respuesta(['ok' => false, 'message' => 'La variación no existe o ya fue desanclada.'], 400);
            }
            if ($nombre === '') {
                var_respuesta(['ok' => false, 'message' => 'Indique el nombre de la variación.'], 400);
            }
            if ($precioCompra < 0 || $cantidadUnidades <= 0) {
                var_respuesta(['ok' => false, 'message' => 'Precio o unidades por bulto no válidos.'], 400);
            }
            if ($porcentaje < -100 || $porcentaje > 10000) {
                var_respuesta(['ok' => false, 'message' => 'El porcentaje de ganancia no es válido.'], 400);
            }
            if ($rendimiento < 0 || $rendimiento > 1.5) {
                var_respuesta(['ok' => false, 'message' => 'El rendimiento debe estar entre 0 y 1.5.'], 400);
            }
            if (!in_array($origen, ['v', 'c', 'm'], true)) {
                $origen = 'v';
            }
            if ($categoria > 0) {
                $catValida = var_consulta(
                    $conexion,
                    "SELECT id FROM categorias_producto WHERE id = ? AND (bss_id = 0 OR bss_id = ?) LIMIT 1",
                    'ii',
                    [$categoria, $bssId]
                );
                if (!$catValida) {
                    $categoria = 0;
                }
            }
            if ($codigoBarras !== '') {
                $existeCodigo = var_consulta(
                    $conexion,
                    "SELECT id FROM productos WHERE bss_id = ? AND codigo_barras = ? AND id <> ? AND activo = 0 LIMIT 1",
                    'isi',
                    [$bssId, $codigoBarras, $variacionId]
                );
                if ($existeCodigo) {
                    var_respuesta(['ok' => false, 'message' => 'Ya existe otro producto con ese código de barras.'], 400);
                }
            }

            $conexion->begin_transaction();
            try {
                $cat = $categoria > 0 ? $categoria : null;
                $stmt = $conexion->prepare(
                    "UPDATE productos
                     SET nombre = ?, unidad = ?, precio_compra = ?, cantidad_unidades = ?, porcentaje = ?,
                         codigo_barras = ?, origen = ?, proveedor = ?, categoria = ?, rendimiento = ?,
                         seguimiento_estricto = ?
                     WHERE id = ? AND bss_id = ?"
                );
                $precioTexto = (string) $precioCompra;
                $cantidadTexto = (string) $cantidadUnidades;
                $stmt->bind_param(
                    'ssdsdsssidiii',
                    $nombre,
                    $unidad,
                    $precioTexto,
                    $cantidadTexto,
                    $porcentaje,
                    $codigoBarras,
                    $origen,
                    $proveedor,
                    $cat,
                    $rendimiento,
                    $seguimiento,
                    $variacionId,
                    $bssId
                );
                if (!$stmt->execute()) {
                    throw new Exception('No se pudo actualizar la variación: ' . $stmt->error);
                }
                $stmt->close();

                $stmtStock = $conexion->prepare("UPDATE stock SET porcentaje = ? WHERE id_producto = ? AND bss_id = ?");
                if ($stmtStock) {
                    $stmtStock->bind_param('dii', $porcentaje, $variacionId, $bssId);
                    $stmtStock->execute();
                    $stmtStock->close();
                }

                $conexion->commit();
                var_respuesta(['ok' => true, 'message' => 'Variación actualizada correctamente.']);
            } catch (Throwable $e) {
                $conexion->rollback();
                var_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        case 'estado':
            $variacionId = (int) ($_POST['variacion_id'] ?? 0);
            $visible = (int) ($_POST['visible'] ?? 1) === 1 ? 1 : 0;
            $variacion = var_info_producto($conexion, $variacionId, $bssId);
            if (!$variacion || (int) ($variacion['id_producto_relacionado'] ?? 0) <= 0) {
                var_respuesta(['ok' => false, 'message' => 'La variación no existe.'], 400);
            }
            $stmt = $conexion->prepare(
                "UPDATE stock SET visible_venta = ?
                 WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
            );
            $stmt->bind_param('iiii', $visible, $variacionId, $sucursalId, $bssId);
            $stmt->execute();
            $stmt->close();
            var_respuesta([
                'ok' => true,
                'message' => $visible === 1 ? 'Variación activada para la venta.' : 'Variación pausada.',
                'visible' => $visible,
            ]);
            break;

        case 'desanclar':
            $variacionId = (int) ($_POST['variacion_id'] ?? 0);
            $variacion = var_info_producto($conexion, $variacionId, $bssId);
            if (!$variacion || (int) ($variacion['id_producto_relacionado'] ?? 0) <= 0) {
                var_respuesta(['ok' => false, 'message' => 'La variación no existe o ya está desanclada.'], 400);
            }
            $stmt = $conexion->prepare(
                "UPDATE productos SET id_producto_relacionado = NULL, rendimiento = 0
                 WHERE id = ? AND bss_id = ?"
            );
            $stmt->bind_param('ii', $variacionId, $bssId);
            $stmt->execute();
            $stmt->close();
            var_respuesta(['ok' => true, 'message' => 'Variación desanclada. El producto sigue disponible de forma independiente.']);
            break;

        case 'eliminar':
            $variacionId = (int) ($_POST['variacion_id'] ?? 0);
            $variacion = var_info_producto($conexion, $variacionId, $bssId);
            if (!$variacion || (int) ($variacion['id_producto_relacionado'] ?? 0) <= 0) {
                var_respuesta(['ok' => false, 'message' => 'La variación no existe.'], 400);
            }
            $ventas = var_consulta(
                $conexion,
                "SELECT COUNT(*) AS total FROM orden_articulos WHERE product_id = ?",
                'i',
                [$variacionId]
            );
            $movimientos = var_consulta(
                $conexion,
                "SELECT COUNT(*) AS total FROM lotes_detalle WHERE producto_id = ?",
                'i',
                [$variacionId]
            );
            if ((int) ($ventas[0]['total'] ?? 0) > 0 || (int) ($movimientos[0]['total'] ?? 0) > 0) {
                var_respuesta([
                    'ok' => false,
                    'message' => 'La variación tiene ventas o movimientos registrados. Páusala o desancla el producto en lugar de eliminarlo.',
                ], 400);
            }
            $conexion->begin_transaction();
            try {
                $stmt = $conexion->prepare("DELETE FROM stock WHERE id_producto = ? AND bss_id = ?");
                $stmt->bind_param('ii', $variacionId, $bssId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexion->prepare("DELETE FROM producto_reglas WHERE producto_id = ? AND bss_id = ?");
                $stmt->bind_param('ii', $variacionId, $bssId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexion->prepare("DELETE FROM negocio_productos WHERE producto_id = ? AND negocio_id = ?");
                $stmt->bind_param('ii', $variacionId, $bssId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conexion->prepare("DELETE FROM productos WHERE id = ? AND bss_id = ?");
                $stmt->bind_param('ii', $variacionId, $bssId);
                $stmt->execute();
                $stmt->close();

                $conexion->commit();
                var_respuesta(['ok' => true, 'message' => 'Variación eliminada correctamente.']);
            } catch (Throwable $e) {
                $conexion->rollback();
                var_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
            }
            break;

        default:
            var_respuesta(['ok' => false, 'message' => 'Acción no reconocida.'], 400);
    }
} catch (Throwable $e) {
    var_respuesta(['ok' => false, 'message' => $e->getMessage()], 400);
}
