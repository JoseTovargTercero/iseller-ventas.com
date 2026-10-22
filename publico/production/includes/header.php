<?php

function topnav()
{
    global $conexion;

    $nombre   = $_SESSION['nombre'] ?? 'Usuario';
    $inicial  = mb_strtoupper(mb_substr($nombre, 0, 1));
    $tipoUser = ($_SESSION['nivel'] == '1') ? 'Administrador' : 'Empleado';
    $bss_id   = $_SESSION['bss_id'] ?? 0;

    // Consultar vigencia de membresía para alerta
    require_once('../../configurar/MembresiaHelper.php');
    $estadoMem = MembresiaHelper::obtenerEstado($conexion, $bss_id);

    ob_start();

?>
    <script src='../assets/qrious.js' defer></script>
    <script src='../assets/qrcode.min.js' defer></script>

    <div class='top_nav' style="display: flex;">
        <div class='nav_menu'>

            <nav class='nav d-flex justify-content-between align-items-center' style="width: 100%;">
                <div class="left-nav-section d-flex align-items-center">
                    <a id="mobile_menu_toggle" class="mobile-menu-toggle">
                        <ion-icon name="menu-outline"></ion-icon>
                    </a>
                    <div class="text-white page-title" id="nombre_pagina">
                    </div>
                </div>

<div class="topnav-banner-center">
                    <?php if ($estadoMem['por_vencer'] && !$estadoMem['esta_vencida']): ?>
                        <div class="banner-alerta-membresia">
                            <div class="banner-content">
                                <i class="bx bx-alarm-exclamation"></i>
                                <span>
                                    Tu membresía <strong>(Plan <?php echo ucfirst($estadoMem['tipo']); ?>)</strong> está por vencer en
                                    <strong><?php echo $estadoMem['dias_restantes']; ?> <?php echo ($estadoMem['dias_restantes'] == 1) ? 'día' : 'días'; ?></strong>
                                    <?php if ($estadoMem['fecha_fin']): ?>
                                        (el <?php echo date('d/m/Y', strtotime($estadoMem['fecha_fin'])); ?>)
                                    <?php endif; ?>.
                                </span>
                            </div>
                            <a href="membresia_renovacion.php" class="banner-btn-renovar">
                                <i class="bx bx-credit-card"></i> Renovar ahora
                            </a>
                        </div>
                    <?php elseif ($estadoMem['pago_pendiente']): ?>
                        <div class="banner-alerta-membresia pendiente">
                            <div class="banner-content">
                                <i class="bx bx-time-five"></i>
                                <span>Tu comprobante de pago está en <strong>revisión</strong> por el equipo de soporte.</span>
                            </div>
                            <a href="membresia_renovacion.php" class="banner-btn-renovar pendiente">Ver estado</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="nav-user-dropdown d-flex align-items-center gap-3">
                    <div class="dropdown" id="notif-dropdown">
                        <a class="notif-toggle" id="notif-toggle-btn" style="cursor:pointer; position:relative; color:var(--dash-text,#e8edf2); font-size:22px;">
                            <ion-icon name="notifications-outline"></ion-icon>
                            <span class="notif-badge" id="notif-badge" style="display:none; position:absolute; top:-1px; right:-1px; background:#ef5a6f; color:#fff; font-size:10px; font-weight:700; border-radius:50%; min-width:18px; height:18px; line-height:18px; text-align:center; padding:0 4px;"></span>
                        </a>
                        <div class="notif-panel" id="notif-panel" style="display:none; position:absolute; right:0; top:100%; width:340px; max-height:420px; overflow-y:auto; border:1px solid var(--dash-border,#2e353e); border-radius:12px; background:var(--dash-card,#181b22); box-shadow:0 8px 24px rgba(0,0,0,.3); z-index:99999; margin-top:6px;">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2" style="border-bottom:1px solid var(--dash-border,#2e353e);">
                                <strong style="color:var(--dash-text,#e8edf2); font-size:14px;">Notificaciones</strong>
                                <a href="#" id="notif-mark-all" style="font-size:12px; color:var(--dash-mint,#2dd4a0); text-decoration:none;">Marcar todo leído</a>
                            </div>
                            <div id="notif-list"></div>
                            <div id="notif-empty" class="text-center py-4" style="color:var(--dash-text-muted,#8892a0); display:none;">
                                <ion-icon name="notifications-off-outline" style="font-size:32px; opacity:0.4;"></ion-icon>
                                <p class="mt-2 mb-0" style="font-size:13px;">Sin notificaciones</p>
                            </div>
                        </div>
                    </div>
                    <div class="dropdown" id="user-dropdown">
                        <a class="user-toggle" id="user-toggle-btn" style="cursor:pointer;">
                            <span class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($nombre) ?></span>
                            </span>
                            <span class="user-avatar"><?php echo $inicial ?></span>
                            <ion-icon name="chevron-down-outline" class="user-chevron"></ion-icon>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" id="user-menu" style="display:none; right:0; left:auto;">
                            <div class="dropdown-header">
                                <span class="user-avatar-sm"><?php echo $inicial ?></span>
                                <div>
                                    <strong><?php echo htmlspecialchars($nombre) ?></strong>
                                    <small><?php echo $tipoUser ?></small>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="../../login/salir.php">
                                <ion-icon name="log-out-outline"></ion-icon> Cerrar sesión
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </div>

        <script>
        // Notificaciones in-app + user dropdown (vanilla, no Bootstrap conflict)
        (function() {
            const badge = document.getElementById('notif-badge');
            const list = document.getElementById('notif-list');
            const empty = document.getElementById('notif-empty');
            const markAll = document.getElementById('notif-mark-all');
            const notifBtn = document.getElementById('notif-toggle-btn');
            const notifPanel = document.getElementById('notif-panel');
            const userBtn = document.getElementById('user-toggle-btn');
            const userMenu = document.getElementById('user-menu');
            const userChevron = userBtn ? userBtn.querySelector('.user-chevron') : null;

            function closeAll() {
                if (notifPanel) notifPanel.style.display = 'none';
                if (userMenu) userMenu.style.display = 'none';
                if (userChevron) userChevron.style.transform = '';
            }

            if (notifBtn && notifPanel) {
                notifBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var open = notifPanel.style.display !== 'none';
                    closeAll();
                    if (!open) notifPanel.style.display = 'block';
                });
            }

            if (userBtn && userMenu) {
                userBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var open = userMenu.style.display !== 'none';
                    closeAll();
                    if (!open) {
                        userMenu.style.display = 'block';
                        if (userChevron) userChevron.style.transform = 'rotate(180deg)';
                    }
                });
            }

            document.addEventListener('click', function(e) {
                closeAll();
            });

            function timeAgo(str) {
                const d = new Date(str.replace(' ', 'T') + 'Z');
                const now = new Date();
                const s = Math.floor((now - d) / 1000);
                if (s < 60) return 'ahora';
                if (s < 3600) return Math.floor(s / 60) + 'm';
                if (s < 86400) return Math.floor(s / 3600) + 'h';
                return Math.floor(s / 86400) + 'd';
            }

            function iconClass(tipo) {
                if (tipo === 'compra') return 'compra';
                if (tipo === 'stock') return 'stock';
                if (tipo === 'chat') return 'chat';
                return 'sistema';
            }

            function iconName(tipo) {
                if (tipo === 'compra') return 'cart-outline';
                if (tipo === 'stock') return 'alert-circle-outline';
                if (tipo === 'chat') return 'chatbubble-ellipses-outline';
                return 'information-circle-outline';
            }

            function render(data, count) {
                badge.style.display = count > 0 ? 'inline-block' : 'none';
                badge.textContent = count > 99 ? '99+' : count;
                list.innerHTML = '';
                if (!data || data.length === 0) {
                    empty.style.display = 'block';
                    return;
                }
                empty.style.display = 'none';
                data.forEach(function(n) {
                    const div = document.createElement('div');
                    div.className = 'notif-item' + (n.leida == 0 ? ' unread' : '');
                    div.innerHTML =
                        '<div class="notif-icon ' + iconClass(n.tipo) + '"><ion-icon name="' + iconName(n.tipo) + '"></ion-icon></div>' +
                        '<div class="notif-body">' +
                        '<div class="notif-title">' + escHtml(n.titulo) + '</div>' +
                        '<div class="notif-text">' + escHtml(n.cuerpo) + '</div>' +
                        '</div>' +
                        '<div class="notif-time">' + timeAgo(n.created_at) + '</div>' +
                        (n.leida == 0 ? '<div class="notif-dot"></div>' : '');
                    div.addEventListener('click', function() {
                        if (n.leida == 0) {
                            fetch('db/notificaciones_api.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'action=marcar_leida&id=' + n.id
                            });
                        }
                        if (n.tipo === 'compra') {
                            window.location.href = 'tienda_pedidos.php';
                        } else if (n.tipo === 'chat') {
                            window.location.href = 'tienda_chat.php';
                        }
                    });
                    list.appendChild(div);
                });
            }

            function escHtml(s) {
                const d = document.createElement('div');
                d.textContent = s;
                return d.innerHTML;
            }

            function fetchNotifs() {
                // Polling cada 30s en segundo plano: no debe abrir el loader global
                (window.fetchSilencioso || window.fetch)('db/notificaciones_api.php?action=listar&limite=10')
                    .then(function(r) {
                        return r.json()
                    })
                    .then(function(r) {
                        if (r.ok) render(r.data, r.no_leidas);
                    })
                    .catch(function() {});
            }

            fetchNotifs();
            polling = setInterval(fetchNotifs, 30000);

            if (markAll) {
                markAll.addEventListener('click', function(e) {
                    e.preventDefault();
                    fetch('db/notificaciones_api.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: 'action=marcar_todas'
                        })
                        .then(function(r) {
                            return r.json()
                        })
                        .then(function() {
                            fetchNotifs();
                        });
                });
            }
        })();
    </script>
    <script>
        // Custom mobile menu logic
        document.addEventListener("DOMContentLoaded", function() {
            var toggleBtn = document.getElementById('mobile_menu_toggle');
            var navbar = document.getElementById('navbar');

            if (toggleBtn && navbar) {
                // Create overlay
                var overlay = document.createElement('div');
                overlay.className = 'mobile-menu-overlay';
                document.body.appendChild(overlay);

                function toggleMenu() {
                    var open = navbar.classList.toggle('open');
                    overlay.classList.toggle('active', open);
                }

                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    toggleMenu();
                });

                overlay.addEventListener('click', function() {
                    toggleMenu();
                });
            }
        });
    </script>
    <?php
    // ── Tutorial iSeller ── Solo para admins (nivel 1) que no lo hayan completado
    $tutorialUser = null;
    $nivelSesion  = $_SESSION['nivel'] ?? 0;
    $userTutId    = (int)($_SESSION['id'] ?? 0);
    $bssIdTut     = (int)($bss_id ?? 0);

    if ($nivelSesion == 1 && $bssIdTut > 0 && $userTutId > 0) {
        $stmtTut = $conexion->prepare("SELECT tutorial_completado FROM usuarios WHERE id = ? LIMIT 1");
        if ($stmtTut) {
            $stmtTut->bind_param("i", $userTutId);
            $stmtTut->execute();
            $tutRes = $stmtTut->get_result()->fetch_assoc();
            $stmtTut->close();
            $tutorialUser = $tutRes;
        }
    }
    if ($tutorialUser && !$tutorialUser['tutorial_completado']): ?>

        <div id="tutorialOverlay" style="display:none">
            <div id="tutorialBackdrop"></div>
            <div id="tutorialPanel">
                <div id="tHeader">
                    <div id="tHeaderLeft">
                        <div id="tHeaderIcon"><i class="bx bx-bulb" style="font-size:18px"></i></div>
                        <div>
                            <div id="tTitle">Tutorial de inicio</div>
                            <div id="tProgress">Bienvenido</div>
                        </div>
                    </div>
                    <div id="tHeaderActions">
                        <button class="t-hbtn" id="tBtnMin" title="Minimizar"><i class="bx bx-minus" style="font-size:16px"></i></button>
                        <button class="t-hbtn" id="tBtnClose" title="Cerrar"><i class="bx bx-x" style="font-size:18px"></i></button>
                    </div>
                </div>
                <div id="tBody">
                    <div id="tWelcome" style="display:none">
                        <div class="tw-icon">
                            <div id="tLottieSlot"></div>
                        </div>
                        <h2>¡Bienvenido a iSeller!</h2>
                        <p>Te guiaremos paso a paso para dejar tu sistema listo y registrar tu primera venta en minutos.</p>
                        <div class="tw-hint">
                            <i class="bx bx-map-pin"></i>
                            <span>El tutorial te lleva por <strong>3 pasos clave</strong>: configurar tasas, agregar un producto y realizar tu primera venta.</span>
                        </div>
                        <button id="tStartBtn">Comenzar tutorial &rarr;</button>
                    </div>
                    <div id="tStepWrap" style="display:none">
                        <div id="tStepIcon"></div>
                        <div id="tStepLabel"></div>
                        <div id="tStepTitle"></div>
                        <div id="tStepDesc"></div>
                        <div id="tStepDetails" style="display:none"></div>
                        <div id="tStepResource" class="t-resource-card" style="display:none"></div>
                        <a id="tStepBtn">Ir al módulo</a>
                    </div>
                    <div id="tCompleted" style="display:none">
                        <div class="tc-icon"><i class="bx bx-check-circle"></i></div>
                        <h3>¡Tutorial básico completado!</h3>
                        <p>Ya tienes tu sistema listo. Has configurado tus tasas, registrado un producto y realizado tu primera venta.</p>
                        <div class="tc-tutorials-link">
                            <i class="bx bx-play-circle"></i>
                            <span>¿Quieres aprender más? Consulta nuestros</span>
                            <a href="tutoriales.php" class="tc-link">videos tutoriales</a>
                        </div>
                        <button id="tBtnFinish" class="tc-finish-btn" type="button">Entendido, finalizar</button>
                    </div>
                </div>
                <div id="tFooter"></div>
            </div>

            <!-- Modal Video Tutorial (Sin bordes) -->
            <div id="tutorialVideoModal" style="display:none">
                <div id="tVideoBackdrop"></div>
                <div id="tVideoContainer">
                    <button id="tVideoClose" title="Cerrar video" type="button"><i class="bx bx-x"></i></button>
                    <div class="t-video-wrapper">
                        <div id="tVideoLoader">
                            <div class="t-video-spinner"></div>
                            <span>Cargando video...</span>
                        </div>
                        <iframe id="tVideoIframe" src="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>

        
        <script>
            (function() {
                // Limpiar claves obsoletas que puedan bloquear la bienvenida en navegadores que ya abrieron la web
                try {
                    localStorage.removeItem('iseller_tutorial_welcomed');
                    sessionStorage.removeItem('tutorial_dismissed');
                } catch (e) {}

                var BASE = 'db/tutorial_state.php';
                var ICONS = [
                    '<i class="bx bx-transfer-alt" style="font-size:28px"></i>',
                    '<i class="bx bx-package" style="font-size:28px"></i>',
                    '<i class="bx bx-cart-add" style="font-size:28px"></i>'
                ];
                var overlay = document.getElementById('tutorialOverlay'),
                    backdrop = document.getElementById('tutorialBackdrop'),
                    panel = document.getElementById('tutorialPanel'),
                    tProgress = document.getElementById('tProgress'),
                    tWelcome = document.getElementById('tWelcome'),
                    tStepWrap = document.getElementById('tStepWrap'),
                    tCompleted = document.getElementById('tCompleted'),
                    tStepIcon = document.getElementById('tStepIcon'),
                    tStepLabel = document.getElementById('tStepLabel'),
                    tStepTitle = document.getElementById('tStepTitle'),
                    tStepDesc = document.getElementById('tStepDesc'),
                    tStepDetails = document.getElementById('tStepDetails'),
                    tStepResource = document.getElementById('tStepResource'),
                    tStepBtn = document.getElementById('tStepBtn'),
                    tFooter = document.getElementById('tFooter'),
                    tStartBtn = document.getElementById('tStartBtn'),
                    tBtnMin = document.getElementById('tBtnMin'),
                    tBtnClose = document.getElementById('tBtnClose'),
                    tBtnFinish = document.getElementById('tBtnFinish'),
                    videoModal = document.getElementById('tutorialVideoModal'),
                    videoBackdrop = document.getElementById('tVideoBackdrop'),
                    videoClose = document.getElementById('tVideoClose'),
                    videoLoader = document.getElementById('tVideoLoader'),
                    videoIframe = document.getElementById('tVideoIframe');
                var tutorialState = null;

                function openVideoModal(url) {
                    if (!videoIframe || !videoModal) return;
                    // Reset loader
                    if (videoLoader) {
                        videoLoader.classList.remove('t-loader-hidden');
                    }
                    videoIframe.classList.remove('t-video-ready');
                    videoIframe.src = url;
                    videoModal.style.display = 'block';
                }

                function closeVideoModal() {
                    if (!videoIframe || !videoModal) return;
                    videoIframe.src = '';
                    videoIframe.classList.remove('t-video-ready');
                    if (videoLoader) videoLoader.classList.remove('t-loader-hidden');
                    videoModal.style.display = 'none';
                }

                // Hide loader when iframe finishes loading
                if (videoIframe) {
                    videoIframe.addEventListener('load', function() {
                        if (videoModal && videoModal.style.display !== 'none' && videoIframe.src) {
                            videoIframe.classList.add('t-video-ready');
                            if (videoLoader) videoLoader.classList.add('t-loader-hidden');
                        }
                    });
                }

                if (videoClose) videoClose.onclick = closeVideoModal;
                if (videoBackdrop) videoBackdrop.onclick = closeVideoModal;
                window.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && videoModal && videoModal.style.display !== 'none') {
                        closeVideoModal();
                    }
                });

                function showBackdrop() {
                    if (!backdrop) return;
                    backdrop.style.display = 'block';
                    setTimeout(function() {
                        backdrop.classList.add('t-visible')
                    }, 10);
                }

                function hideBackdrop() {
                    if (!backdrop) return;
                    backdrop.classList.remove('t-visible');
                    setTimeout(function() {
                        backdrop.style.display = 'none'
                    }, 300);
                }

                function onPage(url) {
                    return window.location.pathname.endsWith('/' + url) || window.location.href.indexOf(url) > -1;
                }

                function setPos(on) {
                    if (on) {
                        panel.classList.remove('t-centered');
                        panel.classList.add('t-anchored');
                        hideBackdrop();
                    } else {
                        panel.classList.remove('t-anchored');
                        panel.classList.add('t-centered');
                        showBackdrop();
                    }
                }

                function renderDots(d, success) {
                    tFooter.innerHTML = '';
                    for (var i = 0; i < d.totalSteps; i++) {
                        var dot = document.createElement('div');
                        dot.className = 't-dot';
                        if (success) dot.classList.add('t-success');
                        else if (i < d.currentStep) dot.classList.add('t-done');
                        else if (i === d.currentStep) dot.classList.add('t-active');
                        tFooter.appendChild(dot);
                    }
                }

                function renderStep(d) {
                    var step = d.steps[d.currentStep];
                    if (!step) {
                        overlay.style.display = 'none';
                        return;
                    }
                    var op = onPage(step.url);
                    tWelcome.style.display = 'none';
                    tCompleted.style.display = 'none';
                    tStepWrap.style.display = '';
                    overlay.style.display = '';
                    tProgress.textContent = 'Paso ' + (d.currentStep + 1) + ' de ' + d.totalSteps;
                    tStepIcon.innerHTML = ICONS[d.currentStep] || '<i class="bx bx-clipboard"></i>';
                    tStepLabel.textContent = 'Paso ' + (d.currentStep + 1) + ' de ' + d.totalSteps;
                    tStepTitle.textContent = step.label;
                    tStepDesc.innerHTML = step.desc;
                    if (step.details && op) {
                        tStepDetails.innerHTML = step.details;
                        tStepDetails.style.display = '';
                    } else {
                        tStepDetails.style.display = 'none';
                    }

                    // Recurso Especial (Video Tutorial) — solo cuando panel está anclado (op = en la página del paso)
                    if (step.video_url && tStepResource && op) {
                        tStepResource.style.display = 'flex';
                        tStepResource.innerHTML = '<div class="t-resource-left">' +
                            '<div class="t-resource-play"><i class="bx bx-play" style="margin-left:2px;"></i></div>' +
                            '<div class="t-resource-info">' +
                            '<span class="t-resource-tag">RECURSO ESPECIAL</span>' +
                            '<span class="t-resource-title">Consultar video detallado</span>' +
                            '</div>' +
                            '</div>' +
                            '<i class="bx bx-chevron-right t-resource-arrow"></i>';
                        tStepResource.onclick = function() {
                            openVideoModal(step.video_url);
                        };
                    } else if (tStepResource) {
                        tStepResource.style.display = 'none';
                        tStepResource.onclick = null;
                    }

                    if (op) {
                        tStepBtn.classList.add('t-current');
                        tStepBtn.innerHTML = '<i class="bx bx-check"></i> Continuar';
                        tStepBtn.removeAttribute('href');
                        tStepBtn.onclick = advanceStep;
                    } else {
                        tStepBtn.classList.remove('t-current');
                        tStepBtn.innerHTML = '<i class="bx bx-right-arrow-alt"></i> Ir al m&oacute;dulo';
                        tStepBtn.href = step.url;
                        tStepBtn.onclick = null;
                    }
                    setPos(op);
                    renderDots(d, false);
                }

                function renderCompleted() {
                    tWelcome.style.display = 'none';
                    tStepWrap.style.display = 'none';
                    tCompleted.style.display = '';
                    overlay.style.display = '';
                    tProgress.textContent = '¡Completado!';
                    panel.classList.remove('t-anchored');
                    panel.classList.add('t-centered');
                    showBackdrop();
                    if (tutorialState) renderDots({
                        totalSteps: tutorialState.totalSteps,
                        currentStep: tutorialState.totalSteps
                    }, true);
                    sessionStorage.setItem('tutorial_done', '1');
                    fetch(BASE, {
                        method: 'POST'
                    }).catch(function() {});
                }

                function advanceStep() {
                    if (!tutorialState) {
                        loadTutorial();
                        return;
                    }
                    var cur = tutorialState.currentStep;
                    var pasoACompletar = cur + 1;

                    var fd = new FormData();
                    fd.append('accion', 'completar_paso');
                    fd.append('paso', pasoACompletar);

                    fetch(BASE, {
                            method: 'POST',
                            body: fd
                        })
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function() {
                            fetch(BASE).then(function(r) {
                                return r.json();
                            }).then(function(json) {
                                if (!json.value) return;
                                var d = json.data;
                                tutorialState = d;
                                if (d.completed) {
                                    renderCompleted();
                                    return;
                                }
                                renderStep(d);
                            });
                        })
                        .catch(function() {});
                }
                window.tutorialAvanzar = advanceStep;

                function loadLottie(cb) {
                    if (window.customElements && customElements.get('lottie-player')) {
                        cb();
                        return;
                    }
                    var s = document.createElement('script');
                    s.src = '../vendors/lottie/lottie-player.js';
                    s.onload = cb;
                    s.onerror = function() {
                        /* fallback icon already visible */
                    };
                    document.head.appendChild(s);
                }

                function showWelcome(d) {
                    overlay.style.display = '';
                    tStepWrap.style.display = 'none';
                    tCompleted.style.display = 'none';
                    tWelcome.style.display = '';
                    tProgress.textContent = 'Bienvenida';
                    panel.classList.remove('t-anchored');
                    panel.classList.add('t-centered');
                    showBackdrop();
                    renderDots(d, false);

                    // Icono fallback inmediato
                    var slot = document.getElementById('tLottieSlot');
                    if (slot) {
                        slot.innerHTML = '<i class="bx bx-store-alt" style="font-size:64px;color:var(--dash-mint,#2dd4a0);"></i>';
                        loadLottie(function() {
                            slot.innerHTML = '<lottie-player src="https://lottie.host/7a4696fa-7e20-4d1a-a8b0-83d31de4060b/Psu3cdYI3u.json" background="transparent" speed="0.85" style="width:120px;height:120px" loop autoplay></lottie-player>';
                        });
                    }
                }

                function loadTutorial() {
                    if (sessionStorage.getItem('tutorial_done')) return;

                    fetch(BASE)
                        .then(function(r) {
                            return r.json();
                        })
                        .then(function(json) {
                            if (!json.value) return;
                            var d = json.data;
                            tutorialState = d;

                            if (d.completed) {
                                sessionStorage.setItem('tutorial_done', '1');
                                return;
                            }

                            // Si estamos en el paso 0 (Tasas) y no hemos presionado "Comenzar" en esta sesión
                            if (d.currentStep === 0 && !sessionStorage.getItem('iseller_tutorial_started')) {
                                showWelcome(d);
                            } else {
                                renderStep(d);
                            }
                        })
                        .catch(function() {});
                }

                tStartBtn.addEventListener('click', function() {
                    sessionStorage.setItem('iseller_tutorial_started', '1');
                    var step1Url = (tutorialState && tutorialState.steps && tutorialState.steps[0]) ? tutorialState.steps[0].url : 'cambiar_tasas.php';
                    if (!onPage(step1Url)) {
                        window.location.href = step1Url;
                    } else {
                        if (tutorialState) renderStep(tutorialState);
                    }
                });

                tBtnMin.addEventListener('click', function() {
                    panel.classList.toggle('t-minimized');
                    var icon = tBtnMin.querySelector('i');
                    icon.className = panel.classList.contains('t-minimized') ? 'bx bx-chevron-up' : 'bx bx-minus';
                });

                tBtnClose.addEventListener('click', function() {
                    overlay.style.display = 'none';
                    hideBackdrop();
                });

                if (tBtnFinish) {
                    tBtnFinish.addEventListener('click', function() {
                        overlay.style.display = 'none';
                        hideBackdrop();
                    });
                }


                function startInit() {
                    if (sessionStorage.getItem('tutorial_done')) return;
                    loadTutorial();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', startInit);
                } else {
                    startInit();
                }
            })();
        </script>

    <?php endif; ?>
<?php
    return ob_get_clean();
}
?>