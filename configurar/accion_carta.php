<?php
// Inicialización
include 'la-carta.php';
$cart = new Cart;

require_once('configuracion.php');
require_once('session.php');
require("_tasas_cambio.php");


$query = "SELECT * FROM configuracion WHERE bss_id = $bss_id";
$search = $conexion->query($query);
if ($search->num_rows > 0) {
    while ($rowT = $search->fetch_assoc()) {
        $registro_clientes = $rowT['registro_clientes'];
    }
}




$desscontado = '';

$id_sucursal = $_SESSION["sucursal"];
$bss_id = $_SESSION["bss_id"];

// Obtener configuración del sistema

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
        case 'enviarPedidos':
            procesarCarritos();
            break;

        case 'logErrorVenta':
            logErrorVenta();
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

function logErrorVenta()
{
    global $conexion;
    $pedido = $_REQUEST['pedido'] ?? '';
    $errorMsg = $_REQUEST['error'] ?? '';
    $bss_id = $_SESSION["bss_id"];
    $sucursal_id = $_SESSION["sucursal"];


    $stmt = $conexion->prepare("INSERT INTO errores_ventas (venta, error_msg, bss_id, sucursal_id) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssii", $pedido, $errorMsg, $bss_id, $sucursal_id);
        $stmt->execute();
        echo json_encode(['status' => true]);
    } else {
        echo json_encode(['status' => false, 'error' => $conexion->error]);
    }
}

function procesarCarritos()
{
    $pedidos = $_REQUEST['pedidos'];
    global $conexion;
    // recorre pedidos, cada pedido es un carrito. que posee un 'metodoPago', despacho (puede se placeOrder o placeOrderCredito), y un array de productos
    $pedidos = json_decode($pedidos, true);
    if (empty($pedidos)) {
        echo json_encode(['status' => false, 'data' => 'No hay pedidos para procesar']);
        return;
    }

    $errores = [];

    // Inicializar array de órdenes procesadas en sesión si no existe
    if (!isset($_SESSION['processed_orders'])) {
        $_SESSION['processed_orders'] = [];
    }

    foreach ($pedidos as $pedido) {
        $idPedido = $pedido['id'];

        // Verificar si el pedido ya fue procesado
        if (in_array($idPedido, $_SESSION['processed_orders'])) {
            // Ya fue procesado, se ignora pero no se reporta como error para que el cliente limpie la cola
            continue;
        }

        $metodoPago = str_replace('option', '', $pedido['metodoPago']);

        $valorFinalBs = $pedido['valorFinalBs'];
        $valorFinalCop = $pedido['valorFinalCop'];
        $despacho = $pedido['despacho'];
        $productos = $pedido['productos'];
        $idPedido = $pedido['id'];
        $cliente = $pedido['datosCliente'] ?? []; // Cliente puede ser un array vacío si no se proporciona información

        if (empty($productos)) {
            $errores[] = "El pedido con método de pago '$metodoPago' no tiene productos.";
            continue;
        }

        // Crear un nuevo carrito
        $cart = new Cart;

        // Agregar productos al carrito
        foreach ($productos as $producto) {
            agregarAlCarrito($cart, $producto);
        }

        // Procesar la orden según el método de pago
        $tipoVeta = ($despacho == '2' ? 'credito' : 'contado'); // Credito/contado

        $respuesta = procesarOrden(
            $conexion,
            $cart,
            $tipoVeta,
            $despacho,
            $metodoPago,
            $valorFinalBs,
            $valorFinalCop,
            $cliente,
            $idPedido
        );
        if (!$respuesta['status']) {
            $errores[$idPedido] = $respuesta['data'];
        } else {
            // Marcar como procesado exitosamente
            $_SESSION['processed_orders'][] = $idPedido;

            // Limitar el tamaño del historial para ahorrar memoria (últimos 50)
            if (count($_SESSION['processed_orders']) > 50) {
                array_shift($_SESSION['processed_orders']);
            }
        }

        $cart->destroy();
    }
    if (empty($errores)) {
        echo json_encode(['status' => true, 'data' => 'Todos las vetnas se procesaron correctamente.']);
    } else {
        echo json_encode(['status' => false, 'data' => $errores]);
    }
}



