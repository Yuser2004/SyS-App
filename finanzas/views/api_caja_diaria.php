<?php
// finanzas/views/api_caja_diaria.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

// 1. OBTENER FILTROS
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$id_sede_seleccionada = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : 1;

// 2. OBTENER SALDO DE APERTURA
$fecha_anterior = date('Y-m-d', strtotime($fecha_seleccionada . ' -1 day'));
$stmt_apertura = $conn->prepare("SELECT saldo_final FROM cierres_caja WHERE id_sede = ? AND fecha = ?");
$stmt_apertura->bind_param("is", $id_sede_seleccionada, $fecha_anterior);
$stmt_apertura->execute();
$saldo_apertura = $stmt_apertura->get_result()->fetch_assoc()['saldo_final'] ?? 0;
$stmt_apertura->close();

// 3. CALCULAR MOVIMIENTOS DEL DÍA

// INGRESOS POR VENTAS (operativos)
$sql_ingresos = "SELECT r.metodo_pago, SUM(r.valor_servicio) AS total FROM recibos r JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND r.fecha_tramite = ? AND a.id_sede = ? GROUP BY r.metodo_pago";
$stmt_ingresos = $conn->prepare($sql_ingresos);
$stmt_ingresos->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_ingresos->execute();
$ingresos_result = $stmt_ingresos->get_result();
$total_ingresos = 0;
$ingresos_por_metodo = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $ingresos_result->fetch_assoc()){
    $total_ingresos += $fila['total'];
    if (isset($ingresos_por_metodo[$fila['metodo_pago']])) {
        $ingresos_por_metodo[$fila['metodo_pago']] = $fila['total'];
    }
}
$stmt_ingresos->close();

// EGRESOS DE SERVICIO (operativos)
$sql_egresos = "SELECT e.forma_pago, SUM(e.monto) as total FROM egresos e JOIN recibos r ON e.recibo_id = r.id JOIN asesor a ON r.id_asesor = a.id_asesor WHERE r.estado = 'completado' AND e.fecha = ? AND a.id_sede = ? AND e.tipo = 'servicio' GROUP BY e.forma_pago";
$stmt_egresos = $conn->prepare($sql_egresos);
$stmt_egresos->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_egresos->execute();
$egresos_result = $stmt_egresos->get_result();
$total_egresos = 0;
$egresos_por_metodo = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $egresos_result->fetch_assoc()){
    $total_egresos += $fila['total'];
    if (isset($egresos_por_metodo[$fila['forma_pago']])) {
        $egresos_por_metodo[$fila['forma_pago']] = $fila['total'];
    }
}
$stmt_egresos->close();

// --- MOVIMIENTOS NO OPERATIVOS ---

// PRÉSTAMOS ENVIADOS (Salida de dinero)
$sql_prestamos_enviados = "SELECT forma_pago, SUM(monto) as total FROM egresos WHERE fecha = ? AND sede_origen_id = ? AND tipo = 'prestamo' GROUP BY forma_pago";
$stmt_prestamos_enviados = $conn->prepare($sql_prestamos_enviados);
$stmt_prestamos_enviados->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_prestamos_enviados->execute();
$prestamos_enviados_result = $stmt_prestamos_enviados->get_result();
$total_prestamos_enviados = 0;
$prestamos_enviados_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $prestamos_enviados_result->fetch_assoc()){
    $total_prestamos_enviados += $fila['total'];
    if (isset($prestamos_enviados_desglose[$fila['forma_pago']])) {
        $prestamos_enviados_desglose[$fila['forma_pago']] = $fila['total'];
    }
}
$stmt_prestamos_enviados->close();

// PRÉSTAMOS RECIBIDOS (Entrada de dinero)
$sql_prestamos_recibidos = "SELECT forma_pago, SUM(monto) as total FROM egresos WHERE fecha = ? AND sede_destino_id = ? AND tipo = 'prestamo' GROUP BY forma_pago";
$stmt_prestamos_recibidos = $conn->prepare($sql_prestamos_recibidos);
$stmt_prestamos_recibidos->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_prestamos_recibidos->execute();
$prestamos_recibidos_result = $stmt_prestamos_recibidos->get_result();
$total_prestamos_recibidos = 0;
$prestamos_recibidos_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $prestamos_recibidos_result->fetch_assoc()){
    $total_prestamos_recibidos += $fila['total'];
    if (isset($prestamos_recibidos_desglose[$fila['forma_pago']])) {
        $prestamos_recibidos_desglose[$fila['forma_pago']] = $fila['total'];
    }
}
$stmt_prestamos_recibidos->close();

