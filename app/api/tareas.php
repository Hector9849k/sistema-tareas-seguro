<?php
require_once '../config/database.php';

setCorsHeaders();

$database = new Database();
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

    if (empty($data->usuario_id) || empty($data->titulo)) {
        sendResponse(400, ['error' => 'Usuario ID y título requeridos']);
    }

    $query = "INSERT INTO tareas (usuario_id, titulo, descripcion, prioridad, estado) 
              VALUES (:usuario_id, :titulo, :descripcion, :prioridad, :estado)";
    $stmt = $db->prepare($query);

    $stmt->bindParam(":usuario_id", $data->usuario_id);
    $stmt->bindParam(":titulo", $data->titulo);
    $stmt->bindParam(":descripcion", $data->descripcion);
    $stmt->bindParam(":prioridad", $data->prioridad);
    $stmt->bindParam(":estado", $data->estado);

    if ($stmt->execute()) {
        // Registrar actividad
        $log_query = "INSERT INTO actividades (usuario_id, accion, detalles) 
                      VALUES (:usuario_id, 'crear_tarea', :detalles)";
        $log_stmt = $db->prepare($log_query);
        $detalles = "Tarea creada: " . $data->titulo;
        $log_stmt->bindParam(":usuario_id", $data->usuario_id);
        $log_stmt->bindParam(":detalles", $detalles);
        $log_stmt->execute();

        sendResponse(201, [
            'mensaje' => 'Tarea creada exitosamente',
            'tarea_id' => $db->lastInsertId()
        ]);
    } else {
        sendResponse(500, ['error' => 'Error al crear tarea']);
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
    $stmt->bindParam(":descripcion", $data->descripcion);
    $stmt->bindParam(":prioridad", $data->prioridad);
    $stmt->bindParam(":estado", $data->estado);

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
?>
