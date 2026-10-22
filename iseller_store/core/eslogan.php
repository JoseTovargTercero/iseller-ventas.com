<?php
/**
 * Eslogan que aparece bajo el nombre de la tienda en su perfil público.
 *
 * Lo escribe cada tienda en publico/production/tienda_configuracion.php
 * (negocio.eslogan). Si está vacío se usa el texto por defecto de su categoría
 * (negocio.categoria), para que ninguna tienda quede sin presentación.
 */

// Texto por defecto por categoría. Las categorías son las de $categorias_list
// en publico/production/tienda_configuracion.php.
function eslogan_defaults(): array
{
    return [
        'Restaurantes y Comida Rápida' => "Comida fresca y sabrosa, lista para disfrutar.\nPide fácil y recibe en casa.",
        'Ropa y Accesorios'            => "Moda y estilo para tu día a día.\nEncuentra tu look con nosotros.",
        'Supermercado y Víveres'       => "Tu mercado completo, cerca de ti.\nTodo lo que necesitas al mejor precio.",
        'Salud y Belleza'              => "Cuida tu salud y tu belleza.\nProductos de confianza para sentirte bien.",
        'Tecnología y Electrónica'     => "Tecnología y novedades a tu alcance.\nEquípate con lo último.",
        'Hogar y Decoración'           => "Todo para tu hogar, a un clic.\nDale vida a tus espacios.",
        'Juguetes y Niños'             => "Diversión y alegría para los más pequeños.\nJuguetes y regalos con amor.",
        'Mascotas'                     => "Todo para consentir a tu mascota.\nComida, juguetes y mucho cariño.",
        'Servicios'                    => "Servicios de confianza, cerca de ti.\nContrata fácil y sin complicaciones.",
    ];
}

// Eslogan por defecto de una categoría; si no la conocemos (o es "Otro"), el genérico.
function eslogan_categoria_default(string $categoria): string
{
    $defaults = eslogan_defaults();

    return $defaults[trim($categoria)]
        ?? "Productos de calidad para tu día a día.\nTu tienda de confianza, siempre cerca de ti.";
}

// Eslogan de la tienda, o el de su categoría si no escribió ninguno.
function eslogan_mostrar(?string $eslogan, ?string $categoria): string
{
    $eslogan = trim((string)$eslogan);

    return $eslogan !== '' ? $eslogan : eslogan_categoria_default((string)$categoria);
}
