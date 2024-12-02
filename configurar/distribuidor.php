<?php
require_once("configuracion.php");


if (isset($_GET['accion'])) {

  if ($_GET['accion'] == "activar") {
    $accion = "1";
  } else {
    $accion = "0";
  }
  $stmt = $conexion->prepare("UPDATE sistem SET tickets='$accion'");
  $stmt->execute();
  $stmt->close();
  
  define('PAGINA_INICIO', '../publico/production/configuracion.php?accion=notificacion' . $accion . '');
  header('Location: ' . PAGINA_INICIO);
}







if (isset($_GET['borrar'])) {
  $id =  $_GET['id'];
  $stmt = $conexion->prepare("DELETE FROM distribuidor WHERE id='$id'");
  $stmt->execute();
  $stmt->close();

  define('PAGINA_RETORNO', '../publico/production/distribuidorIndex.php?agregado=borrado');
  header('Location: ' . PAGINA_RETORNO);
}

?>








                       <?php

                        if (isset($_POST['nombre'])) {
                          $nombre  = $_POST['nombre'];
                          $cantidad_unidades  = $_POST['cantidad_unidades'];
                          $precio_compra  = $_POST['precio_compra'];


                          $insertar = "INSERT INTO distribuidor (nombre, cantidad, precio) VALUES ('$nombre','$cantidad_unidades','$precio_compra')";

                          $resultado2 = mysqli_query($conexion, $insertar);
                          if (!$resultado2) {

                            define('PAGINA_RETORNO', '../publico/production/distribuidorIndex.php?agregado=error');
                            header('Location: ' . PAGINA_RETORNO);
                          } else {
                            define('PAGINA_RETORNO', '../publico/production/distribuidorIndex.php?agregado=correcto');
                            header('Location: ' . PAGINA_RETORNO);

                            echo '<script>
            function mensaje(){	
			alertify.success("Agregado a correctamente.");}
            </script>
            <body onload="mensaje()">
            </body>';
                          }
                        }




                        ?>