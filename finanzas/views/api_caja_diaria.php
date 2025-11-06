<?php
// finanzas/views/api_caja_diaria.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

// 1. OBTENER FILTROS
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$id_sede_seleccionada = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : 1;

// 2. OBTENER SALDO DE APERTURA DESGLOSADO
// (Esta lógica no cambia, ya que la apertura se basa en el cierre total anterior)
$stmt_last_fecha = $conn->prepare(
    "SELECT MAX(fecha) AS fecha
     FROM cierres_caja
     WHERE id_sede = ? 
       AND fecha < ?
       AND (total_ingresos <> 0 OR total_egresos <> 0 OR conteo_efectivo_cierre <> 0 OR conteo_transferencia_cierre <> 0)"
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
// --- INGRESOS (Con desglose de 'detalle_pago') ---
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
// Estructura de desglose anidada
$ingresos_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];

while($fila = $ingresos_result->fetch_assoc()){
    $total_ingresos += $fila['total'];
    $metodo = $fila['metodo_pago'];

    if ($metodo === 'transferencia') {
        // Agregamos el total al bucket general de 'transferencia'
        $ingresos_desglose['transferencia'] += $fila['total'];
        
        // Y también lo agregamos al desglose detallado
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


// --- EGRESOS DE SERVICIO (Con desglose de 'detalle_pago') ---
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
// Estructura de desglose anidada
$egresos_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0, 'transferencias' => []];

while($fila = $egresos_result->fetch_assoc()){
    $total_egresos += $fila['total'];
    $metodo = $fila['forma_pago']; // 'forma_pago' en egresos

    if ($metodo === 'transferencia') {
        // Agregamos el total al bucket general de 'transferencia'
        $egresos_desglose['transferencia'] += $fila['total'];

        // Y también lo agregamos al desglose detallado
        $entidad = $fila['detalle_pago'] ?? 'Sin Asignar';
        if (!isset($egresos_desglose['transferencias'][$entidad])) {
            $egresos_desglose['transferencias'][$entidad] = 0;
        }
        $egresos_desglose['transferencias'][$entidad] += $fila['total'];

    } elseif (isset($egresos_desglose[$metodo])) {
        $egresos_desglose[$metodo] += $fila['total'];
    }
}
$stmt_egresos->close();


// --- MOVIMIENTOS NO OPERATIVOS ---
// --- (Toda la lógica de préstamos y devoluciones ha sido eliminada) ---


// 4. CÁLCULOS FINALES (SIMPLIFICADOS Y CON DESGLOSE)

// --- Calcular Balance del Día por Método (con desglose) ---
$balance_dia_por_metodo = [
    'efectivo' => $ingresos_desglose['efectivo'] - $egresos_desglose['efectivo'],
    'tarjeta' => $ingresos_desglose['tarjeta'] - $egresos_desglose['tarjeta'],
    'otro' => $ingresos_desglose['otro'] - $egresos_desglose['otro'],
    'transferencia' => $ingresos_desglose['transferencia'] - $egresos_desglose['transferencia'], // Balance total de transferencias
    'transferencias' => [] // Desglose de balance por cuenta
];

// Calcular desglose de balance para transferencias
$cuentas_transferencia = array_unique(array_merge(
    array_keys($ingresos_desglose['transferencias']),
    array_keys($egresos_desglose['transferencias'])
));

foreach ($cuentas_transferencia as $cuenta) {
    $ingreso_cuenta = $ingresos_desglose['transferencias'][$cuenta] ?? 0;
    $egreso_cuenta = $egresos_desglose['transferencias'][$cuenta] ?? 0;
    $balance_dia_por_metodo['transferencias'][$cuenta] = $ingreso_cuenta - $egreso_cuenta;
}

// Total balance del día
$balance_dia = $balance_dia_por_metodo['efectivo'] 
             + $balance_dia_por_metodo['transferencia'] 
             + $balance_dia_por_metodo['tarjeta'] 
             + $balance_dia_por_metodo['otro'];

$hubo_movimientos_hoy = ($total_ingresos != 0 || $total_egresos != 0);

// --- Calcular Saldo Final Esperado (para rellenar el formulario de cierre) ---
$saldo_final_esperado_efectivo = $saldo_apertura_efectivo + $balance_dia_por_metodo['efectivo'];
$saldo_final_esperado_transferencia = $saldo_apertura_transferencia + $balance_dia_por_metodo['transferencia'];
$saldo_final_esperado_tarjeta = $balance_dia_por_metodo['tarjeta']; // solo movimientos, no apertura
$saldo_final_esperado_otro = $balance_dia_por_metodo['otro'];

$saldo_final_esperado = $saldo_final_esperado_efectivo + $saldo_final_esperado_transferencia + $saldo_final_esperado_tarjeta + $saldo_final_esperado_otro;


// 5. LÓGICA DE VALIDACIÓN DE CIERRE (SIMPLIFICADA)
$stmt_ultimo_cierre = $conn->prepare("SELECT MAX(fecha) FROM cierres_caja WHERE id_sede = ?");
$stmt_ultimo_cierre->bind_param("i", $id_sede_seleccionada);
$stmt_ultimo_cierre->execute();
$ultimo_cierre = $stmt_ultimo_cierre->get_result()->fetch_row()[0] ?? '1970-01-01';
$stmt_ultimo_cierre->close();

// SQL de día pendiente simplificado (sin préstamos/devoluciones)
$sql_dia_pendiente = "
    SELECT MIN(fecha_movimiento) FROM (
        SELECT DATE(r.fecha_tramite) as fecha_movimiento 
        FROM recibos r 
        JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE a.id_sede = ? AND DATE(r.fecha_tramite) > ?
        UNION
        SELECT DATE(e.fecha) as fecha_movimiento 
        FROM egresos e 
        JOIN recibos r ON e.recibo_id = r.id 
        JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE a.id_sede = ? AND e.tipo = 'servicio' AND DATE(e.fecha) > ?
    ) as movimientos
";
$stmt_pendiente = $conn->prepare($sql_dia_pendiente);
$stmt_pendiente->bind_param("isis", 
    $id_sede_seleccionada, $ultimo_cierre, 
    $id_sede_seleccionada, $ultimo_cierre
);
$stmt_pendiente->execute();
$dia_pendiente_de_cierre = $stmt_pendiente->get_result()->fetch_row()[0] ?? null;
$stmt_pendiente->close();

$se_puede_cerrar_hoy = false;
$mensaje_cierre_bloqueado = '';
if ($dia_pendiente_de_cierre === null) {
    $se_puede_cerrar_hoy = true; 
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

// 7. CONSTRUIR RESPONSABILIDAD JSON (SIMPLIFICADA)
$respuesta = [
    'cierre_info' => $cierre_info,
    'se_puede_cerrar' => $se_puede_cerrar_hoy,
    'mensaje_cierre_bloqueado' => $mensaje_cierre_bloqueado,
    'hubo_movimientos_hoy' => $hubo_movimientos_hoy,
    
    // Saldo de apertura (sin cambios)
    'saldo_apertura' => [
        'total' => $saldo_apertura,
        'desglose' => [
            'efectivo' => $saldo_apertura_efectivo,
            'transferencia' => $saldo_apertura_transferencia
        ]
    ],

    // Desglose de movimientos (ahora anidado)
    'ingresos' => ['total' => $total_ingresos, 'desglose' => $ingresos_desglose],
    'egresos' => ['total' => $total_egresos, 'desglose' => $egresos_desglose],
    
    // (Campos de préstamos/devoluciones eliminados)
    
    // Balance del día (ahora anidado)
    'balance_dia' => [
        'total' => $balance_dia,
        'desglose' => $balance_dia_por_metodo 
    ],

    // Saldo final (desglose plano para rellenar el formulario de conteo)
    'saldo_final_esperado' => [
        'total' => $saldo_final_esperado,
        'desglose' => [
            'efectivo' => $saldo_final_esperado_efectivo,
            'transferencia' => $saldo_final_esperado_transferencia,
            'tarjeta' => $saldo_final_esperado_tarjeta
        ]
    ]
];

echo json_encode($respuesta, JSON_PRETTY_PRINT);
exit();
?>