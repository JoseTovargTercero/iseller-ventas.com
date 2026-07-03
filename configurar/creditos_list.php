<?php
require_once("configuracion.php");
require_once('session.php');

header('Content-Type: application/json');

$data = [];



$sucursal = ($_SESSION["nivel"] == 1)
  ? ($_POST['sucursal'] ?? null)
  : $_SESSION["sucursal"];

$extraCond = $sucursal ? 'AND C.sucursal_id=' . $sucursal : '';


$query6 = $conexion->query("SELECT DISTINCT(C.negocio), COUNT(*) AS total, SUM(C.total_price) AS suma_monto, S.nombre AS sucursal FROM `creditos` AS C 
LEFT JOIN sucursales AS S ON S.id = C.sucursal_id
WHERE C.estado = 2 AND C.bss_id = $bss_id $extraCond GROUP BY C.negocio");
if ($query6->num_rows > 0) {
  while ($row6 = $query6->fetch_assoc()) {

    array_push($data, [
      'cliente' => $row6["negocio"],
      'cantidad_creditos' => $row6["total"],
      'suma_monto' => $row6["suma_monto"] ? number_format($row6["suma_monto"], 2, '.', ',') : '0.00',
      'sucursal' => $row6['sucursal']
    ]);
  }
}
echo json_encode($data);
