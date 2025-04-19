<?php
require 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar y capturar los datos del formulario
    $tipo = $_POST['tipo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $capacidad = $_POST['capacidad'] ?? 0;
    $precio = $_POST['precio'] ?? 0.0;
    $imagen = $_POST['imagen'] ?? '';
    $disponible = isset($_POST['disponible']) ? 1 : 0;

    // Validaciones básicas
    if ($tipo && $nombre && $capacidad > 0 && $precio >= 0 && $imagen) {
        try {
            $stmt = $conn->prepare("INSERT INTO hospedajes (tipo, nombre, capacidad, precio_noche, imagen_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$tipo, $nombre, $capacidad, $precio, $imagen]);

            // Redireccionar o dar respuesta
            echo "Alojamiento guardado exitosamente.";
        } catch (PDOException $e) {
            echo "Error al guardar: " . $e->getMessage();
        }
    } else {
        echo "Todos los campos son obligatorios.";
    }
} else {
    echo "Método no permitido.";
}
?>
