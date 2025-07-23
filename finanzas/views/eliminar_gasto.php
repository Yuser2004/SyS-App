<?php
// finanzas/views/eliminar_gasto.php

include __DIR__ . '/../models/conexion.php';

// Verificamos que se reciba un ID por GET
if (isset($_GET['id'])) {
    $id_a_eliminar = intval($_GET['id']);

    if ($id_a_eliminar > 0) {
        $stmt = $conn->prepare("DELETE FROM gastos WHERE id = ?");
        $stmt->bind_param("i", $id_a_eliminar);
        
        if ($stmt->execute()) {
            echo "ok"; // Éxito
        } else {
            echo "Error al eliminar el gasto.";
        }
        $stmt->close();
    } else {
        echo "ID de gasto no válido.";
    }
} else {
    echo "No se proporcionó un ID de gasto.";
}
?>