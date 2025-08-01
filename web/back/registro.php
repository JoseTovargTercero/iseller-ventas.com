<?php
header('Content-Type: application/json');

// Leer JSON
$data = json_decode(file_get_contents('php://input'), true);

// Sanitizar
$nombres = trim($data['nombres'] ?? '');
$email = filter_var(trim($data['email'] ?? ''), FILTER_VALIDATE_EMAIL);
$password = trim($data['password'] ?? '');

// Validar
if (!$nombres || !$email || !$password || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
    exit;
}

// Aquí deberías verificar si el email ya existe en la base de datos

// Encriptar la contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

// Guardar en la base de datos (ejemplo)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=mi_base', 'usuario', 'clave');
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombres, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$nombres, $email, $hash]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar.']);
}
