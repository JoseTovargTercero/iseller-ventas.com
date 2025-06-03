<?php
/* ---------- INICIO: capturamos la salida ---------- */
ob_start();                 // 1) Arrancamos el buffer

?>
<div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
  <div class="menu_section">
    <ul class="nav side-menu">

      <?php
      require_once '../../configurar/configuracion.php';
      $menu_list = [];

      $stmt = mysqli_prepare($conexion, "SELECT * FROM `menu`");
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {

          $listar = ($_SESSION["nivel"] == 1 || isset($_SESSION['permisos'][$row['id']]));

          if ($listar) {
            // Estructura base
            $item = [
              "tipo"      => is_null($row['categoria']) ? 'item' : 'categoria',
              "categoria" => $row['categoria'],
              "icono"     => $row['icono'] ?? 'bx-error',
              "enlace"    => $row['dir'] ?? '#',
              "nombre"    => $row['nombre'] ?? 'Sin nombre',
              "sub-item"  => null
            ];

            if ($item['tipo'] === 'item') {
              $menu_list[$row['id']] = $item;
            } else {
              // categoría
              if (!isset($menu_list[$row['categoria']])) {
                $menu_list[$row['categoria']] = [
                  "tipo"      => "categoria",
                  "categoria" => $row['categoria'],
                  "icono"     => $row['icono'] ?? 'bx-error',
                  "sub-item"  => []
                ];
              }
              $menu_list[$row['categoria']]['sub-item'][] = [
                "enlace" => $row['dir'] ?? '#',
                "nombre" => $row['nombre'] ?? 'Sin nombre'
              ];
            }
          }
        }
      }
      $stmt->close();

      /* ----- función para imprimir items y categorías ----- */
      function generarMenu(array $menu_list): void
      {
        foreach ($menu_list as $item) {
          if ($item['tipo'] === 'item') {
            echo '<li class="pc-item">';
            echo    '<a href="' . htmlspecialchars($item['enlace']) . '" class="pc-link">';
            echo        '<i class=" ' . htmlspecialchars($item['icono']) . '"></i>';
            echo        '<span class="pc-mtext">' . htmlspecialchars($item['nombre']) . '</span>';
            echo    '</a>';
            echo '</li>';
          } else { // categoría con sub‑items
            echo '<li class="pc-item pc-hasmenu">';
            echo    '<a class="pc-link">';
            echo        '<i class=" ' . htmlspecialchars($item['icono']) . '"></i>';
            echo        '<span class="pc-mtext">' . htmlspecialchars($item['categoria']) . '</span>';
            echo        '<span class="pc-arrow"><i class="ti ti-chevron-right"></i></span>';
            echo    '</a>';
            echo    '<ul class="nav child_menu">';
            foreach ($item['sub-item'] as $sub) {
              echo '<li class="pc-item">';
              echo    '<a class="pc-link" href="' . htmlspecialchars($sub['enlace']) . '">';
              echo        htmlspecialchars($sub['nombre']);
              echo    '</a>';
              echo '</li>';
            }
            echo    '</ul>';
            echo '</li>';
          }
        }
      }

      generarMenu($menu_list);

      /* -------- bloque adicional solo para nivel 1 -------- */
      if ($_SESSION["nivel"] == 1): ?>
        <li class="m-3 p-2 pc-item pc-caption" style="text-align: center;">
          <label>Administrativo</label>
          <i data-feather="sidebar"></i>
        </li>
        <li class="pc-item pc-hasmenu">
          <a class="pc-link"><i class="line icon-people"></i> Usuarios
            <span class="fa fa-chevron-down"></span>
          </a>
          <ul class="nav child_menu">
            <li class="pc-item"><a class="pc-link" href="users.php">Nuevos</a></li>
            <li class="pc-item"><a class="pc-link" href="permisos.php">Permisos</a></li>
          </ul>
        </li>
      <?php endif; ?>

    </ul>
  </div>
</div>

<?php
/* ---------- FIN: capturamos la salida ---------- */
$menu = ob_get_clean();   // 2) Recuperamos y limpiamos el buffer

/* Ahora $menu contiene todo el HTML generado */


/*
     <li><a><i class="line icon-wallet"></i> Ventas <span class="fa fa-chevron-down"></span></a>
                    <ul class="nav child_menu">
                       <li><a href="listaVentas.php">Ventas del dia</a></li>
                      <li><a href="resumenSemana.php">Ventas de la semana</a></li>
                      <li><a href="resumenMes.php">Ventas del mes</a></li>
                    </ul>
                  </li>
*/
