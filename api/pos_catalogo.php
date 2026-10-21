<?php
/**
 * API — Catálogo offline del POS nativo  →  GET /api/pos_catalogo.php
 *
 * Devuelve todo lo que la caja necesita para vender sin internet:
 * productos con stock, combos, clientes, tasas y configuración.
 * Los precios NO se envían calculados: la app aplica la misma fórmula
 * (CalculadoraPrecios) con las tasas recibidas.
 *
 * Si el cliente manda ?v=<version> y coincide con el catálogo actual,
 * responde 304 sin cuerpo (ahorra ancho de banda).
 *
 * Autenticación: X-Api-Key + Bearer.
 */

require_once __DIR__ . '/_pos_comun.php';

exigirMetodo('GET');
exigirApiKey();
limitar('catalogo', 30, 60);

$sesion = exigirSesion($conexion);
$bssId = $sesion['bss_id'];
$sucursalId = (int)($sesion['sucursal_id'] ?? 0);

if ($sucursalId <= 0) {
    salir(409, ['ok' => false, 'error' => 'La sesión no tiene sucursal']);
}

$puedeMayor = puedeVenderAlMayor($conexion, $sesion['id_user'], $sesion['nivel']);

// ── Combos: stock armable según los hijos ──────────────────────────────────────
$combos = [];
$stmtCombos = $conexion->prepare(
    "SELECT ci.combo_producto_id, ci.producto_id, ci.cantidad, p.nombre,
            COALESCE(s.stock, 0) AS stock_hijo
     FROM combo_items ci
     INNER JOIN productos p ON p.id = ci.producto_id
     LEFT JOIN stock s ON s.id_producto = p.id AND s.id_sucursal = ? AND s.bss_id = ?
     WHERE ci.bss_id = ?"
);
if ($stmtCombos) {
    $stmtCombos->bind_param('iii', $sucursalId, $bssId, $bssId);
    $stmtCombos->execute();
    $resCombos = $stmtCombos->get_result();
    while ($filaC = $resCombos->fetch_assoc()) {
        $comboId = (int)$filaC['combo_producto_id'];
        $cantidad = (float)$filaC['cantidad'];
        $stockHijo = (float)$filaC['stock_hijo'];
        $armables = $cantidad > 0 ? (int)floor($stockHijo / $cantidad) : 0;

        if (!isset($combos[$comboId])) {
            $combos[$comboId] = ['stock_disponible' => $armables, 'items' => []];
        } elseif ($armables < $combos[$comboId]['stock_disponible']) {
            $combos[$comboId]['stock_disponible'] = $armables;
        }

        $combos[$comboId]['items'][] = [
            'nombre' => (string)$filaC['nombre'],
            'cantidad' => $cantidad,
        ];
    }
    $stmtCombos->close();
}

// ── Imágenes: misma resolución que ventas.php (negocio, optimizada o stock) ──
$imgRoot = __DIR__ . '/../iseller_store';
$setOptimized = listarArchivos($imgRoot . '/assets/img/stock/optimized');
$setStock = listarArchivos($imgRoot . '/assets/img/stock');
$setTienda = listarArchivos($imgRoot . '/assets/img/tiendas/' . $bssId);

// ── Productos (mismo filtro que ventas.php) ────────────────────────────────────
$sql = "SELECT p.cantidad_unidades, p.origen, p.precio_compra, s.porcentaje, p.nombre, p.mayor,
               p.es_combo, p.id, s.stock, p.codigo_barras, np.foto_url
        FROM productos p
        INNER JOIN stock s ON p.id = s.id_producto
        LEFT JOIN negocio_productos np ON np.producto_id = p.id AND np.negocio_id = ?
        WHERE p.activo = 0 AND s.id_sucursal = ? AND s.bss_id = ?";
if (!$puedeMayor) {
    $sql .= " AND (p.mayor IS NULL OR p.mayor != '1')";
}

