<?php
$host = 'localhost';
$usuario = 'root';
$contrasena = ''; // Cambia esto si tu MySQL tiene contraseña
$basedatos = 'alojamientos';

try {
    $conn = new PDO("mysql:host=$host;dbname=$basedatos;charset=utf8", $usuario, $contrasena);
    // Establecer modo de errores
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>
