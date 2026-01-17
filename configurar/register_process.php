<?php
header('Content-Type: application/json');
require_once 'configuracion.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido");
    }

    // 1. Recibir y validar datos
    $tipo_negocio = $_POST['tipo_negocio'] ?? '';
    $otro_tipo = $_POST['otro_tipo'] ?? '';
    $plan = $_POST['plan'] ?? null;
    if ($tipo_negocio === 'otro' && !empty($otro_tipo)) {
        $tipo_negocio = $otro_tipo;
    }

    $nombre_negocio = $_POST['nombre_negocio'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($tipo_negocio) || empty($nombre_negocio) || empty($nombres) || empty($email) || empty($password)) {
        throw new Exception("Todos los campos son obligatorios");
    }

    // 1.5 Verificar si el email ya existe
    $stmtCheck = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
    if (!$stmtCheck) throw new Exception("Error al verificar disponibilidad de email");
    
    $stmtCheck->bind_param("s", $email);
    $stmtCheck->execute();
    $stmtCheck->store_result();
    
    if ($stmtCheck->num_rows > 0) {
        $stmtCheck->close();
        throw new Exception("El correo electrónico ya se encuentra registrado");
    }
    $stmtCheck->close();

    // 2. Iniciar transacción
    $conexion->begin_transaction();

    // 3. Paso 1: Registrar negocio
    $fecha_registro = date('Y-m-d H:i:s');
    $proxima_facturacion = date('Y-m-d', strtotime('+3 months'));
    $estado = 'activo';

    $stmtNegocio = $conexion->prepare("INSERT INTO negocio (tipo, nombre, fecha_regitro, proxima_facturacion, estado, plan) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmtNegocio) throw new Exception("Error preparando negocio: " . $conexion->error);
    
    $stmtNegocio->bind_param("ssssss", $tipo_negocio, $nombre_negocio, $fecha_registro, $proxima_facturacion, $estado, $plan);
    
    if (!$stmtNegocio->execute()) {
        throw new Exception("Error al registrar negocio: " . $stmtNegocio->error);
    }
    $bss_id = $conexion->insert_id;
    $stmtNegocio->close();

    // 4. Paso 2: Registro de sucursal
    $tipo_sucursal = $tipo_negocio; 
    $nombre_sucursal = "Sucursal 1";
    $principal = 1;
    $stockCritico = 10;

    $stmtSucursal = $conexion->prepare("INSERT INTO sucursales (bss_id, tipo, nombre, principal, stockCritico) VALUES (?, ?, ?, ?, ?)");
    if (!$stmtSucursal) throw new Exception("Error preparando sucursal: " . $conexion->error);

    $stmtSucursal->bind_param("issii", $bss_id, $tipo_sucursal, $nombre_sucursal, $principal, $stockCritico);
    
    if (!$stmtSucursal->execute()) {
        throw new Exception("Error al registrar sucursal: " . $stmtSucursal->error);
    }
    $id_sucursal = $conexion->insert_id;
    $stmtSucursal->close();

    // 5. Paso 3: Registrar al usuario
    // Hashear contraseña para seguridad
    $hashed_password = password_hash($password, PASSWORD_DEFAULT); 
    
    $nivel = '1'; // Administrador
    $status = 0; // Por defecto
    $darkMode = 0; // Por defecto
    
    $stmtUsuario = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, nivel, contrasena, status, darkMode, bss_id, id_sucursal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtUsuario) throw new Exception("Error preparando usuario: " . $conexion->error);

    $stmtUsuario->bind_param("ssssiiii", $nombres, $email, $nivel, $hashed_password, $status, $darkMode, $bss_id, $id_sucursal);
    
    if (!$stmtUsuario->execute()) {
        throw new Exception("Error al registrar usuario: " . $stmtUsuario->error);
    }
    $stmtUsuario->close();

    // 6. Paso 4: Registro de configuración
    $tickets = 1;
    $bs_ticket = 1;
    
    $stmtConfig = $conexion->prepare("INSERT INTO configuracion (tickets, bs_ticket, id_sucursal, bss_id) VALUES (?, ?, ?, ?)");
    if (!$stmtConfig) throw new Exception("Error preparando configuración: " . $conexion->error);

    $stmtConfig->bind_param("iiii", $tickets, $bs_ticket, $id_sucursal, $bss_id);

    if (!$stmtConfig->execute()) {
        throw new Exception("Error al registrar configuración: " . $stmtConfig->error);
    }
    $stmtConfig->close();

    // 7. Paso 5: cambios_mostrados
    $tasas_default = json_encode(["precio_bolivar_dolar"=>"1","precio_bolivar_peso"=>"1","precio_bs_visible"=>"1"]);
    
    $stmtCambios = $conexion->prepare("INSERT INTO cambios_mostrados (bss_id, tasas) VALUES (?, ?)");
    if (!$stmtCambios) throw new Exception("Error preparando cambios mostrados: " . $conexion->error);

    $stmtCambios->bind_param("is", $bss_id, $tasas_default);

    if (!$stmtCambios->execute()) {
        throw new Exception("Error al registrar cambios mostrados: " . $stmtCambios->error);
    }
    $stmtCambios->close();

    // 8. Paso 6: cambio (Copiar template)
    $sqlTemplate = "SELECT pesoDolar, bolivar_peso, peso_bolivar, DolarBolivar, tipo_tasa_bs, margen_neto, redondeo FROM cambio LIMIT 1";
    $resultTemplate = $conexion->query($sqlTemplate);
    
    // Valores por defecto
    $pesoDolar = '1';
    $bolivar_peso = '1';
    $peso_bolivar = '1';
    $DolarBolivar = '1';
    $tipo_tasa_bs = 1;
    $margen_neto = '10';
    $redondeo = 1;

    if ($resultTemplate && $resultTemplate->num_rows > 0) {
        $row = $resultTemplate->fetch_assoc();
        $pesoDolar = $row['pesoDolar'];
        $bolivar_peso = $row['bolivar_peso'];
        $peso_bolivar = $row['peso_bolivar'];
        $DolarBolivar = $row['DolarBolivar'];
        $tipo_tasa_bs = $row['tipo_tasa_bs'];
        $margen_neto = $row['margen_neto'];
        $redondeo = $row['redondeo'];
    }

    $stmtInsertCambio = $conexion->prepare("INSERT INTO cambio (pesoDolar, bolivar_peso, peso_bolivar, DolarBolivar, tipo_tasa_bs, margen_neto, redondeo, bss_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtInsertCambio) throw new Exception("Error preparando cambio: " . $conexion->error);

    $stmtInsertCambio->bind_param("ssssisii", $pesoDolar, $bolivar_peso, $peso_bolivar, $DolarBolivar, $tipo_tasa_bs, $margen_neto, $redondeo, $bss_id);

    if (!$stmtInsertCambio->execute()) {
        throw new Exception("Error al registrar cambio: " . $stmtInsertCambio->error);
    }
    $stmtInsertCambio->close();

    // Commit si todo salió bien
    $conexion->commit();

    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} finally {
    if (isset($conexion)) {
        $conexion->close();
    }
}
?>
