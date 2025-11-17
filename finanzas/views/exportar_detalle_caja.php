<?php
ob_start(); // <-- Inicia el búfer de salida
// finanzas/views/exportar_detalle_caja.php
// ¡VERSIÓN 2.0!
// Añadida la sección "Movimientos Internos" y su lógica a los saldos.

// 1. CARGAR LIBRERÍAS
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../models/conexion.php'; 

// Usamos las clases de PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Forzar que se muestren los errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. OBTENER FILTROS
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$id_sede_seleccionada = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : 1;

// 3. OBTENER CAJA INICIAL (MODIFICADO PARA DESGLOSE)
$stmt_last_fecha = $conn->prepare(
    "SELECT MAX(fecha) AS fecha
     FROM cierres_caja
     WHERE id_sede = ? 
       AND fecha < ?
       AND (conteo_efectivo_cierre <> 0 OR conteo_transferencia_cierre <> 0)" // Corregido
);
$stmt_last_fecha->bind_param("is", $id_sede_seleccionada, $fecha_inicio);
$stmt_last_fecha->execute();
$last_row = $stmt_last_fecha->get_result()->fetch_assoc();
$fecha_apertura = $last_row['fecha'] ?? null;
$stmt_last_fecha->close();

// --- Variables de seguimiento de saldos ---
$saldo_efectivo_dia_siguiente = 0.0;
$saldos_por_cuenta = []; // Array para Nequi, Bancolombia, etc.

if ($fecha_apertura) {
    $stmt_apertura = $conn->prepare(
        "SELECT conteo_efectivo_cierre, conteo_transferencia_cierre
         FROM cierres_caja
         WHERE id_sede = ? AND fecha = ? LIMIT 1"
    );
    $stmt_apertura->bind_param("is", $id_sede_seleccionada, $fecha_apertura);
    $stmt_apertura->execute();
    $resultado_apertura = $stmt_apertura->get_result()->fetch_assoc();
    
    // Asignamos los saldos iniciales
    $saldo_efectivo_dia_siguiente = (float)($resultado_apertura['conteo_efectivo_cierre'] ?? 0);
    $saldo_transferencia_total = (float)($resultado_apertura['conteo_transferencia_cierre'] ?? 0);
    
    // Asignamos el saldo de transferencia a una cuenta genérica
    if ($saldo_transferencia_total > 0) {
        $saldos_por_cuenta['Saldo Anterior Cuentas'] = $saldo_transferencia_total;
    }
    
    $stmt_apertura->close();
}


// 4. OBTENER DATOS DE DETALLE (Todo en arrays)
$sede_nombre = "Sede General";
$stmt_sede = $conn->prepare("SELECT nombre FROM sedes WHERE id = ?");
$stmt_sede->bind_param("i", $id_sede_seleccionada);
$stmt_sede->execute();
$res_sede = $stmt_sede->get_result();
if ($fila_sede = $res_sede->fetch_assoc()) {
    $sede_nombre = $fila_sede['nombre'];
}
$stmt_sede->close();

// --- Ingresos ---
$sql_ingresos = "
    SELECT r.fecha_tramite, r.id, r.concepto_servicio, r.metodo_pago, r.detalle_pago, r.valor_servicio 
    FROM recibos r JOIN asesor a ON r.id_asesor = a.id_asesor
    WHERE r.fecha_tramite BETWEEN ? AND ? AND a.id_sede = ? AND r.estado IN ('completado', 'pendiente')
    ORDER BY r.fecha_tramite ASC, r.id ASC
";
$stmt_ingresos = $conn->prepare($sql_ingresos);
$stmt_ingresos->bind_param("ssi", $fecha_inicio, $fecha_fin, $id_sede_seleccionada);
$stmt_ingresos->execute();
$ingresos_data = $stmt_ingresos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_ingresos->close();

// --- Egresos ---
$sql_egresos = "
    SELECT e.fecha, e.recibo_id, e.descripcion, e.forma_pago, e.detalle_pago, e.monto
    FROM egresos e JOIN recibos r ON e.recibo_id = r.id JOIN asesor a ON r.id_asesor = a.id_asesor
    WHERE e.fecha BETWEEN ? AND ? AND a.id_sede = ? AND e.tipo = 'servicio'
    ORDER BY e.fecha ASC, e.id ASC
