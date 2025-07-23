<?php
// finanzas/views/api_reporte.php

// Establece la cabecera para indicar que la respuesta es JSON
header('Content-Type: application/json');

// Incluir la conexión a la base de datos
include __DIR__ . '/../models/conexion.php';

// Obtener fechas
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');

// --- 1. CÁLCULO DE TOTALES GENERALES ---
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


// =================================================================
// --- INSERCIÓN: CÁLCULO DE DESGLOSE POR MÉTODO DE PAGO ---
// =================================================================
$metodos_pago = ['efectivo', 'transferencia', 'tarjeta', 'otro'];
$desglose_pagos = [];

foreach ($metodos_pago as $metodo) {
    // Ingresos por método (de la tabla 'recibos')
    $sql_ingresos_m = "SELECT SUM(valor_servicio) AS total FROM recibos WHERE estado = 'completado' AND metodo_pago = ? AND fecha_tramite BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_ingresos_m);
    $stmt->bind_param("sss", $metodo, $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $ingresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Egresos por método (de la tabla 'egresos')
    $sql_egresos_m = "SELECT SUM(monto) AS total FROM egresos WHERE forma_pago = ? AND fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_egresos_m);
    $stmt->bind_param("sss", $metodo, $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $egresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Gastos por método (de la tabla 'gastos')
    $sql_gastos_m = "SELECT SUM(monto) AS total FROM gastos WHERE metodo_pago = ? AND fecha BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql_gastos_m);
    $stmt->bind_param("sss", $metodo, $fecha_desde, $fecha_hasta);
    $stmt->execute();
    $gastos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Guardamos el balance para este método
    $desglose_pagos[$metodo] = [
        'ingresos' => $ingresos_metodo,
        'salidas' => $egresos_metodo + $gastos_metodo, // Egresos + Gastos = Salidas Totales
        'balance' => $ingresos_metodo - ($egresos_metodo + $gastos_metodo)
    ];
    $stmt->close();
}


// --- CÁLCULO DEL DESGLOSE DIARIO (sin cambios) ---
$sql_detalle_diario = "
    SELECT 
        fecha, 
        SUM(ingreso) AS ingresos_diarios, 
        SUM(egreso) AS egresos_diarios
    FROM (
        -- Subconsulta para los ingresos (recibos completados)
        SELECT 
            fecha_tramite AS fecha, 
            valor_servicio AS ingreso, 
            0 AS egreso 
        FROM recibos 
        WHERE estado = 'completado' AND fecha_tramite BETWEEN ? AND ?

        UNION ALL

        -- Subconsulta para los egresos de servicios
        SELECT 
            fecha, 
            0 AS ingreso, 
            monto AS egreso 
        FROM egresos 
        WHERE fecha BETWEEN ? AND ?

        UNION ALL

        -- NUEVA Subconsulta para los gastos fijos/secundarios
        SELECT 
            fecha, 
            0 AS ingreso, 
            monto AS egreso 
        FROM gastos 
        WHERE fecha BETWEEN ? AND ?
    ) AS transacciones
    GROUP BY fecha
    ORDER BY fecha DESC
";
$stmt_detalle = $conn->prepare($sql_detalle_diario);
$stmt_detalle->bind_param("ssssss", $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta);
$stmt_detalle->execute();
$resultado_detalle = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalle->close();


// --- PREPARAR LA RESPUESTA JSON (actualizada) ---
$respuesta = [
    'resumen' => [
        'total_ingresos' => $total_ingresos,
        'total_egresos' => $total_egresos,
        'ganancia_neta' => $ganancia_neta_total,
        'total_gastos' => $total_gastos,
        'utilidad_final' => $utilidad_final,
    ],
    'desglose_pagos' => $desglose_pagos, // <-- NUEVA SECCIÓN EN LA RESPUESTA
    'detalle' => $resultado_detalle
];

// IMPRIMIR LA RESPUESTA EN FORMATO JSON Y SALIR
echo json_encode($respuesta);
exit();
?>