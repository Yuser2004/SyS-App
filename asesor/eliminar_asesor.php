<?php
include __DIR__ . '/models/conexion.php';
require_once __DIR__ . '/../auth_check.php';
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM asesor WHERE id_asesor = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "ID no especificado.";
}

$conn->close();
