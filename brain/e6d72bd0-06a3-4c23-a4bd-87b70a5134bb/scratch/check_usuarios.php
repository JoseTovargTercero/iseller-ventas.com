<?php
require 'publico/production/includes/requires.php';

// Describe usuarios table
$r = $conexion->query('DESCRIBE usuarios');
if ($r) {
    echo "=== COLUMNS IN usuarios ===\n";
    while ($row = $r->fetch_assoc()) {
        echo $row['Field'] . " | " . $row['Type'] . "\n";
    }
} else {
    echo "Error: " . $conexion->error . "\n";
}

// Test the exact prepare that's failing
$stmt = mysqli_prepare($conexion, "SELECT id, nombre, id_sucursal FROM `usuarios` WHERE bss_id = ? AND status = 0");
if (!$stmt) {
    echo "\n=== PREPARE ERROR ===\n";
    echo $conexion->error . "\n";
} else {
    echo "\n=== PREPARE OK ===\n";
    $stmt->close();
}
