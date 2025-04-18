<?php
require __DIR__ . '/vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;

$loop = React\EventLoop\Loop::get();
$baseDir = __DIR__;

// Función mejorada para servir archivos
function serveFile(string $requestedPath, string $baseDir): Response {
    // Mapeo de rutas URL a archivos físicos
    $routeMap = [
        '/' => '/index.html',
        '/alojamientos' => '/alojamientos.html',
        '/contact' => '/contact.html'
    ];
    
    // Verifica si es una ruta conocida
    if (isset($routeMap[$requestedPath])) {
        $filePath = $baseDir . $routeMap[$requestedPath];
    } else {
        // Intenta servir el archivo directamente
        $filePath = $baseDir . '/public' . $requestedPath;
        
        // Si no está en /public, busca en la raíz
        if (!file_exists($filePath)) {
            $filePath = $baseDir . $requestedPath;
        }
    }

    // Verifica si el archivo existe
    if (!file_exists($filePath)) {
        return new Response(404, ['Content-Type' => 'text/plain'], 'Archivo no encontrado: ' . $requestedPath);
    }

    // Tipos MIME
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'html' => 'text/html',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'json' => 'application/json'
    ];

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $contentType = $mimeTypes[$extension] ?? mime_content_type($filePath);

    return new Response(200, ['Content-Type' => $contentType], file_get_contents($filePath));
}

// Servidor HTTP
$server = new HttpServer($loop, function (ServerRequestInterface $request) use ($baseDir) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();

    // Configuración CORS
    $corsHeaders = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type'
    ];

    // Manejar solicitud OPTIONS para CORS
    if ($method === 'OPTIONS') {
        return new Response(200, $corsHeaders, '');
    }

// API de Alojamientos - Rutas generales y específicas
if (strpos($path, '/api/alojamientos') === 0) {
    $jsonFile = $baseDir . '/alojamientos.json';
    
    try {
        // Leer archivo JSON
        $alojamientos = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        
        // Ruta para operaciones con ID específico (PUT, DELETE, GET uno)
        if (preg_match('#^/api/alojamientos/([^/]+)$#', $path, $matches)) {
            $id = $matches[1];
            
            switch ($method) {
                case 'GET':
                    // Buscar alojamiento por ID
                    foreach ($alojamientos as $alojamiento) {
                        if ($alojamiento['id'] === $id) {
                            return new Response(
                                200, 
                                array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                                json_encode($alojamiento)
                            );
                        }
                    }
                    return new Response(
                        404, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode(['error' => 'Alojamiento no encontrado'])
                    );
                    
                case 'PUT':
                    $data = json_decode((string)$request->getBody(), true);
                    
                    // Buscar alojamiento por ID
                    $found = false;
                    foreach ($alojamientos as &$alojamiento) {
                        if ($alojamiento['id'] === $id) {
                            // Actualizar solo los campos proporcionados
                            $alojamiento = array_merge($alojamiento, $data);
                            $found = true;
                            break;
                        }
                    }
                    
                    if (!$found) {
                        return new Response(
                            404, 
                            array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                            json_encode(['error' => 'Alojamiento no encontrado'])
                        );
                    }
                    
                    // Guardar cambios
                    file_put_contents($jsonFile, json_encode($alojamientos, JSON_PRETTY_PRINT));
                    
                    return new Response(
                        200, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode(['message' => 'Alojamiento actualizado correctamente'])
                    );
                    
                case 'DELETE':
                    // Filtrar alojamientos
                    $initialCount = count($alojamientos);
                    $alojamientos = array_filter($alojamientos, function($aloj) use ($id) {
                        return $aloj['id'] !== $id;
                    });
                    
                    // Verificar si se eliminó algún elemento
                    if (count($alojamientos) === $initialCount) {
                        return new Response(
                            404, 
                            array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                            json_encode(['error' => 'Alojamiento no encontrado'])
                        );
                    }
                    
                    // Reindexar y guardar
                    $alojamientos = array_values($alojamientos);
                    file_put_contents($jsonFile, json_encode($alojamientos, JSON_PRETTY_PRINT));
                    
                    return new Response(
                        200, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode(['message' => 'Alojamiento eliminado correctamente'])
                    );
                    
                default:
                    return new Response(
                        405, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode(['error' => 'Método no permitido'])
                    );
            }
        }
        // Ruta para operaciones generales (GET todos, POST)
        elseif ($path === '/api/alojamientos') {
            switch ($method) {
                case 'GET':
                    return new Response(
                        200, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode($alojamientos)
                    );
                    
                case 'POST':
                    $data = json_decode((string)$request->getBody(), true);
                    
                    // Validación básica
                    if (empty($data['nombre']) || empty($data['tipo'])) {
                        return new Response(
                            400, 
                            array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                            json_encode(['error' => 'Nombre y tipo son requeridos'])
                        );
                    }
                    
                    // Validación básica (opcional para la URL)
    if (isset($data['imagen']) && $data['imagen'] !== null) {
        if (!filter_var($data['imagen'], FILTER_VALIDATE_URL)) {
            return new Response(
                400, 
                array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                json_encode(['error' => 'URL de imagen no válida'])
            );
        }
    }
                    
                    // Agregar nuevo alojamiento
                    $data['id'] = uniqid(); // ID único
                    $alojamientos[] = $data;
                    
                    // Guardar en archivo
                    file_put_contents($jsonFile, json_encode($alojamientos, JSON_PRETTY_PRINT));
                    
                    return new Response(
                        201, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode($data)
                    );
                    
                default:
                    return new Response(
                        405, 
                        array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
                        json_encode(['error' => 'Método no permitido'])
                    );
            }
        }
    } catch (Exception $e) {
        return new Response(
            500, 
            array_merge(['Content-Type' => 'application/json'], $corsHeaders), 
            json_encode(['error' => $e->getMessage()])
        );
    }
}

    // Servir archivos estáticos y páginas HTML
    return serveFile($path, $baseDir);
});

// Iniciar servidor
$socket = new SocketServer('0.0.0.0:8081');
$server->listen($socket);

echo "Servidor ReactPHP funcionando en http://localhost:8081\n";

$loop->run();