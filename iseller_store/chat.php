<?php
require_once('core/db.php');
require_once('core/session.php');
requireLogin();

// Tiendas y motivos para "Nueva consulta"
$tiendasChat = [];
$resTiendas = $conexion->query("SELECT id, COALESCE(NULLIF(nombre_visible, ''), nombre) AS nombre
                                FROM negocio
                                WHERE tiene_tienda_virtual = 1 AND estado = 'activo'
                                ORDER BY nombre");
if ($resTiendas) {
    while ($rowT = $resTiendas->fetch_assoc()) {
        $tiendasChat[] = $rowT;
    }
}
$categoriasChat = [];
$resCats = $conexion->query("SELECT id, nombre FROM tienda_chat_categorias WHERE activo = 1 ORDER BY orden, nombre");
if ($resCats) {
    while ($rowC = $resCats->fetch_assoc()) {
        $categoriasChat[] = $rowC;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Chats - iSeller Store</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/img/logo-tienda.png">
    <link rel="apple-touch-icon" href="assets/img/logo-tienda.png">
    <link href="../publico/vendors/bootstrap5/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../publico/vendors/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/global-styles.css">
    <style>
        body {
            background: #f8faf9;
        }

        .chat-page {
            display: flex;
            height: calc(100vh - var(--topbar-h, 64px));
            overflow: hidden;
            margin-top: var(--topbar-h, 64px);
        }

        .conv-sidebar {
            width: 340px;
            min-width: 340px;
            background: #fff;
            border-right: 1px solid var(--border-color, #e5e7eb);
            display: flex;
            flex-direction: column;
        }

        .sidebar-head {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border-color, #f0f2f1);
        }

        .sidebar-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-title .icon-wrap {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #1a7f4b;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .95rem;
        }

        .conv-list {
            flex: 1;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }

        .conv-list::-webkit-scrollbar {
            width: 5px;
        }

        .conv-list::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .conv-item {
            padding: 14px 18px;
            border-bottom: 1px solid var(--border-color, #f3f4f6);
            cursor: pointer;
            transition: all .2s ease;
            border-left: 3px solid transparent;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }

        .conv-item:hover {
            background: rgba(26, 127, 75, .03);
        }

        .conv-item.active {
            background: rgba(26, 127, 75, .06);
            border-left-color: #1a7f4b;
        }

        .conv-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f7f3;
            border: 1.5px solid var(--border-color, #e5e7eb);
        }

        .conv-avatar-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            flex-shrink: 0;
            background: #f0f7f3;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a7f4b;
            font-size: .9rem;
            font-weight: 600;
            border: 1.5px solid var(--border-color, #e5e7eb);
        }

        .conv-body {
            flex: 1;
            min-width: 0;
        }

        .conv-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 3px;
        }

        .conv-store {
            font-weight: 600;
            font-size: .78rem;
            color: var(--text-secondary);
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-subject {
            font-weight: 600;
            font-size: .88rem;
            color: var(--text-primary);
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .conv-time {
            font-size: .65rem;
            color: var(--text-secondary);
            white-space: nowrap;
            margin-left: 8px;
            opacity: .7;
        }

        .conv-preview {
            font-size: .75rem;
            color: var(--text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
        }

        .conv-meta {
            display: flex;
            gap: 6px;
            align-items: center;
            margin-top: 5px;
        }

        .conv-badge {
            font-size: .6rem;
            padding: 2px 8px;
            border-radius: var(--radius-pill);
            font-weight: 600;
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .unread-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #1a7f4b;
            flex-shrink: 0;
        }

        .sidebar-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 30px;
            text-align: center;
        }

        .sidebar-empty .icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            background: #f0f7f3;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 14px;
            color: #1a7f4b;
            font-size: 1.4rem;
        }

        .sidebar-empty p {
            font-size: .82rem;
            color: var(--text-secondary);
            margin: 0;
            line-height: 1.5;
        }

        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f8faf9;
            position: relative;
        }

        .chat-header {
            padding: 14px 24px;
            border-bottom: 1px solid var(--border-color, #e5e7eb);
            background: rgba(255, 255, 255, .9);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-header-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            background: #f0f7f3;
            border: 1.5px solid var(--border-color, #e5e7eb);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1a7f4b;
            font-weight: 600;
        }

        .chat-header-title {
            font-weight: 600;
            font-size: .92rem;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-header-cat {
            font-size: .72rem;
            color: var(--text-secondary);
            margin-top: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .chat-header-badge {
            font-size: .65rem;
            padding: 4px 10px;
            border-radius: var(--radius-pill);
            font-weight: 600;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }

        .chat-messages::-webkit-scrollbar {
            width: 5px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 10px;
        }

        .chat-input-bar {
            padding: 16px 24px;
            border-top: 1px solid var(--border-color, #e5e7eb);
            background: rgba(255, 255, 255, .9);
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .chat-input-bar textarea {
            flex: 1;
            border: 1.5px solid var(--border-color, #e5e7eb);
            border-radius: 12px;
            padding: 11px 14px;
            font-size: .85rem;
            resize: none;
            max-height: 80px;
            outline: none;
            font-family: inherit;
            transition: border-color .2s;
            background: #fff;
        }

        .chat-input-bar textarea:focus {
            border-color: #1a7f4b;
            box-shadow: 0 0 0 3px rgba(26, 127, 75, .1);
        }

        .send-btn {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: #1a7f4b;
            color: #fff;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all .2s ease;
        }

        .send-btn:hover {
            background: #145a35;
        }

        .send-btn:disabled {
            background: var(--border-color, #e5e7eb);
            color: var(--text-secondary);
            cursor: not-allowed;
        }

        .msg {
            display: flex;
            gap: 8px;
        }

        .msg.user {
            justify-content: flex-end;
        }

        .msg-bubble {
            max-width: 65%;
            padding: 12px 16px;
            border-radius: 16px;
            font-size: .85rem;
            line-height: 1.5;
        }

        .msg.user .msg-bubble {
            background: #1a7f4b;
            color: #fff;
            border-bottom-right-radius: 4px;
        }

        .msg.admin .msg-bubble {
            background: #fff;
            color: var(--text-primary);
            border: 1px solid var(--border-color, #e5e7eb);
            border-bottom-left-radius: 4px;
        }

        .msg-time {
            font-size: .63rem;
            margin-top: 5px;
            opacity: .6;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
        }

        .empty-state .icon {
            width: 80px;
            height: 80px;
            border-radius: 24px;
            background: #f0f7f3;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: #1a7f4b;
            font-size: 2rem;
        }

        .empty-state h5 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 6px;
        }

        .empty-state p {
            font-size: .82rem;
            margin: 0;
        }

        .closed-banner {
            padding: 10px 24px;
            text-align: center;
            font-size: .78rem;
            color: var(--text-secondary);
            border-top: 1px solid var(--border-color);
            background: rgba(245, 247, 246, .8);
        }

        @media(max-width:768px) {
            .conv-sidebar {
                width: 100%;
                min-width: 100%;
                position: absolute;
                inset: 0;
                z-index: 10;
            }

            .chat-page {
                position: relative;
            }

            .chat-page.show-chat .conv-sidebar {
                display: none;
            }

            .chat-page.show-chat .chat-area {
                position: relative;
                z-index: 10;
            }

            .msg-bubble {
                max-width: 80%;
            }
        }

        /* Nueva consulta */
        .sidebar-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .nc-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            z-index: 2000;
        }

        .nc-overlay.open {
            display: flex;
        }

        .nc-box {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 470px;
            padding: 22px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
            max-height: 92vh;
            overflow-y: auto;
        }

        .nc-box h5 {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 0 0 16px;
            color: var(--text-primary);
        }

        .nc-box label {
            display: block;
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin: 12px 0 5px;
        }

        .nc-box input,
        .nc-box select,
        .nc-box textarea {
            width: 100%;
            border: 1px solid #dfe5e2;
            border-radius: 8px;
            padding: 9px 11px;
            font-size: .88rem;
            font-family: inherit;
            background: #fff;
        }

        .nc-box textarea {
            resize: vertical;
        }

        .nc-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 18px;
        }

        .nc-actions button {
            border: 0;
            border-radius: 8px;
            padding: 9px 16px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
        }

        .nc-cancel {
            background: #f1f5f3;
            color: var(--text-secondary);
        }

        .nc-send {
            background: #1a7f4b;
            color: #fff;
        }

        .nc-error {
            display: none;
            margin: 10px 0 0;
            color: #b91c1c;
            font-size: .8rem;
        }
    </style>
</head>

<body>
    <!-- Topbar compartido -->
    <?php include 'includes/navbar.php'; ?>

    <div class="chat-page" id="chat-page">
        <div class="conv-sidebar" id="conv-sidebar">

            <div class="sidebar-head">
                <h1 class="sidebar-title"><span class="icon-wrap"><i class="bi bi-chat-dots"></i></span> Mis chats</h1>
                <button type="button" class="btn btn-success btn-sm" id="btn-nueva-consulta" <?= empty($tiendasChat) || empty($categoriasChat) ? 'disabled' : '' ?>>
                    <i class="bi bi-plus-lg"></i> Nueva
                </button>
            </div>

            <div class="conv-list" id="conv-list">
                <div class="text-center py-5 text-muted" style="font-size:.82rem;">
                    <div class="spinner-border spinner-border-sm text-success mb-2"></div><br>Cargando...
                </div>
            </div>
        </div>
        <div class="chat-area" id="chat-area">
            <div class="empty-state">
                <div class="icon"><i class="bi bi-chat-text"></i></div>
                <h5>Selecciona una conversaci&oacute;n</h5>
                <p>Elige una conversaci&oacute;n de la lista para empezar a chatear</p>
            </div>
        </div>
    </div>

    <!-- Nueva consulta -->
    <div class="nc-overlay" id="nc-overlay" role="dialog" aria-modal="true" aria-labelledby="nc-title">
        <div class="nc-box">
            <h5 id="nc-title">Nueva consulta</h5>
            <form id="nc-form">
                <label for="nc-tienda">Tienda</label>
                <select id="nc-tienda" required>
                    <?php foreach ($tiendasChat as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="nc-categoria">Motivo</label>
                <select id="nc-categoria" required>
                    <?php foreach ($categoriasChat as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="nc-asunto">Asunto</label>
                <input type="text" id="nc-asunto" maxlength="200" required placeholder="Ej: Consulta sobre mi pedido">

                <label for="nc-mensaje">Mensaje</label>
                <textarea id="nc-mensaje" rows="4" maxlength="5000" required placeholder="Describe tu consulta..."></textarea>

                <p class="nc-error" id="nc-error"></p>
                <div class="nc-actions">
                    <button type="button" class="nc-cancel" id="nc-cancel">Cancelar</button>
                    <button type="submit" class="nc-send" id="nc-send">Enviar consulta</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        /* Offset del topbar compartido (fixed-top) para que no tape el contenido */
        (function() {
            function syncTopbar() {
                var nav = document.getElementById('navbar');
                if (!nav) return;
                document.documentElement.style.setProperty('--topbar-h', nav.offsetHeight + 'px');
            }
            syncTopbar();
            window.addEventListener('resize', syncTopbar);
        })();
    </script>

    <script>
        (function() {
            var convList = document.getElementById('conv-list');
            var chatArea = document.getElementById('chat-area');
            var chatPage = document.getElementById('chat-page');
            var convs = [],
                activeConv = null,
                pollTimer = null;

            function esc(t) {
                var d = document.createElement('div');
                d.textContent = t;
                return d.innerHTML;
            }

            function fmtTime(f) {
                if (!f) return '';
                var d = new Date(f.replace(' ', 'T') + 'Z');
                var diff = (Date.now() - d.getTime()) / 60000;
                if (diff < 1) return 'Ahora';
                if (diff < 60) return Math.floor(diff) + 'm';
                if (diff < 1440) return Math.floor(diff / 60) + 'h';
                return d.toLocaleDateString('es-ES', {
                    day: 'numeric',
                    month: 'short'
                });
            }

            function makeAvatar(c) {
                if (c.tienda_logo) {
                    return '<img class="conv-avatar" src="' + esc(c.tienda_logo) + '" alt="" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">' +
                        '<div class="conv-avatar-placeholder" style="display:none"><i class="bi bi-shop"></i></div>';
                }
                var ch = (c.tienda_nombre || 'T').substring(0, 1).toUpperCase();
                return '<div class="conv-avatar-placeholder">' + ch + '</div>';
            }

            // Abre directo la conversación indicada con ?conv=<id> (ej. "Ver mensajes" de una compra)
            var convDeUrlAbierta = false;

            function abrirConvDeUrl() {
                if (convDeUrlAbierta) return;
                var id = parseInt(new URLSearchParams(window.location.search).get('conv') || 0, 10);
                if (!id) {
                    convDeUrlAbierta = true;
                    return;
                }
                var c = convs.filter(function(x) {
                    return Number(x.id) === id;
                })[0];
                if (!c) return;
                convDeUrlAbierta = true;
                selectConv(c);
            }

            async function loadConvs() {
                try {
                    var r = await fetch('api/chat/cliente_chat.php?action=listar');
                    var d = await r.json();
                    if (!d.success) return;
                    convs = d.data;
                    renderConvs();
                    abrirConvDeUrl();
                } catch (e) {}
            }

            function renderConvs() {
                if (!convs.length) {
                    convList.innerHTML = '<div class="sidebar-empty"><div class="icon"><i class="bi bi-chat-dots"></i></div><p><strong>Sin conversaciones</strong><br>Cuando inicies un chat, aparecer&aacute; aqu&iacute;</p></div>';
                    return;
                }
                convList.innerHTML = '';
                convs.forEach(function(c) {
                    var div = document.createElement('div');
                    var isActive = activeConv && activeConv.id === c.id;
                    div.className = 'conv-item' + (isActive ? ' active' : '');
                    var ec = c.estado === 'abierta' ? '#3b82f6' : c.estado === 'resuelta' ? '#10b981' : '#9ca3af';
                    div.innerHTML = makeAvatar(c) +
                        '<div class="conv-body">' +
                        '<div class="conv-top"><span class="conv-subject">' + esc(c.asunto) + '</span><span class="conv-time">' + fmtTime(c.ultimo_mensaje_en) + '</span></div>' +
                        (c.tienda_nombre ? '<div class="conv-store"><i class="bi bi-shop-window me-1" style="font-size:.6rem;"></i>' + esc(c.tienda_nombre) + '</div>' : '') +
                        '<div class="conv-preview">' + esc(c.ultimo_mensaje || '') + '</div>' +
                        '<div class="conv-meta"><span class="conv-badge" style="background:' + ec + '15;color:' + ec + ';">' + c.estado + '</span>' +
                        (c.mensajes_sin_leer > 0 ? '<div class="unread-dot"></div>' : '') +
                        '</div></div>';
                    div.onclick = function() {
                        selectConv(c);
                    };
                    convList.appendChild(div);
                });
            }

            async function selectConv(c) {
                activeConv = c;
                renderConvs();
                stopPoll();
                chatPage.classList.add('show-chat');
                chatArea.innerHTML = '<div class="text-center py-5"><div class="spinner-border spinner-border-sm text-success"></div></div>';
                var r = await fetch('api/chat/cliente_chat.php?action=mensajes&conversacion_id=' + c.id);
                var d = await r.json();
                if (!d.success) return;
                renderChat(c, d.data || []);
                startPoll();
            }

            function renderChat(c, msgs) {
                var isClosed = c.estado === 'cerrada';
                var bb = isClosed ? '#f3f4f6' : c.estado === 'abierta' ? '#dbeafe' : '#dcfce7';
                var bc = isClosed ? '#6b7280' : c.estado === 'abierta' ? '#1e40af' : '#166534';
                var bi = isClosed ? 'bi-lock-fill' : c.estado === 'abierta' ? 'bi-hourglass-split' : 'bi-check-circle-fill';
                var storeName = c.tienda_nombre || 'Tienda';
                var storeInitial = storeName.substring(0, 1).toUpperCase();
                var storeAvatar = c.tienda_logo
                    ? '<img class="chat-header-avatar" src="' + esc(c.tienda_logo) + '" alt="" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\'">' +
                      '<div class="chat-header-avatar" style="display:none">' + storeInitial + '</div>'
                    : '<div class="chat-header-avatar">' + storeInitial + '</div>';
                chatArea.innerHTML =
                    '<div class="chat-header"><div class="chat-header-main">' + storeAvatar +
                    '<div style="min-width:0">' +
                    '<div class="chat-header-title">' + esc(storeName) + '</div>' +
                    '<div class="chat-header-cat"><i class="bi bi-tag-fill me-1" style="font-size:.6rem;"></i>' + esc(c.asunto) + ' &middot; ' + esc(c.categoria_nombre || 'General') + '</div>' +
                    '</div></div>' +
                    '<span class="chat-header-badge" style="background:' + bb + ';color:' + bc + ';"><i class="bi ' + bi + ' me-1" style="font-size:.55rem;"></i>' + c.estado + '</span></div>' +
                    '<div class="chat-messages" id="chat-msgs"></div>' +
                    (isClosed ?
                        '<div class="closed-banner"><i class="bi bi-lock-fill me-1"></i>Esta conversaci&oacute;n ha sido cerrada</div>' :
                        '<div class="chat-input-bar"><textarea id="chat-input" rows="1" placeholder="Escribe tu mensaje..."></textarea>' +
                        '<button class="send-btn" id="chat-send"><i class="bi bi-send-fill"></i></button></div>');
                renderMsgs(msgs);
                if (!isClosed) {
                    var input = document.getElementById('chat-input');
                    var btn = document.getElementById('chat-send');
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            sendMsg();
                        }
                    });
                    btn.addEventListener('click', sendMsg);
                }
            }

            function renderMsgs(msgs) {
                var el = document.getElementById('chat-msgs');
                if (!el) return;
                if (!msgs.length) {
                    el.innerHTML = '<div class="text-center text-muted py-5" style="font-size:.82rem;"><i class="bi bi-chat-left-text d-block mb-2" style="font-size:1.8rem;opacity:.25;"></i>Escribe el primer mensaje</div>';
                    return;
                }
                el.innerHTML = '';
                msgs.forEach(function(m) {
                    var isUser = !m.es_admin;
                    var div = document.createElement('div');
                    div.className = 'msg ' + (isUser ? 'user' : 'admin');
                    div.innerHTML = '<div class="msg-bubble">' + esc(m.mensaje) + '<div class="msg-time">' + fmtTime(m.creado_en) + '</div></div>';
                    el.appendChild(div);
                });
                el.scrollTop = el.scrollHeight;
            }

            async function sendMsg() {
                var input = document.getElementById('chat-input');
                var btn = document.getElementById('chat-send');
                var txt = input.value.trim();
                if (!txt || !activeConv) return;
                input.disabled = true;
                btn.disabled = true;
                try {
                    var r = await fetch('api/chat/cliente_chat.php?action=responder', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            conversacion_id: activeConv.id,
                            mensaje: txt
                        })
                    });
                    var d = await r.json();
                    if (d.success) {
                        input.value = '';
                        var r2 = await fetch('api/chat/cliente_chat.php?action=mensajes&conversacion_id=' + activeConv.id);
                        var d2 = await r2.json();
                        if (d2.success) renderMsgs(d2.data || []);
                    }
                } catch (e) {}
                input.disabled = false;
                btn.disabled = false;
                input.focus();
            }

            function startPoll() {
                stopPoll();
                doPoll();
            }

            function stopPoll() {
                clearTimeout(pollTimer);
                pollTimer = null;
            }

            function doPoll() {
                if (!activeConv) return;
                fetch('api/chat/cliente_chat.php?action=mensajes&conversacion_id=' + activeConv.id)
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(d) {
                        if (d.success) renderMsgs(d.data || []);
                    })
                    .catch(function() {});
                pollTimer = setTimeout(doPoll, 5000);
            }

            loadConvs();
        })();
    </script>

    <script>
        (function() {
            var overlay = document.getElementById('nc-overlay');
            var form = document.getElementById('nc-form');
            var openBtn = document.getElementById('btn-nueva-consulta');
            var cancelBtn = document.getElementById('nc-cancel');
            var sendBtn = document.getElementById('nc-send');
            var errorEl = document.getElementById('nc-error');
            if (!overlay || !form || !openBtn) return;

            function openModal() {
                errorEl.style.display = 'none';
                errorEl.textContent = '';
                overlay.classList.add('open');
                document.getElementById('nc-asunto').focus();
            }

            function closeModal() {
                overlay.classList.remove('open');
                form.reset();
            }

            openBtn.addEventListener('click', openModal);
            cancelBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeModal();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && overlay.classList.contains('open')) closeModal();
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var asunto = document.getElementById('nc-asunto').value.trim();
                var mensaje = document.getElementById('nc-mensaje').value.trim();
                if (asunto.length < 3 || mensaje.length < 5) {
                    errorEl.textContent = 'El asunto y el mensaje son obligatorios.';
                    errorEl.style.display = 'block';
                    return;
                }

                sendBtn.disabled = true;
                sendBtn.textContent = 'Enviando...';
                errorEl.style.display = 'none';

                fetch('api/chat/start.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            bss_id: parseInt(document.getElementById('nc-tienda').value, 10),
                            categoria_id: parseInt(document.getElementById('nc-categoria').value, 10),
                            asunto: asunto,
                            mensaje: mensaje
                        })
                    })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            window.location.href = 'chat.php?conv=' + encodeURIComponent(d.conversacion_id);
                        } else {
                            errorEl.textContent = d.message || 'No se pudo crear la consulta.';
                            errorEl.style.display = 'block';
                        }
                    })
                    .catch(function() {
                        errorEl.textContent = 'Error de conexión. Intenta de nuevo.';
                        errorEl.style.display = 'block';
                    })
                    .finally(function() {
                        sendBtn.disabled = false;
                        sendBtn.textContent = 'Enviar consulta';
                    });
            });
        })();
    </script>
</body>

</html>