<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr en $errfile:$errline");
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
    exit;
});

require_once '../config/database.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, ['error' => 'Método no permitido']);
}

$data = json_decode(file_get_contents("php://input"));

if (!$data || empty($data->email) || empty($data->password)) {
    sendResponse(400, ['error' => 'Email y contraseña requeridos']);
}

// ✅ Rate limit (internamente llama iniciarSesionSegura)
verificarRateLimit($data->email, 5, 300);

$email = validarEmail($data->email);

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT id, nombre, email, password FROM usuarios WHERE email = :email LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        error_log("Login fallido - Email no encontrado: " . $email);
        sendResponse(401, ['error' => 'Credenciales inválidas']);
    }

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (password_verify($data->password, $usuario['password'])) {

        // ✅ Usar iniciarSesionSegura en lugar de session_start() directo
        iniciarSesionSegura();
        session_regenerate_id(true);

        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['last_activity'] = time();

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Log de actividad
        $log_query = "INSERT INTO actividades (usuario_id, accion, detalles) 
                      VALUES (:usuario_id, 'login', 'Inicio de sesión exitoso')";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bindParam(":usuario_id", $usuario['id'], PDO::PARAM_INT);
        $log_stmt->execute();

        sendResponse(200, [
            'success'    => true,
            'mensaje'    => 'Login exitoso',
            'usuario'    => [
                'id'     => (int)$usuario['id'],
                'nombre' => sanitizarInput($usuario['nombre']),
                'email'  => $usuario['email']
            ],
            'csrf_token' => $_SESSION['csrf_token'],
            'session_id' => session_id()
        ]);

    } else {
        error_log("Login fallido - Password incorrecta para: " . $email);
        sendResponse(401, ['error' => 'Credenciales inválidas']);
    }

} catch (PDOException $e) {
    error_log("Error DB en login: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
} catch (Exception $e) {
    error_log("Error en login: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
}
?>