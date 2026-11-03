<?php
/**
 * Correo de recuperación de contraseña.
 *
 * Se arma como multipart/alternative (texto + HTML) con Date y Message-ID, y
 * con remitente del propio dominio para que pase SPF/DKIM y Gmail no lo mande
 * a spam. Devuelve asunto, cuerpo y cabeceras listos para mail().
 */
declare(strict_types=1);

function email_recuperacion(string $nombre, string $email, string $enlace, string $sitio): array
{
    $remitente = 'no-reply@iseller-tiendas.com';
    $sitio     = rtrim($sitio, '/');

    $nombre = trim((string)preg_replace('/\s+/u', ' ', $nombre));
    $saludo = $nombre !== '' ? 'Hola ' . $nombre . ':' : 'Hola:';

    $emailEsc = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $enlaceEsc = htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8');
    $sitioEsc = htmlspecialchars($sitio, ENT_QUOTES, 'UTF-8');
    $saludoEsc = htmlspecialchars($saludo, ENT_QUOTES, 'UTF-8');

    $texto = $saludo . "\n\n"
        . "Recibimos una solicitud para restablecer la contraseña de tu cuenta de iSeller Store ({$email}).\n\n"
        . "Abre este enlace para crear una contraseña nueva. Vence en 1 hora:\n\n"
        . $enlace . "\n\n"
        . "Si no solicitaste el cambio, ignora este correo: tu contraseña actual sigue funcionando.\n\n"
        . "iSeller Store\n"
        . $sitio . "\n";

    $html = '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Restablece tu contraseña</title></head>'
        . '<body style="margin:0;padding:24px;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" '
        . 'style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px">'
        . '<tr><td style="padding:32px">'
        . '<h1 style="margin:0 0 16px;font-size:20px;color:#111827">Restablece tu contraseña</h1>'
        . '<p style="margin:0 0 12px;font-size:15px;line-height:1.5">' . $saludoEsc . '</p>'
        . '<p style="margin:0 0 12px;font-size:15px;line-height:1.5">Recibimos una solicitud para '
        . 'restablecer la contraseña de tu cuenta de iSeller Store (' . $emailEsc . ').</p>'
        . '<p style="margin:24px 0"><a href="' . $enlaceEsc . '" '
        . 'style="display:inline-block;background:#0f7b4f;color:#ffffff;text-decoration:none;'
        . 'padding:12px 24px;border-radius:10px;font-weight:bold">Crear contraseña nueva</a></p>'
        . '<p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#4b5563">'
        . 'El enlace vence en 1 hora. Si el botón no abre, copia esta dirección:</p>'
        . '<p style="margin:0 0 20px;font-size:12px;line-height:1.5;color:#4b5563;word-break:break-all">'
        . $enlaceEsc . '</p>'
        . '<p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#4b5563">'
        . 'Si no solicitaste el cambio, ignora este correo: tu contraseña actual sigue funcionando.</p>'
        . '<p style="margin:0;font-size:13px;line-height:1.5;color:#6b7280">iSeller Store<br>'
        . '<a href="' . $sitioEsc . '" style="color:#0f7b4f">' . $sitioEsc . '</a></p>'
        . '</td></tr></table></body></html>';

    $frontera = 'iseller_' . bin2hex(random_bytes(12));

    $cuerpo = "--{$frontera}\r\n"
        . "Content-Type: text/plain; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($texto), 76, "\r\n") . "\r\n"
        . "--{$frontera}\r\n"
        . "Content-Type: text/html; charset=UTF-8\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html), 76, "\r\n") . "\r\n"
        . "--{$frontera}--\r\n";

    $cabeceras = 'From: iSeller Store <' . $remitente . ">\r\n"
        . 'Reply-To: iSeller Store <' . $remitente . ">\r\n"
        . 'Date: ' . date('r') . "\r\n"
        . 'Message-ID: <' . bin2hex(random_bytes(16)) . '@iseller-tiendas.com>' . "\r\n"
        . "MIME-Version: 1.0\r\n"
        . 'Content-Type: multipart/alternative; boundary="' . $frontera . '"';

    return [
        'asunto'    => mb_encode_mimeheader('Restablece tu contraseña de iSeller Store', 'UTF-8', 'B'),
        'cuerpo'    => $cuerpo,
        'cabeceras' => $cabeceras,
        // Sobre del remitente: sin esto el SPF se valida contra el host del
        // servidor y no contra el dominio.
        'sobre'     => '-f' . $remitente,
    ];
}
