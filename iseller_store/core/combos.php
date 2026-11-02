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

if (!function_exists('producto_stock_info')) {
    /**
     * Stock de un producto en una sucursal, expresado en unidades de venta.
     * Para productos al mayor convierte el stock base (unidades) a pacas.
     */
    function producto_stock_info($conexion, int $productoId, int $sucursalId, int $bssId): ?array
    {
        $stmt = $conexion->prepare(
            "SELECT s.id, s.id_stock, s.stock, p.mayor, p.cantidad_unidades
               FROM stock s
               INNER JOIN productos p ON p.id = s.id_producto
              WHERE s.id_producto = ? AND s.id_sucursal = ? AND s.bss_id = ?
              LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('iii', $productoId, $sucursalId, $bssId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }

        $rowId = (int)$row['id'];
        $pack = 1.0;
        $stock = (float)$row['stock'];

        if ((string)$row['mayor'] === '1') {
            $pack = max(1.0, (float)$row['cantidad_unidades']);
            $baseId = (int)($row['id_stock'] ?? 0);
            if ($baseId > 0) {
                $stmtBase = $conexion->prepare("SELECT stock FROM stock WHERE id = ? AND bss_id = ? LIMIT 1");
                if ($stmtBase) {
                    $stmtBase->bind_param('ii', $baseId, $bssId);
                    $stmtBase->execute();
                    $base = $stmtBase->get_result()->fetch_assoc();
                    $stmtBase->close();
                    if ($base) {
                        $rowId = $baseId;
                        $stock = $pack > 0 ? ((float)$base['stock'] / $pack) : 0;
                    }
                }
            }
        }

        return [
            'row_id' => $rowId,
            'stock'  => round($stock, 2),
            'pack'   => $pack,
            'mayor'  => (int)($row['mayor'] ?? 0),
        ];
    }
}

if (!function_exists('producto_stock_descontar')) {
    /** Descuenta stock en unidades de venta; para al mayor usa el stock base. */
    function producto_stock_descontar($conexion, int $productoId, int $sucursalId, int $bssId, float $cantidad): bool
    {
        $info = producto_stock_info($conexion, $productoId, $sucursalId, $bssId);
        if (!$info || $cantidad <= 0) {
            return false;
        }

        $descontar = $cantidad * (float)$info['pack'];
        $rowId = (int)$info['row_id'];
        $stmt = $conexion->prepare(
            "UPDATE stock SET stock = stock - ? WHERE id = ? AND bss_id = ? AND stock >= ?"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('diid', $descontar, $rowId, $bssId, $descontar);
        $stmt->execute();
        $ok = $stmt->affected_rows === 1;
        $stmt->close();
        return $ok;
    }
}

if (!function_exists('producto_stock_devolver')) {
    /** Devuelve stock en unidades de venta; para al mayor usa el stock base. */
    function producto_stock_devolver($conexion, int $productoId, int $sucursalId, int $bssId, float $cantidad): bool
    {
        $info = producto_stock_info($conexion, $productoId, $sucursalId, $bssId);
        if (!$info || $cantidad <= 0) {
            return false;
        }

        $devolver = $cantidad * (float)$info['pack'];
        $rowId = (int)$info['row_id'];
        $stmt = $conexion->prepare(
            "UPDATE stock SET stock = stock + ? WHERE id = ? AND bss_id = ?"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('dii', $devolver, $rowId, $bssId);
        $stmt->execute();
        $ok = $stmt->affected_rows === 1;
        $stmt->close();
        return $ok;
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

        $posibles = null;
        foreach ($items as $item) {
            $cantidad = (float)$item['cantidad'];
            if ($cantidad <= 0) {
                return 0;
            }

            $info = producto_stock_info(
                $conexion,
                (int)$item['producto_id'],
                $sucursalId,
                $bssId
            );
            $stock = $info ? (float)$info['stock'] : 0;
            $armables = (int)floor($stock / $cantidad);
            if ($posibles === null || $armables < $posibles) {
                $posibles = $armables;
            }
        }

        return max(0, (int)($posibles ?? 0));
    }
}
