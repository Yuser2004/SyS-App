<?php
include 'models/conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM sedes WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "error: ID no especificado";
}

$conn->close();
?>
