<?php

    include 'configuracion.php';
    require_once('session.php');

    $bss_id = $_SESSION['bss_id'];

    $productos_ignorar = [3803, 4956, 1684, 5440, 1651, ];

    $productos_a_poner_a_cero = [];

    //Obtener todos los productos
    $query6 = $conexion->query("SELECT p.id, p.nombre, s.stock FROM productos as p
    LEFT JOIN stock as s ON s.id_producto = p.id
     WHERE s.bss_id='$bss_id' AND s.id_sucursal='12'");
    if ($query6 && $query6->num_rows > 0) {
        while ($row6 = $query6->fetch_assoc()) {
            echo $row6['id'] . " - " . $row6['nombre'] . " - " . $row6['stock'] . "<br>";
        }
    }



    /*
Milanesa
Cochino
Chuleta ahumada 
Pollo fresco 
Pollo entero
Alas
Patas
Molleja 
Queso llanero
Jamón fiambre Drago
Jamón espalda Drago
Jamón pierna Drago
Mortadela especial dragó

    */