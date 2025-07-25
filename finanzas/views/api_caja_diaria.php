<?php
// finanzas/views/api_caja_diaria.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

// 1. OBTENER FILTROS
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$id_sede_seleccionada = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : 1;

// 2. OBTENER SALDO DE APERTURA
$fecha_anterior = date('Y-m-d', strtotime($fecha_seleccionada . ' -1 day'));
$stmt = $conn->prepare("SELECT saldo_final FROM cierres_caja WHERE id_sede = ? AND fecha = ?");
$stmt->bind_param("is", $id_sede_seleccionada, $fecha_anterior);
$stmt->execute();
$saldo_apertura = $stmt->get_result()->fetch_assoc()['saldo_final'] ?? 0;
$stmt->close();

// 3. CALCULAR MOVIMIENTOS DEL DÍA SELECCIONADO

// === BLOQUE DE INGRESOS (ESTO FALTABA) ===
$sql_ingresos = "SELECT r.metodo_pago, SUM(r.valor_servicio) AS total FROM recibos r JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND r.fecha_tramite = ? AND a.id_sede = ? GROUP BY r.metodo_pago";
$stmt = $conn->prepare($sql_ingresos);
$stmt->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt->execute();
$ingresos_result = $stmt->get_result();
$total_ingresos = 0;
$ingresos_por_metodo = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $ingresos_result->fetch_assoc()){
    if (isset($ingresos_por_metodo[$fila['metodo_pago']])) {
        $ingresos_por_metodo[$fila['metodo_pago']] = $fila['total'];
        $total_ingresos += $fila['total'];
    }
}
$stmt->close();

// Egresos (código no duplicado)
$sql_egresos = "
    SELECT e.forma_pago, SUM(e.monto) as total
    FROM egresos e
    JOIN recibos r ON e.recibo_id = r.id
    JOIN asesor a ON r.id_asesor = a.id_asesor
    WHERE r.estado = 'completado' AND e.fecha = ? AND a.id_sede = ?
    GROUP BY e.forma_pago
";
$stmt = $conn->prepare($sql_egresos);
$stmt->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt->execute();
$egresos_result = $stmt->get_result();
$total_egresos = 0;
$egresos_por_metodo = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $egresos_result->fetch_assoc()){
    if (isset($egresos_por_metodo[$fila['forma_pago']])) {
        $egresos_por_metodo[$fila['forma_pago']] = $fila['total'];
        $total_egresos += $fila['total'];
    }
}
$stmt->close();

// Gastos
$sql_gastos = "SELECT metodo_pago, SUM(monto) as total FROM gastos WHERE fecha = ? AND id_sede = ? GROUP BY metodo_pago";
$stmt = $conn->prepare($sql_gastos);
$stmt->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt->execute();
$gastos_result = $stmt->get_result();
$total_gastos = 0;
$gastos_por_metodo = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $gastos_result->fetch_assoc()){
    if (isset($gastos_por_metodo[$fila['metodo_pago']])) {
        $gastos_por_metodo[$fila['metodo_pago']] = $fila['total'];
        $total_gastos += $fila['total'];
    }
}
$stmt->close();

// 4. CÁLCULOS FINALES
$balance_dia = $total_ingresos - ($total_egresos + $total_gastos);
$saldo_final_esperado = $saldo_apertura + $balance_dia;
// --- NUEVO: VALIDACIÓN DE CIERRE DE CAJA PENDIENTE ---
$fecha_cierre_mas_reciente = null;
$stmt_ultimo_cierre = $conn->prepare("SELECT MAX(fecha) FROM cierres_caja WHERE id_sede = ?");
$stmt_ultimo_cierre->bind_param("i", $id_sede_seleccionada);
$stmt_ultimo_cierre->execute();
$stmt_ultimo_cierre->bind_result($fecha_cierre_mas_reciente);
$stmt_ultimo_cierre->fetch();
$stmt_ultimo_cierre->close();

