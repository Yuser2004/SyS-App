<?php
// caja/views/guardar_cierre.php
include __DIR__ . '/../models/conexion.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Recibir los datos principales del formulario
    $id_sede = intval($_POST['id_sede']);
    $fecha_cierre = $_POST['fecha_cierre'];
    $saldo_apertura = floatval($_POST['saldo_apertura']);
    $total_ingresos = floatval($_POST['total_ingresos']);
    $total_egresos = floatval($_POST['total_egresos']);
    $balance_dia = floatval($_POST['balance_dia']);
    $saldo_final_esperado = floatval($_POST['saldo_final']); // Este es el valor TOTAL que el sistema calculó
    $notas = $_POST['notas'];

    // Recibimos el NUEVO campo con el conteo total
    $conteo_final_total = floatval($_POST['conteo_final_total']);

    // ==========================================================
    // CÁLCULO FINAL DE LA DIFERENCIA (TOTAL vs TOTAL)
    // ==========================================================
    // Comparamos el valor total que el sistema esperaba, con el valor total que la asesora contó.
    $diferencia = $conteo_final_total - $saldo_final_esperado;

    // Preparamos la inserción a la base de datos
    $stmt = $conn->prepare("
        INSERT INTO cierres_caja 
        (id_sede, fecha, saldo_apertura, total_ingresos, total_egresos, balance_dia, saldo_final, conteo_efectivo_cierre, diferencia, notas)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    // El 'conteo_efectivo_cierre' ahora guarda el conteo total
    $stmt->bind_param("isddddddds", 
        $id_sede, 
        $fecha_cierre, 
        $saldo_apertura, 
        $total_ingresos, 
        $total_egresos, 
        $balance_dia, 
        $saldo_final_esperado, // Saldo que el sistema esperaba
        $conteo_final_total,    // Conteo que el usuario ingresó
        $diferencia, 
        $notas
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