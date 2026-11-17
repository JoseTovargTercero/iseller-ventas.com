<?php
// Política de privacidad pública de la app iSeller Store.
// Página autocontenida: no usa base de datos ni sesión.
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de privacidad · iSeller Store</title>
    <style>
        :root { --tinta: #111827; --gris: #6b7280; --linea: #e5e7eb; --verde: #1a7f4b; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f8fafc; color: var(--tinta);
               font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; line-height: 1.6; }
        header { background: #fff; border-bottom: 1px solid var(--linea); padding: 16px 20px; }
        header a { color: var(--verde); font-weight: 700; text-decoration: none; font-size: 18px; }
        main { max-width: 760px; margin: 0 auto; padding: 28px 20px 60px; }
        h1 { font-size: 26px; margin: 0 0 6px; }
        h2 { font-size: 18px; margin: 28px 0 8px; }
        p, li { font-size: 15px; color: #1f2937; }
        .fecha { color: var(--gris); font-size: 13px; margin-bottom: 20px; }
        .caja { background: #fff; border: 1px solid var(--linea); border-radius: 12px; padding: 16px 18px; }
        a { color: var(--verde); }
        footer { max-width: 760px; margin: 0 auto; padding: 0 20px 40px; color: var(--gris); font-size: 13px; }
    </style>
</head>
<body>
<header><a href="https://iseller-tiendas.com/">iSeller Store</a></header>
<main>
    <h1>Política de privacidad de iSeller Store</h1>
    <p class="fecha">Última actualización: 17 de noviembre de 2026</p>

    <div class="caja">
        <p>
            Esta política explica qué datos trata la aplicación móvil <strong>iSeller Store</strong>
            (paquete <code>com.iseller.clientes</code>) y el sitio
            <a href="https://iseller-tiendas.com/">iseller-tiendas.com</a>, operados por iSeller.
            Al usar la app aceptas las prácticas descritas aquí.
        </p>
    </div>

    <h2>1. Datos que recopilamos</h2>
    <ul>
        <li><strong>Datos de la cuenta:</strong> nombre, correo electrónico, teléfono, estado y municipio, y contraseña almacenada de forma cifrada.</li>
        <li><strong>Datos de entrega:</strong> dirección, referencia y coordenadas de ubicación que tú eliges al crear una dirección de envío. La app solo accede a tu ubicación cuando lo pides para fijar la dirección.</li>
        <li><strong>Datos de pedidos:</strong> productos, cantidades, montos y referencias de pago que registras al comprar.</li>
        <li><strong>Datos técnicos:</strong> identificador de dispositivo y token de notificaciones, para mantener la sesión y enviarte avisos de tus pedidos.</li>
    </ul>

    <h2>2. Para qué los usamos</h2>
    <ul>
        <li>Crear y proteger tu cuenta e iniciar sesión.</li>
        <li>Procesar pedidos, calcular envíos y notificar el estado de la compra.</li>
        <li>Comunicarte con la tienda donde compras y atender soporte.</li>
        <li>Prevenir fraudes y cumplir obligaciones legales.</li>
    </ul>

    <h2>3. Con quién se comparten</h2>
    <p>
        Los datos necesarios para preparar y entregar tu pedido (nombre, teléfono, dirección y detalle de la compra)
        se comparten con la tienda que elegiste. No vendemos datos personales ni los usamos para publicidad de terceros.
        Podemos usar proveedores tecnológicos (alojamiento y notificaciones) que tratan los datos solo por cuenta de iSeller.
    </p>

    <h2>4. Conservación</h2>
    <p>
        Conservamos los datos mientras tu cuenta esté activa y el tiempo necesario para obligaciones contables o legales.
        Puedes solicitar la eliminación de tu cuenta y de sus datos asociados desde la app
        (<em>Perfil → Eliminar mi cuenta</em>) o en
        <a href="eliminar_cuenta.php">iseller-tiendas.com/iseller_store/eliminar_cuenta.php</a>.
    </p>

    <h2>5. Seguridad</h2>
    <p>
        Las comunicaciones con la app usan HTTPS. Las contraseñas se almacenan con hash y el acceso a la sesión
        se protege con un token que puedes revocar al cerrar sesión.
    </p>

    <h2>6. Tus derechos</h2>
    <p>
        Puedes acceder, corregir o eliminar tus datos, oponerte a su tratamiento y solicitar una copia escribiendo a
        <a href="mailto:soporte@iseller-tiendas.com">soporte@iseller-tiendas.com</a>. Responderemos en un plazo razonable.
    </p>

    <h2>7. Menores de edad</h2>
    <p>La app no está dirigida a menores de 16 años y no recopilamos datos de forma consciente de ellos.</p>

    <h2>8. Cambios</h2>
    <p>Si modificamos esta política publicaremos la nueva versión en esta misma página con su fecha de actualización.</p>

    <h2>9. Contacto</h2>
    <p>
        Responsable: iSeller · Venezuela<br>
        Correo: <a href="mailto:soporte@iseller-tiendas.com">soporte@iseller-tiendas.com</a>
    </p>
</main>
<footer>© 2026 iSeller Store</footer>
</body>
</html>
