<?php
require_once __DIR__ . '/security.php';
AdminSecurity::logout();
header('Location: index.php');
exit;
