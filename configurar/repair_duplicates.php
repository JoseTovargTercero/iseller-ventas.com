<?php
// Configuración básica
require_once('configuracion.php'); // Asegúrate de que esto conecte a la BD en $conexion

// Configuración del script
$id_sucursal = 12;
$fecha_inicio = '2026-01-01';
$fecha_fin = '2026-02-01';
$dry_run = false; // Cambiar a false para ejecutar los cambios reales

$limit = 1000; // Cantidad de grupos de duplicados a procesar por ejecución

echo "<pre>";
echo "Iniciando proceso de limpieza de duplicados...\n";
echo "Sucursal: $id_sucursal\n";
echo "Rango de fechas: $fecha_inicio a $fecha_fin\n";
echo "Límite por ejecución: $limit grupos\n";
echo "Modo: " . ($dry_run ? "SIMULACIÓN (No se borrará nada)" : "EJECUCIÓN REAL") . "\n\n";

if (!$conexion) {
    die("Error de conexión a la base de datos.");
}

try {
    // Iniciar transacción
    $conexion->begin_transaction();

    // 1. Identificar duplicados
    $sql = "
        SELECT 
            GROUP_CONCAT(id ORDER BY id ASC) as ids, 
            COUNT(*) as cnt,
            total_price,
            created
        FROM orden 
        WHERE id_sucursal = ? 
        AND modified BETWEEN ? AND ?
        GROUP BY total_price, created, id_sucursal
        HAVING cnt > 1
        LIMIT ?
    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issi", $id_sucursal, $fecha_inicio, $fecha_fin, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $ordenes_eliminadas = 0;
    $articulos_restaurados = 0;

    while ($row = $result->fetch_assoc()) {
        $ids = explode(',', $row['ids']);
        $total_price = $row['total_price'];
        $created = $row['created'];
        
        // Dejar el primero como válido (el ID más bajo)
        $id_valido = array_shift($ids); 
        
        echo "Grupo duplicado encontrado (Total: $total_price, Creado: $created):\n";
        echo " - ID Válido (conservado): $id_valido\n";
        echo " - IDs a eliminar: " . implode(', ', $ids) . "\n";

        foreach ($ids as $id_eliminar) {
            // 2. Obtener artículos de la orden a eliminar
            $sql_art = "SELECT product_id, quantity FROM orden_articulos WHERE order_id = ?";
            $stmt_art = $conexion->prepare($sql_art);
            $stmt_art->bind_param("i", $id_eliminar);
            $stmt_art->execute();
            $res_art = $stmt_art->get_result();

            while ($art = $res_art->fetch_assoc()) {
                $product_id = $art['product_id'];
                $quantity = $art['quantity'];

                // 3. Restaurar stock
                if (!$dry_run) {
                    $sql_stock = "UPDATE stock SET stock = stock + ? WHERE id_producto = ? AND id_sucursal = ?";
                    $stmt_stock = $conexion->prepare($sql_stock);
                    $stmt_stock->bind_param("dii", $quantity, $product_id, $id_sucursal);
                    $stmt_stock->execute();
                    $stmt_stock->close();
                }
                echo "   -> Producto $product_id: Restaurando $quantity al stock.\n";
                $articulos_restaurados++;
            }
            $stmt_art->close();

            // 4. Eliminar artículos de la orden
            if (!$dry_run) {
                $sql_del_art = "DELETE FROM orden_articulos WHERE order_id = ?";
                $stmt_del_art = $conexion->prepare($sql_del_art);
                $stmt_del_art->bind_param("i", $id_eliminar);
                $stmt_del_art->execute();
                $stmt_del_art->close();
            }

            // 5. Eliminar la orden duplicada
            if (!$dry_run) {
                $sql_del_ord = "DELETE FROM orden WHERE id = ?";
                $stmt_del_ord = $conexion->prepare($sql_del_ord);
                $stmt_del_ord->bind_param("i", $id_eliminar);
                $stmt_del_ord->execute();
                $stmt_del_ord->close();
            }

            $ordenes_eliminadas++;
        }
        echo "---------------------------------------------------\n";
    }

    if ($dry_run) {
        $conexion->rollback();
        echo "\nFin de la simulación. No se realizaron cambios.\n";
    } else {
        $conexion->commit();
        echo "\nProceso finalizado exitosamente.\n";
        echo "Órdenes eliminadas: $ordenes_eliminadas\n";
        echo "Registros de artículos restaurados: $articulos_restaurados\n";
    }

} catch (Exception $e) {
    $conexion->rollback();
    echo "ERROR: Ocurrió un problema y se revirtieron los cambios.\n";
    echo $e->getMessage();
}

echo "</pre>";
?>
