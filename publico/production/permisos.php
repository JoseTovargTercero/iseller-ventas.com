<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1) {
    $topnav = topnav();



    // Asegurar existencia del permiso 'Venta de productos al mayor'
    $stmtEnsure = $conexion->prepare("SELECT id FROM menu WHERE nombre = 'Venta de productos al mayor' LIMIT 1");
    if ($stmtEnsure) {
        $stmtEnsure->execute();
        $resEnsure = $stmtEnsure->get_result();
        if ($resEnsure && $resEnsure->num_rows === 0) {
            $catIns = 'ventas';
            $nomIns = 'Venta de productos al mayor';
            $dirIns = '';
            $icoIns = 'bag-add-outline';
            $admIns = 0;
            $insP = $conexion->prepare("INSERT INTO menu (categoria, nombre, dir, icono, admin) VALUES (?, ?, ?, ?, ?)");
            if ($insP) {
                $insP->bind_param("ssssi", $catIns, $nomIns, $dirIns, $icoIns, $admIns);
                $insP->execute();
                $insP->close();
            }
        }
        $stmtEnsure->close();
    }




?>
    <!DOCTYPE html>
    <html lang='es'>

    <head>
        <title>Permisos de usuarios </title>
        <?php require_once('includes/headers.php'); ?>
        <style>
            .avtar.avtar-xs {
                width: 28px;
                height: 28px;
                font-size: 12px;
                border-radius: 6px;
                cursor: pointer;
                transition: all .15s;
                flex-shrink: 0;
            }

            .avtar.avtar-xs:hover {
                opacity: .8;
                transform: scale(1.08);
            }

            .avtar {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                font-size: 18px;
                font-weight: 600;
                width: 48px;
                height: 48px;
            }

            td>a {
                color: gray !important;
            }

            /* ── Modal de permisos ── */
            .perm-modal-inner {
                padding: 18px 20px;
            }

            .perm-modal-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
            }

            .perm-modal-title {
                font-size: 16px;
                font-weight: 700;
                color: var(--dash-text);
                margin: 0 0 4px;
            }

            .perm-modal-sub {
                font-size: 12px;
                color: var(--dash-text-muted);
                margin: 0 0 18px;
            }

            .perm-modal-close {
                flex-shrink: 0;
                width: 34px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border-radius: 8px;
                border: 1px solid var(--dash-border);
                background: transparent;
                color: var(--dash-text-muted);
                font-size: 1.1rem;
                cursor: pointer;
                transition: all .15s;
            }

            .perm-modal-close:hover {
                border-color: #ef5a6f;
                color: #ef5a6f;
            }

            .perm-category {
                margin-bottom: 16px;
            }

            .perm-category-label {
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .6px;
                color: var(--dash-text-muted);
                margin-bottom: 8px;
                padding-bottom: 4px;
                border-bottom: 1px solid var(--dash-border);
            }

            .perm-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }

            /* Cada permiso: se pulsa en toda la tarjeta, no sólo en el icono */
            .perm-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 9px 11px;
                border: 1px solid var(--dash-border);
                border-radius: 10px;
                background: rgba(255, 255, 255, .02);
                cursor: pointer;
                transition: border-color .15s, background .15s;
                -webkit-tap-highlight-color: transparent;
            }

            .perm-item:hover {
                border-color: rgba(45, 212, 160, .35);
                background: rgba(45, 212, 160, .04);
            }

            .perm-item.is-active {
                border-color: rgba(45, 212, 160, .45);
                background: rgba(45, 212, 160, .1);
            }

            .perm-item-name {
                font-size: 12.5px;
                font-weight: 500;
                color: var(--dash-text);
                flex: 1;
                line-height: 1.3;
            }

            .perm-item.is-active .perm-item-name {
                color: var(--dash-mint);
            }

            /* Estado del permiso: check en verde / cruz en rojo */
            .perm-item .avtar.avtar-xs {
                width: 26px;
                height: 26px;
                border-radius: 8px;
                font-size: 14px;
                background: rgba(239, 90, 111, .12);
                border: 1px solid rgba(239, 90, 111, .25);
                color: #ef5a6f;
            }

            .perm-item.is-active .avtar.avtar-xs {
                background: rgba(45, 212, 160, .14);
                border-color: rgba(45, 212, 160, .35);
                color: var(--dash-mint);
            }

            /* ── Móvil (WebView de la app) ───────────────────────────────── */
            @media (max-width: 768px) {

                /* La tabla pasa a una tarjeta por usuario: los accesos y el
                   botón Modificar empujaban la página fuera de la pantalla. */
                #table {
                    display: block;
                }

                #table thead {
                    display: none;
                }

                #table tbody {
                    display: block;
                }

                #table tr {
                    display: grid;
                    grid-template-columns: 26px 1fr;
                    gap: 6px 10px;
                    padding: 12px;
                    margin-bottom: 10px;
                    border: 1px solid var(--dash-border);
                    border-radius: 12px;
                    background: rgba(255, 255, 255, .02);
                }

                #table td {
                    display: block;
                    padding: 0 !important;
                    border: none !important;
                    background: transparent !important;
                }

                /* Icono + nombre en la primera línea, la fecha debajo */
                #table td:nth-child(1) {
                    grid-column: 1;
                    color: var(--dash-text-muted);
                    font-size: 1.05rem;
                }

                #table td:nth-child(2) {
                    grid-column: 2;
                    font-weight: 600;
                    color: var(--dash-text);
                    font-size: .9rem;
                }

                #table td:nth-child(3) {
                    grid-column: 2;
                    font-size: .72rem;
                    color: var(--dash-text-muted);
                }

                /* Accesos del usuario */
                #table td:nth-child(4) {
                    grid-column: 1 / -1;
                    margin-top: 4px;
                }

                #table td:nth-child(4) .badge {
                    font-size: .68rem;
                    margin-bottom: 4px;
                }

                /* "Modificar" a lo ancho */
                #table td:nth-child(5) {
                    grid-column: 1 / -1;
                    margin-top: 6px;
                }

                #table td:nth-child(5) .bt {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 100%;
                    padding: 10px;
                    border-radius: 8px;
                    font-size: .85rem;
                }

                /* El modal de permisos: una opción por fila y espacio de dedo */
                .perm-modal-inner {
                    padding: 16px 14px 26px;
                }

                .perm-modal-title {
                    font-size: 17px;
                }

                .perm-grid {
                    grid-template-columns: 1fr;
                    gap: 8px;
                }

                .perm-item {
                    min-height: 46px;
                    padding: 12px;
                }

                .perm-item .avtar.avtar-xs {
                    width: 28px;
                    height: 28px;
                }

                .perm-item-name {
                    font-size: 13.5px;
                }
            }

            /* En tablet, el modal al 50% aprieta las dos columnas de permisos */
            @media (min-width: 564px) and (max-width: 991px) {
                #w-modal {
                    width: 72% !important;
                }
            }
        </style>
    </head>

    <body class='nav-md'>
        <div class='container body'>
            <div class='main_container'>

                <?php echo $menu ?>

                <!-- top navigation -->
                <?php echo $topnav ?>
                <!-- /top navigation -->
                <!-- page content -->
                <div class='right_col' role='main'>


                    <div class='row'>

                        <div class='col-md-12 col-sm-12'>
                            <div class='x_panel'>
                                <div class='x_title'>
                                    <h2>Permisos</h2>

                                    <div class='clearfix'></div>
                                </div>
                                <div class='x_content altoScroll'>
                                    <table id="table" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th class="w-5"></th>
                                                <th class="w-20">Usuario</th>
                                                <th class="w-10">Fecha de creación</th>
                                                <th class="w-50">Acceso</th>
                                                <th class="w-10"></th>
                                            </tr>
                                        </thead>
                                        <tbody>

                                        </tbody>

                                    </table>
                                </div>
                            </div>
                        </div>


                    </div>

                </div>


            </div>
        </div>

        <?php require('../assets/templates/modal.html'); ?>
        <!-- jQuery -->
        <script src='../vendors/jquery/dist/jquery.min.js'></script>
        <!-- Bootstrap -->
        <script src='../vendors/bootstrap/dist/js/bootstrap.bundle.min.js'></script>
        <!-- FastClick -->
        <script src='../vendors/fastclick/lib/fastclick.js'></script>
        <!-- NProgress -->
        <script src='../vendors/nprogress/nprogress.js'></script>
        <script src='../build/js/custom.js'></script>
        <script src="../build/js/modal.js"></script>
        <script src="js/nombre_pagina.js"></script>

        <script>
            // ── Inicializar modal con nuevo diseño ──
            $(document).ready(function() {
                const modal_body = document.getElementById('modal-body');
                modal_body.style.overflow = 'auto';
                modal_body.innerHTML = `
                    <div class="perm-modal-inner">
                        <div class="perm-modal-head">
                            <div>
                                <p class="perm-modal-title">Permisos de acceso</p>
                                <p class="perm-modal-sub" id="perm-modal-sub">Selecciona los módulos a los que este usuario tendrá acceso.</p>
                            </div>
                            <button type="button" class="perm-modal-close" aria-label="Cerrar" onclick="cerrarPermisos()">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <div id="perm-categories-container"></div>
                    </div>`;

                document.getElementById('modal').style.height = '100%';
                const modal = document.getElementById('w-modal');
                modal.classList.add('w-50', 'h-60');
                modal.style.padding = '0px';
            });

            const url_back = '../../configurar/glob_users_access_back.php';

            function cargarTabla() {
                $.ajax({
                    url: url_back,
                    type: 'POST',
                    data: {
                        tabla: true
                    },
                    cache: false,
                    success: function(response) {
                        $('#table tbody').html('');
                        if (!response) return;
                        var data = JSON.parse(response);
                        for (var i = 0; i < data.length; i++) {
                            let u_id = data[i].u_id;
                            let u_nombre = data[i].u_nombre;
                            let creado = data[i].creado;
                            let permisos = data[i].permisos;
                            let html_permisos = '';
                            if (permisos) {
                                permisos.forEach(el => {
                                    html_permisos += `<span title="${el[2]}" class="badge bg-info me-1">${el[1]}</span>`;
                                });
                            }
                            $('#table tbody').append(`<tr>
                                <td><i class="bx bx-user"></i></td>
                                <td>${u_nombre}</td>
                                <td>${creado}</td>
                                <td>${html_permisos}</td>
                                <td><a class="bt nowrap btn-sm btn-info text-white" onclick="modificar(${u_id})"><i class="bx bx-edit-alt me-2"></i> Modificar</a></td>
                            </tr>`);
                        }
                    }
                });
            }
            cargarTabla();

            let mdf;



            function permisosDisponibles(user) {
                mdf = user;
                $('#cargando').show();

                $.ajax({
                    url: url_back,
                    type: 'POST',
                    data: {
                        permisos: true,
                        user: user
                    },
                    cache: false,
                    success: function(response) {
                        const container = document.getElementById('perm-categories-container');
                        container.innerHTML = '';
                        if (!response) {
                            $('#cargando').hide();
                            return;
                        }

                        const data = JSON.parse(response);

                        // Agrupar por categoría
                        const groups = {};
                        data.forEach(item => {
                            const cat = item.categoria || 'General';
                            if (!groups[cat]) groups[cat] = [];
                            groups[cat].push(item);
                        });

                        // Renderizar cada grupo
                        Object.entries(groups).forEach(([cat, items]) => {
                            const section = document.createElement('div');
                            section.className = 'perm-category';

                            const label = document.createElement('div');
                            label.className = 'perm-category-label';
                            label.textContent = cat;
                            section.appendChild(label);

                            const grid = document.createElement('div');
                            grid.className = 'perm-grid';

                            items.forEach(item => {
                                const active = !!item.permisos;
                                const card = document.createElement('div');
                                card.className = 'perm-item' + (active ? ' is-active' : '');
                                card.setAttribute('role', 'button');
                                card.setAttribute('tabindex', '0');
                                card.setAttribute('aria-pressed', active ? 'true' : 'false');
                                card.innerHTML = `
                                    <span class="avtar avtar-xs">
                                        <i class="bx ${active ? 'bx-check' : 'bx-x'} f-20"></i>
                                    </span>
                                    <span class="perm-item-name">${item.nombre}</span>`;

                                // Toda la tarjeta alterna el permiso
                                const alternar = () => setPermiso(String(user), String(item.id), active);
                                card.addEventListener('click', alternar);
                                card.addEventListener('keydown', (e) => {
                                    if (e.key === 'Enter' || e.key === ' ') {
                                        e.preventDefault();
                                        alternar();
                                    }
                                });

                                grid.appendChild(card);
                            });

                            section.appendChild(grid);
                            container.appendChild(section);
                        });

                        $('#cargando').hide();
                    }
                });
            }


            function setPermiso(user, permiso, status) {
                $('#cargando').show()


                $.ajax({
                    url: url_back,
                    type: 'POST',
                    data: {
                        set_permisos: true,
                        user: user,
                        permiso: permiso,
                        status: status
                    },
                    cache: false,
                    success: function(response) {
                        let respuesta = JSON.parse(response)

                        if (respuesta.success) {
                            Alerta.toast('success', respuesta.success)
                            permisosDisponibles(user)
                            cargarTabla()
                        } else {
                            Alerta.toast('error', respuesta.success)
                        }
                        $('#cargando').hide()
                    }
                });

            }


            function modificar(user) {
                permisosDisponibles(user)
                modalContainer.classList.add("active");
            }

            // El modal reemplaza su contenido, así que su botón de cerrar original
            // se pierde: este lo cierra sin depender de modal.js
            function cerrarPermisos() {
                document.getElementById('modal-container').classList.remove('active');
            }




            /**
             * Deletes a record with the specified ID.
             * @param {number} id - The ID of the record to be deleted.
             * @returns {boolean} - Returns true if the record is deleted successfully, false otherwise.
             */
            function eliminar(id) {
                Swal.fire({
                    title: "¿Estás seguro?",
                    text: "¡No podrás revertir esto!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#04a9f5",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Sí, eliminarlo!",
                    cancelButtonText: "Cancelar",
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: url_back,
                            type: "POST",
                            data: {
                                eliminar: true,
                                id: id,
                            },
                            success: function(response) {
                                if (response.trim() == "ok") {
                                    cargarTabla();

                                    Alerta.toast("success", "Eliminado con éxito");
                                } else {
                                    Alerta.toast("error", response);
                                }
                            },
                        });
                    }
                });
            }
        </script>


    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>