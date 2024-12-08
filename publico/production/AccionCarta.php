<?php
include 'La-carta.php';
$cart = new Cart;

require_once('../../configurar/configuracion.php');


$query = "SELECT * FROM sistem";
$search = $conexion->query($query);
if ($search->num_rows > 0) {
    while ($rowT = $search->fetch_assoc()) {
        $tickets = $rowT['tickets'];
    }
}


$query222222 = "SELECT * FROM cambio WHERE id='1'";
$buscarAlumnos222222 = $conexion->query($query222222);
if ($buscarAlumnos222222->num_rows > 0) {
    while ($filaAlumnos222222 = $buscarAlumnos222222->fetch_assoc()) {
        $PesoDolar = $filaAlumnos222222['pesoDolar'];
        $dolarBolivar = $filaAlumnos222222['DolarBolivar'];;
    }
}

$desscontado = '';

function diaSemana($fecha)
{
    return date('N', strtotime($fecha));
}


function Semana($fecha)
{
    return date('W', strtotime($fecha));
}



if (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {

    if ($_REQUEST['action'] == 'addToCart' && !empty($_REQUEST['id'])) {
        $productID = $_REQUEST['id'];
        $cant = $_REQUEST['cant'];
        $dolarventa = $_REQUEST['dolarventa'];
        $pesoventa = $_REQUEST['pesoventa'];
        $bolivarventa = $_REQUEST['bolivarventa'];

        // get product details
        $query = $conexion->query('SELECT * FROM productos WHERE id = ' . $productID);
        $row = $query->fetch_assoc();


        $valorPorductoBs = ($row['precio_compra'] / $row['cantidad_unidades']) * $PesoDolar;
        $valorPorductoPeso = ($row['precio_compra'] / $row['cantidad_unidades'])  * $dolarBolivar;


        $itemData = array(
            'codigo' => $row['codigo'],
            'id' => $row['id'],
            'name' => $row['nombre'],
            'price_C' => $row['precio_compra'] / $row['cantidad_unidades'],
            'price_C_Bs' =>  $valorPorductoBs,
            'price_C_Cop' => $valorPorductoPeso,
            'price' => $dolarventa,
            'pricePeso' => $pesoventa,
            'priceBolivar' => $bolivarventa,
            'qty' => $cant
        );

        $insertItem = $cart->insert($itemData);
        //  $redirectLoc = $insertItem ? 'ventas.php' : 'ventas.php';
        //  header('Location: ' . $redirectLoc);
    } elseif ($_REQUEST['action'] == 'updateCartItem' && !empty($_REQUEST['id'])) {
        $itemData = array(
            'rowid' => $_REQUEST['id'],
            'qty' => $_REQUEST['qty']
        );
        $updateItem = $cart->update($itemData);
        echo $updateItem ? 'ok' : 'err';
        die;
    } elseif ($_REQUEST['action'] == 'removeCartItem' && !empty($_REQUEST['id'])) {
        $deleteItem = $cart->remove($_REQUEST['id']);
        header('Location: ventas.php');
    } elseif ($_REQUEST['action'] == 'placeOrder' && $cart->total_items() > 0 && !empty($_SESSION['id'])) {
        // insert order details into database
        $fechaVenta = $_GET['fechaVenta'];
        $cedula = $_GET['cedula'];
        $valorFinalVenta = $_GET['valorFinalVenta'];
        $compraTipo = $_GET['compraTipo'];
        $pagoTipo = $_GET['pagoTipo'];


        $precioBs = $_GET['valorFinalBs'];
        $precioCop = $_GET['valorFinalCop'];

        if (isset($_GET['statusV'])) {
            $statusV = 3;
            $noticia = "descuento";
            $valorFinalVenta = $cart->total();
        } elseif ($compraTipo == '4') {
            $statusV = 4;
            $noticia = "vendido";
            $desscontado = $_SESSION['descontado'];
            unset($_SESSION['descontado']);
        } else {
            $statusV = 1;
            $noticia = "vendido";
        }


        if ($statusV == 3) {
            $fechaVenta = date('Y-m-d');
        }

        $explodeFecha = explode('-', $fechaVenta);
        $mes = $explodeFecha[0] . '-' . $explodeFecha[1];
        $fechatAno = $explodeFecha[0];
        $fechatMine = $explodeFecha[0] . '-' . Semana($fechaVenta);
        $dia = diaSemana($fechaVenta);



        $precioCop = formatPeso($precioCop);


        // saber que dia de la semana es una fehca ingresada
        // saber a que semana del ano pertenece
        ///////////////////////
        /////////////////////// REGISTRO DE PRODUCTOS DE LA ORDEN

        $insertOrder = $conexion->query("INSERT INTO orden (status, customer_id, total_price, created, modified, fecha, semana, ano, total_price_bs, total_price_cop, tipoPago, dia, descontado, isellerAct) VALUES ('$statusV', '" . $_SESSION['id'] . "', '$valorFinalVenta', '" . date('Y-m-d H:i:s') . "', '" . $fechaVenta . "', '" . $mes . "', '$fechatMine', '$fechatAno', '$precioBs', '$precioCop', '$pagoTipo', '" . $dia . "', '$desscontado', '1')");

        if ($insertOrder) {
            $orderID = $conexion->insert_id;

            $sql = '';
            // get cart items
            $cartItems = $cart->contents();
            foreach ($cartItems as $item) {

                $bolivar = $item['price_C'] * $dolarBolivar;
                $peso = $bolivar * $PesoDolar;

                $sql .= "INSERT INTO orden_articulos (order_id, product_id, quantity, precio, bolivar, peso, precio_venta_dolar, precio_venta_bs, precio_venta_cop) VALUES ('" . $orderID . "', '" . $item['id'] . "', '" . $item['qty'] . "', '" . $item['price_C'] . "', '" . $item['price_C_Bs'] . "', '" . $item['price_C_Cop'] . "', '" . $item['price'] . "', '" . $item['priceBolivar'] . "', '" . $item['pricePeso'] . "');";




                $query22 = "SELECT * FROM productos WHERE id='" . $item['id'] . "' LIMIT 1";
                $buscarAlumnos22 = $conexion->query($query22);
                if ($buscarAlumnos22->num_rows > 0) {
                    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
                        $stock1 = $filaAlumnos22['stock'];
                        $stock = $stock1 - $item['qty'];

                        if ($stock <= 0) {
                            $stock = 0;
                        }
                    }
                }




                $stmt_o = $conexion->prepare("UPDATE productos SET stock='$stock' WHERE id='" . $item['id'] . "'");
                $stmt_o->execute();
                $stmt_o->close();
            }
            if ($pagoTipo == 8) {
                $punto = $_GET['punto'];
                $pagoMovil = $_GET['pagoMovil'];
                $transferencia = $_GET['transferencia'];
                $bioPago = $_GET['bioPago'];
                $efectivo = $_GET['efectivo'];
                $pesos = $_GET['pesos'];
                $dolares = $_GET['dolares'];

                $insertFracciones = $conexion->query("INSERT INTO fracciones (id_order, punto, pagoMovil, transferencia, bioPago, efectivo, pesos, dolares, ValorPesos, ValorDolar) VALUES ('$orderID', '$punto', '$pagoMovil', '$transferencia', '$bioPago', '$efectivo', '$pesos', '$dolares', '$PesoDolar', '$dolarBolivar')");
            }




            $articulos =  count($cartItems);

            // insert order items into database
            $insertOrderItems = $conexion->multi_query($sql);




            if ($insertOrderItems) {
                $cart->destroy();



                if ($tickets == 0) {
                    header('Location: ventas.php?id=' . $orderID . '&accion=' . $noticia . '');
                } else {
                    header('Location: ticket.php?id=' . $orderID . '&accion=' . $noticia . '&qty=' . $articulos);
                }
            } else {
                header('Location: Pagos.php');
            }
        } else {
            //  header( 'Location: Pagos.php' );

        }
    } elseif ($_REQUEST['action'] == 'placeOrderCredito' && $cart->total_items() > 0 && !empty($_SESSION['id'])) {
        $fechaVenta = $_GET['fechaVenta'];
        // insert order details into database

        $valorFinalVenta = $_GET['valorFinalVenta'];
        $compraTipo = $_GET['compraTipo'];


        $precioBs = $_GET['valorFinalBs'];
        $precioCop = $_GET['valorFinalCop'];

        if (isset($_GET['statusV'])) {
            $statusV = 3;
            $noticia = "descuento";
            $valorFinalVenta = $cart->total();
        } elseif ($compraTipo == '4') {
            $statusV = 4;
            $noticia = "vendido";
            $desscontado = $_SESSION['descontado'];
            unset($_SESSION['descontado']);
        } else {
            $statusV = 1;
            $noticia = "vendido";
        }

        if ($statusV == 3) {
            $fechaVenta = date('Y-m-d');
        }

        ///////////////////////
        ///////////////////////

        $nombreC = $_GET['nombreC'];
        $cedula = $_GET['cedula'];
        $telefono = $_GET['telefono'];
        $nombreNego = $_GET['nombreNego'];
        $direccion = $_GET['direccion'];
        $compraTipo = $_GET['compraTipo'];


        $explodeFecha = explode('-', $fechaVenta);
        $mes = $explodeFecha[0] . '-' . $explodeFecha[1];
        $fechatAno = $explodeFecha[0];
        $fechatMine = $explodeFecha[0] . '-' . Semana($fechaVenta);
        $dia = diaSemana($fechaVenta);

        // saber que dia de la semana es una fehca ingresada
        // saber a que semana del ano pertenece

        $insertOrder = $conexion->query("INSERT INTO orden (status, customer_id, total_price, created, modified, fecha, semana, ano, total_price_bs, total_price_cop, dia, descontado, isellerAct) VALUES ('2','" . $_SESSION['id'] . "', '$valorFinalVenta', '" . date('Y-m-d H:i:s') . "', '" . $fechaVenta . "', '" . $mes . "', '$fechatMine', '$fechatAno', '$precioBs', '$precioCop', '" . $dia . "', '$desscontado', '1')");

        if ($insertOrder) {
            $orderID = $conexion->insert_id;
            $sql = '';
            // get cart items
            $cartItems = $cart->contents();
            foreach ($cartItems as $item) {
                $bolivar = $item['price_C'] * $dolarBolivar;
                $peso = $bolivar * $PesoDolar;

                $sql .= "INSERT INTO orden_articulos (order_id, product_id, quantity, precio, bolivar, peso, precio_venta_dolar, precio_venta_bs, precio_venta_cop) VALUES ('" . $orderID . "', '" . $item['id'] . "', '" . $item['qty'] . "', '" . $item['price_C'] . "', '" . $item['price_C_Bs'] . "', '" . $item['price_C_Cop'] . "', '" . $item['price'] . "', '" . $item['pricePeso'] . "', '" . $item['priceBolivar'] . "');";


                $query22 = "SELECT * FROM productos WHERE id='" . $item['id'] . "' LIMIT 1";
                $buscarAlumnos22 = $conexion->query($query22);
                if ($buscarAlumnos22->num_rows > 0) {
                    while ($filaAlumnos22 = $buscarAlumnos22->fetch_assoc()) {
                        $stock1 = $filaAlumnos22['stock'];
                        $stock = $stock1 - $item['qty'];
                        if ($stock <= 0) {
                            $stock = 0;
                        }
                    }
                }




                $stmt_o = $conexion->prepare("UPDATE productos SET stock='$stock' WHERE id='" . $item['id'] . "'");
                $stmt_o->execute();
                $stmt_o->close();
            }
            // insert order items into database


            $insertCliente = "INSERT INTO creditos (
                order_id, 
                total_price, 
                cliente, 
                cedula, 
                telefono, 
                negocio, 
                direccion,
                tipoCompra,
                estado) VALUES (
                    '$orderID', 
                    '" . $valorFinalVenta . "', 
                    '$nombreC', 
                    '$cedula', 
                    '$telefono', 
                    '$nombreNego', 
                    '$direccion', 
                    '$compraTipo',
                    '2')";


            $resultado2 = mysqli_query($conexion, $insertCliente);
            if (!$resultado2) {
            }


            $insertOrderItems = $conexion->multi_query($sql);



            if ($insertOrderItems) {
                $cart->destroy();
                header('Location: ventas.php?id=' . $orderID . '&accion=credito');
            } else {
                header('Location: Pagos.php');
            }
        } else {
            header('Location: Pagos.php');
        }
    } else {
        header('Location: index.php');
    }
} else {
    header('Location: index.php');
}
