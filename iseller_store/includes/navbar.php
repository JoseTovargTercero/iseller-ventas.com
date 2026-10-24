<?php
require_once __DIR__ . '/../core/venezuela_geo.php';

// Ensure $loyaltyData['foto'] is available for the navbar
if (isLoggedIn() && (!isset($loyaltyData) || !isset($loyaltyData['foto']))) {
    if (!isset($loyaltyData)) $loyaltyData = [];
    $uid_nav = getUserId();
    $stmt_nav = $conexion_store->prepare("SELECT foto FROM tienda_usuarios WHERE id = ?");
    $stmt_nav->bind_param("i", $uid_nav);
    $stmt_nav->execute();
    $loyaltyData['foto'] = $stmt_nav->get_result()->fetch_assoc()['foto'] ?? null;
    $stmt_nav->close();
}

// Usuarios antiguos pueden tener la cuenta sin estado/municipio. El modal
// de ubicación es obligatorio hasta que completen ambos datos.
$navGeoEstado = '';
$navGeoMunicipio = '';
$navGeoPendiente = false;
if (isLoggedIn()) {
    $uidGeo = (int)getUserId();
    $stmtGeo = $conexion_store->prepare("SELECT geo_estado, geo_municipio FROM tienda_usuarios WHERE id = ? LIMIT 1");
    if ($stmtGeo) {
        $stmtGeo->bind_param("i", $uidGeo);
        $stmtGeo->execute();
        $rowGeo = $stmtGeo->get_result()->fetch_assoc();
        $stmtGeo->close();
        $navGeoEstado = trim((string)($rowGeo['geo_estado'] ?? ''));
        $navGeoMunicipio = trim((string)($rowGeo['geo_municipio'] ?? ''));
        $navGeoPendiente = ($navGeoEstado === '' || $navGeoMunicipio === '');
    }
}
?>
<nav class="navbar navbar-custom fixed-top" id="navbar">
    <div class="container-fluid d-flex align-items-center justify-content-between px-3 px-md-5">
        <!-- Left: Logo -->
        <a class="navbar-brand" href="../">
            <img src="assets/img/logo-tienda.png" alt="" class="brand-logo" width="32" height="32">
            <span style="color: var(--primary-color);">iSeller</span> <span style="color: var(--text-primary);">Store</span>
        </a>


        <!-- Center: Search (Hidden on small mobile) -->
        <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
            <div class="d-none d-md-flex flex-grow-1 justify-content-center mx-4">
                <div class="header-search-container">
                    <i class="bi bi-search text-muted"></i>
                    <input type="text" class="header-search-input" id="search" placeholder="Buscar productos..." autocomplete="off">
                </div>
                <!-- Search Results Dropdown -->
                <div id="search-results" class="search-results-container"></div>
            </div>
        <?php endif; ?>

        <!-- Right: Actions -->
        <div class="header-actions d-flex align-items-center gap-3">
            <?php if (isLoggedIn() && $enable_rewards): ?>
                <div class="reward-container position-relative hide" id="reward-dropdown">
                    <button class="btn-icon btn-reward" data-bs-toggle="modal" data-bs-target="#modalRecompensas" title="Tienes recompensas">
                        <i class="bi bi-gift-fill"></i>
                        <span class="badge bg-danger rounded-circle badge-reward" id="badge-reward">1</span>
                    </button>
                </div>
            <?php endif; ?>




            <?php if (isLoggedIn()): ?>
                <a href="perfil.php?tab=orders" class="btn-icon" title="Mis Compras">
                    <i class="bi bi-bag-check"></i>
                </a>
                <a href="chat.php" class="btn-icon" title="Mis Chats">
                    <i class="bi bi-chat-dots"></i>
                </a>
            <?php endif; ?>

            <div class="cart-container">
                <button class="btn-icon btn-cart position-relative" id="cartDropdown" data-bs-toggle="modal" data-bs-target="#modalCarrito">
                    <i class="bi bi-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm border border-light" id="cart-count">
                        0
                    </span>
                </button>
            </div>

            <?php if (isLoggedIn()): ?>
            <!-- Notificaciones cliente -->
            <div class="position-relative" id="notif-dropdown">
                <button class="btn-icon position-relative" id="notif-bell" title="Notificaciones">
                    <i class="bi bi-bell"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm border border-light d-none" id="notif-badge" style="font-size:10px;">0</span>
                </button>
                <div class="notif-panel" id="notif-panel" style="display:none; position:absolute; right:0; top:calc(100% + 8px); width:340px; max-height:420px; overflow:hidden; border:1px solid var(--border-color, #e5e7eb); border-radius: var(--radius-md, 0.5rem); background:#fff; box-shadow:0 8px 24px rgba(0,0,0,.12); z-index:9999;">
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <h6 class="mb-0 fw-bold">Notificaciones</h6>
                        <button class="btn btn-link btn-sm text-decoration-none p-0 text-success fw-semibold" id="notif-mark-all">Marcar todo leido</button>
                    </div>
                    <div class="notif-list" id="notif-list" style="overflow-y:auto; max-height:360px;"></div>
                    <div class="text-center text-muted small py-3 d-none" id="notif-empty">
                        <i class="bi bi-bell-slash d-block mb-1" style="font-size:1.5rem; opacity:.4;"></i>
                        Sin notificaciones
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="dropdown">
                <?php if (isLoggedIn()): ?>
                    <button class="btn-icon p-0 overflow-hidden" data-bs-toggle="dropdown" title="Mi Cuenta">
                        <?php if (!empty($loyaltyData['foto'])): ?>
                            <img src="assets/img/profiles/<?php echo $loyaltyData['foto']; ?>" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%;">
                        <?php else: ?>
                            <i class="bi bi-person"></i>
                        <?php endif; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                        <li>
                            <h6 class="dropdown-header"><?php echo htmlspecialchars(getUserName()); ?></h6>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person-circle me-2"></i> Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="javascript:void(0)" onclick="typeof loadSavedCarts==='function'&&loadSavedCarts()"><i class="bi bi-folder-check me-2"></i> Cargar Carrito</a></li>
                        <li><a class="dropdown-item text-success fw-bold" href="iseller_store.apk" download><i class="bi bi-android2 me-2"></i> Descargar App</a></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                    </ul>
                <?php else: ?>
                    <a href="login.php" class="btn-icon" title="Iniciar Sesión">
                        <i class="bi bi-person"></i>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Search Toggle Button (Mobile Only) -->
            <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
                <button class="btn-icon d-md-none" id="btn-search-toggle" title="Buscar">
                    <i class="bi bi-search"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Search Overlay (Animated) -->
    <?php if (basename($_SERVER['PHP_SELF']) === 'index.php'): ?>
        <div id="mobile-search-overlay" class="mobile-search-overlay d-md-none">
            <div class="header-search-container w-100">
                <i class="bi bi-search text-muted"></i>
                <input type="text" class="header-search-input" id="search-mobile" placeholder="Buscar productos..." autocomplete="off">
                <button class="btn border-0 p-0 ms-2" id="btn-search-close">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <!-- Mobile Search Results -->
            <div id="search-results-mobile" class="search-results-container"></div>
        </div>
    <?php endif; ?>

    <!-- Sticky Progress Bar (Hidden by default) -->
    <div id="navbar-progress-container" class="navbar-progress">
        <div class="navbar-progress-bar" style="width: 0%"></div>
    </div>
