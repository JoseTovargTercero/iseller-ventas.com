<?php
require_once('configuracion.php');
require_once('session.php');
header('Content-Type: application/json');

if (!isset($_SESSION['nivel']) || $_SESSION['nivel'] != 1) {
   echo json_encode(['tipo' => 'error', 'mensaje' => 'No tienes permisos para realizar esta acción.']);
   exit;
}


// Obtener y validar los datos del formulario
$nombre = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
$user = filter_input(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
$contrasena = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
$conf_contrasena = filter_input(INPUT_POST, 'password2', FILTER_DEFAULT);
$nivel = filter_input(INPUT_POST, 'nivel', FILTER_VALIDATE_INT);
$sucursal_asociada = filter_input(INPUT_POST, 'sucursal_asociada', FILTER_VALIDATE_INT);

if (!$nombre || !$user || !$contrasena || !$nivel) {
   echo json_encode(['tipo' => 'error', 'mensaje' => 'Todos los campos son obligatorios.']);
   exit;
}

if ($contrasena !== $conf_contrasena) {
   echo json_encode(['tipo' => 'error', 'mensaje' => 'Las contraseñas no coinciden.']);
   exit;
}



$stmt = mysqli_prepare($conexion, "SELECT * FROM `usuarios` WHERE status = '0' AND usuario = ?");
$stmt->bind_param('s', $user);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
   echo json_encode(['tipo' => 'error', 'mensaje' => 'El usuario ya existe.']);
   exit;
}
$stmt->close();




// Encriptar la contraseña con password_hash
$contrasena_hashed = password_hash($contrasena, PASSWORD_BCRYPT);
$status = 0;
try {
   $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, contrasena, nivel, id_sucursal, bss_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
   $stmt->bind_param("sssiiii", $nombre, $user, $contrasena_hashed, $nivel, $sucursal_asociada, $bss_id, $status);

   if ($stmt->execute()) {
      echo json_encode(['tipo' => 'success', 'mensaje' => 'Usuario registrado correctamente.']);
   } else {
      echo json_encode(['tipo' => 'error', 'mensaje' => 'Error al registrar usuario.']);
   }

   $stmt->close();
} catch (Exception $e) {
   echo json_encode(['tipo' => 'error', 'mensaje' => 'Error: ' . $e->getMessage()]);
}

$conexion->close();
