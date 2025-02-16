<?php

// URL del sitio web
$url = "https://www.bcv.org.ve/";

// Inicializar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Desactivar verificación SSL (si es necesario)

// Ejecutar cURL
$response = curl_exec($ch);

// Verificar si hubo errores
if (curl_errno($ch)) {
    die("Error al conectar a la URL: " . curl_error($ch));
}

// Cerrar cURL
curl_close($ch);

// Guardar el contenido HTML en un archivo para inspección
file_put_contents("response.html", $response);

// Verificar si se obtuvo respuesta
if ($response === false) {
    die("No se pudo obtener contenido de la URL.");
}

// Usar DOMDocument para analizar el contenido HTML
$dom = new DOMDocument();
libxml_use_internal_errors(true); // Evitar errores por HTML mal formado
$dom->loadHTML($response);
libxml_clear_errors();

// Buscar el elemento que contiene el valor del USD
$xpath = new DOMXPath($dom);

// Ajustar el XPath según el contenido del HTML real
$nodes = $xpath->query("//div[contains(@class, 'field-content')]/text()[contains(., 'USD')]");

// Extraer el valor del USD
if ($nodes->length > 0) {
    $usdValue = $nodes[0]->nodeValue;
    echo "El valor del USD es: $usdValue";
} else {
    echo "No se encontró el valor del USD.";
}
