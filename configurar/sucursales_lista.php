<?php
require_once("configuracion.php");
require_once('session.php');
if ($_SESSION["nivel"] != 1) {
    header("Location: ../index.php");
    exit;
} // Comprobar permisos

$bss_id = (int) $_SESSION["bss_id"];


$data = [];

$stmt = mysqli_prepare($conexion, "SELECT * FROM `sucursales` WHERE bss_id = ?");
$stmt->bind_param('s', $bss_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id = $row['id'];

        $productos = contar("SELECT count(*) FROM stock WHERE id_sucursal='$id'");
        $usuarios = contar("SELECT count(*) FROM usuarios WHERE id_sucursal='$id'");

        array_push($data, [
            'id' => $row['id'],
            'tipo' => $row['tipo'],
            'nombre' => $row['nombre'],
            'productos' => $productos,
            'usuarios' => $usuarios
        ]);
    }
}
$stmt->close();

echo json_encode($data);
