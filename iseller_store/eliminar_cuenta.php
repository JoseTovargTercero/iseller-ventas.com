<?php
// Página pública para solicitar la eliminación de la cuenta de iSeller Store.
// Requisito de Google Play: recurso web accesible sin instalar la app.
header('Content-Type: text/html; charset=UTF-8');

$correo = 'soporte@iseller-tiendas.com';
$asunto = rawurlencode('Eliminar mi cuenta de iSeller Store');
$cuerpo = rawurlencode(
    "Hola, solicito la eliminación de mi cuenta de iSeller Store y de los datos asociados.\n\n" .
    "Correo de la cuenta: \n" .
    "Nombre registrado: \n" .
    "Teléfono (opcional): \n"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar mi cuenta · iSeller Store</title>
    <style>
        :root { --tinta: #111827; --gris: #6b7280; --linea: #e5e7eb; --verde: #1a7f4b; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8fafc; color: var(--tinta);
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height: 1.6; }
        header { background: #fff; border-bottom: 1px solid var(--linea); padding: 16px 20px; }
        header a { color: var(--verde); font-weight: 700; text-decoration: none; font-size: 18px; }
        main { max-width: 720px; margin: 0 auto; padding: 28px 20px 60px; }
        h1 { font-size: 26px; margin: 0 0 12px; }
        p, li { font-size: 15px; }
        .caja { background: #fff; border: 1px solid var(--linea); border-radius: 12px; padding: 18px 20px; margin: 18px 0; }
        .boton { display: inline-block; background: var(--verde); color: #fff; text-decoration: none;
                 padding: 12px 20px; border-radius: 10px; font-weight: 600; }
        .nota { color: var(--gris); font-size: 13px; }
        a { color: var(--verde); }
    </style>
</head>
<body>
<header><a href="https://iseller-tiendas.com/">iSeller Store</a></header>
<main>
    <h1>Eliminar mi cuenta de iSeller Store</h1>
    <p>
        Desde aquí puedes solicitar la eliminación de tu cuenta de la app <strong>iSeller Store</strong>
        (paquete <code>com.iseller.clientes</code>) y de los datos personales asociados.
    </p>

    <div class="caja">
        <p><strong>Forma rápida:</strong> envía la solicitud desde el correo con el que te registraste.</p>
        <p>
            <a class="boton"
               href="mailto:<?= htmlspecialchars($correo) ?>?subject=<?= $asunto ?>&body=<?= $cuerpo ?>">
                Solicitar eliminación por correo
            </a>
        </p>
        <p class="nota">
            También puedes escribir directamente a
            <a href="mailto:<?= htmlspecialchars($correo) ?>"><?= htmlspecialchars($correo) ?></a>
            indicando el correo de tu cuenta.
        </p>
    </div>

    <div class="caja">
        <p><strong>Desde la app:</strong> abre <em>Perfil → Eliminar mi cuenta</em> y seguirás el mismo proceso.</p>
    </div>

    <h2>Qué se elimina</h2>
    <ul>
        <li>Tu cuenta: nombre, correo, teléfono, estado y municipio.</li>
        <li>Tus direcciones de entrega y datos de ubicación guardados.</li>
        <li>Tus mercados guardados y token de notificaciones.</li>
    </ul>
    <p class="nota">
        Conservamos el historial de pedidos ya facturados durante el tiempo exigido por obligaciones contables y fiscales.
        El resto de los datos se elimina en un plazo de hasta 30 días desde la confirmación de la solicitud.
    </p>

    <h2>Antes de continuar</h2>
    <p class="nota">
        La eliminación es permanente. Si solo quieres dejar de recibir notificaciones, puedes cerrar sesión en la app
        o desactivarlas desde los ajustes del teléfono.
    </p>
</main>
</body>
</html>
