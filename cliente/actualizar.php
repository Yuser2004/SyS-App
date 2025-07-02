<?php
include 'models/conexion.php';

$id = $_POST['id_cliente'];
$nombre = $_POST['nombre_completo'];
$documento = $_POST['documento'];
$telefono = $_POST['telefono'];
$ciudad = $_POST['ciudad'];
$direccion = $_POST['direccion'];

$sql = "UPDATE clientes 
        SET nombre_completo = ?, documento = ?, telefono = ?, ciudad = ?, direccion = ?
        WHERE id_cliente = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $nombre, $documento, $telefono, $ciudad, $direccion, $id);

if ($stmt->execute()) {
    header("Location: views/index.php");
    exit;
} else {
    echo "Error al actualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
