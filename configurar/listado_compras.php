 <?php
    include 'configuracion.php';
    require_once('session.php');
    if (!isset($_SESSION["nivel"])) {
        exit;
    }

    $query6 = $conexion->query("SELECT * FROM compras WHERE bss_id='{$_SESSION['bss_id']}' ORDER BY id DESC LIMIT 150");

    if ($query6 && $query6->num_rows > 0) {
        $tabla6 = '';
        $contador = 1;

        while ($row6 = $query6->fetch_assoc()) {
            $id = (int) $row6["id"];
            $fecha = htmlspecialchars($row6["fecha"]);
            $producto = htmlspecialchars($row6["producto"]);
            $cantidad = htmlspecialchars($row6["cantidad"]);

            $deshacer = ($contador <= 10)
                ? "<a class='btn btn-sm btn-danger' href='../../configurar/vaciarFactura.php?idDeshacer={$id}&origen=simple'>Deshacer</a>"
                : '';

            $tabla6 .= <<<HTML
                    <tr class="even pointer">
                        <td>{$producto} <br> <small class="text-muted">{$fecha}</small></td>
                        <td>{$cantidad}</td>
                        <td class="a-right a-right">{$deshacer}</td>
                    </tr>
                    HTML;

            $contador++;
        }

        echo $tabla6;
    }
    ?>