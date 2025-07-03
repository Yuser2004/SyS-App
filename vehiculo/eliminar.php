<?php
include 'models/conexion.php';

$id = intval($_GET['id']);

$sql = "DELETE FROM vehiculo WHERE id_vehiculo = $id";

if ($conn->query($sql)) {
    header("Location: views/index.php"); 
} else {
    echo "Error al eliminar: " . $conn->error;
}

$conn->close();
?>
