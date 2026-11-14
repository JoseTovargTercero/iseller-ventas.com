<?php
require_once('configuracion.php');
require_once('session.php');

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['validate']) || $_SESSION['validate'] !== 'ok' || empty($_SESSION['bss_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión no válida.']);
    exit;
}

$nivel = (int) ($_SESSION['nivel'] ?? 0);
if (!in_array($nivel, [1, 2], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permiso para asignar tasas.']);
    exit;
}

$bssId = (int) $_SESSION['bss_id'];
$accion = (string) ($_POST['accion'] ?? $_GET['accion'] ?? '');
$paresValidos = ['usd_bs', 'usd_cop', 'cop_bs', 'bs_cop'];

function responder(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function productoDelNegocio($conexion, int $productoId, int $bssId): ?array
{
    $stmt = $conexion->prepare("SELECT id FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('ii', $productoId, $bssId);
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $fila ?: null;
}

$productoId = (int) ($_POST['producto_id'] ?? $_GET['producto_id'] ?? 0);
if ($productoId <= 0) {
    responder(['success' => false, 'message' => 'Producto no válido.']);
}
if (!productoDelNegocio($conexion, $productoId, $bssId)) {
    responder(['success' => false, 'message' => 'Producto no encontrado.']);
}

if ($accion === 'obtener') {
    $stmt = $conexion->prepare(
        "SELECT pt.par, pt.tasa_id, tp.nombre, tp.valor
         FROM producto_tasas_personalizadas pt
         INNER JOIN cambio_tasas_personalizadas tp
             ON tp.id = pt.tasa_id AND tp.bss_id = pt.bss_id AND tp.par = pt.par
         WHERE pt.producto_id = ? AND pt.bss_id = ?"
    );
    $asignadas = [];
    if ($stmt) {
        $stmt->bind_param('ii', $productoId, $bssId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $asignadas[$fila['par']] = [
                'tasa_id' => (int) $fila['tasa_id'],
                'nombre' => $fila['nombre'],
                'valor' => (float) $fila['valor'],
            ];
        }
        $stmt->close();
    }

    $stmt = $conexion->prepare(
        "SELECT id, par, nombre, valor
         FROM cambio_tasas_personalizadas
         WHERE bss_id = ?
         ORDER BY FIELD(par, 'usd_bs','usd_cop','cop_bs','bs_cop'), nombre ASC"
    );
    $disponibles = [];
    if ($stmt) {
        $stmt->bind_param('i', $bssId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($fila = $res->fetch_assoc()) {
            $disponibles[] = [
                'id' => (int) $fila['id'],
                'par' => $fila['par'],
                'nombre' => $fila['nombre'],
                'valor' => (float) $fila['valor'],
            ];
        }
        $stmt->close();
    }

    responder([
        'success' => true,
        'producto_id' => $productoId,
        'asignadas' => $asignadas,
        'disponibles' => $disponibles,
    ]);
}

if ($accion === 'preview') {
    require_once('_tasas_cambio.php');
    require_once('_calculadrora_precios.php');

    $stmt = $conexion->prepare("SELECT id, precio_compra, cantidad_unidades, porcentaje, origen, mayor FROM productos WHERE id = ? AND bss_id = ? LIMIT 1");
    if (!$stmt) {
        responder(['success' => false, 'message' => 'No se pudo cargar el producto.']);
    }
    $stmt->bind_param('ii', $productoId, $bssId);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$producto) {
        responder(['success' => false, 'message' => 'Producto no encontrado.']);
    }
    $producto['producto_id'] = (int) $producto['id'];

    // La fila del listado puede traer el porcentaje de la sucursal seleccionada.
    if (isset($_POST['precio']) && is_numeric($_POST['precio'])) {
        $producto['precio_compra'] = (float) $_POST['precio'];
    }
    if (isset($_POST['cantidad']) && is_numeric($_POST['cantidad'])) {
        $producto['cantidad_unidades'] = (float) $_POST['cantidad'];
    }
    if (isset($_POST['porcentaje'])) {
        $porcentajePreview = str_replace(['%', ','], ['', '.'], (string) $_POST['porcentaje']);
        if (is_numeric($porcentajePreview)) {
            $producto['porcentaje'] = (float) $porcentajePreview;
        }
    }
    if (!empty($_POST['origen'])) {
        $producto['origen'] = (string) $_POST['origen'];
    }
    if (isset($_POST['mayor'])) {
        $producto['mayor'] = (string) $_POST['mayor'];
    }

    $raw = $_POST['tasas'] ?? [];
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) {
        $raw = [];
    }

    $dolarBolivar = (float) $dolarBolivar;
    $bcvPreview = (float) $bcv;
    $pesoDolarPreview = (float) $pesoDolar;
    $pesoBolivarPreview = (float) $peso_bolivar;
    $bolivarPesoPreview = (float) $bolivar_peso;

    foreach ($paresValidos as $par) {
        $tasaId = (int) ($raw[$par] ?? 0);
        if ($tasaId <= 0) {
            continue;
        }
        $stmt = $conexion->prepare("SELECT valor FROM cambio_tasas_personalizadas WHERE id = ? AND bss_id = ? AND par = ? LIMIT 1");
        if (!$stmt) {
            continue;
        }
        $stmt->bind_param('iis', $tasaId, $bssId, $par);
        $stmt->execute();
        $tasa = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$tasa) {
            continue;
        }
        $valor = (float) $tasa['valor'];
        if ($valor <= 0) {
            continue;
        }
        switch ($par) {
            case 'usd_bs':
                $dolarBolivar = $valor;
                $bcvPreview = $valor;
                break;
            case 'usd_cop':
                $pesoDolarPreview = $valor;
                break;
            case 'cop_bs':
                $pesoBolivarPreview = $valor;
                break;
            case 'bs_cop':
                $bolivarPesoPreview = $valor;
                break;
        }
    }

    $calculadora = new CalculadoraPrecios(
        $pesoDolarPreview,
        $pesoBolivarPreview,
        $dolarBolivar,
        $bolivarPesoPreview,
        $bcvPreview,
        is_array($data_monedas) ? $data_monedas : []
    );
    $precios = $calculadora->calcularPrecios($producto);
    $cantidad = max(1, (float) $producto['cantidad_unidades']);
    $costoUnitario = ((string) $producto['mayor'] === '1')
        ? (float) $producto['precio_compra']
        : ((float) $producto['precio_compra'] / $cantidad);

    responder([
        'success' => true,
        'precios' => $precios,
        'costo_unitario' => $costoUnitario,
    ]);
}

if ($accion === 'guardar') {
    $raw = $_POST['tasas'] ?? [];
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) {
        $raw = [];
    }

    $seleccionadas = [];
    foreach ($paresValidos as $par) {
        $tasaId = (int) ($raw[$par] ?? 0);
        if ($tasaId <= 0) {
            continue;
        }
        $stmt = $conexion->prepare("SELECT id FROM cambio_tasas_personalizadas WHERE id = ? AND bss_id = ? AND par = ? LIMIT 1");
        if (!$stmt) {
            responder(['success' => false, 'message' => 'Error al validar las tasas.']);
        }
        $stmt->bind_param('iis', $tasaId, $bssId, $par);
        $stmt->execute();
        $valida = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$valida) {
            responder(['success' => false, 'message' => 'Una de las tasas seleccionadas no es válida para el par indicado.']);
        }
        $seleccionadas[$par] = $tasaId;
    }

    $origenNuevo = (string) ($_POST['origen'] ?? '');
    $origenAplicado = in_array($origenNuevo, ['v', 'c', 'm'], true) ? $origenNuevo : null;

    $conexion->begin_transaction();
    try {
        if ($origenAplicado !== null) {
            $upd = $conexion->prepare("UPDATE productos SET origen = ? WHERE id = ? AND bss_id = ?");
            if (!$upd) {
                throw new Exception('Error al preparar el origen.');
            }
            $upd->bind_param('sii', $origenNuevo, $productoId, $bssId);
            if (!$upd->execute()) {
                throw new Exception($upd->error);
            }
            $upd->close();
        }

        $del = $conexion->prepare("DELETE FROM producto_tasas_personalizadas WHERE producto_id = ? AND bss_id = ?");
        if (!$del) {
            throw new Exception('Error al preparar la limpieza.');
        }
        $del->bind_param('ii', $productoId, $bssId);
        if (!$del->execute()) {
            throw new Exception($del->error);
        }
        $del->close();

        if ($seleccionadas) {
            $ins = $conexion->prepare("INSERT INTO producto_tasas_personalizadas (producto_id, par, tasa_id, bss_id) VALUES (?, ?, ?, ?)");
            if (!$ins) {
                throw new Exception('Error al preparar la asignación.');
            }
            foreach ($seleccionadas as $par => $tasaId) {
                $ins->bind_param('isii', $productoId, $par, $tasaId, $bssId);
                if (!$ins->execute()) {
                    throw new Exception($ins->error);
                }
            }
            $ins->close();
        }
        $conexion->commit();
    } catch (Throwable $e) {
        $conexion->rollback();
        responder(['success' => false, 'message' => 'No se pudieron guardar las tasas: ' . $e->getMessage()]);
    }

    $nombres = [];
    if ($seleccionadas) {
        $stmt = $conexion->prepare("SELECT nombre FROM cambio_tasas_personalizadas WHERE id = ? AND bss_id = ? LIMIT 1");
        if ($stmt) {
            foreach ($seleccionadas as $par => $tasaId) {
                $stmt->bind_param('ii', $tasaId, $bssId);
                $stmt->execute();
                $fila = $stmt->get_result()->fetch_assoc();
                if ($fila) {
                    $nombres[$par] = $fila['nombre'];
                }
            }
            $stmt->close();
        }
    }

    responder([
        'success' => true,
        'message' => $seleccionadas ? 'Tasas personalizadas actualizadas.' : 'El producto usa las tasas principales.',
        'asignadas' => $seleccionadas,
        'nombres' => $nombres,
        'origen' => $origenAplicado,
    ]);
}

responder(['success' => false, 'message' => 'Acción no válida.']);
