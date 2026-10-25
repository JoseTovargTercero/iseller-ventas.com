<?php
/** GET /iseller_store/api/v1/auth/me.php — perfil del cliente autenticado. */
declare(strict_types=1);
require_once __DIR__ . '/../../../core/api_v1.php';

api_method('GET');
$user = api_auth();
api_ok(api_user_public($user));