$productos = [];
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('iii', $bssId, $sucursalId, $bssId);
$stmt->execute();
$res = $stmt->get_result();
while ($fila = $res->fetch_assoc()) {
    $esCombo = (int)($fila['es_combo'] ?? 0);
    $id = (int)$fila['id'];
    $stock = ($esCombo === 1 && isset($combos[$id])) ? $combos[$id]['stock_disponible'] : (float)$fila['stock'];

    $fotoUrl = trim((string)($fila['foto_url'] ?? ''));
    $foto = '';
    if ($fotoUrl !== '' && isset($setTienda[basename($fotoUrl)])) {
        $foto = $fotoUrl;
    } elseif (isset($setOptimized[$id . '.webp'])) {
        $foto = 'assets/img/stock/optimized/' . $id . '.webp';
    } elseif (isset($setStock[$id . '.png'])) {
        $foto = 'assets/img/stock/' . $id . '.png';
    }

    $productos[] = [
        'id' => $id,
        'nombre' => (string)$fila['nombre'],
        'codigo_barras' => trim((string)$fila['codigo_barras']),
        'origen' => (string)$fila['origen'],
        'precio_compra' => (float)$fila['precio_compra'],
        'cantidad_unidades' => (float)$fila['cantidad_unidades'],
        'porcentaje' => (float)$fila['porcentaje'],
        'mayor' => (string)$fila['mayor'],
        'es_combo' => $esCombo,
        'foto' => $foto,
        'stock' => $stock,
        'combo_items' => ($esCombo === 1 && isset($combos[$id])) ? $combos[$id]['items'] : [],
    ];
}
$stmt->close();

// ── Clientes ───────────────────────────────────────────────────────────────────
$clientes = [];
$stmt = $conexion->prepare("SELECT cedula, nombre, telefono FROM clientes WHERE bss_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $clientes[] = [
            'cedula' => (string)$fila['cedula'],
            'nombre' => (string)$fila['nombre'],
            'telefono' => (string)$fila['telefono'],
        ];
    }
    $stmt->close();
}

// ── Configuración del negocio ──────────────────────────────────────────────────
$configuracion = ['tickets' => 0, 'bs_ticket' => 0, 'cortes_caja' => 0, 'registro_clientes' => 0];
$stmt = $conexion->prepare(
    "SELECT tickets, bs_ticket, cortes_caja, registro_clientes
     FROM configuracion WHERE bss_id = ? ORDER BY id DESC LIMIT 1"
);
if ($stmt) {
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($fila) {
        $configuracion = [
            'tickets' => (int)$fila['tickets'],
            'bs_ticket' => (int)$fila['bs_ticket'],
            'cortes_caja' => (int)$fila['cortes_caja'],
            'registro_clientes' => (int)$fila['registro_clientes'],
        ];
    }
}

$tasas = tasasDe($conexion, $bssId);

// ── Versión: hash de lo que cambia el contenido (sin campos volátiles) ─────────
$contenido = [
    'bss_id' => $bssId,
    'sucursal_id' => $sucursalId,
    'tasas' => $tasas,
    'configuracion' => $configuracion,
    'productos' => $productos,
    'clientes' => $clientes,
];
$version = hash('sha256', json_encode($contenido, JSON_UNESCAPED_UNICODE));

$vRecibida = trim((string)($_GET['v'] ?? ''));
if ($vRecibida !== '' && hash_equals($version, $vRecibida)) {
    http_response_code(304);
    exit;
}

salir(200, [
    'ok' => true,
    'version' => $version,
    'servidor_en' => servidorEn(),
    'tasas' => $tasas,
    'configuracion' => $configuracion,
    'productos' => $productos,
    'clientes' => $clientes,
]);

function listarArchivos(string $dir): array
{
    $set = [];
    if (is_dir($dir)) {
        foreach ((array)@scandir($dir) as $f) {
            if ($f !== '.' && $f !== '..') {
                $set[$f] = true;
            }
        }
    }
    return $set;
}