";
$stmt_egresos = $conn->prepare($sql_egresos);
$stmt_egresos->bind_param("ssi", $fecha_inicio, $fecha_fin, $id_sede_seleccionada);
$stmt_egresos->execute();
$egresos_data = $stmt_egresos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_egresos->close();

// --- ¡NUEVO! Movimientos Internos ---
$sql_movimientos = " (SELECT DATE(m.fecha) as fecha, 'Entrada' as tipo, m.monto, m.metodo_pago_destino as metodo, m.detalle_pago_destino as detalle, m.descripcion, s.nombre as sede_relacionada
                     FROM movimientos_inter_sede m
                     LEFT JOIN sedes s ON m.id_sede_origen = s.id
                     WHERE DATE(m.fecha) BETWEEN ? AND ? AND m.id_sede_destino = ?)
                   UNION ALL
                     (SELECT DATE(m.fecha) as fecha, 'Salida' as tipo, m.monto, m.metodo_pago_origen as metodo, m.detalle_pago_origen as detalle, m.descripcion, s.nombre as sede_relacionada
                     FROM movimientos_inter_sede m
                     LEFT JOIN sedes s ON m.id_sede_destino = s.id
                     WHERE DATE(m.fecha) BETWEEN ? AND ? AND m.id_sede_origen = ?)
                   ORDER BY fecha ASC";
$stmt_mov = $conn->prepare($sql_movimientos);
$stmt_mov->bind_param("ssissi", $fecha_inicio, $fecha_fin, $id_sede_seleccionada, $fecha_inicio, $fecha_fin, $id_sede_seleccionada);
$stmt_mov->execute();
$movimientos_data = $stmt_mov->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_mov->close();
// --- FIN NUEVO ---

// --- Cierres ---
$sql_cierres = "
    SELECT fecha, saldo_final, conteo_efectivo_cierre, conteo_transferencia_cierre, diferencia, notas
    FROM cierres_caja
    WHERE id_sede = ? AND fecha BETWEEN ? AND ?
";
$stmt_cierres = $conn->prepare($sql_cierres);
$stmt_cierres->bind_param("iss", $id_sede_seleccionada, $fecha_inicio, $fecha_fin);
$stmt_cierres->execute();
$cierres_result = $stmt_cierres->get_result();
$cierres_data = [];
while ($fila = $cierres_result->fetch_assoc()) {
    $cierres_data[$fila['fecha']] = $fila; // Clave por fecha
}
$stmt_cierres->close();
$conn->close();


// 5. CREAR Y "PINTAR" EL ARCHIVO EXCEL (LÓGICA DE SALDOS MODIFICADA)
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Informe Cronológico');

// --- (Definición de Estilos - Sin cambios) ---
$currency_format = '$#,##0';
$style_borde_fino = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
$style_borde_grueso_inf = ['borders' => ['bottom' => ['borderStyle' => Border::BORDER_THICK, 'color' => ['rgb' => '000000']]]];
$style_fuente_negrita = ['font' => ['bold' => true]];

