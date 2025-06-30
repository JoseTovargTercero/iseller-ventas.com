<?php
// Inicialización
include 'la-carta.php';
$cart = new Cart;

require_once('configuracion.php');
require_once('session.php');
require("_tasas_cambio.php");

$desscontado = '';
$tickets = 0;

$id_sucursal = $_SESSION["sucursal"];
$bss_id = $_SESSION["bss_id"];

// Obtener configuración del sistema
$tickets = 0;

// Funciones auxiliares
function diaSemana($fecha)
{
    return date('N', strtotime($fecha));
}

function semanaAno($fecha)
{
    return date('W', strtotime($fecha));
}

// Validar acción
if (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
    $action = $_REQUEST['action'];

    switch ($action) {
        case 'addToCart':
            agregarAlCarrito($conexion, $cart);
            break;

        case 'updateCartItem':
            actualizarItemCarrito($cart);
            break;

        case 'removeCartItem':
            eliminarItemCarrito($cart);
            break;

        case 'placeOrder':
            procesarOrden($conexion, $cart, 'contado', $tickets);
            break;

        case 'placeOrderCredito':
            procesarOrden($conexion, $cart, 'credito', $tickets);
            break;

        default:
            echo json_encode(['status' => false, 'data' => 'No se ha especificado una acción']);
    }
} else {
    echo json_encode(['status' => false, 'data' => 'No se ha especificado una acción']);
}

// -----------------------------------------------------------
// FUNCIONES
// -----------------------------------------------------------

function agregarAlCarrito($conexion, $cart)
{
    $id = intval($_REQUEST['id']);
    $cant = $_REQUEST['cant'];
    $dolarventa = floatval($_REQUEST['dolarventa']);
    $pesoventa = floatval($_REQUEST['pesoventa']);
    $bolivarventa = floatval($_REQUEST['bolivarventa']);

    $stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    global $pesoDolar, $dolarBolivar;

    $valorUnidad = $producto['precio_compra'] / $producto['cantidad_unidades'];
    $mayor = floatval($producto['mayor']);

    $itemData = [
        'codigo' => $producto['codigo'],
        'id' => $producto['id'],
        'name' => $producto['nombre'],
        'price_C' => $valorUnidad,
        'price_C_Bs' => $valorUnidad * $dolarBolivar,
        'price_C_Cop' => $valorUnidad * $pesoDolar,
        'price' => $dolarventa,
        'pricePeso' => $pesoventa,
        'priceBolivar' => $bolivarventa,
        'qty' => $cant,
        'mayor' => $mayor,
        'cantidadPaca' => $producto['cantidad_unidades']
    ];

    $cart->insert($itemData);
}

function actualizarItemCarrito($cart)
{
    $itemData = [
        'rowid' => $_REQUEST['id'],
        'qty' => $_REQUEST['qty']
    ];
    echo $cart->update($itemData) ? 'ok' : 'err';
    exit;
}

function eliminarItemCarrito($cart)
{
    $cart->remove($_REQUEST['id']);
    echo json_encode(['status' => true, 'data' => 'Eliminado correctamente']);
    exit;
}
/*
function procesarOrden($conexion, $cart, $tipo = 'contado', $tickets = 0)
{
    global $id_sucursal, $bss_id;
    if ($cart->total_items() <= 0 || empty($_SESSION['id'])) {
        echo json_encode(['status' => false, 'data' => 'No hay productos en el carrito']);
        return;
    }

    // Datos base
    $fechaVenta = date('Y-m-d');
    $compraTipo = $_GET['compraTipo'] ?? '1';
    $pagoTipo = $_GET['pagoTipo'] ?? '';
    $precioBs = $_GET['valorFinalBs'] ?? 0;
    $precioCop = formatPeso($_GET['valorFinalCop'] ?? 0);
    $valorFinalVenta = $_GET['valorFinalVenta'] ?? 0;
    $statusV = $_GET['statusV'] ?? 1;
    if ($tipo == 'credito') {
        $statusV = 2;
    }
    $valorFinalVenta = $cart->total();

    $mes = date('Y-m');
    $ano = date('Y');
    $semana =  date('Y-W');
    $dia = date('N');

    // Registrar orden
    $stmt = $conexion->prepare("
        INSERT INTO orden (status, customer_id, total_price, created, modified, fecha, semana, ano, total_price_bs, total_price_cop, tipoPago, dia, id_sucursal, bss_id)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisssssddsiii", $statusV, $_SESSION['id'], $valorFinalVenta, $fechaVenta,  $mes, $semana, $ano, $precioBs, $precioCop, $pagoTipo, $dia, $id_sucursal, $bss_id);
    $stmt->execute();
    $orderID = $stmt->insert_id;
    $stmt->close();

    // Guardar artículos
    guardarArticulosOrden($conexion, $cart, $orderID);

    $msg = 'Venta realizada con éxito';

    // Si es crédito, guardar cliente
    if ($tipo === 'credito') {
        $nombreC = $_GET['nombreC'];

        $stmtC = $conexion->prepare("
            INSERT INTO creditos (order_id, total_price, negocio, tipoCompra, bss_id, sucursal_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmtC->bind_param("dsssii", $orderID, $valorFinalVenta, $nombreC, $compraTipo, $bss_id, $id_sucursal);
        $stmtC->execute();
        $stmtC->close();

        $msg = 'Crédito otorgado éxito';
    }



    $cart->destroy();
    $accion = ($statusV == 3) ? "descuento" : ($tipo == "credito" ? "credito" : "vendido");

    // Redirigir
    if ($tickets == 0) {
        echo json_encode(['status' => true, 'data' => $msg, 'id' => $orderID]);
    } else {
        // $qty = count($cart->contents());
        // header("Location: ../publico/production/ticket.php?id=$orderID&accion=$accion&qty=$qty");
    }
    exit;
}*/

