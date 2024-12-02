<?php


function MenuAdministrador(){
$menu =  '
    



 <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3>General</h3>
                <ul class="nav side-menu">
                  <li><a><i class="fa fa-heart"></i>00000000<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="index.php">00000000</a></li>
                    </ul>
                  </li>
                  
                   
                   <li><a><i class="fa fa-heart"></i>00000000<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="nuevoProducto.php">00000000</a></li>
                      <li><a href="nuevaCompra.php">00000000</a></li>
                                
                    </ul>
                  </li>
                  
                   
                   <li><a><i class="fa fa-heart"></i>00000000<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="creditos.php">00000000</a></li>
                      <li><a href="productos.php">00000000</a></li>
                     
                    </ul>
                  </li>
               
                   
                     
                     
                        <li><a href="users.php"> <i class="fa fa-heart"></i>00000000</a></li>
                       
                     
                       <li><a href="ventas.php"><i class="fa fa-heart"></i>00000000</a></li>
                 
                  </ul>    

              </div>

            </div>

            <!-- /menu footer buttons -->
            <div class="sidebar-footer hidden-small">
            
              <a href="configuracion.php" data-toggle="tooltip" data-placement="top" title="Configuración">
                <i class="fa fa-heart"></i>
              </a>
              <a href="compras.php"  data-toggle="tooltip" data-placement="top" title="Compras">
 <img src="images/fa-logo.png" height="21px" width="19px" alt="">
</a>
              <a  href="../build/pdf/precio.php" data-toggle="tooltip" data-placement="top" title="Etiquetas">
                <i class="fa fa-heart"></i>
              </a>
              <a data-toggle="tooltip" data-placement="top" title="Salir" href="../../login/salir.php">
                <i class="fa fa-heart"></i>
              </a>
            </div>
            <!-- /menu footer buttons -->




    ';
    
    return $menu;
    
    
}

function MenuStandar(){
$menu =  '
    



 <!-- sidebar menu -->
            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
              <div class="menu_section">
                <h3>General</h3>
                <ul class="nav side-menu">
             
                   
                   <li><a><i class="fa fa-edit"></i>00000000<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="nuevoProducto.php">00000000</a></li>
                      <li><a href="nuevaCompra.php">00000000</a></li>
                                
                    </ul>
                  </li>
                  
                   
                   <li><a><i class="fa fa-sliders"></i>00000000<span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                      <li><a href="creditos.php">00000000</a></li>
                     
                    </ul>
                  </li>
               
           
                       <li><a href="ventas.php"><i class="fa fa-shopping-cart"></i>Vender</a></li>
                 
                  </ul>    

              </div>

            </div>

    



    ';
    
    return $menu;
    
    
}

?>
               
























