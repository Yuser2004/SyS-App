<?php
include __DIR__ . '/models/conexion.php';

$id = intval($_GET['id']);

// Verificar si el vehículo tiene recibos asociados
$sqlCheck = "SELECT COUNT(*) AS total FROM recibos WHERE id_vehiculo = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $id);
$stmtCheck->execute();
$resultado = $stmtCheck->get_result()->fetch_assoc();
$stmtCheck->close();

if ($resultado['total'] > 0) {
    echo "no-se-puede-eliminar";
    $conn->close();
    exit;
}

// Si no tiene recibos, se elimina normalmente
$sql = "DELETE FROM vehiculo WHERE id_vehiculo = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "ok";
} else {
    echo "error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
