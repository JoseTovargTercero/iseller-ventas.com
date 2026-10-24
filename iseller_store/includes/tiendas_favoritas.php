<?php
// Sección "Tus tiendas favoritas": solo se pinta si el cliente tiene favoritas.
if (!isLoggedIn()) {
    return;
}

require_once(__DIR__ . '/../core/favoritos.php');
if (!favoritos_asegurar($conexion_store)) {
    return;
}

$stmtFav = $conexion_store->prepare("
    SELECT n.id, n.nombre, n.nombre_visible, n.logo_url, n.portada_url, n.categoria, n.slug,
           (SELECT COUNT(*) FROM negocio_productos np
            WHERE np.negocio_id = n.id AND np.estado_publicacion = 1) AS total_productos
      FROM tienda_favoritos f
      INNER JOIN negocio n ON n.id = f.negocio_id
     WHERE f.usuario_id = ?
       AND n.tiene_tienda_virtual = 1
       AND n.estado = 'activo'
       AND n.slug IS NOT NULL AND n.slug <> ''
     ORDER BY f.creado_en DESC
");
if (!$stmtFav) {
    return;
}

$uidFav = (int)getUserId();
$stmtFav->bind_param('i', $uidFav);
$stmtFav->execute();
$favoritas = $stmtFav->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtFav->close();

if (!$favoritas) {
    return;
}
?>
<section class="container-fluid px-3 px-md-5 favoritas-section">
    <div class="favoritas-head">
        <div>
            <h2><i class="bi bi-heart-fill"></i> Tus tiendas favoritas</h2>
            <p>Las tiendas que guardaste para volver rápido.</p>
        </div>
    </div>
    <div class="favoritas-grid">
        <?php foreach ($favoritas as $fav):
            $portada_fav = $fav['portada_url'] ?? '';
            if ($portada_fav && is_file(__DIR__ . '/../' . substr($portada_fav, 0, -5) . '_min.webp')) {
                $portada_fav = substr($portada_fav, 0, -5) . '_min.webp';
            }
        ?>
            <a href="<?= htmlspecialchars($script_dir . '/' . $fav['slug']) ?>" class="tienda-chip">
                <div class="tienda-chip-cover">
                    <?php if ($portada_fav !== ''): ?>
                        <img src="<?= htmlspecialchars($portada_fav) ?>" onerror="imgFallback(this)" alt="" loading="lazy" decoding="async">
                    <?php else: ?>
                        <i class="bi bi-image"></i>
                    <?php endif; ?>
                </div>
                <div class="tienda-chip-body">
                    <div class="tienda-chip-logo">
                        <?php if (!empty($fav['logo_url'])): ?>
                            <img src="<?= htmlspecialchars($fav['logo_url']) ?>" onerror="imgFallback(this)" alt="<?= htmlspecialchars($fav['nombre_visible'] ?: $fav['nombre']) ?>" loading="lazy">
                        <?php else: ?>
                            <i class="bi bi-shop"></i>
                        <?php endif; ?>
                    </div>
                    <div class="tienda-chip-info">
                        <span class="tienda-chip-name">
                            <?= htmlspecialchars($fav['nombre_visible'] ?: $fav['nombre']) ?>
                            <i class="bi bi-patch-check-fill verified-dot"></i>
                        </span>
                        <span class="tienda-chip-cat"><?= htmlspecialchars($fav['categoria'] ?: 'Tienda') ?></span>
                        <span class="tienda-chip-count"><?= number_format((int)$fav['total_productos'], 0, ',', '.') ?> productos</span>
                    </div>
                    <i class="bi bi-chevron-right tienda-chip-arrow"></i>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
