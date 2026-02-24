<?php
/**
 * =================================================================
 * API: LOGIN
 * =================================================================
 * PROBLEMAS RESUELTOS:
 * ✅ Problema 1 - Broken Access Control: Rate limiting, sesiones
 * ✅ Problema 2 - Fallas Criptográficas: Hash bcrypt, sesiones seguras
 * ✅ Problema 3 - Diseño Inseguro: No revelar info, logs seguros
 * =================================================================
 */

require_once '../config/database.php';

setCorsHeaders();

/**
 * =================================================================
 * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
 * =================================================================
 * Solo POST, no GET (datos no en caché ni URL)
 * =================================================================
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(405, ['error' => 'Método no permitido']);
}

/**
 * =================================================================
 * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
 * =================================================================
 * Rate limiting para prevenir fuerza bruta
 * =================================================================
 */
$data = json_decode(file_get_contents("php://input"));

if (empty($data->email) || empty($data->password)) {
    sendResponse(400, ['error' => 'Email y contraseña requeridos']);
}

// Rate limit por email
verificarRateLimit($data->email, 5, 300);

/**
 * =================================================================
 * PROBLEMA 2 - FALLAS CRIPTOGRÁFICAS: SOLUCIONADO ✅
 * =================================================================
 * Validar y sanitizar email
 * =================================================================
 */
$email = validarEmail($data->email);

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    /**
     * =============================================================
     * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
     * =============================================================
     * Prepared statements para prevenir SQL Injection
     * =============================================================
     */
    $query = "SELECT id, nombre, email, password FROM usuarios WHERE email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->execute();

    /**
     * =============================================================
     * PROBLEMA 3 - DISEÑO INSEGURO: SOLUCIONADO ✅
     * =============================================================
     * Mismo mensaje genérico para usuario no existe o password incorrecta
     * No revelar si el email está registrado
     * =============================================================
     */
    if ($stmt->rowCount() === 0) {
        // Log de intento fallido
        error_log("Login fallido - Email no encontrado: " . $email);
        sendResponse(401, ['error' => 'Credenciales inválidas']);
    }

    $usuario = $stmt->fetch();

    /**
     * =============================================================
     * PROBLEMA 2 - FALLAS CRIPTOGRÁFICAS: SOLUCIONADO ✅
     * =============================================================
     * Verificación segura de password con bcrypt
     * password_verify compara hash de forma segura
     * =============================================================
     */
    if (password_verify($data->password, $usuario['password'])) {
        
        /**
         * ==========================================================
         * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
         * ==========================================================
         * Crear sesión segura del lado del servidor
         * ==========================================================
         */
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true
        ]);
        
        // Regenerar ID de sesión (prevenir session fixation)
        session_regenerate_id(true);
        
        // Guardar datos en sesión
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['last_activity'] = time();
        
        // Token CSRF
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        /**
         * ==========================================================
         * Registrar actividad exitosa
         * ==========================================================
         */
        $log_query = "INSERT INTO actividades (usuario_id, accion, detalles) 
                      VALUES (:usuario_id, 'login', 'Inicio de sesión exitoso')";
        $log_stmt = $db->prepare($log_query);
        $log_stmt->bindParam(":usuario_id", $usuario['id'], PDO::PARAM_INT);
        $log_stmt->execute();

        /**
         * ==========================================================
         * PROBLEMA 2 - FALLAS CRIPTOGRÁFICAS: SOLUCIONADO ✅
         * ==========================================================
         * NO enviar password en la respuesta
         * Incluir token CSRF para requests futuros
         * ==========================================================
         */
        sendResponse(200, [
            'mensaje' => 'Login exitoso',
            'usuario' => [
                'id' => $usuario['id'],
                'nombre' => sanitizarInput($usuario['nombre']),
                'email' => $usuario['email']
            ],
            'csrf_token' => $_SESSION['csrf_token'],
            'session_id' => session_id()
        ]);
    } else {
        /**
         * ==========================================================
         * PROBLEMA 3 - DISEÑO INSEGURO: SOLUCIONADO ✅
         * ==========================================================
         * Mismo mensaje que "usuario no existe"
         * Log de intento fallido para auditoría
         * ==========================================================
         */
        error_log("Login fallido - Password incorrecta para: " . $email);
        sendResponse(401, ['error' => 'Credenciales inválidas']);
    }

} catch(PDOException $e) {
    /**
     * =============================================================
     * PROBLEMA 3 - DISEÑO INSEGURO: SOLUCIONADO ✅
     * =============================================================
     * No revelar detalles del error SQL
     * Log en servidor, mensaje genérico al usuario
     * =============================================================
     */
    error_log("Error DB en login: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
}
?>
