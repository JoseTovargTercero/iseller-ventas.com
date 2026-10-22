<?php
require_once('../../configurar/configuracion.php');
require_once('../../configurar/session.php');
require_once('../../configurar/_tasas_cambio.php');
require_once('includes/header.php');
require_once('includes/menu.php');

// recupera el nombre de la pagina actual
$nombrePagina = basename($_SERVER['PHP_SELF']);

// Si no hay sucursal en sesión y el negocio solo tiene una sucursal, cargarla por defecto
if (empty($_SESSION['sucursal']) && !empty($_SESSION['bss_id']) && isset($conexion)) {
    $stmtSucUnica = $conexion->prepare("SELECT id FROM sucursales WHERE bss_id = ?");
    if ($stmtSucUnica) {
        $bssIdVal = (int)$_SESSION['bss_id'];
        $stmtSucUnica->bind_param("i", $bssIdVal);
        $stmtSucUnica->execute();
        $resSucUnica = $stmtSucUnica->get_result();
        if ($resSucUnica && $resSucUnica->num_rows === 1) {
            $rowSucUnica = $resSucUnica->fetch_assoc();
            $_SESSION['sucursal'] = (int)$rowSucUnica['id'];
        }
        $stmtSucUnica->close();
    }
}

// El interceptor de membresía vencida ahora vive en configurar/session.php,
// de modo que también cubre los endpoints AJAX que no incluyen este archivo.

if ($_SESSION["nivel"] == 2) {
    $archivoActual = basename($_SERVER['PHP_SELF']);
    $coincidencia = false;
    $archivo = false;
    foreach ($_SESSION["permisos"] as $key => $value) {
        $url_acceso = $value;
        $archivo = $value;

        if ($url_acceso == $archivoActual) {
            $coincidencia = true;
            $archivo = false;
        }
    }
    if ($archivoActual === 'tienda_planes_entrega.php' && in_array('tienda_pedidos.php', $_SESSION["permisos"] ?? [], true)) {
        $coincidencia = true;
    }
    if ($coincidencia == false && $nombrePagina != 'detallesVenta.php' ) {
        $primerPermiso = reset($_SESSION["permisos"]);
        if ($primerPermiso) {
            header("Location: " . $primerPermiso);
            exit;
        }
        header("Location: ../../login/salir.php");
        exit;
    }
}

// Slugs reservados / duplicados: validacion compartida con api/tienda_slug_check.php
require_once(__DIR__ . '/slug.php');
