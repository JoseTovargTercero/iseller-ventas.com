<?php
/**
 * Ranking de los 9 productos más vendidos de una sucursal.
 * Considera las últimas 100 ventas válidas (status 1 = contado, 4 = al mayor), las mismas
 * reglas de los KPIs existentes (ficha.php, admCierre.php). Filtra por id_sucursal ANTES
 * de limitar a 100, de modo que nunca mezcla ventas de otras sucursales.
 *
 * @param mysqli $conexion
 * @param int    $sucursal  id de la sucursal activa (obtenido de la sesión, nunca del cliente)
 * @return int[] ids de producto ordenados de mayor a menor cantidad vendida
 */
function calcular_top_vendidos($conexion, $sucursal)
{
    $ids = [];
    if (!($conexion instanceof mysqli) || !$sucursal) {
        return $ids;
    }

    $stmt = $conexion->prepare(
        "SELECT oa.product_id, SUM(CAST(oa.quantity AS UNSIGNED)) AS total
         FROM (SELECT id FROM orden
               WHERE id_sucursal = ? AND status IN (1, 4)
               ORDER BY created DESC, id DESC
               LIMIT 100) o
         INNER JOIN orden_articulos oa ON oa.order_id = o.id
         GROUP BY oa.product_id
         ORDER BY total DESC, oa.product_id ASC
         LIMIT 9"
    );
    if ($stmt) {
        $stmt->bind_param("i", $sucursal);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['product_id'];
        }
        $stmt->close();
    }
    return $ids;
}
