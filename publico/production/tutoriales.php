<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav = topnav();

    if ($_SESSION["validate"] != "ok") {
        define('PAGINA_INICIO', '../../index.php');
        header('Location: ' . PAGINA_INICIO);
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <title>Tutoriales</title>
  <?php require_once('includes/headers.php'); ?>
  <style>
    .right_col {
      background: var(--dash-bg);
      min-height: 100vh;
      padding: 24px 28px !important;
    }
    .tuto-header {
      margin-bottom: 28px;
    }
    .tuto-header h3 {
      font-size: 20px;
      font-weight: 700;
      color: var(--dash-text);
      margin: 0;
    }
    .tuto-header p {
      color: var(--dash-text-muted);
      margin: 2px 0 0;
      font-size: 13px;
    }
    .tuto-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
    }
    .tuto-card {
      background: var(--dash-card);
      border: 1px solid var(--dash-border);
      border-radius: 14px;
      overflow: hidden;
      cursor: pointer;
      transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.2s ease;
      position: relative;
    }
    .tuto-card:hover {
      border-color: rgba(45, 212, 160, 0.35);
      box-shadow: 0 0 0 1px rgba(45, 212, 160, 0.08), 0 8px 30px rgba(0,0,0,0.25);
      transform: translateY(-2px);
    }
    .tuto-card.active {
      border-color: var(--dash-mint);
      box-shadow: 0 0 0 2px rgba(45, 212, 160, 0.25);
    }
    .tuto-thumb {
      position: relative;
      width: 100%;
      aspect-ratio: 16 / 9;
      background: #1a1e24;
      overflow: hidden;
    }
    .tuto-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.3s ease;
    }
    .tuto-card:hover .tuto-thumb img {
      transform: scale(1.04);
    }
    .play-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,0.35);
      transition: background 0.25s ease;
    }
    .tuto-card:hover .play-overlay {
      background: rgba(0,0,0,0.2);
    }
    .play-overlay ion-icon {
      font-size: 52px;
      color: #fff;
      filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
      transition: transform 0.2s ease;
    }
    .tuto-card:hover .play-overlay ion-icon {
      transform: scale(1.08);
    }
    .tuto-body {
      padding: 14px 18px 18px;
    }
    .tuto-body h6 {
      font-size: 14px;
      font-weight: 600;
      color: var(--dash-text);
      margin: 0 0 4px;
    }
    .tuto-body small {
      font-size: 12px;
      color: var(--dash-text-muted);
      display: block;
      line-height: 1.45;
    }

    /* Modal video container */
    #videoModal .modal-content {
      background: var(--dash-card);
      border: 1px solid var(--dash-border);
      border-radius: 14px;
    }
    #videoModal .modal-header {
      border-bottom: 1px solid var(--dash-border);
      padding: 16px 22px;
    }
    #videoModal .modal-title {
      color: var(--dash-text);
      font-weight: 600;
      font-size: 16px;
    }
    #videoModal .close {
      color: var(--dash-text-muted);
      font-size: 24px;
      opacity: 0.7;
      transition: opacity 0.2s;
    }
    #videoModal .close:hover { opacity: 1; }
    #videoModal .modal-body {
      padding: 0;
      background: #000;
      border-radius: 0 0 14px 14px;
      overflow: hidden;
    }
    #videoModal .modal-body .video-wrap {
      position: relative;
      width: 100%;
      padding-bottom: 56.25%;
    }
    #videoModal .modal-body .video-wrap iframe {
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      border: 0;
    }
  </style>
</head>
<body class="nav-md">
  <div class="container body">
    <div class="main_container">
      <?php echo $menu ?>
      <?php echo $topnav ?>
      <div class="right_col">
        <div class="tuto-header">
          <h3>Tutoriales</h3>
          <p>Aprende a usar el sistema con estos videos guía</p>
        </div>

        <div class="tuto-grid" id="tutoGrid">
          <div class="tuto-card active" data-video="lvw7DftrvQ0" data-title="Nuevos productos">
            <div class="tuto-thumb">
              <img src="https://img.youtube.com/vi/lvw7DftrvQ0/maxresdefault.jpg" alt="Nuevos productos" loading="lazy">
              <div class="play-overlay"><ion-icon name="play-circle-outline"></ion-icon></div>
            </div>
            <div class="tuto-body">
              <h6>Nuevos productos</h6>
              <small>Cómo agregar y gestionar productos en el catálogo.</small>
            </div>
          </div>
          <div class="tuto-card" data-video="0G0YRhPiPnE" data-title="Tasas de cambio">
            <div class="tuto-thumb">
              <img src="https://img.youtube.com/vi/0G0YRhPiPnE/maxresdefault.jpg" alt="Tasas de cambio" loading="lazy">
              <div class="play-overlay"><ion-icon name="play-circle-outline"></ion-icon></div>
            </div>
            <div class="tuto-body">
              <h6>Tasas de cambio</h6>
              <small>Configuración y actualización de tasas de cambio diarias.</small>
            </div>
          </div>
          <div class="tuto-card" data-video="7dJqrOZTbuY" data-title="Registro de ventas">
            <div class="tuto-thumb">
              <img src="https://img.youtube.com/vi/7dJqrOZTbuY/maxresdefault.jpg" alt="Registro de ventas" loading="lazy">
              <div class="play-overlay"><ion-icon name="play-circle-outline"></ion-icon></div>
            </div>
            <div class="tuto-body">
              <h6>Registro de ventas</h6>
              <small>Cómo registrar y dar seguimiento a las ventas del día.</small>
            </div>
          </div>
          <div class="tuto-card" data-video="KQdipbZEEYw" data-title="Registro de compras">
            <div class="tuto-thumb">
              <img src="https://img.youtube.com/vi/KQdipbZEEYw/maxresdefault.jpg" alt="Registro de compras" loading="lazy">
              <div class="play-overlay"><ion-icon name="play-circle-outline"></ion-icon></div>
            </div>
            <div class="tuto-body">
              <h6>Registro de compras</h6>
              <small>Cómo registrar compras y llevar el control de inventario.</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="videoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="videoModalTitle"></h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="video-wrap">
            <iframe id="videoIframe" src="" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../vendors/jquery/dist/jquery.min.js"></script>
  <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../build/js/custom.js"></script>
  <script>
    const cards = document.querySelectorAll('.tuto-card');
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoIframe');
    const modalTitle = document.getElementById('videoModalTitle');

    cards.forEach(card => {
      card.addEventListener('click', function() {
        const videoId = this.dataset.video;
        const title = this.dataset.title;
        modalTitle.textContent = title;
        iframe.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1&rel=0';
        $('#videoModal').modal('show');
      });
    });

    $('#videoModal').on('hidden.bs.modal', function () {
      iframe.src = '';
    });
  </script>
</body>
</html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>
