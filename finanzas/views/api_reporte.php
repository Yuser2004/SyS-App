<?php
// finanzas/views/api_reporte.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

// Obtener filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$sede_id = $_GET['sede_id'] ?? '';

// --- 1. CÁLCULO DE TOTALES ---
// Ingresos:
$sql_total_ingresos = "SELECT SUM(r.valor_servicio) AS total 
                       FROM recibos r
                       LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
                       WHERE r.estado = 'completado' AND r.fecha_tramite BETWEEN ? AND ?";
if (!empty($sede_id)) $sql_total_ingresos .= " AND a.id_sede = ?";
$stmt_ingresos = $conn->prepare($sql_total_ingresos);
$types = !empty($sede_id) ? "ssi" : "ss";
$params = !empty($sede_id) ? [$fecha_desde, $fecha_hasta, $sede_id] : [$fecha_desde, $fecha_hasta];
$stmt_ingresos->bind_param($types, ...$params);
$stmt_ingresos->execute();
$total_ingresos = $stmt_ingresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_ingresos->close();

// Egresos:
$sql_total_egresos = "SELECT SUM(e.monto) AS total 
                      FROM egresos e 
                      JOIN recibos r ON e.recibo_id = r.id
                      LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
                      WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio'";
if (!empty($sede_id)) $sql_total_egresos .= " AND a.id_sede = ?";
$stmt_egresos = $conn->prepare($sql_total_egresos);
// Los parámetros son los mismos que en ingresos
$stmt_egresos->bind_param($types, ...$params);
$stmt_egresos->execute();
$total_egresos = $stmt_egresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_egresos->close();

// La lógica de gastos fijos se elimina. Utilidad ahora es más simple.
$utilidad_final = $total_ingresos - $total_egresos;

// --- 2. DESGLOSE POR MÉTODO DE PAGO ---
$metodos_pago = ['efectivo', 'transferencia', 'tarjeta', 'otro'];
$desglose_pagos = [];
foreach ($metodos_pago as $metodo) {
    // Ingresos por método
    $sql_ing_m = "SELECT SUM(r.valor_servicio) AS total FROM recibos r LEFT JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND r.metodo_pago = ? AND r.fecha_tramite BETWEEN ? AND ?";
    if (!empty($sede_id)) $sql_ing_m .= " AND a.id_sede = ?";
    $stmt = $conn->prepare($sql_ing_m);
    $types_m = !empty($sede_id) ? "sssi" : "sss";
    $params_m = !empty($sede_id) ? [$metodo, $fecha_desde, $fecha_hasta, $sede_id] : [$metodo, $fecha_desde, $fecha_hasta];
    $stmt->bind_param($types_m, ...$params_m);
    $stmt->execute();
    $ingresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Egresos por método
    $sql_egr_m = "SELECT SUM(e.monto) AS total FROM egresos e JOIN recibos r ON e.recibo_id = r.id LEFT JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND e.forma_pago = ? AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio'";

    if (!empty($sede_id)) $sql_egr_m .= " AND a.id_sede = ?";
    $stmt = $conn->prepare($sql_egr_m);
    // Los parámetros son los mismos
    $stmt->bind_param($types_m, ...$params_m);
    $stmt->execute();
    $egresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $desglose_pagos[$metodo] = [
        'ingresos' => $ingresos_metodo,
        'salidas' => $egresos_metodo, // Salidas ahora es solo egresos
        'balance' => $ingresos_metodo - $egresos_metodo
    ];
    $stmt->close();
}

// --- 3. DESGLOSE DIARIO (simplificado sin gastos) ---
$sql_detalle_diario_base = "
    SELECT fecha, SUM(ingreso) AS ingresos_diarios, SUM(egreso) AS egresos_diarios
    FROM (
        SELECT r.fecha_tramite AS fecha, r.valor_servicio AS ingreso, 0 AS egreso FROM recibos r LEFT JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND r.fecha_tramite BETWEEN ? AND ? %s
        UNION ALL
        SELECT e.fecha, 0 AS ingreso, e.monto AS egreso FROM egresos e JOIN recibos r ON e.recibo_id = r.id LEFT JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' %s

    ) AS transacciones
    GROUP BY fecha ORDER BY fecha DESC
";
$params_detalle = [$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta];
$types_detalle = "ssss";
$where_sede_clause = '';

if (!empty($sede_id)) {
    $where_sede_clause = " AND a.id_sede = ? ";
    array_push($params_detalle, $sede_id, $sede_id);
    $types_detalle .= "ii";
}

$sql_detalle_diario = sprintf($sql_detalle_diario_base, $where_sede_clause, $where_sede_clause);
$stmt_detalle = $conn->prepare($sql_detalle_diario);
$stmt_detalle->bind_param($types_detalle, ...$params_detalle);
$stmt_detalle->execute();
$resultado_detalle = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalle->close();

// --- 4. PREPARAR RESPUESTA JSON (simplificada) ---
$respuesta = [
    'resumen' => [
        'total_ingresos' => $total_ingresos,
        'total_egresos' => $total_egresos,
        'total_gastos' => 0, // Se envía 0 porque ya no se calcula
        'utilidad_final' => $utilidad_final,
    ],
    'desglose_pagos' => $desglose_pagos,
    'detalle' => $resultado_detalle
];

echo json_encode($respuesta);
exit();
?>