<?php
ob_start();
?>

<nav id="navbar">
  <ul class="navbar-items flexbox-col">
    <li class="navbar-logo d-flex justify-content-between" style="padding-left: 12px;">
      <a class="navbar-item-inner brand-lockup" aria-label="iSeller">
        <img class="brand-mark" src='images/logo1-inv-compact.png' alt="iSeller">
        <span class="brand-wordmark" aria-hidden="true"><span style="--i:0">i</span><span style="--i:1">S</span><span style="--i:2">e</span><span style="--i:3">l</span><span style="--i:4">l</span><span style="--i:5">e</span><span style="--i:6">r</span></span>
      </a>
      <a class="navbar-item-inner flexbox" id="menu_button"></a>
    </li>

    <?php
    require_once '../../configurar/configuracion.php';

    // Consultar estado de tienda virtual y órdenes pendientes
    $bss_id = $_SESSION['bss_id'] ?? 0;

    require_once '../../configurar/MembresiaHelper.php';
    $estadoMemMenu = MembresiaHelper::obtenerEstado($conexion, $bss_id);

    $tiene_tienda = 0;
    $pendientes = 0;
    if ($bss_id > 0) {
      $stmtTienda = mysqli_prepare($conexion, "SELECT tiene_tienda_virtual FROM negocio WHERE id = ?");
      if ($stmtTienda) {
        $stmtTienda->bind_param("i", $bss_id);
        $stmtTienda->execute();
        $resTienda = $stmtTienda->get_result();
        if ($rowTienda = $resTienda->fetch_assoc()) {
          $tiene_tienda = (int)$rowTienda['tiene_tienda_virtual'];
        }
        $stmtTienda->close();
      }
      if ($tiene_tienda) {
        $stmtPed = mysqli_prepare($conexion, "SELECT COUNT(*) AS total FROM tienda_ordenes WHERE bss_id = ? AND status = 1");
        if ($stmtPed) {
          $stmtPed->bind_param("i", $bss_id);
          $stmtPed->execute();
          $resPed = $stmtPed->get_result();
          if ($rowPed = $resPed->fetch_assoc()) {
            $pendientes = (int)$rowPed['total'];
          }
          $stmtPed->close();
        }
      }
    }

    $menu_list = [];

    $stmt = mysqli_prepare($conexion, "SELECT * FROM `menu`");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
        $cat_lower = strtolower($row['categoria'] ?? '');
        $is_tienda_cat = ($cat_lower === 'tienda virtual' || $cat_lower === 'tienda online');

        // Si es de tienda y el negocio aún no tiene tienda activa, no cargamos los subitems normales
        if ($is_tienda_cat && !$tiene_tienda) {
          continue;
        }

        // Si no tiene enlace o es un permiso interno sin ruta de menú, no mostrar en el menú
        if (empty($row['dir']) || $row['dir'] === '#' || $row['nombre'] === 'Venta de productos al mayor') {
          continue;
        }

        $listar = ($_SESSION["nivel"] == 1 || isset($_SESSION['permisos'][$row['id']]) || ($row['dir'] === 'tienda_planes_entrega.php' && in_array('tienda_pedidos.php', $_SESSION['permisos'] ?? [], true)));
        if ($listar) {
          $item = [
            "tipo"      => is_null($row['categoria']) ? 'item' : 'categoria',
            "categoria" => $row['categoria'],
            "icono"     => $row['icono'] ?? 'help-outline',
            "enlace"    => $row['dir'] ?? '#',
            "nombre"    => $row['nombre'] ?? 'Sin nombre',
            "sub-item"  => null
          ];

          if ($item['tipo'] === 'item') {
            $menu_list[$row['id']] = $item;
          } else {
            if (!isset($menu_list[$row['categoria']])) {
              $cat_icon = $row['icono'] ?? 'help-outline';
              if ($is_tienda_cat) {
                $cat_icon = 'bx bx-store';
              }
              $menu_list[$row['categoria']] = [
                "tipo"      => "categoria",
                "categoria" => $row['categoria'],
                "icono"     => $cat_icon,
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

    // Si el negocio NO tiene tienda virtual y es admin (nivel 1), mostrar opción para crearla
    if (!$tiene_tienda && $_SESSION["nivel"] == 1) {
      $menu_list['Tienda Online'] = [
        "tipo"      => "categoria",
        "categoria" => "Tienda Online",
        "icono"     => "bx bx-store",
        "sub-item"  => [
          [
            "enlace" => "tienda_onboarding.php",
            "nombre" => "Crear Mi Tienda"
          ]
        ]
      ];
    }

    function renderMenuIcon(string $icon, int $pendientes = 0, bool $is_tienda = false): string
    {
      $html = '';
      $icon = trim($icon);

      if (empty($icon)) {
        $html = '<ion-icon name="help-outline"></ion-icon>';
      } elseif (strpos($icon, 'bx') !== false) {
        $classes = $icon;
        if (strpos($classes, 'bx ') === false && strpos($classes, 'bxs ') === false && strpos($classes, 'bxl ') === false) {
          $classes = 'bx ' . $classes;
        }
        $html = '<i class="' . htmlspecialchars($classes) . '"></i>';
      } else {
        $html = '<ion-icon name="' . htmlspecialchars($icon) . '"></ion-icon>';
      }

      if ($is_tienda) {
        if ($pendientes > 0) {
          $html .= '<span class="menu-badge menu-badge-icon">' . (int)$pendientes . '</span>';
        } else {
          $html .= '<span class="tienda-star">&#9733;</span>';
        }
      }

      return $html;
    }

    function generarMenuAdaptado(array $menu_list, int $pendientes = 0): void
    {
      foreach ($menu_list as $item) {
        if ($item['tipo'] === 'item') {
          echo '<li class="navbar-item flexbox-left">';
          echo   '<a class="navbar-item-inner flexbox-left" href="' . htmlspecialchars($item['enlace']) . '">';
          echo     '<div class="navbar-item-inner-icon-wrapper flexbox">';
          echo       renderMenuIcon($item['icono']);
          echo     '</div>';
          echo     '<span class="link-text">' . htmlspecialchars($item['nombre']) . '</span>';
          echo   '</a>';
          echo '</li>';
        } else {
          $cat_lower = strtolower($item['categoria']);
          $is_tienda = ($cat_lower === 'tienda virtual' || $cat_lower === 'tienda online');

          echo '<li class="navbar-item navdrop">';
          echo   '<div class="navdrop-toggle navbar-item-inner flexbox-left">';
          echo     '<div class="navbar-item-inner-icon-wrapper flexbox">';
          echo       renderMenuIcon($item['icono'], $pendientes, $is_tienda);
          echo     '</div>';
          echo     '<span class="link-text">' . htmlspecialchars($item['categoria']) . '</span>';
          echo   '</div>';
          echo   '<div class="navdrop-menu">';
          foreach ($item['sub-item'] as $sub) {
            $badge = '';
            if ($is_tienda && $pendientes > 0 && strpos($sub['enlace'], 'tienda_pedidos.php') !== false) {
              $badge = ' <span class="menu-badge">' . (int)$pendientes . '</span>';
            }
            echo '<a href="' . htmlspecialchars($sub['enlace']) . '">' . htmlspecialchars($sub['nombre']) . $badge . '</a>';
          }
          echo   '</div>';
          echo '</li>';
        }
      }
    }

    generarMenuAdaptado($menu_list, $pendientes);

    if ($_SESSION["nivel"] == 1): ?>
      <li class="navbar-item navdrop">
        <div class="navdrop-toggle navbar-item-inner flexbox-left">
          <div class="navbar-item-inner-icon-wrapper flexbox">
            <ion-icon name="people-outline"></ion-icon>
          </div>
          <span class="link-text">Usuarios</span>
        </div>
        <div class="navdrop-menu">
          <a href="users.php">Nuevos</a>
          <a href="permisos.php">Permisos</a>
        </div>
      </li>

      <!-- Ítem Membresía -->
      <?php
      $badgeMembresia = '';
      if ($estadoMemMenu['esta_vencida']) {
        $badgeMembresia = ' <span class="menu-badge" style="background:#ef4444;">Vencida</span>';
      } elseif ($estadoMemMenu['por_vencer']) {
        $badgeMembresia = ' <span class="menu-badge" style="background:#f59e0b;">' . $estadoMemMenu['dias_restantes'] . 'd</span>';
      } elseif ($estadoMemMenu['pago_pendiente']) {
        $badgeMembresia = ' <span class="menu-badge" style="background:#3b82f6;">Revisión</span>';
      }
      ?>
      <li class="navbar-item flexbox-left">
        <a class="navbar-item-inner flexbox-left" href="membresia.php">
          <div class="navbar-item-inner-icon-wrapper flexbox">
            <i class="bx bx-id-card" style="font-size: 1.35rem; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%;"></i>
            <?php if ($estadoMemMenu['por_vencer'] || $estadoMemMenu['esta_vencida']): ?>
              <span class="menu-badge menu-badge-icon" style="background:<?php echo $estadoMemMenu['esta_vencida'] ? '#ef4444' : '#f59e0b'; ?>;">!</span>
            <?php endif; ?>
          </div>
          <span class="link-text">Membresía<?php echo $badgeMembresia; ?></span>
        </a>
      </li>
    <?php endif; ?>

  </ul>
</nav>

<?php
$menu = ob_get_clean();
?>