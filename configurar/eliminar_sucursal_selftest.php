<?php
/**
 * Selftest CLI del borrado de datos de una sucursal.
 * Uso: php configurar/eliminar_sucursal_selftest.php
 *
 * Trabaja dentro de una transacción y hace rollback al final: no deja datos
 * de prueba ni borra nada de verdad.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo CLI.');
}

require_once __DIR__ . '/configuracion.php';
require_once __DIR__ . '/eliminar_sucursal_lib.php';

// configuracion.php apaga los errores visibles; en la prueba sí los queremos.
error_reporting(E_ALL);
ini_set('display_errors', '1');

function afirmar($condicion, string $mensaje): void
{
    if (!$condicion) {
        fwrite(STDERR, "FALLO: {$mensaje}\n");
        exit(1);
    }
}

$sucursal = $conexion->query(
    "SELECT s.id, s.bss_id
       FROM sucursales s
       JOIN stock st ON st.id_sucursal = s.id
      GROUP BY s.id, s.bss_id
      LIMIT 1"
)->fetch_assoc();

if (!$sucursal) {
    fwrite(STDERR, "No hay sucursales con stock para probar.\n");
    exit(1);
}

$sucursalId = (int) $sucursal['id'];
$bssId = (int) $sucursal['bss_id'];

$admin = $conexion->query("SELECT id FROM usuarios WHERE bss_id = {$bssId} AND nivel = 1 LIMIT 1")->fetch_assoc();
afirmar($admin !== null, 'El negocio de prueba no tiene usuario nivel 1.');
$adminId = (int) $admin['id'];

$conexion->begin_transaction();

$stockAntes = contar("SELECT COUNT(*) FROM stock WHERE bss_id = {$bssId} AND id_sucursal = {$sucursalId}");
afirmar($stockAntes > 0, 'La sucursal de prueba no tiene stock.');

$sufijo = time();
$conexion->query("INSERT INTO clientes (bss_id, sucursal_id, nombre) VALUES ({$bssId}, {$sucursalId}, 'Cliente selftest {$sufijo}')");
$clienteId = (int) $conexion->insert_id;

$conexion->query(
    "INSERT INTO usuarios (bss_id, id_sucursal, usuario, nivel, contrasena, status)
     VALUES ({$bssId}, {$sucursalId}, 'selftest_{$sufijo}', 2, '', 0)"
);
$usuarioId = (int) $conexion->insert_id;
$conexion->query("INSERT INTO users_permisos (id_user, id_item_menu) VALUES ({$usuarioId}, 1)");

$borrados = eliminar_sucursal_datos($conexion, $bssId, $sucursalId, $adminId);

afirmar($borrados > 0, 'No se borró ninguna fila.');
afirmar(
    contar("SELECT COUNT(*) FROM stock WHERE bss_id = {$bssId} AND id_sucursal = {$sucursalId}") === 0,
    'Quedó stock de la sucursal.'
);
afirmar(
    contar("SELECT COUNT(*) FROM abonos WHERE bss_id = {$bssId} AND sucursal_id = {$sucursalId}") === 0,
    'Quedaron abonos de la sucursal.'
);
afirmar(
    contar("SELECT COUNT(*) FROM cortes_de_caja WHERE bss_id = {$bssId} AND sucursal_id = {$sucursalId}") === 0,
    'Quedaron cortes de caja de la sucursal.'
);
afirmar(
    contar("SELECT COUNT(*) FROM clientes WHERE id = {$clienteId}") === 1,
    'Se borró un cliente; debe conservarse.'
);
afirmar(
    contar("SELECT COUNT(*) FROM clientes WHERE id = {$clienteId} AND sucursal_id IS NULL") === 1,
    'El cliente no quedó sin sucursal.'
);
afirmar(
    contar("SELECT COUNT(*) FROM usuarios WHERE id = {$usuarioId}") === 0,
    'No se borró el usuario nivel 2 de la sucursal.'
);
afirmar(
    contar("SELECT COUNT(*) FROM usuarios WHERE id = {$adminId}") === 1,
    'Se borró el usuario nivel 1 (dueño).'
);
afirmar(
    contar("SELECT COUNT(*) FROM users_permisos WHERE id_user = {$usuarioId}") === 0,
    'Quedaron permisos del usuario borrado.'
);
afirmar(
    contar("SELECT COUNT(*) FROM sucursales WHERE id = {$sucursalId}") === 1,
    'La función no debe borrar la fila de sucursales.'
);

$conexion->rollback();

afirmar(
    contar("SELECT COUNT(*) FROM stock WHERE bss_id = {$bssId} AND id_sucursal = {$sucursalId}") === $stockAntes,
    'El rollback no restauró el stock.'
);
afirmar(
    contar("SELECT COUNT(*) FROM clientes WHERE id = {$clienteId}") === 0,
    'El rollback dejó el cliente de prueba.'
);

echo "OK: borrado de sucursal {$sucursalId} verificado ({$borrados} filas en la prueba).\n";
