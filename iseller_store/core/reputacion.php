<?php
/**
 * Cálculo y presentación de la reputación de tiendas.
 * Umbral: 5 calificaciones para publicar el porcentaje.
 */
function reputacion_nivel($total, $pct)
{
    $total = (int)$total;

    if ($total < 5 || $pct === null || $pct === '') {
        return ['label' => 'Sin reputación', 'color' => '#9e9e9e', 'bars' => 0, 'visible' => false];
    }

    $pct = (float)$pct;
    if ($pct >= 95) {
        return ['label' => 'Excelente', 'color' => '#00a650', 'bars' => 5, 'visible' => true];
    }
    if ($pct >= 85) {
        return ['label' => 'Muy bueno', 'color' => '#7ac943', 'bars' => 4, 'visible' => true];
    }
    if ($pct >= 70) {
        return ['label' => 'Bueno', 'color' => '#ffce00', 'bars' => 3, 'visible' => true];
    }
    if ($pct >= 50) {
        return ['label' => 'Regular', 'color' => '#ff9f00', 'bars' => 2, 'visible' => true];
    }
    return ['label' => 'Bajo', 'color' => '#f23d4f', 'bars' => 1, 'visible' => true];
}

/**
 * Recalcula el agregado cacheado en `negocio` y lo devuelve.
 */
function reputacion_recalcular($conexion, $negocio_id)
{
    $negocio_id = (int)$negocio_id;

    // Serializa el recálculo entre votos concurrentes del mismo negocio.
    $stmt = $conexion->prepare("SELECT id FROM negocio WHERE id = ? FOR UPDATE");
    $stmt->bind_param("i", $negocio_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conexion->prepare("
        SELECT COUNT(*) AS total, COALESCE(SUM(recomienda = 1), 0) AS recomiendan
        FROM tienda_reputacion_votos
        WHERE negocio_id = ?
    ");
    $stmt->bind_param("i", $negocio_id);
    $stmt->execute();
    $agregado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total       = (int)$agregado['total'];
    $recomiendan = (int)$agregado['recomiendan'];
    $pct         = $total >= 5 ? round($recomiendan * 100 / $total) : null;

    $stmt = $conexion->prepare("
        UPDATE negocio
        SET reputacion_total = ?, reputacion_recomiendan = ?, reputacion_pct = ?, reputacion_actualizada = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("iidi", $total, $recomiendan, $pct, $negocio_id);
    $stmt->execute();
    $stmt->close();

    return [
        'total'       => $total,
        'recomiendan' => $recomiendan,
        'pct'         => $pct,
        'visible'     => $total >= 5
    ];
}

/**
 * Nombre público de un comprador: "María G." (nunca email ni teléfono).
 */
function reputacion_nombre_publico($nombre)
{
    $nombre = trim((string)$nombre);
    if ($nombre === '') {
        return 'Comprador';
    }

    $partes = preg_split('/\s+/u', $nombre);
    $primero = $partes[0];
    if (count($partes) > 1) {
        $inicial = mb_substr(end($partes), 0, 1, 'UTF-8');
        return $primero . ' ' . mb_strtoupper($inicial, 'UTF-8') . '.';
    }

    return $primero;
}
