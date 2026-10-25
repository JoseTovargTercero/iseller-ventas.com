<?php
/** GET /iseller_store/api/v1/geo.php — estados y municipios de Venezuela. */
declare(strict_types=1);
require_once __DIR__ . '/../../core/api_v1.php';
require_once __DIR__ . '/../../core/venezuela_geo.php';

api_method('GET');
api_rate_limit('geo_ip', 30, 60);
api_ok(['states' => venezuela_estados_municipios()]);
