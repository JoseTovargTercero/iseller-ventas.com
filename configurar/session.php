<?php
require_once 'configuracion.php';

if (!$_SESSION["bss_id"]) {
    define('PAGINA_INICIO', '../index.php');
    header('Location: ' . PAGINA_INICIO);
}
