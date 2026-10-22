<?php
/**
 * TEMPORAL — Otorga 7 días de "prueba" a los negocios que no tienen pago vigente.
 * Abrir en el navegador: primero muestra la simulación.
 * Para aplicar los cambios: agregar ?aplicar=1 a la URL.
 * Borrar este archivo después de usarlo.
 */
$rutaConfig = is_file(__DIR__ . '/configurar/configuracion.php')
    ? __DIR__ . '/configurar/configuracion.php'
    : __DIR__ . '/../configurar/configuracion.php';
require_once $rutaConfig;

header('Content-Type: text/plain; charset=utf-8');

$dias = 7;
$inicio = date('Y-m-d H:i:s');
$fin = date('Y-m-d 23:59:59', strtotime("+{$dias} days"));
$proxima = date('Y-m-d', strtotime("+{$dias} days"));

// Quedan fuera los que ya tienen una membresía de pago vigente.
// Para aplicar a TODOS sin excepción, cambiar $where por "1".
$where = "NOT (membresia_tipo IN ('mensual','anual') AND membresia_estado = 'activa' AND fecha_fin_membresia > '$inicio')";

$lista = $conexion->query("SELECT id, nombre, plan, membresia_tipo, membresia_estado, fecha_fin_membresia FROM negocio WHERE $where ORDER BY id");
if (!$lista) {
    exit("ERROR al consultar negocios: " . $conexion->error . "\n");
}

echo "Fecha de ejecución: " . date('Y-m-d H:i:s') . "\n";
echo "Nueva fecha de vencimiento: $fin\n\n";
echo "Negocios que recibirán los $dias días (" . $lista->num_rows . "):\n";
while ($n = $lista->fetch_assoc()) {
    echo "  #{$n['id']} {$n['nombre']} | plan: " . ($n['plan'] === '' || $n['plan'] === null ? '(vacío)' : $n['plan'])
        . " | {$n['membresia_tipo']}/{$n['membresia_estado']} | vencía: " . ($n['fecha_fin_membresia'] ?: '(sin fecha)') . "\n";
}

$omitidos = $conexion->query("SELECT COUNT(*) c FROM negocio WHERE NOT ($where)")->fetch_assoc()['c'];
echo "\nNegocios omitidos por tener pago vigente: $omitidos\n";

if (!isset($_GET['aplicar'])) {
    exit("\nSIMULACIÓN. No se modificó nada. Para aplicar: " . basename(__FILE__) . "?aplicar=1\n");
}

$ok = $conexion->query(
    "UPDATE negocio
        SET membresia_tipo = 'prueba',
            plan = IF(plan IN ('mensual','anual'), plan, 'mensual'),
            membresia_estado = 'activa',
            fecha_inicio_membresia = '$inicio',
            fecha_fin_membresia = '$fin',
            proxima_facturacion = '$proxima'
      WHERE $where"
);

if (!$ok) {
    exit("\nERROR al actualizar: " . $conexion->error . "\n");
}

echo "\nLISTO. Negocios actualizados: " . $conexion->affected_rows . ". Vencen el $fin.\n";
