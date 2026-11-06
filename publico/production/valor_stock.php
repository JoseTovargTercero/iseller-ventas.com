<?php
require_once('includes/requires.php');

$esAdmin = (int) $_SESSION['nivel'] === 1;
if (!$esAdmin && !in_array('valor_stock.php', $_SESSION['permisos'] ?? [], true)) {
    header('Location: ../../index.php');
    exit;
}

$topnav = topnav();

// Sucursales del negocio
$sucursales = [];
$stmt = mysqli_prepare($conexion, "SELECT id, nombre FROM sucursales WHERE bss_id = ? ORDER BY principal DESC, nombre ASC");
$stmt->bind_param('i', $bss_id);
$stmt->execute();
$resSuc = $stmt->get_result();
while ($rowSuc = $resSuc->fetch_assoc()) {
    $sucursales[] = $rowSuc;
}
$stmt->close();

// Sucursal seleccionada: el admin puede elegir; el empleado queda atado a la suya
$sucursalSel = $esAdmin ? (int) ($_GET['sucursal'] ?? 0) : (int) ($_SESSION['sucursal'] ?? 0);
if ($sucursalSel > 0 && !in_array($sucursalSel, array_column($sucursales, 'id'))) {
    $sucursalSel = 0;
}
$condSucursal = $sucursalSel > 0 ? ' AND s.id_sucursal = ' . $sucursalSel : '';

// Resumen: valor al costo (sin ganancia) y ganancia esperada
$sqlResumen = "
    SELECT
        COALESCE(SUM(CAST(s.stock AS DECIMAL(12,2))), 0) AS unidades,
        COALESCE(SUM(CAST(s.stock AS DECIMAL(12,2)) * (CAST(p.precio_compra AS DECIMAL(12,2)) / IFNULL(NULLIF(p.cantidad_unidades, 0), 1))), 0) AS valor_costo,
        COALESCE(SUM(CAST(s.stock AS DECIMAL(12,2)) * (CAST(p.precio_compra AS DECIMAL(12,2)) / IFNULL(NULLIF(p.cantidad_unidades, 0), 1)) * (CAST(p.porcentaje AS DECIMAL(12,2)) / 100)), 0) AS ganancia
    FROM productos p
    INNER JOIN stock s ON s.id_producto = p.id
    WHERE p.activo = 0
      AND p.bss_id = " . (int) $bss_id . "
      AND s.bss_id = " . (int) $bss_id . "
      AND CAST(s.stock AS DECIMAL(12,2)) > 0
      $condSucursal
";
$resResumen = $conexion->query($sqlResumen);
$resumen = $resResumen ? $resResumen->fetch_assoc() : [];
$totalCosto = (float) ($resumen['valor_costo'] ?? 0);
$totalGanancia = (float) ($resumen['ganancia'] ?? 0);
$totalUnidades = (float) ($resumen['unidades'] ?? 0);

// Listado de productos con stock positivo (agrupado por producto si se ven todas las sucursales)
$sqlProductos = "
    SELECT
        p.nombre,
        (CAST(p.precio_compra AS DECIMAL(12,2)) / IFNULL(NULLIF(p.cantidad_unidades, 0), 1)) AS costo_unitario,
        SUM(CAST(s.stock AS DECIMAL(12,2))) AS cantidad,
        SUM(CAST(s.stock AS DECIMAL(12,2)) * (CAST(p.precio_compra AS DECIMAL(12,2)) / IFNULL(NULLIF(p.cantidad_unidades, 0), 1))) AS subtotal
    FROM productos p
    INNER JOIN stock s ON s.id_producto = p.id
    WHERE p.activo = 0
      AND p.bss_id = " . (int) $bss_id . "
      AND s.bss_id = " . (int) $bss_id . "
      AND CAST(s.stock AS DECIMAL(12,2)) > 0
      $condSucursal
    GROUP BY p.id, p.nombre, costo_unitario
    ORDER BY subtotal DESC, p.nombre ASC
";
$resProductos = $conexion->query($sqlProductos);
$productos = [];
$totalLista = 0;
if ($resProductos) {
    while ($row = $resProductos->fetch_assoc()) {
        $totalLista += (float) $row['subtotal'];
        $productos[] = $row;
    }
}

function vs_moneda($valor)
{
    return '$' . number_format((float) $valor, 2, '.', ',');
}

function vs_cantidad($valor)
{
    return rtrim(rtrim(number_format((float) $valor, 2, '.', ','), '0'), '.');
}

