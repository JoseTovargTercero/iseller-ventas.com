<?php
require_once("configuracion.php");
require_once('session.php');
require_once('eliminar_sucursal_lib.php');

header('Content-Type: application/json');

$response = ['success' => false, 'mensaje' => ''];

function responder(array $response): void
{
    echo json_encode($response);
    exit;
}

if ((int) $_SESSION['nivel'] !== 1) {
    $response['mensaje'] = 'No tienes permiso para eliminar sucursales.';
    responder($response);
}

$bss_id = (int) $_SESSION['bss_id'];
$usuario_id = (int) $_SESSION['id'];
$id = (int) ($_POST['id_sucursal'] ?? 0);
$password = (string) ($_POST['password'] ?? '');

if ($id <= 0 || $password === '') {
    $response['mensaje'] = 'Faltan datos: sucursal y contraseña.';
    responder($response);
}

// Contraseña del usuario que está ejecutando la acción.
$stmt = $conexion->prepare("SELECT contrasena FROM usuarios WHERE id = ? AND bss_id = ? LIMIT 1");
$stmt->bind_param("ii", $usuario_id, $bss_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario || !password_verify($password, (string) $usuario['contrasena'])) {
    $response['mensaje'] = 'Contraseña incorrecta. No se eliminó nada.';
    responder($response);
}

// La sucursal debe ser del negocio de la sesión.
$stmt = $conexion->prepare("SELECT id, nombre FROM sucursales WHERE id = ? AND bss_id = ? LIMIT 1");
$stmt->bind_param("ii", $id, $bss_id);
$stmt->execute();
$sucursal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$sucursal) {
    $response['mensaje'] = 'La sucursal no existe o no pertenece a tu negocio.';
    responder($response);
}

$stmt = $conexion->prepare("SELECT COUNT(*) AS total FROM sucursales WHERE bss_id = ?");
$stmt->bind_param("i", $bss_id);
$stmt->execute();
$total_sucursales = (int) $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

if ($total_sucursales <= 1) {
    $response['mensaje'] = 'No se puede eliminar la única sucursal del negocio.';
    responder($response);
}

$conexion->begin_transaction();

try {
    $borrados = eliminar_sucursal_datos($conexion, $bss_id, $id, $usuario_id);

    $stmt = $conexion->prepare("DELETE FROM sucursales WHERE id = ? AND bss_id = ?");
    $stmt->bind_param("ii", $id, $bss_id);
    if (!$stmt->execute()) {
        throw new RuntimeException('Error al borrar la sucursal: ' . $stmt->error);
    }
    $stmt->close();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    error_log('eliminar_sucursal: ' . $e->getMessage());
    $response['mensaje'] = 'No se pudo eliminar la sucursal. No se borró ningún dato.';
    responder($response);
}

// Fuera de la transacción: el logo es un archivo, no una fila.
$logo = __DIR__ . '/../publico/production/images/sucursal_logo/' . $id . '.png';
if (is_file($logo)) {
    @unlink($logo);
}

// Si la sesión apuntaba a la sucursal borrada, se deja sin sucursal activa.
if ((int) ($_SESSION['sucursal'] ?? 0) === $id) {
    unset($_SESSION['sucursal']);
}

$response['success'] = true;
$response['mensaje'] = 'Sucursal "' . $sucursal['nombre'] . '" eliminada. Registros borrados: ' . $borrados . '.';
responder($response);
