<?php
require __DIR__ . '/vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use React\EventLoop\Factory;
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
    $jsonFile = $baseDir . '/data/alojamientos.json';
    
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

// Ruta /data: muestra alojamientos en HTML o JSON según el header Accept
    if ($method === 'GET' && $path === '/data') {
        $jsonFile = $baseDir . '/data/alojamientos.json';

        if (!file_exists($jsonFile)) {
            return new Response(
                404,
                array_merge(['Content-Type' => 'text/plain'], $corsHeaders),
                'Archivo de datos no encontrado'
            );
        }

        $alojamientos = json_decode(file_get_contents($jsonFile), true);
        $acceptHeader = $request->getHeaderLine('Accept');

        if (strpos($acceptHeader, 'text/html') !== false) {
            // Devolver tabla HTML
            $html = '<html><head><title>Alojamientos</title><style>
            body{font-family:sans-serif}table{border-collapse:collapse;margin:20px 0;} td,th{border:1px solid #ccc;padding:6px;text-align:left}
            img{max-height:60px}
            </style></head><body>';
            $html .= '<h1>Lista de Alojamientos</h1>';
            $html .= '<table><thead><tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Imagen</th>
            <th>Capacidad</th>
            <th>Precio por noche</th>
            <th>Disponible</th>
            </tr></thead><tbody>';

            foreach ($alojamientos as $aloj) {
                $id = htmlspecialchars($aloj['id'] ?? 'N/A');
                $nombre = htmlspecialchars($aloj['nombre'] ?? 'N/A');
                $tipo = htmlspecialchars($aloj['tipo'] ?? 'N/A');
                $imagen = htmlspecialchars($aloj['imagen'] ?? '');
                $capacidad = htmlspecialchars($aloj['capacidad'] ?? 'N/A');
                $precio = htmlspecialchars($aloj['precio_por_noche'] ?? 'N/A');
                $disponible = isset($aloj['disponible']) && $aloj['disponible'] ? 'Sí' : 'No';
            
                $html .= "<tr>
                    <td>$id</td>
                    <td>$nombre</td>
                    <td>$tipo</td>
                    <td><img src=\"$imagen\" alt=\"imagen\"></td>
                    <td>$capacidad</td>
                    <td>$$precio</td>
                    <td>$disponible</td>
                </tr>";
            }

            $html .= '</tbody></table></body></html>';

            return new Response(
                200,
                array_merge(['Content-Type' => 'text/html'], $corsHeaders),
                $html
            );
        } else {
            // Devolver JSON
            return new Response(
                200,
                array_merge(['Content-Type' => 'application/json'], $corsHeaders),
                json_encode($alojamientos)
            );
        }
    }


    // Ruta GET para mostrar contactos como HTML con Bootstrap
if ($path === '/contact/contactos' && $method === 'GET') {
    $jsonFile = $baseDir . '/contact/contactos.json';

    if (!file_exists($jsonFile)) {
        return new Response(
            404,
            array_merge(['Content-Type' => 'text/html'], $corsHeaders),
            '<h1 class="text-center text-danger mt-5">404 - Archivo de contactos no encontrado.</h1>'
        );
    }

    $contactos = json_decode(file_get_contents($jsonFile), true);

    $html = '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Lista de Contactos</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <h1 class="mb-4 text-center text-primary">Contactos Recibidos</h1>
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Fecha de Llegada</th>
                        <th>Fecha de Salida</th>
                        <th>Tipo</th>
                        <th>N.º Huéspedes</th>
                        <th>Mensaje</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($contactos as $c) {
        $html .= '<tr>
            <td>' . htmlspecialchars($c['nombre']) . '</td>
            <td>' . htmlspecialchars($c['correo']) . '</td>
            <td>' . htmlspecialchars($c['fecha_llegada']) . '</td>
            <td>' . htmlspecialchars($c['fecha_salida']) . '</td>
            <td>' . htmlspecialchars($c['tipo']) . '</td>
            <td>' . htmlspecialchars($c['huespedes'] ?? 'No especificado') . '</td>
            <td>' . nl2br(htmlspecialchars($c['mensaje'] ?? '')) . '</td>
        </tr>';
    }

    $html .= '</tbody></table>
            <div class="text-center mt-4">
                <a href="/contact.html" class="btn btn-primary">Volver al formulario</a>
            </div>
        </div>
    </body>
    </html>';

    return new Response(
        200,
        array_merge(['Content-Type' => 'text/html'], $corsHeaders),
        $html
    );
}



            // Ruta POST para enviar datos de contacto
if ($path === '/contact' && $method === 'POST') {
    $jsonFile = $baseDir . '/contact/contactos.json';

    try {
        // Obtener los datos enviados en la solicitud POST
        $data = json_decode((string)$request->getBody(), true);

        // Validación básica de los datos
        if (
            empty($data['nombre']) ||
            empty($data['correo']) ||
            empty($data['fecha_llegada']) ||
            empty($data['fecha_salida']) ||
            empty($data['tipo'])
        ) {
            return new Response(
                400,
                array_merge(['Content-Type' => 'application/json'], $corsHeaders),
                json_encode(['error' => 'Por favor complete todos los campos obligatorios.'])
            );
        }

        // Si el archivo no existe, devolvemos 404
        if (!file_exists($jsonFile)) {
            return new Response(
                404,
                array_merge(['Content-Type' => 'application/json'], $corsHeaders),
                json_encode(['error' => 'Archivo de contactos no encontrado.'])
            );
        }

        // Cargar contactos existentes y agregar el nuevo
        $contactos = json_decode(file_get_contents($jsonFile), true);
        $data['id'] = uniqid();
        $contactos[] = $data;

        // Guardar en el archivo JSON
        file_put_contents($jsonFile, json_encode($contactos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return new Response(
            200,
            array_merge(['Content-Type' => 'application/json'], $corsHeaders),
            json_encode(['message' => 'Solicitud enviada correctamente.'])
        );

    } catch (Exception $e) {
        return new Response(
            500,
            array_merge(['Content-Type' => 'application/json'], $corsHeaders),
            json_encode(['error' => 'Error interno del servidor: ' . $e->getMessage()])
        );
    }
}

// Si llega aquí y la ruta es /contacto pero no es POST, lanzar 404
if ($path === '/contacto') {
    return new Response(
        404,
        array_merge(['Content-Type' => 'application/json'], $corsHeaders),
        json_encode(['error' => 'Ruta o método no válido para /contacto.'])
    );
}

            

    
    // Servir archivos estáticos y páginas HTML
    return serveFile($path, $baseDir);
});

// Iniciar servidor
$socket = new SocketServer('0.0.0.0:8081');
$server->listen($socket);

echo "Servidor ReactPHP funcionando en http://localhost:8081\n";

$loop->run();