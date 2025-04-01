<?php

// Verifica si la variable de sesión darkMode está definida, si no, consulta la base de datos
if (isset($_SESSION["darkMode"])) {
  $darkMode = $_SESSION["darkMode"];
} else {
  $id = $_SESSION['id'];
  $queryDarkModes = "SELECT darkMode FROM usuarios WHERE id='$id' LIMIT 1";
  $result = $conexion->query($queryDarkModes);

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $darkMode = $row['darkMode'] ? 'SI' : 'NO';
  } else {
    // Si no se encuentra el usuario, establece un valor predeterminado
    $darkMode = 'NO';
  }
}

// Determina cuál archivo CSS cargar según el modo oscuro
if ($darkMode == 'SI') {
  echo "<link href='../build/css/custom.min.dark-mode.css' rel='stylesheet'>";
} else {
  echo "<link href='../build/css/custom.min.css' rel='stylesheet'>";
}





/*

if (@$_SESSION["darkMode"] == "SI" || @$_SESSION["darkMode"] == "NO") {

  if ($_SESSION["darkMode"] == "SI") {
    echo "<link href='../build/css/custom.min.dark-mode.css' rel='stylesheet'>";
  } else {
    echo "<link href='../build/css/custom.min.css' rel='stylesheet'>";
  }
} else {

  $id35000 = $_SESSION['id'];
  $queryDarkModes = "SELECT * FROM usuarios WHERE id='$id35000'";
  $searchDarkMode = $conexion->query($queryDarkModes);
  if ($searchDarkMode->num_rows > 0) {
    while ($rowDark = $searchDarkMode->fetch_assoc()) {
      $darkMode = $rowDark['darkMode'];
    }
  }

  if ($darkMode == 1) {
    echo "<link href='../build/css/custom.min.dark-mode.css' rel='stylesheet'>";
  } else {
    echo "<link href='../build/css/custom.min.css' rel='stylesheet'>";
  }
}
*/