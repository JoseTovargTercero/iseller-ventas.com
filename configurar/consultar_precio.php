
 <?php
  require_once("configuracion.php");
  require_once('session.php');
  require("_tasas_cambio.php");
  require("_calculadrora_precios.php");
  $calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);


  // Función para eliminar caracteres no numéricos
  function removeNonNumeric($string)
  {
    return preg_replace('/\D/', '', $string);
  }


  $precio = $_POST['precio'] ?? 1;
  $origen = $_POST['origen'] ?? 'v';
  $cantidad = $_POST['cantidad'] ?? 1;
  $porcentaje = $_POST['porcentaje']  ?? 0;

  if ($precio == 0) {
    exit(json_encode(['status' => 'success', 'data' => ['dolar' => 0, 'peso' => 0, 'bs' => 0]]));
  }

  $row = [
    'mayor' => 0,
    'stock' => 0,
    'porcentaje' => $porcentaje,
    'cantidad_unidades' => $cantidad,
    'origen' => $origen,
    'precio_compra' => $precio
  ];

  $precios = $calculadora->calcularPrecios($row);


  $data = [
    'dolar' => $precios['precio_venta_dolar'],
    'peso'  => $precios['precio_venta_peso'],
    'bs'    => $precios['precio_venta_bs'],
  ];

  echo json_encode(['status' => 'ok', 'data' => $data]);


  ?>