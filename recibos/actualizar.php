<?php
include __DIR__ . '/models/conexion.php';

$id = $_POST['id'] ?? null;
$estado = $_POST['estado'] ?? null;
$descripcion_servicio = $_POST['descripcion_servicio'] ?? null;

// Validación mínima
if (!$id || !$estado) {
    echo "ID y estado son obligatorios.";
    exit;
}

$sql = "UPDATE recibos SET estado = ?, descripcion_servicio = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ssi", $estado, $descripcion_servicio, $id);

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error en la preparación: " . $conn->error;
}

$conn->close();
