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
    <title>Chat — Tienda Virtual</title>
    <?php require_once('includes/headers.php'); ?>
    <style>
        .chat-page{display:flex;height:calc(100vh - 60px);overflow:hidden;}
        .conv-sidebar{width:360px;min-width:360px;background:var(--dash-card);border-right:1px solid var(--dash-border);display:flex;flex-direction:column;}
        .sidebar-head{padding:16px;border-bottom:1px solid var(--dash-border);}
        .sidebar-head h4{font-size:.95rem;font-weight:600;color:var(--dash-text);margin:0 0 12px;display:flex;align-items:center;gap:8px;}
        .sidebar-head h4 i{color:var(--dash-mint);}
        .stats-row{display:flex;gap:8px;margin-bottom:12px;}
        .stat-badge{flex:1;padding:8px;border-radius:8px;text-align:center;font-size:.78rem;background:rgba(255,255,255,.04);border:1px solid var(--dash-border);color:var(--dash-text-muted);}
        .stat-badge strong{display:block;font-size:1.1rem;color:var(--dash-text);font-weight:700;}
        .filter-row{display:flex;gap:8px;}
        .filter-row input,.filter-row select{background:var(--dash-bg);border:1px solid var(--dash-border);color:var(--dash-text);border-radius:6px;padding:6px 10px;font-size:.82rem;}
        .filter-row input{flex:1;}
        .conv-list{flex:1;overflow-y:auto;}
        .conv-item{padding:14px 16px;border-bottom:1px solid var(--dash-border);cursor:pointer;transition:background .15s;position:relative;}
        .conv-item:hover{background:rgba(255,255,255,.03);}
        .conv-item.active{background:rgba(45,212,160,.06);border-left:3px solid var(--dash-mint);}
        .conv-item.unread{background:rgba(250,204,21,.04);}
        .conv-top{display:flex;justify-content:space-between;align-items:start;margin-bottom:4px;}
        .conv-user{font-weight:600;color:var(--dash-text);font-size:.88rem;}
        .conv-time{font-size:.7rem;color:var(--dash-text-muted);white-space:nowrap;}
        .conv-subject{font-size:.8rem;color:var(--dash-text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-bottom:2px;}
        .conv-preview{font-size:.75rem;color:var(--dash-text-muted);opacity:.7;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .conv-meta{display:flex;gap:6px;margin-top:6px;align-items:center;}
        .conv-badge{font-size:.65rem;padding:2px 7px;border-radius:99px;font-weight:600;}
        .badge-abierta{background:rgba(59,130,246,.12);color:#60a5fa;}
        .badge-cerrada{background:rgba(255,255,255,.06);color:var(--dash-text-muted);}
        .badge-resuelta{background:rgba(45,212,160,.12);color:var(--dash-mint);}
        .unread-dot{width:8px;height:8px;border-radius:50%;background:#ef5a6f;flex-shrink:0;}

        .chat-area{flex:1;display:flex;flex-direction:column;background:var(--dash-bg);}
        .chat-header{padding:14px 20px;border-bottom:1px solid var(--dash-border);background:var(--dash-card);display:flex;justify-content:space-between;align-items:center;}
        .chat-header h5{margin:0;font-size:.92rem;font-weight:600;color:var(--dash-text);}
        .chat-header small{color:var(--dash-text-muted);font-size:.75rem;}
        .chat-header-actions{display:flex;gap:8px;align-items:center;}
        .chat-header-actions select{background:var(--dash-bg);border:1px solid var(--dash-border);color:var(--dash-text);border-radius:6px;padding:5px 10px;font-size:.8rem;}
        .chat-header-actions button{background:rgba(45,212,160,.1);border:1px solid rgba(45,212,160,.2);color:var(--dash-mint);border-radius:6px;padding:5px 12px;font-size:.8rem;cursor:pointer;}
        .chat-header-actions button:hover{background:rgba(45,212,160,.2);}

        .chat-messages{flex:1;overflow-y:auto;padding:20px;background:rgba(0,0,0,.1);}
        .msg{margin-bottom:14px;display:flex;gap:10px;}
        .msg.admin{flex-direction:row-reverse;}
        .msg-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:.72rem;flex-shrink:0;}
        .msg.admin .msg-avatar{background:var(--dash-mint);color:#000;}
        .msg.user .msg-avatar{background:rgba(96,165,250,.15);color:#60a5fa;}
        .msg-bubble{max-width:65%;padding:10px 14px;border-radius:12px;font-size:.85rem;line-height:1.45;}
        .msg.admin .msg-bubble{background:var(--dash-mint);color:#000;border-bottom-right-radius:4px;}
        .msg.user .msg-bubble{background:var(--dash-card);color:var(--dash-text);border:1px solid var(--dash-border);border-bottom-left-radius:4px;}
        .msg-time{font-size:.68rem;color:var(--dash-text-muted);margin-top:3px;padding:0 4px;}

        .chat-input{padding:14px 20px;border-top:1px solid var(--dash-border);background:var(--dash-card);display:flex;gap:10px;align-items:flex-end;}
        .chat-input textarea{flex:1;background:var(--dash-bg);border:1px solid var(--dash-border);color:var(--dash-text);border-radius:10px;padding:10px 14px;font-size:.85rem;resize:none;max-height:100px;outline:none;}
        .chat-input textarea:focus{border-color:var(--dash-mint);}
        .send-btn{width:42px;height:42px;padding:0;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;}
        .send-btn:disabled{background:var(--dash-border);color:var(--dash-text-muted);}

        .empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--dash-text-muted);}
        .empty-state i{font-size:3.5rem;opacity:.3;margin-bottom:12px;}
        .empty-state h5{font-size:.95rem;font-weight:500;margin:0 0 4px;color:var(--dash-text-muted);}
        .empty-state p{font-size:.8rem;margin:0;}

        .loading{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px;color:var(--dash-text-muted);}
        .spinner{border:3px solid var(--dash-border);border-top-color:var(--dash-mint);border-radius:50%;width:30px;height:30px;animation:spin .8s linear infinite;margin-bottom:10px;}
        @keyframes spin{to{transform:rotate(360deg);}}

        /* Volver a la lista: sólo se muestra en el teléfono */
        .chat-back{display:none;width:34px;height:34px;flex-shrink:0;align-items:center;justify-content:center;background:rgba(255,255,255,.05);border:1px solid var(--dash-border);border-radius:8px;color:var(--dash-text);font-size:1rem;cursor:pointer;}

        @media(max-width:768px){
            .conv-sidebar{width:100%;min-width:100%;}
            .chat-page{flex-direction:column;}

            /* En el teléfono se ve la lista o la conversación, no las dos */
            .chat-page.viendo-chat .conv-sidebar{display:none;}
            .chat-page:not(.viendo-chat) .chat-area{display:none;}

            .chat-back{display:inline-flex;}

            .chat-header{padding:10px 12px;gap:8px;}
            .chat-header>div:first-child{min-width:0;}
            .chat-header h5{font-size:.86rem;}
            .chat-header small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
            .chat-messages{padding:14px 12px;}
            .msg-bubble{max-width:80%;}
            .chat-input{padding:10px 12px;}
        }
    </style>
</head>
<body class="nav-md">
<div class="container body">
<div class="main_container">
    <?php echo $menu ?>
    <?php echo $topnav ?>

    <div class="right_col" role="main" style="padding:0;">
        <div class="chat-page">
            <div class="conv-sidebar">
                <div class="sidebar-head">
                    <h4><i class="bi bi-chat-dots-fill"></i> Chat Tienda</h4>
                    <div class="stats-row">
                        <div class="stat-badge"><strong id="st-abiertas">0</strong>Abiertas</div>
                        <div class="stat-badge"><strong id="st-resueltas">0</strong>Resueltas</div>
                        <div class="stat-badge"><strong id="st-cerradas">0</strong>Cerradas</div>
                    </div>
                    <div class="filter-row">
                        <input type="text" id="f-search" placeholder="Buscar...">
                        <select id="f-estado">
                            <option value="">Todas</option>
                            <option value="abierta">Abiertas</option>
                            <option value="resuelta">Resueltas</option>
                            <option value="cerrada">Cerradas</option>
                        </select>
                    </div>
                </div>
                <div class="conv-list" id="conv-list">
                    <div class="loading"><div class="spinner"></div><p style="font-size:.8rem;">Cargando...</p></div>
                </div>
            </div>

            <div class="chat-area" id="chat-area">
                <div class="empty-state">
                    <i class="bi bi-chat-text"></i>
                    <h5>Selecciona una conversación</h5>
                    <p>Elige una conversación de la lista para ver los mensajes</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
var conversaciones = [];
var convActiva = null;
var mensajes = [];
var lastMsgId = 0;
var pollingTimer = null;
var abortCtrl = null;

function cargarConversaciones(showLoading) {
    if (showLoading !== false) {
        document.getElementById('conv-list').innerHTML = '<div class="loading"><div class="spinner"></div><p style="font-size:.8rem;">Cargando...</p></div>';
    }
    var estado = document.getElementById('f-estado').value;
    var busq   = document.getElementById('f-search').value;
    var url = 'db/tienda_chat_api.php?action=listar';
    if (estado) url += '&estado=' + encodeURIComponent(estado);
    if (busq)   url += '&busqueda=' + encodeURIComponent(busq);

    fetch(url).then(function(r){return r.json()}).then(function(d){
        if (!d.ok) return;
        conversaciones = d.conversaciones;
        renderConv();
        var s = d.estadisticas;
        document.getElementById('st-abiertas').textContent  = s.abiertas;
        document.getElementById('st-resueltas').textContent = s.resueltas;
        document.getElementById('st-cerradas').textContent  = s.cerradas;
    });
}

function renderConv() {
    var el = document.getElementById('conv-list');
    if (!conversaciones.length) {
        el.innerHTML = '<div class="empty-state"><i class="bi bi-chat-dots" style="font-size:2rem;"></i><p style="margin-top:8px;">No hay conversaciones</p></div>';
        return;
    }
    el.innerHTML = '';
    conversaciones.forEach(function(c) {
        var active = convActiva && convActiva.id === c.id;
        var div = document.createElement('div');
        div.className = 'conv-item' + (c.mensajes_sin_leer > 0 ? ' unread' : '') + (active ? ' active' : '');
        div.innerHTML =
            '<div class="conv-top"><div class="conv-user">' + esc(c.usuario_nombre || 'N/A') + '</div><div class="conv-time">' + fmtTime(c.ultimo_mensaje_en) + '</div></div>' +
            '<div class="conv-subject">' + esc(c.asunto) + '</div>' +
            '<div class="conv-preview">' + esc(c.ultimo_mensaje || '') + '</div>' +
            '<div class="conv-meta">' +
                '<span class="conv-badge badge-' + c.estado + '">' + c.estado + '</span>' +
                '<span style="font-size:.68rem;color:var(--dash-text-muted);">' + c.total_mensajes + ' msg</span>' +
                (c.mensajes_sin_leer > 0 ? '<div class="unread-dot"></div>' : '') +
            '</div>';
        div.onclick = function() { seleccionar(c); };
        el.appendChild(div);
    });
}

function seleccionar(conv) {
    if (convActiva && convActiva.id === conv.id) return;
    convActiva = conv;
    document.querySelector('.chat-page').classList.add('viendo-chat');
    stopPolling();
    renderConv();
    document.getElementById('chat-area').innerHTML = '<div class="loading"><div class="spinner"></div><p style="font-size:.8rem;">Cargando mensajes...</p></div>';
    mensajes = [];
    lastMsgId = 0;
    fetchMensajes(conv.id).then(function(){
        renderChat();
        startPolling();
    });
}

function fetchMensajes(convId) {
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();
    return fetch('db/tienda_chat_api.php?action=mensajes&conversacion_id=' + convId, {signal: abortCtrl.signal})
        .then(function(r){return r.json()})
        .then(function(d){
            if (!d.ok) return;
            mensajes = d.mensajes;
            if (mensajes.length) lastMsgId = mensajes[mensajes.length-1].id;
            renderMensajes();
        }).catch(function(e){ if (e.name !== 'AbortError') console.error(e); });
}

function renderChat() {
    var el = document.getElementById('chat-area');
    if (!convActiva) {
        el.innerHTML = '<div class="empty-state"><i class="bi bi-chat-text"></i><h5>Selecciona una conversación</h5></div>';
        return;
    }
    el.innerHTML =
        '<div class="chat-header"><div style="display:flex;align-items:center;gap:10px;min-width:0;">' +
            '<button class="chat-back" onclick="volverLista()" aria-label="Volver a la lista"><i class="bi bi-arrow-left"></i></button>' +
            '<div style="min-width:0;"><h5>' + esc(convActiva.usuario_nombre) + '</h5><small>' + esc(convActiva.usuario_email || '') + ' · ' + esc(convActiva.asunto) + '</small></div></div>' +
        '<div class="chat-header-actions"><select id="estado-select"><option value="abierta"' + (convActiva.estado==='abierta'?' selected':'') + '>Abierta</option><option value="resuelta"' + (convActiva.estado==='resuelta'?' selected':'') + '>Resuelta</option><option value="cerrada"' + (convActiva.estado==='cerrada'?' selected':'') + '>Cerrada</option></select>' +
        '<button onclick="cambiarEstado()"><i class="bi bi-check-lg"></i></button></div></div>' +
        '<div class="chat-messages" id="chat-msgs"></div>' +
        '<div class="chat-input"><textarea id="msg-input" placeholder="Escribe tu respuesta..." rows="1"></textarea><button class="btn btn-success send-btn" id="send-btn" onclick="enviar()"><i class="bi bi-send-fill"></i></button></div>';
    renderMensajes();
    var ta = document.getElementById('msg-input');
    ta.addEventListener('keydown', function(e){ if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); enviar(); }});
}

// En el teléfono la conversación tapa la lista; este botón vuelve
function volverLista() {
    document.querySelector('.chat-page').classList.remove('viendo-chat');
}

function renderMensajes() {
    var el = document.getElementById('chat-msgs');
    if (!el) return;
    if (!mensajes.length) { el.innerHTML = '<div class="empty-state"><p>Sin mensajes</p></div>'; return; }
    el.innerHTML = '';
    mensajes.forEach(function(m) {
        var initials = m.es_admin ? 'AD' : (convActiva.usuario_nombre || 'U').substring(0,2).toUpperCase();
        var div = document.createElement('div');
        div.className = 'msg ' + (m.es_admin ? 'admin' : 'user');
        div.innerHTML = '<div class="msg-avatar">' + initials + '</div><div><div class="msg-bubble">' + esc(m.mensaje) + '</div><div class="msg-time">' + fmtTime(m.creado_en) + '</div></div>';
        el.appendChild(div);
    });
    el.scrollTop = el.scrollHeight;
}

function enviar() {
    var input = document.getElementById('msg-input');
    var btn = document.getElementById('send-btn');
    var txt = input.value.trim();
    if (!txt || !convActiva) return;
    input.disabled = true;
    btn.disabled = true;
    fetch('db/tienda_chat_api.php?action=responder', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({conversacion_id: convActiva.id, mensaje: txt})
    }).then(function(r){return r.json()}).then(function(d){
        if (d.ok) {
            input.value = '';
            return fetchMensajes(convActiva.id);
        }
    }).finally(function(){
        input.disabled = false;
        btn.disabled = false;
        input.focus();
    });
}

function cambiarEstado() {
    var sel = document.getElementById('estado-select');
    if (!sel || !convActiva) return;
    fetch('db/tienda_chat_api.php?action=cambiar_estado', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({conversacion_id: convActiva.id, estado: sel.value})
    }).then(function(r){return r.json()}).then(function(d){
        if (d.ok) { convActiva.estado = sel.value; cargarConversaciones(false); }
    });
}

