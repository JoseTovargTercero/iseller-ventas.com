<?php
require_once __DIR__ . '/../core/api_v1.php';
echo api_jwt_issue(6, 'test-tracking');
