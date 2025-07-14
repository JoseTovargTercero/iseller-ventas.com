<?php

require_once '_calculadrora_precios.php'; // Asegúrate de que el nombre del archivo sea correcto

function testFormatPeso()
{
    $calculadora = new CalculadoraPrecios(
        4000,     // pesoDolar
        0.03,     // peso_bolivar
        115.4185,     // dolarBolivar
        0.03,     // bolivar_peso
        115.4185,     // bcv
        []     // data_monedas
    );

    $tests = [
        ['input' => 10,     'expected' => 100],
        ['input' => 25,     'expected' => 100],
        ['input' => 75,     'expected' => 100],
        ['input' => 99,     'expected' => 100],
        ['input' => 149,    'expected' => 100],
        ['input' => 150,    'expected' => 200],
        ['input' => 1620,   'expected' => 1600],
        ['input' => 1750,   'expected' => 1800],
        ['input' => 1801,   'expected' => 1800],
        ['input' => 1851,   'expected' => 1900],
    ];

    foreach ($tests as $test) {
        $producto = [
            'cantidad_unidades' => 1,
            'origen' => 'c',
            'precio_compra' => $test['input'],
            'porcentaje' => 0,
            'mayor' => 1
        ];

        $resultado = $calculadora->calcularPrecios($producto);
        $real = $resultado['precio_venta_peso'];

        $status = ($real === $test['expected']) ? '✅' : '❌';
        echo "$status Entrada: {$test['input']} → Esperado: {$test['expected']} | Obtenido: $real <br>";
    }
}

testFormatPeso();
