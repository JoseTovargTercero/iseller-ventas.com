<?php
/**
 * Autochequeo de la cadena variación → respaldo → padre (solo CLI):
 *   php configurar/tests/variaciones_respaldo_selftest.php
 *
 * Trabaja dentro de una transacción y la revierte: no deja datos.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../configuracion.php';
require_once __DIR__ . '/../_canal.php';

function check(bool $condicion, string $nombre): void
{
    if (!$condicion) {
        fwrite(STDERR, "FALLÓ: $nombre\n");
        exit(1);
    }
    echo "ok - $nombre\n";
}

$fila = $conexion->query('SELECT bss_id, id_sucursal FROM stock WHERE stock IS NOT NULL LIMIT 1')->fetch_assoc();
if (!$fila) {
    fwrite(STDERR, "FALTAN DATOS: no hay filas de stock para probar\n");
    exit(1);
}
$bssId = (int) $fila['bss_id'];
$sucursalId = (int) $fila['id_sucursal'];

$crearProducto = function (string $nombre, int $padreId, int $respaldoId = 0) use ($conexion, $bssId): int {
    $stmt = $conexion->prepare(
        "INSERT INTO productos (nombre, bss_id, activo, mayor, id_producto_relacionado, id_producto_respaldo)
         VALUES (?, ?, 0, 0, ?, ?)"
    );
    $stmt->bind_param('siii', $nombre, $bssId, $padreId, $respaldoId);
    $stmt->execute();
    $id = (int) $conexion->insert_id;
    $stmt->close();
    return $id;
};

$crearStock = function (int $productoId, float $stock) use ($conexion, $bssId, $sucursalId): void {
    $stmt = $conexion->prepare(
        "INSERT INTO stock (id_producto, porcentaje, stock, id_sucursal, bss_id, mayor, visible_venta)
         VALUES (?, '0', ?, ?, ?, 0, 1)"
    );
    $stockTexto = (string) $stock;
    $stmt->bind_param('isii', $productoId, $stockTexto, $sucursalId, $bssId);
    $stmt->execute();
    $stmt->close();
};

$stockDe = function (int $productoId) use ($conexion, $bssId, $sucursalId): float {
    $filas = canal_consulta(
        $conexion,
        "SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1",
        'iii',
        [$productoId, $sucursalId, $bssId]
    );
    return (float) ($filas[0]['stock'] ?? 0);
};

$conexion->begin_transaction();

try {
    $padre = $crearProducto('TEST padre', 0);
    $respaldo = $crearProducto('TEST respaldo', $padre);
    $variacion = $crearProducto('TEST variacion', $padre, $respaldo);
    $otroPadre = $crearProducto('TEST otro padre', 0);
    $ajeno = $crearProducto('TEST ajeno', $otroPadre);

    $crearStock($padre, 10);
    $crearStock($variacion, 2);
    $crearStock($respaldo, 3);
    $crearStock($ajeno, 50);

    $faltante = canal_consumir_variacion($conexion, $variacion, $sucursalId, $bssId, 12);
    check($faltante === 0.0, 'cubre la demanda entre variación, respaldo y padre');
    check($stockDe($variacion) === 0.0, 'la variación queda en 0');
    check($stockDe($respaldo) === 0.0, 'el respaldo queda en 0');
    check($stockDe($padre) === 3.0, 'el padre descuenta solo el remanente');

    $pasar = function (int $id, float $valor) use ($conexion, $bssId, $sucursalId): void {
        $stmt = $conexion->prepare(
            "UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
        );
        $texto = (string) $valor;
        $stmt->bind_param('siii', $texto, $id, $sucursalId, $bssId);
        $stmt->execute();
        $stmt->close();
    };

    $pasar($padre, 10);
    $pasar($variacion, 2);
    $pasar($respaldo, 3);
    $faltante = canal_consumir_variacion($conexion, $variacion, $sucursalId, $bssId, 20);
    check($faltante === 5.0, 'devuelve el faltante cuando todo el stock se agota');
    check($stockDe($padre) === 0.0, 'nunca deja stock negativo');

    $pasar($padre, 7);
    $pasar($variacion, 0);
    $pasar($respaldo, 0);
    $faltante = canal_consumir_variacion($conexion, $variacion, $sucursalId, $bssId, 2);
    check($faltante === 0.0 && $stockDe($padre) === 5.0, 'con variación y respaldo en 0 descuenta del padre');

    $pasar($padre, 7);
    $pasar($variacion, 1);
    $stmt = $conexion->prepare('UPDATE productos SET id_producto_respaldo = ? WHERE id = ?');
    $stmt->bind_param('ii', $ajeno, $variacion);
    $stmt->execute();
    $stmt->close();
    $faltante = canal_consumir_variacion($conexion, $variacion, $sucursalId, $bssId, 4);
    check($faltante === 0.0 && $stockDe($ajeno) === 50.0, 'ignora un respaldo de otro padre');
    check($stockDe($padre) === 4.0, 'el padre cubre lo que la variación no tiene');

    check(canal_consumir_variacion($conexion, $padre, $sucursalId, $bssId, 1) === null, 'un producto base no usa la cadena');

    echo "Variaciones → respaldo → padre: autochequeo correcto\n";
} finally {
    $conexion->rollback();
}
