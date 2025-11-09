<?php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

// Obtener filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$sede_id = $_GET['sede_id'] ?? '';

// --- 1. CÁLCULO DE TOTALES ---
$params_base = [$fecha_desde, $fecha_hasta];
$types_base = "ss";
$where_sede_recibo_asesor = ""; // Alias 'a' para asesor en recibos
$where_sede_gasto = ""; // Alias 'g' para gastos_sede

if (!empty($sede_id)) {
    $params_base[] = $sede_id;
    $types_base .= "i";
    $where_sede_recibo_asesor = " AND a.id_sede = ? ";
    $where_sede_gasto = " AND g.id_sede = ? "; // Filtro para la tabla gastos_sede
}

// Ingresos:
$sql_total_ingresos = "SELECT SUM(r.valor_servicio) AS total 
                       FROM recibos r
                       LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
                       WHERE r.estado = 'completado' AND r.fecha_tramite BETWEEN ? AND ? $where_sede_recibo_asesor";
$stmt_ingresos = $conn->prepare($sql_total_ingresos);
$stmt_ingresos->bind_param($types_base, ...$params_base);
$stmt_ingresos->execute();
$total_ingresos = $stmt_ingresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_ingresos->close();

// Egresos (asociados a servicios):
$sql_total_egresos = "SELECT SUM(e.monto) AS total 
                      FROM egresos e 
                      JOIN recibos r ON e.recibo_id = r.id
                      LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
                      WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' $where_sede_recibo_asesor";
$stmt_egresos = $conn->prepare($sql_total_egresos);
$stmt_egresos->bind_param($types_base, ...$params_base);
$stmt_egresos->execute();
$total_egresos = $stmt_egresos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_egresos->close();

// Gastos de Sede (independientes):
$params_gasto = [$fecha_desde, $fecha_hasta];
$types_gasto = "ss";
$where_sede_gasto_simple = "";
if (!empty($sede_id)) {
    $params_gasto[] = $sede_id;
    $types_gasto .= "i";
    $where_sede_gasto_simple = " AND id_sede = ? ";
}

$sql_total_gastos = "SELECT SUM(g.monto) AS total 
                     FROM gastos_sede g
                     WHERE g.fecha BETWEEN ? AND ? $where_sede_gasto_simple";
$stmt_gastos = $conn->prepare($sql_total_gastos);
$stmt_gastos->bind_param($types_gasto, ...$params_gasto);
$stmt_gastos->execute();
$total_gastos = $stmt_gastos->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_gastos->close();


// Utilidad (Actualizada)
$utilidad_final = $total_ingresos - $total_egresos - $total_gastos;

