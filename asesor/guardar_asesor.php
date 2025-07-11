<?php
include __DIR__ . '/models/conexion.php';

$id_sede = intval($_POST['id_sede']);
$nombre = trim($_POST['nombre']);
$documento = trim($_POST['documento']);

$errores = [];

if (empty($nombre)) {
    $errores[] = "El nombre es obligatorio.";
}

if (empty($documento)) {
    $errores[] = "El documento es obligatorio.";
}

if ($id_sede <= 0) {
    $errores[] = "Debe seleccionar una sede válida.";
}

if (!empty($errores)) {
    echo implode("\n", $errores);
    $conn->close();
    exit;
}

$stmt = $conn->prepare("INSERT INTO asesor (id_sede, nombre, documento) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id_sede, $nombre, $documento);

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "Error al guardar: " . $stmt->error;
}

$stmt->close();
$conn->close();
