<?php
require_once("configuracion.php");
require_once('session.php');
require_once(__DIR__ . '/_nombres.php');

header('Content-Type: application/json');

$data = [];

$nivel = (int) ($_SESSION["nivel"] ?? 0);
$sucursal = $nivel === 1
  ? (int) ($_POST['sucursal'] ?? 0)
  : (int) ($_SESSION["sucursal"] ?? 0);

$extraCond = $sucursal > 0 ? 'AND C.sucursal_id = ' . $sucursal : '';

// Agrupa por cédula. Si el crédito no tiene cédula, agrupa por el nombre
// para no mezclar clientes distintos que tampoco tienen cédula.
$query6 = $conexion->query("SELECT
    MAX(C.negocio) AS negocio,
    MAX(TRIM(C.cedula)) AS cedula,
    COUNT(*) AS total,
    SUM(C.total_price) AS suma_monto,
    MAX(S.nombre) AS sucursal,
    MAX(O.created) AS ultimo_credito
  FROM `creditos` AS C
  LEFT JOIN sucursales AS S ON S.id = C.sucursal_id
  LEFT JOIN orden AS O ON O.id = C.order_id
  WHERE C.estado = 2 AND C.bss_id = $bss_id $extraCond
  GROUP BY CASE
      WHEN TRIM(COALESCE(C.cedula, '')) = '' THEN CONCAT('NOMBRE:', COALESCE(C.negocio, ''))
      ELSE CONCAT('CEDULA:', TRIM(C.cedula))
  END
  ORDER BY negocio ASC");

$filas = [];
$cedulas = [];
if ($query6 && $query6->num_rows > 0) {
  while ($row6 = $query6->fetch_assoc()) {
    $row6['cedula'] = trim((string) ($row6['cedula'] ?? ''));

    // Días transcurridos desde la fecha del último crédito del cliente.
    $ultimoCredito = trim((string) ($row6['ultimo_credito'] ?? ''));
    $diasUltimoCredito = null;
    $ultimoCreditoFecha = '';
    if ($ultimoCredito !== '' && strpos($ultimoCredito, '0000-00-00') !== 0) {
      try {
        $fechaCredito = new DateTime($ultimoCredito);
        $fechaCredito->setTime(0, 0, 0);
        $hoy = new DateTime('today');
        $diasUltimoCredito = max(0, (int) $fechaCredito->diff($hoy)->format('%r%a'));
        $ultimoCreditoFecha = $fechaCredito->format('d/m/Y');
      } catch (Throwable $e) {
        $diasUltimoCredito = null;
        $ultimoCreditoFecha = '';
      }
    }

    $row6['ultimo_credito_fecha'] = $ultimoCreditoFecha;
    $row6['dias_ultimo_credito'] = $diasUltimoCredito;

    $filas[] = $row6;
    if ($row6['cedula'] !== '') {
      $cedulas[$row6['cedula']] = true;
    }
  }
}
$cedulas = array_keys($cedulas);

// Nombre oficial registrado en base_nombres para cada cédula.
$mapaBase = [];
if ($cedulas && nb_tabla_existe($conexion)) {
  $marcadores = implode(',', array_fill(0, count($cedulas), '?'));
  $tipos = str_repeat('s', count($cedulas));
  $stmtBase = $conexion->prepare(
    "SELECT cedula, nombre FROM base_nombres WHERE cedula IN ($marcadores) ORDER BY id ASC"
  );
  if ($stmtBase) {
    $stmtBase->bind_param($tipos, ...$cedulas);
    if ($stmtBase->execute()) {
      $resBase = $stmtBase->get_result();
      while ($base = $resBase->fetch_assoc()) {
        $cedulaBase = trim((string) ($base['cedula'] ?? ''));
        if ($cedulaBase !== '') {
          // Si hay duplicados, se queda con el registro de mayor id.
          $mapaBase[$cedulaBase] = trim((string) ($base['nombre'] ?? ''));
        }
      }
    }
    $stmtBase->close();
  }
}

foreach ($filas as $fila) {
  $cedula = trim((string) ($fila['cedula'] ?? ''));
  $data[] = [
    'cliente' => $fila['negocio'],
    'cedula' => $cedula,
    'sin_cedula' => $cedula === '' ? 1 : 0,
    'nombre_base' => $cedula !== '' ? ($mapaBase[$cedula] ?? '') : '',
    'cantidad_creditos' => $fila['total'],
    'suma_monto' => $fila['suma_monto'] ? number_format($fila['suma_monto'], 2, '.', ',') : '0.00',
    'sucursal' => $fila['sucursal'],
    'ultimo_credito' => $fila['ultimo_credito_fecha'],
    'dias_ultimo_credito' => $fila['dias_ultimo_credito']
  ];
}

echo json_encode($data);