$style_titulo_principal = [
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1DE']]
];
$style_subtitulo_periodo = [
    'font' => ['bold' => false, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1DE']]
];
$style_header_dia = [
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A99']], // Azul oscuro
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$style_header_tabla = [
    'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']], // Azul claro
    'borders' => $style_borde_fino['borders']
];
$style_celda_datos = $style_borde_fino;
$style_total_dia = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']], // Amarillo
    'borders' => $style_borde_fino['borders']
];
$style_cierre_label = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']], // Gris claro
    'borders' => $style_borde_fino['borders']
];
$style_cierre_label_desglose = [
    'font' => ['bold' => false, 'italic' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2F2F2']],
    'borders' => $style_borde_fino['borders']
];
$style_cierre_valor = [
    'borders' => $style_borde_fino['borders']
];
$style_diferencia_negativa = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FF0000']], // Rojo
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC7CE']], // Rosa claro
    'borders' => $style_borde_fino['borders']
];
$style_diferencia_positiva = [
    'font' => ['bold' => true, 'color' => ['rgb' => '006100']], // Verde oscuro
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C6EFCE']], // Verde claro
    'borders' => $style_borde_fino['borders']
];
$style_notas = [
    'font' => ['italic' => true],
    'alignment' => ['wrapText' => true],
    'borders' => $style_borde_fino['borders']
];
$style_no_cierre = [
    'font' => ['bold' => true, 'italic' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF2CC']] // Amarillo pálido
];
// --- NUEVOS ESTILOS PARA RESUMEN SUPERIOR ---
$style_resumen_header = [
    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B050']], // Verde
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => $style_borde_fino['borders']
];
$style_resumen_label = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
    'borders' => $style_borde_fino['borders']
];
$style_resumen_label_cuenta = [
    'font' => ['italic' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
    'borders' => $style_borde_fino['borders']
];
$style_resumen_valor = [
    'font' => ['bold' => true],
    'borders' => $style_borde_fino['borders']
];
$style_resumen_valor_cuenta = [
    'borders' => $style_borde_fino['borders']
];


// --- Títulos Generales ---
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'Seguros & Servicios');
$sheet->getStyle('A1:F1')->applyFromArray($style_titulo_principal);
$sheet->getRowDimension('1')->setRowHeight(25);

$sheet->mergeCells('A2:F2');
$titulo_rango = 'Oficina: ' . htmlspecialchars($sede_nombre) . ' (Periodo: ' . $fecha_inicio . ' al ' . $fecha_fin . ')';
$sheet->setCellValue('A2', $titulo_rango);
$sheet->getStyle('A2:F2')->applyFromArray($style_subtitulo_periodo);
$sheet->getRowDimension('2')->setRowHeight(22);


// --- BUCLE MAESTRO POR DÍA ---
$start_date = new DateTime($fecha_inicio);
$end_date = new DateTime($fecha_fin);
$end_date->modify('+1 day');
$interval = new DatePeriod($start_date, new DateInterval('P1D'), $end_date);

$row_num = 5; // Empezamos a pintar desde la fila 5

