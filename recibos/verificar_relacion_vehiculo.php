<?php
include __DIR__ . '/../models/conexion.php';

$id_cliente = intval($_GET['id_cliente'] ?? 0);
$id_vehiculo = intval($_GET['id_vehiculo'] ?? 0);

$sql = "SELECT 1 FROM vehiculo WHERE id_vehiculo = ? AND id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_vehiculo, $id_cliente);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    echo "valido";
} else {
    echo "invalido";
}

$stmt->close();
$conn->close();
