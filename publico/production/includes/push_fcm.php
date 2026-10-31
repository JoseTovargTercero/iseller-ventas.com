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

    /**
     * Asegura la tabla de tokens y que un mismo teléfono pueda estar registrado
     * a la vez como panel y como transportista.
     *
     * El índice viejo `uq_push_token` era único solo por token: al iniciar sesión
     * como transportista se actualizaba la fila del panel y se perdía su push (y
     * viceversa). El índice compuesto conserva una fila por tipo/usuario.
     */
    function push_asegurar_tabla(mysqli $conexion): void
    {
        static $hecho = false;
        if ($hecho) return;
        $hecho = true;

        $conexion->query(
            "CREATE TABLE IF NOT EXISTS push_dispositivos (
                id int(11) NOT NULL AUTO_INCREMENT,
                tipo varchar(10) NOT NULL,
                usuario_id int(11) NOT NULL,
                bss_id int(11) DEFAULT NULL,
                token varchar(255) NOT NULL,
                plataforma varchar(20) NOT NULL DEFAULT 'android',
                creado_en datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                actualizado_en datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_push_token_tipo_usuario (token(191), tipo, usuario_id),
                KEY idx_push_usuario (tipo, usuario_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );

        $idx = $conexion->query("SHOW INDEX FROM push_dispositivos");
        $porIndice = [];
        if ($idx) {
            while ($fila = $idx->fetch_assoc()) {
                if ((int)$fila['Non_unique'] !== 0) continue;
                $porIndice[$fila['Key_name']][(int)$fila['Seq_in_index']] = $fila['Column_name'];
            }
        }
        foreach ($porIndice as $nombreIndice => $columnas) {
            ksort($columnas);
            if ($nombreIndice !== 'PRIMARY' && array_values($columnas) === ['token']) {
                $nombreSeguro = str_replace('`', '``', $nombreIndice);
                $conexion->query("ALTER TABLE push_dispositivos DROP INDEX `$nombreSeguro`");
            }
        }

        $idx = $conexion->query("SHOW INDEX FROM push_dispositivos WHERE Key_name = 'uq_push_token_tipo_usuario'");
        if ($idx && $idx->num_rows === 0) {
            $conexion->query(
                "DELETE p1 FROM push_dispositivos p1
                   INNER JOIN push_dispositivos p2
                      ON p1.token = p2.token AND p1.tipo = p2.tipo AND p1.usuario_id = p2.usuario_id
                    WHERE p1.id > p2.id"
            );
            $conexion->query("ALTER TABLE push_dispositivos ADD UNIQUE KEY uq_push_token_tipo_usuario (token(191), tipo, usuario_id)");
        }
    }

    /** Un token no debe seguir recibiendo avisos de una cuenta anterior del mismo rol. */
    function push_limpiar_token_ajeno(mysqli $conexion, string $tipo, int $usuarioId, string $token): void
    {
        $stmt = $conexion->prepare("DELETE FROM push_dispositivos WHERE token = ? AND tipo = ? AND usuario_id <> ?");
        if (!$stmt) return;
        $stmt->bind_param('ssi', $token, $tipo, $usuarioId);
        $stmt->execute();
        $stmt->close();
    }

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
                    $pista = $r['status'] === 403
                        ? ' (revisa que FCM_SERVICE_ACCOUNT sea del MISMO proyecto Firebase que google-services.json y que FCM_PROJECT_ID no apunte a otro proyecto)'
                        : '';
                    error_log('FCM: error ' . $r['status'] . $pista . ' ' . substr($cuerpoError, 0, 300));
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
     *
     * Devuelve el resultado para que el panel pueda avisar por qué no se envió
     * (push deshabilitado, sin token de la app, error de Firebase...).
     */
    function enviar_push_transportista(mysqli $conexion, int $transportistaId, string $titulo, string $cuerpo, array $data = []): array
    {
        if (!push_habilitado()) {
            return ['ok' => false, 'sent' => 0, 'msg' => 'Las notificaciones push no están habilitadas en el servidor.'];
        }
        if ($transportistaId <= 0) {
            return ['ok' => false, 'sent' => 0, 'msg' => 'Transportista inválido.'];
        }

        try {
            $project = push_project_id();
            if (!$project) {
                error_log('FCM transportista: falta FCM_SERVICE_ACCOUNT/FCM_PROJECT_ID');
                return ['ok' => false, 'sent' => 0, 'msg' => 'Firebase no está configurado en el servidor.'];
            }

            $access = push_access_token();
            if (!$access) {
                return ['ok' => false, 'sent' => 0, 'msg' => 'No se pudo autenticar con Firebase.'];
            }

            $stmt = $conexion->prepare("SELECT id, token FROM push_dispositivos WHERE tipo = 'transp' AND usuario_id = ?");
            if (!$stmt) {
                error_log('FCM transportista: ' . $conexion->error);
                return ['ok' => false, 'sent' => 0, 'msg' => 'No se pudieron leer los dispositivos del transportista.'];
            }
            $stmt->bind_param('i', $transportistaId);
            $stmt->execute();
            $dispositivos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            if (!$dispositivos) {
                error_log('FCM transportista: sin token para transportista ' . $transportistaId);
                return ['ok' => false, 'sent' => 0, 'msg' => 'El transportista no tiene la app con notificaciones registradas.'];
            }

            $data['titulo'] = $titulo;
            $data['mensaje'] = $cuerpo;

            $url = "https://fcm.googleapis.com/v1/projects/$project/messages:send";
            $headers = [
                'Authorization: Bearer ' . $access,
                'Content-Type: application/json',
            ];

            $enviados = 0;
            $ultimoError = '';
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
                    $ultimoError = 'La app del transportista tiene un token vencido; debe abrir la app para registrarse de nuevo.';
                } elseif ($r['status'] < 200 || $r['status'] >= 300) {
                    error_log('FCM transportista: error ' . $r['status'] . ' ' . substr($cuerpoError, 0, 300));
                    $ultimoError = 'Firebase rechazó la notificación (' . $r['status'] . ').';
                } else {
                    $enviados++;
                }
            }

            if ($enviados > 0) {
                return ['ok' => true, 'sent' => $enviados, 'msg' => ''];
            }
            return ['ok' => false, 'sent' => 0, 'msg' => $ultimoError ?: 'Firebase rechazó la notificación.'];
        } catch (Throwable $e) {
            error_log('FCM transportista: ' . $e->getMessage());
            return ['ok' => false, 'sent' => 0, 'msg' => 'Error interno al enviar la notificación.'];
        }
    }
}