foreach ($interval as $date_obj) {
    $current_date_str = $date_obj->format('Y-m-d');
    
    // Reseteamos totales diarios
    $total_ingresos_dia_efectivo = 0;
    $total_ingresos_dia_bancos = 0;
    $total_egresos_dia_efectivo = 0;
    $total_egresos_dia_bancos = 0;
    // --- ¡NUEVO! Totales de movimientos ---
    $total_mov_in_efectivo = 0;
    $total_mov_in_bancos = 0;
    $total_mov_out_efectivo = 0;
    $total_mov_out_bancos = 0;

    // --- 1. CABECERA DEL DÍA Y SALDO INICIAL ---
    $sheet->mergeCells("A{$row_num}:F{$row_num}");
    $sheet->setCellValue("A{$row_num}", "MOVIMIENTOS DEL DÍA: " . $date_obj->format('d/m/Y'));
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_header_dia);
    $row_num++;

    // --- SALDO INICIAL DESGLOSADO ---
    $saldo_transferencia_total_dia_siguiente = 0.0;
    foreach($saldos_por_cuenta as $monto) {
        $saldo_transferencia_total_dia_siguiente += $monto;
    }
    $caja_inicial_total_dia = $saldo_efectivo_dia_siguiente + $saldo_transferencia_total_dia_siguiente;

    $sheet->mergeCells("A{$row_num}:E{$row_num}");
    $sheet->setCellValue("A{$row_num}", "SALDO INICIAL TOTAL (Conteo Cierre Anterior):");
    $sheet->getStyle("A{$row_num}:E{$row_num}")->applyFromArray($style_cierre_label);
    $sheet->setCellValue("F{$row_num}", $caja_inicial_total_dia);
    $sheet->getStyle("F{$row_num}")->applyFromArray($style_cierre_valor);
    $sheet->getStyle("F{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;
    
    // Desglose Saldo Inicial
    $sheet->mergeCells("B{$row_num}:E{$row_num}");
    $sheet->setCellValue("B{$row_num}", "Efectivo Inicial:");
    $sheet->getStyle("B{$row_num}:E{$row_num}")->applyFromArray($style_cierre_label_desglose);
    $sheet->setCellValue("F{$row_num}", $saldo_efectivo_dia_siguiente);
    $sheet->getStyle("F{$row_num}")->applyFromArray($style_cierre_valor);
    $sheet->getStyle("F{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;
    
    $sheet->mergeCells("B{$row_num}:E{$row_num}");
    $sheet->setCellValue("B{$row_num}", "Transferencia Inicial (Total):");
    $sheet->getStyle("B{$row_num}:E{$row_num}")->applyFromArray($style_cierre_label_desglose);
    $sheet->setCellValue("F{$row_num}", $saldo_transferencia_total_dia_siguiente);
    $sheet->getStyle("F{$row_num}")->applyFromArray($style_cierre_valor);
    $sheet->getStyle("F{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;
    $row_num++; // Espaciador

    // --- 2. INGRESOS DEL DÍA ---
    $sheet->setCellValue('A' . $row_num, 'INGRESOS');
    $sheet->getStyle('A' . $row_num)->applyFromArray($style_fuente_negrita);
    $row_num++;
    $sheet->setCellValue('A' . $row_num, 'FECHA');
    $sheet->setCellValue('B' . $row_num, 'RECIBO No');
    $sheet->setCellValue('C' . $row_num, 'DESCRIPCIÓN');
    $sheet->setCellValue('D' . $row_num, 'EFECTIVO');
    $sheet->setCellValue('E' . $row_num, 'BANCOS');
    $sheet->setCellValue('F' . $row_num, 'ENTIDAD');
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_header_tabla);
    $row_num++;

    $ingresos_del_dia = array_filter($ingresos_data, function($ingreso) use ($current_date_str) {
        return $ingreso['fecha_tramite'] == $current_date_str;
    });

    if (empty($ingresos_del_dia)) {
        $sheet->mergeCells("A{$row_num}:F{$row_num}");
        $sheet->setCellValue("A{$row_num}", "No se registraron ingresos este día.");
        $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_celda_datos);
        $row_num++;
    } else {
        foreach ($ingresos_del_dia as $fila) {
            $sheet->setCellValue('A' . $row_num, $fila['fecha_tramite']);
            $sheet->setCellValue('B' . $row_num, $fila['id']);
            $sheet->setCellValue('C' . $row_num, $fila['concepto_servicio']);
            
            $valor_servicio = (float)$fila['valor_servicio'];
            
            if ($fila['metodo_pago'] == 'efectivo') {
                $sheet->setCellValue('D' . $row_num, $valor_servicio);
                $total_ingresos_dia_efectivo += $valor_servicio;
            } else {
                $sheet->setCellValue('E' . $row_num, $valor_servicio);
                $cuenta = $fila['detalle_pago'] ?? $fila['metodo_pago'];
                $sheet->setCellValue('F' . $row_num, $cuenta);
                $total_ingresos_dia_bancos += $valor_servicio;
                
                // --- Actualizar saldo por cuenta ---
                if (!isset($saldos_por_cuenta[$cuenta])) {
                    $saldos_por_cuenta[$cuenta] = 0.0;
                }
                $saldos_por_cuenta[$cuenta] += $valor_servicio; // SUMAR ingreso
            }
            $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_celda_datos);
            $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
            $row_num++;
        }
    }
    // Total Ingresos Día
    $sheet->mergeCells("A{$row_num}:C{$row_num}");
    $sheet->setCellValue("A{$row_num}", "Total Entradas del Día:");
    $sheet->setCellValue('D' . $row_num, $total_ingresos_dia_efectivo);
    $sheet->setCellValue('E' . $row_num, $total_ingresos_dia_bancos);
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_total_dia);
    $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;
    $row_num++; // Espaciador

    // --- 3. EGRESOS DEL DÍA ---
    $sheet->setCellValue('A' . $row_num, 'EGRESOS');
    $sheet->getStyle('A' . $row_num)->applyFromArray($style_fuente_negrita);
    $row_num++;
    $sheet->setCellValue('A' . $row_num, 'FECHA');
    $sheet->setCellValue('B' . $row_num, 'RECIBO No');
    $sheet->setCellValue('C' . $row_num, 'DESCRIPCIÓN');
    $sheet->setCellValue('D' . $row_num, 'EFECTIVO');
    $sheet->setCellValue('E' . $row_num, 'BANCOS');
    $sheet->setCellValue('F' . $row_num, 'ENTIDAD');
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_header_tabla);
    $row_num++;

    $egresos_del_dia = array_filter($egresos_data, function($egreso) use ($current_date_str) {
        return $egreso['fecha'] == $current_date_str;
    });

    if (empty($egresos_del_dia)) {
        $sheet->mergeCells("A{$row_num}:F{$row_num}");
        $sheet->setCellValue("A{$row_num}", "No se registraron egresos este día.");
        $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_celda_datos);
        $row_num++;
    } else {
        foreach ($egresos_del_dia as $fila) {
            $sheet->setCellValue('A' . $row_num, $fila['fecha']);
            $sheet->setCellValue('B' . $row_num, $fila['recibo_id']);
            $sheet->setCellValue('C' . $row_num, $fila['descripcion']);
            
            $monto = (float)$fila['monto'];

            if ($fila['forma_pago'] == 'efectivo') {
                $sheet->setCellValue('D' . $row_num, $monto);
                $total_egresos_dia_efectivo += $monto;
            } else {
                $sheet->setCellValue('E' . $row_num, $monto);
                $cuenta = $fila['detalle_pago'] ?? $fila['forma_pago'];
                $sheet->setCellValue('F' . $row_num, $cuenta);
                $total_egresos_dia_bancos += $monto;
                
                // --- Actualizar saldo por cuenta ---
                if (!isset($saldos_por_cuenta[$cuenta])) {
                    $saldos_por_cuenta[$cuenta] = 0.0;
                }
                $saldos_por_cuenta[$cuenta] -= $monto; // RESTAR egreso
            }
            $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_celda_datos);
            $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
            $row_num++;
        }
    }
    // Total Egresos Día
    $sheet->mergeCells("A{$row_num}:C{$row_num}");
    $sheet->setCellValue("A{$row_num}", "Total Salidas del Día:");
    $sheet->setCellValue('D' . $row_num, $total_egresos_dia_efectivo);
    $sheet->setCellValue('E' . $row_num, $total_egresos_dia_bancos);
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_total_dia);
    $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;
    $row_num++; // Espaciador
    

    // --- 4. ¡NUEVO! MOVIMIENTOS INTERNOS DEL DÍA ---
    $sheet->setCellValue('A' . $row_num, 'MOVIMIENTOS INTERNOS');
    $sheet->getStyle('A' . $row_num)->applyFromArray($style_fuente_negrita);
    $row_num++;
    $sheet->setCellValue('A' . $row_num, 'FECHA');
    $sheet->setCellValue('B' . $row_num, 'TIPO');
    $sheet->setCellValue('C' . $row_num, 'DESCRIPCIÓN');
    $sheet->setCellValue('D' . $row_num, 'EFECTIVO');
    $sheet->setCellValue('E' . $row_num, 'BANCOS');
    $sheet->setCellValue('F' . $row_num, 'CUENTA/DETALLE');
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_header_tabla);
    $row_num++;

    $movimientos_del_dia = array_filter($movimientos_data, function($mov) use ($current_date_str) {
        return $mov['fecha'] == $current_date_str;
    });

    if (empty($movimientos_del_dia)) {
        $sheet->mergeCells("A{$row_num}:F{$row_num}");
        $sheet->setCellValue("A{$row_num}", "No se registraron movimientos internos este día.");
        $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_celda_datos);
        $row_num++;
    } else {
        foreach ($movimientos_del_dia as $fila) {
            $sheet->setCellValue('A' . $row_num, $fila['fecha']);
            $sheet->setCellValue('B' . $row_num, $fila['tipo']);
            $desc = $fila['descripcion'] . ' (Sede: ' . ($fila['sede_relacionada'] ?? 'N/A') . ')';
            $sheet->setCellValue('C' . $row_num, $desc);
            
            $monto = (float)$fila['monto'];
            $cuenta = $fila['detalle'] ?? $fila['metodo'];
            
            if ($fila['tipo'] == 'Entrada') {
                if ($fila['metodo'] == 'efectivo') {
                    $sheet->setCellValue('D' . $row_num, $monto);
                    $total_mov_in_efectivo += $monto;
                } else {
                    $sheet->setCellValue('E' . $row_num, $monto);
                    $sheet->setCellValue('F' . $row_num, $cuenta);
                    $total_mov_in_bancos += $monto;
                    // Actualizar saldo cuenta
                    if (!isset($saldos_por_cuenta[$cuenta])) $saldos_por_cuenta[$cuenta] = 0.0;
                    $saldos_por_cuenta[$cuenta] += $monto; // SUMAR
                }
            } else { // 'Salida'
                if ($fila['metodo'] == 'efectivo') {
                    $sheet->setCellValue('D' . $row_num, -$monto); // Salida en negativo
                    $total_mov_out_efectivo += $monto;
                } else {
                    $sheet->setCellValue('E' . $row_num, -$monto); // Salida en negativo
                    $sheet->setCellValue('F' . $row_num, $cuenta);
                    $total_mov_out_bancos += $monto;
                    // Actualizar saldo cuenta
                    if (!isset($saldos_por_cuenta[$cuenta])) $saldos_por_cuenta[$cuenta] = 0.0;
                    $saldos_por_cuenta[$cuenta] -= $monto; // RESTAR
                }
            }
            $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_celda_datos);
            $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
            $row_num++;
        }
    }
    // Total Movimientos Día
    $sheet->mergeCells("A{$row_num}:C{$row_num}");
    $sheet->setCellValue("A{$row_num}", "Total Entradas Internas del Día:");
    $sheet->setCellValue('D' . $row_num, $total_mov_in_efectivo);
    $sheet->setCellValue('E' . $row_num, $total_mov_in_bancos);
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_total_dia);
    $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;

    $sheet->mergeCells("A{$row_num}:C{$row_num}");
    $sheet->setCellValue("A{$row_num}", "Total Salidas Internas del Día:");
    $sheet->setCellValue('D' . $row_num, -$total_mov_out_efectivo);
    $sheet->setCellValue('E' . $row_num, -$total_mov_out_bancos);
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_total_dia);
    $sheet->getStyle("D{$row_num}:E{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
    $row_num++;
    $row_num++; // Espaciador
    // --- FIN NUEVA SECCIÓN ---


    // --- 5. CIERRE DEL DÍA ---
    $sheet->mergeCells("A{$row_num}:F{$row_num}");
    $sheet->setCellValue("A{$row_num}", "CIERRE DEL DÍA: " . $date_obj->format('d/m/Y'));
    $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_header_dia);
    $sheet->getStyle("A{$row_num}:F{$row_num}")->getFill()->getStartColor()->setRGB('00B050'); // Verde
    $row_num++;

    // --- ¡MODIFICADO! Cálculo de saldo esperado ahora incluye movimientos ---
    $saldo_esperado_efectivo_dia = $saldo_efectivo_dia_siguiente + $total_ingresos_dia_efectivo - $total_egresos_dia_efectivo + $total_mov_in_efectivo - $total_mov_out_efectivo;
    
    $saldo_esperado_transferencia_dia = 0.0;
    foreach($saldos_por_cuenta as $monto) {
        $saldo_esperado_transferencia_dia += $monto;
    }
    $saldo_esperado_total_dia = $saldo_esperado_efectivo_dia + $saldo_esperado_transferencia_dia;


    if (isset($cierres_data[$current_date_str])) {
        $cierre = $cierres_data[$current_date_str];
        $conteo_registrado_efectivo = (float)$cierre['conteo_efectivo_cierre'];
        $conteo_registrado_transferencia = (float)$cierre['conteo_transferencia_cierre'];
        $conteo_registrado_total = $conteo_registrado_efectivo + $conteo_registrado_transferencia;
        
        // ¡CUIDADO! La diferencia en la BD puede ser incorrecta si no incluía movimientos.
        // La recalculamos aquí para el Excel.
        $diferencia_calculada = $conteo_registrado_total - $saldo_esperado_total_dia;

        // Saldo Esperado (Calculado)
        $sheet->mergeCells("A{$row_num}:E{$row_num}");
        $sheet->setCellValue("A{$row_num}", "Saldo Esperado (Calculado):");
        $sheet->getStyle("A{$row_num}:E{$row_num}")->applyFromArray($style_cierre_label);
        $sheet->setCellValue("F{$row_num}", $saldo_esperado_total_dia);
        $sheet->getStyle("F{$row_num}")->applyFromArray($style_cierre_valor);
        $sheet->getStyle("F{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
        $row_num++;

        // Conteo Registrado
        $sheet->mergeCells("A{$row_num}:E{$row_num}");
        $sheet->setCellValue("A{$row_num}", "Conteo Registrado (Total):");
        $sheet->getStyle("A{$row_num}:E{$row_num}")->applyFromArray($style_cierre_label);
        $sheet->setCellValue("F{$row_num}", $conteo_registrado_total);
        $sheet->getStyle("F{$row_num}")->applyFromArray($style_cierre_valor);
        $sheet->getStyle("F{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
        $row_num++;
        
        // Diferencia de Caja
        $sheet->mergeCells("A{$row_num}:E{$row_num}");
        $sheet->setCellValue("A{$row_num}", "Diferencia de Caja (Calculada):");
        $sheet->getStyle("A{$row_num}:E{$row_num}")->applyFromArray($style_cierre_label);
        $sheet->setCellValue("F{$row_num}", $diferencia_calculada);
        if ($diferencia_calculada < 0) $sheet->getStyle("F{$row_num}")->applyFromArray($style_diferencia_negativa);
        else if ($diferencia_calculada > 0) $sheet->getStyle("F{$row_num}")->applyFromArray($style_diferencia_positiva);
        else $sheet->getStyle("F{$row_num}")->applyFromArray($style_cierre_valor);
        $sheet->getStyle("F{$row_num}")->getNumberFormat()->setFormatCode($currency_format);
        $row_num++;
        
        // Notas
        $sheet->mergeCells("A{$row_num}:F{$row_num}");
        $sheet->setCellValue("A{$row_num}", "Notas del Cierre: \n" . ($cierre['notas'] ?? 'Sin notas.'));
        $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_notas);
        $sheet->getRowDimension($row_num)->setRowHeight(40);
        $row_num++;

        // Asignamos los CONTEOS del cierre como el saldo inicial del próximo día
        $saldo_efectivo_dia_siguiente = $conteo_registrado_efectivo;
        
        // Reseteamos saldos por cuenta y asignamos el total de transferencia
        $saldos_por_cuenta = []; 
        $saldos_por_cuenta['Saldo Cierre Anterior'] = $conteo_registrado_transferencia;

    } else {
        // No hay cierre
        $sheet->mergeCells("A{$row_num}:F{$row_num}");
        $sheet->setCellValue("A{$row_num}", "Caja de este día no fue cerrada.");
        $sheet->getStyle("A{$row_num}:F{$row_num}")->applyFromArray($style_no_cierre);
        $row_num++;

        // Si no hubo cierre, el saldo del próximo día es el SALDO ESPERADO de este día
        $saldo_efectivo_dia_siguiente = $saldo_esperado_efectivo_dia;
        // Los saldos por cuenta ya se actualizaron, así que se arrastran
    }

    $sheet->getStyle("A".($row_num-1).":F".($row_num-1))->applyFromArray($style_borde_grueso_inf);
    $row_num++;
} 


