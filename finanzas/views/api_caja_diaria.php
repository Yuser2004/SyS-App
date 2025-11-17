<?php
// finanzas/views/api_caja_diaria.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';
$debug_entradas_leidas = []; // <-- ¡AÑADE ESTA LÍNEA!
// 1. OBTENER FILTROS
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$id_sede_seleccionada = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : 1;

// 2. OBTENER SALDO DE APERTURA DESGLOSADO
$stmt_last_fecha = $conn->prepare(
    "SELECT MAX(fecha) AS fecha
     FROM cierres_caja
     WHERE id_sede = ? 
       AND fecha < ?
       AND (conteo_efectivo_cierre <> 0 OR conteo_transferencia_cierre <> 0)" // <-- ¡CORREGIDO!
);
$stmt_last_fecha->bind_param("is", $id_sede_seleccionada, $fecha_seleccionada);
$stmt_last_fecha->execute();

$last_row = $stmt_last_fecha->get_result()->fetch_assoc();
$fecha_apertura = $last_row['fecha'] ?? null;
$stmt_last_fecha->close();

$saldo_apertura_efectivo = 0;
$saldo_apertura_transferencia = 0;

if ($fecha_apertura) {
    $stmt_apertura = $conn->prepare(
        "SELECT conteo_efectivo_cierre, conteo_transferencia_cierre
         FROM cierres_caja
         WHERE id_sede = ? AND fecha = ? LIMIT 1"
    );
    $stmt_apertura->bind_param("is", $id_sede_seleccionada, $fecha_apertura);
    $stmt_apertura->execute();
    $resultado_apertura = $stmt_apertura->get_result()->fetch_assoc();
    $saldo_apertura_efectivo = $resultado_apertura['conteo_efectivo_cierre'] ?? 0;
    $saldo_apertura_transferencia = $resultado_apertura['conteo_transferencia_cierre'] ?? 0;
    $stmt_apertura->close();
}
$saldo_apertura = $saldo_apertura_efectivo + $saldo_apertura_transferencia;


// 3. CALCULAR MOVIMIENTOS DEL DÍA (MODIFICADO)
// --- INGRESOS (Sin cambios) ---
$sql_ingresos = "
    SELECT r.metodo_pago, r.detalle_pago, SUM(r.valor_servicio) AS total 
    FROM recibos r 
    JOIN asesor a ON r.id_asesor = a.id_asesor 
    WHERE r.estado IN ('completado', 'pendiente') 
      AND r.fecha_tramite = ? 
      AND a.id_sede = ? 
    GROUP BY r.metodo_pago, r.detalle_pago
";
$stmt_ingresos = $conn->prepare($sql_ingresos);
$stmt_ingresos->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_ingresos->execute();
$ingresos_result = $stmt_ingresos->get_result();

$total_ingresos = 0;
$ingresos_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];

while($fila = $ingresos_result->fetch_assoc()){
    $total_ingresos += $fila['total'];
    $metodo = $fila['metodo_pago'];
    if ($metodo === 'transferencia') {
        $ingresos_desglose['transferencia'] += $fila['total'];
        $entidad = $fila['detalle_pago'] ?? 'Sin Asignar';
        if (!isset($ingresos_desglose['transferencias'][$entidad])) {
            $ingresos_desglose['transferencias'][$entidad] = 0;
        }
        $ingresos_desglose['transferencias'][$entidad] += $fila['total'];
    } elseif (isset($ingresos_desglose[$metodo])) {
        $ingresos_desglose[$metodo] += $fila['total'];
    }
}
$stmt_ingresos->close();


// --- EGRESOS DE SERVICIO (Sin cambios) ---
$sql_egresos = "
    SELECT e.forma_pago, e.detalle_pago, SUM(e.monto) as total 
    FROM egresos e 
    JOIN recibos r ON e.recibo_id = r.id 
    JOIN asesor a ON r.id_asesor = a.id_asesor 
    WHERE r.estado IN ('completado', 'pendiente') 
      AND e.fecha = ? 
      AND a.id_sede = ? 
      AND e.tipo = 'servicio' 
    GROUP BY e.forma_pago, e.detalle_pago
";
$stmt_egresos = $conn->prepare($sql_egresos);
$stmt_egresos->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_egresos->execute();
$egresos_result = $stmt_egresos->get_result();

$total_egresos = 0;
$egresos_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];


