<?php
include 'models/conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: views/index.php");
        exit;
    } else {
        echo "Error al eliminar cliente: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "ID de cliente no especificado.";
}

$conn->close();
?>
