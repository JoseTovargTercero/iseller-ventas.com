<?php

function topnav()
{
    global $conexion;

    $nombre   = $_SESSION['nombre'] ?? 'Usuario';
    $inicial  = mb_strtoupper(mb_substr($nombre, 0, 1));
    $tipoUser = ($_SESSION['nivel'] == '1') ? 'Administrador' : 'Empleado';

    ob_start();

?>
    <script src='../assets/qrious.js'></script>
    <script src='../assets/qrcode.min.js'></script>

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

                <div class="nav-user-dropdown">
                    <div class="dropdown">
                        <a class="user-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="user-info">
                                <span class="user-name"><?php echo htmlspecialchars($nombre) ?></span>
                            </span>
                            <span class="user-avatar"><?php echo $inicial ?></span>
                            <ion-icon name="chevron-down-outline" class="user-chevron"></ion-icon>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
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

    <style>
        .page-title {
            margin-left: 85px;
        }

        .mobile-menu-toggle {
            display: none;
            color: var(--dash-text, #e8edf2);
            font-size: 28px;
            cursor: pointer;
            margin-left: 15px;
            margin-right: 15px;
        }

        @media (max-width: 768px) {
            .page-title {
                margin-left: 0 !important;
                font-size: 15px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 150px;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .nav-user-dropdown {
                margin-right: 5px !important;
            }
        }

        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9997;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .mobile-menu-overlay.active {
            display: block;
            opacity: 1;
        }

        .nav-user-dropdown {
            display: flex;
            align-items: center;
            margin-right: 12px;
        }

        .user-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            background: transparent;
            outline: transparent;
            cursor: pointer;
            padding: 10px;
            transition: all .2s ease;
            font-family: inherit;
        }

        .user-toggle:hover,
        .user-toggle:focus {
            background: rgba(255, 255, 255, 0);
            outline: none;
        }

        .user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }

        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--dash-text, #e8edf2);
        }

        .user-role {
            font-size: 10px;
            font-weight: 500;
            color: var(--dash-text-muted, #8892a0);
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .user-chevron {
            font-size: 14px;
            color: var(--dash-text-muted, #8892a0);
            transition: transform .2s ease;
        }

        .user-toggle[aria-expanded="true"] .user-chevron {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            background: var(--dash-card, #1e1d22) !important;
            border: 1px solid var(--dash-border, #2e353e) !important;
            border-radius: 12px !important;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .3) !important;
            min-width: 200px;
            margin-top: 6px;
            padding: 6px 0;
        }

        .dropdown-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px 8px;
            color: var(--dash-text, #e8edf2);
        }

        .dropdown-header strong {
            display: block;
            font-size: 13px;
            font-weight: 600;
        }

        .dropdown-header small {
            display: block;
            font-size: 10px;
            color: var(--dash-text-muted, #8892a0);
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .user-avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .dropdown-item {
            color: var(--dash-text-muted, #8892a0) !important;
            font-size: 13px;
            padding: 9px 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all .15s ease;
        }

        .dropdown-item:hover {
            color: var(--dash-danger, #ef5a6f) !important;
            background: rgba(239, 90, 111, .06) !important;
        }

        .dropdown-item ion-icon {
            font-size: 16px;
        }

        .dropdown-divider {
            border-color: var(--dash-border, #2e353e) !important;
            margin: 4px 0;
        }
    </style>
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
                    navbar.classList.toggle('open');
                    if (navbar.classList.contains('open')) {
                        overlay.classList.add('active');
                        document.body.style.overflow = 'hidden'; // Prevent scrolling
                    } else {
                        overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
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

    return ob_get_clean();
}
?>