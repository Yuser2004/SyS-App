<?php
include __DIR__ . '/models/conexion.php';

$id_cliente = $_POST['id_cliente'] ?? null;
$id_asesor = $_POST['id_asesor'] ?? null;
$id_vehiculo = $_POST['id_vehiculo'] ?? null;
$concepto_servicio = $_POST['concepto_servicio'] ?? null;
$valor_servicio = $_POST['valor_servicio'] ?? null;
$estado = $_POST['estado'] ?? null;
$descripcion_servicio = $_POST['descripcion_servicio'] ?? null;
$metodo_pago = $_POST['metodo_pago'] ?? null;

// Convertir valores vacíos a null si aplica
$id_asesor = $id_asesor === '' ? null : $id_asesor;

$sql = "INSERT INTO recibos 
(id_cliente, id_asesor, id_vehiculo, concepto_servicio, valor_servicio, estado, descripcion_servicio, metodo_pago)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        "iiisssss",
        $id_cliente,
        $id_asesor,
        $id_vehiculo,
        $concepto_servicio,
        $valor_servicio,
        $estado,
        $descripcion_servicio,
        $metodo_pago
    );
    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error al guardar: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error en la preparación: " . $conn->error;
}

$conn->close();
