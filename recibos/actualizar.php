<?php
include '../models/conexion.php';

$id = intval($_POST['id']);
$id_cliente = intval($_POST['id_cliente']);
$id_vehiculo = intval($_POST['id_vehiculo']);
$id_asesor = intval($_POST['id_asesor']);
$valor = floatval($_POST['valor_servicio']);
$estado = trim($_POST['estado']);
$metodo = trim($_POST['metodo_pago']);
$concepto = trim($_POST['concepto']);

$errores = [];

if ($id_cliente <= 0) $errores[] = "Cliente no válido.";
if ($id_vehiculo <= 0) $errores[] = "Vehículo no válido.";
if ($id_asesor <= 0) $errores[] = "Asesor no válido.";
if ($valor <= 0) $errores[] = "Valor debe ser mayor a 0.";
if ($estado !== 'pendiente' && $estado !== 'pagado') $errores[] = "Estado inválido.";
if ($metodo === '') $errores[] = "Método de pago requerido.";
if (empty($concepto)) $errores[] = "Concepto obligatorio.";

if (!empty($errores)) {
    echo implode("\n", $errores);
    $conn->close();
    exit;
}

$sql = "
    UPDATE recibos SET 
        id_cliente = ?, 
        id_vehiculo = ?, 
        id_asesor = ?, 
        valor_servicio = ?, 
        estado = ?, 
        metodo_pago = ?, 
        concepto_servicio = ?
    WHERE id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiissssi", $id_cliente, $id_vehiculo, $id_asesor, $valor, $estado, $metodo, $concepto, $id);

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "Error al actualizar: " . $stmt->error;
}

$stmt->close();
$conn->close();
