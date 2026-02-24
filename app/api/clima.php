<?php
require_once '../config/database.php';

setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendResponse(405, ['error' => 'Método no permitido']);
}

$ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : 'London';

try {
    // API gratuita de Open-Meteo (no requiere API key)
    // Primero obtenemos las coordenadas de la ciudad usando Nominatim
    $geocode_url = "https://nominatim.openstreetmap.org/search?q=" . urlencode($ciudad) . "&format=json&limit=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $geocode_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ProyectoWeb/1.0');
    $geocode_response = curl_exec($ch);
    curl_close($ch);

    $geocode_data = json_decode($geocode_response, true);

    if (empty($geocode_data)) {
        sendResponse(404, ['error' => 'Ciudad no encontrada']);
    }

    $lat = $geocode_data[0]['lat'];
    $lon = $geocode_data[0]['lon'];
    $nombre_ciudad = $geocode_data[0]['display_name'];

    // Ahora obtenemos el clima usando Open-Meteo
    $weather_url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=auto";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $weather_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $weather_response = curl_exec($ch);
    curl_close($ch);

    $weather_data = json_decode($weather_response, true);

    // Interpretar código de clima
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
    sendResponse(500, ['error' => 'Error al obtener datos del clima: ' . $e->getMessage()]);
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
