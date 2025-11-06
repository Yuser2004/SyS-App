<?php
// finanzas/views/guardar_cierre.php
include __DIR__ . '/../models/conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sede = intval($_POST['id_sede']);
    $fecha_cierre = $_POST['fecha_cierre'];

    // --- Apertura (estos se guardan para referencia) ---
    $saldo_apertura_efectivo = floatval($_POST['saldo_apertura_efectivo']);
    $saldo_apertura_transferencia = floatval($_POST['saldo_apertura_transferencia']);
    $saldo_apertura = $saldo_apertura_efectivo + $saldo_apertura_transferencia;

    // --- Movimientos del día ---
    $total_ingresos = floatval($_POST['total_ingresos']);
    $total_egresos = floatval($_POST['total_egresos']);
    $balance_dia    = floatval($_POST['balance_dia']);

    // --- Saldo esperado por sistema (Apertura + Balance) ---
    $saldo_final = floatval($_POST['saldo_final']); // Viene del formulario (calculado en JS)

    // --- Conteo real ingresado ---
    $conteo_efectivo      = floatval($_POST['conteo_efectivo']);
    $conteo_transferencia = floatval($_POST['conteo_transferencia']);
    $conteo_total = $conteo_efectivo + $conteo_transferencia;

    // --- Diferencia ---
    $diferencia = $conteo_total - $saldo_final;

    $notas = $_POST['notas'] ?? '';

    // --- INSERT (CORREGIDO) ---
    // Se usan los nombres de columna correctos de tu tabla
    // (conteo_efectivo_cierre, conteo_transferencia_cierre)
    $stmt = $conn->prepare("
        INSERT INTO cierres_caja 
        (id_sede, fecha, 
         saldo_apertura, total_ingresos, total_egresos, balance_dia, saldo_final, 
         diferencia, notas,
         conteo_efectivo_cierre, conteo_transferencia_cierre)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            saldo_apertura = VALUES(saldo_apertura),
            total_ingresos = VALUES(total_ingresos),
            total_egresos = VALUES(total_egresos),
            balance_dia = VALUES(balance_dia),
            saldo_final = VALUES(saldo_final),
            diferencia = VALUES(diferencia),
            notas = VALUES(notas),
            conteo_efectivo_cierre = VALUES(conteo_efectivo_cierre),
            conteo_transferencia_cierre = VALUES(conteo_transferencia_cierre)
    ");

    // BIND_PARAM (CORREGIDO)
    // Se ajusta el número de variables y los tipos (11 variables)
    // (Tu base de datos no tiene columnas para guardar el desglose de apertura)
    $stmt->bind_param("isdddddsdsd", 
        $id_sede, 
        $fecha_cierre, 
        $saldo_apertura,
        $total_ingresos, 
        $total_egresos, 
        $balance_dia, 
        $saldo_final,
        $diferencia, 
        $notas,
        $conteo_efectivo,       // Se guarda en 'conteo_efectivo_cierre'
        $conteo_transferencia   // Se guarda en 'conteo_transferencia_cierre'
    );

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error al guardar el cierre: " . $stmt->error;
    }
    $stmt->close();
    exit();
} else {
    header("Location: ../../../");
    exit();
}
?>