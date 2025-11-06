<?php
// finanzas/views/exportar_detalle_caja.php

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

// Forzar que se muestren los errores (¡quitar en producción!)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 2. OBTENER FILTROS (MODIFICADO)
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$id_sede_seleccionada = isset($_GET['id_sede']) ? intval($_GET['id_sede']) : 1;

// 3. OBTENER CAJA INICIAL (MODIFICADO)
// La caja inicial se calcula basada en el día ANTERIOR al $fecha_inicio
$stmt_last_fecha = $conn->prepare(
    "SELECT MAX(fecha) AS fecha
     FROM cierres_caja
     WHERE id_sede = ? 
       AND fecha < ?  -- Usamos fecha_inicio como referencia
       AND (total_ingresos <> 0 OR total_egresos <> 0 OR conteo_efectivo_cierre <> 0 OR conteo_transferencia_cierre <> 0)"
);
$stmt_last_fecha->bind_param("is", $id_sede_seleccionada, $fecha_inicio); // <-- Usamos $fecha_inicio
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
$caja_inicial_total = $saldo_apertura_efectivo + $saldo_apertura_transferencia;


// 4. OBTENER DATOS DE DETALLE
// --- Nombre de la Sede ---
$sede_nombre = "Sede General";
$stmt_sede = $conn->prepare("SELECT nombre FROM sedes WHERE id = ?");
$stmt_sede->bind_param("i", $id_sede_seleccionada);
$stmt_sede->execute();
$res_sede = $stmt_sede->get_result();
if ($fila_sede = $res_sede->fetch_assoc()) {
    $sede_nombre = $fila_sede['nombre'];
}
$stmt_sede->close();

// --- Lista de Ingresos (Recibos) - MODIFICADO ---
$sql_ingresos = "
    SELECT r.fecha_tramite, r.id, r.concepto_servicio, r.metodo_pago, r.detalle_pago, r.valor_servicio 
    FROM recibos r 
    JOIN asesor a ON r.id_asesor = a.id_asesor
    WHERE r.fecha_tramite BETWEEN ? AND ? -- Rango de fechas
      AND a.id_sede = ? 
      AND r.estado IN ('completado', 'pendiente')
    ORDER BY r.fecha_tramite ASC, r.id ASC
";
$stmt_ingresos = $conn->prepare($sql_ingresos);
$stmt_ingresos->bind_param("ssi", $fecha_inicio, $fecha_fin, $id_sede_seleccionada);
$stmt_ingresos->execute();
$ingresos_result = $stmt_ingresos->get_result();

// --- Lista de Egresos (Servicio) - MODIFICADO ---
$sql_egresos = "
    SELECT e.fecha, e.recibo_id, e.descripcion, e.forma_pago, e.detalle_pago, e.monto
    FROM egresos e
    JOIN recibos r ON e.recibo_id = r.id
    JOIN asesor a ON r.id_asesor = a.id_asesor
    WHERE e.fecha BETWEEN ? AND ? -- Rango de fechas
      AND a.id_sede = ? 
      AND e.tipo = 'servicio'
    ORDER BY e.fecha ASC, e.id ASC
";
$stmt_egresos = $conn->prepare($sql_egresos);
$stmt_egresos->bind_param("ssi", $fecha_inicio, $fecha_fin, $id_sede_seleccionada);
$stmt_egresos->execute();
$egresos_result = $stmt_egresos->get_result();

// (Aquí es donde añadiríamos la consulta para Egresos TIPO GASTO/PRESTAMO cuando la tengas)


