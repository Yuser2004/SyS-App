<?php
include __DIR__ . '/models/conexion.php';

$id = $_POST['id'] ?? null;
$id_cliente = $_POST['id_cliente'] ?? null;
$id_asesor = $_POST['id_asesor'] ?? null;
$id_vehiculo = $_POST['id_vehiculo'] ?? null;
$concepto_servicio = $_POST['concepto_servicio'] ?? null;
$valor_servicio = $_POST['valor_servicio'] ?? null;
$estado = $_POST['estado'] ?? null;
$metodo_pago = $_POST['metodo_pago'] ?? null;
$descripcion_servicio = $_POST['descripcion_servicio'] ?? null;

// Validaciones básicas
$errores = [];

if (!$id) $errores[] = "ID del recibo no válido.";
if (!$id_cliente) $errores[] = "Debe seleccionar un cliente.";
if (!$id_vehiculo) $errores[] = "Debe seleccionar un vehículo.";
if (!$concepto_servicio) $errores[] = "El concepto no puede estar vacío.";
if ($valor_servicio <= 0) $errores[] = "El valor debe ser mayor a cero.";
if (!$estado) $errores[] = "Debe seleccionar un estado.";
if (!$metodo_pago) $errores[] = "Debe seleccionar un método de pago.";

if ($errores) {
    echo implode("\n", $errores);
    exit;
}

$id_asesor = $id_asesor === '' ? null : $id_asesor;

// Actualización
$sql = "UPDATE recibos SET 
    id_cliente = ?, 
    id_asesor = ?, 
    id_vehiculo = ?, 
    concepto_servicio = ?, 
    valor_servicio = ?, 
    estado = ?, 
    metodo_pago = ?, 
    descripcion_servicio = ?
WHERE id = ?";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        "iiisssssi",
        $id_cliente,
        $id_asesor,
        $id_vehiculo,
        $concepto_servicio,
        $valor_servicio,
        $estado,
        $metodo_pago,
        $descripcion_servicio,
        $id
    );

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error al actualizar: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error en la preparación: " . $conn->error;
}

$conn->close();
