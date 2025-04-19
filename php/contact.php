<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET");

$dataFile = __DIR__ . "/../data/contact_messages.json";

// Leer mensajes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($dataFile)) {
        $messages = file_get_contents($dataFile);
        echo $messages;
    } else {
        echo json_encode([]);
    }
    exit;
}

// Guardar nuevo mensaje
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    // Validar campos mínimos
    if (
        !isset($input["nombre"]) || !isset($input["correo"]) ||
        !isset($input["fecha_llegada"]) || !isset($input["fecha_salida"]) ||
        !isset($input["tipo"])
    ) {
        http_response_code(400);
        echo json_encode(["error" => "Faltan campos obligatorios"]);
        exit;
    }

    // Cargar mensajes actuales
    $messages = [];
    if (file_exists($dataFile)) {
        $messages = json_decode(file_get_contents($dataFile), true);
    }

    // Agregar nuevo mensaje
    $messages[] = $input;

    // Guardar en archivo
    file_put_contents($dataFile, json_encode($messages, JSON_PRETTY_PRINT));
    echo json_encode(["message" => "Mensaje guardado correctamente"]);
    exit;
}

http_response_code(405);
echo json_encode(["error" => "Método no permitido"]);
