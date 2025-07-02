<?php
include 'models/conexion.php';

$nombre = $_POST['nombre_completo'];
$documento = $_POST['documento'];
$telefono = $_POST['telefono'];
$ciudad = $_POST['ciudad'];
$direccion = $_POST['direccion'];

$sql = "INSERT INTO clientes (nombre_completo, documento, telefono, ciudad, direccion)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $nombre, $documento, $telefono, $ciudad, $direccion);

if ($stmt->execute()) {
    header("Location: views/index.php");
    exit;
} else {
    echo "Error al guardar cliente: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