function vs_exportar_xlsx(array $productos, float $total, int $sucursalSel)
{
    $esc = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');

    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . '<cols><col min="1" max="1" width="45" customWidth="1"/><col min="2" max="4" width="20" customWidth="1"/></cols>'
        . '<sheetData>'
        . '<row r="1">'
        . '<c r="A1" t="inlineStr"><is><t>Nombre</t></is></c>'
        . '<c r="B1" t="inlineStr"><is><t>Costo unitario</t></is></c>'
        . '<c r="C1" t="inlineStr"><is><t>Cantidad en stock</t></is></c>'
        . '<c r="D1" t="inlineStr"><is><t>Sub total</t></is></c>'
        . '</row>';

    $fila = 1;
    foreach ($productos as $producto) {
        $fila++;
        $sheet .= '<row r="' . $fila . '">'
            . '<c r="A' . $fila . '" t="inlineStr"><is><t>' . $esc($producto['nombre']) . '</t></is></c>'
            . '<c r="B' . $fila . '" s="1"><v>' . number_format((float) $producto['costo_unitario'], 2, '.', '') . '</v></c>'
            . '<c r="C' . $fila . '" s="1"><v>' . number_format((float) $producto['cantidad'], 2, '.', '') . '</v></c>'
            . '<c r="D' . $fila . '" s="1"><v>' . number_format((float) $producto['subtotal'], 2, '.', '') . '</v></c>'
            . '</row>';
    }

    $fila++;
    $sheet .= '<row r="' . $fila . '">'
        . '<c r="A' . $fila . '" t="inlineStr"><is><t>Total</t></is></c>'
        . '<c r="B' . $fila . '"/>'
        . '<c r="C' . $fila . '"/>'
        . '<c r="D' . $fila . '" s="1"><v>' . number_format($total, 2, '.', '') . '</v></c>'
        . '</row>'
        . '</sheetData></worksheet>';

    $partes = [
        '[Content_Types].xml' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>',
        '_rels/.rels' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>',
        'xl/workbook.xml' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Valor del stock" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>',
        'xl/_rels/workbook.xml.rels' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>',
        'xl/styles.xml' =>
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            . '</styleSheet>',
        'xl/worksheets/sheet1.xml' => $sheet,
    ];

    $tmp = tempnam(sys_get_temp_dir(), 'vs_xlsx_');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }
    foreach ($partes as $ruta => $contenido) {
        $zip->addFromString($ruta, $contenido);
    }
    $zip->close();

    $nombre = 'valor_stock_' . ($sucursalSel > 0 ? 'sucursal_' . $sucursalSel . '_' : '') . date('Y-m-d') . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $nombre . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: max-age=0');
    readfile($tmp);
    unlink($tmp);
    exit;
}

