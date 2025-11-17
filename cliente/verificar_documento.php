<?php
include 'models/conexion.php';
require_once __DIR__ . '/../auth_check.php';

$documento = $_GET['documento'];
$id_actual = isset($_GET['id']) ? $_GET['id'] : 0;

$sql = "SELECT id_cliente FROM clientes WHERE documento = '$documento' AND id_cliente != '$id_actual'";
$resultado = $conn->query($sql);

echo $resultado->num_rows > 0 ? 'existe' : 'ok';

$conn->close();
?>
