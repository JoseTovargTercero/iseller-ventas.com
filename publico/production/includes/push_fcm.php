<?php
/**
 * Push a Android vía Google FCM (HTTP v1).
 *
 * Para habilitarlo:
 *   1. Crear la app Android en Firebase y descargar la service account
 *      (Configuración del proyecto → Cuentas de servicio → Generar nueva clave privada).
 *   2. Guardar ese JSON FUERA del webroot, p. ej. C:\xampp\fcm\iseller-fcm.json
 *   3. En el .env de la raíz (junto a DB_USER):
 *        PUSH_HABILITADO=1
 *        FCM_SERVICE_ACCOUNT=C:/xampp/fcm/iseller-fcm.json
 *        # FCM_PROJECT_ID es opcional: si falta se usa el project_id del JSON.
 *      El JSON debe ser del MISMO proyecto Firebase que la app (google_app_id 1:552887229814:android:...).
 *   4. Crear la tabla con iseller_store/sql/push_dispositivos.sql
 *   5. La app registra su token en iseller_store/api/push/registrar_token.php
 *
 * Sin PUSH_HABILITADO=1 todo es no-op: no rompe nada si todavía no está configurado.
 * La ruta relativa de FCM_SERVICE_ACCOUNT se resuelve desde la raíz de htdocs (donde vive el .env).
 */

