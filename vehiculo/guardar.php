<?php
include __DIR__ . '/models/conexion.php';

$placa = strtoupper(trim($_POST['placa']));
$id_cliente = intval($_POST['id_cliente']);
$id_vehiculo = intval($_POST['id_vehiculo'] ?? 0); // 0 si no está presente

$errores = [];

// Validación básica
if (empty($placa)) {
    $errores[] = "La placa es obligatoria.";
}

if ($id_cliente <= 0) {
    $errores[] = "Debes seleccionar un cliente válido.";
}

// Validar si ya existe esa placa (excluyendo el vehículo actual si es edición)
$stmt = $conn->prepare("SELECT id_vehiculo FROM vehiculo WHERE placa = ? AND id_vehiculo != ?");
$stmt->bind_param("si", $placa, $id_vehiculo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $errores[] = "Ya existe un vehículo registrado con esa placa.";
}
$stmt->close();

// Si hay errores, devolver el primero
if (!empty($errores)) {
    echo $errores[0];
    $conn->close();
    exit;
}

// Insertar o actualizar
if ($id_vehiculo > 0) {
    // Actualizar
    $stmt = $conn->prepare("UPDATE vehiculo SET placa = ?, id_cliente = ? WHERE id_vehiculo = ?");
    $stmt->bind_param("sii", $placa, $id_cliente, $id_vehiculo);
} else {
    // Insertar nuevo
    $stmt = $conn->prepare("INSERT INTO vehiculo (placa, id_cliente) VALUES (?, ?)");
    $stmt->bind_param("si", $placa, $id_cliente);
}

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "Error al guardar: " . $stmt->error;
}
$stmt->close();
$conn->close();
