<?php
/**
 * Precio de compra de productos y sincronización con sus presentaciones al mayor.
 *
 * Cada cambio relevante se registra en productos_precio_historial para poder
 * auditar cuándo cambió, desde qué proceso y con qué valores.
 */

function producto_precio_float($valor): float
{
    $texto = trim((string) $valor);
    if ($texto === '') {
        return 0.0;
    }
    $texto = str_replace([' ', '$'], '', $texto);
    if (strpos($texto, ',') !== false) {
        // "1.234,56" -> 1234.56 | "1234,56" -> 1234.56
        if (strpos($texto, '.') !== false) {
            $texto = str_replace('.', '', $texto);
        }
        $texto = str_replace(',', '.', $texto);
    }
    return round((float) $texto, 4);
}

function producto_precio_texto(float $valor): string
{
    $texto = number_format($valor, 4, '.', '');
    $texto = rtrim(rtrim($texto, '0'), '.');
    return $texto === '' ? '0' : $texto;
}

function producto_asegurar_historial_precio($conexion): bool
{
    static $listo = null;
    if ($listo !== null) {
        return $listo;
    }

    $res = $conexion->query("SHOW TABLES LIKE 'productos_precio_historial'");
    if ($res && $res->num_rows > 0) {
        $res->free();
        return $listo = true;
    }

    $listo = (bool) $conexion->query(
        "CREATE TABLE IF NOT EXISTS `productos_precio_historial` (
            `id`                     INT(11)       NOT NULL AUTO_INCREMENT,
            `bss_id`                 INT(11)       NOT NULL,
            `producto_id`            INT(11)       NOT NULL,
            `producto_nombre`        VARCHAR(255)  NOT NULL DEFAULT '',
            `es_mayor`               TINYINT(1)    NOT NULL DEFAULT 0,
            `producto_relacionado_id` INT(11)      DEFAULT NULL,
            `precio_anterior`        DECIMAL(15,4) NOT NULL DEFAULT 0,
            `precio_nuevo`           DECIMAL(15,4) NOT NULL DEFAULT 0,
            `origen`                 VARCHAR(30)   NOT NULL DEFAULT '',
            `id_sucursal`            INT(11)       DEFAULT NULL,
            `usuario_id`             INT(11)       NOT NULL DEFAULT 0,
            `usuario_nombre`         VARCHAR(150)  NOT NULL DEFAULT '',
            `created_at`             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_pph_bss_producto` (`bss_id`, `producto_id`, `created_at`),
            KEY `idx_pph_bss_fecha` (`bss_id`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    return $listo;
}

function producto_registrar_historial_precio(
    $conexion,
    int $bssId,
    int $productoId,
    string $productoNombre,
    bool $esMayor,
    ?int $relacionadoId,
    float $precioAnterior,
    float $precioNuevo,
    string $origen,
    int $usuarioId,
    string $usuarioNombre,
    ?int $sucursalId
): void {
    if (abs($precioAnterior - $precioNuevo) < 0.0001) {
        return;
    }

    $stmt = $conexion->prepare(
        "INSERT INTO productos_precio_historial
         (bss_id, producto_id, producto_nombre, es_mayor, producto_relacionado_id,
          precio_anterior, precio_nuevo, origen, id_sucursal, usuario_id, usuario_nombre)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    if (!$stmt) {
        return;
    }

    $esMayorInt = $esMayor ? 1 : 0;
    $precioAnterior = round($precioAnterior, 4);
    $precioNuevo = round($precioNuevo, 4);
    $stmt->bind_param(
        'iisiiddsiis',
        $bssId,
        $productoId,
        $productoNombre,
        $esMayorInt,
        $relacionadoId,
        $precioAnterior,
        $precioNuevo,
        $origen,
        $sucursalId,
        $usuarioId,
        $usuarioNombre
    );
    @$stmt->execute();
    $stmt->close();
}

/**
 * Actualiza el precio de compra del producto y, si es un producto base,
 * sincroniza automáticamente sus presentaciones al mayor.
 */
function producto_actualizar_precio_compra(
    $conexion,
    int $bssId,
    int $productoId,
    $precioNuevo,
    string $origen,
    int $usuarioId = 0,
    string $usuarioNombre = '',
    ?int $sucursalId = null
): array {
    $precioNuevo = producto_precio_float($precioNuevo);

    $stmt = $conexion->prepare(
        "SELECT id, nombre, precio_compra, mayor, id_producto_relacionado
         FROM productos WHERE id = ? AND bss_id = ? LIMIT 1"
    );
    if (!$stmt) {
        return ['ok' => false, 'msg' => 'No se pudo consultar el producto.'];
    }
    $stmt->bind_param('ii', $productoId, $bssId);
    $stmt->execute();
    $producto = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$producto) {
        return ['ok' => false, 'msg' => 'Producto no encontrado.'];
    }

    $precioAnterior = producto_precio_float($producto['precio_compra']);
    $esMayor = (int) ($producto['mayor'] ?? 0) === 1;
    $relacionadoId = $producto['id_producto_relacionado'] !== null
        ? (int) $producto['id_producto_relacionado']
        : null;
    $cambio = abs($precioAnterior - $precioNuevo) >= 0.0001;

    if ($cambio) {
        $precioTexto = producto_precio_texto($precioNuevo);
        $stmt = $conexion->prepare("UPDATE productos SET precio_compra = ? WHERE id = ? AND bss_id = ?");
        if ($stmt) {
            $stmt->bind_param('sii', $precioTexto, $productoId, $bssId);
            $stmt->execute();
            $stmt->close();
        }
        producto_registrar_historial_precio(
            $conexion,
            $bssId,
            $productoId,
            (string) $producto['nombre'],
            $esMayor,
            $relacionadoId,
            $precioAnterior,
            $precioNuevo,
            $origen,
            $usuarioId,
            $usuarioNombre,
            $sucursalId
        );
    }

    $mayoresActualizados = 0;
    if (!$esMayor) {
        $stmt = $conexion->prepare(
            "SELECT id, nombre, precio_compra
             FROM productos
             WHERE bss_id = ? AND id_producto_relacionado = ? AND mayor = 1"
        );
        if ($stmt) {
            $stmt->bind_param('ii', $bssId, $productoId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mayores = [];
            while ($fila = $result->fetch_assoc()) {
                $mayores[] = $fila;
            }
            $stmt->close();

            $precioTexto = producto_precio_texto($precioNuevo);
            foreach ($mayores as $mayor) {
                $precioMayorAnterior = producto_precio_float($mayor['precio_compra']);
                if (abs($precioMayorAnterior - $precioNuevo) < 0.0001) {
                    continue;
                }

                $stmtM = $conexion->prepare("UPDATE productos SET precio_compra = ? WHERE id = ? AND bss_id = ?");
                if (!$stmtM) {
                    continue;
                }
                $mayorId = (int) $mayor['id'];
                $stmtM->bind_param('sii', $precioTexto, $mayorId, $bssId);
                $stmtM->execute();
                $stmtM->close();

                producto_registrar_historial_precio(
                    $conexion,
                    $bssId,
                    $mayorId,
                    (string) $mayor['nombre'],
                    true,
                    $productoId,
                    $precioMayorAnterior,
                    $precioNuevo,
                    $origen,
                    $usuarioId,
                    $usuarioNombre,
                    $sucursalId
                );
                $mayoresActualizados++;
            }
        }
    }

    return [
        'ok' => true,
        'cambio' => $cambio,
        'precio_anterior' => $precioAnterior,
        'precio_nuevo' => $precioNuevo,
        'mayores_actualizados' => $mayoresActualizados,
    ];
}
