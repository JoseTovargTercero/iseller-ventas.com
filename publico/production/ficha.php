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

    /* ─── Sucursales (para el filtro de período) ────────────────── */
    $stmtS = $conexion->prepare('SELECT id, nombre FROM sucursales WHERE bss_id = ?');
    $stmtS->bind_param('s', $bss_id);
    $stmtS->execute();
    $sucursalesRows = $stmtS->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtS->close();

    /* ─── Usuarios (para el filtro y el desglose por sucursal) ───── */
    $stmtU = $conexion->prepare('SELECT id, nombre, id_sucursal FROM usuarios WHERE bss_id = ? ORDER BY nombre');
    $stmtU->bind_param('s', $bss_id);
    $stmtU->execute();
    $usuariosRows = $stmtU->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtU->close();

    $sucursalesPorId = [];
    foreach ($sucursalesRows as $suc) {
        $sucursalesPorId[(int) $suc['id']] = (string) $suc['nombre'];
    }

    $usuariosSelect = [];
    foreach ($usuariosRows as $u) {
        $sucursalUsuario = (int) ($u['id_sucursal'] ?? 0);
        $usuariosSelect[] = [
            'id'          => (int) $u['id'],
            'nombre'      => (string) ($u['nombre'] ?? ''),
            'id_sucursal' => $sucursalUsuario,
            'sucursal'    => $sucursalesPorId[$sucursalUsuario] ?? '',
        ];
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

            /* ── Selects del filtro ──────────────────────────────────── */
            select.filter-input {
                min-width: 170px;
                appearance: none;
                -webkit-appearance: none;
                background-image:
                    linear-gradient(45deg, transparent 50%, #64748b 50%),
                    linear-gradient(135deg, #64748b 50%, transparent 50%);
                background-position:
                    calc(100% - 16px) 50%,
                    calc(100% - 11px) 50%;
                background-size: 5px 5px, 5px 5px;
                background-repeat: no-repeat;
                padding-right: 30px;
            }

            select.filter-input option {
                background: #0f172a;
                color: #e2e8f0;
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

            .branch-total {
                margin-left: auto;
                font-size: 11px;
                font-weight: 600;
                letter-spacing: 0;
                text-transform: none;
                color: #2dd4bf;
            }

            /* ── Detalle de ventas ───────────────────────────────────── */
            .sales-details {
                margin-top: 14px;
                padding-top: 12px;
                border-top: 1px dashed rgba(255, 255, 255, .08);
            }

            .sales-details summary {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
                list-style: none;
                color: #2dd4bf;
                font-size: 12px;
                font-weight: 600;
                user-select: none;
            }

            .sales-details summary::-webkit-details-marker {
                display: none;
            }

            .sales-details summary::before {
                content: '▸';
                font-size: 10px;
                transition: transform .2s ease;
            }

            .sales-details[open] summary::before {
                transform: rotate(90deg);
            }

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

            /* ── Responsive ──────────────────────────────────────────── */
            @media (max-width: 640px) {
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

                .filter-field,
                select.filter-input {
                    width: 100%;
                }

                .branch-summary {
                    grid-template-columns: 1fr 1fr;
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
                                    Movimiento de stock
                                </span>
                                <div class="preset-chips">
                                    <span class="preset-chip stock-chip" data-stock-days="7">7 días</span>
                                    <span class="preset-chip stock-chip active" data-stock-days="30">30 días</span>
                                    <span class="preset-chip stock-chip" data-stock-days="90">90 días</span>
                                    <span class="preset-chip stock-chip" data-stock-days="365">1 año</span>
                                </div>
                            </div>
                            <div class="ficha-panel-body">
                                <div class="stock-legend">
                                    <span><i style="background:#3b82f6;"></i> Compras</span>
                                    <span><i style="background:#34d399;"></i> Ventas</span>
                                    <span><i style="background:#f87171;"></i> Mermas</span>
                                    <span id="stock-current" class="stock-current"></span>
                                </div>
                                <div id="stock-chart"></div>
                                <div id="stock-chart-empty" class="state-empty" style="display:none;">
                                    <i class="fa fa-inbox"></i>Sin movimientos en este período
                                </div>
                            </div>
                        </div>

                        <!-- ── Period Filter Panel ─────────────────────────── -->
                        <div class="ficha-panel">
                            <div class="ficha-panel-header">
                                <span class="ficha-panel-title">
                                    <i class="fa fa-filter" style="color:#14b8a6;"></i>
                                    Análisis por Sucursal y Usuario
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
                                    <div class="filter-field">
                                        <label>Sucursal</label>
                                        <select id="fp-sucursal" class="filter-input">
                                            <option value="0">Todas las sucursales</option>
                                            <?php foreach ($sucursalesRows as $suc): ?>
                                                <option value="<?= (int) $suc['id'] ?>"><?= htmlspecialchars($suc['nombre']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="filter-field">
                                        <label>Usuario</label>
                                        <select id="fp-usuario" class="filter-input">
                                            <option value="0">Todos los usuarios</option>
                                        </select>
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
                                <p class="num-sub" style="margin:12px 0 0;">
                                    <i class="fa fa-cube" style="color:#fbbf24;"></i>
                                    Las cajas se calculan con las unidades por caja de cada presentación.
                                    Puedes consultar todas las sucursales o filtrar por una sucursal y un usuario.
                                </p>

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
                let stockChart = null;

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

                        acumulado += evento.tipo === 'compras' ? cantidad : -cantidad;
                        puntos.push({
                            label: etiqueta,
                            y: Math.round(acumulado * 100) / 100,
                            tipo: evento.tipo
                        });
                        if (fechaDia) ultimaFecha = fechaDia;
                    });

                    const total = puntos.length;
                    const datos = {
                        compras: Array(total).fill(null),
                        ventas: Array(total).fill(null),
                        mermas: Array(total).fill(null),
                        neutro: Array(total).fill(null)
                    };

                    for (let j = 1; j < total; j++) {
                        const tipo = puntos[j].tipo;
                        datos[tipo][j - 1] = puntos[j - 1].y;
                        datos[tipo][j] = puntos[j].y;
                    }

                    return {
                        categories: puntos.map(p => p.label),
                        series: [
                            { name: 'Compras', data: datos.compras },
                            { name: 'Ventas', data: datos.ventas },
                            { name: 'Mermas', data: datos.mermas },
                            { name: 'Sin movimiento', data: datos.neutro }
                        ]
                    };
                }

                function renderStockChart(data) {
                    const unidad = data.unidad || 'UND';
                    const stockActualEl = document.getElementById('stock-current');
                    const stockActual = Number(data.stock_actual || 0);
                    if (stockActualEl) {
                        stockActualEl.textContent = 'Stock actual: ' +
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
                        colors: ['#3b82f6', '#34d399', '#f87171', 'rgba(148,163,184,.4)'],
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
                    fetch('ficha_movimientos_stock.php?id=' + encodeURIComponent(productoId) + '&dias=' + encodeURIComponent(dias))
                        .then(r => r.json())
                        .then(data => {
                            if (!data.ok) throw new Error(data.error || 'No se pudieron cargar los movimientos');
                            renderStockChart(data);
                        })
                        .catch(error => {
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

            /* ── Datos para el filtro de usuarios ───────────────────────────── */
            const USUARIOS = <?= json_encode($usuariosSelect, JSON_UNESCAPED_UNICODE) ?>;
            const selSucursal = document.getElementById('fp-sucursal');
            const selUsuario  = document.getElementById('fp-usuario');

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

            function cargarUsuariosSucursal(sucursalId, seleccionado) {
                const anterior = seleccionado !== undefined ? String(seleccionado) : selUsuario.value;
                const lista = sucursalId > 0
                    ? USUARIOS.filter(u => u.id_sucursal === sucursalId || !u.id_sucursal)
                    : USUARIOS;

                selUsuario.innerHTML = '<option value="0">Todos los usuarios</option>' +
                    lista.map(u => {
                        const etiqueta = u.sucursal
                            ? `${u.nombre || 'Sin nombre'} — ${u.sucursal}`
                            : (u.nombre || 'Sin nombre');
                        return `<option value="${u.id}">${esc(etiqueta)}</option>`;
                    }).join('');

                selUsuario.value = lista.some(u => String(u.id) === anterior) ? anterior : '0';
            }

            function renderResumen(data) {
                const sucursales = data.sucursales || [];
                if (!sucursales.length) {
                    return `<div class="state-empty"><i class="fa fa-inbox"></i>Sin ventas del producto en este período</div>`;
                }

                let html = '';
                sucursales.forEach(suc => {
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
                        ? `<p class="truncated-note">Mostrando las ${suc.ventas_mostradas} ventas más recientes de ${suc.ventas_total}. Acota el período o filtra por sucursal/usuario para ver el resto.</p>`
                        : '';

                    html += `
                    <div class="branch-block">
                        <p class="branch-title">
                            <i class="fa fa-map-marker"></i> ${esc(suc.sucursal)}
                            <span class="branch-total">${Number(suc.ventas) || 0} venta(s) · ${money(suc.total)}</span>
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

                        <div class="sales-table-wrap">
                            <table class="ficha-table">
                                <thead>
                                    <tr>
                                        <th>Usuario de la sucursal</th>
                                        <th>Ventas</th>
                                        <th>Cajas</th>
                                        <th>Unidades</th>
                                        <th>Valor vendido</th>
                                        <th>Ganancia</th>
                                        <th>Prom. caja</th>
                                    </tr>
                                </thead>
                                <tbody>${filasUsuarios}</tbody>
                            </table>
                        </div>

                        <details class="sales-details">
                            <summary>Ver ventas que incluyeron el producto (${Number(suc.ventas_total) || 0})</summary>
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
                                    <tbody>${filasVentas}</tbody>
                                </table>
                            </div>
                            ${truncado}
                        </details>
                    </div>`;
                });

                return html;
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

            selSucursal.addEventListener('change', function() {
                cargarUsuariosSucursal(parseInt(this.value, 10) || 0, 0);
                consultarPeriodo();
            });
            selUsuario.addEventListener('change', consultarPeriodo);

            /* ── Period query ───────────────────────────────────────────────── */
            function consultarPeriodo() {
                const desde = document.getElementById('fp-desde').value;
                const hasta = document.getElementById('fp-hasta').value;
                const sucursal = parseInt(selSucursal.value, 10) || 0;
                const usuario = parseInt(selUsuario.value, 10) || 0;
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
                    hasta: hasta,
                    sucursal: sucursal,
                    usuario: usuario
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

            // Auto-load on page open
            cargarUsuariosSucursal(0);
            consultarPeriodo();
        </script>
    </body>

    </html>
<?php
} else {
    define('PAGINA_INICIO', '../../index.php');
    header('Location: ' . PAGINA_INICIO);
}
?>