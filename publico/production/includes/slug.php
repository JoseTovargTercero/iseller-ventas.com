<?php
/**
 * Validación de slugs de tienda (URL pública). Compartido por los formularios de
 * tienda y por el endpoint api/tienda_slug_check.php.
 */

// Slugs que no pueden usarse como URL de tienda: el .htaccess y el propio index.php
// sirven el archivo/carpeta real antes de resolver el slug, asi que la tienda nunca se veria.
function slugs_reservados()
{
    $lista = [
        // carpetas reales en la raiz del sitio
        'admin', 'api', 'brain', 'configurar', 'login', 'logs', 'publico', 'temp', 'web',
        // archivos reales en la raiz
        'index', 'landing', 'registro',
        // redireccion fija del .htaccess
        'inventario',
    ];

    // Cualquier carpeta real de la raiz gana sobre el slug (el .htaccess sirve
    // el directorio antes de resolver la tienda). Se agregan automaticamente
    // para no depender de una lista que se quede vieja (apk, iseller_store, ...).
    $raiz = dirname(__DIR__, 3);
    if (is_dir($raiz)) {
        $entradas = @scandir($raiz);
        if (is_array($entradas)) {
            foreach ($entradas as $entrada) {
                if ($entrada === '.' || $entrada === '..') continue;
                if (!is_dir($raiz . DIRECTORY_SEPARATOR . $entrada)) continue;
                $slug = slug_normalizar($entrada);
                if ($slug !== '') $lista[] = $slug;
            }
        }
    }

    return array_values(array_unique($lista));
}

// Normaliza lo que escribe el usuario antes de guardar o comparar:
// "Login." / " login " -> "login" (sin guiones en los extremos).
function slug_normalizar($slug)
{
    $slug = strtolower(trim((string)$slug));
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    return trim($slug, '-');
}

function slug_reservado($slug)
{
    return in_array(strtolower(trim((string)$slug)), slugs_reservados(), true);
}

// true si otra tienda (id distinto de $bss_id) ya usa ese slug. negocio.slug tiene indice unico.
function slug_duplicado($conexion, $slug, $bss_id)
{
    $stmt = $conexion->prepare("SELECT id FROM negocio WHERE slug = ? AND id <> ? LIMIT 1");
    $stmt->bind_param("si", $slug, $bss_id);
    $stmt->execute();
    $duplicado = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $duplicado;
}
