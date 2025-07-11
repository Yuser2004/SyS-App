<?php
include 'models/conexion.php';

$id = intval($_POST['id']);
$nombre = trim($_POST['nombre']);
$direccion = trim($_POST['direccion']);

$errores = [];

// Validaciones básicas
if (empty($nombre) || !preg_match("/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/", $nombre)) {
    $errores[] = "El nombre solo puede contener letras, números y espacios.";
}

if (empty($direccion)) {
    $errores[] = "La dirección es obligatoria.";
}

// Verificar duplicado en la base de datos (excluyendo la sede actual)
$stmt = $conn->prepare("SELECT COUNT(*) FROM sedes WHERE nombre = ? AND id != ?");
$stmt->bind_param("si", $nombre, $id);
$stmt->execute();
$stmt->bind_result($existe);
$stmt->fetch();
$stmt->close();

if ($existe > 0) {
    $errores[] = "Ya existe otra sede con ese nombre.";
}

if (!empty($errores)) {
    echo implode("\n", $errores);
    $conn->close();
    exit;
}

// Si todo está bien, ejecutar la actualización
$sql = "UPDATE sedes SET nombre = ?, direccion = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $nombre, $direccion, $id);

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "Error al actualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
