<?php
/**
 * Reglas para sugerir una categoría de gasto a partir del concepto.
 * Devuelve el nombre canónico de la categoría (se resuelve contra
 * gastos_categorias del negocio); null si no hay coincidencia.
 *
 * El orden importa: gana la primera regla que coincida.
 */
function gasto_normalizar(string $texto): string
{
    $texto = strtoupper($texto);
    $texto = strtr($texto, [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U', 'Â' => 'A', 'Ê' => 'E',
        'Ô' => 'O', 'Ã' => 'A', 'Õ' => 'O', 'Ç' => 'C',
    ]);
    $texto = preg_replace('/[^A-Z0-9]+/', ' ', $texto);
    return trim(preg_replace('/\s+/', ' ', $texto));
}

/**
 * Devuelve el id de la categoría del negocio que corresponde al nombre
 * sugerido, comparando nombres normalizados (tolera variantes como
 * "Servicios" vs "Servicios públicos").
 */
function gasto_resolver_categoria(mysqli $conexion, int $bss_id, string $sugerida): ?array
{
    $buscada = gasto_normalizar($sugerida);
    $stmt = $conexion->prepare("SELECT id, nombre FROM gastos_categorias WHERE bss_id = ? AND activo = 1");
    $stmt->bind_param('i', $bss_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $candidata = null;
    while ($row = $res->fetch_assoc()) {
        $actual = gasto_normalizar($row['nombre']);
        if ($actual === $buscada) {
            return ['id' => (int) $row['id'], 'nombre' => $row['nombre']];
        }
        if ($candidata === null && (str_contains($buscada, $actual) || str_contains($actual, $buscada))) {
            $candidata = ['id' => (int) $row['id'], 'nombre' => $row['nombre']];
        }
    }
    return $candidata;
}

function gasto_sugerir(string $concepto): ?string
{
    $texto = ' ' . gasto_normalizar($concepto) . ' ';
    foreach (GASTO_REGLAS as [$categoria, $patron]) {
        if (preg_match('/\b(?:' . $patron . ')\b/', $texto)) {
            return $categoria;
        }
    }
    return null;
}

const GASTO_REGLAS = [
    // Servicios públicos antes que Tecnología ("internet", "teléfono")
    ['Servicios públicos', 'LUZ|ELECTRICIDAD|AGUA|INTERNET|TELEFONOS?|ASEO|ASEADO|GAS|CANTV|CORPOELEC|HIDROLOGICA|TELECOMUNICACIONES|CABLE|SATELITAL'],
    ['Alquiler', 'ALQUILER|ARRENDAMIENTO|RENTA|CANON'],
    ['Personal', 'SUELDOS?|SALARIOS?|NOMINA|PRESTACIONES|AGUINALDOS?|BONOS?|VACACIONES|EMPLEADOS?|PERSONAL|SEGURO SOCIAL|IVSS|CESTATICKET'],
    ['Impuestos', 'IMPUESTOS?|IVA|ISLR|SENIAT|MUNICIPAL|PATENTE|CONTRIBUCION'],
    ['Comisiones', 'COMISION(?:ES)?|BANCARI[OA]S?|BANCO|TARJETA|PUNTO DE VENTA|TRANSACCION'],
    ['Transporte', 'TRANSPORTE|FLETES?|GASOLINA|COMBUSTIBLE|DIESEL|DELIVERY|ENVIOS?|PEAJE|ESTACIONAMIENTO|CAMION'],
    ['Publicidad', 'PUBLICIDAD|ANUNCIOS?|MARKETING|FACEBOOK|INSTAGRAM|REDES SOCIALES|PROMOCION|VOLANTES'],
    ['Limpieza', 'LIMPIEZA|DETERGENTES?|DESINFECTANTE|CLORO|ESCOBAS?|BASURA|PAPEL HIGIENICO|SERVILLETAS?'],
    ['Papelería', 'PAPELERIA|FOTOCOPIAS?|IMPRESION|TINTA|TONER|CUADERNOS?|BOLIGRAFOS?|PAPEL BOND|PAPEL CARTA|RESMA'],
    ['Tecnología', 'TECNOLOGIA|SOFTWARE|SISTEMAS?|COMPUTADORAS?|COMPUTADORES?|LAPTOP|CELULAR|HOSTING|DOMINIO|SOPORTE TECNICO'],
    ['Reparaciones', 'REPARACION(?:ES)?|COMPOSTURA|TALLER'],
    ['Mantenimiento', 'MANTENIMIENTO|ARREGLO|PINTURA|PLOMERIA'],
    ['Gastos administrativos', 'ADMINISTRATIVOS?|CONTADOR|HONORARIOS|NOTARIA|LEGAL|ABOGADO|REGISTRO|TRAMITES?'],
];
