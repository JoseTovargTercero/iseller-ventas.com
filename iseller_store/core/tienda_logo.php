<?php
/**
 * Miniatura comprimida del logo de una tienda (WebP, 96x96 por defecto).
 *
 * Genera la miniatura una sola vez y la cachea en assets/img/tiendas/thumbs/.
 * Así el cintillo no descarga el logo completo (que puede pesar cientos de KB).
 *
 * @param string $logoRel Ruta relativa a iseller_store/ (columna negocio.logo_url)
 * @return string Ruta relativa a iseller_store/ de la miniatura, o '' si no hay logo.
 */
function tienda_logo_thumb(string $logoRel, int $tamano = 96, int $calidad = 80): string
{
    $logoRel = trim($logoRel);
    if ($logoRel === '') {
        return '';
    }
    // Un logo externo no se puede comprimir desde aquí: se deja como está.
    if (preg_match('#^https?://#i', $logoRel)) {
        return $logoRel;
    }

    $base = dirname(__DIR__); // iseller_store/
    $origen = $base . '/' . ltrim(str_replace('\\', '/', $logoRel), '/');
    if (!is_file($origen)) {
        return '';
    }

    $dirThumbs = $base . '/assets/img/tiendas/thumbs';
    if (!is_dir($dirThumbs) && !@mkdir($dirThumbs, 0775, true) && !is_dir($dirThumbs)) {
        return $logoRel;
    }

    $sello = @filemtime($origen) . '|' . @filesize($origen);
    $clave = md5($logoRel . '|' . $sello . '|' . $tamano . '|' . $calidad);
    $destino = $dirThumbs . '/' . $clave . '.webp';
    $url = 'assets/img/tiendas/thumbs/' . $clave . '.webp';

    if (is_file($destino) && filesize($destino) > 0) {
        return $url;
    }

    if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
        return $logoRel;
    }

    $datos = @file_get_contents($origen);
    if ($datos === false) {
        return $logoRel;
    }
    $src = @imagecreatefromstring($datos);
    if (!$src) {
        return $logoRel;
    }

    $ancho = imagesx($src);
    $alto = imagesy($src);
    if ($ancho < 1 || $alto < 1) {
        imagedestroy($src);
        return $logoRel;
    }

    $lienzo = imagecreatetruecolor($tamano, $tamano);
    if (!$lienzo) {
        imagedestroy($src);
        return $logoRel;
    }

    imagealphablending($lienzo, false);
    imagesavealpha($lienzo, true);
    $transparente = imagecolorallocatealpha($lienzo, 0, 0, 0, 127);
    imagefilledrectangle($lienzo, 0, 0, $tamano, $tamano, $transparente);

    // Encajar el logo completo dentro del cuadrado, sin recortarlo.
    $escala = min($tamano / $ancho, $tamano / $alto);
    $nuevoAncho = max(1, (int)round($ancho * $escala));
    $nuevoAlto = max(1, (int)round($alto * $escala));
    $x = (int)(($tamano - $nuevoAncho) / 2);
    $y = (int)(($tamano - $nuevoAlto) / 2);

    imagealphablending($lienzo, true);
    imagecopyresampled($lienzo, $src, $x, $y, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
    imagealphablending($lienzo, false);

    @imagewebp($lienzo, $destino, $calidad);
    imagedestroy($src);
    imagedestroy($lienzo);

    return (is_file($destino) && filesize($destino) > 0) ? $url : $logoRel;
}
