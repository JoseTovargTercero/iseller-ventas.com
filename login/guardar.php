<?php


require_once('../configurar/configuracion.php');
//recuperamos usuario y contraseña y lo filtramos

$doc = mysqli_real_escape_string($conexion,  $_POST['login']);
$contrasena = mysqli_real_escape_string($conexion,  $_POST['password']);


$numberCode = rand(15000, 15000000);
$letras = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');

function ramdomAlphaCode()
{
    global $letras;
    $alphaCode = '';

    for ($i = 0; $i < 10; $i++) {
        $alphaCode .= $letras[rand(1, 26)];
    }
    return $alphaCode;
}


$stmt = mysqli_prepare($conexion, "SELECT * FROM usuarios WHERE usuario=?");
$stmt->bind_param("s", $doc);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if (password_verify($contrasena, $row['contrasena'])) {



            $_SESSION['nombre'] = $row['nombre'];
            $_SESSION['nivel'] = $row['nivel'];
            $_SESSION['id'] = $row['id'];
            $_SESSION["validate"] = "ok";

            $id = $_SESSION['id'];
            $nombre = $_SESSION['nombre'];
            $fecha = date('d/m/Y');
            $hora =  date('h:i A');
            $nivel = $_SESSION['nivel'];

            $_SESSION['qrcode'] = $id . '-' . $numberCode . ramdomAlphaCode();

            if ($_SESSION['nivel'] == '1') {
                define('PAGINA_INICIO', '../publico/production/ventas.php');
                header('Location: ' . PAGINA_INICIO);
            } else {
                define('PAGINA_INICIO', '../publico/production/ventas.php');
                header('Location: ' . PAGINA_INICIO);
            }
        } else {
            define('PAGINA_INICIO', '../index.php?error=error');
            header('Location: ' . PAGINA_INICIO);
        }
    }
} else {
    define('PAGINA_INICIO', '../index.php?error=error');
    header('Location: ' . PAGINA_INICIO);
}
