<?php
ob_start(); // Inicia el búfer de salida
// La ruta a vendor es ../ (sube de /recibos a /SyS-app y entra a /vendor)
require __DIR__ . '/../vendor/autoload.php'; 
// La ruta a models es interna ( /recibos/models/conexion.php )
include __DIR__ . '/models/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\RichText\RichText; // <-- ¡NUEVO! Para texto enriquecido

// 1. Obtener los filtros desde la URL (GET)
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fechaDesde'] ?? '';
$fechaHasta = $_GET['fechaHasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$id_sede = $_GET['id_sede'] ?? ''; 

// ==========================================================
// ¡CONSULTA SQL MODIFICADA!
// Añadimos r.detalle_pago y e_detalle_pago (agregado)
// ==========================================================
$sql = "
    SELECT 
        r.id, 
        r.fecha_tramite, 
        c.nombre_completo AS cliente, 
        v.placa, 
        r.concepto_servicio AS concepto,
        a.nombre AS asesor, 
        r.valor_servicio, 
        r.estado, 
        r.metodo_pago,
        r.detalle_pago AS r_detalle_pago, -- <--- NUEVO: Detalle de INGRESO
        (
            SELECT COALESCE(SUM(e.monto), 0)
            FROM egresos e
            WHERE e.recibo_id = r.id
              AND e.tipo = 'servicio' 
        ) AS valor_total_egresos,
        (
            SELECT GROUP_CONCAT(DISTINCT e.detalle_pago SEPARATOR ', ')
            FROM egresos e
            WHERE e.recibo_id = r.id
              AND e.tipo = 'servicio'
              AND e.forma_pago = 'transferencia'
              AND e.detalle_pago IS NOT NULL
        ) AS e_detalle_pago -- <--- NUEVO: Detalle de EGRESO
    FROM recibos r
    LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
    LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
    LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
";
$where = [];
$params = [];
$types = '';

if (!empty($estado)) {
    $where[] = "r.estado = ?";
    $params[] = $estado;
    $types .= 's';
}
if (!empty($fechaDesde)) {
    $where[] = "r.fecha_tramite >= ?";
    $params[] = $fechaDesde;
    $types .= 's';
}
if (!empty($fechaHasta)) {
    $where[] = "r.fecha_tramite <= ?";
    $params[] = $fechaHasta;
    $types .= 's';
}
if (!empty($busqueda)) {
    $where[] = "(c.nombre_completo LIKE ? OR v.placa LIKE ? OR a.nombre LIKE ?)";
    $likeParam = "%" . $busqueda . "%";
    $params[] = $likeParam;
    $params[] = $likeParam;
    $params[] = $likeParam;
    $types .= 'sss';
}
if (!empty($id_sede)) {
    $where[] = "a.id_sede = ?";
    $params[] = $id_sede;
    $types .= 'i';
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY r.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// --- Obtener nombre de la Sede (para el título) ---
$sede_nombre = "Reporte General"; // Default
if (!empty($id_sede)) {
    $stmt_sede = $conn->prepare("SELECT nombre FROM sedes WHERE id = ?");
    $stmt_sede->bind_param("i", $id_sede);
    $stmt_sede->execute();
    $res_sede = $stmt_sede->get_result();
    if ($fila_sede = $res_sede->fetch_assoc()) {
        $sede_nombre = $fila_sede['nombre'];
    }
    $stmt_sede->close();
}

// ==========================================================
// Crear el Excel
// ==========================================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reporte de Recibos');

// --- Definición de Estilos ---
$currency_format = '$#,##0';
$style_borde_fino = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
$style_titulo_principal = [
    'font' => ['bold' => true, 'size' => 18],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1DE']]
];
$style_subtitulo_periodo = [
    'font' => ['bold' => false, 'size' => 14],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF1DE']]
];
$style_header_tabla = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A99']], // Azul oscuro
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]]
];
$style_celda_datos = [
    'borders' => $style_borde_fino['borders'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];
$style_total_final = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']], // Amarillo
    'borders' => $style_borde_fino['borders'],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
];
$style_total_final_valor = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
    'borders' => $style_borde_fino['borders']
];
// ==========================================================
// ¡NUEVOS ESTILOS!
// ==========================================================
// Estilo para el valor principal (ej: $2.000.000)
$mainAmountStyle = new Font();
$mainAmountStyle->setBold(true)->setSize(11);

