<?php
ini_set('display_errors', 0);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr en $errfile:$errline");
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor']);
    exit;
});

require_once '../config/database.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, ['error' => 'Método no permitido']);
}

$usuario_id = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : null;

if (!$usuario_id) {
    sendResponse(400, ['error' => 'ID de usuario requerido']);
}

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $query = "SELECT 
                COUNT(*) as total_tareas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN prioridad = 'alta' THEN 1 ELSE 0 END) as prioridad_alta,
                SUM(CASE WHEN prioridad = 'media' THEN 1 ELSE 0 END) as prioridad_media,
                SUM(CASE WHEN prioridad = 'baja' THEN 1 ELSE 0 END) as prioridad_baja
              FROM tareas 
              WHERE usuario_id = :usuario_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":usuario_id", $usuario_id);
    $stmt->execute();
    $estadisticas_tareas = $stmt->fetch(PDO::FETCH_ASSOC);

    $query_actividades = "SELECT accion, detalles, fecha 
                          FROM actividades 
                          WHERE usuario_id = :usuario_id 
                          ORDER BY fecha DESC 
                          LIMIT 10";
    $stmt_act = $db->prepare($query_actividades);
    $stmt_act->bindParam(":usuario_id", $usuario_id);
    $stmt_act->execute();
    $actividades = $stmt_act->fetchAll(PDO::FETCH_ASSOC);

    $total = intval($estadisticas_tareas['total_tareas']);
    $porcentaje_completadas = $total > 0 ? round(($estadisticas_tareas['completadas'] / $total) * 100, 2) : 0;

    sendResponse(200, [
        'estadisticas' => [
            'total_tareas' => intval($estadisticas_tareas['total_tareas']),
            'pendientes' => intval($estadisticas_tareas['pendientes'] ?? 0),
            'en_progreso' => intval($estadisticas_tareas['en_progreso'] ?? 0),
            'completadas' => intval($estadisticas_tareas['completadas'] ?? 0),
            'porcentaje_completadas' => $porcentaje_completadas,
            'prioridad_alta' => intval($estadisticas_tareas['prioridad_alta'] ?? 0),
            'prioridad_media' => intval($estadisticas_tareas['prioridad_media'] ?? 0),
            'prioridad_baja' => intval($estadisticas_tareas['prioridad_baja'] ?? 0)
        ],
        'actividades_recientes' => $actividades
    ]);

} catch(PDOException $e) {
    error_log("Error DB en estadísticas: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
} catch(Exception $e) {
    error_log("Error en estadísticas: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
}
?>
