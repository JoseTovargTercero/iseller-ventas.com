<?php
session_start();

unset($_SESSION);

session_destroy();

define('PAGINA_INICIO', '../inventario');
header('Location: ' . PAGINA_INICIO);
