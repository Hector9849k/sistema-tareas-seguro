<?php
require_once '../config/database.php';

setCorsHeaders();

// Solo aceptar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, ['error' => 'Método no permitido']);
}

// Obtener datos JSON
$data = json_decode(file_get_contents("php://input"));

// Validar datos
if (empty($data->nombre) || empty($data->email) || empty($data->password)) {
    sendResponse(400, ['error' => 'Faltan datos requeridos']);
}

// Validar email
if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(400, ['error' => 'Email inválido']);
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Verificar si el email ya existe
    $query = "SELECT id FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        sendResponse(409, ['error' => 'El email ya está registrado']);
    }

    // Insertar nuevo usuario
    $query = "INSERT INTO usuarios (nombre, email, password) VALUES (:nombre, :email, :password)";
    $stmt = $db->prepare($query);

    // Hash de la contraseña
    $password_hash = password_hash($data->password, PASSWORD_BCRYPT);

    $stmt->bindParam(":nombre", $data->nombre);
    $stmt->bindParam(":email", $data->email);
    $stmt->bindParam(":password", $password_hash);

    if ($stmt->execute()) {
        sendResponse(201, [
            'mensaje' => 'Usuario registrado exitosamente',
            'usuario_id' => $db->lastInsertId()
        ]);
    } else {
        sendResponse(500, ['error' => 'Error al registrar usuario']);
    }

} catch(PDOException $e) {
    sendResponse(500, ['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
