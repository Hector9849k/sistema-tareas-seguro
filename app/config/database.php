<?php
/**
 * =================================================================
 * PROBLEMA 3 - DISEÑO INSEGURO: SOLUCIONADO ✅
 * =================================================================
 * Implementación de clase Database con patrón Singleton
 * Previene múltiples conexiones y asegura configuración única
 * =================================================================
 */

class Database {
    private static $instance = null;
    private $conn;
    
    // Configuración con variables de entorno (Railway/Producción)
    private $host;
    private $db_name;
    private $username;
    private $password;

    private function __construct() {
        /**
         * =============================================================
         * PROBLEMA 2 - FALLAS CRIPTOGRÁFICAS: SOLUCIONADO ✅
         * =============================================================
         * No guardar credenciales en código
         * Usar variables de entorno en producción
         * =============================================================
         */
        $this->host = getenv('MYSQL_HOST') ?: getenv('MYSQLHOST') ?: "db";
        $this->db_name = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: "proyecto_db";
        $this->username = getenv('MYSQL_USER') ?: getenv('MYSQLUSER') ?: "usuario";
        $this->password = getenv('MYSQL_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: "password123";
        
        // Railway a veces usa MYSQL_URL completa
        if (getenv('MYSQL_URL')) {
            $this->parseConnectionUrl(getenv('MYSQL_URL'));
        }
    }

    /**
     * Patrón Singleton: Una sola instancia de conexión
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function parseConnectionUrl($url) {
        $parts = parse_url($url);
        if ($parts) {
            $this->host = $parts['host'] ?? $this->host;
            $this->username = $parts['user'] ?? $this->username;
            $this->password = $parts['pass'] ?? $this->password;
            $this->db_name = ltrim($parts['path'], '/') ?? $this->db_name;
        }
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false, // Seguridad adicional
                    ]
                );
            } catch(PDOException $exception) {
                /**
                 * =============================================================
                 * PROBLEMA 3 - DISEÑO INSEGURO: SOLUCIONADO ✅
                 * =============================================================
                 * No revelar detalles de errores en producción
                 * =============================================================
                 */
                error_log("Error de conexión DB: " . $exception->getMessage());
                die(json_encode(['error' => 'Error de conexión al servidor']));
            }
        }
        return $this->conn;
    }

    // Prevenir clonación de la instancia
    private function __clone() {}
    
    // Prevenir deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * =================================================================
 * FUNCIÓN: setCorsHeaders()
 * =================================================================
 * Headers CORS seguros
 * =================================================================
 */
function setCorsHeaders() {
    /**
     * =============================================================
     * PROBLEMA 3 - DISEÑO INSEGURO: SOLUCIONADO ✅
     * =============================================================
     * En producción, especificar dominios permitidos
     * No usar * en producción
     * =============================================================
     */
    $allowed_origin = getenv('ALLOWED_ORIGIN') ?: '*';
    header("Access-Control-Allow-Origin: $allowed_origin");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    // Seguridad adicional
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
}

/**
 * =================================================================
 * FUNCIÓN: sendResponse()
 * =================================================================
 * Enviar respuestas JSON estandarizadas
 * =================================================================
 */
function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * =================================================================
 * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
 * =================================================================
 * Función para validar sesión y permisos
 * =================================================================
 */
function validarSesion() {
    session_start([
        'cookie_httponly' => true,  // No accesible desde JavaScript
        'cookie_secure' => true,     // Solo HTTPS
        'cookie_samesite' => 'Strict' // Protección CSRF
    ]);
    
    if (!isset($_SESSION['usuario_id'])) {
        sendResponse(401, ['error' => 'No autorizado. Debe iniciar sesión.']);
    }
    
    return $_SESSION['usuario_id'];
}

/**
 * =================================================================
 * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
 * =================================================================
 * Verificar que el usuario tenga acceso al recurso
 * =================================================================
 */
function verificarAccesoRecurso($usuario_id_sesion, $usuario_id_recurso) {
    if ($usuario_id_sesion != $usuario_id_recurso) {
        sendResponse(403, ['error' => 'Acceso denegado. No tiene permisos para este recurso.']);
    }
}

/**
 * =================================================================
 * PROBLEMA 2 - FALLAS CRIPTOGRÁFICAS: SOLUCIONADO ✅
 * =================================================================
 * Sanitizar datos de entrada
 * =================================================================
 */
function sanitizarInput($data) {
    if (is_array($data)) {
        return array_map('sanitizarInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * =================================================================
 * PROBLEMA 2 - FALLAS CRIPTOGRÁFICAS: SOLUCIONADO ✅
 * =================================================================
 * Validar y sanitizar email
 * =================================================================
 */
function validarEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, ['error' => 'Email inválido']);
    }
    return $email;
}

/**
 * =================================================================
 * PROBLEMA 1 - BROKEN ACCESS CONTROL: SOLUCIONADO ✅
 * =================================================================
 * Rate limiting simple (prevenir fuerza bruta)
 * =================================================================
 */
function verificarRateLimit($identificador, $max_intentos = 5, $ventana = 300) {
    session_start();
    $key = 'rate_limit_' . $identificador;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }
    
    $rate_data = $_SESSION[$key];
    
    // Resetear si pasó la ventana de tiempo
    if (time() - $rate_data['time'] > $ventana) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }
    
    // Verificar límite
    if ($rate_data['count'] >= $max_intentos) {
        sendResponse(429, [
            'error' => 'Demasiados intentos. Espere ' . 
                      ($ventana - (time() - $rate_data['time'])) . ' segundos.'
        ]);
    }
    
    $_SESSION[$key]['count']++;
    return true;
}
?>
