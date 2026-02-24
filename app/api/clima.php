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

$ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : 'London';

try {
    $geocode_url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($ciudad) . "&format=json&limit=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geocode_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ProyectoWeb/1.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $geocode_response = curl_exec($ch);
    curl_close($ch);

    $geocode_data = json_decode($geocode_response, true);

    if (empty($geocode_data)) {
        sendResponse(404, ['error' => 'Ciudad no encontrada']);
    }

    $lat = $geocode_data[0]['lat'];
    $lon = $geocode_data[0]['lon'];
    $nombre_ciudad = $geocode_data[0]['display_name'];

    $weather_url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=auto";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $weather_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $weather_response = curl_exec($ch);
    curl_close($ch);

    $weather_data = json_decode($weather_response, true);

    if (empty($weather_data) || empty($weather_data['current'])) {
        sendResponse(500, ['error' => 'No se pudo obtener datos del clima']);
    }

    $weather_code = $weather_data['current']['weather_code'];
    $descripcion = obtenerDescripcionClima($weather_code);

    sendResponse(200, [
        'ciudad' => $nombre_ciudad,
        'temperatura' => $weather_data['current']['temperature_2m'] . '°C',
        'humedad' => $weather_data['current']['relative_humidity_2m'] . '%',
        'velocidad_viento' => $weather_data['current']['wind_speed_10m'] . ' km/h',
        'descripcion' => $descripcion,
        'fecha_hora' => $weather_data['current']['time']
    ]);

} catch(Exception $e) {
    error_log("Error al obtener clima: " . $e->getMessage());
    sendResponse(500, ['error' => 'Error al obtener datos del clima']);
}

function obtenerDescripcionClima($code) {
    $descripciones = [
        0 => 'Cielo despejado',
        1 => 'Principalmente despejado',
        2 => 'Parcialmente nublado',
        3 => 'Nublado',
        45 => 'Niebla',
        48 => 'Niebla con escarcha',
        51 => 'Llovizna ligera',
        53 => 'Llovizna moderada',
        55 => 'Llovizna densa',
        61 => 'Lluvia ligera',
        63 => 'Lluvia moderada',
        65 => 'Lluvia intensa',
        71 => 'Nevada ligera',
        73 => 'Nevada moderada',
        75 => 'Nevada intensa',
        95 => 'Tormenta'
    ];

    return isset($descripciones[$code]) ? $descripciones[$code] : 'Desconocido';
}
?>
