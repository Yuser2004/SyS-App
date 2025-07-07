<?php
include __DIR__ . '/models/conexion.php';

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
    echo $errores[0]; // solo devuelve el primero (puedes unir todos si prefieres)
    $conn->close();
    exit;
}

// Insertar si todo está bien
$sql = "INSERT INTO vehiculo (placa, id_cliente) VALUES ('$placa', $id_cliente)";
if ($conn->query($sql)) {
    echo "ok";
} else {
    echo "Error al guardar: " . $conn->error;
}
$conn->close();
