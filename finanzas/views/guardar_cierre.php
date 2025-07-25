<?php
// caja/views/guardar_cierre.php
include __DIR__ . '/../models/conexion.php';

// Verificamos que sea una petición POST
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

    // Recibir los totales de efectivo para el cálculo
    $ingresos_efectivo = floatval($_POST['ingresos_efectivo']);
    $egresos_efectivo = floatval($_POST['egresos_efectivo']);
    $gastos_efectivo = floatval($_POST['gastos_efectivo']);
    
    // --- CÁLCULO CORRECTO DE LA DIFERENCIA DE CAJA ---
    // El saldo de apertura se asume que es principalmente efectivo
    $efectivo_esperado = $saldo_apertura + $ingresos_efectivo - $egresos_efectivo - $gastos_efectivo;
    $diferencia = $conteo_efectivo - $efectivo_esperado;

    // Preparamos la inserción a la base de datos
    $stmt = $conn->prepare("
        INSERT INTO cierres_caja 
        (id_sede, fecha, saldo_apertura, total_ingresos, total_egresos, total_gastos, balance_dia, saldo_final, conteo_efectivo_cierre, diferencia, notas, realizado_por)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isddddddddss", $id_sede, $fecha_cierre, $saldo_apertura, $total_ingresos, $total_egresos, $total_gastos, $balance_dia, $saldo_final, $conteo_efectivo, $diferencia, $notas, $realizado_por);
    
    if ($stmt->execute()) {
        echo "ok"; // Éxito: respondemos a JavaScript
    } else {
        echo "Error al guardar el cierre: " . $stmt->error;
    }
    $stmt->close();
    exit();

} else {
    // Si se intenta acceder al archivo directamente, redirigir por seguridad
    header("Location: ../../../");
    exit();
}
?>