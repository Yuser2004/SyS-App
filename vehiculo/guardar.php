<?php
include 'models/conexion.php';

$placa = strtoupper(trim($_POST['placa']));
$id_cliente = intval($_POST['id_cliente']);

$errores = [];

// Validación básica
if (empty($placa)) {
    $errores[] = "La placa es obligatoria.";
}

if ($id_cliente <= 0) {
    $errores[] = "Debes seleccionar un cliente válido.";
}

// Validar si ya existe esa placa
$verificar = $conn->query("SELECT id_vehiculo FROM vehiculo WHERE placa = '$placa'");
if ($verificar->num_rows > 0) {
    $errores[] = "Ya existe un vehículo registrado con esa placa.";
}

if (!empty($errores)) {
    foreach ($errores as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<a href='views/crear.php'>← Volver al formulario</a>";
    $conn->close();
    exit;
}

// Insertar si todo está bien
$sql = "INSERT INTO vehiculo (placa, id_cliente) VALUES ('$placa', $id_cliente)";

if ($conn->query($sql)) {
    header("Location: views/index.php");
} else {
    echo "Error al guardar: " . $conn->error;
}

$conn->close();
?>