function agregarAlCarrito($cart, $producto)
{
    $mayor = $producto['mayor'] == 'undefined' ? '0' : ($producto['mayor'] ?? 0);

    $itemData = [
        'id' => $producto['id'],
        'name' => $producto['name'],
        'price_C' => $producto['price_C'],
        'price_C_Bs' => $producto['price_C_Bs'],
        'price_C_Cop' => $producto['price_C_Cop'],
        'price' => floatval($producto['price']),
        'pricePeso' => floatval($producto['pricePeso']),
        'priceBolivar' => floatval($producto['priceBolivar']),
        'qty' => $producto['qty'],
        'mayor' => $mayor,
        'cantidadPaca' => $producto['cantidadPaca']
    ];

    $cart->insert($itemData);
}


function es_venta_mayor($cart)
{
    $result = false;

    foreach ($cart->contents() as $item) {
        if ($item['mayor'] == '1') {
            $result = true;
        }
    }
    return $result;
}

/* * Procesa la orden de compra, ya sea al contado o a crédito.
 * @param object $conexion Conexión a la base de datos.
 * @param object $cart Objeto del carrito de compras.
 * @param string $tipoVenta Tipo de venta ( 1 es venta normal. 2 es credito, 3 es descuento, 4 es venta al mayor ).
 * @param int $compraTipo Tipo de compra (1 para detal, 2 para mayor).
 * @param int $pagoTipo Tipo de pago (Punto, biopago, pesos, etc).
 * @param float $precioBs Precio en bolívares.
 * @param float $precioCop Precio en pesos colombianos.
 */

