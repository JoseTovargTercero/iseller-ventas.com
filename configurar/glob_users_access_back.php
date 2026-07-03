<?php
require_once 'configuracion.php';
require_once 'session.php';

if ($_SESSION["nivel"] != 1) {
    header("Location:" . constant('URL'));
}


$bss_id = $_SESSION["bss_id"];


function listaPermisosUser($id)
{ // cargar los permisos que tiene el usuario
    global $conexion;
    $permisos = [];

    $stmt = mysqli_prepare($conexion, "SELECT sup.id_item_menu, menu.nombre, menu.categoria FROM `users_permisos` AS sup
    LEFT JOIN menu ON menu.id = sup.id_item_menu
     WHERE id_user = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            array_push($permisos, [$row['id_item_menu'], $row['nombre'], $row['categoria']]);
        }
    }
    $stmt->close();
    return $permisos;
}

function verificarPermiso($item, $user)
{
    global $conexion;

    $stmt = mysqli_prepare($conexion, "SELECT * FROM `users_permisos` WHERE id_item_menu=? AND id_user = ?");
    $stmt->bind_param('ii', $item, $user);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return true;
    }
    $stmt->close();
    return false;
}





if (@$_POST["tabla"]) { // tabla de usuarios

    $datos = array();
    $stmt = mysqli_prepare($conexion, "SELECT * FROM `usuarios` WHERE bss_id = ? AND nivel!='1' AND status !='1'");
    $stmt->bind_param('s', $bss_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $datos[] =  array(
                "u_id" => $row["id"],
                "u_nombre" => $row["nombre"],
                "permisos" => listaPermisosUser($row["id"]),
                "creado" => $row["creado"]
            );
        }
    }
    $stmt->close();
    echo json_encode($datos);
} elseif (@$_POST['permisos']) {  // cargar los permisos que le pueden asignar/quitar al usuario

    $user = $_POST['user'];

    $datos = array();
    $stmt = mysqli_prepare($conexion, "SELECT * FROM `menu` WHERE admin = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $datos[] =  array(
                "id" => $row["id"],
                "categoria" => $row["categoria"],
                "nombre" => $row["nombre"],
                "icono" => $row["icono"],
                "permisos" => verificarPermiso($row['id'], $user)
            );
        }
    }
    $stmt->close();
    echo json_encode($datos, JSON_PRETTY_PRINT);
} elseif (@$_POST["set_permisos"]) {
    $user = $_POST["user"];
    $permiso = $_POST["permiso"];
    $status = $_POST["status"];


    // verificar al usuario administrador
    if ($_SESSION["nivel"] != 1) {
        echo json_encode(['error' => 'No tiene permisos para modificar el nivel acceso de este usuario']);
        exit;
    }


    // Modificar el acceso
    if ($status == 'true') { // eliminar

        $stmt = $conexion->prepare("DELETE FROM `users_permisos` WHERE id_user = ? AND id_item_menu = ?");
        $stmt->bind_param("ii", $user, $permiso);

        if ($stmt->execute()) {
            echo json_encode(['success' => 'Se quito el acceso al modulo']);
        } else {
            echo json_encode(['error' => 'No se pudo quitar el acceso al modulo']);
        }
        $stmt->close();
    } else { // registrar

        $stmt_o = $conexion->prepare("INSERT INTO `users_permisos` (id_user, id_item_menu) VALUES (?, ?)");
        $stmt_o->bind_param("ss", $user, $permiso);

        if ($stmt_o->execute()) {
            echo json_encode(['success' => 'Se agrego permisos nuevos al usuario']);
        } else {
            echo json_encode(['error' => 'No se pudo agregar el permiso']);
        }
        $stmt_o->close();
    }
}
