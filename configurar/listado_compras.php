 <?php
    include 'configuracion.php';
    if (!isset($_SESSION["nivel"])) {
        exit;
    }

    $query2 = "SELECT * FROM empresa";
    $buscarAlumnos2 = $conexion->query($query2);
    if ($buscarAlumnos2->num_rows > 0) {
        while ($filaAlumnos2 = $buscarAlumnos2->fetch_assoc()) {
            $nombreEmpresa = $filaAlumnos2['emp'];
            $deshacerCompra = $filaAlumnos2['deshacerCompra'];
        }
    }

    $query6 = $conexion->query("SELECT * FROM compras ORDER BY id DESC LIMIT 150");

    if ($query6 && $query6->num_rows > 0) {
        $tabla6 = '';
        $contador = 1;

        while ($row6 = $query6->fetch_assoc()) {
            $id = (int) $row6["id"];
            $fecha = htmlspecialchars($row6["fecha"]);
            $producto = htmlspecialchars($row6["producto"]);
            $cantidad = htmlspecialchars($row6["cantidad"]);

            $deshacer = ($contador <= $deshacerCompra)
                ? "<a class='btn btn-sm btn-secondary' href='../../configurar/vaciarFactura.php?idDeshacer={$id}&origen=simple'>Deshacer</a>"
                : '';

            $tabla6 .= <<<HTML
                    <tr class="even pointer">
                        <td>{$contador}</td>
                        <td>{$fecha}</td>
                        <td>{$producto}</td>
                        <td>{$cantidad}</td>
                        <td class="a-right a-right">{$deshacer}</td>
                    </tr>
                    HTML;

            $contador++;
        }

        echo $tabla6;
    }
    ?>