</nav>

<style>
.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background .15s;
    border-bottom: 1px solid var(--border-color, #f3f4f6);
}
.notif-item:hover { background: var(--bg-secondary, #f9fafb); }
.notif-item.unread { background: rgba(26,127,75,.04); }
.notif-item .notif-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
}
.notif-item .notif-icon.chat { background: rgba(26,127,75,.1); color: #1a7f4b; }
.notif-item .notif-icon.compra { background: rgba(45,212,160,.12); color: #10b981; }
.notif-item .notif-icon.sistema { background: rgba(96,165,250,.12); color: #60a5fa; }
.notif-item .notif-body { flex:1; min-width:0; }
.notif-item .notif-title { font-size:13px; font-weight:600; color:var(--text-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.notif-item .notif-text { font-size:12px; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.notif-item .notif-time { font-size:11px; color:var(--text-secondary); white-space:nowrap; flex-shrink:0; }
.notif-item .notif-dot { width:8px; height:8px; border-radius:50%; background:#1a7f4b; flex-shrink:0; margin-top:6px; }
</style>

<script>
/* ========== Notificaciones Cliente (incluido con el topbar) ========== */
(function() {
    const bell = document.getElementById('notif-bell');
    const panel = document.getElementById('notif-panel');
    const list = document.getElementById('notif-list');
    const empty = document.getElementById('notif-empty');
    const badge = document.getElementById('notif-badge');
    const markAll = document.getElementById('notif-mark-all');
    if (!bell || !panel) return;

    function iconFor(t) {
        if (t === 'compra') return '<i class="bi bi-bag-check"></i>';
        if (t === 'chat') return '<i class="bi bi-chat-dots"></i>';
        return '<i class="bi bi-info-circle"></i>';
    }

    function timeAgo(str) {
        const s = Math.floor((Date.now() - new Date(str.replace(' ', 'T') + 'Z').getTime()) / 1000);
        if (s < 60) return 'ahora';
        if (s < 3600) return Math.floor(s / 60) + 'm';
        if (s < 86400) return Math.floor(s / 3600) + 'h';
        return Math.floor(s / 86400) + 'd';
    }

    async function loadNotifs() {
        try {
            const r = await fetch('api/notificaciones.php?action=listar&limite=15');
            const d = await r.json();
            if (!d.success) return;
            list.innerHTML = '';
            const hasData = d.data && d.data.length > 0;
            empty.style.display = hasData ? 'none' : 'block';
            if (!hasData) return;
            d.data.forEach(function(n) {
                const div = document.createElement('div');
                div.className = 'notif-item' + (n.leida == 0 ? ' unread' : '');
                div.innerHTML =
                    '<div class="notif-icon ' + n.tipo + '">' + iconFor(n.tipo) + '</div>' +
                    '<div class="notif-body"><div class="notif-title">' + n.titulo + '</div>' +
                    '<div class="notif-text">' + (n.cuerpo || '') + '</div></div>' +
                    '<div class="notif-time">' + timeAgo(n.created_at) + '</div>' +
                    (n.leida == 0 ? '<div class="notif-dot"></div>' : '');
                div.addEventListener('click', async function() {
                    if (n.leida == 0) {
                        await fetch('api/notificaciones.php?action=marcar_leida&id=' + n.id, {
                            method: 'POST'
                        });
                    }
                    var link = n.enlace;
                    if (!link) {
                        if (n.tipo === 'chat') link = 'chat.php';
                        else if (n.tipo === 'compra') link = 'perfil.php?tab=orders';
                        else link = 'perfil.php';
                    }
                    window.location.href = link;
                });
                list.appendChild(div);
            });
        } catch (e) {
            console.error('notif load error', e);
        }
    }

    async function loadBadge() {
        try {
            const r = await fetch('api/notificaciones.php?action=no_leidas');
            const d = await r.json();
            if (d.success && d.total > 0) {
                badge.textContent = d.total > 99 ? '99+' : d.total;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        } catch (e) {}
    }

    function openPanel() {
        panel.style.display = 'block';
        loadNotifs();
    }

    function closePanel() {
        panel.style.display = 'none';
    }

    bell.addEventListener('click', function(e) {
        e.stopPropagation();
        if (panel.style.display === 'block') {
            closePanel();
        } else {
            openPanel();
        }
    });

    markAll.addEventListener('click', async function() {
        await fetch('api/notificaciones.php?action=marcar_todas', {
            method: 'POST'
        });
        loadNotifs();
        loadBadge();
    });

    document.addEventListener('click', function(e) {
        if (!panel.contains(e.target) && e.target !== bell && !bell.contains(e.target)) {
            closePanel();
        }
    });

    loadBadge();
    setInterval(loadBadge, 30000);
})();
</script>
<?php if (isLoggedIn() && $navGeoPendiente): ?>
    <?php
    $navGeoMap = venezuela_estados_municipios();
    $navGeoOpciones = $navGeoMap[$navGeoEstado] ?? [];
    ?>
    <style>
        .geo-required-overlay {
            position: fixed;
            inset: 0;
            z-index: 3000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background: rgba(15, 23, 42, .62);
            backdrop-filter: blur(3px);
        }

        .geo-required-card {
            width: 100%;
            max-width: 430px;
            max-height: 92vh;
            overflow-y: auto;
            background: #fff;
            border-radius: 16px;
            padding: 26px 24px 22px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .28);
        }

        .geo-required-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            color: #1a7f4b;
            background: #f0f7f3;
            font-size: 1.5rem;
        }

        .geo-required-card h2 {
            margin: 0 0 8px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: #111827;
        }

        .geo-required-card p.geo-required-text {
            margin: 0 0 20px;
            text-align: center;
            font-size: .88rem;
            line-height: 1.5;
            color: #6b7280;
        }

        .geo-required-card label {
            display: block;
            margin: 13px 0 6px;
            font-size: .76rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .geo-required-card select {
            width: 100%;
            border: 1.5px solid #dfe5e2;
            border-radius: 9px;
            padding: 11px 12px;
            font-size: 16px;
            background: #fff;
            color: #111827;
            outline: none;
        }

        .geo-required-card select:focus {
            border-color: #1a7f4b;
            box-shadow: 0 0 0 3px rgba(26, 127, 75, .12);
        }

        .geo-required-error {
            display: none;
            margin: 12px 0 0;
            color: #b91c1c;
            font-size: .82rem;
        }

        .geo-required-submit {
            width: 100%;
            margin-top: 20px;
            border: 0;
            border-radius: 9px;
            padding: 12px 16px;
            background: #1a7f4b;
            color: #fff;
            font-size: .92rem;
            font-weight: 700;
            cursor: pointer;
        }

        .geo-required-submit:disabled {
            opacity: .65;
            cursor: wait;
        }

        @media (max-width: 480px) {
            .geo-required-card {
                padding: 22px 18px 18px;
                border-radius: 14px;
            }
        }
    </style>

    <div class="geo-required-overlay" id="geoRequiredModal" role="dialog" aria-modal="true" aria-labelledby="geoRequiredTitle">
        <div class="geo-required-card">
            <div class="geo-required-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <h2 id="geoRequiredTitle">Completa tu ubicación</h2>
            <p class="geo-required-text">Necesitamos tu estado y municipio para continuar usando la tienda. Estos datos son obligatorios.</p>
            <form id="geoRequiredForm">
                <label for="geoRequiredEstado">Estado</label>
                <select id="geoRequiredEstado" required>
                    <option value="">Seleccione un estado...</option>
                    <?php foreach ($navGeoMap as $estado => $municipios): ?>
                        <option value="<?= htmlspecialchars($estado) ?>" <?= $navGeoEstado === $estado ? 'selected' : '' ?>><?= htmlspecialchars($estado) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="geoRequiredMunicipio">Municipio</label>
                <select id="geoRequiredMunicipio" required>
                    <option value="">Seleccione un municipio...</option>
                    <?php foreach ($navGeoOpciones as $municipio): ?>
                        <option value="<?= htmlspecialchars($municipio) ?>" <?= $navGeoMunicipio === $municipio ? 'selected' : '' ?>><?= htmlspecialchars($municipio) ?></option>
                    <?php endforeach; ?>
                </select>

                <p class="geo-required-error" id="geoRequiredError"></p>
                <button type="submit" class="geo-required-submit" id="geoRequiredSubmit">Guardar y continuar</button>
            </form>
        </div>
    </div>

    <script>
        (function() {
            var overlay = document.getElementById('geoRequiredModal');
            var form = document.getElementById('geoRequiredForm');
            var estadoSel = document.getElementById('geoRequiredEstado');
            var municipioSel = document.getElementById('geoRequiredMunicipio');
            var errorEl = document.getElementById('geoRequiredError');
            var submitBtn = document.getElementById('geoRequiredSubmit');
            var GEO = <?= json_encode($navGeoMap, JSON_UNESCAPED_UNICODE) ?>;
            if (!overlay || !form) return;

            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = (window.innerWidth - document.documentElement.clientWidth) + 'px';

            function cargarMunicipios(seleccionado) {
                municipioSel.innerHTML = '<option value="">Seleccione un municipio...</option>';
                var lista = GEO[estadoSel.value] || [];
                lista.forEach(function(m) {
                    var opt = document.createElement('option');
                    opt.value = m;
                    opt.textContent = m;
                    if (m === seleccionado) opt.selected = true;
                    municipioSel.appendChild(opt);
                });
            }

            estadoSel.addEventListener('change', function() {
                cargarMunicipios('');
            });

            cargarMunicipios(<?= json_encode($navGeoMunicipio, JSON_UNESCAPED_UNICODE) ?>);
            estadoSel.focus();

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                errorEl.style.display = 'none';
                if (!estadoSel.value || !municipioSel.value) {
                    errorEl.textContent = 'Selecciona tu estado y municipio.';
                    errorEl.style.display = 'block';
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Guardando...';

                fetch('api/update_geo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        geo_estado: estadoSel.value,
                        geo_municipio: municipioSel.value
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (!d.success) throw new Error(d.message || 'No se pudieron guardar los datos.');
                    overlay.remove();
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                })
                .catch(function(err) {
                    errorEl.textContent = err.message || 'Error de conexión. Intenta de nuevo.';
                    errorEl.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Guardar y continuar';
                });
            });
        })();
    </script>
<?php endif; ?>
