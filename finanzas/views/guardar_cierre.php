<?php
// finanzas/views/guardar_cierre.php
include __DIR__ . '/../models/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibir todos los datos del formulario de cierre
    $id_sede = intval($_POST['id_sede']);
    $fecha_cierre = $_POST['fecha_cierre'];
    $saldo_apertura = floatval($_POST['saldo_apertura']);
    $total_ingresos = floatval($_POST['total_ingresos']);
    $total_egresos = floatval($_POST['total_egresos']);
    $total_gastos = floatval($_POST['total_gastos']);
    $balance_dia = floatval($_POST['balance_dia']);
    $saldo_final = floatval($_POST['saldo_final']);
    $conteo_efectivo = floatval($_POST['conteo_efectivo']);
    $notas = $_POST['notas'];
    $realizado_por = "Usuario Admin"; // Podrías cambiar esto por un usuario real en el futuro
    
    // Calcular la diferencia
    $diferencia = $conteo_efectivo - ($saldo_apertura + ($ingresos_por_metodo['efectivo'] ?? 0) - ($egresos_por_metodo['efectivo'] ?? 0) - ($gastos_por_metodo['efectivo'] ?? 0));

    $stmt = $conn->prepare("
        INSERT INTO cierres_caja (id_sede, fecha, saldo_apertura, total_ingresos, total_egresos, total_gastos, balance_dia, saldo_final, conteo_efectivo_cierre, diferencia, notas, realizado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isddddddddss", $id_sede, $fecha_cierre, $saldo_apertura, $total_ingresos, $total_egresos, $total_gastos, $balance_dia, $saldo_final, $conteo_efectivo, $diferencia, $notas, $realizado_por);
    $stmt->execute();
    $stmt->close();
}

// Redirigir de vuelta a la página de caja diaria
header("Location: ../../../?vista=finanzas/views/caja_diaria.php&fecha=$fecha_cierre&id_sede=$id_sede");
exit();
?>