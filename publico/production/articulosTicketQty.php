<?php
require_once("../../configurar/configuracion.php");
require_once('../../configurar/session.php');

$id = $_POST['id'];
echo contar("SELECT COUNT(*) FROM orden_articulos WHERE order_id='$id'");
