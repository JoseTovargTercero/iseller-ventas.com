<?php
require_once('includes/requires.php');

$topnav = topnav();
$bss_id = $_SESSION['bss_id'];

$stmtT = $conexion->prepare("SELECT slug, tiene_tienda_virtual, nombre, nombre_visible FROM negocio WHERE id = ?");
$stmtT->bind_param("i", $bss_id);
$stmtT->execute();
$tienda = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

if (!$tienda || !$tienda['tiene_tienda_virtual']) {
    header("Location: tienda_onboarding.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Mis transportistas — Tienda Virtual</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .transp-page {
            padding: 24px;
            max-width: 1100px;
        }

        .transp-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }

        .transp-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .transp-header h2 i {
            color: var(--dash-mint);
        }

        .transp-header p {
            margin: 6px 0 0;
            font-size: 0.82rem;
            color: var(--dash-text-muted);
        }

        .transp-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 22px;
            margin-bottom: 20px;
        }

        .transp-card h3 {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dash-text);
            margin: 0 0 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .transp-card h3 i {
            color: var(--dash-mint);
        }

        .transp-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
        }

        .transp-card-head h3 {
            margin: 0;
        }

        .transp-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .transp-input {
            flex: 1;
            min-width: 220px;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.88rem;
        }

        .transp-input:focus {
            outline: none;
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12);
        }

        .transp-input::placeholder {
            color: var(--dash-text-muted);
            opacity: 0.7;
        }

        .transp-hint {
            margin: 10px 0 0;
            font-size: 0.75rem;
            color: var(--dash-text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .transp-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .transp-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            background: var(--dash-bg);
        }

        .transp-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(45, 212, 160, 0.12);
            color: var(--dash-mint);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        .transp-info {
            flex: 1;
            min-width: 0;
        }

        .transp-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--dash-text);
            overflow-wrap: anywhere;
        }

        .transp-meta {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 3px;
            font-size: 0.76rem;
            color: var(--dash-text-muted);
        }

        .transp-meta span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .transp-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .transp-badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .transp-badge.ok {
            background: rgba(45, 212, 160, 0.1);
            color: var(--dash-mint);
        }

        .transp-badge.off {
            background: rgba(239, 90, 111, 0.1);
            color: var(--dash-danger);
        }

        .transp-empty {
            text-align: center;
            padding: 38px 20px;
            color: var(--dash-text-muted);
            font-size: 0.85rem;
        }

        .transp-empty i {
            display: block;
            font-size: 2rem;
            opacity: 0.4;
            margin-bottom: 10px;
        }

        .transp-empty a {
            color: var(--dash-mint);
            text-decoration: none;
            font-weight: 600;
        }

        .transp-filtros .transp-input {
            min-width: 150px;
        }

        .transp-select {
            flex: 0 1 170px;
            cursor: pointer;
        }

        .transp-date {
            flex: 0 1 190px;
        }

        .transp-table-wrap {
            overflow-x: auto;
        }

        .transp-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }

        .transp-table th {
            text-align: left;
            padding: 0 12px 10px;
            color: var(--dash-text-muted);
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            border-bottom: 1px solid var(--dash-border);
            white-space: nowrap;
        }

        .transp-table td {
            padding: 12px;
            border-bottom: 1px solid var(--dash-border);
            color: var(--dash-text);
            vertical-align: middle;
        }

        .transp-table tr:last-child td {
            border-bottom: none;
        }

        .transp-table .num {
            text-align: right;
            white-space: nowrap;
        }

        .transp-table .nombre {
            font-weight: 600;
        }

        .transp-table .sub {
            display: block;
            margin-top: 2px;
            font-size: 0.72rem;
            font-weight: 400;
            color: var(--dash-text-muted);
        }

        .transp-eval {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .transp-eval small {
            font-size: 0.7rem;
            color: var(--dash-text-muted);
        }

        .transp-estrellas {
            color: #f5b942;
            font-size: 0.85rem;
            letter-spacing: 1px;
            white-space: nowrap;
        }

        .transp-estrellas .off {
            color: var(--dash-border);
        }

        @media (max-width: 640px) {
            .transp-page {
                padding: 16px;
            }

            .transp-item {
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .transp-badge {
                order: 3;
            }

            .transp-remove span {
                display: none;
            }
        }
    </style>
</head>

<body class="nav-md">
    <div class="container body">
        <div class="main_container">
            <?php echo $menu ?>
            <?php echo $topnav ?>

            <div class="right_col" role="main">
                <div class="transp-page">
                    <div class="transp-header">
                        <div>
                            <h2><i class="bi bi-person-badge"></i> Mis transportistas</h2>
                            <p>Agrega por cédula a los transportistas registrados en la app. Solo ellos aparecerán al asignar un delivery.</p>
                        </div>
                        <a href="tienda_pedidos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a pedidos
                        </a>
                    </div>

                    <div class="transp-card">
                        <h3><i class="bi bi-plus-circle"></i> Agregar transportista</h3>
                        <form id="formTransportista" class="transp-form" autocomplete="off">
                            <input type="text" id="cedula" class="transp-input" placeholder="Cédula del transportista (ej: 12345678)" inputmode="numeric" maxlength="12" required>
                            <button type="submit" class="btn btn-success" id="btnAgregar">
                                <i class="bi bi-search"></i> Buscar y agregar
                            </button>
                        </form>
                        <p class="transp-hint">
                            <i class="bi bi-info-circle"></i>
                            El transportista debe estar registrado en la app con esa cédula.
                        </p>
                    </div>

                    <div class="transp-card">
                        <div class="transp-card-head">
                            <h3><i class="bi bi-graph-up-arrow"></i> Rendimiento de transportistas</h3>
                        </div>
                        <div class="transp-form transp-filtros">
                            <select id="filtroPeriodo" class="transp-input transp-select" onchange="cargarRendimiento()">
                                <option value="dia">Día</option>
                                <option value="semana">Semana</option>
                                <option value="mes">Mes</option>
                            </select>
                            <input type="date" id="filtroFecha" class="transp-input transp-date" value="<?php echo date('Y-m-d'); ?>" onchange="cargarRendimiento()">
                            <button type="button" class="btn btn-success" onclick="cargarRendimiento()">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                        </div>
                        <p class="transp-hint" id="rendimientoRango">
                            <i class="bi bi-info-circle"></i>
                            Elige día, semana o mes y la fecha a consultar.
                        </p>
                        <div class="transp-table-wrap">
                            <table class="transp-table">
                                <thead>
                                    <tr>
                                        <th>Transportista</th>
                                        <th class="num">Pedidos entregados</th>
                                        <th class="num">Tiempo medio de entrega</th>
                                        <th>Evaluación <span style="text-transform:none;font-weight:400;">(últimas 30)</span></th>
                                    </tr>
                                </thead>
                                <tbody id="tablaRendimiento">
                                    <tr>
                                        <td colspan="4" class="transp-empty">Cargando...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="transp-hint">
                            <i class="bi bi-clock-history"></i>
                            El tiempo se mide desde que el pedido pasa a “Enviado” hasta que pasa a “Entregado”.
                            Las estrellas evalúan el tiempo medio de las últimas 30 entregas.
                        </p>
                    </div>

                    <div class="transp-card">
                        <div class="transp-card-head">
                            <h3><i class="bi bi-truck"></i> Transportistas de mi tienda</h3>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cargarTransportistas()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                        <div id="listaTransportistas" class="transp-list">
                            <div class="transp-empty"><i class="bi bi-hourglass-split"></i> Cargando...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        var API_TRANSPORTISTAS = 'db/tienda_transportistas_api.php';

        function escaparHtml(texto) {
            return String(texto == null ? '' : texto).replace(/[&<>"']/g, function(c) {
                return {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                } [c];
            });
        }

        function cargarTransportistas() {
            var cont = document.getElementById('listaTransportistas');
            fetch(API_TRANSPORTISTAS + '?action=listar')
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) throw new Error(data.msg || 'Error al cargar');
                    var filas = data.data || [];
                    if (!filas.length) {
                        cont.innerHTML = '<div class="transp-empty">' +
                            '<i class="bi bi-person-plus"></i>' +
                            'Aún no tienes transportistas agregados.<br>' +
                            'Pide al transportista que se registre en la app y agrega su cédula arriba.' +
                            '</div>';
                        return;
                    }
                    cont.innerHTML = filas.map(function(t) {
                        var disponible = parseInt(t.activo) === 1 && parseInt(t.deliverys) === 1;
                        return '<div class="transp-item">' +
                            '<div class="transp-avatar"><i class="bi bi-person-badge"></i></div>' +
                            '<div class="transp-info">' +
                            '<div class="transp-name">' + escaparHtml(t.nombre) + '</div>' +
                            '<div class="transp-meta">' +
                            '<span><i class="bi bi-person-vcard"></i> ' + escaparHtml(t.cedula) + '</span>' +
                            '<span><i class="bi bi-telephone"></i> ' + escaparHtml(t.telefono || '—') + '</span>' +
                            '</div></div>' +
                            '<span class="transp-badge ' + (disponible ? 'ok' : 'off') + '">' +
                            (disponible ? 'Disponible' : 'No disponible') +
                            '</span>' +
                            '<button type="button" class="btn btn-outline-danger btn-sm transp-remove" onclick="quitarTransportista(' + parseInt(t.id) + ')" title="Quitar transportista">' +
                            '<i class="bi bi-trash"></i><span> Quitar</span>' +
                            '</button>' +
                            '</div>';
                    }).join('');
                })
                .catch(function(err) {
                    cont.innerHTML = '<div class="transp-empty"><i class="bi bi-exclamation-triangle"></i> ' + escaparHtml(err.message) + '</div>';
                });
        }

        function formatoMinutos(minutos) {
            if (minutos === null || minutos === undefined || isNaN(minutos)) return '—';
            var total = Math.round(Number(minutos));
            if (total < 60) return total + ' min';
            var horas = Math.floor(total / 60);
            var resto = total % 60;
            return horas + ' h' + (resto ? ' ' + resto + ' min' : '');
        }

        function formatoFecha(fecha) {
            if (!fecha) return '';
            var partes = String(fecha).split('-');
            return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : fecha;
        }

        function estrellasHtml(cantidad) {
            var html = '';
            for (var i = 1; i <= 5; i++) {
                html += '<span class="' + (i <= cantidad ? '' : 'off') + '">★</span>';
            }
            return html;
        }

        function evaluacionHtml(t) {
            if (!t.estrellas) {
                return '<span class="transp-eval"><small>Sin datos suficientes</small></span>';
            }
            var detalle = (t.ultimas_n >= 30 ? 'Últimas 30' : 'Últimas ' + t.ultimas_n) +
                ' entregas · ' + formatoMinutos(t.ultimas_min) + ' media';
            return '<span class="transp-eval">' +
                '<span class="transp-estrellas">' + estrellasHtml(t.estrellas) + ' ' + escaparHtml(t.etiqueta) + '</span>' +
                '<small>' + escaparHtml(detalle) + '</small>' +
                '</span>';
        }

        function cargarRendimiento() {
            var tbody = document.getElementById('tablaRendimiento');
            var rango = document.getElementById('rendimientoRango');
            var periodo = document.getElementById('filtroPeriodo').value;
            var fecha = document.getElementById('filtroFecha').value;

            tbody.innerHTML = '<tr><td colspan="4" class="transp-empty">Cargando...</td></tr>';

            fetch(API_TRANSPORTISTAS + '?action=rendimiento&periodo=' + encodeURIComponent(periodo) + '&fecha=' + encodeURIComponent(fecha))
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) throw new Error(data.msg || 'Error al cargar');
                    var filas = data.data || [];

                    var total = 0;
                    var suma = 0;
                    filas.forEach(function(t) {
                        if (t.promedio_min !== null && t.entregas > 0) {
                            total += t.entregas;
                            suma += t.promedio_min * t.entregas;
                        }
                    });
                    var media = total ? (suma / total) : null;
                    var etiquetaRango = data.desde === data.hasta
                        ? 'Día ' + formatoFecha(data.desde)
                        : 'Del ' + formatoFecha(data.desde) + ' al ' + formatoFecha(data.hasta);
                    rango.innerHTML = '<i class="bi bi-calendar3"></i> ' + escaparHtml(etiquetaRango) +
                        ' · ' + total + ' entrega' + (total === 1 ? '' : 's') +
                        (media !== null ? ' · media ' + formatoMinutos(media) : '');

                    if (!filas.length) {
                        tbody.innerHTML = '<tr><td colspan="4" class="transp-empty">Agrega un transportista para ver su rendimiento.</td></tr>';
                        return;
                    }

                    tbody.innerHTML = filas.map(function(t) {
                        return '<tr>' +
                            '<td><span class="nombre">' + escaparHtml(t.nombre) + '</span>' +
                            '<span class="sub">' + escaparHtml(t.cedula) + '</span></td>' +
                            '<td class="num">' + parseInt(t.entregas) + '</td>' +
                            '<td class="num">' + formatoMinutos(t.promedio_min) + '</td>' +
                            '<td>' + evaluacionHtml(t) + '</td>' +
                            '</tr>';
                    }).join('');
                })
                .catch(function(err) {
                    rango.innerHTML = '<i class="bi bi-exclamation-triangle"></i> ' + escaparHtml(err.message);
                    tbody.innerHTML = '<tr><td colspan="4" class="transp-empty">' + escaparHtml(err.message) + '</td></tr>';
                });
        }

        function aviso(icono, titulo, texto) {
            if (typeof Swal === 'undefined') {
                alert(titulo + ': ' + texto);
                return;
            }
            Swal.fire({
                icon: icono,
                title: titulo,
                text: texto,
                background: '#181b22',
                color: '#e8edf2',
                confirmButtonColor: '#2dd4a0'
            });
        }

        document.getElementById('formTransportista').addEventListener('submit', function(e) {
            e.preventDefault();
            var cedula = document.getElementById('cedula').value.trim();
            if (cedula === '') return;

            var boton = document.getElementById('btnAgregar');
            boton.disabled = true;

            var fd = new FormData();
            fd.append('action', 'agregar');
            fd.append('cedula', cedula);

            fetch(API_TRANSPORTISTAS, {
                    method: 'POST',
                    body: fd
                })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    boton.disabled = false;
                    if (data.ok) {
                        document.getElementById('cedula').value = '';
                        aviso('success', 'Transportista agregado', data.msg);
                        cargarTransportistas();
                        cargarRendimiento();
                    } else {
                        aviso('error', 'No se pudo agregar', data.msg || 'Intenta de nuevo.');
                    }
                })
                .catch(function() {
                    boton.disabled = false;
                    aviso('error', 'Error de conexión', 'No se pudo conectar con el servidor.');
                });
        });

        function quitarTransportista(id) {
            var confirmar = function() {
                var fd = new FormData();
                fd.append('action', 'quitar');
                fd.append('transportista_id', id);
                fetch(API_TRANSPORTISTAS, {
                        method: 'POST',
                        body: fd
                    })
                    .then(function(res) {
                        return res.json();
                    })
                    .then(function(data) {
                        if (data.ok) {
                            cargarTransportistas();
                            cargarRendimiento();
                        } else {
                            aviso('error', 'No se pudo quitar', data.msg || 'Intenta de nuevo.');
                        }
                    })
                    .catch(function() {
                        aviso('error', 'Error de conexión', 'No se pudo conectar con el servidor.');
                    });
            };

            if (typeof Swal === 'undefined') {
                if (confirm('¿Quitar este transportista de tu tienda?')) confirmar();
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: '¿Quitar transportista?',
                text: 'Dejará de aparecer en la asignación de deliveries.',
                showCancelButton: true,
                confirmButtonText: 'Sí, quitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef5a6f',
                background: '#181b22',
                color: '#e8edf2'
            }).then(function(res) {
                if (res.isConfirmed) confirmar();
            });
        }

        cargarTransportistas();
        cargarRendimiento();
    </script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>

</html>
