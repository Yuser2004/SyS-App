<?php
include __DIR__ . '/models/conexion.php';

$nombre = trim($_POST['nombre'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

if ($nombre === '' || $direccion === '') {
    echo "Todos los campos son obligatorios";
    exit;
}

// Validar que no exista otra sede con el mismo nombre
$stmt = $conn->prepare("SELECT COUNT(*) FROM sedes WHERE nombre = ?");
$stmt->bind_param("s", $nombre);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

if ($existe > 0) {
    echo "Ya existe una sede con ese nombre";
    exit;
}

// Si no existe, insertar la nueva sede
$stmt = $conn->prepare("INSERT INTO sedes (nombre, direccion) VALUES (?, ?)");
$stmt->bind_param("ss", $nombre, $direccion);

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "Error al guardar: " . $stmt->error;
}

$stmt->close();
$conn->close();
