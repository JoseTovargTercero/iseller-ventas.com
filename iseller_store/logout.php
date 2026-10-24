<?php
/**
 * Logout - Cerrar Sesión
 */

require_once('core/session.php');

// Cerrar sesión
logoutUser();

// Redirigir al inicio de la tienda
redirect('../');