// --- 2. DESGLOSE POR MÉTODO DE PAGO ---
$metodos_pago = ['efectivo', 'transferencia', 'tarjeta', 'otro'];
$desglose_pagos = [];
foreach ($metodos_pago as $metodo) {
    
    $params_metodo = [$metodo, $fecha_desde, $fecha_hasta];
    $types_metodo = "sss";
    if (!empty($sede_id)) {
        $params_metodo[] = $sede_id;
        $types_metodo .= "i";
    }

    // Ingresos por método
    $sql_ing_m = "SELECT SUM(r.valor_servicio) AS total 
                  FROM recibos r 
                  LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                  WHERE r.estado = 'completado' AND r.metodo_pago = ? AND r.fecha_tramite BETWEEN ? AND ? $where_sede_recibo_asesor";
    $stmt = $conn->prepare($sql_ing_m);
    $stmt->bind_param($types_metodo, ...$params_metodo);
    $stmt->execute();
    $ingresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Egresos por método
    $sql_egr_m = "SELECT SUM(e.monto) AS total 
                  FROM egresos e 
                  JOIN recibos r ON e.recibo_id = r.id 
                  LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                  WHERE r.estado = 'completado' AND e.forma_pago = ? AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' $where_sede_recibo_asesor";
    $stmt = $conn->prepare($sql_egr_m);
    $stmt->bind_param($types_metodo, ...$params_metodo);
    $stmt->execute();
    $egresos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

    // Gastos por método
    $params_gasto_metodo = [$metodo, $fecha_desde, $fecha_hasta];
    $types_gasto_metodo = "sss";
    if (!empty($sede_id)) {
        $params_gasto_metodo[] = $sede_id;
        $types_gasto_metodo .= "i";
    }
    // IMPORTANTE: $where_sede_gasto_simple NO LLEVA ALIAS 'g'
    $sql_gasto_m = "SELECT SUM(g.monto) AS total 
                    FROM gastos_sede g
                    WHERE g.metodo_pago = ? AND g.fecha BETWEEN ? AND ? $where_sede_gasto_simple";
    $stmt = $conn->prepare($sql_gasto_m);
    $stmt->bind_param($types_gasto_metodo, ...$params_gasto_metodo);
    $stmt->execute();
    $gastos_metodo = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // ==========================================================
    // ¡NUEVA LÓGICA! Obtener desglose de cuentas para transferencias
    // ==========================================================
    $cuentas_detalle = [];
    if ($metodo === 'transferencia') {
        // 1. Ingresos por 'detalle_pago' (de tabla 'recibos')
        $sql_ing_detalle = "SELECT SUM(r.valor_servicio) AS total, r.detalle_pago 
                            FROM recibos r 
                            LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                            WHERE r.estado = 'completado' AND r.metodo_pago = ? AND r.fecha_tramite BETWEEN ? AND ? $where_sede_recibo_asesor 
                            AND r.detalle_pago IS NOT NULL
                            GROUP BY r.detalle_pago";
        $stmt_d = $conn->prepare($sql_ing_detalle);
        $stmt_d->bind_param($types_metodo, ...$params_metodo);
        $stmt_d->execute();
        $res_d = $stmt_d->get_result();
        while ($fila_d = $res_d->fetch_assoc()) {
            $cuentas_detalle[$fila_d['detalle_pago']]['ingresos'] = ($cuentas_detalle[$fila_d['detalle_pago']]['ingresos'] ?? 0) + $fila_d['total'];
        }
        $stmt_d->close();

        // 2. Egresos por 'detalle_pago' (de tabla 'egresos')
        $sql_egr_detalle = "SELECT SUM(e.monto) AS total, e.detalle_pago 
                            FROM egresos e 
                            JOIN recibos r ON e.recibo_id = r.id 
                            LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                            WHERE r.estado = 'completado' AND e.forma_pago = ? AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' $where_sede_recibo_asesor 
                            AND e.detalle_pago IS NOT NULL
                            GROUP BY e.detalle_pago";
        $stmt_d = $conn->prepare($sql_egr_detalle);
        $stmt_d->bind_param($types_metodo, ...$params_metodo);
        $stmt_d->execute();
        $res_d = $stmt_d->get_result();
        while ($fila_d = $res_d->fetch_assoc()) {
            $cuentas_detalle[$fila_d['detalle_pago']]['salidas'] = ($cuentas_detalle[$fila_d['detalle_pago']]['salidas'] ?? 0) + $fila_d['total'];
        }
        $stmt_d->close();

        // 3. Gastos por 'detalle_pago' (de tabla 'gastos_sede')
        $sql_gasto_detalle = "SELECT SUM(g.monto) AS total, g.detalle_pago 
                              FROM gastos_sede g
                              WHERE g.metodo_pago = ? AND g.fecha BETWEEN ? AND ? $where_sede_gasto_simple 
                              AND g.detalle_pago IS NOT NULL
                              GROUP BY g.detalle_pago";
        $stmt_d = $conn->prepare($sql_gasto_detalle);
        $stmt_d->bind_param($types_gasto_metodo, ...$params_gasto_metodo);
        $stmt_d->execute();
        $res_d = $stmt_d->get_result();
        while ($fila_d = $res_d->fetch_assoc()) {
            $cuentas_detalle[$fila_d['detalle_pago']]['salidas'] = ($cuentas_detalle[$fila_d['detalle_pago']]['salidas'] ?? 0) + $fila_d['total'];
        }
        $stmt_d->close();
    }
    // ==========================================================

    $desglose_pagos[$metodo] = [
        'ingresos' => $ingresos_metodo,
        'salidas' => $egresos_metodo + $gastos_metodo, // Salidas = Egresos + Gastos
        'balance' => $ingresos_metodo - ($egresos_metodo + $gastos_metodo),
        'cuentas' => $cuentas_detalle // ¡NUEVO!
    ];
}
$stmt->close(); // Cierra el último $stmt del bucle

// --- 3. DESGLOSE DIARIO (fechas forzadas con DATE()) ---
// ¡LIMPIADO DE CARACTERES INVISIBLES!
$sql_detalle_diario_base = "
    SELECT fecha, SUM(ingreso) AS ingresos_diarios, SUM(egreso) AS egresos_diarios, SUM(gasto) AS gastos_diarios
    FROM (
        SELECT DATE(r.fecha_tramite) AS fecha, r.valor_servicio AS ingreso, 0 AS egreso, 0 AS gasto
        FROM recibos r 
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE r.estado = 'completado' AND r.fecha_tramite BETWEEN ? AND ? %s

        UNION ALL

        SELECT DATE(e.fecha) AS fecha, 0 AS ingreso, e.monto AS egreso, 0 AS gasto
        FROM egresos e 
        JOIN recibos r ON e.recibo_id = r.id 
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' %s

        UNION ALL

        SELECT DATE(g.fecha) AS fecha, 0 AS ingreso, 0 AS egreso, g.monto AS gasto
        FROM gastos_sede g
        WHERE g.fecha BETWEEN ? AND ? %s
    ) AS transacciones
    GROUP BY fecha ORDER BY fecha DESC
";

$params_detalle = [$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta];
$types_detalle = "ssssss";
$where_sede_r = ''; // Para recibos (alias a)
$where_sede_e = ''; // Para egresos (alias a)
$where_sede_g = ''; // Para gastos (alias g)

if (!empty($sede_id)) {
    $where_sede_r = " AND a.id_sede = ? ";
    $where_sede_e = " AND a.id_sede = ? ";
    $where_sede_g = " AND g.id_sede = ? "; // Diferente alias/tabla
    array_push($params_detalle, $sede_id, $sede_id, $sede_id);
    $types_detalle .= "iii";
}

$sql_detalle_diario = sprintf($sql_detalle_diario_base, $where_sede_r, $where_sede_e, $where_sede_g);
$stmt_detalle = $conn->prepare($sql_detalle_diario);
$stmt_detalle->bind_param($types_detalle, ...$params_detalle);
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
    'desglose_pagos' => $desglose_pagos, // Ahora contiene la sub-info de 'cuentas'
    'detalle' => $resultado_detalle // Contiene 'gastos_diarios'
];

echo json_encode($respuesta);
exit();
?>