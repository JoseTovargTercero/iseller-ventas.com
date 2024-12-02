<?php
    
if(@$_SESSION["darkMode"] == "SI" || @$_SESSION["darkMode"] == "NO" ){

  if($_SESSION["darkMode"] == "SI"){
    echo '
    <style>
      html{
        filter: invert(1) !important;
      }

      button, .buttons, .btn, .modal-footer .btn + .btn, .navbar img, .contenedorImagen img, .dataTables_paginate a, .timeline .tags, .mio3, .mio3 a {
        opacity: 1 !important;
        filter: invert(1) !important;
    }


    .card::before {
      filter: invert(0.5);
  }



  tr, table.jambo_table thead{
    filter: invert(0.9) !important;
  }

  table.dataTable {
    border-collapse: collapse !important;
}
.modal-backdrop {
  
  background-color: #fff !important;
}

      </style>';
  }else{
    echo '
    <style>
    html{
        filter: invert(0) !important;
      }
      
      </style>';
  }

}else{

$id35000 = $_SESSION['id'];
$queryDarkModes = "SELECT * FROM usuarios WHERE id='$id35000'";
$searchDarkMode = $conexion->query($queryDarkModes);
    if ($searchDarkMode->num_rows > 0) {
        while ($rowDark = $searchDarkMode->fetch_assoc()) {
            $darkMode = $rowDark['darkMode'];
    }
}

if($darkMode == 1){
  echo '
  <style>
  html{
      filter: invert(0.9) !important;
    }
    </style>';
}else{
  echo '
  <style>
  html{
      filter: invert(0) !important;
    }
    </style>';
}


}

    
  ?>
