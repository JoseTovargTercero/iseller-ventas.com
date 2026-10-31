<?php
require_once('includes/requires.php');

$topnav = topnav();
$bss_id = $_SESSION['bss_id'];

$stmtT = $conexion->prepare("SELECT slug, tiene_tienda_virtual FROM negocio WHERE id = ?");
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
    <title>Seguimiento de deliverys — Tienda Virtual</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <link rel="stylesheet" href="../vendors/leaflet/leaflet.css">
    <style>
        .seg-page {
            padding: 24px;
        }

        .seg-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .seg-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .seg-header h2 i {
            color: var(--dash-mint);
        }

        .seg-header p {
            margin: 6px 0 0;
            font-size: 0.82rem;
            color: var(--dash-text-muted);
        }

        .seg-toolbar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 18px;
        }

        .seg-input {
            flex: 1;
            min-width: 190px;
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.86rem;
        }

        .seg-input:focus {
            outline: none;
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 3px rgba(45, 212, 160, 0.12);
        }

        .seg-select {
            flex: 0 1 190px;
            cursor: pointer;
        }

        .seg-layout {
            display: grid;
            grid-template-columns: 370px minmax(0, 1fr);
            gap: 18px;
            align-items: start;
        }

        .seg-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 14px;
        }

        .seg-list-card {
            overflow: hidden;
        }

        .seg-list-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--dash-border);
            font-size: 0.74rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--dash-text-muted);
        }

        .seg-list {
            max-height: 620px;
            overflow-y: auto;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .seg-item {
            width: 100%;
            text-align: left;
            border: 1px solid var(--dash-border);
            background: var(--dash-bg);
            border-radius: 10px;
            padding: 12px 13px;
            color: var(--dash-text);
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .seg-item:hover {
            border-color: rgba(45, 212, 160, 0.5);
        }

        .seg-item.active {
            border-color: var(--dash-mint);
            background: rgba(45, 212, 160, 0.06);
        }

        .seg-item-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .seg-item-id {
            font-size: 0.86rem;
            font-weight: 700;
        }

        .seg-item-cliente {
            font-size: 0.82rem;
            color: var(--dash-text);
            margin-top: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .seg-item-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 7px;
            font-size: 0.72rem;
            color: var(--dash-text-muted);
        }

        .seg-empty {
            padding: 32px 18px;
            text-align: center;
            color: var(--dash-text-muted);
            font-size: 0.85rem;
        }

        .seg-empty i {
            display: block;
            font-size: 2rem;
            opacity: 0.35;
            margin-bottom: 10px;
        }

        .seg-map-card {
            overflow: hidden;
        }

        .seg-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            padding: 14px 16px;
            border-bottom: 1px solid var(--dash-border);
        }

        .seg-map-title {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--dash-text);
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-status::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-2 {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .status-3 {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
        }

        .status-6 {
            background: rgba(34, 211, 238, 0.1);
            color: #22d3ee;
        }

        #segMapa {
            height: 440px;
            background: var(--dash-bg);
        }

        .seg-info {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            padding: 16px;
            border-top: 1px solid var(--dash-border);
        }

        .seg-info-item {
            min-width: 0;
        }

        .seg-info-label {
            display: block;
            font-size: 0.68rem;
            color: var(--dash-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }

        .seg-info-value {
            font-size: 0.84rem;
            color: var(--dash-text);
            overflow-wrap: anywhere;
        }

        .seg-info-value a {
            color: var(--dash-mint);
            text-decoration: none;
        }

        .seg-marker-dot {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            border: 3px solid rgba(0, 0, 0, 0.25);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.35);
        }

        .seg-marker-dot.origen {
            background: #2dd4a0;
            color: #06251b;
            border-color: #fff;
        }

        .seg-marker-dot.transp {
            background: #22d3ee;
            color: #062229;
            border-color: #fff;
        }

        .seg-marker-dot.destino {
            background: #ef5a6f;
            color: #fff;
            border-color: #fff;
        }

        @media (max-width: 980px) {
            .seg-layout {
                grid-template-columns: 1fr;
            }

            .seg-list {
                max-height: 320px;
            }

            .seg-info {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .seg-page {
                padding: 16px;
            }

            .seg-info {
                grid-template-columns: 1fr;
            }

            #segMapa {
                height: 330px;
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
                <div class="seg-page">
                    <div class="seg-header">
                        <div>
                            <h2><i class="bi bi-geo-alt"></i> Seguimiento de deliverys</h2>
                            <p>Consulta el estado del pedido, el transportista asignado y su última ubicación.</p>
                        </div>
                        <a href="tienda_pedidos.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a pedidos
                        </a>
                    </div>

                    <div class="seg-toolbar">
                        <input type="search" id="segBuscar" class="seg-input" placeholder="Buscar por pedido, cliente o transportista" autocomplete="off">
                        <select id="segEstado" class="seg-input seg-select">
                            <option value="">Todos los estados</option>
                            <option value="6">Transportista asignado</option>
                            <option value="3">Enviados</option>
                            <option value="2">En preparación</option>
                        </select>
                        <button type="button" class="btn btn-success" onclick="cargarSeguimiento()">
                            <i class="bi bi-arrow-clockwise"></i> Actualizar
                        </button>
                    </div>

                    <div class="seg-layout">
                        <div class="seg-card seg-list-card">
                            <div class="seg-list-head" id="segContador">Deliverys en seguimiento</div>
                            <div id="segLista" class="seg-list">
                                <div class="seg-empty"><i class="bi bi-hourglass-split"></i> Cargando...</div>
                            </div>
                        </div>

                        <div class="seg-card seg-map-card">
                            <div class="seg-map-head">
                                <div class="seg-map-title" id="segTitulo">Selecciona un delivery</div>
                                <div id="segBadge"></div>
                            </div>
                            <div id="segMapa"></div>
                            <div class="seg-info">
                                <div class="seg-info-item">
                                    <span class="seg-info-label">Transportista</span>
                                    <span class="seg-info-value" id="segTransportista">—</span>
                                </div>
                                <div class="seg-info-item">
                                    <span class="seg-info-label">Estado del servicio</span>
                                    <span class="seg-info-value" id="segServicio">—</span>
                                </div>
                                <div class="seg-info-item">
                                    <span class="seg-info-label">Última ubicación</span>
                                    <span class="seg-info-value" id="segUbicacion">—</span>
                                </div>
                                <div class="seg-info-item">
                                    <span class="seg-info-label">Cliente</span>
                                    <span class="seg-info-value" id="segCliente">—</span>
                                </div>
                                <div class="seg-info-item">
                                    <span class="seg-info-label">Dirección de entrega</span>
                                    <span class="seg-info-value" id="segDireccion">—</span>
                                </div>
                                <div class="seg-info-item">
                                    <span class="seg-info-label">Precisión</span>
                                    <span class="seg-info-value" id="segPrecision">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/jquery-3.7.1.min.js"></script>
    <script src="../vendors/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/leaflet/leaflet.js"></script>
    <script>
        var SEG_API = 'db/tienda_seguimiento_api.php';
        var segOrders = [];
        var segSelectedId = null;
        var segMap = null;
        var segLayer = null;
        var segRouteCache = {};
        var segRouteToken = 0;

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

        function estadoInfo(status) {
            status = parseInt(status);
            var map = {
                2: { texto: 'En preparación', clase: 'status-2' },
                3: { texto: 'Enviado', clase: 'status-3' },
                6: { texto: 'Transportista asignado', clase: 'status-6' }
            };
            return map[status] || { texto: 'En seguimiento', clase: 'status-2' };
        }

        function servicioTexto(estado) {
            var map = {
                asignado: 'Asignado',
                aceptado: 'Aceptado',
                en_camino: 'En camino',
                completado: 'Completado',
                cancelado: 'Cancelado',
                rechazado: 'Rechazado'
            };
            return map[estado] || (estado || '—');
        }

        function hace(fecha) {
            if (!fecha) return '—';
            var d = new Date(String(fecha).replace(' ', 'T'));
            if (isNaN(d.getTime())) return fecha;
            var segundos = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
            if (segundos < 60) return 'hace ' + segundos + ' s';
            var minutos = Math.floor(segundos / 60);
            if (minutos < 60) return 'hace ' + minutos + ' min';
            var horas = Math.floor(minutos / 60);
            if (horas < 24) return 'hace ' + horas + ' h';
            return 'hace ' + Math.floor(horas / 24) + ' d';
        }

        function initMapa() {
            if (segMap) return;
            segMap = L.map('segMapa', { zoomControl: true }).setView([10.5, -66.9], 5);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap · Ruta: OSRM'
            }).addTo(segMap);
            segLayer = L.layerGroup().addTo(segMap);
        }

        function iconoPunto(tipo) {
            var iconos = {
                origen: 'bi-shop',
                destino: 'bi-geo-alt-fill',
                transp: 'bi-truck'
            };
            return L.divIcon({
                className: '',
                html: '<span class="seg-marker-dot ' + tipo + '"><i class="bi ' +
                    (iconos[tipo] || 'bi-geo-alt') + '"></i></span>',
                iconSize: [34, 34],
                iconAnchor: [17, 17],
                popupAnchor: [0, -18]
            });
        }

        function cargarSeguimiento() {
            fetch(SEG_API + '?action=listar', { cache: 'no-store' })
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.ok) throw new Error(data.msg || 'Error al cargar');
                    segOrders = data.data || [];

                    var anterior = segSelectedId;
                    if (!segOrders.some(function(o) { return o.id === segSelectedId; })) {
                        segSelectedId = segOrders.length ? segOrders[0].id : null;
                    }
                    renderLista();
                    renderSeleccion(anterior !== segSelectedId);
                })
                .catch(function(err) {
                    document.getElementById('segLista').innerHTML =
                        '<div class="seg-empty"><i class="bi bi-exclamation-triangle"></i> ' +
                        escaparHtml(err.message) + '</div>';
                });
        }

        function renderLista() {
            var cont = document.getElementById('segLista');
            var filtro = document.getElementById('segBuscar').value.trim().toLowerCase();
            var estado = document.getElementById('segEstado').value;
            var lista = segOrders.filter(function(o) {
                if (estado !== '' && String(o.status) !== estado) return false;
                if (filtro === '') return true;
                var texto = ('#' + o.id + ' ' + o.cliente + ' ' + (o.transportista_nombre || '')).toLowerCase();
                return texto.indexOf(filtro) !== -1;
            });

            document.getElementById('segContador').textContent =
                lista.length + ' delivery' + (lista.length === 1 ? '' : 's') +
                ' de ' + segOrders.length + ' en seguimiento';

            if (!segOrders.length) {
                cont.innerHTML = '<div class="seg-empty"><i class="bi bi-truck"></i>' +
                    'No hay deliverys activos.<br>Aparecerán aquí al asignarles transportista.</div>';
                return;
            }
            if (!lista.length) {
                cont.innerHTML = '<div class="seg-empty"><i class="bi bi-search"></i>Ningún delivery coincide con el filtro.</div>';
                return;
            }

            cont.innerHTML = lista.map(function(o) {
                var st = estadoInfo(o.status);
                var transporte = o.transportista_nombre
                    ? '<i class="bi bi-person-badge"></i> ' + escaparHtml(o.transportista_nombre)
                    : '<i class="bi bi-person-dash"></i> Sin transportista';
                var ubicacion = o.ubicacion
                    ? '<i class="bi bi-clock"></i> ' + escaparHtml(hace(o.ubicacion.creado_en))
                    : '<i class="bi bi-geo-alt"></i> Sin ubicación';
                return '<button type="button" class="seg-item' + (o.id === segSelectedId ? ' active' : '') +
                    '" onclick="seleccionarDelivery(' + o.id + ')">' +
                    '<div class="seg-item-top"><span class="seg-item-id">#' + o.id + '</span>' +
                    '<span class="badge-status ' + st.clase + '">' + st.texto + '</span></div>' +
                    '<div class="seg-item-cliente">' + escaparHtml(o.cliente || 'Cliente') + '</div>' +
                    '<div class="seg-item-meta">' + transporte + ' · ' + ubicacion + '</div>' +
                    '</button>';
            }).join('');
        }

        function seleccionarDelivery(id) {
            segSelectedId = id;
            renderLista();
            renderSeleccion(true);
        }

        function renderSeleccion(reencuadrar) {
            initMapa();
            segRouteToken++;
            segLayer.clearLayers();

            var o = segOrders.find(function(item) { return item.id === segSelectedId; });
            var titulo = document.getElementById('segTitulo');
            var badge = document.getElementById('segBadge');

            if (!o) {
                titulo.textContent = 'Selecciona un delivery';
                badge.innerHTML = '';
                document.getElementById('segTransportista').textContent = '—';
                document.getElementById('segServicio').textContent = '—';
                document.getElementById('segUbicacion').textContent = '—';
                document.getElementById('segCliente').textContent = '—';
                document.getElementById('segDireccion').textContent = '—';
                document.getElementById('segPrecision').textContent = '—';
                return;
            }

            var st = estadoInfo(o.status);
            titulo.textContent = '#' + o.id + ' · ' + (o.cliente || 'Cliente');
            badge.innerHTML = '<span class="badge-status ' + st.clase + '">' + st.texto + '</span>';

            var tel = o.transportista_telefono
                ? ' · <a href="tel:' + encodeURIComponent(o.transportista_telefono) + '">' +
                    escaparHtml(o.transportista_telefono) + '</a>'
                : '';
            document.getElementById('segTransportista').innerHTML = o.transportista_nombre
                ? escaparHtml(o.transportista_nombre) + tel
                : 'Sin asignar';
            document.getElementById('segServicio').textContent = servicioTexto(o.servicio_estado);
            document.getElementById('segUbicacion').textContent = o.ubicacion
                ? hace(o.ubicacion.creado_en) + ' · ' + o.ubicacion.creado_en
                : 'Sin ubicación reportada';
            document.getElementById('segCliente').textContent = o.cliente || '—';
            document.getElementById('segDireccion').textContent =
                (o.direccion || '—') + (o.referencia ? ' · ' + o.referencia : '');
            document.getElementById('segPrecision').textContent = (o.ubicacion && o.ubicacion.precision_m !== null)
                ? '±' + Math.round(o.ubicacion.precision_m) + ' m'
                : '—';

            var puntos = [];
            if (o.origen) {
                var marcadorOrigen = L.marker([o.origen.lat, o.origen.lng], { icon: iconoPunto('origen') })
                    .addTo(segLayer)
                    .bindPopup('Punto de origen');
                puntos.push(marcadorOrigen.getLatLng());
            }
            if (o.destino) {
                var marcadorDestino = L.marker([o.destino.lat, o.destino.lng], { icon: iconoPunto('destino') })
                    .addTo(segLayer)
                    .bindPopup('<strong>Destino de entrega</strong><br>' + escaparHtml(o.direccion || o.cliente || ''));
                puntos.push(marcadorDestino.getLatLng());
            }
            if (o.ubicacion) {
                var marcadorTransp = L.marker([o.ubicacion.lat, o.ubicacion.lng], { icon: iconoPunto('transp') })
                    .addTo(segLayer)
                    .bindPopup('<strong>Transportista</strong><br>' + escaparHtml(o.transportista_nombre || 'Sin asignar'));
                puntos.push(marcadorTransp.getLatLng());
            }
            if (o.origen && o.destino) {
                dibujarRutaCalzada(o.origen, o.destino, segRouteToken);
            }

            if (puntos.length === 1) {
                segMap.setView(puntos[0], 15);
            } else if (puntos.length > 1) {
                if (reencuadrar) {
                    segMap.fitBounds(L.latLngBounds(puntos), { padding: [40, 40], maxZoom: 16 });
                }
            } else if (reencuadrar) {
                segMap.setView([10.5, -66.9], 5);
            }
        }

        function claveRuta(origen, destino) {
            return [origen.lat.toFixed(5), origen.lng.toFixed(5),
                    destino.lat.toFixed(5), destino.lng.toFixed(5)].join(',');
        }

        function pintarRutaCalzada(coordenadas) {
            var puntosRuta = coordenadas.map(function(c) {
                return [c[1], c[0]];
            });
            L.polyline(puntosRuta, { color: '#ffffff', weight: 8, opacity: 0.9 }).addTo(segLayer);
            L.polyline(puntosRuta, { color: '#2dd4a0', weight: 4, opacity: 0.95 }).addTo(segLayer);
        }

        // Ruta real por calles entre la tienda (origen) y la dirección del cliente.
        function dibujarRutaCalzada(origen, destino, token) {
            var clave = claveRuta(origen, destino);
            if (segRouteCache[clave]) {
                pintarRutaCalzada(segRouteCache[clave]);
                return;
            }

            var url = 'https://router.project-osrm.org/route/v1/driving/' +
                origen.lng + ',' + origen.lat + ';' +
                destino.lng + ',' + destino.lat +
                '?overview=full&geometries=geojson';

            fetch(url)
                .then(function(res) {
                    return res.json();
                })
                .then(function(data) {
                    if (!data.routes || !data.routes[0] || !data.routes[0].geometry) return;
                    segRouteCache[clave] = data.routes[0].geometry.coordinates;
                    if (token !== segRouteToken) return;
                    pintarRutaCalzada(segRouteCache[clave]);
                })
                .catch(function(err) {
                    console.warn('No se pudo calcular la ruta por calles:', err);
                });
        }

        document.getElementById('segBuscar').addEventListener('input', renderLista);
        document.getElementById('segEstado').addEventListener('change', renderLista);

        cargarSeguimiento();
        setInterval(cargarSeguimiento, 30000);
    </script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>

</html>
