<?php
// finanzas/views/guardar_cierre.php
include __DIR__ . '/../models/conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_sede = intval($_POST['id_sede']);
    $fecha_cierre = $_POST['fecha_cierre'];

    // --- Apertura ---
    $saldo_apertura_efectivo = floatval($_POST['saldo_apertura_efectivo']);
    $saldo_apertura_transferencia = floatval($_POST['saldo_apertura_transferencia']);
    $saldo_apertura = $saldo_apertura_efectivo + $saldo_apertura_transferencia;

    // --- Movimientos del día ---
    $total_ingresos = floatval($_POST['total_ingresos']);
    $total_egresos = floatval($_POST['total_egresos']);
    $balance_dia    = floatval($_POST['balance_dia']);

    // --- Saldo esperado por sistema (apertura + balance) ---
    $saldo_final = $saldo_apertura + $balance_dia;

    // --- Conteo real ingresado ---
    $saldo_cierre_efectivo      = floatval($_POST['conteo_efectivo']);
    $saldo_cierre_transferencia = floatval($_POST['conteo_transferencia']);
    $conteo_total = $saldo_cierre_efectivo + $saldo_cierre_transferencia;

    // --- Diferencia ---
    $diferencia = $conteo_total - $saldo_final;

    $notas = $_POST['notas'] ?? '';

    // --- INSERT ---
    $stmt = $conn->prepare("
        INSERT INTO cierres_caja 
        (id_sede, fecha, saldo_apertura, saldo_apertura_efectivo, saldo_apertura_transferencia,
         total_ingresos, total_egresos, balance_dia, saldo_final,
         diferencia, notas,
         saldo_cierre_efectivo, saldo_cierre_transferencia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            saldo_apertura = VALUES(saldo_apertura),
            saldo_apertura_efectivo = VALUES(saldo_apertura_efectivo),
            saldo_apertura_transferencia = VALUES(saldo_apertura_transferencia),
            total_ingresos = VALUES(total_ingresos),
            total_egresos = VALUES(total_egresos),
            balance_dia = VALUES(balance_dia),
            saldo_final = VALUES(saldo_final),
            diferencia = VALUES(diferencia),
            notas = VALUES(notas),
            saldo_cierre_efectivo = VALUES(saldo_cierre_efectivo),
            saldo_cierre_transferencia = VALUES(saldo_cierre_transferencia)
    ");

    $stmt->bind_param("isdddddddsddd", 
        $id_sede, 
        $fecha_cierre, 
        $saldo_apertura,
        $saldo_apertura_efectivo, 
        $saldo_apertura_transferencia, 
        $total_ingresos, 
        $total_egresos, 
        $balance_dia, 
        $saldo_final,     // <--- ahora se guarda el esperado real del sistema
        $diferencia, 
        $notas,
        $saldo_cierre_efectivo, 
        $saldo_cierre_transferencia
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