while($fila = $egresos_result->fetch_assoc()){
     $total_egresos += $fila['total'];
    $metodo = $fila['forma_pago'];
    if ($metodo === 'transferencia') {
        $egresos_desglose['transferencia'] += $fila['total'];
        $entidad = $fila['detalle_pago'] ?? 'Sin Asignar';
        if (!isset($egresos_desglose['transferencias'][$entidad])) {
        $egresos_desglose['transferencias'][$entidad] = 0;
        }
        $egresos_desglose['transferencias'][$entidad] += $fila['total'];

    } elseif (isset($egresos_desglose[$metodo])) { 
    $egresos_desglose[$metodo] += $fila['total'];
    }
    // --- FIN CORRECCIÓN ---

}
$stmt_egresos->close();


// --- GASTOS DE SEDE (La consulta que faltaba) ---
$sql_gastos = "
    SELECT g.metodo_pago, g.detalle_pago, SUM(g.monto) as total 
    FROM gastos_sede g
    WHERE DATE(g.fecha) = ? 
    AND g.id_sede = ? 
    GROUP BY g.metodo_pago, g.detalle_pago
";
$stmt_gastos = $conn->prepare($sql_gastos);
$stmt_gastos->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_gastos->execute();
$gastos_result = $stmt_gastos->get_result();

$total_gastos = 0;
$gastos_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];

while($fila = $gastos_result->fetch_assoc()){
    $total_gastos += $fila['total'];
    $metodo = $fila['metodo_pago'];

    if ($metodo === 'transferencia') {
        $gastos_desglose['transferencia'] += $fila['total'];
        $entidad = $fila['detalle_pago'] ?? 'Sin Asignar';
        if (!isset($gastos_desglose['transferencias'][$entidad])) {
            $gastos_desglose['transferencias'][$entidad] = 0;
        }
        $gastos_desglose['transferencias'][$entidad] += $fila['total'];
    } elseif (isset($gastos_desglose[$metodo])) {
        $gastos_desglose[$metodo] += $fila['total'];
    }
}
$stmt_gastos->close();


// --- SALIDAS INTERNAS (MOVIMIENTOS) ---
$sql_salidas = "
    SELECT m.metodo_pago_origen, m.detalle_pago_origen, SUM(m.monto) as total 
    FROM movimientos_inter_sede m
    WHERE DATE(m.fecha) = ? 
    AND m.id_sede_origen = ? 
    GROUP BY m.metodo_pago_origen, m.detalle_pago_origen
";
$stmt_salidas = $conn->prepare($sql_salidas);
$stmt_salidas->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_salidas->execute();
$salidas_result = $stmt_salidas->get_result();

$total_salidas_internas = 0;
$salidas_internas_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];

while($fila = $salidas_result->fetch_assoc()){
    $total_salidas_internas += $fila['total'];
    $metodo = $fila['metodo_pago_origen'];

    if ($metodo === 'transferencia') {
        $salidas_internas_desglose['transferencia'] += $fila['total'];
        $entidad = $fila['detalle_pago_origen'] ?? 'Sin Asignar';
        if (!isset($salidas_internas_desglose['transferencias'][$entidad])) {
            $salidas_internas_desglose['transferencias'][$entidad] = 0;
        }
        $salidas_internas_desglose['transferencias'][$entidad] += $fila['total'];
    } elseif (isset($salidas_internas_desglose[$metodo])) {
        $salidas_internas_desglose[$metodo] += $fila['total'];
    }
}
$stmt_salidas->close();


// --- ENTRADAS INTERNAS (MOVIMIENTOS) ---
$sql_entradas = "
    SELECT m.metodo_pago_destino, m.detalle_pago_destino, SUM(m.monto) as total 
    FROM movimientos_inter_sede m
    WHERE DATE(m.fecha) = ? 
    AND m.id_sede_destino = ? 
    GROUP BY m.metodo_pago_destino, m.detalle_pago_destino
";
$stmt_entradas = $conn->prepare($sql_entradas);
$stmt_entradas->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_entradas->execute();
$entradas_result = $stmt_entradas->get_result();

$total_entradas_internas = 0;
$entradas_internas_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];

while($fila = $entradas_result->fetch_assoc()){
    $debug_entradas_leidas[] = $fila; // <-- ¡AÑADE ESTA LÍNEA!
    $total_entradas_internas += $fila['total'];
    $metodo = $fila['metodo_pago_destino'];

    if ($metodo === 'transferencia') {
        $entradas_internas_desglose['transferencia'] += $fila['total'];
        $entidad = $fila['detalle_pago_destino'] ?? 'Sin Asignar';
        if (!isset($entradas_internas_desglose['transferencias'][$entidad])) {
            $entradas_internas_desglose['transferencias'][$entidad] = 0;
        }
        $entradas_internas_desglose['transferencias'][$entidad] += $fila['total'];
    } elseif (isset($entradas_internas_desglose[$metodo])) {
        $entradas_internas_desglose[$metodo] += $fila['total'];
    }
}
$stmt_entradas->close();


