<?php
declare(strict_types=1);

/**
 * Análisis de resultados de un cargamento con Gemini.
 * Mantiene el payload compacto, cachea por hash y prueba modelos de respaldo.
 */
final class GeminiCargamento
{
    private const PROMPT_VERSION = 4;
    private const CACHE_TTL = 2592000; // 30 días
    private const MODELS = [
        'gemini-3.5-flash-lite',
        'gemini-3.1-flash-lite',
        'gemini-3.8-flash',
    ];

    private string $apiKey;
    private string $cacheDir;

    public function __construct()
    {
        $config = function_exists('ds_config') ? ds_config() : [];
        $key = (string) ($config['gemini_api_key'] ?? '');
        if ($key === '') {
            $key = (string) (getenv('GEMINI_API_KEY') ?: '');
        }

        $local = __DIR__ . '/gemini.local.php';
        if ($key === '' && is_file($local)) {
            $localConfig = require $local;
            if (is_array($localConfig)) {
                $key = (string) ($localConfig['api_key'] ?? '');
            }
        }

        $this->apiKey = $key;
        $baseDir = (string) ($config['data_dir'] ?? __DIR__ . '/../data');
        $this->cacheDir = rtrim($baseDir, '/\\') . '/analisis';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public function analizar(array $cargamento, bool $forzar = false): array
    {
        if ($this->apiKey === '') {
            return ['error' => 'El servicio de análisis no está configurado.'];
        }

        $payload = $this->normalizar($cargamento);
        $hash = md5(self::PROMPT_VERSION . '|' . json_encode($payload, JSON_UNESCAPED_UNICODE));
        $cacheFile = $this->cacheDir . '/analisis_' . $hash . '.json';

        if (!$forzar && is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL) {
            $cached = json_decode((string) file_get_contents($cacheFile), true);
            if (is_array($cached)) {
                $cached['_from_cache'] = true;
                return $cached;
            }
        }

        $raw = $this->llamarGemini($payload);
        if (isset($raw['error'])) {
            return $raw;
        }

        $parsed = $this->parsear($raw['text']);
        $parsed['_from_cache'] = false;
        $parsed['_generated_at'] = gmdate('c');
        $parsed['_model'] = $raw['model_used'] ?? '';

        @file_put_contents(
            $cacheFile,
            json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );

        return $parsed;
    }

    private function normalizar(array $cargamento): array
    {
        $resumen = is_array($cargamento['resumen'] ?? null) ? $cargamento['resumen'] : [];

        $salida = [
            'tipo' => substr((string) ($cargamento['tipo'] ?? ''), 0, 40),
            'fecha' => substr((string) ($cargamento['fecha'] ?? ''), 0, 20),
            'resumen' => [
                'total_reses' => (int) ($resumen['reses'] ?? 0),
                'despostadas' => (int) ($resumen['despostadas'] ?? 0),
                'peso_canal' => round((float) ($resumen['peso_canal'] ?? 0), 2),
                'peso_cortes' => round((float) ($resumen['peso_cortes'] ?? 0), 2),
                'rendimiento' => round((float) ($resumen['rendimiento'] ?? 0), 2),
                'merma' => round((float) ($resumen['merma'] ?? 0), 2),
                'merma_pct' => round((float) ($resumen['merma_pct'] ?? 0), 2),
                'costo' => round((float) ($resumen['costo'] ?? 0), 2),
                'valor_cortes' => round((float) ($resumen['valor_cortes'] ?? 0), 2),
                'ganancia_neta' => round((float) ($resumen['ganancia_neta'] ?? 0), 2),
                'ganancias' => round((float) ($resumen['ganancias'] ?? 0), 2),
                'perdidas' => round((float) ($resumen['perdidas'] ?? 0), 2),
                'reses_con_perdida' => (int) ($resumen['reses_con_perdida'] ?? 0),
            ],
            'cortes' => [],
            'reses' => [],
        ];

        $cortes = is_array($cargamento['cortes'] ?? null) ? $cargamento['cortes'] : [];
        foreach (array_slice($cortes, 0, 50) as $corte) {
            if (!is_array($corte)) {
                continue;
            }
            $salida['cortes'][] = [
                'nombre' => substr((string) ($corte['nombre'] ?? ''), 0, 60),
                'peso' => round((float) ($corte['peso'] ?? 0), 2),
                'valor' => round((float) ($corte['valor'] ?? 0), 2),
                'esperado' => round((float) ($corte['esperado'] ?? 0), 2),
                'obtenido' => round((float) ($corte['obtenido'] ?? 0), 2),
                'diferencia' => round((float) ($corte['diferencia'] ?? 0), 2),
            ];
        }

        $reses = is_array($cargamento['reses'] ?? null) ? $cargamento['reses'] : [];
        foreach (array_slice($reses, 0, 200) as $res) {
            if (!is_array($res)) {
                continue;
            }
            $salida['reses'][] = [
                'identificador' => substr((string) ($res['identificador'] ?? ''), 0, 60),
                'sexo' => substr((string) ($res['sexo'] ?? ''), 0, 20),
                'peso_canal' => round((float) ($res['peso_canal'] ?? 0), 2),
                'rendimiento' => round((float) ($res['rendimiento'] ?? 0), 2),
                'merma' => round((float) ($res['merma'] ?? 0), 2),
                'ganancia' => round((float) ($res['ganancia'] ?? 0), 2),
                'despostada' => (bool) ($res['despostada'] ?? false),
            ];
        }

        return $salida;
    }

    private function llamarGemini(array $payload): array
    {
        $body = [
            'system_instruction' => [
                'parts' => [['text' => $this->promptSistema()]],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [[
                        'text' => "Analiza estos resultados de desposte:\n\n```json\n"
                            . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                            . "\n```",
                    ]],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 900,
                'responseMimeType' => 'application/json',
            ],
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        if ($jsonBody === false) {
            return ['error' => 'No se pudo preparar la solicitud de análisis.'];
        }

        foreach (self::MODELS as $model) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
                . rawurlencode($model) . ':generateContent';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $jsonBody,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-goog-api-key: ' . $this->apiKey,
                ],
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
            ]);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($response === false) {
                continue;
            }

