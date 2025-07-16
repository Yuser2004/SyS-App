<?php
include 'models/conexion.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Validar si el cliente tiene recibos asociados a sus vehículos
    $sqlCheck = "
        SELECT COUNT(*) AS total
        FROM recibos r
        INNER JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
        WHERE v.id_cliente = ?
    ";

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

    // Si no hay recibos asociados, proceder a eliminar
    $sql = "DELETE FROM clientes WHERE id_cliente = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "error: ID no especificado";
}

$conn->close();
?>
            