// --- SECCIÓN 6: RESUMEN FINAL EN LA PARTE SUPERIOR (MODIFICADO) ---
// Esta sección ahora es DINÁMICA y se basa en los saldos finales calculados.
$resumen_row = 1;
$sheet->mergeCells("H{$resumen_row}:I{$resumen_row}");
$sheet->setCellValue("H{$resumen_row}", "RESUMEN DE CAJA FINAL");
$sheet->getStyle("H{$resumen_row}:I{$resumen_row}")->applyFromArray($style_resumen_header);
$resumen_row++;

// Efectivo Final (es el último $saldo_efectivo_dia_siguiente calculado)
$sheet->setCellValue("H{$resumen_row}", "CAJA EFECTIVO:");
$sheet->getStyle("H{$resumen_row}")->applyFromArray($style_resumen_label);
$sheet->setCellValue("I{$resumen_row}", $saldo_efectivo_dia_siguiente);
$sheet->getStyle("I{$resumen_row}")->applyFromArray($style_resumen_valor);
$sheet->getStyle("I{$resumen_row}")->getNumberFormat()->setFormatCode($currency_format);
$resumen_row++;

// Transferencia Total Final
$saldo_transferencia_final_total = 0.0;
foreach($saldos_por_cuenta as $monto) {
    $saldo_transferencia_final_total += $monto;
}
$sheet->setCellValue("H{$resumen_row}", "CAJA TRANSFERENCIA:");
$sheet->getStyle("H{$resumen_row}")->applyFromArray($style_resumen_label);
$sheet->setCellValue("I{$resumen_row}", $saldo_transferencia_final_total);
$sheet->getStyle("I{$resumen_row}")->applyFromArray($style_resumen_valor);
$sheet->getStyle("I{$resumen_row}")->getNumberFormat()->setFormatCode($currency_format);
$resumen_row++;

