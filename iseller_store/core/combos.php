<?php
/**
 * Utilidades de combos: piezas que lo forman y cuántos se pueden armar
 * con el stock actual de una sucursal.
 */

if (!function_exists('combo_items')) {
    function combo_items($conexion, int $comboId, int $bssId): array
    {
        $stmt = $conexion->prepare(
            "SELECT ci.producto_id, ci.cantidad, p.nombre
               FROM combo_items ci
               INNER JOIN productos p ON p.id = ci.producto_id
              WHERE ci.combo_producto_id = ? AND ci.bss_id = ?
              ORDER BY ci.id ASC"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $comboId, $bssId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'producto_id' => (int)$row['producto_id'],
                'cantidad'    => (float)$row['cantidad'],
                'nombre'      => (string)$row['nombre'],
            ];
        }
        return $items;
    }
}

if (!function_exists('combo_stock')) {
    /** Combos armables en una sucursal. Si falta stock de una pieza, devuelve 0. */
    function combo_stock($conexion, int $comboId, int $bssId, int $sucursalId, ?array &$items = null): int
    {
        $items = combo_items($conexion, $comboId, $bssId);
        if (!$items) {
            return 0;
        }

        $stmt = $conexion->prepare(
            "SELECT COALESCE(SUM(CAST(stock AS DECIMAL(12,2))), 0) AS stock
               FROM stock
              WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?"
        );
        if (!$stmt) {
            return 0;
        }

        $posibles = null;
        foreach ($items as $item) {
            $cantidad = (float)$item['cantidad'];
            if ($cantidad <= 0) {
                $stmt->close();
                return 0;
            }

            $productoId = (int)$item['producto_id'];
            $stmt->bind_param('iii', $productoId, $sucursalId, $bssId);
            $stmt->execute();
            $stock = (float)($stmt->get_result()->fetch_assoc()['stock'] ?? 0);
            $armables = (int)floor($stock / $cantidad);
            if ($posibles === null || $armables < $posibles) {
                $posibles = $armables;
            }
        }
        $stmt->close();

        return max(0, (int)($posibles ?? 0));
    }
}
