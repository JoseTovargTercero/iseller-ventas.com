<?php
/**
 * API — Catálogo de códigos de barras de la app Android "iSeller Códigos".
 *
 *   GET  /api/codigos.php          lista {id, nombre, codigo_barras} del bss_id
 *   POST /api/codigos.php          {producto_id, codigo_barras} asigna el código
 *
 * Autenticación: Bearer de codigos_login.php (PosAuth). Sin X-Api-Key: la llave
 * no se puede incrustar en un APK.
 *
 * Contrato: docs/APP_CODIGOS_BARRAS.md
 */

require_once __DIR__ . '/_pos_comun.php';

$sesion = exigirSesion($conexion);
$bssId = (int)$sesion['bss_id'];

$metodo = $_SERVER['REQUEST_METHOD'] ?? '';

if ($metodo === 'GET') {
    limitar('codigos_list', 60, 60);

    $productos = [];
    $stmt = $conexion->prepare(
        "SELECT id, nombre, codigo_barras
         FROM productos
         WHERE bss_id = ? AND activo = 0
         ORDER BY nombre"
    );
    if (!$stmt) {
        salir(500, ['ok' => false, 'error' => 'Error interno']);
    }
    $stmt->bind_param('i', $bssId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($fila = $res->fetch_assoc()) {
        $productos[] = [
            'id' => (int)$fila['id'],
            'nombre' => (string)$fila['nombre'],
            'codigo_barras' => trim((string)$fila['codigo_barras']),
        ];
    }
    $stmt->close();

    $negocio = '';
    $stmt = $conexion->prepare("SELECT nombre FROM negocio WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $bssId);
        $stmt->execute();
        $negocio = (string)($stmt->get_result()->fetch_assoc()['nombre'] ?? '');
        $stmt->close();
    }

    salir(200, [
        'ok' => true,
        'bss_id' => $bssId,
        'negocio' => $negocio,
        'productos' => $productos,
    ]);
}

if ($metodo !== 'POST') {
    salir(405, ['ok' => false, 'error' => 'Método no permitido']);
}

limitar('codigos_set', 120, 60);

$in = entrada();
$productoId = (int)($in['producto_id'] ?? 0);
$codigo = trim((string)($in['codigo_barras'] ?? ''));

if ($productoId <= 0) {
    salir(400, ['ok' => false, 'error' => 'Producto no válido']);
}
if ($codigo === '') {
    salir(400, ['ok' => false, 'error' => 'El código está vacío']);
}
if (strlen($codigo) > 255) {
    salir(400, ['ok' => false, 'error' => 'El código es demasiado largo']);
}

// El producto debe ser del negocio del token (evita IDOR).
$stmt = $conexion->prepare(
    "SELECT id, nombre, codigo_barras
     FROM productos WHERE id = ? AND bss_id = ? AND activo = 0 LIMIT 1"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('ii', $productoId, $bssId);
$stmt->execute();
$producto = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$producto) {
    salir(404, ['ok' => false, 'error' => 'Producto no encontrado']);
}

$anterior = trim((string)$producto['codigo_barras']);
if ($anterior === $codigo) {
    salir(200, [
        'ok' => true,
        'sin_cambios' => true,
        'producto' => [
            'id' => (int)$producto['id'],
            'nombre' => (string)$producto['nombre'],
            'codigo_barras' => $codigo,
        ],
    ]);
}

// Un código no puede apuntar a dos productos del mismo negocio.
$stmt = $conexion->prepare(
    "SELECT id, nombre FROM productos
     WHERE bss_id = ? AND codigo_barras = ? AND id <> ? LIMIT 1"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('isi', $bssId, $codigo, $productoId);
$stmt->execute();
$duplicado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($duplicado) {
    salir(409, [
        'ok' => false,
        'error' => 'Ese código ya está asignado a: ' . (string)$duplicado['nombre'],
    ]);
}

$stmt = $conexion->prepare(
    "UPDATE productos SET codigo_barras = ? WHERE id = ? AND bss_id = ?"
);
if (!$stmt) {
    salir(500, ['ok' => false, 'error' => 'Error interno']);
}
$stmt->bind_param('sii', $codigo, $productoId, $bssId);
$stmt->execute();
$stmt->close();

// shortcut: sin índice único (bss_id, codigo_barras); dos escaneos simultáneos
// del mismo código podrían duplicarlo. La app es de un solo operador.
salir(200, [
    'ok' => true,
    'producto' => [
        'id' => (int)$producto['id'],
        'nombre' => (string)$producto['nombre'],
        'codigo_barras' => $codigo,
    ],
]);