// 5. CREAR Y "PINTAR" EL ARCHIVO EXCEL
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// --- Definir Estilos ---
$style_verde_claro = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1DE']]];
$style_amarillo = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']]];
$style_borde_negro_fino = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '000000']
        ]
    ]
];
$style_titulo_principal = [
    'font' => ['bold' => false, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => $style_verde_claro['fill']
];
$style_subtitulo = [
    'font' => ['bold' => false, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => $style_verde_claro['fill']
];
$style_encabezado_tabla = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A99']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]]
];
$style_total_final = [
    'font' => ['bold' => true],
    'fill' => $style_amarillo['fill'],
    'borders' => $style_borde_negro_fino['borders'] // <-- Borde añadido
];
// --- NUEVO: Estilo para las celdas de datos ---
$style_cuadricula = [
    'borders' => $style_borde_negro_fino['borders']
];
$currency_format = '$#,##0';


// --- Títulos y Caja Inicial (MODIFICADO) ---
$sheet->mergeCells('A1:F1');
$sheet->setCellValue('A1', 'Seguros & Servicios');
$sheet->getStyle('A1:F1')->applyFromArray($style_titulo_principal); // Aplicado al rango mergeado
$sheet->getRowDimension('1')->setRowHeight(25);

$sheet->mergeCells('A2:F2');
$titulo_rango = 'Oficina: ' . htmlspecialchars($sede_nombre) . ' (Periodo: ' . $fecha_inicio . ' al ' . $fecha_fin . ')';
$sheet->setCellValue('A2', $titulo_rango);
$sheet->getStyle('A2:F2')->applyFromArray($style_subtitulo); // Aplicado al rango mergeado
$sheet->getRowDimension('2')->setRowHeight(22);

$sheet->setCellValue('E3', 'CAJA INICIAL (Al ' . $fecha_inicio . ')');
$sheet->setCellValue('F3', $caja_inicial_total);
$sheet->getStyle('E3:F3')->applyFromArray($style_total_final);
$sheet->getStyle('F3')->getNumberFormat()->setFormatCode($currency_format);


// --- SECCIÓN 1: INGRESOS ---
$sheet->mergeCells('A5:F5');
$sheet->setCellValue('A5', 'REGISTRO DE ENTRADAS');
$sheet->getStyle('A5:F5')->applyFromArray($style_encabezado_tabla);
$sheet->getRowDimension('5')->setRowHeight(20);

$sheet->setCellValue('A6', 'FECHA');
$sheet->setCellValue('B6', 'RECIBO No');
$sheet->setCellValue('C6', 'DESCRIPCIÓN');
$sheet->setCellValue('D6', 'INGRESOS EN EFECTIVO');
$sheet->setCellValue('E6', 'INGRESOS POR BANCOS');
$sheet->setCellValue('F6', 'ENTIDAD BANCARIA');
$sheet->getStyle('A6:F6')->applyFromArray($style_encabezado_tabla);
$sheet->getRowDimension('6')->setRowHeight(20);

$row_num = 7; // Fila donde empiezan los datos de ingresos
$start_row_ingresos = $row_num; // Guardamos la fila inicial
$total_ingreso_efectivo = 0;
$total_ingreso_bancos = 0;

while ($fila = $ingresos_result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row_num, $fila['fecha_tramite']);
    $sheet->setCellValue('B' . $row_num, $fila['id']);
    $sheet->setCellValue('C' . $row_num, $fila['concepto_servicio']);
    
    if ($fila['metodo_pago'] == 'efectivo') {
        $sheet->setCellValue('D' . $row_num, $fila['valor_servicio']);
        $total_ingreso_efectivo += $fila['valor_servicio'];
    } else {
        $sheet->setCellValue('E' . $row_num, $fila['valor_servicio']);
        $sheet->setCellValue('F' . $row_num, $fila['detalle_pago'] ?? $fila['metodo_pago']);
        $total_ingreso_bancos += $fila['valor_servicio'];
    }
    $row_num++;
}
// Aplicar formato de moneda Y cuadrícula (solo si hay datos)
if ($row_num > $start_row_ingresos) {
    $rango_datos = 'A' . $start_row_ingresos . ':F' . ($row_num - 1);
    $rango_moneda = 'D' . $start_row_ingresos . ':E' . ($row_num - 1);
    
    $sheet->getStyle($rango_moneda)->getNumberFormat()->setFormatCode($currency_format);
    $sheet->getStyle($rango_datos)->applyFromArray($style_cuadricula); // <-- APLICAMOS CUADRÍCULA
}

// Totales de Ingresos
$sheet->setCellValue('C' . $row_num, 'TOTAL ENTRADAS');
$sheet->setCellValue('D' . $row_num, $total_ingreso_efectivo);
$sheet->setCellValue('E' . $row_num, $total_ingreso_bancos);
$sheet->getStyle('C' . $row_num . ':F' . $row_num)->applyFromArray($style_total_final); // Aplicamos amarillo y borde
$sheet->getStyle('D' . $row_num . ':E' . $row_num)->getNumberFormat()->setFormatCode($currency_format);


// --- SECCIÓN 2: EGRESOS ---
$row_num += 2; // Dejamos un espacio
$header_egresos_row = $row_num;
$sheet->mergeCells('A' . $header_egresos_row . ':F' . $header_egresos_row);
$sheet->setCellValue('A' . $header_egresos_row, 'REGISTRO DE SALIDAS');
$sheet->getStyle('A' . $header_egresos_row . ':F' . $header_egresos_row)->applyFromArray($style_encabezado_tabla);
$sheet->getRowDimension($header_egresos_row)->setRowHeight(20);