function procesarOrden($conexion, $cart, $tipo = 'contado', $tipoVenta = 1, $pagoTipo = 0, $precioBs = 0, $precioCop = 0, $cliente = ['nombre' => '', 'cedula' => '', 'telefono' => ''], $idPedido = null)
{
    global $id_sucursal, $bss_id, $registro_clientes;

    if ($cart->total_items() <= 0 || empty($_SESSION['id'])) {
        echo json_encode(['status' => false, 'data' => 'No hay productos en el carrito']);
        return;
    }

    $conexion->begin_transaction();

    try {
        // Datos base
        $fechaVenta = date('Y-m-d');
        $precioCop = formatPeso($precioCop ?? 0);

        $valorFinalVenta = $cart->total();
        $compraTipo = 1; // Detal por defecto
        if ($tipo == 'credito') {
            $tipoVenta = 2;
        }

        // verifica el $cart, si hay algun producto al mayor, el statuV pasa a ser 4
        if (es_venta_mayor($cart)) {
            $compraTipo = 4; // Venta al mayor
            if ($tipo == 'credito') {
                $pagoTipo = 4;
            } else {
                $tipoVenta = 4;
            }
        }

        $cedula = $cliente['cedula'];

        // Registra los datos del cliente si no existe

        if ($registro_clientes == 1 || $tipo === 'credito') {
            $query = "SELECT * FROM clientes WHERE cedula = ? AND bss_id = ?";
            $stmt = $conexion->prepare($query);
            $stmt->bind_param("s", $cliente['cedula']);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();

            if ($result->num_rows == 0) {
                // registra el nuevo cliente
                $query = "INSERT INTO clientes (nombre, cedula, telefono, bss_id, sucursal_id) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param("ssssi", $cliente['nombre'], $cliente['cedula'], $cliente['telefono'], $bss_id, $id_sucursal);
                $stmt->execute();
                $stmt->close();
            }
        }


        $mes = date('Y-m');
        $ano = date('Y');
        $semana = date('Y-W');
        $dia = date('N');

        // Registrar orden
        $stmt = $conexion->prepare("
        INSERT INTO orden (
            status, usuario, total_price, created, modified, fecha,
            semana, ano, total_price_bs, total_price_cop,  tipoPago,
            dia, id_sucursal, bss_id, id_pedido, cliente
        ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        $stmt->bind_param(
            "iisssssddsiiiss",
            $tipoVenta,
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
            $bss_id,
            $idPedido,
            $cedula
        );

        if (!$stmt->execute()) {
            if ($conexion->errno == 1062) {
                // Error de clave duplicada
                $stmt->close();
                // Retornamos éxito para que el cliente piense que se procesó, 
                // ya que en realidad YA SE había procesado.
                return ['status' => true];
            }
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


        // Preparar consulta de inserción para orden_articulos
        $insertStmt = $conexion->prepare(
            "INSERT INTO orden_articulos (
            order_id, product_id, quantity, precio, bolivar, peso,
            precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        // Preparar consultas para stock
        $stmtStock = $conexion->prepare("SELECT stock, id_stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
        // para obtener la cantidad actual
        $updateStmt = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");
        $updateStmtMayor = $conexion->prepare("UPDATE stock SET stock = ? WHERE id = ? AND id_sucursal = ? AND bss_id = ?");
        // para actualizar la cantidad actual
        $stmtStockParaMayor = $conexion->prepare("SELECT stock FROM stock WHERE id = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");


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
            // VALIDACIÓN AQUÍ:
            if (!$insertStmt->execute()) {
                throw new Exception("Error al insertar artículo de la orden: " . $insertStmt->error);
            }

            if ($item['mayor'] == '1') {

                $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
                if (!$stmtStock->execute()) {
                    throw new Exception("Error al consultar stock mayor: " . $stmtStock->error);
                }
                $result = $stmtStock->get_result()->fetch_assoc();
                $id_stock = $result['id_stock'];



                $stmtStockParaMayor->bind_param("iii", $id_stock, $id_sucursal, $bss_id);
                if (!$stmtStockParaMayor->execute()) {
                    throw new Exception("Error al consultar stock real mayor: " . $stmtStockParaMayor->error);
                }
                $result = $stmtStockParaMayor->get_result()->fetch_assoc();
                $stock = max(0, (float) $result['stock'] - ((float) $item['qty'] * (float) $item['cantidadPaca']));
                // se debe multiplicar por la cantidad


                $updateStmtMayor->bind_param("siii", $stock, $id_stock, $id_sucursal, $bss_id);
                if (!$updateStmtMayor->execute()) {
                    throw new Exception("Error al actualizar stock de mayor: " . $updateStmtMayor->error);
                }
            } else {

                // Actualizar stock
                $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
                if (!$stmtStock->execute()) {
                    throw new Exception("Error al consultar stock detal: " . $stmtStock->error);
                }
                $result = $stmtStock->get_result()->fetch_assoc();
                $stock = max(0, (float) $result['stock'] - (float) $item['qty']);


                $updateStmt->bind_param("siii", $stock, $item['id'], $id_sucursal, $bss_id);
                if (!$updateStmt->execute()) {
                    throw new Exception("Error al actualizar stock detal: " . $updateStmt->error);
                }
            }
        }

        // Cerrar todas las sentencias
        $insertStmt->close();
        $stmtStock->close();
        $updateStmt->close();
        $updateStmtMayor->close();

        // Guardar artículos



        // Si es crédito, guardar info, cedula y telefono
        if ($tipo === 'credito') {
            $nombreC = $cliente['nombre'] ?? '';
            $cedulaC = $cliente['cedula'] ?? '';

            $stmtC = $conexion->prepare("
                INSERT INTO creditos (order_id, total_price, total_price_bs, total_price_cop, negocio, tipoCompra, bss_id, sucursal_id, cedula)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtC->bind_param("idssssiii", $orderID, $valorFinalVenta, $precioBs, $precioCop, $nombreC, $compraTipo, $bss_id, $id_sucursal, $cedulaC);
            $stmtC->execute();
            $stmtC->close();
        }

        $conexion->commit(); // Éxito

        $cart->destroy();

        return ['status' => true];
        echo json_encode(['status' => true, 'data' => $msg, 'id' => $orderID]);
    } catch (Exception $e) {
        $conexion->rollback();
        return ['status' => false, 'data' => 'Error al procesar la orden: ' . $e->getMessage()];
    }
}
