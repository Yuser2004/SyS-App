<?php
// finanzas/views/api_reporte.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

// Obtener fechas
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');

// --- 1. CÁLCULO DE TOTALES GENERALES ---
// Ingresos: Solo de recibos completados
$sql_total_ingresos = "SELECT SUM(valor_servicio) AS total FROM recibos WHERE estado = 'completado' AND fecha_tramite BETWEEN ? AND ?";
$stmt_ingresos = $conn->prepare($sql_total_ingresos);
$stmt_ingresos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_ingresos->execute();
$total_ingresos = $stmt_ingresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_ingresos->close();

// Egresos: Solo de recibos completados
$sql_total_egresos = "SELECT SUM(e.monto) AS total FROM egresos e JOIN recibos r ON e.recibo_id = r.id WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ?";
$stmt_egresos = $conn->prepare($sql_total_egresos);
$stmt_egresos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_egresos->execute();
$total_egresos = $stmt_egresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_egresos->close();

// Gastos: Todos los gastos en el rango
$sql_total_gastos = "SELECT SUM(monto) AS total FROM gastos WHERE fecha BETWEEN ? AND ?";
$stmt_gastos = $conn->prepare($sql_total_gastos);
$stmt_gastos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_gastos->execute();
$total_gastos = $stmt_gastos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_gastos->close();

$ganancia_neta_total = $total_ingresos - $total_egresos;
$utilidad_final = $ganancia_neta_total - $total_gastos;

// --- 2. CÁLCULO DE DESGLOSE POR MÉTODO DE PAGO ---
$metodos_pago = ['efectivo', 'transferencia', 'tarjeta', 'otro'];
$desglose_pagos = [];
foreach ($metodos_pago as $metodo) {
    // Ingresos por método (solo de recibos completados)
    $sql_ing_m = "SELECT SUM(valor_servicio) AS total FROM recibos WHERE estado = 'completado' AND metodo_pago = ? AND fecha_tramite BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_ing_m);
    $stmt->bind_param("sss", $metodo, $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $ingresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Egresos por método (solo de recibos completados)
    $sql_egr_m = "SELECT SUM(e.monto) AS total FROM egresos e JOIN recibos r ON e.recibo_id = r.id WHERE r.estado = 'completado' AND e.forma_pago = ? AND e.fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_egr_m);
    $stmt->bind_param("sss", $metodo, $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $egresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Gastos por método
    $sql_gas_m = "SELECT SUM(monto) AS total FROM gastos WHERE metodo_pago = ? AND fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_gas_m);
    $stmt->bind_param("sss", $metodo, $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $gastos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    $desglose_pagos[$metodo] = [
        'ingresos' => $ingresos_metodo,
        'salidas' => $egresos_metodo + $gastos_metodo,
        'balance' => $ingresos_metodo - ($egresos_metodo + $gastos_metodo)
    ];
    $stmt->close();
}

// --- 3. CÁLCULO DEL DESGLOSE DIARIO ---
$sql_detalle_diario = "
    SELECT fecha, SUM(ingreso) AS ingresos_diarios, SUM(egreso) AS egresos_diarios
    FROM (
        SELECT fecha_tramite AS fecha, valor_servicio AS ingreso, 0 AS egreso FROM recibos WHERE estado = 'completado' AND fecha_tramite BETWEEN ? AND ?
        UNION ALL
        SELECT e.fecha, 0 AS ingreso, e.monto AS egreso FROM egresos e JOIN recibos r ON e.recibo_id = r.id WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ?
        UNION ALL
        SELECT fecha, 0 AS ingreso, monto AS egreso FROM gastos WHERE fecha BETWEEN ? AND ?
    ) AS transacciones
    GROUP BY fecha
    ORDER BY fecha DESC
";
$stmt_detalle = $conn->prepare($sql_detalle_diario);
$stmt_detalle->bind_param("ssssss", $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta);
$stmt_detalle->execute();
$resultado_detalle = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalle->close();

// --- 4. PREPARAR RESPUESTA JSON ---
$respuesta = [
    'resumen' => [
        'total_ingresos' => $total_ingresos,
        'total_egresos' => $total_egresos,
        'total_gastos' => $total_gastos,
        'utilidad_final' => $utilidad_final,
    ],
    'desglose_pagos' => $desglose_pagos,
    'detalle' => $resultado_detalle
];

echo json_encode($respuesta);
exit();
?>