<?php
/**
 * Configuración privada del backend de Despostes.
 * Este archivo NO debe quedar dentro de public/.
 */
return [
    // Cambia esta clave antes de publicar.
    'admin_password' => 'CAMBIA-ESTA-CLAVE',

    'timezone' => 'America/Caracas',
    'data_dir' => __DIR__ . '/../data',

    // Duración de la membresía desde que se activa el código.
    'membership_days' => 365,
    'code_prefix' => 'DESP',

    // Texto que se muestra en la página de solicitud.
    'price_label' => 'Membresía anual',
    'payment_url' => '',   // Ej. https://tu-tienda.com/producto/membresia
    'whatsapp' => '',      // Ej. 584141234567 (sin + ni espacios)
    'support_email' => '', // Ej. soporte@tudominio.com
];
