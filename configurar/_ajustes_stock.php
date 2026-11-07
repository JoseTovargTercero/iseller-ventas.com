<?php

/**
 * Catálogos y utilidades compartidas por la página y la API de ajustes de stock.
 * Mantener aquí los motivos evita que la lista del formulario y la validación
 * del servidor se desincronicen.
 */

function ajustes_motivos_merma(): array
{
    return [
        'VENCIMIENTO'      => 'Vencimiento',
        'DETERIORO'        => 'Deterioro',
        'ROTURA'           => 'Rotura',
        'ROBO_HURTO'       => 'Robo o hurto',
        'CONTAMINACION'    => 'Contaminación',
        'CONSUMO_INTERNO'  => 'Consumo interno',
        'ERROR_INVENTARIO' => 'Error de inventario',
        'OTRO'             => 'Otro',
    ];
}

function ajustes_motivos_ajuste(): array
{
    return [
        'CONTEO_FISICO' => 'Conteo físico',
        'CORRECCION'    => 'Corrección',
        'OTRO'          => 'Otro',
    ];
}

function ajustes_numero($valor, int $decimales = 4): string
{
    $texto = number_format((float) $valor, $decimales, '.', '');
    if (strpos($texto, '.') !== false) {
        $texto = rtrim(rtrim($texto, '0'), '.');
    }
    return $texto === '' || $texto === '-' ? '0' : $texto;
}