if (($_GET['export'] ?? '') === 'xlsx') {
    vs_exportar_xlsx($productos, $totalLista, $sucursalSel);
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Valor del stock</title>
    <?php require_once('includes/headers.php'); ?>
    <link rel="stylesheet" href="theme.css">
    <style>
        .right_col {
            background: var(--dash-bg);
            min-height: 100vh;
            padding: 24px 28px !important;
        }

        .vs-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .vs-header h4 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dash-text);
            margin: 0;
            letter-spacing: -.3px;
        }

        .vs-header p {
            color: var(--dash-text-muted);
            margin: 3px 0 0;
            font-size: 13px;
        }

        .vs-actions {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            flex-wrap: wrap;
        }

        .vs-export {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            height: 38px;
            padding: 0 16px;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, #2dd4a0, #25b88a);
            color: #07130f;
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 3px 12px rgba(45, 212, 160, .25);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .vs-export:hover {
            color: #07130f;
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(45, 212, 160, .35);
        }

        .vs-export ion-icon {
            font-size: 16px;
        }

        .vs-filter {
            display: flex;
            flex-direction: column;
            gap: 5px;
            min-width: 210px;
        }

        .vs-filter label {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .45px;
            color: var(--dash-text-muted);
            margin: 0;
        }

        .vs-filter select {
            width: 100%;
            background: rgba(255, 255, 255, .035);
            border: 1px solid var(--dash-border);
            border-radius: 8px;
            color: var(--dash-text);
            padding: 9px 12px;
            font-size: 13px;
            outline: none;
        }

        .vs-filter select:focus {
            border-color: var(--dash-mint);
            box-shadow: 0 0 0 2px rgba(45, 212, 160, .12);
        }

        .vs-filter select option {
            background: #181b22;
            color: var(--dash-text);
        }

        .vs-kpis {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .vs-kpi {
            position: relative;
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, .02);
            padding: 18px 20px;
            overflow: hidden;
        }

        .vs-kpi::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--vs-color, var(--dash-mint));
        }

        .vs-kpi small {
            display: block;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: .45px;
            text-transform: uppercase;
            color: var(--dash-text-muted);
            margin-bottom: 7px;
        }

        .vs-kpi strong {
            display: block;
            font-size: 26px;
            font-weight: 700;
            color: var(--dash-text);
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .vs-kpi span {
            display: block;
            font-size: 11.5px;
            color: var(--dash-text-muted);
            margin-top: 6px;
        }

        .vs-kpi.costo {
            --vs-color: #5b9cf5;
        }

        .vs-kpi.ganancia {
            --vs-color: var(--dash-mint);
        }

        .vs-kpi.ganancia strong {
            color: var(--dash-mint);
        }

        .vs-money {
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .vs-empty {
            padding: 40px 20px;
            text-align: center;
            color: var(--dash-text-muted);
            font-size: 13px;
        }

        @media (max-width: 640px) {
            .right_col {
                padding: 14px 12px !important;
            }

            .vs-kpis {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .vs-kpi strong {
                font-size: 22px;
            }

            .vs-filter {
                width: 100%;
            }

            .vs-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .vs-export {
                width: 100%;
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
                <div class="vs-header">
                    <div>
                        <h4>Valor del stock</h4>
                        <p>Inventario valorizado al costo (sin ganancia) y ganancia esperada.</p>
                    </div>

                    <form method="get" class="vs-actions">
                        <div class="vs-filter">
                            <label for="sucursal">Sucursal</label>
                            <select name="sucursal" id="sucursal" onchange="this.form.submit()">
                                <?php if ($esAdmin && count($sucursales) > 1): ?>
                                    <option value="0" <?= $sucursalSel === 0 ? 'selected' : '' ?>>Todas las sucursales</option>
                                <?php endif; ?>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <?php if (!$esAdmin && (int) $sucursal['id'] !== $sucursalSel) continue; ?>
                                    <option value="<?= (int) $sucursal['id'] ?>" <?= $sucursalSel === (int) $sucursal['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string) $sucursal['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="export" value="xlsx" class="vs-export">
                            <ion-icon name="download-outline"></ion-icon> Exportar XLSX
                        </button>
                    </form>
                </div>

                <div class="vs-kpis">
                    <div class="vs-kpi costo">
                        <small>Valor del stock (sin ganancia)</small>
                        <strong><?= vs_moneda($totalCosto) ?></strong>
                        <span><?= vs_cantidad($totalUnidades) ?> unidades en stock</span>
                    </div>
                    <div class="vs-kpi ganancia">
                        <small>Ganancia esperada</small>
                        <strong><?= vs_moneda($totalGanancia) ?></strong>
                        <span>Según el porcentaje de ganancia de cada producto</span>
                    </div>
                </div>

                <div class="dash-panel">
                    <div class="panel-header">
                        <h6>
                            <ion-icon name="cube-outline" style="margin-right:8px;font-size:16px;color:var(--dash-mint);"></ion-icon>
                            Productos con stock
                        </h6>
                        <span style="font-size:12px;color:var(--dash-text-muted);"><?= count($productos) ?> productos</span>
                    </div>
                    <div class="panel-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th class="text-end">Costo unitario</th>
                                        <th class="text-end">Cantidad en stock</th>
                                        <th class="text-end">Sub total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($productos) === 0): ?>
                                        <tr>
                                            <td colspan="4" class="vs-empty">No hay productos con stock positivo.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($productos as $producto): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string) $producto['nombre']) ?></td>
                                                <td class="text-end vs-money"><?= vs_moneda($producto['costo_unitario']) ?></td>
                                                <td class="text-end vs-money"><?= vs_cantidad($producto['cantidad']) ?></td>
                                                <td class="text-end vs-money"><?= vs_moneda($producto['subtotal']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <?php if (count($productos) > 0): ?>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total</th>
                                            <th class="text-end vs-money"><?= vs_moneda($totalLista) ?></th>
                                        </tr>
                                    </tfoot>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../vendors/jquery/dist/jquery.min.js"></script>
    <script src="../vendors/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendors/fastclick/lib/fastclick.js"></script>
    <script src="../vendors/nprogress/nprogress.js"></script>
    <script src="../build/js/custom.js"></script>
    <script src="js/nombre_pagina.js"></script>
</body>

</html>