$dia_habil_para_cerrar = null;
if ($fecha_cierre_mas_reciente) {
    // Si hay cierres, buscamos el primer día con movimientos DESPUÉS de ese último cierre
    $sql_primer_movimiento = "
        SELECT MIN(fecha) FROM (
            SELECT fecha_tramite as fecha FROM recibos r JOIN asesor a ON r.id_asesor = a.id_asesor WHERE a.id_sede = ? AND r.fecha_tramite > ?
            UNION
            SELECT fecha FROM egresos e JOIN recibos r ON e.recibo_id = r.id JOIN asesor a ON r.id_asesor = a.id_asesor WHERE a.id_sede = ? AND e.fecha > ?
            UNION
            SELECT fecha FROM gastos WHERE id_sede = ? AND fecha > ?
        ) as movimientos
    ";
    $stmt_mov = $conn->prepare($sql_primer_movimiento);
    $stmt_mov->bind_param("isisis", $id_sede_seleccionada, $fecha_cierre_mas_reciente, $id_sede_seleccionada, $fecha_cierre_mas_reciente, $id_sede_seleccionada, $fecha_cierre_mas_reciente);
    $stmt_mov->execute();
    $stmt_mov->bind_result($dia_habil_para_cerrar);
    $stmt_mov->fetch();
    $stmt_mov->close();
} else {
    // Si NUNCA se ha cerrado caja, buscamos el primer día con movimientos en todo el historial
    $sql_primer_movimiento = "
        SELECT MIN(fecha) FROM (
            SELECT fecha_tramite as fecha FROM recibos r JOIN asesor a ON r.id_asesor = a.id_asesor WHERE a.id_sede = ?
            UNION
            SELECT fecha FROM egresos e JOIN recibos r ON e.recibo_id = r.id JOIN asesor a ON r.id_asesor = a.id_asesor WHERE a.id_sede = ?
            UNION
            SELECT fecha FROM gastos WHERE id_sede = ?
        ) as movimientos
    ";
    $stmt_mov = $conn->prepare($sql_primer_movimiento);
    $stmt_mov->bind_param("iii", $id_sede_seleccionada, $id_sede_seleccionada, $id_sede_seleccionada);
    $stmt_mov->execute();
    $stmt_mov->bind_result($dia_habil_para_cerrar);
    $stmt_mov->fetch();
    $stmt_mov->close();
}

$se_puede_cerrar_hoy = ($dia_habil_para_cerrar === null || $fecha_seleccionada == $dia_habil_para_cerrar);
$mensaje_cierre_bloqueado = $se_puede_cerrar_hoy ? '' : "Debes cerrar la caja del día " . date('d/m/Y', strtotime($dia_habil_para_cerrar)) . " primero.";
// Verificar si la caja del día ya está cerrada
$cierre_existente = $conn->query("SELECT id FROM cierres_caja WHERE id_sede = $id_sede_seleccionada AND fecha = '$fecha_seleccionada'")->num_rows > 0;

// 5. PREPARAR RESPUESTA JSON
$respuesta = [
    'caja_cerrada' => $cierre_existente,
    'se_puede_cerrar' => $se_puede_cerrar_hoy, // <-- NUEVO
    'mensaje_cierre_bloqueado' => $mensaje_cierre_bloqueado, // <-- NUEVO
    'saldo_apertura' => $saldo_apertura,
    'ingresos' => [
        'total' => $total_ingresos,
        'desglose' => $ingresos_por_metodo
    ],
    'egresos' => [
        'total' => $total_egresos,
        'desglose' => $egresos_por_metodo
    ],
    'gastos' => [
        'total' => $total_gastos,
        'desglose' => $gastos_por_metodo
    ],
    'balance_dia' => $balance_dia,
    'saldo_final_esperado' => $saldo_final_esperado
];

echo json_encode($respuesta);
exit();
?>