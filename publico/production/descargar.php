<?php
 $remote_file_url = 'https://i-seller.000webhostapp.com/AC-MT.zip';
 $local_file = 'AC-MT.zip';
 $copy = copy($remote_file_url, $local_file);

 if ($copy) {
    
     $zip = new ZipArchive;
     $comprimido= $zip->open('AC-MT.zip');
     if($comprimido === TRUE) {
        $zip->extractTo('../../ACTUALIZACIONES/');
         $zip->close();

         if(unlink('AC-MT.zip')){
           
            echo "<script>
                          alert('Descomprimido y eliminado');
                            </script>;";     
        
         
         }


         define('PAGINA_INICIO', 'compras.php');
         header('Location: ' . PAGINA_INICIO);
     } else {
        define('PAGINA_INICIO', 'compras.php?mensaje=conexion');
        header('Location: ' . PAGINA_INICIO);
     }



 } else {
    define('PAGINA_INICIO', 'compras.php?mensaje=conexion');
    header('Location: ' . PAGINA_INICIO);


 }