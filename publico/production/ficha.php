<?php
require_once('includes/requires.php');

if ($_SESSION['nivel'] == 1 || $_SESSION['nivel'] == 2) {
    $topnav        = topnav();
    $bss_id        = $_SESSION['bss_id'];
    $idProducto    = intval($_GET['id'] ?? 0);
    $nivelUsuario  = $_SESSION['nivel'];
    $nombreUsuario = $_SESSION['nombre'];

    /* ─── Product info ──────────────────────────────────────────────
       Only fetch the columns we actually use.                        */
    $stmtP = $conexion->prepare(
        'SELECT nombre, codigo, cantidad_unidades, mayor, unidad FROM productos WHERE id = ? LIMIT 1'
    );
    $stmtP->bind_param('i', $idProducto);
    $stmtP->execute();
    $rowP      = $stmtP->get_result()->fetch_assoc();
    $stmtP->close();
    $nombreP   = $rowP['nombre'] ?? 'Producto';
    $categoria = '';
    $codigo    = $rowP['codigo'] ?? '';

    /* Datos de empaque: una caja/bulto puede traer varias unidades. */
    $unidadesCaja   = max(1, (float) ($rowP['cantidad_unidades'] ?? 1));
    $esProductoMayor = ((string) ($rowP['mayor'] ?? '0')) === '1';

    /* ─── Productos al mayor enlazados ─────────────────────────────
       La ficha del producto base suma también lo vendido en sus
       presentaciones al mayor.                                     */
    $idsVenta = [(int) $idProducto];
    $stmtMayor = $conexion->prepare(
        "SELECT id FROM productos WHERE bss_id = ? AND id_producto_relacionado = ? AND mayor = '1'"
    );
    $stmtMayor->bind_param('ii', $bss_id, $idProducto);
    $stmtMayor->execute();
    $resMayor = $stmtMayor->get_result();
    while ($m = $resMayor->fetch_assoc()) {
        $idsVenta[] = (int) $m['id'];
    }
    $stmtMayor->close();
    $idsVentaSql = implode(',', $idsVenta);

    $dia    = date('Y-m-d');
    $semana = date('o-W');
    $mes    = date('Y-m');
    $anio   = date('Y');

    /* ─── KPI: día / semana / mes ── 1 sola query ──────────────────
       Usamos agregación condicional para obtener los tres períodos
       en una única pasada sobre la tabla.  El descuento al mayor
       (status=4) se aplica directamente en SQL con CASE WHEN.       */
    $stmtKpi = $conexion->prepare(
        "SELECT
            /* ── Hoy ── */
            SUM(CASE WHEN DATE(o.created) = ? THEN
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END
            ELSE 0 END) AS dia_ventas,

            SUM(CASE WHEN DATE(o.created) = ? THEN
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END - oa.precio * oa.quantity
            ELSE 0 END) AS dia_ganancia,

            /* ── Esta semana ── */
            SUM(CASE WHEN DATE_FORMAT(o.created, '%x-%v') = ? THEN
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END
            ELSE 0 END) AS sem_ventas,

            SUM(CASE WHEN DATE_FORMAT(o.created, '%x-%v') = ? THEN
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END - oa.precio * oa.quantity
            ELSE 0 END) AS sem_ganancia,

            /* ── Este mes ── */
            SUM(CASE WHEN DATE_FORMAT(o.created, '%Y-%m') = ? THEN
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END
            ELSE 0 END) AS mes_ventas,

            SUM(CASE WHEN DATE_FORMAT(o.created, '%Y-%m') = ? THEN
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END - oa.precio * oa.quantity
            ELSE 0 END) AS mes_ganancia

         FROM orden o
         JOIN orden_articulos oa ON oa.order_id = o.id
         WHERE oa.product_id IN ($idsVentaSql)
           AND o.status IN (1, 4)"
    );
    $stmtKpi->bind_param('ssssss', $dia, $dia, $semana, $semana, $mes, $mes);
    $stmtKpi->execute();
    $kpiRow = $stmtKpi->get_result()->fetch_assoc();
    $stmtKpi->close();

    $kpiDia    = ['ventas' => round($kpiRow['dia_ventas']  ?? 0, 2), 'ganancia' => round($kpiRow['dia_ganancia']  ?? 0, 2)];
    $kpiSemana = ['ventas' => round($kpiRow['sem_ventas']  ?? 0, 2), 'ganancia' => round($kpiRow['sem_ganancia']  ?? 0, 2)];
    $kpiMes    = ['ventas' => round($kpiRow['mes_ventas']  ?? 0, 2), 'ganancia' => round($kpiRow['mes_ganancia']  ?? 0, 2)];

    /* ─── Chart: 12 meses del año ── 1 sola query ──────────────────
       GROUP BY mes; el motor agrupa y suma directamente.            */
    $mesesLabel = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    $stmtChart = $conexion->prepare(
        "SELECT
            DATE_FORMAT(o.created, '%m') AS mes_num,
            SUM(
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END
            ) AS ventas,
            SUM(
                CASE WHEN o.status = '4'
                    THEN oa.precio_venta_dolar * oa.quantity * (1 - o.descontado / 100)
                    ELSE oa.precio_venta_dolar * oa.quantity
                END - oa.precio * oa.quantity
            ) AS ganancia,
            SUM(
                CASE WHEN p.mayor = '1'
                    THEN oa.quantity * COALESCE(p.cantidad_unidades, 1)
                    ELSE oa.quantity
                END
            ) AS cantidad
         FROM orden o
         JOIN orden_articulos oa ON oa.order_id = o.id
         LEFT JOIN productos p ON p.id = oa.product_id
         WHERE oa.product_id IN ($idsVentaSql)
           AND o.ano = ?
           AND o.status IN (1, 4)
         GROUP BY mes_num"
    );
    $stmtChart->bind_param('s', $anio);
    $stmtChart->execute();
    $chartRes = $stmtChart->get_result();
    $stmtChart->close();

    // Inicializar arrays con ceros para los 12 meses
    $chartVentas    = array_fill(0, 12, 0);
    $chartGanancias = array_fill(0, 12, 0);
    $chartCantidad  = array_fill(0, 12, 0);

    while ($cr = $chartRes->fetch_assoc()) {
        $idx = intval($cr['mes_num']) - 1; // 0-indexed
        $chartVentas[$idx]    = round(floatval($cr['ventas']),   2);
        $chartGanancias[$idx] = round(floatval($cr['ganancia']), 2);
        $chartCantidad[$idx]  = round(floatval($cr['cantidad']), 2);
    }

    // Enero 2026 queda fuera de la evolución mensual.
    $mesOffset = (int) $anio === 2026 ? 1 : 0;
    if ($mesOffset) {
        $mesesLabel     = array_slice($mesesLabel, $mesOffset);
        $chartVentas    = array_slice($chartVentas, $mesOffset);
        $chartGanancias = array_slice($chartGanancias, $mesOffset);
        $chartCantidad  = array_slice($chartCantidad, $mesOffset);
    }

?>
    <!DOCTYPE html>
    <html lang="es">

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= htmlspecialchars($nombreP) ?> — Ficha de Producto</title>
        <?php require_once('includes/headers.php'); ?>

        <!-- Flatpickr date picker -->
        <link rel="stylesheet" href="../vendors/flatpickr/flatpickr.min.css">
        <script src="../vendors/flatpickr/flatpickr.min.js"></script>
        <script src="../vendors/flatpickr/es.js"></script>

        <style>
            /* ── Google Font ─────────────────────────────────────────── */
            @import url('../vendors/fonts/fonts.css');

            /* ── Reset / base ────────────────────────────────────────── */
            .ficha-wrap * {
                box-sizing: border-box;
            }

            .ficha-wrap {
                font-family: 'DM Sans', sans-serif;
                color: #e2e8f0;
                padding: 24px 20px 40px;
            }

            /* ── Page Header ─────────────────────────────────────────── */
            .ficha-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                margin-bottom: 32px;
                animation: fadeSlideDown .5s ease both;
            }

            .ficha-header-left h1 {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 26px;
                font-weight: 700;
                color: #f8fafc;
                margin: 0 0 4px;
                line-height: 1.2;
            }

            .ficha-header-left .sub {
                font-size: 13px;
                color: #64748b;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .badge-cat {
                display: inline-block;
                border: 1px solid rgba(99, 102, 241, .3);
                border-radius: 20px;
                padding: 2px 10px;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .04em;
            }

            .badge-code {
                display: inline-block;
                background: rgba(20, 184, 166, .12);
                color: #2dd4bf;
                border: 1px solid rgba(20, 184, 166, .25);
                border-radius: 20px;
                padding: 2px 10px;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .04em;
            }

            .badge-box {
                display: inline-block;
                background: rgba(251, 191, 36, .12);
                color: #fbbf24;
                border: 1px solid rgba(251, 191, 36, .25);
                border-radius: 20px;
                padding: 2px 10px;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: .04em;
            }

            /* ── KPI Grid ────────────────────────────────────────────── */
            .kpi-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 16px;
                margin-bottom: 28px;
            }

            .kpi-card {
                border: 1px solid rgba(255, 255, 255, .07);
                border-radius: 16px;
                padding: 20px 22px;
                position: relative;
                overflow: hidden;
                cursor: default;
                transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
                animation: fadeSlideUp .5s ease both;
            }

            .kpi-card:nth-child(1) {
                animation-delay: .05s;
            }

            .kpi-card:nth-child(2) {
                animation-delay: .10s;
            }

            .kpi-card:nth-child(3) {
                animation-delay: .15s;
            }

            .kpi-card:nth-child(4) {
                animation-delay: .20s;
            }

            .kpi-card:nth-child(5) {
                animation-delay: .25s;
            }

            .kpi-card:nth-child(6) {
                animation-delay: .30s;
            }

            .kpi-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 16px 40px rgba(0, 0, 0, .4);
                border-color: rgba(255, 255, 255, .14);
            }

            .kpi-card::before {
                content: '';
                position: absolute;
                inset: 0;
                border-radius: inherit;
                z-index: -1;
            }

            .kpi-glow {
                position: absolute;
                width: 90px;
                height: 90px;
                border-radius: 50%;
                filter: blur(30px);
                opacity: .35;
                top: -20px;
                right: -20px;
                pointer-events: none;
            }

            .kpi-card-label {
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: #94a3b8;
                margin-bottom: 10px;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .kpi-icon {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 14px;
            }

            .kpi-value {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 28px;
                font-weight: 700;
                color: #f1f5f9;
                line-height: 1;
                margin-bottom: 6px;
            }

            .kpi-sub {
                font-size: 12px;
                color: #94a3b8;
            }

            .kpi-sub span {
                font-weight: 600;
            }


            /* ── Panel ───────────────────────────────────────────────── */
            .ficha-panel {
                border: 1px solid rgba(255, 255, 255, .07);
                border-radius: 18px;
                overflow: hidden;
                animation: fadeSlideUp .5s ease .3s both;
            }

            .ficha-panel+.ficha-panel {
                margin-top: 20px;
            }

            .ficha-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                flex-wrap: wrap;
                gap: 12px;
                padding: 18px 22px;
                border-bottom: 1px solid rgba(255, 255, 255, .06);
            }

            .ficha-panel-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 15px;
                font-weight: 600;
                color: #f1f5f9;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .ficha-panel-title i {
                font-size: 16px;
            }

            .ficha-panel-body {
                padding: 22px;
            }

            /* ── Date Filter ─────────────────────────────────────────── */
            .filter-row {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .filter-field {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .filter-field label {
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: .07em;
                color: #64748b;
            }

            .filter-input {
                background: rgba(30, 41, 59, .8);
                border: 1px solid rgba(255, 255, 255, .1);
                border-radius: 10px;
                color: #e2e8f0;
                padding: 8px 14px;
                font-family: 'DM Sans', sans-serif;
                font-size: 14px;
                outline: none;
                cursor: pointer;
                transition: border-color .2s ease;
            }

            .filter-input:focus {
                border-color: #6366f1;
            }

            .filter-input::placeholder {
                color: #475569;
            }

            .btn-filter {
                background: linear-gradient(135deg, #2dd4a0, #25b88a);
                border: none;
                border-radius: 10px;
                color: #fff;
                font-family: 'DM Sans', sans-serif;
                font-size: 14px;
                font-weight: 600;
                padding: 9px 22px;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 6px;
                transition: opacity .2s ease, transform .15s ease;
                align-self: flex-end;
            }

            .btn-filter:hover {
                opacity: .9;
                transform: translateY(-1px);
            }

            .btn-filter:active {
                transform: translateY(0);
            }

            .btn-filter.loading {
                opacity: .7;
                pointer-events: none;
            }

            /* Preset chips */
            .preset-chips {
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                align-self: flex-end;
            }

            .preset-chip {
                background: rgba(30, 41, 59, .7);
                border: 1px solid rgba(255, 255, 255, .09);
                border-radius: 20px;
                color: #94a3b8;
                font-size: 12px;
                font-weight: 500;
                padding: 5px 12px;
                cursor: pointer;
                transition: background .2s ease, color .2s ease, border-color .2s ease;
            }

            .preset-chip:hover,
            .preset-chip.active {
                background: #2dd49f50;
                color: #2dd49ff5;
                border-color: #2dd49f7a;
            }

            /* ── Period Results Table ────────────────────────────────── */
            #period-results {
                margin-top: 18px;
            }

            .branch-block {
                margin-bottom: 24px;
            }

            .branch-block:last-child {
                margin-bottom: 0;
            }

            .branch-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 13px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .1em;
                color: #64748b;
                margin: 0 0 10px;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 6px;
            }

            .branch-title::after {
                content: '';
                flex: 1;
                height: 1px;
                background: rgba(255, 255, 255, .06);
            }

            .ficha-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }

            .ficha-table th {
                text-align: left;
                font-size: 11px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: #475569;
                padding: 10px 14px;
                border-bottom: 1px solid rgba(255, 255, 255, .06);
            }

            .ficha-table td {
                padding: 12px 14px;
                border-bottom: 1px solid rgba(255, 255, 255, .04);
                color: #cbd5e1;
                vertical-align: middle;
            }

            .ficha-table tr:last-child td {
                border-bottom: none;
            }

            .ficha-table tr:hover td {
                background: rgba(255, 255, 255, .03);
            }

            .num-big {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 20px;
                font-weight: 700;
                color: #f1f5f9;
            }

            .num-sub {
                font-size: 12px;
                color: #64748b;
            }

            .gain-pos {
                color: #34d399;
                font-weight: 600;
            }

            .gain-neg {
                color: #f87171;
                font-weight: 600;
            }

            /* ── Botón de desglose ───────────────────────────────────── */
            .btn-desglose {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: rgba(45, 212, 160, .10);
                border: 1px solid rgba(45, 212, 160, .28);
                border-radius: 8px;
                color: #2dd4a0;
                font-family: 'DM Sans', sans-serif;
                font-size: 12px;
                font-weight: 600;
                padding: 6px 12px;
                cursor: pointer;
                white-space: nowrap;
                transition: background .2s ease, border-color .2s ease, color .2s ease;
            }

            .btn-desglose:hover {
                background: rgba(45, 212, 160, .18);
                border-color: rgba(45, 212, 160, .45);
                color: #5eead4;
            }

            /* ── Modal de desglose ───────────────────────────────────── */
            .ficha-modal {
                background: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, .1) !important;
                border-radius: 16px !important;
                color: #e2e8f0;
                box-shadow: 0 24px 80px rgba(0, 0, 0, .55);
            }

            .ficha-modal .modal-header,
            .ficha-modal .modal-footer {
                border-color: rgba(255, 255, 255, .07);
                padding: 16px 22px;
            }

            .ficha-modal .modal-body {
                padding: 18px 22px 24px;
            }

            .ficha-modal .modal-title {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 17px;
                font-weight: 700;
                color: #f8fafc;
            }

            .ficha-modal .close {
                color: #94a3b8;
                opacity: .8;
                text-shadow: none;
            }

            .ficha-modal .close:hover {
                color: #f8fafc;
                opacity: 1;
            }

            .desglose-section {
                margin-top: 22px;
            }

            .desglose-section:first-child {
                margin-top: 0;
            }

            .desglose-section-title {
                display: flex;
                align-items: center;
                gap: 8px;
                margin: 0 0 10px;
                color: #64748b;
                font-family: 'Space Grotesk', sans-serif;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
            }

            .desglose-section-title::after {
                content: '';
                flex: 1;
                height: 1px;
                background: rgba(255, 255, 255, .06);
            }

            .ficha-modal .sales-table-wrap {
                border: 1px solid rgba(255, 255, 255, .06);
            }

            .ficha-modal .ficha-table th {
                background: rgba(15, 23, 42, .7);
            }

            /* ── Resumen por sucursal ────────────────────────────────── */
            .branch-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px;
                margin-bottom: 16px;
            }

            .branch-stat {
                border: 1px solid rgba(255, 255, 255, .06);
                border-radius: 12px;
                padding: 10px 12px;
                background: rgba(30, 41, 59, .35);
            }

            .branch-stat .lbl {
                display: block;
                font-size: 10px;
                font-weight: 600;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #64748b;
                margin-bottom: 3px;
            }

            .branch-stat .val {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 17px;
                font-weight: 700;
                color: #f1f5f9;
                line-height: 1.1;
            }

            .branch-stat .val small {
                font-size: 11px;
                font-weight: 500;
                color: #94a3b8;
            }

            /* ── Detalle de ventas ───────────────────────────────────── */
            .sales-table-wrap {
                overflow-x: auto;
                margin-top: 10px;
                border: 1px solid rgba(255, 255, 255, .05);
                border-radius: 12px;
            }

            .sales-table-wrap .ficha-table th {
                white-space: nowrap;
            }

            .sale-link {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                color: #818cf8;
                font-size: 12px;
                font-weight: 600;
                text-decoration: none;
                white-space: nowrap;
            }

            .sale-link:hover {
                color: #a5b4fc;
                text-decoration: underline;
            }

            .truncated-note {
                margin-top: 8px;
                font-size: 11px;
                color: #64748b;
            }

            /* loading / empty states */
            .state-loading,
            .state-empty {
                text-align: center;
                padding: 32px 0;
                color: #475569;
                font-size: 14px;
            }

            .state-loading i,
            .state-empty i {
                display: block;
                font-size: 28px;
                margin-bottom: 8px;
            }

            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .spin {
                animation: spin .8s linear infinite;
                display: inline-block;
            }

            /* ── Chart Panel ─────────────────────────────────────────── */
            #ficha-chart {
                min-height: 280px;
            }

            #stock-chart {
                min-height: 300px;
            }

            #stock-chart-empty {
                padding-top: 60px;
                padding-bottom: 60px;
            }

            .stock-panel-tools {
                display: flex;
                align-items: flex-end;
                gap: 12px;
                flex-wrap: wrap;
            }

            #stock-sucursal {
                min-width: 180px;
            }

            .stock-legend {
                display: flex;
                align-items: center;
                gap: 16px;
                flex-wrap: wrap;
                margin-bottom: 6px;
                font-size: 12px;
                color: #94a3b8;
            }

            .stock-legend span {
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }

            .stock-legend i {
                width: 10px;
                height: 10px;
                border-radius: 50%;
                display: inline-block;
            }

            .stock-current {
                margin-left: auto;
                font-weight: 700;
                color: #2dd4bf;
                font-size: 12px;
            }

            /* ── Animations ──────────────────────────────────────────── */
            @keyframes fadeSlideDown {
                from {
                    opacity: 0;
                    transform: translateY(-14px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeSlideUp {
                from {
                    opacity: 0;
                    transform: translateY(18px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* ── Flatpickr overrides ──────────────────────────────────── */
            .flatpickr-calendar {
                background: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, .1) !important;
                box-shadow: 0 20px 60px rgba(0, 0, 0, .5) !important;
                border-radius: 12px !important;
            }

            .flatpickr-day.selected,
            .flatpickr-day.startRange,
            .flatpickr-day.endRange {
                background: #6366f1 !important;
                border-color: #6366f1 !important;
            }

            .flatpickr-day.inRange {
                background: rgba(99, 102, 241, .2) !important;
                border-color: transparent !important;
            }

            .flatpickr-day {
                color: #94a3b8 !important;
            }

            .flatpickr-day:hover {
                background: rgba(99, 102, 241, .15) !important;
            }

            .flatpickr-months .flatpickr-month,
            .flatpickr-weekday,
            .numInputWrapper {
                color: #94a3b8 !important;
            }

            /* ══════════════════════════════════════════════════════════
               MOVIMIENTO GENERAL (línea de tiempo tipo git)
               ══════════════════════════════════════════════════════════ */
            .mov-presets {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .mov-preset {
                background: rgba(30, 41, 59, .7);
                border: 1px solid rgba(255, 255, 255, .09);
                border-radius: 20px;
                color: #94a3b8;
                font-size: 12px;
                font-weight: 500;
                padding: 5px 12px;
                cursor: pointer;
                transition: background .2s ease, color .2s ease, border-color .2s ease;
            }

            .mov-preset:hover,
            .mov-preset.active {
                background: rgba(45, 212, 160, .18);
                color: #2dd4a0;
                border-color: rgba(45, 212, 160, .45);
            }

            .mov-toolbar {
                display: flex;
                align-items: flex-end;
                gap: 12px;
                flex-wrap: wrap;
                margin-bottom: 16px;
            }

            .mov-toolbar .filter-field {
                flex: 0 0 auto;
            }

            .mov-toolbar .filter-input {
                min-width: 132px;
            }

            .mov-type-filter {
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
                margin-left: auto;
            }

            .mov-type-chip {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                border: 1px solid rgba(255, 255, 255, .1);
                border-radius: 20px;
                background: rgba(30, 41, 59, .55);
                color: #94a3b8;
                padding: 6px 12px;
                font-size: 12px;
                font-weight: 600;
                cursor: pointer;
                user-select: none;
                transition: all .18s ease;
            }

            .mov-type-chip .dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--tc);
                opacity: .45;
                transition: opacity .18s ease;
            }

            .mov-type-chip.active {
                color: var(--tc);
                border-color: var(--tc);
                background: rgba(255, 255, 255, .035);
            }

            .mov-type-chip.active .dot {
                opacity: 1;
                box-shadow: 0 0 8px var(--tc);
            }

            .mov-tools {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .mov-tool-btn {
                min-width: 36px;
                height: 34px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                border: 1px solid rgba(255, 255, 255, .1);
                border-radius: 9px;
                background: rgba(30, 41, 59, .6);
                color: #94a3b8;
                font-size: 12px;
                font-weight: 600;
                padding: 0 11px;
                cursor: pointer;
                transition: all .18s ease;
            }

            .mov-tool-btn:hover {
                color: #e2e8f0;
                border-color: rgba(255, 255, 255, .22);
                background: rgba(30, 41, 59, .95);
            }

            .mov-summary-wrap {
                overflow-x: auto;
                border: 1px solid rgba(255, 255, 255, .06);
                border-radius: 12px;
                margin-bottom: 16px;
            }

            .mov-summary {
                width: 100%;
                border-collapse: collapse;
                font-size: 12px;
                min-width: 700px;
            }

            .mov-summary th {
                text-align: right;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .06em;
                color: #475569;
                padding: 9px 10px;
                border-bottom: 1px solid rgba(255, 255, 255, .06);
                white-space: nowrap;
            }

            .mov-summary th:first-child,
            .mov-summary td:first-child {
                text-align: left;
                position: sticky;
                left: 0;
                background: #14171d;
                z-index: 1;
            }

            .mov-summary td {
                text-align: right;
                padding: 8px 10px;
                border-bottom: 1px solid rgba(255, 255, 255, .04);
                color: #cbd5e1;
                white-space: nowrap;
                font-variant-numeric: tabular-nums;
            }

            .mov-summary tr:last-child td {
                border-bottom: none;
            }

            .mov-summary .suc-name {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                font-weight: 600;
                color: #e2e8f0;
            }

            .mov-summary .suc-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: var(--lane, #64748b);
                flex: 0 0 8px;
            }

            .mov-summary .kpi-off {
                color: #475569;
            }

            .mov-summary .stock-now {
                color: #2dd4bf;
                font-weight: 700;
            }

            .mov-summary tfoot td {
                background: rgba(255, 255, 255, .025);
                color: #94a3b8;
                font-weight: 700;
                border-top: 1px solid rgba(255, 255, 255, .08);
            }

            .mov-hint {
                margin: 10px 0 0;
                font-size: 11px;
                color: #475569;
                display: flex;
                align-items: center;
                gap: 6px;
                flex-wrap: wrap;
            }

            .mov-graph-wrap {
                overflow: auto;
                max-height: 72vh;
                border: 1px solid rgba(255, 255, 255, .07);
                border-radius: 14px;
                background: rgba(0, 0, 0, .18);
                margin-top: 4px;
            }

            .mov-graph {
                position: relative;
                width: max-content;
                min-width: 100%;
                --cols-template: 170px 1fr;
            }

            .mov-grid {
                display: grid;
                grid-template-columns: var(--cols-template);
                width: 100%;
                min-width: max-content;
            }

            .mov-grid-head {
                position: sticky;
                top: 0;
                z-index: 7;
                background: #14171d;
                border-bottom: 1px solid rgba(255, 255, 255, .08);
            }

            .mov-corner {
                position: sticky;
                left: 0;
                z-index: 8;
                background: #14171d;
                padding: 12px 14px;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: .08em;
                text-transform: uppercase;
                color: #475569;
                border-right: 1px solid rgba(255, 255, 255, .06);
            }

            .mov-branch-head {
                position: relative;
                padding: 9px 10px 8px 33px;
                border-left: 1px solid rgba(255, 255, 255, .045);
                cursor: grab;
                user-select: none;
                transition: background .15s ease, box-shadow .15s ease, opacity .15s ease;
            }

            .mov-branch-head:hover {
                background: rgba(255, 255, 255, .025);
            }

            .mov-branch-head.dragging {
                opacity: .35;
            }

            .mov-branch-head.drop-target {
                box-shadow: inset 2px 0 0 #2dd4a0;
                background: rgba(45, 212, 160, .05);
            }

            .mov-lane-dot {
                position: absolute;
                left: 15px;
                top: 14px;
                width: 11px;
                height: 11px;
                border-radius: 50%;
                background: var(--lane);
                box-shadow: 0 0 0 3px color-mix(in srgb, var(--lane) 20%, transparent);
            }

            .mov-branch-top {
                display: flex;
                align-items: center;
                gap: 6px;
                min-width: 0;
            }

            .mov-drag {
                color: #475569;
                font-size: 13px;
                line-height: 1;
                cursor: grab;
                letter-spacing: -2px;
            }

            .mov-branch-name {
                font-size: 12px;
                font-weight: 700;
                color: #e2e8f0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mov-move-btns {
                margin-left: auto;
                display: inline-flex;
                gap: 2px;
                flex: 0 0 auto;
            }

            .mov-move-btns button {
                width: 18px;
                height: 18px;
                border: 1px solid rgba(255, 255, 255, .08);
                border-radius: 5px;
                background: transparent;
                color: #64748b;
                font-size: 12px;
                line-height: 1;
                padding: 0;
                cursor: pointer;
            }

            .mov-move-btns button:hover {
                color: #2dd4a0;
                border-color: rgba(45, 212, 160, .4);
            }

            .mov-branch-types {
                display: flex;
                gap: 3px;
                margin-top: 6px;
            }

            .mov-btype {
                width: 21px;
                height: 18px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                border: 1px solid rgba(255, 255, 255, .08);
                border-radius: 5px;
                background: transparent;
                color: #475569;
                font-size: 9px;
                font-weight: 800;
                padding: 0;
                cursor: pointer;
                transition: all .15s ease;
            }

            .mov-btype.on {
                color: var(--tc);
                border-color: color-mix(in srgb, var(--tc) 45%, transparent);
                background: transparent;
            }

            .mov-btype:hover {
                border-color: var(--tc);
            }

            .mov-branch-total {
                margin-top: 5px;
                font-size: 9.5px;
                color: #64748b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                font-variant-numeric: tabular-nums;
            }

            .mov-branch-total .off {
                opacity: .35;
                text-decoration: line-through;
            }

            .mov-row {
                position: relative;
                border-bottom: 1px solid rgba(255, 255, 255, .035);
            }

            .mov-row:nth-child(odd) {
                background: rgba(255, 255, 255, .012);
            }

            .mov-time {
                position: sticky;
                left: 0;
                z-index: 4;
                background: #14171d;
                padding: 9px 12px;
                display: flex;
                flex-direction: column;
                gap: 1px;
                border-right: 1px solid rgba(255, 255, 255, .06);
                min-width: 0;
            }

            .mov-time-date {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 11.5px;
                font-weight: 600;
                color: #cbd5e1;
                white-space: nowrap;
            }

            .mov-time-hour {
                font-size: 10.5px;
                color: #64748b;
                font-variant-numeric: tabular-nums;
            }

            .mov-time-type {
                margin-top: 3px;
                font-size: 9px;
                font-weight: 800;
                letter-spacing: .06em;
                text-transform: uppercase;
                color: var(--tc, #94a3b8);
            }

            .mov-cell {
                position: relative;
                min-height: 52px;
                padding: 7px 10px 7px 33px;
                border-left: 1px solid rgba(255, 255, 255, .03);
            }

            .mov-cell::before {
                content: '';
                position: absolute;
                left: 19px;
                top: 0;
                bottom: 0;
                width: 2px;
                background: var(--lane);
                opacity: .22;
            }

            .mov-dot {
                position: absolute;
                left: 15px;
                top: 20px;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                background: var(--tc);
                border: 2px solid #14171d;
                box-shadow: 0 0 0 2px color-mix(in srgb, var(--tc) 25%, transparent);
                z-index: 3;
            }

            .mov-node {
                position: relative;
                z-index: 2;
                border: 1px solid rgba(255, 255, 255, .06);
                border-left: 2px solid var(--tc);
                border-radius: 8px;
                padding: 5px 8px;
                background: #181b22;
                font-size: 11px;
                line-height: 1.35;
                color: #cbd5e1;
                box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
                min-width: 0;
            }

            .mov-node-top {
                display: flex;
                align-items: baseline;
                gap: 6px;
                flex-wrap: wrap;
            }

            .mov-node-qty {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 13px;
                font-weight: 700;
                color: var(--tc);
                white-space: nowrap;
            }

            .mov-node-title {
                font-weight: 700;
                color: #e2e8f0;
                font-size: 10.5px;
                text-transform: uppercase;
                letter-spacing: .03em;
            }

            .mov-node-meta {
                color: #64748b;
                font-size: 9.5px;
                margin-top: 1px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mov-node a {
                color: #93c5fd;
                text-decoration: none;
            }

            .mov-node a:hover {
                text-decoration: underline;
            }

            /* ── Grupo de ventas ───────────────────────────────────── */
            .mov-row.mov-group-row {
                cursor: pointer;
                background: transparent;
            }

            .mov-row.mov-group-row:hover {
                background: rgba(255, 255, 255, .035);
            }

            .mov-group-row:focus-visible {
                outline: 2px solid #2dd4a0;
                outline-offset: -2px;
            }

            .mov-group-time {
                border-left: 3px solid #60a5fa;
            }

            .mov-group-users {
                margin-top: 2px;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 2px 4px;
                max-width: 100%;
                font-size: 9.5px;
                line-height: 1.35;
                font-weight: 600;
                color: #94a3b8;
            }

            .mov-group-users > i {
                font-size: 9px;
                color: #64748b;
            }

            .mov-group-user-link {
                color: #94a3b8;
                text-decoration: none;
                white-space: nowrap;
            }

            .mov-group-user-link span {
                color: #64748b;
                font-weight: 500;
            }

            .mov-group-user-link:hover {
                color: #2dd4a0;
            }

            .mov-group-user-sep,
            .mov-group-user-more {
                color: #475569;
                font-weight: 500;
            }

            .mov-group-total {
                margin-top: 2px;
                font-family: 'Space Grotesk', sans-serif;
                font-size: 14px;
                font-weight: 700;
                color: #93c5fd;
                line-height: 1.15;
            }

            .mov-group-money {
                font-size: 11px;
                font-weight: 700;
                color: #34d399;
            }

            .mov-group-count {
                margin-top: 2px;
                font-size: 9px;
                font-weight: 600;
                letter-spacing: .03em;
                color: #64748b;
            }

            .mov-group-node {
                display: block;
                border: 1px solid rgba(255, 255, 255, .07);
                border-left: 3px solid #60a5fa;
                border-radius: 8px;
                background: #181b22;
                padding: 5px 8px;
                text-decoration: none;
                color: #cbd5e1;
                transition: background .15s ease, border-color .15s ease;
            }

            .mov-group-node:hover {
                background: #1f242c;
                border-color: rgba(255, 255, 255, .16);
                border-left-color: #60a5fa;
                color: #f1f5f9;
            }

            .mov-group-node-top {
                display: flex;
                align-items: baseline;
                justify-content: space-between;
                gap: 6px;
                flex-wrap: wrap;
            }

            .mov-group-node-qty {
                font-family: 'Space Grotesk', sans-serif;
                font-size: 13px;
                font-weight: 700;
                color: #93c5fd;
                white-space: nowrap;
            }

            .mov-group-node-title {
                font-size: 9.5px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .03em;
            }

            .mov-group-node-meta {
                margin-top: 1px;
                font-size: 10px;
                font-weight: 600;
                color: #34d399;
            }

            .mov-group-merma {
                display: inline-block;
                margin-top: 4px;
                border: 1px solid rgba(248, 113, 113, .28);
                border-radius: 5px;
                background: rgba(248, 113, 113, .08);
                color: #f87171;
                font-size: 9.5px;
                font-weight: 700;
                padding: 1px 6px;
                white-space: nowrap;
            }

            .mov-node--out {
                border-left-color: #f59e0b;
            }

            .mov-node--in {
                border-left-color: #22c55e;
            }

            .mov-traslado {
                --tc: #a78bfa;
                --tbg: rgba(167, 139, 250, .12);
            }

            .mov-compra {
                --tc: #34d399;
                --tbg: rgba(52, 211, 153, .12);
            }

            .mov-venta {
                --tc: #60a5fa;
                --tbg: rgba(96, 165, 250, .12);
            }

            .mov-merma {
                --tc: #f87171;
                --tbg: rgba(248, 113, 113, .12);
            }

            .mov-arcs {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                z-index: 1;
                pointer-events: none;
                overflow: visible;
            }

            .mov-arcs path {
                fill: none;
                stroke: #a78bfa;
                stroke-width: 1.7;
                opacity: .65;
            }

            .mov-arcs path.mov-arc-glow {
                stroke-width: 5;
                opacity: .10;
            }

            .mov-graph-status {
                padding: 26px 12px;
            }

            /* ── Responsive ──────────────────────────────────────────── */
            @media (max-width: 640px) {
                .mov-toolbar {
                    align-items: stretch;
                }

                .mov-type-filter {
                    margin-left: 0;
                    width: 100%;
                }

                .mov-tools {
                    width: 100%;
                    justify-content: flex-end;
                }

                .kpi-grid {
                    grid-template-columns: 1fr 1fr;
                }

                /* La tercera tarjeta quedaba sola y descolgada: ocupa la fila completa */
                .kpi-card:last-child {
                    grid-column: 1 / -1;
                }

                .ficha-header {
                    flex-direction: column;
                }

                /* Consultar a lo ancho, como en el resto del panel */
                .btn-filter {
                    width: 100%;
                    justify-content: center;
                }

                .filter-field {
                    width: 100%;
                }

                .branch-summary {
                    grid-template-columns: 1fr 1fr;
                }

                .stock-panel-tools {
                    width: 100%;
                }

                .stock-panel-tools .filter-field {
                    flex: 1;
                }

                #stock-sucursal {
                    width: 100%;
                    min-width: 0;
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
                    <div class="ficha-wrap">

                        <!-- ── Page Header ─────────────────────────────────── -->
                        <div class="ficha-header">
                            <div class="ficha-header-left">
                                <h1><?= htmlspecialchars($nombreP) ?></h1>
                                <div class="sub">
                                    <?php if ($codigo): ?>
                                        <span class="badge-code"><i class="fa fa-barcode"></i> <?= htmlspecialchars($codigo) ?></span>
                                    <?php endif; ?>
                                    <?php if ($categoria): ?>
                                        <span class="badge-cat"><?= htmlspecialchars($categoria) ?></span>
                                    <?php endif; ?>
                                    <?php if ($unidadesCaja > 1): ?>
                                        <span class="badge-box"><i class="fa fa-cube"></i> 1 caja = <?= rtrim(rtrim(number_format($unidadesCaja, 2, '.', ''), '0'), '.') ?> unidades</span>
                                    <?php endif; ?>
                                    <?php if ($esProductoMayor): ?>
                                        <span class="badge-box"><i class="fa fa-cubes"></i> Venta al mayor por caja</span>
                                    <?php endif; ?>
                                    <span>Ficha de producto</span>
                                </div>
                            </div>
                        </div>

                        <!-- ── KPI Cards ───────────────────────────────────── -->
                        <div class="kpi-grid">
                            <?php
                            $kpis = [
                                ['label' => 'Ventas Hoy',          'icon' => 'fa fa-sun-o',      'color' => 'amber',  'ventas' => $kpiDia['ventas'],    'ganancia' => $kpiDia['ganancia']],
                                ['label' => 'Ventas Esta Semana',  'icon' => 'fa fa-calendar',   'color' => 'blue',   'ventas' => $kpiSemana['ventas'], 'ganancia' => $kpiSemana['ganancia']],
                                ['label' => 'Ventas Este Mes',     'icon' => 'fa fa-chart-bar',  'color' => 'indigo', 'ventas' => $kpiMes['ventas'],    'ganancia' => $kpiMes['ganancia']],
                            ];
                            foreach ($kpis as $k): ?>
                                <div class="kpi-card <?= $k['color'] ?>">
                                    <div class="kpi-glow"></div>
                                    <div class="kpi-card-label">
                                        <span class="kpi-icon"><i class="<?= $k['icon'] ?>"></i></span>
                                        <?= $k['label'] ?>
                                    </div>
                                    <div class="kpi-value">$<?= number_format($k['ventas'], 2, '.', ',') ?></div>
                                    <div class="kpi-sub">
                                        Ganancia: <span class="<?= $k['ganancia'] >= 0 ? 'gain-pos' : 'gain-neg' ?>">
                                            $<?= number_format($k['ganancia'], 2, '.', ',') ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- ── Chart Panel ─────────────────────────────────── -->
                        <div class="ficha-panel">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-area-chart" style="color:#6366f1;"></i>
                                    Evolución Mensual <?= $anio ?>
                                </span>
                            </div>
                            <div class="ficha-panel-body" style="padding-bottom: 10px;">
                                <div id="ficha-chart"></div>
                            </div>
                        </div>

                        <!-- ── Stock Movement Chart ────────────────────────── -->
                        <div class="ficha-panel">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-line-chart" style="color:#38bdf8;"></i>
                                    Movimiento de stock por sucursal
                                </span>
                                <div class="stock-panel-tools">
                                    <div class="filter-field">
                                        <label for="stock-sucursal">Sucursal</label>
                                        <select id="stock-sucursal" class="filter-input" aria-label="Sucursal del gráfico">
                                            <option value="">Cargando…</option>
                                        </select>
                                    </div>
                                    <div class="preset-chips">
                                        <span class="preset-chip stock-chip" data-stock-days="7">7 días</span>
                                        <span class="preset-chip stock-chip active" data-stock-days="30">30 días</span>
                                        <span class="preset-chip stock-chip" data-stock-days="90">90 días</span>
                                        <span class="preset-chip stock-chip" data-stock-days="365">1 año</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ficha-panel-body">
                                <div class="stock-legend">
                                    <span title="Compras, compras con factura y traslados recibidos">
                                        <i style="background:#34d399;"></i> Ingresos
                                    </span>
                                    <span title="Ventas del producto en la sucursal">
                                        <i style="background:#60a5fa;"></i> Ventas
                                    </span>
                                    <span title="Traslados enviados a otras sucursales">
                                        <i style="background:#a78bfa;"></i> Salidas
                                    </span>
                                    <span title="Mermas y pérdidas registradas">
                                        <i style="background:#f87171;"></i> Mermas
                                    </span>
                                    <span id="stock-current" class="stock-current"></span>
                                </div>
                                <div id="stock-chart"></div>
                                <div id="stock-chart-empty" class="state-empty" style="display:none;">
                                    <i class="fa fa-inbox"></i>Sin movimientos en este período
                                </div>
                            </div>
                        </div>

                        <!-- ── Movimiento general (compras, ventas, traslados y mermas) ── -->
                        <div class="ficha-panel" id="panel-mov-general">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-code-fork" style="color:#a78bfa;"></i>
                                    Movimiento general
                                </span>
                                <div class="mov-presets">
                                    <span class="mov-preset" data-mov-days="7">7 días</span>
                                    <span class="mov-preset active" data-mov-days="30">30 días</span>
                                    <span class="mov-preset" data-mov-days="90">90 días</span>
                                    <span class="mov-preset" data-mov-days="365">1 año</span>
                                </div>
                            </div>
                            <div class="ficha-panel-body">
                                <div class="mov-toolbar">
                                    <div class="filter-field">
                                        <label>Desde</label>
                                        <input id="mov-desde" class="filter-input" type="text"
                                            value="<?= date('Y-m-d', strtotime('-29 days')) ?>">
                                    </div>
                                    <div class="filter-field">
                                        <label>Hasta</label>
                                        <input id="mov-hasta" class="filter-input" type="text"
                                            value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <button class="btn-filter" id="mov-btn-consultar">
                                        <i class="fa fa-search"></i> Consultar
                                    </button>
                                    <div class="mov-type-filter" id="mov-type-filter"></div>
                                    <div class="mov-tools">
                                        <button type="button" class="mov-tool-btn" id="mov-btn-sort"
                                            title="Cambiar el orden de las fechas">
                                            <i class="fa fa-sort-amount-desc"></i>
                                            <span id="mov-sort-label">Recientes</span>
                                        </button>
                                        <button type="button" class="mov-tool-btn" id="mov-btn-reset"
                                            title="Restablecer columnas, filtros y orden">
                                            <i class="fa fa-undo"></i>
                                        </button>
                                    </div>
                                </div>

                                <div id="mov-summary"></div>
                                <div id="mov-note" class="mov-hint" style="display:none;"></div>

                                <div id="mov-status" class="state-loading mov-graph-status">
                                    <i class="fa fa-circle-o-notch spin"></i>
                                    Cargando movimientos…
                                </div>

                                <div id="mov-graph-wrap" class="mov-graph-wrap" style="display:none;">
                                    <div id="mov-graph" class="mov-graph">
                                        <svg id="mov-arcs" class="mov-arcs" aria-hidden="true"></svg>
                                        <div id="mov-head" class="mov-grid mov-grid-head"></div>
                                        <div id="mov-rows"></div>
                                    </div>
                                </div>

                                <p class="mov-hint">
                                    <i class="fa fa-info-circle"></i>
                                    Arrastra los encabezados o usa ‹ › para reordenar las sucursales.
                                    Los botones C / V / T / M activan o desactivan los KPIs y movimientos de esa sucursal.
                                    La columna <strong>Stock actual</strong> muestra la existencia de hoy y no depende del período.
                                </p>
                            </div>
                        </div>

                        <!-- ── Period Filter Panel ─────────────────────────── -->
                        <div class="ficha-panel">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-filter" style="color:#14b8a6;"></i>
                                    Ventas por Período
                                </span>
                            </div>
                            <div class="ficha-panel-body">
                                <div class="filter-row">
                                    <div class="filter-field">
                                        <label>Desde</label>
                                        <input id="fp-desde" class="filter-input" type="text"
                                            placeholder="<?= date('Y-m-01') ?>" value="<?= date('Y-m-01') ?>">
                                    </div>
                                    <div class="filter-field">
                                        <label>Hasta</label>
                                        <input id="fp-hasta" class="filter-input" type="text"
                                            placeholder="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="preset-chips">
                                        <span class="preset-chip" data-preset="today">Hoy</span>
                                        <span class="preset-chip" data-preset="week">Esta semana</span>
                                        <span class="preset-chip active" data-preset="month">Este mes</span>
                                        <span class="preset-chip" data-preset="quarter">Trimestre</span>
                                        <span class="preset-chip" data-preset="year">Este año</span>
                                    </div>
                                    <button class="btn-filter" id="btn-filtrar">
                                        <i class="fa fa-search"></i> Consultar
                                    </button>
                                </div>
                                <div id="period-results">
                                    <div class="state-loading">
                                        <i class="fa fa-circle-o-notch spin"></i>
                                        Cargando datos…
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /ficha-wrap -->
                </div><!-- /right_col -->

            </div><!-- /main_container -->
        </div><!-- /container body -->

        <!-- Modal: desglose por sucursal -->
        <div class="modal fade" id="modalDesglose" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content ficha-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="desgloseTitulo">Desglose por sucursal</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="desgloseContenido"></div>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <script src="../vendors/jquery/dist/jquery.min.js"></script>
        <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
        <script src="../vendors/fastclick/lib/fastclick.js"></script>
        <script src="../vendors/nprogress/nprogress.js"></script>
        <script src="../build/js/custom.js"></script>
        <script src="../vendors/apexcharts/apexcharts.min.js"></script>
        <script src="js/nombre_pagina.js"></script>

        <script>
            /* ── ApexCharts ─────────────────────────────────────────────────── */
            (function() {
                const esMovil = window.innerWidth < 640;
                const ventasSeries = <?= json_encode($chartVentas) ?>;
                const gananciasSeries = <?= json_encode($chartGanancias) ?>;
                const cantidadSeries = <?= json_encode($chartCantidad) ?>;
                const meses = <?= json_encode($mesesLabel) ?>;
                const mesActual = <?= (int) date('n') - 1 - $mesOffset ?>; // 0-indexed

                // Highlight current month
                const barColors = meses.map((_, i) =>
                    i === mesActual ? '#6366f1' : 'rgba(99,102,241,.35)'
                );

                const opts = {
                    series: [{
                            name: 'Ventas ($)',
                            type: 'bar',
                            data: ventasSeries
                        },
                        {
                            name: 'Ganancia ($)',
                            type: 'bar',
                            data: gananciasSeries
                        },
                        {
                            name: 'Unidades',
                            type: 'line',
                            data: cantidadSeries
                        },
                    ],
                    chart: {
                        type: 'bar',
                        height: 270,
                        background: 'transparent',
                        toolbar: {
                            show: false
                        },
                        fontFamily: "'DM Sans', sans-serif",
                    },
                    plotOptions: {
                        bar: {
                            borderRadius: 6,
                            columnWidth: '55%',
                            endingShape: 'rounded'
                        },
                    },
                    stroke: {
                        width: [0, 0, 2.5],
                        curve: 'smooth'
                    },
                    markers: {
                        size: [0, 0, 4],
                        colors: ['#2dd4bf'],
                        strokeColors: '#0f172a',
                        strokeWidth: 2
                    },
                    colors: ['#6366f1', '#34d399', '#2dd4bf'],
                    dataLabels: {
                        enabled: false
                    },
                    xaxis: {
                        categories: meses,
                        labels: {
                            /* En teléfono los 12 meses se pisaban: menos marcas y más chicas */
                            tickAmount: esMovil ? 6 : undefined,
                            rotate: esMovil ? 0 : -45,
                            style: {
                                colors: '#64748b',
                                fontSize: esMovil ? '10px' : '12px'
                            }
                        },
                        axisBorder: {
                            color: 'rgba(255,255,255,.06)'
                        },
                        axisTicks: {
                            color: 'rgba(255,255,255,.06)'
                        },
                    },
                    yaxis: [{
                            labels: {
                                style: {
                                    colors: '#64748b'
                                },
                                formatter: v => '$' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v.toFixed(0))
                            },
                            axisBorder: {
                                show: false
                            },
                        },
                        {
                            show: false
                        },
                        {
                            opposite: true,
                            labels: {
                                style: {
                                    colors: '#64748b'
                                },
                                formatter: v => v.toFixed(0) + ' u'
                            },
                        },
                    ],
                    grid: {
                        borderColor: 'rgba(255,255,255,.04)',
                        strokeDashArray: 4
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            colors: '#94a3b8'
                        },
                        markers: {
                            radius: 4
                        },
                    },
                    tooltip: {
                        theme: 'dark',
                        y: [{
                                formatter: v => '$' + Number(v).toFixed(2)
                            },
                            {
                                formatter: v => '$' + Number(v).toFixed(2)
                            },
                            {
                                formatter: v => v + ' unidades'
                            },
                        ],
                    },
                    theme: {
                        mode: 'dark'
                    },
                };

                const chart = new ApexCharts(document.querySelector('#ficha-chart'), opts);
                chart.render();
            })();

            /* ── Stock movement chart ───────────────────────────────────────── */
            (function() {
                const productoId = <?= $idProducto ?>;
                const chartEl = document.querySelector('#stock-chart');
                const emptyEl = document.querySelector('#stock-chart-empty');
                const sucursalSelect = document.getElementById('stock-sucursal');
                let stockChart = null;
                let stockDiasActual = 30;
                let stockRequestId = 0;

                // El gráfico siempre trabaja con una sucursal. La primera carga
                // deja seleccionada la sucursal principal que devuelve el servidor.
                function pintarSucursales(data) {
                    if (!sucursalSelect) return;

                    const lista = data.sucursales || [];
                    const actual = data.sucursal && data.sucursal.id !== undefined
                        ? String(data.sucursal.id)
                        : '';

                    const opciones = lista.map((s) =>
                        `<option value="${esc(s.id)}">${esc(s.nombre)}</option>`
                    ).join('');

                    if (sucursalSelect.dataset.opciones !== opciones) {
                        sucursalSelect.innerHTML = opciones;
                        sucursalSelect.dataset.opciones = opciones;
                    }
                    if (actual !== '') {
                        sucursalSelect.value = actual;
                    }
                }

                // Una sola línea con el stock real: cada movimiento se ordena por su hora.
                function fechaEtiqueta(iso) {
                    if (!iso) return '';
                    const partes = String(iso).split('-');
                    return partes.length === 3 ? partes[2] + '/' + partes[1] + '/' + partes[0] : iso;
                }

                function construirLineaSegmentada(data) {
                    const eventos = data.eventos || [];
                    const stockInicial = Number(data.stock_inicial || 0);
                    const desde = data.desde || '';

                    const puntos = [{
                        label: fechaEtiqueta(desde),
                        y: stockInicial,
                        tipo: 'neutro'
                    }];
                    let acumulado = stockInicial;
                    let ultimaFecha = desde;

                    eventos.forEach(function(evento) {
                        const fechaEvento = String(evento.fecha_evento || '');
                        const fechaDia = fechaEvento.slice(0, 10);
                        const etiqueta = fechaDia && fechaDia !== ultimaFecha ? fechaEtiqueta(fechaDia) : '';
                        const cantidad = Number(evento.cantidad || 0);

                        acumulado += evento.tipo === 'ingreso' ? cantidad : -cantidad;
                        puntos.push({
                            label: etiqueta,
                            y: Math.round(acumulado * 100) / 100,
                            tipo: evento.tipo
                        });
                        if (fechaDia) ultimaFecha = fechaDia;
                    });

                    const total = puntos.length;
                    const datos = {
                        ingreso: Array(total).fill(null),
                        venta: Array(total).fill(null),
                        salida: Array(total).fill(null),
                        merma: Array(total).fill(null),
                        neutro: Array(total).fill(null)
                    };

                    for (let j = 1; j < total; j++) {
                        const tipo = puntos[j].tipo;
                        if (!datos[tipo]) continue;
                        datos[tipo][j - 1] = puntos[j - 1].y;
                        datos[tipo][j] = puntos[j].y;
                    }

                    return {
                        categories: puntos.map(p => p.label),
                        series: [
                            { name: 'Ingresos', data: datos.ingreso },
                            { name: 'Ventas', data: datos.venta },
                            { name: 'Salidas', data: datos.salida },
                            { name: 'Mermas', data: datos.merma },
                            { name: 'Sin movimiento', data: datos.neutro }
                        ]
                    };
                }

                function renderStockChart(data) {
                    const unidad = data.unidad || 'UND';
                    const stockActualEl = document.getElementById('stock-current');
                    const stockActual = Number(data.stock_actual || 0);
                    const sucursalNombre = data.sucursal && data.sucursal.nombre
                        ? ' · ' + data.sucursal.nombre
                        : '';
                    if (stockActualEl) {
                        stockActualEl.textContent = 'Stock actual' + sucursalNombre + ': ' +
                            stockActual.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) +
                            ' ' + unidad;
                    }

                    if (!(data.eventos || []).length) {
                        if (stockChart) {
                            stockChart.destroy();
                            stockChart = null;
                        }
                        chartEl.innerHTML = '';
                        emptyEl.style.display = 'block';
                        emptyEl.innerHTML = '<i class="fa fa-inbox"></i>Sin movimientos en este período';
                        return;
                    }

                    emptyEl.style.display = 'none';
                    const segmentada = construirLineaSegmentada(data);
                    const ticks = Math.max(2, Math.min(12, segmentada.categories.length));

                    if (stockChart) {
                        stockChart.updateOptions({
                            xaxis: {
                                categories: segmentada.categories,
                                labels: {
                                    tickAmount: ticks
                                }
                            }
                        });
                        stockChart.updateSeries(segmentada.series);
                        return;
                    }

                    stockChart = new ApexCharts(chartEl, {
                        series: segmentada.series,
                        chart: {
                            type: 'line',
                            height: 320,
                            background: 'transparent',
                            toolbar: {
                                show: false
                            },
                            fontFamily: "'DM Sans', sans-serif",
                        },
                        colors: ['#34d399', '#60a5fa', '#a78bfa', '#f87171', 'rgba(148,163,184,.4)'],
                        stroke: {
                            curve: 'smooth',
                            width: 3
                        },
                        markers: {
                            size: 3,
                            strokeWidth: 2,
                            strokeColors: '#0f172a',
                            hover: {
                                size: 6
                            }
                        },
                        dataLabels: {
                            enabled: false
                        },
                        xaxis: {
                            categories: segmentada.categories,
                            labels: {
                                tickAmount: ticks,
                                rotate: -45,
                                style: {
                                    colors: '#64748b',
                                    fontSize: '11px'
                                }
                            },
                            axisBorder: {
                                color: 'rgba(255,255,255,.06)'
                            },
                            axisTicks: {
                                color: 'rgba(255,255,255,.06)'
                            },
                        },
                        yaxis: {
                            title: {
                                text: 'Stock (' + unidad + ')',
                                style: {
                                    color: '#64748b',
                                    fontSize: '11px'
                                }
                            },
                            labels: {
                                style: {
                                    colors: '#64748b'
                                },
                                formatter: v => Number(v).toLocaleString('es-VE', { maximumFractionDigits: 2 }) + ' ' + unidad
                            },
                        },
                        grid: {
                            borderColor: 'rgba(255,255,255,.04)',
                            strokeDashArray: 4
                        },
                        legend: {
                            show: false
                        },
                        tooltip: {
                            theme: 'dark',
                            shared: false,
                            y: {
                                formatter: v => Number(v).toLocaleString('es-VE', { maximumFractionDigits: 2 }) + ' ' + unidad
                            }
                        },
                        annotations: {
                            yaxis: [{
                                y: 0,
                                borderColor: 'rgba(255,255,255,.16)',
                                strokeDashArray: 4
                            }]
                        },
                        theme: {
                            mode: 'dark'
                        },
                    });
                    stockChart.render();
                }

                function cargarMovimientosStock(dias) {
                    stockDiasActual = Math.max(1, Number(dias) || stockDiasActual || 30);
                    const requestId = ++stockRequestId;

                    const params = new URLSearchParams({
                        id: productoId,
                        dias: stockDiasActual
                    });
                    if (sucursalSelect && sucursalSelect.value) {
                        params.set('sucursal', sucursalSelect.value);
                    }

                    fetch('ficha_movimientos_stock.php?' + params.toString())
                        .then(r => r.json())
                        .then(data => {
                            if (requestId !== stockRequestId) return;
                            if (!data.ok) throw new Error(data.error || 'No se pudieron cargar los movimientos');
                            pintarSucursales(data);
                            renderStockChart(data);
                        })
                        .catch(error => {
                            if (requestId !== stockRequestId) return;
                            if (stockChart) {
                                stockChart.destroy();
                                stockChart = null;
                            }
                            chartEl.innerHTML = '';
                            emptyEl.style.display = 'block';
                            emptyEl.innerHTML = '<i class="fa fa-exclamation-triangle"></i>' + (error.message || 'No se pudieron cargar los movimientos');
                        });
                }

                document.querySelectorAll('.stock-chip').forEach(chip => {
                    chip.addEventListener('click', function() {
                        document.querySelectorAll('.stock-chip').forEach(c => c.classList.remove('active'));
                        this.classList.add('active');
                        cargarMovimientosStock(this.dataset.stockDays);
                    });
                });

                if (sucursalSelect) {
                    sucursalSelect.addEventListener('change', function() {
                        cargarMovimientosStock(stockDiasActual);
                    });
                }

                cargarMovimientosStock(30);
            })();

            /* ── Flatpickr ──────────────────────────────────────────────────── */
            flatpickr('#fp-desde', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                defaultDate: '<?= date('Y-m-01') ?>',
            });
            flatpickr('#fp-hasta', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                defaultDate: '<?= date('Y-m-d') ?>',
            });

            /* ── Utilidades de formato ──────────────────────────────────────── */
            const nf2 = new Intl.NumberFormat('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const num = v => nf2.format(Number(v) || 0);
            const money = v => '$' + num(v);
            const esc = v => String(v ?? '').replace(/[&<>"']/g, c => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[c]));

            function fechaCorta(valor) {
                if (!valor) return '—';
                const s = String(valor);
                const partes = s.slice(0, 10).split('-');
                if (partes.length !== 3) return s;
                const hora = s.slice(11, 16);
                return `${partes[2]}/${partes[1]}/${partes[0]}${hora ? ' ' + hora : ''}`;
            }

            let DATOS_SUCURSALES = {};
            let PERIODO_ACTUAL = {
                desde: '',
                hasta: ''
            };

            /* ── Totales por sucursal ───────────────────────────────────────── */
            function renderResumen(data) {
                const sucursales = data.sucursales || [];

                PERIODO_ACTUAL = {
                    desde: data.desde || '',
                    hasta: data.hasta || ''
                };
                DATOS_SUCURSALES = {};

                if (!sucursales.length) {
                    return `<div class="state-empty"><i class="fa fa-inbox"></i>Sin ventas del producto en este período</div>`;
                }

                let html = '';
                sucursales.forEach(suc => {
                    const sid = String(suc.id);
                    const gainClass = Number(suc.ganancia) >= 0 ? 'gain-pos' : 'gain-neg';
                    DATOS_SUCURSALES[sid] = suc;

                    html += `
                    <div class="branch-block">
                        <p class="branch-title">
                            <i class="fa fa-map-marker"></i> ${esc(suc.sucursal)}
                        </p>

                        <div class="sales-table-wrap">
                            <table class="ficha-table">
                                <thead>
                                    <tr>
                                        <th>Período</th>
                                        <th>Unidades despachadas</th>
                                        <th>Valor Vendido</th>
                                        <th>Ganancia</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>${esc(data.desde)} <span style="color:#475569">→</span> ${esc(data.hasta)}</td>
                                        <td><span class="num-big">${num(suc.unidades)}</span> <span class="num-sub">unid.</span></td>
                                        <td><span class="num-big">${money(suc.total)}</span></td>
                                        <td><span class="${gainClass}">${money(suc.ganancia)}</span></td>
                                        <td>
                                            <button type="button" class="btn-desglose" data-sucursal="${sid}">
                                                <i class="fa fa-users"></i> Ver desglose
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>`;
                });

                return html;
            }

            /* ── Modal de desglose ──────────────────────────────────────────── */
            function abrirDesglose(sucursalId) {
                const suc = DATOS_SUCURSALES[String(sucursalId)];
                if (!suc) return;

                const cajas = Number(suc.cajas) || 0;
                const promedioCaja = cajas > 0 ? (Number(suc.total) || 0) / cajas : 0;
                const gainClass = Number(suc.ganancia) >= 0 ? 'gain-pos' : 'gain-neg';

                const filasUsuarios = (suc.usuarios || []).map(u => {
                    const uc = Number(u.cajas) || 0;
                    const up = uc > 0 ? (Number(u.total) || 0) / uc : 0;
                    const ug = Number(u.ganancia) >= 0 ? 'gain-pos' : 'gain-neg';
                    return `<tr>
                        <td><strong>${esc(u.usuario)}</strong></td>
                        <td>${Number(u.ventas) || 0}</td>
                        <td><span class="num-big" style="font-size:16px;">${num(uc)}</span></td>
                        <td>${num(u.unidades)}</td>
                        <td>${money(u.total)}</td>
                        <td><span class="${ug}">${money(u.ganancia)}</span></td>
                        <td>${money(up)}</td>
                    </tr>`;
                }).join('');

                const filasVentas = (suc.ventas_detalle || []).map(v => {
                    const vg = Number(v.ganancia) >= 0 ? 'gain-pos' : 'gain-neg';
                    return `<tr>
                        <td><strong>#${v.id}</strong></td>
                        <td>${fechaCorta(v.fecha)}</td>
                        <td>${esc(v.usuario)}</td>
                        <td><span class="num-big" style="font-size:16px;">${num(v.cajas)}</span></td>
                        <td>${num(v.unidades)}</td>
                        <td>${money(v.total)}</td>
                        <td><span class="${vg}">${money(v.ganancia)}</span></td>
                        <td>
                            <a class="sale-link" href="detallesVenta.php?id=${v.id}" target="_blank" rel="noopener">
                                <i class="fa fa-external-link"></i> Ver venta
                            </a>
                        </td>
                    </tr>`;
                }).join('');

                const truncado = (Number(suc.ventas_total) || 0) > (Number(suc.ventas_mostradas) || 0)
                    ? `<p class="truncated-note">Mostrando las ${suc.ventas_mostradas} ventas más recientes de ${suc.ventas_total}. Acota el período para ver el resto.</p>`
                    : '';

                document.getElementById('desgloseTitulo').textContent = 'Desglose — ' + suc.sucursal;
                document.getElementById('desgloseContenido').innerHTML = `
                    <p class="num-sub" style="margin:0 0 14px;">
                        <i class="fa fa-calendar"></i>
                        Período: <strong style="color:#cbd5e1;">${esc(PERIODO_ACTUAL.desde)} → ${esc(PERIODO_ACTUAL.hasta)}</strong>
                    </p>

                    <div class="branch-summary">
                        <div class="branch-stat">
                            <span class="lbl">Cajas vendidas</span>
                            <span class="val">${num(cajas)} <small>cajas</small></span>
                        </div>
                        <div class="branch-stat">
                            <span class="lbl">Unidades</span>
                            <span class="val">${num(suc.unidades)} <small>unid.</small></span>
                        </div>
                        <div class="branch-stat">
                            <span class="lbl">Valor vendido</span>
                            <span class="val">${money(suc.total)}</span>
                        </div>
                        <div class="branch-stat">
                            <span class="lbl">Ganancia</span>
                            <span class="val ${gainClass}">${money(suc.ganancia)}</span>
                        </div>
                        <div class="branch-stat">
                            <span class="lbl">Promedio por caja</span>
                            <span class="val">${money(promedioCaja)}</span>
                        </div>
                    </div>

                    <div class="desglose-section">
                        <p class="desglose-section-title"><i class="fa fa-users"></i> Por usuario</p>
                        <div class="sales-table-wrap">
                            <table class="ficha-table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Ventas</th>
                                        <th>Cajas</th>
                                        <th>Unidades</th>
                                        <th>Valor vendido</th>
                                        <th>Ganancia</th>
                                        <th>Prom. caja</th>
                                    </tr>
                                </thead>
                                <tbody>${filasUsuarios || '<tr><td colspan="7" class="state-empty">Sin usuarios con ventas en el período.</td></tr>'}</tbody>
                            </table>
                        </div>
                    </div>

                    <div class="desglose-section">
                        <p class="desglose-section-title"><i class="fa fa-shopping-cart"></i> Ventas que incluyeron el producto</p>
                        <div class="sales-table-wrap">
                            <table class="ficha-table">
                                <thead>
                                    <tr>
                                        <th>Venta</th>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Cajas</th>
                                        <th>Unidades</th>
                                        <th>Vendido</th>
                                        <th>Ganancia</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>${filasVentas || '<tr><td colspan="8" class="state-empty">Sin ventas registradas.</td></tr>'}</tbody>
                            </table>
                        </div>
                        ${truncado}
                    </div>`;

                $('#modalDesglose').modal('show');
            }

            /* ── Preset chips ───────────────────────────────────────────────── */
            document.querySelectorAll('.preset-chip:not(.stock-chip)').forEach(chip => {
                chip.addEventListener('click', function() {
                    document.querySelectorAll('.preset-chip:not(.stock-chip)').forEach(c => c.classList.remove('active'));
                    this.classList.add('active');

                    const today = new Date();
                    const fmt = d => d.toISOString().slice(0, 10);
                    let desde, hasta = fmt(today);

                    switch (this.dataset.preset) {
                        case 'today':
                            desde = fmt(today);
                            break;
                        case 'week': {
                            const d = new Date(today);
                            d.setDate(d.getDate() - d.getDay() + 1);
                            desde = fmt(d);
                            break;
                        }
                        case 'month':
                            desde = fmt(new Date(today.getFullYear(), today.getMonth(), 1));
                            break;
                        case 'quarter':
                            desde = fmt(new Date(today.getFullYear(), Math.floor(today.getMonth() / 3) * 3, 1));
                            break;
                        case 'year':
                            desde = fmt(new Date(today.getFullYear(), 0, 1));
                            break;
                    }
                    document.querySelector('#fp-desde')._flatpickr.setDate(desde);
                    document.querySelector('#fp-hasta')._flatpickr.setDate(hasta);
                    consultarPeriodo();
                });
            });

            /* ── Period query ───────────────────────────────────────────────── */
            function consultarPeriodo() {
                const desde = document.getElementById('fp-desde').value;
                const hasta = document.getElementById('fp-hasta').value;
                const pid = <?= $idProducto ?>;
                const btn = document.getElementById('btn-filtrar');
                const results = document.getElementById('period-results');

                btn.classList.add('loading');
                btn.innerHTML = '<i class="fa fa-circle-o-notch" style="animation:spin .8s linear infinite;"></i> Consultando…';

                results.innerHTML = `
        <div class="state-loading">
            <i class="fa fa-circle-o-notch spin"></i>
            Procesando período…
        </div>`;

                const query = new URLSearchParams({
                    id: pid,
                    desde: desde,
                    hasta: hasta
                });

                fetch('ficha_consulta_periodo.php?' + query.toString())
                    .then(r => r.json())
                    .then(data => {
                        btn.classList.remove('loading');
                        btn.innerHTML = '<i class="fa fa-search"></i> Consultar';

                        if (data.error) {
                            results.innerHTML = `<div class="state-empty"><i class="fa fa-exclamation-circle"></i>${esc(data.error)}</div>`;
                            return;
                        }

                        results.innerHTML = renderResumen(data);
                    })
                    .catch(() => {
                        btn.classList.remove('loading');
                        btn.innerHTML = '<i class="fa fa-search"></i> Consultar';
                        results.innerHTML = `<div class="state-empty"><i class="fa fa-exclamation-triangle"></i>Error al conectar con el servidor</div>`;
                    });
            }

            document.getElementById('btn-filtrar').addEventListener('click', consultarPeriodo);

            document.getElementById('period-results').addEventListener('click', function(evento) {
                const boton = evento.target.closest('.btn-desglose');
                if (boton) {
                    abrirDesglose(boton.dataset.sucursal);
                }
            });

            // Auto-load on page open
            consultarPeriodo();
        </script>

        <script>
            /* ══════════════════════════════════════════════════════════════
               MOVIMIENTO GENERAL
               Línea de tiempo tipo git: una columna/lane por sucursal.
               ══════════════════════════════════════════════════════════════ */
            (function() {
                'use strict';

                const PRODUCTO_ID = <?= (int) $idProducto ?>;
                const BSS_ID = <?= (int) $bss_id ?>;
                const PREFS_KEY = 'fichaMovGeneral:' + BSS_ID;

                const TIPOS = {
                    compra: { label: 'Compras', short: 'C', color: '#34d399' },
                    venta: { label: 'Ventas', short: 'V', color: '#60a5fa' },
                    traslado: { label: 'Traslados', short: 'T', color: '#a78bfa' },
                    merma: { label: 'Mermas', short: 'M', color: '#f87171' }
                };
                const TIPOS_ORDEN = ['compra', 'venta', 'traslado', 'merma'];
                const PALETA = ['#f59e0b', '#22d3ee', '#fb7185', '#a3e635', '#c084fc', '#38bdf8', '#f472b6', '#facc15', '#4ade80', '#e879f9'];

                const MG = {
                    data: null,
                    sucursales: [],
                    mapaSucursales: new Map(),
                    ordenServidor: [],
                    orden: [],
                    tipos: { compra: true, venta: true, traslado: true, merma: true },
                    ocultos: {},
                    sort: 'desc',
                    unidad: 'UND',
                    template: '170px 1fr',
                    cargando: false,
                    truncado: false
                };

                let dragBranchId = null;
                let fpMovDesde = null;
                let fpMovHasta = null;

                const $id = (id) => document.getElementById(id);
                const nf = new Intl.NumberFormat('es-VE', { maximumFractionDigits: 2 });
                const num = (valor) => nf.format(Number(valor) || 0);
                const esc = (valor) => String(valor ?? '').replace(/[&<>"']/g, (c) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;'
                }[c]));
                const pad2 = (n) => String(n).padStart(2, '0');
                const fmtISO = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
                const rgba = (hex, alpha) => {
                    const n = parseInt(String(hex).slice(1), 16);
                    return `rgba(${(n >> 16) & 255}, ${(n >> 8) & 255}, ${n & 255}, ${alpha})`;
                };
                const cssEsc = (s) => String(s).replace(/["\\]/g, '\\$&');
                const colorSucursal = (id) => {
                    let h = 0;
                    String(id).split('').forEach((ch) => {
                        h = (h * 31 + ch.charCodeAt(0)) >>> 0;
                    });
                    return PALETA[h % PALETA.length];
                };

                function fechaPartes(iso) {
                    const s = String(iso || '');
                    const partes = s.split(' ');
                    const f = (partes[0] || '').split('-');
                    return {
                        fecha: f.length === 3 ? `${f[2]}/${f[1]}/${f[0]}` : (partes[0] || ''),
                        hora: (partes[1] || '').slice(0, 5)
                    };
                }

                /* ── Preferencias de vista (por negocio) ───────────────── */
                function cargarPrefs() {
                    try {
                        const raw = JSON.parse(localStorage.getItem(PREFS_KEY) || '{}');
                        if (!raw || typeof raw !== 'object') return;
                        if (Array.isArray(raw.orden)) MG.orden = raw.orden.map(String);
                        if (raw.tipos && typeof raw.tipos === 'object') {
                            TIPOS_ORDEN.forEach((t) => {
                                if (typeof raw.tipos[t] === 'boolean') MG.tipos[t] = raw.tipos[t];
                            });
                        }
                        if (raw.ocultos && typeof raw.ocultos === 'object') {
                            MG.ocultos = raw.ocultos;
                        }
                        if (raw.sort === 'asc' || raw.sort === 'desc') MG.sort = raw.sort;
                    } catch (e) {
                        /* localStorage no disponible */
                    }
                }

                function guardarPrefs() {
                    try {
                        localStorage.setItem(PREFS_KEY, JSON.stringify({
                            orden: MG.orden,
                            tipos: MG.tipos,
                            ocultos: MG.ocultos,
                            sort: MG.sort
                        }));
                    } catch (e) {
                        /* localStorage no disponible */
                    }
                }

                function oculto(branchId, tipo) {
                    const fila = MG.ocultos[String(branchId)];
                    return !!(fila && fila[tipo]);
                }

                function inicializarOcultos(branchId) {
                    const key = String(branchId);
                    if (!MG.ocultos[key] || typeof MG.ocultos[key] !== 'object') {
                        MG.ocultos[key] = {};
                    }
                    TIPOS_ORDEN.forEach((t) => {
                        if (typeof MG.ocultos[key][t] !== 'boolean') MG.ocultos[key][t] = false;
                    });
                }

                /* ── Sucursales y orden de columnas ────────────────────── */
                function prepararSucursales(data) {
                    const mapa = new Map();

                    (data.sucursales || []).forEach((s) => {
                        const id = String(s.id);
                        mapa.set(id, {
                            id: id,
                            nombre: String(s.nombre || ('Sucursal #' + id)),
                            principal: !!s.principal,
                            stock: Number(s.stock) || 0,
                            color: colorSucursal(id)
                        });
                    });

                    (data.eventos || []).forEach((ev) => {
                        const agregar = (id, nombre) => {
                            if (id === null || id === undefined || id === '') return;
                            const key = String(id);
                            if (mapa.has(key)) return;
                            mapa.set(key, {
                                id: key,
                                nombre: String(nombre || ('Sucursal #' + key)),
                                principal: false,
                                stock: 0,
                                color: colorSucursal(key)
                            });
                        };
                        if (ev.tipo === 'traslado') {
                            agregar(ev.origen_id, ev.origen);
                            agregar(ev.destino_id, ev.destino);
                        } else {
                            agregar(ev.sucursal_id, ev.sucursal);
                        }
                    });

                    MG.mapaSucursales = mapa;
                    MG.ordenServidor = Array.from(mapa.keys());
                    const orden = [];
                    MG.orden.forEach((id) => {
                        const key = String(id);
                        if (mapa.has(key) && !orden.includes(key)) orden.push(key);
                    });
                    MG.ordenServidor.forEach((id) => {
                        if (!orden.includes(id)) orden.push(id);
                    });
                    MG.orden = orden;
                    aplicarOrden();
                }

                function aplicarOrden() {
                    MG.sucursales = MG.orden
                        .map((id) => MG.mapaSucursales.get(String(id)))
                        .filter(Boolean);
                    MG.sucursales.forEach((s) => inicializarOcultos(s.id));
                }

                /* ── Eventos y ocurrencias por sucursal ────────────────── */
                function ocurrencias(ev) {
                    if (ev.tipo === 'traslado') {
                        const origen = (ev.origen_id === null || ev.origen_id === undefined) ? null : String(ev.origen_id);
                        const destino = (ev.destino_id === null || ev.destino_id === undefined) ? null : String(ev.destino_id);
                        if (origen && destino && origen === destino) {
                            return [{ id: origen, signo: 0, interno: true }];
                        }
                        const lista = [];
                        if (origen) lista.push({ id: origen, signo: -1 });
                        if (destino) lista.push({ id: destino, signo: 1 });
                        return lista;
                    }
                    return [{
                        id: String(ev.sucursal_id ?? '0'),
                        signo: Number(ev.signo) || 0
                    }];
                }

                function visibleEn(branchId, tipo) {
                    if (!MG.tipos[tipo]) return false;
                    return !oculto(branchId, tipo);
                }

                function ocurrenciasVisibles(ev) {
                    return ocurrencias(ev).filter((o) => visibleEn(o.id, ev.tipo));
                }

                function ordenarEventos(lista) {
                    const factor = MG.sort === 'asc' ? 1 : -1;
                    return lista.slice().sort((a, b) => {
                        const cmp = String(a.fecha || '').localeCompare(String(b.fecha || ''));
                        if (cmp !== 0) return cmp * factor;
                        return String(a.id || '').localeCompare(String(b.id || '')) * factor;
                    });
                }

                function eventosVisibles() {
                    return (MG.data && MG.data.eventos ? MG.data.eventos : []).filter((ev) => {
                        return ocurrenciasVisibles(ev).length > 0;
                    });
                }

                // Los grupos de ventas van por URL. Se comprimen los IDs
                // consecutivos (1-5,8,10-12) para no generar URLs enormes.
                function comprimirIds(ids) {
                    const nums = Array.from(new Set(ids.map(Number).filter((n) => Number.isInteger(n) && n > 0)))
                        .sort((a, b) => a - b);
                    if (!nums.length) return '';

                    const partes = [];
                    let inicio = nums[0];
                    let previo = nums[0];

                    for (let i = 1; i < nums.length; i++) {
                        const actual = nums[i];
                        if (actual === previo + 1) {
                            previo = actual;
                            continue;
                        }
                        partes.push(inicio === previo ? String(inicio) : `${inicio}-${previo}`);
                        inicio = previo = actual;
                    }
                    partes.push(inicio === previo ? String(inicio) : `${inicio}-${previo}`);
                    return partes.join(',');
                }

                // Agrupa TODAS las ventas consecutivas entre compras/traslados.
                // El desglose por usuario se muestra dentro del grupo, sin
                // partir el grupo principal.
                function construirBloques(ascendentes) {
                    const bloques = [];
                    let grupo = null;

                    const cerrar = () => {
                        if (!grupo) return;
                        bloques.push(grupo);
                        grupo = null;
                    };

                    ascendentes.forEach((ev) => {
                        if (ev.tipo === 'venta') {
                            if (!grupo) {
                                grupo = {
                                    tipo: 'grupo',
                                    ventas: [],
                                    mermas: [],
                                    fechaInicio: ev.fecha,
                                    fechaFin: ev.fecha
                                };
                            }
                            grupo.ventas.push(ev);
                            if (String(ev.fecha) < String(grupo.fechaInicio)) grupo.fechaInicio = ev.fecha;
                            if (String(ev.fecha) > String(grupo.fechaFin)) grupo.fechaFin = ev.fecha;
                        } else if (ev.tipo === 'compra' || ev.tipo === 'traslado') {
                            cerrar();
                            bloques.push({ tipo: 'evento', evento: ev });
                        } else if (ev.tipo === 'merma') {
                            // No corta: si hay un grupo abierto, la merma queda dentro.
                            if (grupo) {
                                grupo.mermas.push(ev);
                            } else {
                                bloques.push({ tipo: 'evento', evento: ev });
                            }
                        }
                    });

                    cerrar();
                    return bloques;
                }

                function resumenGrupo(grupo) {
                    const porSucursal = new Map();
                    const porUsuario = new Map();
                    const ordenes = new Set();
                    let totalCantidad = 0;
                    let totalMonto = 0;
                    let totalVentas = 0;

                    const claveUsuario = (ev) => {
                        const id = Number(ev.usuario_id);
                        return (Number.isFinite(id) && id > 0)
                            ? 'u:' + id
                            : 'n:' + String(ev.usuario || '');
                    };

                    const filaVacia = () => ({
                        cantidad: 0,
                        monto: 0,
                        ventas: 0,
                        ordenes: [],
                        mermas: 0,
                        mermaCantidad: 0,
                        mermaDetalle: []
                    });

                    (grupo.ventas || []).forEach((ev) => {
                        const cantidad = Number(ev.cantidad) || 0;
                        const monto = Number(ev.monto) || 0;
                        totalCantidad += cantidad;
                        totalMonto += monto;
                        totalVentas++;

                        if (ev.orden_id) ordenes.add(Number(ev.orden_id));

                        const claveU = claveUsuario(ev);
                        if (!porUsuario.has(claveU)) {
                            porUsuario.set(claveU, {
                                usuario: ev.usuario || 'Sin usuario',
                                ordenes: [],
                                ventas: 0,
                                cantidad: 0,
                                monto: 0
                            });
                        }
                        const filaUsuario = porUsuario.get(claveU);
                        filaUsuario.ventas++;
                        filaUsuario.cantidad += cantidad;
                        filaUsuario.monto += monto;
                        if (ev.orden_id) filaUsuario.ordenes.push(Number(ev.orden_id));

                        ocurrenciasVisibles(ev).forEach((o) => {
                            if (!porSucursal.has(o.id)) porSucursal.set(o.id, filaVacia());
                            const fila = porSucursal.get(o.id);
                            fila.cantidad += cantidad;
                            fila.monto += monto;
                            fila.ventas++;
                            if (ev.orden_id) fila.ordenes.push(Number(ev.orden_id));
                        });
                    });

                    (grupo.mermas || []).forEach((ev) => {
                        const cantidad = Number(ev.cantidad) || 0;
                        const fecha = fechaPartes(ev.fecha);
                        const detalle = `${fecha.fecha} ${fecha.hora || '00:00'} · −${num(cantidad)}`;
                        ocurrenciasVisibles(ev).forEach((o) => {
                            if (!porSucursal.has(o.id)) porSucursal.set(o.id, filaVacia());
                            const fila = porSucursal.get(o.id);
                            fila.mermas++;
                            fila.mermaCantidad += cantidad;
                            fila.mermaDetalle.push(detalle);
                        });
                    });

                    return {
                        porSucursal,
                        porUsuario,
                        ordenes: comprimirIds(Array.from(ordenes)),
                        totalCantidad,
                        totalMonto,
                        totalVentas
                    };
                }

                /* ── Totales que respetan los toggles ──────────────────── */
                function calcularTotales() {
                    const porSucursal = new Map();
                    MG.sucursales.forEach((s) => {
                        porSucursal.set(String(s.id), {
                            compra: 0,
                            venta: 0,
                            trasladoIn: 0,
                            trasladoOut: 0,
                            merma: 0
                        });
                    });

                    const global = { compra: 0, venta: 0, traslado: 0, merma: 0 };

                    (MG.data && MG.data.eventos ? MG.data.eventos : []).forEach((ev) => {
                        if (!MG.tipos[ev.tipo]) return;
                        const occ = ocurrenciasVisibles(ev);
                        if (!occ.length) return;

                        const cantidad = Number(ev.cantidad) || 0;
                        if (ev.tipo === 'traslado') {
                            global.traslado += cantidad;
                        } else {
                            global[ev.tipo] += cantidad;
                        }

                        occ.forEach((o) => {
                            if (!porSucursal.has(o.id)) {
                                porSucursal.set(o.id, {
                                    compra: 0,
                                    venta: 0,
                                    trasladoIn: 0,
                                    trasladoOut: 0,
                                    merma: 0
                                });
                            }
                            const fila = porSucursal.get(o.id);
                            if (ev.tipo === 'traslado') {
                                if (o.signo < 0) fila.trasladoOut += cantidad;
                                if (o.signo > 0) fila.trasladoIn += cantidad;
                            } else {
                                fila[ev.tipo] += cantidad;
                            }
                        });
                    });

                    return {
                        global: global,
                        porSucursal: porSucursal
                    };
                }

                /* ── Cabecera de tipos (global) ────────────────────────── */
                function renderTipos() {
                    const cont = $id('mov-type-filter');
                    if (!cont) return;

                    cont.innerHTML = TIPOS_ORDEN.map((t) => {
                        const cfg = TIPOS[t];
                        const activo = MG.tipos[t] !== false;
                        return `<button type="button" class="mov-type-chip ${activo ? 'active' : ''}"
                            data-global-type="${t}"
                            style="--tc:${cfg.color};--tbg:${rgba(cfg.color, .14)}"
                            title="${activo ? 'Ocultar' : 'Mostrar'} ${cfg.label} en todas las sucursales">
                            <span class="dot"></span>${cfg.label}
                        </button>`;
                    }).join('');

                    cont.querySelectorAll('[data-global-type]').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            const t = btn.dataset.globalType;
                            MG.tipos[t] = MG.tipos[t] === false;
                            guardarPrefs();
                            renderTodo();
                        });
                    });
                }

                /* ── Resumen por sucursal (KPIs por columna) ───────────── */
                function renderSummary(tot) {
                    const cont = $id('mov-summary');
                    if (!cont) return;

                    if (!MG.sucursales.length) {
                        cont.innerHTML = '<div class="state-empty"><i class="fa fa-inbox"></i>Sin sucursales para mostrar</div>';
                        return;
                    }

                    const cero = { compra: 0, venta: 0, trasladoIn: 0, trasladoOut: 0, merma: 0 };
                    const valor = (v, activo) => activo ? num(v) : '<span class="kpi-off">—</span>';

                    const filas = MG.sucursales.map((s) => {
                        const id = String(s.id);
                        const t = tot.porSucursal.get(id) || cero;
                        const cOn = MG.tipos.compra && !oculto(id, 'compra');
                        const vOn = MG.tipos.venta && !oculto(id, 'venta');
                        const tOn = MG.tipos.traslado && !oculto(id, 'traslado');
                        const mOn = MG.tipos.merma && !oculto(id, 'merma');
                        const netoTraslado = t.trasladoIn - t.trasladoOut;

                        return `<tr>
                            <td><span class="suc-name"><span class="suc-dot" style="--lane:${s.color}"></span>${esc(s.nombre)}</span></td>
                            <td>${valor(t.compra, cOn)}</td>
                            <td>${valor(t.venta, vOn)}</td>
                            <td title="Entradas: ${num(t.trasladoIn)} · Salidas: ${num(t.trasladoOut)}">${tOn ? ((netoTraslado > 0 ? '+' : '') + num(netoTraslado)) : '<span class="kpi-off">—</span>'}</td>
                            <td>${valor(t.merma, mOn)}</td>
                            <td class="stock-now" title="Existencia actual en la sucursal">${num(s.stock || 0)}</td>
                        </tr>`;
                    }).join('');

                    const totalTrasladoNeto = MG.sucursales.reduce((acc, s) => {
                        const t = tot.porSucursal.get(String(s.id));
                        return acc + (t ? (t.trasladoIn - t.trasladoOut) : 0);
                    }, 0);
                    const totalStock = MG.sucursales.reduce((acc, s) => acc + (Number(s.stock) || 0), 0);

                    cont.innerHTML = `<div class="mov-summary-wrap">
                        <table class="mov-summary">
                            <thead>
                                <tr>
                                    <th>Sucursal</th>
                                    <th>Compras</th>
                                    <th>Ventas</th>
                                    <th>Traslados (neto)</th>
                                    <th>Mermas</th>
                                    <th title="Existencia actual del producto en cada sucursal">Stock actual (${esc(MG.unidad)})</th>
                                </tr>
                            </thead>
                            <tbody>${filas}</tbody>
                            <tfoot>
                                <tr>
                                    <td>Total visible</td>
                                    <td>${MG.tipos.compra ? num(tot.global.compra) : '—'}</td>
                                    <td>${MG.tipos.venta ? num(tot.global.venta) : '—'}</td>
                                    <td>${MG.tipos.traslado ? ((totalTrasladoNeto > 0 ? '+' : '') + num(totalTrasladoNeto)) : '—'}</td>
                                    <td>${MG.tipos.merma ? num(tot.global.merma) : '—'}</td>
                                    <td class="stock-now">${num(totalStock)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>`;
                }

                /* ── Encabezados de columnas ───────────────────────────── */
                function branchHeadHtml(s, tot) {
                    const id = String(s.id);
                    const t = tot.porSucursal.get(id) || {
                        compra: 0,
                        venta: 0,
                        trasladoIn: 0,
                        trasladoOut: 0,
                        merma: 0
                    };

                    const partes = [];
                    const agregarParte = (tipo, valorTxt) => {
                        const activo = MG.tipos[tipo] && !oculto(id, tipo);
                        partes.push(activo
                            ? `<span style="color:${TIPOS[tipo].color}">${TIPOS[tipo].short} ${valorTxt}</span>`
                            : `<span class="off">${TIPOS[tipo].short}</span>`);
                    };
                    agregarParte('compra', num(t.compra));
                    agregarParte('venta', num(t.venta));
                    const netoTraslado = t.trasladoIn - t.trasladoOut;
                    agregarParte('traslado', `${netoTraslado > 0 ? '+' : ''}${num(netoTraslado)}`);
                    agregarParte('merma', num(t.merma));

                    const botonesTipo = TIPOS_ORDEN.map((tipo) => {
                        const activo = MG.tipos[tipo] && !oculto(id, tipo);
                        return `<button type="button" draggable="false"
                            class="mov-btype ${activo ? 'on' : ''}"
                            data-btype="${tipo}" data-branch-id="${id}"
                            style="--tc:${TIPOS[tipo].color}"
                            title="${activo ? 'Ocultar' : 'Mostrar'} ${TIPOS[tipo].label} de ${esc(s.nombre)}">
                            ${TIPOS[tipo].short}
                        </button>`;
                    }).join('');

                    return `<div class="mov-branch-head" draggable="true"
                        data-branch-id="${id}" style="--lane:${s.color}">
                        <div class="mov-branch-top">
                            <span class="mov-drag" aria-hidden="true">⠿</span>
                            <span class="mov-lane-dot"></span>
                            <span class="mov-branch-name" title="${esc(s.nombre)}">${esc(s.nombre)}</span>
                            <span class="mov-move-btns">
                                <button type="button" draggable="false" data-move="-1" data-branch-id="${id}" title="Mover a la izquierda">‹</button>
                                <button type="button" draggable="false" data-move="1" data-branch-id="${id}" title="Mover a la derecha">›</button>
                            </span>
                        </div>
                        <div class="mov-branch-types">${botonesTipo}</div>
                        <div class="mov-branch-total" title="KPIs visibles de ${esc(s.nombre)}">${partes.join(' · ')}</div>
                    </div>`;
                }

                /* ── Nodo de un evento en una columna ──────────────────── */
                function nodoHtml(ev, occ) {
                    const cfg = TIPOS[ev.tipo] || { color: '#94a3b8', label: 'Movimiento' };
                    const cantidad = Number(ev.cantidad) || 0;
                    const esTraslado = ev.tipo === 'traslado';
                    const esSalida = esTraslado ? occ.signo < 0 : (Number(ev.signo) || 0) < 0;
                    let prefijo = esSalida ? '−' : '+';
                    if (esTraslado && occ.signo === 0) prefijo = '±';

                    let titulo = ev.subtipo || cfg.label.replace(/s$/, '');
                    if (esTraslado) {
                        titulo = occ.interno
                            ? 'Traslado interno'
                            : (esSalida ? 'Sale → ' + (ev.destino || '') : 'Entra ← ' + (ev.origen || ''));
                    }

                    let detalle = '';
                    if (!esTraslado && ev.detalle) {
                        detalle = esc(ev.detalle);
                        if (ev.tipo === 'venta' && ev.referencia) {
                            detalle = `<a href="${esc(ev.referencia)}" target="_blank" rel="noopener">${detalle}</a>`;
                        }
                    }

                    const usuario = ev.usuario ? `<span title="Usuario"> · ${esc(ev.usuario)}</span>` : '';
                    const razon = ev.razon ? ` title="${esc(ev.razon)}"` : '';
                    const claseLado = esTraslado ? (esSalida ? 'mov-node--out' : (occ.interno ? '' : 'mov-node--in')) : '';

                    return `<span class="mov-dot" data-event-id="${esc(ev.id)}" data-branch-id="${esc(occ.id)}" style="--tc:${cfg.color}"></span>
                        <div class="mov-node ${claseLado}" style="--tc:${cfg.color}"${razon}>
                            <div class="mov-node-top">
                                <span class="mov-node-qty">${prefijo}${num(cantidad)} ${esc(MG.unidad)}</span>
                                <span class="mov-node-title">${esc(titulo)}</span>
                            </div>
                            <div class="mov-node-meta">${detalle}${usuario}</div>
                        </div>`;
                }

                /* ── Filas del gráfico ─────────────────────────────────── */
                function rowHtml(ev) {
                    const occ = ocurrenciasVisibles(ev);
                    if (!occ.length) return '';

                    const porSucursal = {};
                    occ.forEach((o) => {
                        if (!porSucursal[o.id]) porSucursal[o.id] = [];
                        porSucursal[o.id].push(o);
                    });

                    const f = fechaPartes(ev.fecha);
                    const cfg = TIPOS[ev.tipo] || { color: '#94a3b8', label: 'Movimiento' };
                    const etiquetaTiempo = ev.subtipo || cfg.label.replace(/s$/, '');

                    const celdas = MG.sucursales.map((s) => {
                        const lista = porSucursal[String(s.id)] || [];
                        return `<div class="mov-cell"
                            data-branch-id="${s.id}" data-event-id="${esc(ev.id)}"
                            style="--lane:${s.color};--tc:${cfg.color}">
                            ${lista.map((o) => nodoHtml(ev, o)).join('')}
                        </div>`;
                    }).join('');

                    return `<div class="mov-grid mov-row" data-event-id="${esc(ev.id)}" style="--cols-template:${MG.template}">
                        <div class="mov-time" style="--tc:${cfg.color}">
                            <span class="mov-time-date">${f.fecha}</span>
                            <span class="mov-time-hour">${f.hora || '—'}</span>
                            <span class="mov-time-type">${esc(etiquetaTiempo)}</span>
                        </div>
                        ${celdas}
                    </div>`;
                }

                // Un grupo de ventas entre dos compras/traslados. El grupo
                // completo (y cada nodo de sucursal) abre listaVentas.php
                // con los IDs de esas ventas en la URL.
                function rowGrupoHtml(grupo) {
                    const resumen = resumenGrupo(grupo);
                    if (!resumen.totalVentas) {
                        return (grupo.mermas || []).map(rowHtml).join('');
                    }
                    // Si el endpoint no trae los IDs de las órdenes, se muestran
                    // las ventas sueltas en vez de perderlas.
                    if (!resumen.ordenes) {
                        return grupo.ventas.map(rowHtml).join('') +
                            (grupo.mermas || []).map(rowHtml).join('');
                    }

                    const desde = fechaPartes(grupo.fechaInicio);
                    const hasta = fechaPartes(grupo.fechaFin);
                    const mismoDia = String(grupo.fechaInicio).slice(0, 10) === String(grupo.fechaFin).slice(0, 10);
                    const rango = mismoDia
                        ? (desde.hora === hasta.hora
                            ? `${desde.fecha} · ${desde.hora || '00:00'}`
                            : `${desde.fecha} · ${desde.hora || '00:00'} – ${hasta.hora || '00:00'}`)
                        : `${desde.fecha} ${desde.hora || '00:00'} – ${hasta.fecha} ${hasta.hora || '00:00'}`;

                    const usuarios = Array.from(resumen.porUsuario.values())
                        .sort((a, b) => (b.cantidad - a.cantidad) || a.usuario.localeCompare(b.usuario));
                    const totalUsuarios = usuarios.length;
                    const tituloUsuarios = usuarios.map((u) => `${u.usuario} (${u.ventas})`).join(', ');
                    const tituloGrupo = `${totalUsuarios} usuario(s) · ${resumen.totalVentas} venta(s) · ${num(resumen.totalCantidad)} ${MG.unidad} · $${num(resumen.totalMonto)}`;

                    const usuariosHtml = usuarios.slice(0, 4).map((u) => {
                        const idsUsuario = comprimirIds(u.ordenes);
                        const texto = `${esc(u.usuario)} <span>${u.ventas}</span>`;
                        if (!idsUsuario) {
                            return `<span class="mov-group-user-link">${texto}</span>`;
                        }
                        return `<a class="mov-group-user-link"
                            href="listaVentas.php?ordenes=${encodeURIComponent(idsUsuario)}"
                            title="Abrir las ${u.ventas} venta(s) de ${esc(u.usuario)}">${texto}</a>`;
                    }).join('<span class="mov-group-user-sep"> · </span>') +
                        (totalUsuarios > 4 ? `<span class="mov-group-user-more"> +${totalUsuarios - 4}</span>` : '');

                    const celdas = MG.sucursales.map((s) => {
                        const fila = resumen.porSucursal.get(String(s.id));
                        if (!fila) {
                            return `<div class="mov-cell" data-branch-id="${s.id}" style="--lane:${s.color}"></div>`;
                        }

                        let contenido = '';
                        if (fila.ventas > 0) {
                            const ordenesSucursal = comprimirIds(fila.ordenes);
                            const urlSucursal = 'listaVentas.php?ordenes=' + encodeURIComponent(ordenesSucursal);
                            contenido += `<a class="mov-group-node" href="${esc(urlSucursal)}"
                                title="Abrir en Lista de ventas las ${fila.ventas} venta(s) de ${esc(s.nombre)}">
                                <span class="mov-group-node-top">
                                    <span class="mov-group-node-qty">−${num(fila.cantidad)} ${esc(MG.unidad)}</span>
                                    <span class="mov-group-node-title">${fila.ventas} venta(s)</span>
                                </span>
                                <span class="mov-group-node-meta">$${num(fila.monto)}</span>
                            </a>`;
                        }

                        if (fila.mermas > 0) {
                            const detalleMermas = (fila.mermaDetalle || []).join(' | ');
                            contenido += `<span class="mov-group-merma"
                                title="${esc(detalleMermas || (fila.mermas + ' merma(s) dentro del grupo'))}">
                                −${num(fila.mermaCantidad)} ${esc(MG.unidad)} merma
                            </span>`;
                        }

                        return `<div class="mov-cell" data-branch-id="${s.id}" style="--lane:${s.color}">${contenido}</div>`;
                    }).join('');

                    return `<div class="mov-grid mov-row mov-group-row"
                        data-ordenes="${esc(resumen.ordenes)}"
                        tabindex="0" role="link"
                        aria-label="Abrir ${esc(tituloGrupo)} en Lista de ventas"
                        title="Abrir ${esc(tituloGrupo)} en Lista de ventas"
                        style="--cols-template:${MG.template}">
                        <div class="mov-time mov-group-time" style="--tc:#60a5fa">
                            <span class="mov-time-date">${desde.fecha}</span>
                            <span class="mov-time-hour">${rango}</span>
                            <span class="mov-time-type">Grupo de ventas</span>
                            <span class="mov-group-users" title="${esc(tituloUsuarios)}">
                                <i class="fa fa-user"></i>${usuariosHtml}
                            </span>
                            <span class="mov-group-total">−${num(resumen.totalCantidad)} ${esc(MG.unidad)}</span>
                            <span class="mov-group-money">$${num(resumen.totalMonto)}</span>
                            <span class="mov-group-count">${resumen.totalVentas} venta(s) · Ver detalle →</span>
                        </div>
                        ${celdas}
                    </div>`;
                }

                function renderGraph(tot) {
                    const status = $id('mov-status');
                    const wrap = $id('mov-graph-wrap');
                    const head = $id('mov-head');
                    const rows = $id('mov-rows');
                    if (!status || !wrap || !head || !rows) return;

                    // Se agrupa siempre en orden cronológico ascendente y luego se
                    // invierte el resultado si la vista está en "recientes".
                    const ordenTipo = { compra: 0, traslado: 1, venta: 2, merma: 3 };
                    const ascendentes = eventosVisibles().slice().sort((a, b) => {
                        const cmp = String(a.fecha || '').localeCompare(String(b.fecha || ''));
                        if (cmp !== 0) return cmp;
                        const ta = ordenTipo[a.tipo] ?? 9;
                        const tb = ordenTipo[b.tipo] ?? 9;
                        if (ta !== tb) return ta - tb;
                        return String(a.id || '').localeCompare(String(b.id || ''));
                    });
                    const bloques = construirBloques(ascendentes);
                    if (MG.sort === 'desc') bloques.reverse();

                    const nota = $id('mov-note');
                    if (nota) {
                        nota.style.display = MG.truncado ? 'flex' : 'none';
                        nota.innerHTML = MG.truncado
                            ? '<i class="fa fa-filter"></i>Hay más de 5000 movimientos: se muestran los 5000 más recientes. Acota el intervalo para ver el resto.'
                            : '';
                    }

                    if (!bloques.length) {
                        status.style.display = 'block';
                        status.className = 'state-empty mov-graph-status';
                        status.innerHTML = '<i class="fa fa-inbox"></i>Sin movimientos del producto en este período';
                        wrap.style.display = 'none';
                        head.innerHTML = '';
                        rows.innerHTML = '';
                        return;
                    }

                    const scrollLeft = wrap.scrollLeft;
                    const scrollTop = wrap.scrollTop;
                    const columnas = Math.max(1, MG.sucursales.length);
                    MG.template = `170px repeat(${columnas}, minmax(190px, 1fr))`;

                    const graph = $id('mov-graph');
                    if (graph) graph.style.setProperty('--cols-template', MG.template);

                    status.style.display = 'none';
                    wrap.style.display = 'block';

                    head.innerHTML = '<div class="mov-corner">Fecha / hora</div>' +
                        MG.sucursales.map((s) => branchHeadHtml(s, tot)).join('');
                    rows.innerHTML = bloques.map((bloque) => {
                        return bloque.tipo === 'grupo'
                            ? rowGrupoHtml(bloque)
                            : rowHtml(bloque.evento);
                    }).join('');

                    wrap.scrollLeft = scrollLeft;
                    wrap.scrollTop = scrollTop;

                    enlazarEncabezados();
                    requestAnimationFrame(dibujarArcos);
                }

                /* ── Arcos de traslado entre columnas ─────────────────── */
                function dibujarArcos() {
                    const graph = $id('mov-graph');
                    const svg = $id('mov-arcs');
                    if (!graph || !svg || !MG.data) return;

                    svg.innerHTML = `<defs>
                        <marker id="mov-arrow" viewBox="0 0 10 10" refX="8" refY="5"
                            markerWidth="7" markerHeight="7" orient="auto-start-reverse">
                            <path d="M 0 0 L 10 5 L 0 10 z" fill="#a78bfa"></path>
                        </marker>
                    </defs>`;

                    const gRect = graph.getBoundingClientRect();

                    (MG.data.eventos || []).forEach((ev) => {
                        if (ev.tipo !== 'traslado' || !MG.tipos.traslado) return;
                        const occ = ocurrenciasVisibles(ev);
                        if (occ.length < 2) return;

                        const salida = occ.find((o) => o.signo < 0);
                        const entrada = occ.find((o) => o.signo > 0);
                        if (!salida || !entrada || salida.id === entrada.id) return;

                        const dotSalida = graph.querySelector(
                            `.mov-dot[data-event-id="${cssEsc(ev.id)}"][data-branch-id="${cssEsc(salida.id)}"]`
                        );
                        const dotEntrada = graph.querySelector(
                            `.mov-dot[data-event-id="${cssEsc(ev.id)}"][data-branch-id="${cssEsc(entrada.id)}"]`
                        );
                        if (!dotSalida || !dotEntrada) return;

                        const r1 = dotSalida.getBoundingClientRect();
                        const r2 = dotEntrada.getBoundingClientRect();
                        const x1 = r1.left - gRect.left + r1.width / 2;
                        const y1 = r1.top - gRect.top + r1.height / 2;
                        const x2 = r2.left - gRect.left + r2.width / 2;
                        const y2 = r2.top - gRect.top + r2.height / 2;
                        const dx = x2 - x1;
                        const curva = Math.min(70, Math.abs(dx) * 0.3);
                        const cx1 = x1 + (dx >= 0 ? curva : -curva);
                        const cx2 = x2 - (dx >= 0 ? curva : -curva);
                        const path = `M ${x1} ${y1} C ${cx1} ${y1 + 22}, ${cx2} ${y2 + 22}, ${x2} ${y2}`;

                        const glow = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        glow.setAttribute('d', path);
                        glow.setAttribute('class', 'mov-arc-glow');

                        const linea = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        linea.setAttribute('d', path);
                        linea.setAttribute('marker-end', 'url(#mov-arrow)');

                        svg.appendChild(glow);
                        svg.appendChild(linea);
                    });
                }

                /* ── Interacción de encabezados ────────────────────────── */
                function enlazarEncabezados() {
                    const head = $id('mov-head');
                    if (!head) return;

                    head.querySelectorAll('.mov-branch-head').forEach((el) => {
                        const branchId = el.dataset.branchId;

                        el.addEventListener('dragstart', (e) => {
                            dragBranchId = branchId;
                            el.classList.add('dragging');
                            try {
                                e.dataTransfer.setData('text/plain', branchId);
                                e.dataTransfer.effectAllowed = 'move';
                            } catch (err) {
                                /* navegador sin DataTransfer */
                            }
                        });

                        el.addEventListener('dragend', () => {
                            dragBranchId = null;
                            head.querySelectorAll('.mov-branch-head').forEach((x) => {
                                x.classList.remove('dragging', 'drop-target');
                            });
                        });

                        el.addEventListener('dragover', (e) => {
                            if (!dragBranchId || dragBranchId === branchId) return;
                            e.preventDefault();
                            head.querySelectorAll('.mov-branch-head').forEach((x) => {
                                x.classList.remove('drop-target');
                            });
                            el.classList.add('drop-target');
                        });

                        el.addEventListener('dragleave', () => {
                            el.classList.remove('drop-target');
                        });

                        el.addEventListener('drop', (e) => {
                            e.preventDefault();
                            el.classList.remove('drop-target');
                            if (dragBranchId && dragBranchId !== branchId) {
                                moverAntes(dragBranchId, branchId);
                            }
                            dragBranchId = null;
                        });
                    });

                    head.querySelectorAll('[data-btype]').forEach((btn) => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            const branchId = btn.dataset.branchId;
                            const tipo = btn.dataset.btype;
                            MG.ocultos[String(branchId)] = MG.ocultos[String(branchId)] || {};
                            MG.ocultos[String(branchId)][tipo] = !MG.ocultos[String(branchId)][tipo];
                            guardarPrefs();
                            renderTodo();
                        });
                    });

                    head.querySelectorAll('[data-move]').forEach((btn) => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            moverLado(btn.dataset.branchId, Number(btn.dataset.move));
                        });
                    });
                }

                function moverAntes(moverId, destinoId) {
                    const mover = String(moverId);
                    const destino = String(destinoId);
                    const desde = MG.orden.indexOf(mover);
                    const hasta = MG.orden.indexOf(destino);

                    if (desde < 0 || hasta < 0) return;
                    MG.orden.splice(desde, 1);
                    const nuevoDestino = MG.orden.indexOf(destino);
                    // Al arrastrar hacia la derecha se suelta después del destino.
                    MG.orden.splice(nuevoDestino + (desde < hasta ? 1 : 0), 0, mover);
                    aplicarOrden();
                    guardarPrefs();
                    renderTodo();
                }

                function moverLado(branchId, direccion) {
                    const id = String(branchId);
                    const indice = MG.orden.indexOf(id);
                    const destino = indice + (direccion < 0 ? -1 : 1);
                    if (indice < 0 || destino < 0 || destino >= MG.orden.length) return;

                    [MG.orden[indice], MG.orden[destino]] = [MG.orden[destino], MG.orden[indice]];
                    aplicarOrden();
                    guardarPrefs();
                    renderTodo();
                }

                /* ── Render completo ───────────────────────────────────── */
                function renderTodo() {
                    if (!MG.data) return;
                    const tot = calcularTotales();
                    renderTipos();
                    renderSummary(tot);
                    renderGraph(tot);
                }

                /* ── Carga del endpoint ────────────────────────────────── */
                function cargar() {
                    if (MG.cargando) return;

                    const desde = ($id('mov-desde') || {}).value || '';
                    const hasta = ($id('mov-hasta') || {}).value || '';
                    const status = $id('mov-status');
                    const wrap = $id('mov-graph-wrap');
                    const summary = $id('mov-summary');

                    MG.cargando = true;
                    MG.data = null;
                    if (status) {
                        status.style.display = 'block';
                        status.className = 'state-loading mov-graph-status';
                        status.innerHTML = '<i class="fa fa-circle-o-notch spin"></i>Cargando movimientos…';
                    }
                    if (wrap) wrap.style.display = 'none';
                    if (summary) summary.innerHTML = '';
                    if ($id('mov-type-filter')) $id('mov-type-filter').innerHTML = '';

                    const params = new URLSearchParams({
                        id: PRODUCTO_ID,
                        desde: desde,
                        hasta: hasta
                    });

                    fetch('ficha_movimiento_general.php?' + params.toString())
                        .then((r) => r.json())
                        .then((data) => {
                            MG.cargando = false;
                            if (!data.ok) {
                                throw new Error(data.error || 'No se pudieron cargar los movimientos');
                            }

                            MG.data = data;
                            MG.unidad = (data.producto && data.producto.unidad) ? data.producto.unidad : 'UND';
                            MG.truncado = !!data.truncado;

                            if ($id('mov-desde')) $id('mov-desde').value = data.desde;
                            if ($id('mov-hasta')) $id('mov-hasta').value = data.hasta;
                            if (fpMovDesde) fpMovDesde.setDate(data.desde, false);
                            if (fpMovHasta) fpMovHasta.setDate(data.hasta, false);

                            prepararSucursales(data);
                            renderTodo();
                        })
                        .catch((error) => {
                            MG.cargando = false;
                            if (status) {
                                status.style.display = 'block';
                                status.className = 'state-empty mov-graph-status';
                                status.innerHTML = '<i class="fa fa-exclamation-triangle"></i>' +
                                    esc(error.message || 'Error al conectar con el servidor');
                            }
                        });
                }

                /* ── Filtros de fecha ──────────────────────────────────── */
                function aplicarRangoDias(dias) {
                    const cantidad = Math.max(1, Number(dias) || 30);
                    const hoy = new Date();
                    const inicio = new Date(hoy);
                    inicio.setDate(inicio.getDate() - (cantidad - 1));

                    const desde = fmtISO(inicio);
                    const hasta = fmtISO(hoy);

                    if (fpMovDesde) fpMovDesde.setDate(desde, false);
                    if (fpMovHasta) fpMovHasta.setDate(hasta, false);
                    if ($id('mov-desde')) $id('mov-desde').value = desde;
                    if ($id('mov-hasta')) $id('mov-hasta').value = hasta;

                    cargar();
                }

                function initControles() {
                    const inputDesde = $id('mov-desde');
                    const inputHasta = $id('mov-hasta');

                    if (typeof flatpickr === 'function') {
                        fpMovDesde = flatpickr(inputDesde, {
                            locale: 'es',
                            dateFormat: 'Y-m-d',
                            defaultDate: inputDesde.value
                        });
                        fpMovHasta = flatpickr(inputHasta, {
                            locale: 'es',
                            dateFormat: 'Y-m-d',
                            defaultDate: inputHasta.value
                        });
                    }

                    const btnConsultar = $id('mov-btn-consultar');
                    if (btnConsultar) {
                        btnConsultar.addEventListener('click', cargar);
                    }

                    document.querySelectorAll('[data-mov-days]').forEach((chip) => {
                        chip.addEventListener('click', () => {
                            document.querySelectorAll('[data-mov-days]').forEach((c) => {
                                c.classList.remove('active');
                            });
                            chip.classList.add('active');
                            aplicarRangoDias(chip.dataset.movDays);
                        });
                    });

                    const btnSort = $id('mov-btn-sort');
                    if (btnSort) {
                        btnSort.addEventListener('click', () => {
                            MG.sort = MG.sort === 'desc' ? 'asc' : 'desc';
                            guardarPrefs();
                            actualizarBotonOrden();
                            renderTodo();
                        });
                    }

                    const btnReset = $id('mov-btn-reset');
                    if (btnReset) {
                        btnReset.addEventListener('click', () => {
                            MG.orden = MG.ordenServidor.slice();
                            MG.ocultos = {};
                            TIPOS_ORDEN.forEach((t) => {
                                MG.tipos[t] = true;
                            });
                            MG.sort = 'desc';
                            aplicarOrden();
                            MG.sucursales.forEach((s) => inicializarOcultos(s.id));
                            guardarPrefs();
                            actualizarBotonOrden();
                            renderTodo();
                        });
                    }

                    const abrirGrupo = (fila) => {
                        if (!fila) return;
                        const ordenes = fila.dataset.ordenes || '';
                        if (!ordenes) return;
                        window.location.href = 'listaVentas.php?ordenes=' + encodeURIComponent(ordenes);
                    };

                    const rowsEl = $id('mov-rows');
                    if (rowsEl) {
                        rowsEl.addEventListener('click', (e) => {
                            const fila = e.target.closest('.mov-group-row');
                            if (!fila) return;
                            // Los enlaces internos (nodos de sucursal) ya navegan solos.
                            if (e.target.closest('a')) return;
                            abrirGrupo(fila);
                        });

                        rowsEl.addEventListener('keydown', (e) => {
                            if (e.key !== 'Enter' && e.key !== ' ') return;
                            const fila = e.target.closest('.mov-group-row');
                            if (!fila) return;
                            e.preventDefault();
                            abrirGrupo(fila);
                        });
                    }

                    actualizarBotonOrden();
                }

                function actualizarBotonOrden() {
                    const label = $id('mov-sort-label');
                    const icon = document.querySelector('#mov-btn-sort i');
                    if (label) label.textContent = MG.sort === 'desc' ? 'Recientes' : 'Antiguos';
                    if (icon) {
                        icon.className = MG.sort === 'desc'
                            ? 'fa fa-sort-amount-desc'
                            : 'fa fa-sort-amount-asc';
                    }
                }

                /* ── Arranque ──────────────────────────────────────────── */
                cargarPrefs();
                initControles();
                cargar();

                let resizeTimer = null;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(() => {
                        if (MG.data) dibujarArcos();
                    }, 150);
                });
            })();
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>