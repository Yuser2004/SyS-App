<?php
$host = 'localhost';
$usuario = 'wwsegu_yuser';
$contrasena = '24012004yuser'; 
$bd = 'wwsegu_seguros_db';

$conn = new mysqli($host, $usuario, $contrasena, $bd);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
?>