            $data = json_decode((string) $response, true);

            // Modelo sobrecargado o retirado: probar el siguiente.
            if (in_array($httpCode, [404, 503], true)) {
                continue;
            }

            if ($httpCode !== 200) {
                $mensaje = is_array($data) ? ($data['error']['message'] ?? 'Error de Gemini') : 'Error de Gemini';
                return ['error' => "Gemini respondió $httpCode: $mensaje"];
            }

            $texto = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!is_string($texto) || trim($texto) === '') {
                continue;
            }

            return ['text' => $texto, 'model_used' => $model];
        }

        return ['error' => 'El servicio de análisis está ocupado. Intenta de nuevo en unos minutos.'];
    }

    private function parsear(string $texto): array
    {
        $texto = trim($texto);
        $texto = preg_replace('/^```json\s*/i', '', $texto) ?? $texto;
        $texto = preg_replace('/\s*```$/', '', $texto) ?? $texto;

        $parsed = json_decode($texto, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            return [
                'raw_text' => $texto,
                'parse_error' => json_last_error_msg(),
            ];
        }

        return $parsed;
    }

    private function promptSistema(): string
    {
        return <<<PROMPT
Eres un analista de producción y rentabilidad de desposte de carne. Recibirás un JSON con los resultados agregados de un cargamento: resumen de pesos, rendimiento, costos, ganancias, pérdidas, mermas, cortes y reses.

Analiza únicamente los datos del JSON. No inventes cifras, no supongas precios ni causas que no estén en los datos.

Devuelve EXCLUSIVAMENTE un objeto JSON válido, breve y fácil de leer:
{
  "resumen": "Máximo 2 frases y 40 palabras con el resultado global.",
  "hallazgos": ["Hallazgo 1", "Hallazgo 2", "Hallazgo 3"],
  "recomendaciones": ["Acción 1", "Acción 2", "Acción 3"],
  "alertas": ["Riesgo o limitación relevante"]
}

Reglas:
- Cada hallazgo, recomendación y alerta debe ocupar una sola línea de máximo 18 palabras.
- Devuelve exactamente 3 hallazgos, 3 recomendaciones y entre 0 y 2 alertas.
- No repitas el mismo dato en varias secciones; prioriza lo más importante y accionable.
- No uses párrafos largos ni introducciones.
- Todos los montos están en dólares: usa $ y dos decimales. Pesos en kg y porcentajes con %.
- Incluye el rendimiento total, la ganancia neta, las pérdidas y la merma en el resumen.
- Señala solo el corte con mayor desviación positiva y el de mayor desviación negativa.
- En los cortes, el campo "diferencia" está en puntos porcentuales, no en kg.
- Menciona una res con pérdida solo si aporta información útil.
- Las recomendaciones deben ser concretas: precio, merma, porcionado, clasificación o control de pesos.
- Usa lenguaje objetivo y probabilístico. No afirmes causalidad sin evidencia.
- Escribe en español, sin emojis, sin markdown y sin texto fuera del JSON.
- Si los datos son insuficientes, indícalo en una alerta y no inventes valores.
PROMPT;
    }
}
