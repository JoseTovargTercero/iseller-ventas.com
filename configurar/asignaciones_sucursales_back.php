<?php
require_once('configuracion.php');
require_once('session.php');
require_once('_asignaciones_sucursales.php');

header('Content-Type: application/json; charset=utf-8');

$bss_id = (int) ($_SESSION['bss_id'] ?? 0);
$usuario_id = (int) ($_SESSION['id'] ?? 0);
$nivel = (int) ($_SESSION['nivel'] ?? 0);
$sucursal_sesion = (int) ($_SESSION['sucursal'] ?? 0);
$esAdmin = $nivel === 1;
$accion = trim((string) ($_POST['accion'] ?? $_GET['accion'] ?? ''));

if ($bss_id <= 0 || $usuario_id <= 0) {
    echo json_encode(asig_result_error('Sesión no válida.'));
    exit;
}

function asig_tiene_permiso(string $archivo, bool $esAdmin): bool
{
    if ($esAdmin) {
        return true;
    }
    return in_array($archivo, $_SESSION['permisos'] ?? [], true);
}

function asig_exigir_lectura(string $archivo, bool $esAdmin): void
{
    if (!asig_tiene_permiso($archivo, $esAdmin)) {
        echo json_encode(asig_result_error('No tiene permisos para consultar este módulo.'));
        exit;
    }
}

function asig_exigir_admin(bool $esAdmin): void
{
    if (!$esAdmin) {
        echo json_encode(asig_result_error('Esta operación solo está disponible para administradores.'));
        exit;
    }
}