function startPolling() { stopPolling(); doPoll(); }
function stopPolling() { clearTimeout(pollingTimer); pollingTimer = null; if (abortCtrl) abortCtrl.abort(); }

function doPoll() {
    if (!convActiva) return;
    abortCtrl = new AbortController();
    fetch('db/tienda_chat_api.php?action=poll&conversacion_id=' + convActiva.id + '&ultimo_mensaje_id=' + lastMsgId, {signal: abortCtrl.signal})
        .then(function(r){return r.json()})
        .then(function(d){
            if (d.ok && d.nuevos_mensajes && d.mensajes && d.mensajes.length) {
                d.mensajes.forEach(function(m){
                    if (!mensajes.find(function(x){return x.id===m.id;})) {
                        mensajes.push(m);
                        lastMsgId = m.id;
                    }
                });
                renderMensajes();
            }
        }).catch(function(e){ if (e.name !== 'AbortError') console.error(e); });
    pollingTimer = setTimeout(doPoll, 3000);
}

function fmtTime(f) {
    if (!f) return '';
    var d = new Date(f.replace(' ','T') + 'Z');
    var diff = (Date.now() - d.getTime()) / 60000;
    if (diff < 1) return 'Ahora';
    if (diff < 60) return Math.floor(diff) + 'm';
    if (diff < 1440) return Math.floor(diff/60) + 'h';
    return d.toLocaleDateString('es-ES', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'});
}

function esc(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

document.getElementById('f-estado').addEventListener('change', cargarConversaciones);
var searchTimer;
document.getElementById('f-search').addEventListener('input', function(){
    clearTimeout(searchTimer);
    searchTimer = setTimeout(cargarConversaciones, 400);
});

cargarConversaciones();
setInterval(function(){ cargarConversaciones(false); }, 30000);
</script>
<script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>
</html>