// DEVOLUCIONES RECIBIDAS (Entrada de dinero)
$sql_devoluciones_recibidas = "SELECT metodo_pago, SUM(monto) as total FROM devoluciones_prestamos WHERE fecha = ? AND id_sede_receptora = ? GROUP BY metodo_pago";
$stmt_devoluciones_recibidas = $conn->prepare($sql_devoluciones_recibidas);
$stmt_devoluciones_recibidas->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_devoluciones_recibidas->execute();
$devoluciones_recibidas_result = $stmt_devoluciones_recibidas->get_result();
$total_devoluciones_recibidas = 0;
$devoluciones_recibidas_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $devoluciones_recibidas_result->fetch_assoc()){
    $total_devoluciones_recibidas += $fila['total'];
    if (isset($devoluciones_recibidas_desglose[$fila['metodo_pago']])) {
        $devoluciones_recibidas_desglose[$fila['metodo_pago']] = $fila['total'];
    }
}
$stmt_devoluciones_recibidas->close();

// DEVOLUCIONES ENVIADAS (Salida de dinero)
$sql_devoluciones_enviadas = "SELECT metodo_pago, SUM(monto) as total FROM devoluciones_prestamos WHERE fecha = ? AND id_sede_origen = ? GROUP BY metodo_pago";
$stmt_devoluciones_enviadas = $conn->prepare($sql_devoluciones_enviadas);
$stmt_devoluciones_enviadas->bind_param("si", $fecha_seleccionada, $id_sede_seleccionada);
$stmt_devoluciones_enviadas->execute();
$devoluciones_enviadas_result = $stmt_devoluciones_enviadas->get_result();
$total_devoluciones_enviadas = 0;
$devoluciones_enviadas_desglose = ['efectivo' => 0, 'transferencia' => 0, 'tarjeta' => 0, 'otro' => 0];
while($fila = $devoluciones_enviadas_result->fetch_assoc()){
    $total_devoluciones_enviadas += $fila['total'];
    if (isset($devoluciones_enviadas_desglose[$fila['metodo_pago']])) {
        $devoluciones_enviadas_desglose[$fila['metodo_pago']] = $fila['total'];
    }
}
$stmt_devoluciones_enviadas->close();

// 4. CÁLCULOS FINALES (CORREGIDOS Y UNIFICADOS)
$total_entradas = $total_ingresos + $total_prestamos_recibidos + $total_devoluciones_recibidas;
$total_salidas = $total_egresos + $total_prestamos_enviados + $total_devoluciones_enviadas;

$balance_dia = $total_entradas - $total_salidas;
$saldo_final_esperado = $saldo_apertura + $balance_dia;
$hubo_movimientos_hoy = ($total_entradas > 0 || $total_salidas > 0);

// 5. LÓGICA DE VALIDACIÓN DE CIERRE
$stmt_ultimo_cierre = $conn->prepare("SELECT MAX(fecha) FROM cierres_caja WHERE id_sede = ?");
$stmt_ultimo_cierre->bind_param("i", $id_sede_seleccionada);
$stmt_ultimo_cierre->execute();
$ultimo_cierre = $stmt_ultimo_cierre->get_result()->fetch_row()[0] ?? '1970-01-01';
$stmt_ultimo_cierre->close();

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
        UNION
        SELECT DATE(fecha) as fecha_movimiento 
        FROM egresos 
        WHERE tipo = 'prestamo' AND sede_origen_id = ? AND DATE(fecha) > ?
        UNION
        SELECT DATE(fecha) as fecha_movimiento 
        FROM devoluciones_prestamos 
        WHERE id_sede_origen = ? AND DATE(fecha) > ?
    ) as movimientos
";
$stmt_pendiente = $conn->prepare($sql_dia_pendiente);
$stmt_pendiente->bind_param("isisisis", 
    $id_sede_seleccionada, $ultimo_cierre, 
    $id_sede_seleccionada, $ultimo_cierre, 
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

// 7. CONSTRUIR RESPUESTA JSON
$respuesta = [
    'cierre_info' => $cierre_info,
    'se_puede_cerrar' => $se_puede_cerrar_hoy,
    'mensaje_cierre_bloqueado' => $mensaje_cierre_bloqueado,
    'hubo_movimientos_hoy' => $hubo_movimientos_hoy,
    'saldo_apertura' => $saldo_apertura,
    'ingresos' => ['total' => $total_ingresos, 'desglose' => $ingresos_por_metodo],
    'egresos' => ['total' => $total_egresos, 'desglose' => $egresos_por_metodo],
    'prestamos_enviados' => ['total' => $total_prestamos_enviados, 'desglose' => $prestamos_enviados_desglose],
    'prestamos_recibidos' => ['total' => $total_prestamos_recibidos, 'desglose' => $prestamos_recibidos_desglose],
    'devoluciones_recibidas' => ['total' => $total_devoluciones_recibidas, 'desglose' => $devoluciones_recibidas_desglose],
    'devoluciones_enviadas' => ['total' => $total_devoluciones_enviadas, 'desglose' => $devoluciones_enviadas_desglose],
    'balance_dia' => $balance_dia,
    'saldo_final_esperado' => $saldo_final_esperado
];

echo json_encode($respuesta);
exit();
?>