// Estilo para la sub-línea de cuenta (ej: ↪ BANCOLOMBIA)
$subAccountStyle = new Font();
$subAccountStyle->setBold(false)->setSize(9)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('555555'));
// ==========================================================


// --- Títulos Generales ---
$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', 'Liquidacion Tramites SyS');
$sheet->getStyle('A1:K1')->applyFromArray($style_titulo_principal);
$sheet->getRowDimension('1')->setRowHeight(30);

$sheet->mergeCells('A2:K2');
$sheet->setCellValue('A2', 'Oficina: ' . htmlspecialchars($sede_nombre));
$sheet->getStyle('A2:K2')->applyFromArray($style_subtitulo_periodo);
$sheet->getRowDimension('2')->setRowHeight(25);

// Crear texto de período
$periodo = '';
if (!empty($fechaDesde) && !empty($fechaHasta)) {
    $periodo = "Periodo: " . $fechaDesde . " al " . $fechaHasta;
} elseif (!empty($fechaDesde)) {
    $periodo = "Desde: " . $fechaDesde;
} elseif (!empty($fechaHasta)) {
    $periodo = "Hasta: " . $fechaHasta;
} else {
    $periodo = "Todos los registros";
}

$sheet->mergeCells('A3:K3');
$sheet->setCellValue('A3', $periodo);
$sheet->getStyle('A3:K3')->applyFromArray($style_subtitulo_periodo);
$sheet->getRowDimension('3')->setRowHeight(25);

// Encabezados de la tabla (fila 5)
$headers = [
    'ID Recibo', 'Fecha', 'Cliente', 'Placa', 'Concepto', 
    'Asesor', 'Valor Servicio', 'Estado', 'Método de Pago', 
    'Valor Egresos', 'Ganancia Neta'
];
$sheet->fromArray($headers, null, 'A5');
$sheet->getStyle('A5:K5')->applyFromArray($style_header_tabla);
$sheet->getRowDimension('5')->setRowHeight(20);

// --- Escribir datos ---
$filaExcel = 6;
$total_valor_servicio = 0;
$total_egresos = 0;
$total_ganancia = 0;

