<?php
require_once('includes/requires.php');

$topnav = topnav();
$bss_id = $_SESSION['bss_id'];

$stmtT = $conexion->prepare("SELECT slug, tiene_tienda_virtual, logo_url, nombre, nombre_visible FROM negocio WHERE id = ?");
$stmtT->bind_param("i", $bss_id);
$stmtT->execute();
$tienda = $stmtT->get_result()->fetch_assoc();
$stmtT->close();

if (!$tienda || !$tienda['tiene_tienda_virtual']) {
    header("Location: tienda_onboarding.php");
    exit;
}

$slug = $tienda['slug'] ?? '';
$nombre_tienda = trim($tienda['nombre_visible'] ?? '') !== '' ? $tienda['nombre_visible'] : ($tienda['nombre'] ?? '');
$nombre_usuario = trim($_SESSION['nombre'] ?? '');
$logo_src = !empty($tienda['logo_url']) ? '../../iseller_store/' . $tienda['logo_url'] : '';

// El QR apunta a la tienda pública: <raiz del sitio>/<slug>
$protocolo = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') ? 'http' : 'https';
$raiz = rtrim(str_replace('\\', '/', dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])))), '/');
$url_tienda = $protocolo . '://' . $_SERVER['HTTP_HOST'] . $raiz . '/' . rawurlencode($slug);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Publicidad — Tienda Virtual</title>
    <?php require_once('includes/headers.php'); ?>
    <style>
        .pub-page {
            padding: 24px;
        }

        .pub-tabs {
            display: flex;
            list-style: none;
            margin: 0 0 22px;
            padding: 0;
            border-bottom: 1px solid var(--dash-border);
        }

        .pub-tab {
            padding: 14px 24px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--dash-text-muted);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .pub-tab:hover {
            color: var(--dash-text);
        }

        .pub-tab.active {
            color: var(--dash-mint);
            border-bottom-color: var(--dash-mint);
        }

        .pub-panel {
            display: none;
        }

        .pub-panel.active {
            display: block;
        }

        .pub-layout {
            display: grid;
            grid-template-columns: minmax(300px, 420px) 1fr;
            gap: 24px;
            align-items: start;
        }

        .pub-card {
            background: var(--dash-card);
            border: 1px solid var(--dash-border);
            border-radius: 12px;
            padding: 22px;
        }

        .pub-card h2 {
            margin: 0 0 4px;
            font-size: 1.05rem;
            font-weight: 600;
            color: var(--dash-text);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pub-card h2 i {
            color: var(--dash-mint);
        }

        .pub-card>p {
            color: var(--dash-text-muted);
            font-size: 0.82rem;
            margin: 0 0 18px;
        }

        .pub-label {
            display: block;
            font-size: 0.78rem;
            color: var(--dash-text-muted);
            margin: 0 0 6px;
        }

        .pub-input {
            width: 100%;
            background: var(--dash-bg);
            border: 1px solid var(--dash-border);
            color: var(--dash-text);
            border-radius: 8px;
            padding: 9px 12px;
            font-size: 0.85rem;
            resize: none;
            margin-bottom: 14px;
        }

        .pub-input:focus {
            outline: none;
            border-color: var(--dash-mint);
        }

        .pub-url {
            font-size: 0.78rem;
            color: var(--dash-text-muted);
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 16px;
            overflow-wrap: anywhere;
        }

        .pub-url strong {
            color: var(--dash-mint);
        }

        .pub-acciones {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pub-preview {
            display: flex;
            justify-content: center;
        }

        #pubCanvas {
            width: 100%;
            max-width: 430px;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45);
        }

        /* Facebook Ads */
        .fb-wrap {
            max-width: 780px;
        }

        .fb-planes {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .fb-plan {
            position: relative;
            background: var(--dash-bg);
            border: 1.5px solid var(--dash-border);
            border-radius: 12px;
            padding: 18px;
            cursor: pointer;
            transition: border-color 0.18s ease, background 0.18s ease, transform 0.18s ease;
        }

        .fb-plan:hover {
            border-color: rgba(45, 212, 160, 0.5);
        }

        .fb-plan.active {
            border-color: var(--dash-mint);
            background: rgba(45, 212, 160, 0.06);
        }

        .fb-plan-badge {
            position: absolute;
            top: -10px;
            right: 14px;
            background: var(--dash-mint);
            color: #052e16;
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 2px 9px;
            border-radius: 99px;
        }

        .fb-plan-top {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .fb-plan-nombre {
            font-size: 0.92rem;
            font-weight: 600;
            color: var(--dash-text);
        }

        .fb-plan-precio {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--dash-mint);
        }

        .fb-plan-precio small {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--dash-text-muted);
        }

        .fb-plan-lista {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.8rem;
            color: var(--dash-text-muted);
        }

        .fb-plan-lista li {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .fb-plan-lista li:last-child {
            margin-bottom: 0;
        }

        .fb-plan-lista i {
            color: var(--dash-mint);
            font-size: 0.9rem;
        }

        .fb-wa-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
            background: rgba(45, 212, 160, 0.06);
            border: 1px solid rgba(45, 212, 160, 0.22);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 4px;
        }

        .fb-wa-info {
            font-size: 0.8rem;
            color: var(--dash-text-muted);
        }

        .fb-wa-info strong {
            color: var(--dash-text);
        }

        @media (max-width: 900px) {
            .pub-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .fb-planes {
                grid-template-columns: 1fr;
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
                <div class="pub-page">
                    <ul class="pub-tabs" id="pubTabs">
                        <li class="pub-tab active" data-panel="fisica">Publicidad Física</li>
                        <li class="pub-tab" data-panel="facebook">Facebook Ads</li>
                    </ul>

                    <div class="pub-panel active" id="panel-fisica">
                        <div class="pub-layout">
                            <div class="pub-card">
                                <h2><i class="bi bi-megaphone-fill"></i> Afiche para imprimir</h2>
                                <p>Formato A4. Incluye el código QR que lleva a tu tienda.</p>

                                <label class="pub-label" for="pubTitulo">Titular</label>
                                <textarea class="pub-input" id="pubTitulo" rows="2">Compra en línea y recíbelo en tu puerta</textarea>

                                <label class="pub-label" for="pubSubtitulo">Subtítulo</label>
                                <textarea class="pub-input" id="pubSubtitulo" rows="2">Mira nuestro catálogo, precios y ofertas desde tu teléfono.</textarea>

                                <div class="pub-url">
                                    El código QR apunta a:<br>
                                    <strong><?php echo htmlspecialchars($url_tienda); ?></strong>
                                </div>

                                <div class="pub-acciones">
                                    <button type="button" class="btn btn-success" id="pubPng">
                                        <i class="bi bi-file-earmark-image"></i> Descargar imagen
                                    </button>
                                    <button type="button" class="btn btn-outline-success" id="pubPdf">
                                        <i class="bi bi-file-earmark-pdf"></i> Descargar PDF
                                    </button>
                                </div>
                            </div>

                            <div class="pub-preview">
                                <canvas id="pubCanvas" width="1240" height="1754"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="pub-panel" id="panel-facebook">
                        <div class="fb-wrap">
                            <div class="pub-card">
                                <h2><i class="bi bi-facebook"></i> Publicidad en Facebook Ads</h2>
                                <p>Elige un plan de difusión pagada para atraer clientes locales directo a tu tienda online.</p>

                                <form id="fbAdsForm">
                                    <label class="pub-label">1. Selecciona un plan</label>
                                    <div class="fb-planes" id="fbPlanes">
                                        <div class="fb-plan active" data-plan="Plan 10 USDT (5 días — ~4.000 vistas diarias / ~20.000 totales)">
                                            <div class="fb-plan-top">
                                                <span class="fb-plan-nombre">Plan 5 Días</span>
                                                <span class="fb-plan-precio">10 <small>USDT</small></span>
                                            </div>
                                            <ul class="fb-plan-lista">
                                                <li><i class="bi bi-calendar-check"></i> 5 días de publicidad activa</li>
                                                <li><i class="bi bi-eye"></i> Media de ~4.000 vistas diarias</li>
                                                <li><i class="bi bi-graph-up-arrow"></i> ~20.000 vistas estimadas en total</li>
                                            </ul>
                                        </div>

                                        <div class="fb-plan" data-plan="Plan 20 USDT (10 días — ~4.000 vistas diarias / ~40.000 totales)">
                                            <span class="fb-plan-badge">Recomendado</span>
                                            <div class="fb-plan-top">
                                                <span class="fb-plan-nombre">Plan 10 Días</span>
                                                <span class="fb-plan-precio">20 <small>USDT</small></span>
                                            </div>
                                            <ul class="fb-plan-lista">
                                                <li><i class="bi bi-calendar-check"></i> 10 días de publicidad activa</li>
                                                <li><i class="bi bi-eye"></i> Media de ~4.000 vistas diarias</li>
                                                <li><i class="bi bi-graph-up-arrow"></i> ~40.000 vistas estimadas en total</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <label class="pub-label">2. Datos para coordinar</label>
                                    <label class="pub-label" for="fbNombre">Nombre de contacto</label>
                                    <input type="text" class="pub-input" id="fbNombre" required placeholder="Tu nombre" value="<?php echo htmlspecialchars($nombre_usuario); ?>">

                                    <label class="pub-label" for="fbNotas">¿Qué productos o promoción te gustaría destacar? (opcional)</label>
                                    <textarea class="pub-input" id="fbNotas" rows="3" placeholder="Ej: Destacar combos de la semana, ofertas o envíos a domicilio..."></textarea>

                                    <div class="fb-wa-bar">
                                        <div class="fb-wa-info">
                                            Atención directa por WhatsApp: <strong>+58 416 067 9095</strong><br>
                                            Al enviar se abrirá WhatsApp con los datos de tu tienda y el plan elegido para coordinar pago y anuncio.
                                        </div>
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-whatsapp"></i> Coordinar pago y publicidad
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function() {
            var PUB = {
                nombre: <?php echo json_encode($nombre_tienda); ?>,
                slug: <?php echo json_encode($slug); ?>,
                url: <?php echo json_encode($url_tienda); ?>,
                logo: <?php echo json_encode($logo_src); ?>
            };

            var MINT = '#2dd4a0';
            var TINTA = '#0f172a';
            var GRIS = '#475569';
            var FUENTE = 'system-ui, "Segoe UI", Arial, sans-serif';

            var canvas = document.getElementById('pubCanvas');
            var inputTitulo = document.getElementById('pubTitulo');
            var inputSubtitulo = document.getElementById('pubSubtitulo');

            var logoImg = null;
            var qrCanvas = null;

            // QRious se carga desde header.php con "defer", así que todavía no existe
            // cuando corre este script: el QR se crea la primera vez que se dibuja.
            function crearQR() {
                if (qrCanvas || typeof QRious === 'undefined') return;
                try {
                    qrCanvas = new QRious({
                        value: PUB.url,
                        size: 1024,
                        padding: 48,
                        level: 'M',
                        background: '#ffffff',
                        foreground: '#000000'
                    }).canvas;
                } catch (e) {
                    console.warn('No se pudo generar el código QR', e);
                }
            }

            if (PUB.logo) {
                logoImg = new Image();
                logoImg.onload = pedirPintado;
                logoImg.src = PUB.logo;
            }

            function redondearRect(ctx, x, y, w, h, r) {
                ctx.beginPath();
                if (ctx.roundRect) {
                    ctx.roundRect(x, y, w, h, r);
                } else {
                    ctx.rect(x, y, w, h);
                }
            }

            // Corta el texto con puntos suspensivos si no entra en maxAncho.
            function recortarTexto(ctx, texto, maxAncho) {
                texto = String(texto || '').trim();
                if (ctx.measureText(texto).width <= maxAncho) return texto;
                while (texto.length > 1 && ctx.measureText(texto + '…').width > maxAncho) {
                    texto = texto.slice(0, -1);
                }
                return texto + '…';
            }

            // Reparte el texto en líneas que quepan en maxAncho.
            function envolverTexto(ctx, texto, maxAncho, maxLineas) {
                var palabras = String(texto || '').trim().split(/\s+/).filter(Boolean);
                var lineas = [];
                var actual = '';

                for (var i = 0; i < palabras.length; i++) {
                    var prueba = actual ? actual + ' ' + palabras[i] : palabras[i];
                    if (!actual || ctx.measureText(prueba).width <= maxAncho) {
                        actual = prueba;
                        continue;
                    }
                    lineas.push(actual);
                    actual = palabras[i];
                    if (lineas.length === maxLineas) {
                        lineas[maxLineas - 1] = recortarTexto(ctx, lineas[maxLineas - 1], maxAncho);
                        return lineas;
                    }
                }
                if (actual) lineas.push(actual);
                return lineas;
            }

            // Dibuja los 3 íconos vectoriales de la franja de beneficios.
            function dibujarIconoBeneficio(ctx, tipo, cx, cy, u) {
                ctx.save();
                ctx.translate(cx, cy);
                ctx.scale(u, u);
                ctx.strokeStyle = '#0f6b58';
                ctx.fillStyle = '#0f6b58';
                ctx.lineWidth = 3.6;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';

                if (tipo === 'carrito') {
                    ctx.beginPath();
                    ctx.moveTo(-26, -20);
                    ctx.lineTo(-17, -20);
                    ctx.lineTo(-9, 10);
                    ctx.lineTo(20, 10);
                    ctx.lineTo(25, -11);
                    ctx.lineTo(-14, -11);
                    ctx.stroke();

                    ctx.beginPath();
                    ctx.arc(-6, 20, 4, 0, Math.PI * 2);
                    ctx.arc(16, 20, 4, 0, Math.PI * 2);
                    ctx.stroke();
                } else if (tipo === 'oferta') {
                    ctx.beginPath();
                    ctx.moveTo(2, -22);
                    ctx.lineTo(22, -22);
                    ctx.lineTo(22, -2);
                    ctx.lineTo(-4, 24);
                    ctx.lineTo(-24, 4);
                    ctx.closePath();
                    ctx.stroke();

                    ctx.beginPath();
                    ctx.arc(12, -12, 3.2, 0, Math.PI * 2);
                    ctx.fill();
                } else if (tipo === 'envio') {
                    // Caja del camión
                    ctx.strokeRect(-26, -16, 30, 24);
                    // Cabina
                    ctx.beginPath();
                    ctx.moveTo(4, -7);
                    ctx.lineTo(17, -7);
                    ctx.lineTo(26, 2);
                    ctx.lineTo(26, 8);
                    ctx.lineTo(4, 8);
                    ctx.closePath();
                    ctx.stroke();
                    // Ruedas
                    ctx.beginPath();
                    ctx.arc(-13, 14, 5.5, 0, Math.PI * 2);
                    ctx.arc(15, 14, 5.5, 0, Math.PI * 2);
                    ctx.fillStyle = '#e6f9f0';
                    ctx.fill();
                    ctx.stroke();
                }
                ctx.restore();
            }

            // Esquinas tipo visor alrededor de la tarjeta del QR.
            function dibujarEsquinasQR(ctx, x, y, lado, u) {
                var sep = 18 * u;
                var largo = 44 * u;
                var r = 14 * u;
                var x0 = x - sep;
                var y0 = y - sep;
                var x1 = x + lado + sep;
                var y1 = y + lado + sep;

                ctx.save();
                ctx.strokeStyle = '#0f6b58';
                ctx.lineWidth = 5 * u;
                ctx.lineCap = 'round';

                // Superior izquierda
                ctx.beginPath();
                ctx.moveTo(x0, y0 + largo);
                ctx.lineTo(x0, y0 + r);
                ctx.quadraticCurveTo(x0, y0, x0 + r, y0);
                ctx.lineTo(x0 + largo, y0);
                ctx.stroke();

                // Superior derecha
                ctx.beginPath();
                ctx.moveTo(x1 - largo, y0);
                ctx.lineTo(x1 - r, y0);
                ctx.quadraticCurveTo(x1, y0, x1, y0 + r);
                ctx.lineTo(x1, y0 + largo);
                ctx.stroke();

                // Inferior izquierda
                ctx.beginPath();
                ctx.moveTo(x0, y1 - largo);
                ctx.lineTo(x0, y1 - r);
                ctx.quadraticCurveTo(x0, y1, x0 + r, y1);
                ctx.lineTo(x0 + largo, y1);
                ctx.stroke();

                // Inferior derecha
                ctx.beginPath();
                ctx.moveTo(x1 - largo, y1);
                ctx.lineTo(x1 - r, y1);
                ctx.quadraticCurveTo(x1, y1, x1, y1 - r);
                ctx.lineTo(x1, y1 - largo);
                ctx.stroke();

                ctx.restore();
            }

            // Dibuja el afiche A4. Todo se mide en unidades de 1240px (A4 @150dpi) y se
            // escala con "u", así la previsualización y el archivo exportado son idénticos.
            function dibujarPoster(destino, ancho) {
                var u = ancho / 1240;
                var alto = Math.round(ancho * 297 / 210);
                destino.width = ancho;
                destino.height = alto;

                var ctx = destino.getContext('2d');
                crearQR();
                var centro = ancho / 2;

                // Fondo suave con degradado menta muy claro
                var gradFondo = ctx.createLinearGradient(0, 0, ancho, alto);
                gradFondo.addColorStop(0, '#f2fbf7');
                gradFondo.addColorStop(0.5, '#fbfefd');
                gradFondo.addColorStop(1, '#eefaf4');
                ctx.fillStyle = gradFondo;
                ctx.fillRect(0, 0, ancho, alto);

                // Onda decorativa superior izquierda
                var gradSup = ctx.createLinearGradient(0, 0, 360 * u, 380 * u);
                gradSup.addColorStop(0, '#99f6d4');
                gradSup.addColorStop(1, 'rgba(167, 243, 208, 0.15)');
                ctx.fillStyle = gradSup;
                ctx.beginPath();
                ctx.moveTo(0, 0);
                ctx.lineTo(350 * u, 0);
                ctx.quadraticCurveTo(170 * u, 160 * u, 0, 410 * u);
                ctx.closePath();
                ctx.fill();

                // Onda decorativa inferior derecha
                var gradInf = ctx.createLinearGradient(ancho, alto, ancho - 380 * u, alto - 380 * u);
                gradInf.addColorStop(0, '#99f6d4');
                gradInf.addColorStop(1, 'rgba(167, 243, 208, 0.15)');
                ctx.fillStyle = gradInf;
                ctx.beginPath();
                ctx.moveTo(ancho, alto);
                ctx.lineTo(ancho - 390 * u, alto);
                ctx.quadraticCurveTo(ancho - 170 * u, alto - 150 * u, ancho, alto - 370 * u);
                ctx.closePath();
                ctx.fill();

                ctx.textAlign = 'center';
                ctx.textBaseline = 'alphabetic';

                // Logo, o la inicial del negocio
                var radio = 64 * u;
                var cy = 148 * u;
                ctx.save();
                ctx.beginPath();
                ctx.arc(centro, cy, radio, 0, Math.PI * 2);
                ctx.clip();
                ctx.fillStyle = '#dcfce7';
                ctx.fillRect(centro - radio, cy - radio, radio * 2, radio * 2);
                if (logoImg && logoImg.complete && logoImg.naturalWidth) {
                    var escala = Math.max((2 * radio) / logoImg.naturalWidth, (2 * radio) / logoImg.naturalHeight);
                    var dw = logoImg.naturalWidth * escala;
                    var dh = logoImg.naturalHeight * escala;
                    ctx.drawImage(logoImg, centro - dw / 2, cy - dh / 2, dw, dh);
                } else {
                    ctx.fillStyle = '#0f6b58';
                    ctx.font = '800 ' + (68 * u) + 'px ' + FUENTE;
                    ctx.textBaseline = 'middle';
                    ctx.fillText((PUB.nombre || '?').trim().charAt(0).toUpperCase(), centro, cy + 4 * u);
                    ctx.textBaseline = 'alphabetic';
                }
                ctx.restore();

                ctx.beginPath();
                ctx.arc(centro, cy, radio, 0, Math.PI * 2);
                ctx.strokeStyle = '#34d399';
                ctx.lineWidth = 4.5 * u;
                ctx.stroke();

                // Nombre del negocio con espaciado entre letras + barra inferior
                ctx.fillStyle = '#0f6b58';
                ctx.font = '600 ' + (32 * u) + 'px ' + FUENTE;
                var nombreEspaciado = (PUB.nombre || '').toUpperCase().trim().split('').join(' ');
                ctx.fillText(recortarTexto(ctx, nombreEspaciado, ancho * 0.85), centro, 266 * u);

                ctx.fillStyle = '#6ee7b7';
                redondearRect(ctx, centro - 50 * u, 292 * u, 100 * u, 9 * u, 4.5 * u);
                ctx.fill();

                // Titular a dos tonos (primera línea oscura, segunda verde esmeralda)
                ctx.font = '800 ' + (74 * u) + 'px ' + FUENTE;
                var y = 404 * u;
                var lineasTitulo = envolverTexto(ctx, inputTitulo.value, ancho * 0.76, 2);
                for (var i = 0; i < lineasTitulo.length; i++) {
                    ctx.fillStyle = (i === 0) ? TINTA : '#0f6b58';
                    ctx.fillText(lineasTitulo[i], centro, y);
                    y += 86 * u;
                }

                // Subtítulo
                ctx.fillStyle = '#2d4452';
                ctx.font = '400 ' + (38 * u) + 'px ' + FUENTE;
                y += 6 * u;
                var lineasSub = envolverTexto(ctx, inputSubtitulo.value, ancho * 0.66, 2);
                for (var j = 0; j < lineasSub.length; j++) {
                    ctx.fillText(lineasSub[j], centro, y);
                    y += 50 * u;
                }

                // Franja de 3 beneficios con íconos y separadores
                var beneficios = [{
                        icono: 'carrito',
                        l1: 'Catálogo completo',
                        l2: 'y actualizado'
                    },
                    {
                        icono: 'oferta',
                        l1: 'Precios especiales',
                        l2: 'y ofertas'
                    },
                    {
                        icono: 'envio',
                        l1: 'Recibe en la puerta',
                        l2: 'de tu casa'
                    }
                ];
                var colsX = [275 * u, 620 * u, 965 * u];
                var iconY = 772 * u;

                ctx.strokeStyle = '#cdeee0';
                ctx.lineWidth = 2 * u;
                [447 * u, 793 * u].forEach(function(dx) {
                    ctx.beginPath();
                    ctx.moveTo(dx, 742 * u);
                    ctx.lineTo(dx, 875 * u);
                    ctx.stroke();
                });

                beneficios.forEach(function(b, idx) {
                    var bx = colsX[idx];
                    ctx.fillStyle = '#e6f9f0';
                    ctx.strokeStyle = '#c2eed8';
                    ctx.lineWidth = 2 * u;
                    ctx.beginPath();
                    ctx.arc(bx, iconY, 52 * u, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.stroke();

                    dibujarIconoBeneficio(ctx, b.icono, bx, iconY, u);

                    ctx.fillStyle = '#1e293b';
                    ctx.font = '500 ' + (26 * u) + 'px ' + FUENTE;
                    ctx.fillText(b.l1, bx, 862 * u);
                    ctx.fillText(b.l2, bx, 895 * u);
                });

                // Tarjeta del código QR con sombra suave y esquinas de escaneo
                var lado = 386 * u;
                var qx = centro - lado / 2;
                var qy = 975 * u;

                ctx.save();
                ctx.shadowColor = 'rgba(16, 185, 129, 0.22)';
                ctx.shadowBlur = 36 * u;
                ctx.shadowOffsetY = 12 * u;
                ctx.fillStyle = '#ffffff';
                redondearRect(ctx, qx, qy, lado, lado, 32 * u);
                ctx.fill();
                ctx.restore();

                ctx.strokeStyle = '#bbf7d0';
                ctx.lineWidth = 2.5 * u;
                redondearRect(ctx, qx, qy, lado, lado, 32 * u);
                ctx.stroke();

                dibujarEsquinasQR(ctx, qx, qy, lado, u);

                var margen = 36 * u;
                if (qrCanvas) {
                    ctx.drawImage(qrCanvas, qx + margen, qy + margen, lado - margen * 2, lado - margen * 2);
                } else {
                    ctx.fillStyle = '#94a3b8';
                    ctx.font = (30 * u) + 'px ' + FUENTE;
                    ctx.fillText('QR no disponible', centro, qy + lado / 2);
                }

                // Pastilla 1: ESCANEA Y COMPRA EN LÍNEA (con ícono de enlace)
                var p1W = 650 * u;
                var p1H = 72 * u;
                var p1X = centro - p1W / 2;
                var p1Y = 1435 * u;
                ctx.fillStyle = '#dff7ec';
                ctx.strokeStyle = '#bcebd6';
                ctx.lineWidth = 2 * u;
                redondearRect(ctx, p1X, p1Y, p1W, p1H, p1H / 2);
                ctx.fill();
                ctx.stroke();

                // Ícono de eslabón + divisor vertical
                ctx.save();
                ctx.translate(p1X + 54 * u, p1Y + p1H / 2);
                ctx.rotate(-Math.PI / 4);
                ctx.strokeStyle = '#0f6b58';
                ctx.lineWidth = 3.6 * u;
                redondearRect(ctx, -18 * u, -8 * u, 22 * u, 16 * u, 8 * u);
                ctx.stroke();
                redondearRect(ctx, -4 * u, -8 * u, 22 * u, 16 * u, 8 * u);
                ctx.stroke();
                ctx.restore();

                ctx.strokeStyle = '#9ee2c4';
                ctx.lineWidth = 2 * u;
                ctx.beginPath();
                ctx.moveTo(p1X + 94 * u, p1Y + 16 * u);
                ctx.lineTo(p1X + 94 * u, p1Y + p1H - 16 * u);
                ctx.stroke();

                ctx.fillStyle = '#0f6b58';
                ctx.font = '700 ' + (26 * u) + 'px ' + FUENTE;
                ctx.fillText('ESCANEA Y COMPRA EN LÍNEA', centro + 32 * u, p1Y + 46 * u);

                // Pastilla 2: URL de la tienda en verde oscuro
                var p2W = 700 * u;
                var p2H = 82 * u;
                var p2X = centro - p2W / 2;
                var p2Y = 1528 * u;
                ctx.fillStyle = '#0f6b58';
                redondearRect(ctx, p2X, p2Y, p2W, p2H, p2H / 2);
                ctx.fill();

                ctx.fillStyle = '#ffffff';
                ctx.font = '600 ' + (30 * u) + 'px ' + FUENTE;
                var urlLimpia = PUB.url.replace(/^https?:\/\//, '');
                ctx.fillText(recortarTexto(ctx, urlLimpia, p2W - 60 * u), centro, p2Y + 52 * u);
            }

            // 1240px = A4 a 150dpi (previsualización rápida al escribir)
            function pintarPreview() {
                dibujarPoster(canvas, 1240);
            }

            // 2480px = A4 a 300dpi (calidad de imprenta)
            function posterExportable() {
                var c = document.createElement('canvas');
                dibujarPoster(c, 2480);
                return c;
            }

            var pintadoPendiente = false;

            function pedirPintado() {
                if (pintadoPendiente) return;
                pintadoPendiente = true;
                requestAnimationFrame(function() {
                    pintadoPendiente = false;
                    pintarPreview();
                });
            }

            inputTitulo.addEventListener('input', pedirPintado);
            inputSubtitulo.addEventListener('input', pedirPintado);

            function archivoNombre(extension) {
                return 'publicidad-' + (PUB.slug || 'tienda') + '.' + extension;
            }

            function descargar(url, nombre) {
                var a = document.createElement('a');
                a.href = url;
                a.download = nombre;
                document.body.appendChild(a);
                a.click();
                a.remove();
            }

            document.getElementById('pubPng').addEventListener('click', function() {
                try {
                    descargar(posterExportable().toDataURL('image/png'), archivoNombre('png'));
                } catch (e) {
                    alert('No se pudo generar la imagen.');
                }
            });

            document.getElementById('pubPdf').addEventListener('click', async function() {
                var boton = this;
                var textoOriginal = boton.innerHTML;
                boton.disabled = true;
                boton.innerHTML = 'Generando PDF…';
                try {
                    var datos = new FormData();
                    datos.append('imagen', posterExportable().toDataURL('image/jpeg', 0.95));
                    datos.append('nombre', PUB.slug || '');
                    var respuesta = await fetch('api/tienda_publicidad_pdf.php', {
                        method: 'POST',
                        body: datos
                    });
                    if (!respuesta.ok) {
                        throw new Error(await respuesta.text());
                    }
                    var url = URL.createObjectURL(await respuesta.blob());
                    descargar(url, archivoNombre('pdf'));
                    setTimeout(function() {
                        URL.revokeObjectURL(url);
                    }, 4000);
                } catch (e) {
                    alert(e.message || 'No se pudo generar el PDF.');
                } finally {
                    boton.disabled = false;
                    boton.innerHTML = textoOriginal;
                }
            });

            // Pestañas
            document.getElementById('pubTabs').addEventListener('click', function(e) {
                var tab = e.target.closest('.pub-tab');
                if (!tab) return;
                document.querySelectorAll('.pub-tab').forEach(function(t) {
                    t.classList.remove('active');
                });
                document.querySelectorAll('.pub-panel').forEach(function(p) {
                    p.classList.remove('active');
                });
                tab.classList.add('active');
                document.getElementById('panel-' + tab.dataset.panel).classList.add('active');
            });

            // Facebook Ads: selección de plan y envío a WhatsApp (+58 416 067 9095)
            document.getElementById('fbPlanes').addEventListener('click', function(e) {
                var card = e.target.closest('.fb-plan');
                if (!card) return;
                document.querySelectorAll('.fb-plan').forEach(function(c) {
                    c.classList.remove('active');
                });
                card.classList.add('active');
            });

            document.getElementById('fbAdsForm').addEventListener('submit', function(e) {
                e.preventDefault();
                var planActivo = document.querySelector('.fb-plan.active');
                var plan = planActivo ? planActivo.getAttribute('data-plan') : 'Plan 10 USDT (5 días)';
                var nombre = document.getElementById('fbNombre').value.trim();
                var notas = document.getElementById('fbNotas').value.trim();

                var lineas = [
                    'Hola, quiero contratar publicidad en Facebook Ads para mi tienda en iSeller:',
                    '',
                    '• Tienda: ' + (PUB.nombre || PUB.slug),
                    '• Enlace: ' + PUB.url,
                    '• Plan elegido: ' + plan,
                    '• Solicita: ' + nombre
                ];
                if (notas) {
                    lineas.push('• Detalles / promoción: ' + notas);
                }
                var urlWa = 'https://wa.me/584160679095?text=' + encodeURIComponent(lineas.join('\n'));
                window.open(urlWa, '_blank', 'noopener');
            });

            // El primer pintado espera a que corran los scripts con defer (QRious).
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', pintarPreview);
            } else {
                pintarPreview();
            }
        })();
    </script>
    <script src="js/nombre_pagina.js?v=<?php echo @filemtime(__DIR__ . '/js/nombre_pagina.js'); ?>"></script>
</body>

</html>