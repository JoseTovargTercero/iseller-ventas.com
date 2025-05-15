<?php
// Inicialización
include 'la-carta.php';
$cart = new Cart;

require_once('configuracion.php');
require_once('session.php');
require("_tasas_cambio.php");

$desscontado = '';
$tickets = 0;

$id_sucursal = $_SESSION["id_sucursal"];
$bss_id = $_SESSION["bss_id"];

// Obtener configuración del sistema
$result = $conexion->query("SELECT tickets FROM sistem");
if ($result && $row = $result->fetch_assoc()) {
    $tickets = $row['tickets'];
}

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
            header('Location: index.php');
    }
} else {
    header('Location: index.php');
}

// -----------------------------------------------------------
// FUNCIONES
// -----------------------------------------------------------

function agregarAlCarrito($conexion, $cart)
{
    $id = intval($_REQUEST['id']);
    $cant = floatval($_REQUEST['cant']);
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

    $itemData = [
        'codigo' => $producto['codigo'],
        'id' => $producto['id'],
        'name' => $producto['nombre'],
        'price_C' => $valorUnidad,
        'price_C_Bs' => $valorUnidad * $pesoDolar,
        'price_C_Cop' => $valorUnidad * $dolarBolivar,
        'price' => $dolarventa,
        'pricePeso' => $pesoventa,
        'priceBolivar' => $bolivarventa,
        'qty' => $cant
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
    header('Location: ventas.php');
    exit;
}

function procesarOrden($conexion, $cart, $tipo = 'contado', $tickets = 0)
{
    global $id_sucursal, $bss_id;
    if ($cart->total_items() <= 0 || empty($_SESSION['id'])) {
        header('Location: index.php');
        return;
    }

    // Datos base
    $fechaVenta = $_GET['fechaVenta'] ?? date('Y-m-d');
    $compraTipo = $_GET['compraTipo'] ?? '1';
    $pagoTipo = $_GET['pagoTipo'] ?? '';
    $precioBs = $_GET['valorFinalBs'];
    $precioCop = formatPeso($_GET['valorFinalCop']);
    $valorFinalVenta = $_GET['valorFinalVenta'];
    $statusV = 1;
    $desscontado = '';

    if (isset($_GET['statusV'])) {
        $statusV = 3;
        $valorFinalVenta = $cart->total();
    } elseif ($compraTipo == '4') {
        $statusV = 4;
        $desscontado = $_SESSION['descontado'] ?? '';
        unset($_SESSION['descontado']);
    }

    $mes = date('Y-m', strtotime($fechaVenta));
    $ano = date('Y', strtotime($fechaVenta));
    $semana = date('Y', strtotime($fechaVenta)) . '-' . semanaAno($fechaVenta);
    $dia = diaSemana($fechaVenta);

    // Registrar orden
    $stmt = $conexion->prepare("
        INSERT INTO orden (status, customer_id, total_price, created, modified, fecha, semana, ano, 
        total_price_bs, total_price_cop, tipoPago, dia, descontado, isellerAct, id_sucursal, bss_id)
        VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
    ");
    $stmt->bind_param("iisssssddsisii", $statusV, $_SESSION['id'], $valorFinalVenta, $fechaVenta, $fechaVenta, $mes, $ano, $precioBs, $precioCop, $pagoTipo, $dia, $desscontado, $id_sucursal, $bss_id);
    $stmt->execute();
    $orderID = $stmt->insert_id;
    $stmt->close();

    // Guardar artículos
    guardarArticulosOrden($conexion, $cart, $orderID);

    // Si es crédito, guardar cliente
    if ($tipo === 'credito') {
        $nombreC = $_GET['nombreC'];
        $cedula = $_GET['cedula'];
        $telefono = $_GET['telefono'];
        $nombreNego = $_GET['nombreNego'];
        $direccion = $_GET['direccion'];

        $stmtC = $conexion->prepare("
            INSERT INTO creditos (order_id, total_price, cliente, cedula, telefono, negocio, direccion, tipoCompra, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 2)
        ");
        $stmtC->bind_param("dsssssss", $orderID, $valorFinalVenta, $nombreC, $cedula, $telefono, $nombreNego, $direccion, $compraTipo);
        $stmtC->execute();
        $stmtC->close();
    }

    // Si es pago fraccionado
    if ($pagoTipo == 8) {
        $stmtF = $conexion->prepare("
            INSERT INTO fracciones (id_order, punto, pagoMovil, transferencia, bioPago, efectivo, pesos, dolares, ValorPesos, ValorDolar)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmtF->bind_param(
            "isssssssss",
            $orderID,
            $_GET['punto'],
            $_GET['pagoMovil'],
            $_GET['transferencia'],
            $_GET['bioPago'],
            $_GET['efectivo'],
            $_GET['pesos'],
            $_GET['dolares'],
            $GLOBALS['pesoDolar'],
            $GLOBALS['dolarBolivar']
        );
        $stmtF->execute();
        $stmtF->close();
    }

    $cart->destroy();
    $accion = ($statusV == 3) ? "descuento" : ($tipo == "credito" ? "credito" : "vendido");

    // Redirigir
    if ($tickets == 0) {
        header("Location: ../publico/production/ventas.php?id=$orderID&accion=$accion");
    } else {
        $qty = count($cart->contents());
        header("Location: ../publico/production/ticket.php?id=$orderID&accion=$accion&qty=$qty");
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
    $stmtStock  = $conexion->prepare("SELECT stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
    $updateStmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");

    foreach ($cart->contents() as $item) {

        // Ejecutar inserción del artículo de la orden
        $insertStmt->bind_param(
            "iiidddddddi",
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

        // Actualizar stock
        $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
        $stmtStock->execute();
        $result = $stmtStock->get_result()->fetch_assoc();

        $stock = max(0, $result['stock'] - $item['qty']);

        $updateStmt->bind_param("iiii", $stock, $item['id'], $id_sucursal, $bss_id);
        $updateStmt->execute();
    }

    // Cerrar todas las sentencias
    $insertStmt->close();
    $stmtStock->close();
    $updateStmt->close();
}
