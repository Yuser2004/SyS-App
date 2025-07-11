<?php
include __DIR__ . '/models/conexion.php';

$placa = trim($_GET['placa'] ?? '');
$id = intval($_GET['id'] ?? 0); // ID del vehículo actual

if ($placa === '') {
    echo "La placa no puede estar vacía.";
    exit;
}
file_put_contents("log_placa.txt", "Placa: $placa | ID: $id\n", FILE_APPEND);
$stmt = $conn->prepare("SELECT COUNT(*) FROM vehiculo WHERE placa = ? AND id_vehiculo != ?");
$stmt->bind_param("si", $placa, $id);
$stmt->execute();
$stmt->bind_result($total);
$stmt->fetch();
$stmt->close();

if ($total > 0) {
    echo "Ya existe un vehículo con esa placa.";
} else {
    echo "ok";
}
