<?php
$host = 'localhost';
$usuario = 'root';
$contrasena = '';
$bd = 'seguros_db';

$conn = new mysqli($host, $usuario, $contrasena, $bd);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
?>
