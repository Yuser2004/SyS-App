<?php
include __DIR__ . '/models/conexion.php';

$id = intval($_POST['id'] ?? 0);

if ($id <= 0) {
    echo "ID inválido.";
    exit;
}

$sql = "DELETE FROM recibos WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error al eliminar: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Error en la preparación: " . $conn->error;
}

$conn->close();
