<?php
include '../models/conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "ID inválido.";
    exit;
}

// Verificar si el recibo tiene egresos relacionados (si aplica lógica similar a tu caso de vehículos)
$verificar = $conn->query("SELECT COUNT(*) AS total FROM egresos WHERE id_recibo = $id");
$datos = $verificar->fetch_assoc();

if ($datos['total'] > 0) {
    echo "no-se-puede-eliminar"; // Manejado desde el JS
    $conn->close();
    exit;
}

// Si no hay egresos, eliminar
$eliminar = $conn->query("DELETE FROM recibos WHERE id = $id");

if ($eliminar) {
    echo "ok";
} else {
    echo "Error al eliminar: " . $conn->error;
}

$conn->close();
