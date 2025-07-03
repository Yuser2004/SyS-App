<?php
include 'models/conexion.php';

$id = intval($_POST['id_vehiculo']);
$placa = trim($_POST['placa']);
$id_cliente = intval($_POST['id_cliente']);

$errores = [];

// Validación PHP
if (empty($placa)) {
    $errores[] = "La placa es obligatoria.";
}
if ($id_cliente <= 0) {
    $errores[] = "Debe seleccionar un cliente válido.";
}

// Verificar errores
if (!empty($errores)) {
    foreach ($errores as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<a href='views/editar.php?id=$id'>← Volver</a>";
    $conn->close();
    exit;
}

// Actualizar vehículo
$sql = "UPDATE vehiculo SET placa='$placa', id_cliente='$id_cliente' WHERE id_vehiculo=$id";
if ($conn->query($sql)) {
    header("Location: views/index.php");
} else {
    echo "Error al actualizar: " . $conn->error;
}

$conn->close();
?>
