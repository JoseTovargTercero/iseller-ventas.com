<?php

// Inicializar cURL
$ch = curl_init("https://ve.dolarapi.com/v1/dolares/oficial");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Importante para obtener la respuesta

// Ejecutar la solicitud y obtener la respuesta como string
$response = curl_exec($ch);

// Cerrar cURL
curl_close($ch);

// Verificar si hubo error
if ($response === false) {
    die("Error al obtener datos");
}



// Convertir el JSON a array asociativo
$data = json_decode($response, true);

// Mostrar el array (para depuración)
print_r($data);

// Acceder a datos específicos
if (isset($data['promedio'])) {
    echo "El precio del dólar oficial es: " . $data['promedio'];
} else {
    echo "No se pudo obtener el precio.";
}

exit;


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
print_r($response);

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