if (!function_exists('push_habilitado')) {

    function push_habilitado(): bool
    {
        return !empty($_ENV['PUSH_HABILITADO']) && $_ENV['PUSH_HABILITADO'] !== '0';
    }

    /** Datos de la service account de Firebase, o null si falta/está mal configurada. */
    function push_service_account(): ?array
    {
        static $cache = null;
        static $leido = false;
        if ($leido) return $cache;
        $leido = true;

        $ruta = trim($_ENV['FCM_SERVICE_ACCOUNT'] ?? '');
        if ($ruta === '') return $cache;

        $absoluta = preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $ruta);
        if (!$absoluta) {
            $ruta = dirname(__DIR__, 4) . '/' . ltrim(str_replace('\\', '/', $ruta), '/');
        }
        if (!is_readable($ruta)) return $cache;

        $json = json_decode((string)@file_get_contents($ruta), true);
        if (!is_array($json) || empty($json['client_email']) || empty($json['private_key'])) return $cache;

        return $cache = $json;
    }

    /** ID del proyecto FCM: del .env o, si no está, del propio JSON de la service account. */
    function push_project_id(): ?string
    {
        $env = trim($_ENV['FCM_PROJECT_ID'] ?? '');
        if ($env !== '') return $env;

        $sa = push_service_account();
        return !empty($sa['project_id']) ? (string)$sa['project_id'] : null;
    }

    /** JWT firmado (RS256) para pedir el access token. Separado para poder verificarlo en pruebas. */
    function push_jwt_assertion(array $sa): ?string
    {
        $b64 = function ($v) {
            return rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
        };
        $tokenUri = $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token';
        $iat = time();

        $header = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims = $b64(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => $tokenUri,
            'iat'   => $iat,
            'exp'   => $iat + 3600,
        ]));

        $firma = '';
        if (!openssl_sign($header . '.' . $claims, $firma, $sa['private_key'], OPENSSL_ALGO_SHA256)) {
            return null;
        }
        return $header . '.' . $claims . '.' . $b64($firma);
    }

    /** POST HTTP aislado, para poder sustituirlo en las pruebas. */
    if (!function_exists('fcm_http_post')) {
        function fcm_http_post(string $url, array $headers, string $body): array
        {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_TIMEOUT        => 5,
            ]);
            $resp = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);

            return ['status' => $code, 'body' => $resp === false ? $err : $resp];
        }
    }

    /** Access token OAuth2, cacheado ~50 min en un archivo temporal. */
    function push_access_token(): ?string
    {
        if (!push_habilitado()) return null;
        $sa = push_service_account();
        if (!$sa) return null;

        $cache = sys_get_temp_dir() . '/iseller_fcm_token_' . md5($sa['client_email']) . '.json';
        if (is_readable($cache)) {
            $c = json_decode((string)@file_get_contents($cache), true);
            if (!empty($c['token']) && (int)($c['exp'] ?? 0) > time() + 60) {
                return $c['token'];
            }
        }

        $assertion = push_jwt_assertion($sa);
        if (!$assertion) return null;

        $res = fcm_http_post(
            $sa['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $assertion,
            ])
        );
        $data = json_decode((string)($res['body'] ?? ''), true);
        if (empty($data['access_token'])) {
            error_log('FCM: sin access_token (' . $res['status'] . ') ' . substr((string)$res['body'], 0, 300));
            return null;
        }

        @file_put_contents($cache, json_encode([
            'token' => $data['access_token'],
            'exp'   => time() + (int)($data['expires_in'] ?? 3600),
        ]));

        return $data['access_token'];
    }

    /** URL que abre la notificación al tocarla (web push). */
    function push_enlace(string $destino, array $data): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host === '') return null;

        $tipoNotif = $data['tipo'] ?? '';
        if ($destino === 'panel') {
            $ruta = $tipoNotif === 'chat' ? '/publico/production/tienda_chat.php'
                : ($tipoNotif === 'compra' ? '/publico/production/tienda_pedidos.php' : '/publico/production/');
        } else {
            $ruta = $tipoNotif === 'chat' ? '/iseller_store/chat.php'
                : ($tipoNotif === 'compra' ? '/iseller_store/perfil.php?tab=orders' : '/');
        }

        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return $esquema . '://' . $host . $ruta;
    }

    /**
     * Envía un push a todos los dispositivos de un usuario.
     *
     * @param string $tipo 'panel' (usuarios del panel) o 'cliente' (tienda_usuarios)
     * @param array  $data Datos extra para que la app decida a dónde ir (ej: ['tipo' => 'chat', 'id' => 5])
     *
     * Nunca lanza: un fallo de push no debe tumbar la venta ni el chat.
     * shortcut: envío en línea; si la latencia molesta, mover a una cola con cron.
     */
    function enviar_push_usuario(mysqli $conexion, string $tipo, int $usuario_id, string $titulo, string $cuerpo, array $data = []): void
    {
        if (!push_habilitado() || $usuario_id <= 0) return;

        try {
            $project = push_project_id();
            if (!$project) return;

            $access = push_access_token();
            if (!$access) return;

            $stmt = $conexion->prepare("SELECT id, token FROM push_dispositivos WHERE tipo = ? AND usuario_id = ?");
            $stmt->bind_param('si', $tipo, $usuario_id);
            $stmt->execute();
            $dispositivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if (!$dispositivos) return;

            $url = "https://fcm.googleapis.com/v1/projects/$project/messages:send";
            $headers = [
                'Authorization: Bearer ' . $access,
                'Content-Type: application/json',
            ];
            $enlace = push_enlace($tipo, $data);

            foreach ($dispositivos as $d) {
                $mensaje = [
                    'token'        => $d['token'],
                    'notification' => ['title' => $titulo, 'body' => $cuerpo],
                    'data'         => array_map('strval', $data),
                    'android'      => ['priority' => 'high'],
                ];
                if ($enlace) {
                    // Al tocar la notificación en el navegador se abre esta URL
                    $mensaje['webpush'] = ['fcm_options' => ['link' => $enlace]];
                }

                $r = fcm_http_post($url, $headers, json_encode(['message' => $mensaje]));

                // Token inservible (app desinstalada, permiso revocado, token viejo): se descarta.
                // Ojo: un 400 por payload (JSON inválido, data muy grande) NO debe borrar el token.
                $cuerpoError = (string)$r['body'];
                $tokenMuerto = $r['status'] === 404
                    || stripos($cuerpoError, 'UNREGISTERED') !== false
                    || stripos($cuerpoError, 'not a valid FCM registration token') !== false;

                if ($tokenMuerto) {
                    $del = $conexion->prepare("DELETE FROM push_dispositivos WHERE id = ?");
                    $del->bind_param('i', $d['id']);
                    $del->execute();
                    $del->close();
                } elseif ($r['status'] < 200 || $r['status'] >= 300) {
                    error_log('FCM: error ' . $r['status'] . ' ' . substr($cuerpoError, 0, 300));
                }
            }
        } catch (Throwable $e) {
            error_log('FCM: ' . $e->getMessage());
        }
    }

    /**
     * Push de datos para el transportista.
     * Sin bloque "notification": el mensaje pasa siempre por PosFcmService.onMessageReceived,
     * que lo pinta y abre el panel del transportista al tocarlo.
     */
    function enviar_push_transportista(mysqli $conexion, int $transportistaId, string $titulo, string $cuerpo, array $data = []): void
    {
        if (!push_habilitado() || $transportistaId <= 0) return;

        try {
            $project = push_project_id();
            if (!$project) return;

            $access = push_access_token();
            if (!$access) return;

            $stmt = $conexion->prepare("SELECT id, token FROM push_dispositivos WHERE tipo = 'transp' AND usuario_id = ?");
            $stmt->bind_param('i', $transportistaId);
            $stmt->execute();
            $dispositivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if (!$dispositivos) return;

            $data['titulo'] = $titulo;
            $data['mensaje'] = $cuerpo;

            $url = "https://fcm.googleapis.com/v1/projects/$project/messages:send";
            $headers = [
                'Authorization: Bearer ' . $access,
                'Content-Type: application/json',
            ];

            foreach ($dispositivos as $d) {
                $mensaje = [
                    'token'   => $d['token'],
                    'data'    => array_map('strval', $data),
                    'android' => ['priority' => 'high'],
                ];

                $r = fcm_http_post($url, $headers, json_encode(['message' => $mensaje]));

                $cuerpoError = (string)$r['body'];
                $tokenMuerto = $r['status'] === 404
                    || stripos($cuerpoError, 'UNREGISTERED') !== false
                    || stripos($cuerpoError, 'not a valid FCM registration token') !== false;

                if ($tokenMuerto) {
                    $del = $conexion->prepare("DELETE FROM push_dispositivos WHERE id = ?");
                    $del->bind_param('i', $d['id']);
                    $del->execute();
                    $del->close();
                } elseif ($r['status'] < 200 || $r['status'] >= 300) {
                    error_log('FCM transportista: error ' . $r['status'] . ' ' . substr($cuerpoError, 0, 300));
                }
            }
        } catch (Throwable $e) {
            error_log('FCM transportista: ' . $e->getMessage());
        }
    }
}