// 4. CÁLCULOS FINALES
// --- Calcular Balance del Día por Método ---
$balance_dia_por_metodo = [
    'efectivo' => ($ingresos_desglose['efectivo'] + $entradas_internas_desglose['efectivo']) - ($egresos_desglose['efectivo'] + $gastos_desglose['efectivo'] + $salidas_internas_desglose['efectivo']),
    'tarjeta' => ($ingresos_desglose['tarjeta'] + $entradas_internas_desglose['tarjeta']) - ($egresos_desglose['tarjeta'] + $gastos_desglose['tarjeta'] + $salidas_internas_desglose['tarjeta']),
    'otro' => ($ingresos_desglose['otro'] + $entradas_internas_desglose['otro']) - ($egresos_desglose['otro'] + $gastos_desglose['otro'] + $salidas_internas_desglose['otro']),
    'transferencia' => ($ingresos_desglose['transferencia'] + $entradas_internas_desglose['transferencia']) - ($egresos_desglose['transferencia'] + $gastos_desglose['transferencia'] + $salidas_internas_desglose['transferencia']),
    'transferencias' => []
];

// Calcular desglose de balance para transferencias
$cuentas_transferencia = array_unique(array_merge(
    array_keys($ingresos_desglose['transferencias']),
    array_keys($egresos_desglose['transferencias']),
    array_keys($gastos_desglose['transferencias']), 
    array_keys($entradas_internas_desglose['transferencias']),
    array_keys($salidas_internas_desglose['transferencias'])
));

foreach ($cuentas_transferencia as $cuenta) {
    if (empty($cuenta)) $cuenta = 'Sin Asignar';
    
    $ingreso_cuenta = $ingresos_desglose['transferencias'][$cuenta] ?? 0;
    $egreso_cuenta = $egresos_desglose['transferencias'][$cuenta] ?? 0;
    $gasto_cuenta = $gastos_desglose['transferencias'][$cuenta] ?? 0;
    $entrada_interna_cuenta = $entradas_internas_desglose['transferencias'][$cuenta] ?? 0;
    $salida_interna_cuenta = $salidas_internas_desglose['transferencias'][$cuenta] ?? 0;
    
    $balance_cuenta_dia = ($ingreso_cuenta + $entrada_interna_cuenta) - ($egreso_cuenta + $gasto_cuenta + $salida_interna_cuenta);
    
    if (!isset($balance_dia_por_metodo['transferencias'][$cuenta])) {
        $balance_dia_por_metodo['transferencias'][$cuenta] = 0;
    }
    $balance_dia_por_metodo['transferencias'][$cuenta] += $balance_cuenta_dia;
}

// Total balance del día
$balance_dia = $balance_dia_por_metodo['efectivo'] 
             + $balance_dia_por_metodo['transferencia'] 
             + $balance_dia_por_metodo['tarjeta'] 
             + $balance_dia_por_metodo['otro'];

// ¡CORREGIDO! $total_gastos debe incluirse aquí
$hubo_movimientos_hoy = ($total_ingresos != 0 || $total_egresos != 0 || $total_gastos != 0 || $total_salidas_internas != 0 || $total_entradas_internas != 0);

// --- Calcular Saldo Final Esperado ---
$saldo_final_esperado_efectivo = $saldo_apertura_efectivo + $balance_dia_por_metodo['efectivo'];
$saldo_final_esperado_transferencia = $saldo_apertura_transferencia + $balance_dia_por_metodo['transferencia'];
$saldo_final_esperado_tarjeta = $balance_dia_por_metodo['tarjeta'];
$saldo_final_esperado_otro = $balance_dia_por_metodo['otro'];

$saldo_final_esperado = $saldo_final_esperado_efectivo + $saldo_final_esperado_transferencia + $saldo_final_esperado_tarjeta + $saldo_final_esperado_otro;


// 5. LÓGICA DE VALIDACIÓN DE CIERRE
$stmt_ultimo_cierre = $conn->prepare("SELECT MAX(fecha) FROM cierres_caja WHERE id_sede = ?");
$stmt_ultimo_cierre->bind_param("i", $id_sede_seleccionada);
$stmt_ultimo_cierre->execute();
$ultimo_cierre = $stmt_ultimo_cierre->get_result()->fetch_row()[0] ?? '1970-01-01';
$stmt_ultimo_cierre->close();

