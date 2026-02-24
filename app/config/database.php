
<?php
class Database {
    private static $instance = null;
    private $conn;
    
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;

    private function __construct() {
        // Railway usa estas variables — se prueba en orden de más a menos específico
        $this->host     = getenv('MYSQLHOST')     ?: getenv('MYSQL_HOST')     ?: 'localhost';
        $this->port     = getenv('MYSQLPORT')     ?: getenv('MYSQL_PORT')     ?: '3306';
        $this->db_name  = getenv('MYSQLDATABASE') ?: getenv('MYSQL_DATABASE') ?: 'proyecto_db';
        $this->username = getenv('MYSQLUSER')     ?: getenv('MYSQL_USER')     ?: 'root';
        $this->password = getenv('MYSQLPASSWORD') ?: getenv('MYSQL_PASSWORD') ?: '';

        // Si Railway provee URL completa, usarla
        $url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL');
        if ($url) {
            $this->parseConnectionUrl($url);
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function parseConnectionUrl($url) {
        $parts = parse_url($url);
        if ($parts) {
            $this->host     = $parts['host']                      ?? $this->host;
            $this->port     = isset($parts['port']) ? (string)$parts['port'] : $this->port;
            $this->username = $parts['user']                      ?? $this->username;
            $this->password = $parts['pass']                      ?? $this->password;
            // Quitar el "/" inicial del path para obtener el nombre de la DB
            if (!empty($parts['path'])) {
                $this->db_name = ltrim($parts['path'], '/') ?: $this->db_name;
            }
        }
    }

    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
                $this->conn = new PDO($dsn, $this->username, $this->password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 10,
                ]);
            } catch (PDOException $e) {
                error_log("Error de conexión DB: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Error de conexión al servidor']);
                exit;
            }
        }
        return $this->conn;
    }

    private function __clone() {}
    public function __wakeup() { throw new Exception("Cannot unserialize singleton"); }
}

// ─── CORS ────────────────────────────────────────────────────────────────────
function setCorsHeaders() {
    $allowed_origin = getenv('ALLOWED_ORIGIN') ?: '*';
    header("Access-Control-Allow-Origin: $allowed_origin");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");

    // ✅ Responder preflight OPTIONS y salir
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// ─── Respuesta JSON ───────────────────────────────────────────────────────────
function sendResponse($status, $data) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ─── Sesión segura (llamar UNA sola vez) ─────────────────────────────────────
function iniciarSesionSegura() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
}

// ─── Validar sesión ───────────────────────────────────────────────────────────
function validarSesion() {
    iniciarSesionSegura();
    if (!isset($_SESSION['usuario_id'])) {
        sendResponse(401, ['error' => 'No autorizado. Debe iniciar sesión.']);
    }
    return $_SESSION['usuario_id'];
}

// ─── Verificar acceso a recurso ───────────────────────────────────────────────
function verificarAccesoRecurso($usuario_id_sesion, $usuario_id_recurso) {
    if ($usuario_id_sesion != $usuario_id_recurso) {
        sendResponse(403, ['error' => 'Acceso denegado.']);
    }
}

// ─── Sanitizar ────────────────────────────────────────────────────────────────
function sanitizarInput($data) {
    if (is_array($data)) {
        return array_map('sanitizarInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// ─── Validar email ────────────────────────────────────────────────────────────
function validarEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(400, ['error' => 'Email inválido']);
    }
    return $email;
}

// ─── Rate limiting ────────────────────────────────────────────────────────────
// ✅ Ya NO llama session_start() — usa iniciarSesionSegura() que evita doble inicio
function verificarRateLimit($identificador, $max_intentos = 5, $ventana = 300) {
    iniciarSesionSegura(); // ← Seguro: solo inicia si no hay sesión activa

    $key = 'rate_limit_' . md5($identificador);

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'time' => time()];
    }

    // Resetear ventana si expiró
    if (time() - $_SESSION[$key]['time'] > $ventana) {
        $_SESSION[$key] = ['count' => 1, 'time' => time()];
        return true;
    }

    if ($_SESSION[$key]['count'] >= $max_intentos) {
        $espera = $ventana - (time() - $_SESSION[$key]['time']);
        sendResponse(429, ['error' => "Demasiados intentos. Espere {$espera} segundos."]);
    }

    $_SESSION[$key]['count']++;
    return true;
}
?>