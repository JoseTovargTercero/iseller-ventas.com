<?php

$stmt = mysqli_prepare($conexion, "SELECT * FROM `go_planes` WHERE ano = ?");
$stmt->bind_param('s', $var);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
    }
}
$stmt->close();


/* */
/* */
/* */


$stmt2 = $conexion->prepare("UPDATE `sheet_r` SET `estatus`='CERRADO' WHERE id=?");
$stmt2->bind_param("s", $caso);
$stmt2->execute();
$stmt2->close();




/* */
/* */
/* */
/* */



$stmt = $conexion->prepare("DELETE FROM `sheet_d2` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();


if ($stmt) {
    echo '1';
} else {
    echo '0';
}


/* */
/* */
/* */
/* */
$stmt_o = $conexion->prepare("INSERT INTO go_tareas (id_operacion, id_plan, tarea, descripcion, fecha, trimestre, ano, ubicacion, cords) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt_o->bind_param("sssssssss", $i, $id_plan, $res_car_nombre, $res_car_descripcion, $fecha, $trimestre, $anio, $ubicacion, $map);
$stmt_o->execute();

if ($stmt_o) {
    $id_r = $conexion->insert_id;
} else {
    echo "error";
}
$stmt_o->close();

header("Location: ../../public/index.php");





function contar($condicion)
{
    global $conexion;

    //$condicion = "SELECT count(*) FROM $table WHERE $condicion";
    $stmt = $conexion->prepare("SELECT count(*) FROM go_planes WHERE tipo='2' AND cerrado='1' AND ano='$ano' AND trimestre='1'");
    $stmt->execute();
    $row = $stmt->get_result()->fetch_row();
    $galTotal = $row[0];

    return $galTotal;
}






/*
     document.getElementById('sucursal-form').addEventListener('submit', function(e) {
                    e.preventDefault(); // Evitar envío tradicional

                    const form = e.target;
                    const formData = new FormData(form);

                    fetch('../../configurar/sucursales.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(res => res.text())
                        .then(text => {
                            console.log("Respuesta cruda del servidor:", text);
                            let json;
                            try {
                                json = JSON.parse(text);
                            } catch (e) {
                                console.error("Error al parsear JSON:", e);

                                Alerta.mostrar('error', 'El servidor no devolvió un JSON válido.');
                                return;
                            }

                            if (json.success) {
                                modalContainer.classList.remove("active");

                                Alerta.mostrar('success', json.message);
                                form.reset(); // Opcional: limpia el formulario
                                cargar_tabla()
                            } else {

                                Alerta.mostrar('warning', 'Hubo un problema ' + json.message);
                            }
                        })
                        .catch(err => {
                            modalContainer.classList.remove("active");

                            console.error("Error en la solicitud:", err);
                            Alerta.mostrar('error', 'No se pudo contactar con el servidor');
                        });
                });
*/


/*
 function cargar_tabla() {
                fetch('../../configurar/DatabaseHandler/_DBH-select.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            table: 'sucursales',
                            config: '_sucursales'
                        })
                    })
                    .then(response => response.text()) // Primero obtenemos el texto plano
                    .then(text => {
                        console.log("Respuesta cruda:", text); // Debug: ver el texto antes del parseo

                        try {
                            const data = JSON.parse(text); // Luego lo intentamos convertir a JSON

                            if (data.status === "success") {
                                console.log("Datos recibidos:", data.data);
                            } else {
                                console.error("Error:", data.message || data.error);
                            }
                        } catch (e) {
                            console.error("Error al parsear JSON:", e, "\nTexto recibido:", text);
                        }
                    })
                    .catch(error => {
                        console.error("Error en la solicitud:", error);
                    });
            }
*/