// ¡CORREGIDO! Esta consulta ahora es IDÉNTICA a la lógica de $hubo_movimientos_hoy
$sql_dia_pendiente = "
    SELECT MIN(fecha_movimiento) FROM (
        SELECT DATE(r.fecha_tramite) as fecha_movimiento 
        FROM recibos r 
        JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE a.id_sede = ? 
          AND DATE(r.fecha_tramite) > ?
          AND r.estado IN ('completado', 'pendiente')
        UNION
        SELECT DATE(e.fecha) as fecha_movimiento 
        FROM egresos e 
        JOIN recibos r ON e.recibo_id = r.id 
        JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE a.id_sede = ? 
          AND e.tipo = 'servicio' 
          AND DATE(e.fecha) > ?
          AND r.estado IN ('completado', 'pendiente')
        UNION
        SELECT DATE(g.fecha) as fecha_movimiento
        FROM gastos_sede g
        WHERE g.id_sede = ? AND DATE(g.fecha) > ?
        UNION
        SELECT DATE(m.fecha) as fecha_movimiento
        FROM movimientos_inter_sede m
        WHERE (m.id_sede_origen = ? OR m.id_sede_destino = ?) AND DATE(m.fecha) > ?
    ) as movimientos
";
$stmt_pendiente = $conn->prepare($sql_dia_pendiente);
$stmt_pendiente->bind_param("isisisisi", 
    $id_sede_seleccionada, $ultimo_cierre, 
    $id_sede_seleccionada, $ultimo_cierre,
    $id_sede_seleccionada, $ultimo_cierre,
    $id_sede_seleccionada, $id_sede_seleccionada, $ultimo_cierre
);
$stmt_pendiente->execute();
$dia_pendiente_de_cierre = $stmt_pendiente->get_result()->fetch_row()[0] ?? null;
$stmt_pendiente->close();

$se_puede_cerrar_hoy = false;
$mensaje_cierre_bloqueado = '';

if ($dia_pendiente_de_cierre === null) {
    $se_puede_cerrar_hoy = $hubo_movimientos_hoy; 
} else if ($fecha_seleccionada == $dia_pendiente_de_cierre) {
    $se_puede_cerrar_hoy = true;
} else {
    $mensaje_cierre_bloqueado = "Debes cerrar la caja del día " . date('d/m/Y', strtotime($dia_pendiente_de_cierre)) . " primero.";
}

// 6. VERIFICAR SI LA CAJA YA ESTÁ CERRADA
$cierre_info = null;
$stmt_cierre_existente = $conn->prepare("SELECT * FROM cierres_caja WHERE id_sede = ? AND fecha = ?");
$stmt_cierre_existente->bind_param("is", $id_sede_seleccionada, $fecha_seleccionada);
$stmt_cierre_existente->execute();
$resultado_cierre = $stmt_cierre_existente->get_result();
if ($resultado_cierre->num_rows > 0) {
    $cierre_info = $resultado_cierre->fetch_assoc();
}
$stmt_cierre_existente->close();

// 7. CONSTRUIR RESPUESTA JSON
$respuesta = [
    'cierre_info' => $cierre_info,
    'se_puede_cerrar' => $se_puede_cerrar_hoy,
    'mensaje_cierre_bloqueado' => $mensaje_cierre_bloqueado,
    'hubo_movimientos_hoy' => $hubo_movimientos_hoy,
    
    'saldo_apertura' => [
        'total' => $saldo_apertura,
        'desglose' => [
            'efectivo' => $saldo_apertura_efectivo,
            'transferencia' => $saldo_apertura_transferencia
        ]
    ],

    'ingresos' => ['total' => $total_ingresos, 'desglose' => $ingresos_desglose],
    'egresos' => ['total' => $total_egresos, 'desglose' => $egresos_desglose],
    'gastos' => ['total' => $total_gastos, 'desglose' => $gastos_desglose], 
    
    'salidas_internas' => ['total' => $total_salidas_internas, 'desglose' => $salidas_internas_desglose],
    'entradas_internas' => ['total' => $total_entradas_internas, 'desglose' => $entradas_internas_desglose],
    
    'balance_dia' => [
        'total' => $balance_dia,
        'desglose' => $balance_dia_por_metodo 
    ],

    'saldo_final_esperado' => [
        'total' => $saldo_final_esperado,
        'desglose' => [
            'efectivo' => $saldo_final_esperado_efectivo,
            'transferencia' => $saldo_final_esperado_transferencia,
            'tarjeta' => $saldo_final_esperado_tarjeta
        ]
        ],
        'debug_info_entradas_leidas' => $debug_entradas_leidas // <-- ¡AÑADE ESTA LÍNEA!
];

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$conn->close();
exit();
?>