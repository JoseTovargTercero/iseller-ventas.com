<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {

    $topnav = topnav();
?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Seleccionar sucursal — iSeller</title>
        <?php require_once('includes/headers.php'); ?>
        <link rel="stylesheet" href="theme.css">
        <style>
            .right_col {
                background: var(--dash-bg);
                min-height: 100vh;
                padding: 24px 28px !important;
            }

            .page-head {
                margin-bottom: 28px;
            }

            .page-head h3 {
                font-size: 20px;
                font-weight: 700;
                color: var(--dash-text);
                margin: 0;
                letter-spacing: -.3px;
            }

            .page-head p {
                color: var(--dash-text-muted);
                margin: 3px 0 0;
                font-size: 13px;
            }


            .dash-panel .panel-header {
                padding: 20px 24px 16px;
                border-bottom: 1px solid var(--dash-border);
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .dash-panel .panel-header h5 {
                font-size: 14px;
                font-weight: 600;
                color: var(--dash-text);
                margin: 0;
            }

            .dash-panel .panel-header h5 ion-icon {
                color: var(--dash-mint);
                margin-right: 8px;
                font-size: 17px;
                vertical-align: middle;
            }

            .dash-panel .panel-body {
                padding: 8px 0;
            }

            .branch-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 12px;
                padding: 18px 20px 20px;
            }

            .branch-card {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 16px 18px;
                border: 1px solid var(--dash-border);
                border-radius: 12px;
                background: rgba(255, 255, 255, .02);
                cursor: pointer;
                transition: all .2s ease;
                position: relative;
                overflow: hidden;
            }

            .branch-card::before {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: 12px;
                opacity: 0;
                background: linear-gradient(135deg, rgba(45, 212, 160, .06), transparent);
                transition: opacity .25s ease;
                pointer-events: none;
            }

            .branch-card:hover {
                border-color: rgba(45, 212, 160, .25);
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, .2);
            }

            .branch-card:hover::before {
                opacity: 1;
            }

            .branch-card:active {
                transform: translateY(0);
                box-shadow: none;
            }

            .branch-card.active-item {
                border-color: var(--dash-mint);
                background: rgba(45, 212, 160, .06);
                box-shadow: 0 0 0 1px var(--dash-mint), 0 4px 14px rgba(45, 212, 160, .12);
            }

            .branch-card.active-item::before {
                opacity: 1;
            }

            .branch-icon {
                width: 42px;
                height: 42px;
                border-radius: 10px;
                background: rgba(45, 212, 160, .1);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                transition: all .2s ease;
            }

            .branch-card:hover .branch-icon {
                background: rgba(45, 212, 160, .18);
            }

            .branch-card.active-item .branch-icon {
                background: rgba(45, 212, 160, .2);
            }

            .branch-icon ion-icon {
                font-size: 20px;
                color: var(--dash-mint);
            }

            .branch-info {
                flex: 1;
                min-width: 0;
            }

            .branch-name {
                font-size: 14px;
                font-weight: 600;
                color: var(--dash-text);
                display: block;
                line-height: 1.3;
            }

            .branch-hint {
                font-size: 11px;
                color: var(--dash-text-muted);
                margin-top: 1px;
            }

            .branch-check {
                width: 24px;
                height: 24px;
                border-radius: 50%;
                border: 2px solid var(--dash-border);
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                transition: all .25s ease;
            }

            .branch-card.active-item .branch-check {
                border-color: var(--dash-mint);
                background: var(--dash-mint);
            }

            .branch-check ion-icon {
                font-size: 14px;
                color: #fff;
                opacity: 0;
                transform: scale(.5);
                transition: all .2s ease;
            }

            .branch-card.active-item .branch-check ion-icon {
                opacity: 1;
                transform: scale(1);
            }

            .empty-state {
                text-align: center;
                padding: 50px 20px;
                color: var(--dash-text-muted);
            }

            .empty-state ion-icon {
                font-size: 40px;
                margin-bottom: 12px;
                opacity: .4;
            }

            .empty-state h6 {
                color: var(--dash-text-muted);
                font-weight: 500;
            }
        </style>
    </head>

    <body class='nav-md'>
        <div class="contenedor-loader" id="cargando"><span class="loader"></span></div>

        <div class='container body'>
            <div class='main_container'>
                <?php echo $menu ?>
                <?php echo $topnav ?>

                <div class="right_col" role='main'>


                    <div class="row">
                        <div class="col-lg-10 col-xl-8 m-auto">
                            <div class="dash-panel">
                                <div class="panel-header">
                                    <h5><ion-icon name="storefront-outline"></ion-icon>Sucursales disponibles</h5>
                                    <span id="branch-count" style="font-size:12px;color:var(--dash-text-muted);"></span>
                                </div>
                                <div class="panel-body">
                                    <div class="branch-grid" id="sucursales"></div>
                                    <div class="empty-state" id="empty-state" style="display:none;">
                                        <ion-icon name="storefront-outline"></ion-icon>
                                        <h6>No hay sucursales disponibles</h6>
                                        <p style="font-size:12px;margin:4px 0 0;">Crea una sucursal para comenzar.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="js/tablas.js"></script>
        <script src="../build/js/global-loader.js"></script>
        <script src="js/nombre_pagina.js"></script>

        <script>
            function cargarSucursales() {
                const loader = new TablaLoader('../../configurar/DatabaseHandler/_DBH-select.php');
                loader.cargar('sucursales', '_sucursales').then(data => {
                    if (!Array.isArray(data) || data.length === 0) {
                        window.location.href = 'sucursales.php';
                        return;
                    }

                    const grid = document.getElementById('sucursales');
                    grid.innerHTML = '';
                    document.getElementById('branch-count').textContent = `${data.length} sucursal${data.length !== 1 ? 'es' : ''}`;

                    data.forEach(s => {
                        const card = document.createElement('div');
                        card.className = 'branch-card';
                        card.id = `s-${s.id}`;
                        card.innerHTML = `
                <div class="branch-icon"><ion-icon name="storefront-outline"></ion-icon></div>
                <div class="branch-info">
                    <span class="branch-name">${s.nombre}</span>
                    <span class="branch-hint">Entrar a esta sucursal</span>
                </div>
                <div class="branch-check"><ion-icon name="checkmark-outline"></ion-icon></div>
            `;
                        card.addEventListener('click', () => seleccionar(s.id));
                        grid.appendChild(card);
                    });

                    getSucursalActivada();
                    document.getElementById('cargando').style.display = 'none';
                });
            }

            function seleccionar(id) {
                fetch('../../configurar/seleccion_sucursal.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `sucursal=${encodeURIComponent(id)}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            location.href = 'ventas.php';
                        } else {
                            Swal.fire('Error', data.error || 'No se pudo seleccionar la sucursal', 'error');
                        }
                    })
                    .catch(() => Swal.fire('Error', 'Error de conexión', 'error'));
            }

            async function getSucursalActivada() {
                try {
                    const res = await fetch('../../configurar/seleccion_sucursal_activada.php');
                    if (res.status === 204) return;
                    const {
                        ok,
                        sucursal
                    } = await res.json();
                    if (ok && sucursal) {
                        document.querySelectorAll('.branch-card.active-item').forEach(el => el.classList.remove('active-item'));
                        const el = document.getElementById(`s-${sucursal}`);
                        if (el) el.classList.add('active-item');
                    }
                } catch (err) {
                    console.error(err);
                }
            }

            cargarSucursales();
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>