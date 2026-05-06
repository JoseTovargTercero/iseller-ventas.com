<?php

    include 'configuracion.php';
    require_once('session.php');

    $bss_id = $_SESSION['bss_id'];


    $updateStmt = $conexion->prepare("UPDATE stock SET stock = 0 WHERE id = ?");

    //Obtener todos los productos
    $query6 = $conexion->query("SELECT p.id, p.nombre, s.stock FROM productos as p
    LEFT JOIN stock as s ON s.id_producto = p.id
     WHERE s.bss_id='$bss_id' AND s.id_sucursal='12' AND p.id NOT IN (
        3803, 4956, 1684, 5440, 1651, 3804, 4097, 3805, 1657, 5291, 5289,5290, 5397
     ) AND s.stock > 0");
    if ($query6 && $query6->num_rows > 0) {
        while ($row6 = $query6->fetch_assoc()) {
            $id  = $row6['id'];
            $id_sucursal = 12;

            $updateStmt->bind_param("i", $id);
            if ($updateStmt->execute()) {
                echo "Se puso a 0 el stock de: " . $row6['nombre'] . " - " . $row6['stock'] . "<br>";
            }else{
                echo "Error al poner a 0 el stock de: " . $row6['nombre'] . " - " . $row6['stock'] . "<br>";
            }
            
           

        }
    }