// Desglose de Cuentas Final
foreach ($saldos_por_cuenta as $cuenta => $monto) {
    if ($monto == 0) continue; // No mostrar saldos en cero
    $sheet->setCellValue("H{$resumen_row}", $cuenta . ":");
    $sheet->getStyle("H{$resumen_row}")->applyFromArray($style_resumen_label_cuenta);
    $sheet->setCellValue("I{$resumen_row}", $monto);
    $sheet->getStyle("I{$resumen_row}")->applyFromArray($style_resumen_valor_cuenta);
    $sheet->getStyle("I{$resumen_row}")->getNumberFormat()->setFormatCode($currency_format);
    $resumen_row++;
}

// Gran Total
$gran_total = $saldo_efectivo_dia_siguiente + $saldo_transferencia_final_total;
$sheet->setCellValue("H{$resumen_row}", "GRAN TOTAL:");
$sheet->getStyle("H{$resumen_row}")->applyFromArray($style_resumen_label);
$sheet->getStyle("H{$resumen_row}")->getFill()->getStartColor()->setRGB('FFFF00'); // Amarillo
$sheet->setCellValue("I{$resumen_row}", $gran_total);
$sheet->getStyle("I{$resumen_row}")->applyFromArray($style_resumen_valor);
$sheet->getStyle("I{$resumen_row}")->getFill()->getStartColor()->setRGB('FFFF00'); // Amarillo
$sheet->getStyle("I{$resumen_row}")->getNumberFormat()->setFormatCode($currency_format);


// --- Ajustar anchos de columna (Incluyendo el resumen) ---
$sheet->getColumnDimension('A')->setWidth(15); // Fecha
$sheet->getColumnDimension('B')->setWidth(15); // Recibo No / Tipo
$sheet->getColumnDimension('C')->setWidth(45); // Descripción (más ancha)
$sheet->getColumnDimension('D')->setWidth(20); // Efectivo
$sheet->getColumnDimension('E')->setWidth(20); // Bancos
$sheet->getColumnDimension('F')->setWidth(25); // Entidad / Valores
// Columnas de Resumen
$sheet->getColumnDimension('G')->setWidth(2);  // Separador
$sheet->getColumnDimension('H')->setWidth(25); // Etiquetas Resumen
$sheet->getColumnDimension('I')->setWidth(20); // Valores Resumen


// 7. ENVIAR EL ARCHIVO AL NAVEGADOR
ob_clean(); // <-- Limpia el búfer (borra cualquier error o espacio)
$spreadsheet->setActiveSheetIndex(0); // Asegurarse de que el usuario vea la primera hoja
$filename = "detalle_caja_cronologico_" . $fecha_inicio . "_al_" . $fecha_fin . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit();
?>