$row_num++;
$sheet->setCellValue('A' . $row_num, 'FECHA');
$sheet->setCellValue('B' . $row_num, 'RECIBO No');
$sheet->setCellValue('C' . $row_num, 'DESCRIPCIÓN');
$sheet->setCellValue('D' . $row_num, 'PAGOS EN EFECTIVO');
$sheet->setCellValue('E' . $row_num, 'PAGOS POR TRANSFERENCIA');
$sheet->setCellValue('F' . $row_num, 'ENTIDAD BANCARIA');
$sheet->getStyle('A' . $row_num . ':F' . $row_num)->applyFromArray($style_encabezado_tabla);
$sheet->getRowDimension($row_num)->setRowHeight(20);

$row_num++; // Fila donde empiezan los datos de egresos
$total_egreso_efectivo = 0;
$total_egreso_bancos = 0;
$start_row_egresos = $row_num; // Guardamos la fila inicial de datos

while ($fila = $egresos_result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row_num, $fila['fecha']);
    $sheet->setCellValue('B' . $row_num, $fila['recibo_id']);
    $sheet->setCellValue('C' . $row_num, $fila['descripcion']);
    
    if ($fila['forma_pago'] == 'efectivo') {
        $sheet->setCellValue('D' . $row_num, $fila['monto']);
        $total_egreso_efectivo += $fila['monto'];
    } else {
        $sheet->setCellValue('E' . $row_num, $fila['monto']);
        $sheet->setCellValue('F' . $row_num, $fila['detalle_pago'] ?? $fila['forma_pago']);
        $total_egreso_bancos += $fila['monto'];
    }
    $row_num++;
}
// Aplicar formato de moneda Y cuadrícula (solo si hay datos)
if ($row_num > $start_row_egresos) { 
    $rango_datos = 'A' . $start_row_egresos . ':F' . ($row_num - 1);
    $rango_moneda = 'D' . $start_row_egresos . ':E' . ($row_num - 1);

    $sheet->getStyle($rango_moneda)->getNumberFormat()->setFormatCode($currency_format);
    $sheet->getStyle($rango_datos)->applyFromArray($style_cuadricula); // <-- APLICAMOS CUADRÍCULA
}

// Totales de Egresos
$sheet->setCellValue('C' . $row_num, 'TOTAL SALIDAS');
$sheet->setCellValue('D' . $row_num, $total_egreso_efectivo);
$sheet->setCellValue('E' . $row_num, $total_egreso_bancos);
$sheet->getStyle('C' . $row_num . ':F' . $row_num)->applyFromArray($style_total_final); // Aplicamos amarillo y borde
$sheet->getStyle('D' . $row_num . ':E' . $row_num)->getNumberFormat()->setFormatCode($currency_format);


// --- SECCIÓN 3: CAJA FINAL ---
$row_num += 2; // Dejamos un espacio
$caja_final_total = $caja_inicial_total + ($total_ingreso_efectivo + $total_ingreso_bancos) - ($total_egreso_efectivo + $total_egreso_bancos);

$sheet->setCellValue('E' . $row_num, 'CAJA FINAL (Al ' . $fecha_fin . ')');
$sheet->setCellValue('F' . $row_num, $caja_final_total);
$sheet->getStyle('E' . $row_num . ':F' . $row_num)->applyFromArray($style_total_final); // Aplicamos amarillo y borde
$sheet->getStyle('F' . $row_num)->getNumberFormat()->setFormatCode($currency_format);


// --- Ajustar anchos de columna ---
$sheet->getColumnDimension('A')->setWidth(15); // Fecha
$sheet->getColumnDimension('B')->setWidth(12); // Recibo No
$sheet->getColumnDimension('C')->setWidth(45); // Descripción (más ancha)
$sheet->getColumnDimension('D')->setWidth(22); // Ingresos Efectivo
$sheet->getColumnDimension('E')->setWidth(22); // Ingresos Bancos
$sheet->getColumnDimension('F')->setWidth(25); // Entidad


// 6. ENVIAR EL ARCHIVO AL NAVEGADOR
$filename = "detalle_caja_" . $fecha_inicio . "_al_" . $fecha_fin . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

$conn->close();
exit();
?>