<?php
include __DIR__ . '/models/conexion.php';

$id = intval($_GET['id']);

$sql = "DELETE FROM vehiculo WHERE id_vehiculo = $id";

if ($conn->query($sql)) {
    echo "ok";
} else {
    echo "Error al eliminar: " . $conn->error;
}

$conn->close();
?>
