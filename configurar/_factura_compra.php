<?php

// Total de una línea: el precio se guarda por bulto y la cantidad en unidades.
function fc_linea_total($precioBulto, $unidadesPorBulto, $unidades)
{
    $precioBulto = (float) $precioBulto;
    $unidadesPorBulto = (float) $unidadesPorBulto;
    $unidades = (float) $unidades;

    if ($precioBulto <= 0 || $unidadesPorBulto <= 0 || $unidades <= 0) {
        return 0.0;
    }

    return round(($precioBulto / $unidadesPorBulto) * $unidades, 2);
}

function fc_estado($total, $abonado)
{
    return ((float) $abonado + 0.005 >= (float) $total) ? 'pagada' : 'pendiente';
}

// Autochequeo: php configurar/_factura_compra.php
if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $fallos = 0;
    $casos = [
        [10, 10, 3, 3.00],
        [10, 4, 2, 5.00],
        [0, 10, 3, 0.00],
        [10, 0, 3, 0.00],
    ];

    foreach ($casos as $caso) {
        if (abs(fc_linea_total($caso[0], $caso[1], $caso[2]) - $caso[3]) > 0.001) {
            $fallos++;
            fwrite(STDERR, "Fallo línea: " . json_encode($caso) . PHP_EOL);
        }
    }

    if (fc_estado(10, 10) !== 'pagada') $fallos++;
    if (fc_estado(10, 9.99) !== 'pendiente') $fallos++;
    if (fc_estado(0, 0) !== 'pagada') $fallos++;

    echo $fallos === 0 ? "OK\n" : "FALLOS: $fallos\n";
    exit($fallos === 0 ? 0 : 1);
}
