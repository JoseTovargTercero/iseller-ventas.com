<?php 
require_once("configuracion.php");



$id = $_SESSION['id'];


$queryDarkModes = "SELECT * FROM usuarios WHERE id='$id'";
$searchDarkMode = $conexion->query($queryDarkModes);
    if ($searchDarkMode->num_rows > 0) {
        while ($rowDark = $searchDarkMode->fetch_assoc()) {
            $darkMode = $rowDark['darkMode'];
    }
}


if($darkMode == 0){
    $_SESSION["darkMode"] = "SI";

    $update = "UPDATE usuarios SET darkMode='1'  WHERE id='$id'";
    $result = mysqli_query($conexion , $update );
    
   
}else{
    $update = "UPDATE usuarios SET darkMode='0'  WHERE id='$id'";
    $result = mysqli_query($conexion , $update );

    $_SESSION["darkMode"] = "NO";
}




?>

<script>
    window.history.go(-1);
</script>