function es_venta_mayor($cart)
{
    $result = false;

    foreach ($cart->contents() as $item) {
        if ($item['mayor'] == '1') {
            $result = true;
        }
    }
    return true;
}


function procesarOrden($conexion, $cart, $tipo = 'contado', $tickets = 0)
{
    global $id_sucursal, $bss_id;

    if ($cart->total_items() <= 0 || empty($_SESSION['id'])) {
        echo json_encode(['status' => false, 'data' => 'No hay productos en el carrito']);
        return;
    }

    $conexion->begin_transaction();

    try {
        // Datos base
        $fechaVenta = date('Y-m-d');
        $compraTipo = $_GET['compraTipo'] ?? 1;
        $pagoTipo = $_GET['pagoTipo'] ?? 0;
        $precioBs = $_GET['valorFinalBs'] ?? 0;
        $precioCop = formatPeso($_GET['valorFinalCop'] ?? 0);
        $valorFinalVenta = $cart->total();
        $statusV = $_GET['statusV'] ?? 1;
        if ($tipo == 'credito') {
            $statusV = 2;
        }



        // verifica el $cart, si hay algun producto al mayor, el statuV pasa a ser 4
        if (es_venta_mayor($cart)) {
            if ($tipo == 'credito') {
                $pagoTipo = 4;
            } else {
                $statusV = 4;
            }
        }


        $mes = date('Y-m');
        $ano = date('Y');
        $semana = date('Y-W');
        $dia = date('N');

        // Registrar orden
        $stmt = $conexion->prepare("
        INSERT INTO orden (
            status, customer_id, total_price, created, modified, fecha,
            semana, ano, total_price_bs, total_price_cop, tipoPago,
            dia, id_sucursal, bss_id
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        $stmt->bind_param(
            "iisssssddsiii",
            $statusV,
            $_SESSION['id'],
            $valorFinalVenta,
            $fechaVenta,
            $mes,
            $semana,
            $ano,
            $precioBs,
            $precioCop,
            $pagoTipo,
            $dia,
            $id_sucursal,
            $bss_id
        );

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Error al ejecutar la inserción en orden: " . $error);
        }

        $orderID = $stmt->insert_id;
        $stmt->close();

        if ($orderID <= 0) {
            throw new Exception("No se pudo obtener el ID de la orden insertada.");
        }


        // Guardar artículos
        guardarArticulosOrden($conexion, $cart, $orderID);

        // Si es crédito, guardar info
        if ($tipo === 'credito') {
            $nombreC = $_GET['nombreC'] ?? '';

            $stmtC = $conexion->prepare("
                INSERT INTO creditos (order_id, total_price, negocio, tipoCompra, bss_id, sucursal_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtC->bind_param("dsssii", $orderID, $valorFinalVenta, $nombreC, $compraTipo, $bss_id, $id_sucursal);
            $stmtC->execute();
            $stmtC->close();
        }

        $conexion->commit(); // Éxito

        $cart->destroy();
        $msg = ($tipo === 'credito') ? 'Crédito otorgado con éxito' : 'Venta realizada con éxito';

        echo json_encode(['status' => true, 'data' => $msg, 'id' => $orderID]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['status' => false, 'data' => 'Error al procesar orden: ' . $e->getMessage()]);
    }

    exit;
}



function guardarArticulosOrden($conexion, $cart, $orderID)
{
    global $dolarBolivar, $pesoDolar, $id_sucursal, $bss_id;

    // Preparar consulta de inserción para orden_articulos
    $insertStmt = $conexion->prepare(
        "INSERT INTO orden_articulos (
            order_id, product_id, quantity, precio, bolivar, peso,
            precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    // Preparar consultas para stock
    $stmtStock  = $conexion->prepare("SELECT stock, id_stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
    // para obtener la cantidad actual
    $updateStmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
    $updateStmtMayor = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?");
    // para actualizar la cantidad actual
    $stmtStockParaMayor  = $conexion->prepare("SELECT stock FROM stock WHERE id = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");


    foreach ($cart->contents() as $item) {

        // Ejecutar inserción del artículo de la orden
        $insertStmt->bind_param(
            "iiddddddddi",
            $orderID,
            $item['id'],
            $item['qty'],
            $item['price_C'],
            $item['price_C_Bs'],
            $item['price_C_Cop'],
            $item['price'],
            $item['priceBolivar'],
            $item['pricePeso'],
            $id_sucursal,
            $bss_id
        );
        $insertStmt->execute();



        if ($item['mayor'] == '1') {

            $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
            $stmtStock->execute();
            $result = $stmtStock->get_result()->fetch_assoc();
            $id_stock = $result['id_stock'];



            $stmtStockParaMayor->bind_param("iii", $id_stock, $id_sucursal, $bss_id);
            $stmtStockParaMayor->execute();
            $result = $stmtStockParaMayor->get_result()->fetch_assoc();
            $stock = max(0, $result['stock'] - ($item['qty'] * $item['cantidadPaca']));
            // se debe multiplicar por la cantidad




            $updateStmtMayor->bind_param("iiii", $stock, $id_stock, $id_sucursal, $bss_id);
            $updateStmtMayor->execute();
        } else {

            // Actualizar stock
            $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
            $stmtStock->execute();
            $result = $stmtStock->get_result()->fetch_assoc();
            $stock = max(0, $result['stock'] - $item['qty']);


            $updateStmt->bind_param("iiii", $stock, $item['id'], $id_sucursal, $bss_id);
            $updateStmt->execute();
        }
    }





    // Cerrar todas las sentencias
    $insertStmt->close();
    $stmtStock->close();
    $updateStmt->close();
    $updateStmtMayor->close();
}
