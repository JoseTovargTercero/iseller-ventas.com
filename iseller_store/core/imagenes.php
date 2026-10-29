<?php
/**
 * Validación y guardado de imágenes subidas (logos y fotos de producto).
 *
 * No confía en la extensión ni en el Content-Type del navegador: comprueba el
 * contenido real (finfo + getimagesize) y recodifica a WebP para descartar
 * payloads, EXIF y archivos que solo "parecen" imágenes.
 *
 * @return string|null Nombre del archivo generado, o null si no es una imagen válida.
 */
function imagen_guardar_webp(
    array $archivo,
    string $directorio,
    string $prefijo,
    int $anchoMaximo = 400,
    int $bytesMaximos = 2097152,
    int $pixelesMaximos = 25000000,
    int $calidad = 85,
    bool $esSubida = true
): ?string {
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    // $esSubida = false solo para archivos que descarga el propio servidor (no vienen de $_FILES).
    $tmp = (string)($archivo['tmp_name'] ?? '');
    $tamano = (int)($archivo['size'] ?? 0);
    if ($tmp === '' || $tamano <= 0 || $tamano > $bytesMaximos || ($esSubida && !is_uploaded_file($tmp))) {
        return null;
    }

    $info = @getimagesize($tmp);
    if (!$info || empty($info['mime'])) {
        return null;
    }
    $mime = (string)$info['mime'];
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return null;
    }

    $ancho = (int)$info[0];
    $alto = (int)$info[1];
    if ($ancho < 1 || $alto < 1 || ($ancho * $alto) > $pixelesMaximos) {
        return null;
    }

    // El MIME detectado por el contenido debe coincidir con el de getimagesize().
    $mimeReal = $mime;
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo ? (string)finfo_file($finfo, $tmp) : '';
    }
    if ($mimeReal !== $mime) {
        return null;
    }

    switch ($mime) {
        case 'image/jpeg':
            $origen = @imagecreatefromjpeg($tmp);
            break;
        case 'image/png':
            $origen = @imagecreatefrompng($tmp);
            break;
        case 'image/webp':
            $origen = @imagecreatefromwebp($tmp);
            break;
        default:
            return null;
    }
    if (!$origen) {
        return null;
    }

    $nuevoAncho = $ancho > $anchoMaximo ? $anchoMaximo : $ancho;
    $nuevoAlto = (int)round($nuevoAncho * $alto / $ancho);
    if ($nuevoAlto < 1) {
        $nuevoAlto = 1;
    }

    $destinoImg = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
    if (!$destinoImg) {
        return null;
    }
    if ($mime === 'image/png' || $mime === 'image/webp') {
        imagealphablending($destinoImg, false);
        imagesavealpha($destinoImg, true);
        $transparente = imagecolorallocatealpha($destinoImg, 255, 255, 255, 127);
        imagefilledrectangle($destinoImg, 0, 0, $nuevoAncho, $nuevoAlto, $transparente);
    }
    imagecopyresampled($destinoImg, $origen, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);

    if (!is_dir($directorio) && !mkdir($directorio, 0775, true)) {
        return null;
    }

    $nombre = $prefijo . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.webp';
    $ruta = rtrim($directorio, '/\\') . DIRECTORY_SEPARATOR . $nombre;
    $ok = imagewebp($destinoImg, $ruta, max(1, min(100, $calidad)));

    return $ok ? $nombre : null;
}
