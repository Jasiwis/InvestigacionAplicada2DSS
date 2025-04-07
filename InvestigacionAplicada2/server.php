<?php

require __DIR__ . '/reactphp-sitio/vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;

// Crear el servidor HTTP
$server = new HttpServer(function (ServerRequestInterface $request) {
    $path = $request->getUri()->getPath();

    // 📦 Servir archivos estáticos como imágenes
    if (preg_match('/\.(png|jpg|jpeg|gif|svg)$/', $path)) {
        $file = __DIR__ . $path;
    
        echo "Buscando archivo: $file\n"; // 👈 AGREGAR ESTO
    
        if (file_exists($file)) {
            $mimeType = mime_content_type($file);
            return new Response(200, ['Content-Type' => $mimeType], file_get_contents($file));
        } else {
            return new Response(404, ['Content-Type' => 'text/plain'], 'Archivo no encontrado');
        }
    }
    
    

    // 🌐 Rutas del sitio
    switch ($path) {
        case '/':
            return new Response(
                200,
                ['Content-Type' => 'text/html'],
                file_get_contents('index.html')
            );
        case '/contact':
            return new Response(
                200,
                ['Content-Type' => 'text/html'],
                file_get_contents('contact.html')
            );
        case '/data':
            $data = json_decode(file_get_contents('alojamientos.json'), true);
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($data)
            );
        case '/style.css':
            return new Response(
                200,
                ['Content-Type' => 'text/css'],
                file_get_contents('style.css')
            );
        default:
            return new Response(
                404,
                ['Content-Type' => 'text/plain'],
                'Página no encontrada'
            );
    }
});

$socket = new SocketServer('0.0.0.0:8081');
$server->listen($socket);
echo "Servidor en ejecución en http://localhost:8081\n";
