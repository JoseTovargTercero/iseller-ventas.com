<?php
require_once('configuracion.php');
require_once('session.php');

$bss_id      = (int) $_SESSION['bss_id'];
$nivel       = (int) ($_SESSION['nivel'] ?? 0);
$id_sucursal = (int) ($_SESSION['sucursal'] ?? 0);
$id          = (int) ($_POST['id'] ?? 0);

if (!$bss_id || $id <= 0 || ($nivel !== 1 && $id_sucursal <= 0)) {
    echo 'No autorizado';
    exit;
}

$filtroSucursal = $nivel === 1 ? '' : ' AND id_sucursal=?';
$stmt = $conexion->prepare("DELETE FROM gastos WHERE id=? AND bss_id=?$filtroSucursal");
if ($nivel === 1) {
    $stmt->bind_param('ii', $id, $bss_id);
} else {
    $stmt->bind_param('iii', $id, $bss_id, $id_sucursal);
}
$stmt->execute();
$stmt->close();

echo 'id - ' . $id;
