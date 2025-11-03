

<?php
include __DIR__ . '/models/conexion.php';



ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$id_cliente = $_POST['id_cliente'] ?? null;
$id_asesor = $_POST['id_asesor'] ?? null;
$id_vehiculo = $_POST['id_vehiculo'] ?? null;
$concepto_servicio = $_POST['concepto_servicio'] ?? null;
$valor_servicio = $_POST['valor_servicio'] ?? null;
$estado = $_POST['estado'] ?? null;
$descripcion_servicio = $_POST['descripcion_servicio'] ?? null;
$metodo_pago = $_POST['metodo_pago'] ?? null;
$detalle_pago = $_POST['detalle_pago'] ?? null; // <-- 1. SE RECIBE LA NUEVA VARIABLE
$fecha_tramite = date('Y-m-d'); // fecha actual en formato YYYY-MM-DD

// Convertir valores vacíos a null si es necesario
$id_asesor = $id_asesor === '' ? null : $id_asesor;
// Si el detalle_pago está vacío, guardarlo como NULL
$detalle_pago = $detalle_pago === '' ? null : $detalle_pago;

$sql = "INSERT INTO recibos 
(id_cliente, id_asesor, id_vehiculo, concepto_servicio, valor_servicio, estado, descripcion_servicio, metodo_pago, detalle_pago, fecha_tramite)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Se actualizan los tipos y se añade la nueva variable
    $stmt->bind_param(
        "iiisssssss",
        $id_cliente,
        $id_asesor,
        $id_vehiculo,
        $concepto_servicio,
        $valor_servicio,
        $estado,
        $descripcion_servicio,
        $metodo_pago,
        $detalle_pago, 
        $fecha_tramite  // la fecha acá

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
?>