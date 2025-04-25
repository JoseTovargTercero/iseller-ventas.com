
            <?php

            function obtenerTiposDeCambio()
            {
                $url = "https://magicloops.dev/api/loop/4b921d65-98a4-4a6e-827a-76552b0c53af/run";

                $data = json_encode([
                    "consulta" => "Obtener tipos de cambio actuales"
                ]);

                $ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                $response = curl_exec($ch);

                if (curl_errno($ch)) {
                    echo 'Error en la solicitud: ' . curl_error($ch);
                } else {
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($httpCode !== 200) {
                        echo "Error en la respuesta: $httpCode";
                    } else {
                        $resultado = json_decode($response, true);
                        echo "Respuesta del servidor:" . PHP_EOL;
                        print_r($resultado);
                    }
                }

                curl_close($ch);
            }

            obtenerTiposDeCambio()

            ?>