switch ($accion) {
    case 'sucursales':
        asig_exigir_lectura('asignaciones.php', $esAdmin);
        echo json_encode(asig_result_ok('', ['sucursales' => asig_listar_sucursales($conexion, $bss_id)]));
        break;

    case 'buscar_productos':
        asig_exigir_lectura('asignaciones.php', $esAdmin);
        $sucursal = (int) ($_POST['sucursal'] ?? 0);
        $busqueda = trim((string) ($_POST['q'] ?? ''));
        $tipo = trim((string) ($_POST['tipo'] ?? ''));
        if (!asig_sucursal_existe($conexion, $bss_id, $sucursal)) {
            echo json_encode(asig_result_error('Seleccione una sucursal de origen válida.'));
            break;
        }
        echo json_encode(asig_result_ok('', [
            'productos' => asig_buscar_productos($conexion, $bss_id, $sucursal, $busqueda, $tipo),
        ]));
        break;

    case 'listar':
        asig_exigir_lectura('asignaciones.php', $esAdmin);
        $items = asig_listar_asignaciones($conexion, $bss_id, $usuario_id, $esAdmin, $sucursal_sesion, [
            'estado' => $_POST['estado'] ?? '',
            'sucursal' => $_POST['sucursal'] ?? 0,
            'desde' => $_POST['desde'] ?? '',
            'hasta' => $_POST['hasta'] ?? '',
        ]);
        echo json_encode(asig_result_ok('', ['items' => $items]));
        break;

    case 'detalle':
        asig_exigir_lectura('asignaciones.php', $esAdmin);
        $id = (int) ($_POST['id'] ?? 0);
        echo json_encode(asig_detalle_asignacion($conexion, $bss_id, $usuario_id, $esAdmin, $sucursal_sesion, $id));
        break;

    case 'guardar':
        asig_exigir_admin($esAdmin);
        $detalles = json_decode((string) ($_POST['detalles'] ?? '[]'), true);
        $resultado = asig_guardar_asignacion($conexion, $bss_id, $usuario_id, [
            'id' => (int) ($_POST['id'] ?? 0),
            'sucursal_origen' => (int) ($_POST['sucursal_origen'] ?? 0),
            'sucursal_destino' => (int) ($_POST['sucursal_destino'] ?? 0),
            'fecha' => $_POST['fecha'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
            'descuenta_origen' => $_POST['descuenta_origen'] ?? 1,
            'suma_destino' => $_POST['suma_destino'] ?? 1,
            'detalles' => is_array($detalles) ? $detalles : [],
        ]);
        echo json_encode($resultado);
        break;

    case 'aplicar':
        asig_exigir_admin($esAdmin);
        $id = (int) ($_POST['id'] ?? 0);
        echo json_encode(asig_aplicar_asignacion($conexion, $bss_id, $usuario_id, $id));
        break;

    case 'rectificar':
        asig_exigir_admin($esAdmin);
        $detalles = json_decode((string) ($_POST['detalles'] ?? '[]'), true);
        $resultado = asig_rectificar_asignacion(
            $conexion,
            $bss_id,
            $usuario_id,
            (int) ($_POST['id'] ?? 0),
            [
                'sucursal_origen' => (int) ($_POST['sucursal_origen'] ?? 0),
                'sucursal_destino' => (int) ($_POST['sucursal_destino'] ?? 0),
                'fecha' => $_POST['fecha'] ?? '',
                'observaciones' => $_POST['observaciones'] ?? '',
                'descuenta_origen' => $_POST['descuenta_origen'] ?? 1,
                'suma_destino' => $_POST['suma_destino'] ?? 1,
                'detalles' => is_array($detalles) ? $detalles : [],
            ],
            (string) ($_POST['motivo'] ?? '')
        );
        echo json_encode($resultado);
        break;

    case 'eliminar':
        asig_exigir_admin($esAdmin);
        $id = (int) ($_POST['id'] ?? 0);
        echo json_encode(asig_eliminar_borrador($conexion, $bss_id, $usuario_id, $id));
        break;

    case 'deudas_listar':
        asig_exigir_lectura('deudas_sucursales.php', $esAdmin);
        $items = asig_listar_cuentas($conexion, $bss_id, $usuario_id, $esAdmin, $sucursal_sesion, [
            'sucursal_deudora' => $_POST['sucursal_deudora'] ?? 0,
            'sucursal_acreedora' => $_POST['sucursal_acreedora'] ?? 0,
            'estado' => $_POST['estado'] ?? '',
        ]);
        echo json_encode(asig_result_ok('', ['items' => $items]));
        break;

    case 'deudas_resumen':
        asig_exigir_lectura('deudas_sucursales.php', $esAdmin);
        $items = asig_listar_cuentas($conexion, $bss_id, $usuario_id, $esAdmin, $sucursal_sesion, [
            'sucursal_deudora' => $_POST['sucursal_deudora'] ?? 0,
            'sucursal_acreedora' => $_POST['sucursal_acreedora'] ?? 0,
            'estado' => $_POST['estado'] ?? '',
        ]);
        echo json_encode(asig_result_ok('', ['resumen' => asig_resumen_cuentas($items)]));
        break;

    case 'deudas_detalle':
        asig_exigir_lectura('deudas_sucursales.php', $esAdmin);
        $id = (int) ($_POST['id'] ?? 0);
        echo json_encode(asig_detalle_cuenta($conexion, $bss_id, $esAdmin, $sucursal_sesion, $id));
        break;

    case 'registrar_pago':
        asig_exigir_admin($esAdmin);
        $resultado = asig_registrar_pago($conexion, $bss_id, $usuario_id, [
            'cuenta_id' => (int) ($_POST['cuenta_id'] ?? 0),
            'monto_usd' => $_POST['monto_usd'] ?? 0,
            'fecha' => $_POST['fecha'] ?? '',
            'tasa_bcv' => $_POST['tasa_bcv'] ?? 0,
            'tasa_peso_dolar' => $_POST['tasa_peso_dolar'] ?? 0,
            'metodo_pago' => $_POST['metodo_pago'] ?? '',
            'referencia' => $_POST['referencia'] ?? '',
            'observaciones' => $_POST['observaciones'] ?? '',
        ]);
        echo json_encode($resultado);
        break;

    default:
        echo json_encode(asig_result_error('Acción no reconocida.'));
        break;
}
