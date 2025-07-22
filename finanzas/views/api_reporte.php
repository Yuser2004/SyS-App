<?php
// finanzas/views/api_reporte.php

// Establece la cabecera para indicar que la respuesta es JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos
include __DIR__ . '/../models/conexion.php';

// Obtener fechas (igual que antes)
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');

// --- 1. CÁLCULO DE TOTALES ---
$sql_total_ingresos = "SELECT SUM(valor_servicio) AS total FROM recibos WHERE estado = 'completado' AND fecha_tramite BETWEEN ? AND ?";
$stmt_ingresos = $conn->prepare($sql_total_ingresos);
$stmt_ingresos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_ingresos->execute();
$total_ingresos = $stmt_ingresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_ingresos->close();

$sql_total_egresos = "SELECT SUM(monto) AS total FROM egresos WHERE fecha BETWEEN ? AND ?";
$stmt_egresos = $conn->prepare($sql_total_egresos);
$stmt_egresos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_egresos->execute();
$total_egresos = $stmt_egresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_egresos->close();

$sql_total_gastos = "SELECT SUM(monto) AS total FROM gastos WHERE fecha BETWEEN ? AND ?";
$stmt_gastos = $conn->prepare($sql_total_gastos);
$stmt_gastos->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt_gastos->execute();
$total_gastos = $stmt_gastos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_gastos->close();

$ganancia_neta_total = $total_ingresos - $total_egresos;
$utilidad_final = $ganancia_neta_total - $total_gastos;

// --- 2. CÁLCULO DEL DESGLOSE DIARIO ---
$sql_detalle_diario = "
    SELECT fecha, SUM(ingreso) AS ingresos_diarios, SUM(egreso) AS egresos_diarios
    FROM (
        SELECT fecha_tramite AS fecha, valor_servicio AS ingreso, 0 AS egreso FROM recibos WHERE estado = 'completado' AND fecha_tramite BETWEEN ? AND ?
        UNION ALL
        SELECT fecha, 0 AS ingreso, monto AS egreso FROM egresos WHERE fecha BETWEEN ? AND ?
    ) AS transacciones
    GROUP BY fecha
    ORDER BY fecha DESC
";
$stmt_detalle = $conn->prepare($sql_detalle_diario);
$stmt_detalle->bind_param("ssss", $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta);
$stmt_detalle->execute();
$resultado_detalle = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalle->close();

// --- 3. PREPARAR LA RESPUESTA JSON ---
$respuesta = [
    'resumen' => [
        'total_ingresos' => $total_ingresos,
        'total_egresos' => $total_egresos,
        'ganancia_neta' => $ganancia_neta_total,
        'total_gastos' => $total_gastos,
        'utilidad_final' => $utilidad_final,
    ],
    'detalle' => $resultado_detalle
];

// 4. IMPRIMIR LA RESPUESTA EN FORMATO JSON Y SALIR
echo json_encode($respuesta);
exit();
?>