while ($fila = $resultado->fetch_assoc()) {
    $valor_servicio = (float)$fila['valor_servicio'];
    $valor_egresos = (float)$fila['valor_total_egresos'];
    $ganancia_neta = $valor_servicio - $valor_egresos;

    // Escribir datos estándar (G y J se sobrescriben luego)
    $sheet->fromArray([
        $fila['id'],
        $fila['fecha_tramite'],
        $fila['cliente'],
        $fila['placa'],
        $fila['concepto'],
        $fila['asesor'],
        $valor_servicio, // Columna G
        $fila['estado'],
        $fila['metodo_pago'],
        $valor_egresos,  // Columna J
        $ganancia_neta   // Columna K
    ], null, "A{$filaExcel}");
    
    // ==========================================================
    // ¡NUEVA LÓGICA DE CELDA ENRIQUECIDA!
    // ==========================================================
    
    // --- Celda G: Valor Servicio (Ingreso) ---
    $valorServicioCell = new RichText();
    // Línea 1: El valor
    $textRun = $valorServicioCell->createTextRun(NumberFormat::toFormattedString($valor_servicio, $currency_format));
    $textRun->setFont(clone $mainAmountStyle);
    
    // Línea 2: La cuenta (si es transferencia y existe)
    if ($fila['metodo_pago'] == 'transferencia' && !empty($fila['r_detalle_pago'])) {
        $valorServicioCell->createText("\n"); // Salto de línea
        $textRunAccount = $valorServicioCell->createTextRun('↪ ' . $fila['r_detalle_pago']);
        $textRunAccount->setFont(clone $subAccountStyle);
    }
    $sheet->getCell("G{$filaExcel}")->setValue($valorServicioCell);
    // Aplicar color verde al texto principal (el formato $currency_format se pierde con RichText, pero el color no)
    $sheet->getStyle("G{$filaExcel}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('008000'));
    
    // --- Celda J: Valor Egresos ---
    $valorEgresosCell = new RichText();
    // Línea 1: El valor
    $textRunE = $valorEgresosCell->createTextRun(NumberFormat::toFormattedString($valor_egresos, $currency_format));
    $textRunE->setFont(clone $mainAmountStyle);
    
    // Línea 2: La cuenta (si es transferencia y existe)
    // e_detalle_pago es el GROUP_CONCAT
    if (!empty($fila['e_detalle_pago'])) { 
        $valorEgresosCell->createText("\n"); // Salto de línea
        $textRunAccountE = $valorEgresosCell->createTextRun('↪ ' . $fila['e_detalle_pago']);
        $textRunAccountE->setFont(clone $subAccountStyle);
    }
    $sheet->getCell("J{$filaExcel}")->setValue($valorEgresosCell);
    // Aplicar color rojo al texto principal
    $sheet->getStyle("J{$filaExcel}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000'));
    
    // Aplicar formato de moneda solo a la ganancia neta (que no es RichText)
    $sheet->getStyle("K{$filaExcel}")->getNumberFormat()->setFormatCode($currency_format);
    // ==========================================================

    // Sumar a totales
    $total_valor_servicio += $valor_servicio;
    $total_egresos += $valor_egresos;
    $total_ganancia += $ganancia_neta;

    $filaExcel++;
}

// Aplicar estilos a las celdas de datos
$rango_datos = "A6:K" . ($filaExcel - 1);
$sheet->getStyle($rango_datos)->applyFromArray($style_celda_datos);
$sheet->getStyle($rango_datos)->getAlignment()->setWrapText(true); // ¡Importante para RichText!

// Aplicar formato de moneda (SOLO a K, G y J se manejan arriba)
$sheet->getStyle("K6:K" . ($filaExcel - 1))->getNumberFormat()->setFormatCode($currency_format);

// --- Escribir fila de Totales ---
$sheet->mergeCells("A{$filaExcel}:F{$filaExcel}");
$sheet->setCellValue("A{$filaExcel}", "TOTALES:");
$sheet->getStyle("A{$filaExcel}:F{$filaExcel}")->applyFromArray($style_total_final);

// Valores de totales
$sheet->setCellValue("G{$filaExcel}", $total_valor_servicio);
$sheet->setCellValue("J{$filaExcel}", $total_egresos);
$sheet->setCellValue("K{$filaExcel}", $total_ganancia);

// Estilos de totales
$sheet->getStyle("G{$filaExcel}")->applyFromArray($style_total_final_valor);
$sheet->getStyle("H{$filaExcel}")->applyFromArray($style_total_final_valor);
$sheet->getStyle("I{$filaExcel}")->applyFromArray($style_total_final_valor);
$sheet->getStyle("J{$filaExcel}")->applyFromArray($style_total_final_valor);
$sheet->getStyle("K{$filaExcel}")->applyFromArray($style_total_final_valor);

// Formato moneda a totales
$sheet->getStyle("G{$filaExcel}")->getNumberFormat()->setFormatCode($currency_format);
$sheet->getStyle("J{$filaExcel}")->getNumberFormat()->setFormatCode($currency_format);
$sheet->getStyle("K{$filaExcel}")->getNumberFormat()->setFormatCode($currency_format);
$sheet->getRowDimension($filaExcel)->setRowHeight(20);


// Ajustar ancho automático y alto
foreach (range('A', 'K') as $col) {
    if (in_array($col, ['C', 'E'])) {
        $sheet->getColumnDimension($col)->setWidth(35); // Columnas anchas para Cliente y Concepto
    } else if (in_array($col, ['G', 'J', 'K'])) {
        $sheet->getColumnDimension($col)->setWidth(20); // Columnas de dinero
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}
// ==========================================================
// ¡NUEVO! Ajustar alto de filas de datos para el RichText
// ==========================================================
for ($i = 6; $i < $filaExcel; $i++) {
    $sheet->getRowDimension($i)->setRowHeight(40); // Aumentar alto para ver las dos líneas
}
// ==========================================================

// ==========================================================
// Salida del archivo Excel
// ==========================================================
ob_clean(); // Limpia el búfer de salida
$nombreArchivo = 'reporte_recibos_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>