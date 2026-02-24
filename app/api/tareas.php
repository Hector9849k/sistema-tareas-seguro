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

try {
    $database = Database::getInstance();
    $db = $database->getConnection();

    $metodo = $_SERVER['REQUEST_METHOD'];

    // OBTENER TAREAS (GET)
    if ($metodo === 'GET') {
        $usuario_id = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : null;
        
        if (!$usuario_id) {
            sendResponse(400, ['error' => 'ID de usuario requerido']);
        }

        $query = "SELECT * FROM tareas WHERE usuario_id = :usuario_id ORDER BY fecha_creacion DESC";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario_id", $usuario_id);
        $stmt->execute();

        $tareas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        sendResponse(200, ['tareas' => $tareas]);
    }

   // CREAR TAREA (POST)
if ($metodo === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!$data || empty($data->usuario_id) || empty($data->titulo)) {
        sendResponse(400, ['error' => 'Usuario ID y título requeridos']);
    }

    try {

        $query = "INSERT INTO tareas 
                  (usuario_id, titulo, descripcion, prioridad, estado) 
                  VALUES (:usuario_id, :titulo, :descripcion, :prioridad, :estado)";

        $stmt = $db->prepare($query);

        $stmt->bindParam(":usuario_id", $data->usuario_id);
        $stmt->bindParam(":titulo", $data->titulo);
        $stmt->bindParam(":descripcion", $data->descripcion);
        $stmt->bindParam(":prioridad", $data->prioridad);
        $stmt->bindParam(":estado", $data->estado);

        $stmt->execute();

        $tarea_id = $db->lastInsertId();

        // Intentar log (pero no fallar si no existe la tabla)
        try {
            $log_query = "INSERT INTO actividades (usuario_id, accion, detalles) 
                          VALUES (:usuario_id, 'crear_tarea', :detalles)";
            $log_stmt = $db->prepare($log_query);

            $detalles = "Tarea creada: " . $data->titulo;

            $log_stmt->bindParam(":usuario_id", $data->usuario_id);
            $log_stmt->bindParam(":detalles", $detalles);
            $log_stmt->execute();

        } catch (Exception $e) {
            error_log("Tabla actividades no existe o error log: " . $e->getMessage());
        }

        sendResponse(201, [
            'mensaje' => 'Tarea creada exitosamente',
            'tarea_id' => $tarea_id
        ]);

    } catch (PDOException $e) {

        error_log("Error al crear tarea: " . $e->getMessage());

        sendResponse(500, [
            'error' => 'Error al crear tarea',
            'detalle' => $e->getMessage()
        ]);
    }
}
    // ACTUALIZAR TAREA (PUT)
    if ($metodo === 'PUT') {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->id) || empty($data->usuario_id)) {
            sendResponse(400, ['error' => 'ID de tarea y usuario requeridos']);
        }

        $query = "UPDATE tareas SET 
                  titulo = :titulo,
                  descripcion = :descripcion,
                  prioridad = :prioridad,
                  estado = :estado
                  WHERE id = :id AND usuario_id = :usuario_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        $stmt->bindParam(":usuario_id", $data->usuario_id);
        $stmt->bindParam(":titulo", $data->titulo);
        $stmt->bindParam(":descripcion", $data->descripcion ?? '');
        $stmt->bindParam(":prioridad", $data->prioridad ?? 'media');
        $stmt->bindParam(":estado", $data->estado ?? 'pendiente');

        if ($stmt->execute()) {
            sendResponse(200, ['mensaje' => 'Tarea actualizada exitosamente']);
        } else {
            sendResponse(500, ['error' => 'Error al actualizar tarea']);
        }
    }

    // ELIMINAR TAREA (DELETE)
    if ($metodo === 'DELETE') {
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->id) || empty($data->usuario_id)) {
            sendResponse(400, ['error' => 'ID de tarea y usuario requeridos']);
        }

        $query = "DELETE FROM tareas WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $data->id);
        $stmt->bindParam(":usuario_id", $data->usuario_id);

        if ($stmt->execute()) {
            sendResponse(200, ['mensaje' => 'Tarea eliminada exitosamente']);
        } else {
            sendResponse(500, ['error' => 'Error al eliminar tarea']);
        }
    }

} catch(PDOException $e) {
    error_log("Error DB en tareas: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
} catch(Exception $e) {
    error_log("Error en tareas: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error del servidor']);
}
?>
