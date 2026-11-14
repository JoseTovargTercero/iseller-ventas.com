<?php
/**
 * Selftest CLI de tasas personalizadas por producto.
 * Uso: php configurar/tasas_personalizadas_selftest.php
 *
 * Crea tasas y asignaciones dentro de una transacción y hace rollback al final.
 * No deja datos de prueba.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo CLI.');
}

require_once __DIR__ . '/configuracion.php';
require_once __DIR__ . '/_calculadrora_precios.php';

function fallar(string $mensaje): void
{
    fwrite(STDERR, "FALLO: {$mensaje}\n");
    exit(1);
}

function afirmar($condicion, string $mensaje): void
{
    if (!$condicion) {
        fallar($mensaje);
    }
}

$fila = $conexion->query(
    "SELECT c.bss_id, c.pesoDolar, c.peso_bolivar, c.DolarBolivar, c.bolivar_peso,
            p.id AS producto_id
     FROM cambio c
     INNER JOIN productos p ON p.bss_id = c.bss_id
     WHERE p.precio_compra > 0 AND p.cantidad_unidades > 0
     LIMIT 1"
)->fetch_assoc();

if (!$fila) {
    fwrite(STDERR, "No hay negocio/producto de prueba.\n");
    exit(1);
}

$bssId = (int) $fila['bss_id'];
$productoId = (int) $fila['producto_id'];
$bcvRes = $conexion->query("SELECT valor FROM cambios_bcv_historico ORDER BY time DESC LIMIT 1");
$bcv = $bcvRes && $bcvRes->num_rows ? (float) $bcvRes->fetch_assoc()['valor'] : (float) $fila['DolarBolivar'];

$conexion->begin_transaction();
try {
    $valores = [
        'usd_bs' => max(1, (float) $fila['DolarBolivar']) + 10,
        'usd_cop' => max(1, (float) $fila['pesoDolar']) + 100,
        'cop_bs' => max(0.000001, (float) $fila['peso_bolivar']) + 0.0001,
        'bs_cop' => max(0.000001, (float) $fila['bolivar_peso']) + 0.0001,
    ];

    $ids = [];
    $stmt = $conexion->prepare("INSERT INTO cambio_tasas_personalizadas (bss_id, par, nombre, valor) VALUES (?, ?, ?, ?)");
    foreach ($valores as $par => $valor) {
        $nombre = '__selftest_' . $par;
        $stmt->bind_param('issd', $bssId, $par, $nombre, $valor);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        $ids[$par] = (int) $stmt->insert_id;
    }
    $stmt->close();

    $stmt = $conexion->prepare("INSERT INTO producto_tasas_personalizadas (producto_id, par, tasa_id, bss_id) VALUES (?, ?, ?, ?)");
    foreach ($ids as $par => $tasaId) {
        $stmt->bind_param('isii', $productoId, $par, $tasaId, $bssId);
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }
    $stmt->close();

    $base = new CalculadoraPrecios(
        (float) $fila['pesoDolar'],
        (float) $fila['peso_bolivar'],
        (float) $fila['DolarBolivar'],
        (float) $fila['bolivar_peso'],
        $bcv,
        []
    );
    $calc = new CalculadoraPrecios(
        (float) $fila['pesoDolar'],
        (float) $fila['peso_bolivar'],
        (float) $fila['DolarBolivar'],
        (float) $fila['bolivar_peso'],
        $bcv,
        [],
        $conexion,
        $bssId
    );

    $producto = [
        'id' => $productoId,
        'producto_id' => $productoId,
        'cantidad_unidades' => 1,
        'origen' => 'v',
        'precio_compra' => 10,
        'porcentaje' => 10,
        'mayor' => '0',
    ];

    $t = $calc->tasasDeProducto($producto);
    afirmar(abs($t['dolarBolivar'] - $valores['usd_bs']) < 0.000001, 'usd_bs no reemplazó dolarBolivar');
    afirmar(abs($t['bcv'] - $valores['usd_bs']) < 0.000001, 'usd_bs no reemplazó bcv');
    afirmar(abs($t['pesoDolar'] - $valores['usd_cop']) < 0.000001, 'usd_cop no reemplazó pesoDolar');
    afirmar(abs($t['peso_bolivar'] - $valores['cop_bs']) < 0.000001, 'cop_bs no reemplazó peso_bolivar');
    afirmar(abs($t['bolivar_peso'] - $valores['bs_cop']) < 0.000001, 'bs_cop no reemplazó bolivar_peso');

    $precioBase = $base->calcularPrecios($producto);
    $precioCustom = $calc->calcularPrecios($producto);
    afirmar($precioBase['precio_venta_bs'] != $precioCustom['precio_venta_bs'], 'calcularPrecios no aplicó usd_bs');

    $convBase = $base->convertirMonto(1, 'usd', 'v');
    $convCustom = $calc->convertirMonto(1, 'usd', 'v', $producto);
    afirmar($convBase['bs'] != $convCustom['bs'], 'convertirMonto no aplicó usd_bs');

    $sinAsignacion = $calc->tasasDeProducto(['id' => -1, 'producto_id' => -1]);
    afirmar(abs($sinAsignacion['dolarBolivar'] - (float) $fila['DolarBolivar']) < 0.000001, 'producto sin asignación no usa la principal');

    $conexion->rollback();
    echo "OK: tasas personalizadas por producto.\n";
} catch (Throwable $e) {
    $conexion->rollback();
    fallar($e->getMessage());
}
