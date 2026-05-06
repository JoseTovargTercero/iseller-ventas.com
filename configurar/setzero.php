<?php

    include 'configuracion.php';
    require_once('session.php');

    $bss_id = $_SESSION['bss_id'];


    $updateStmt = $conexion->prepare("UPDATE stock SET stock = 0 WHERE id = ? AND id_sucursal='12'");

    //Obtener todos los productos
    $query6 = $conexion->query("SELECT s.id, p.nombre, s.stock FROM productos as p
    LEFT JOIN stock as s ON s.id_producto = p.id
     WHERE s.bss_id='$bss_id' AND s.id_sucursal='12'");
    if ($query6 && $query6->num_rows > 0) {
        while ($row6 = $query6->fetch_assoc()) {
            $id  = $row6['id'];
            $id_sucursal = 12;

          //  $updateStmt->bind_param("i", $id);
          //  if ($updateStmt->execute()) {
                echo "Se puso a 0 el stock de:  " . $row6['id'] . $row6['nombre'] . " - " . $row6['stock'] . "<br>";
            //}else{
            //    echo "Error al poner a 0 el stock de: " . $row6['nombre'] . " - " . $row6['stock'] . "<br>";
          //  }
            
           

        }
    }


    /*
 AND p.id NOT IN ( )
    
        3803, 4956, 1684, 5440, 1651, 3804, 4097, 3805, 1657, 5291, 5289,5290, 5397
    

UPDATE stock as s 
               INNER JOIN productos as p ON s.id_producto = p.id 
               SET s.stock = 0 
               WHERE s.id_sucursal = '12' 
               AND s.stock > 0
               AND p.id NOT IN (3803, 4956, 1684, 5440, 1651, 3804, 4097, 3805, 1657, 5291, 5289, 5290, 5397)
    */
