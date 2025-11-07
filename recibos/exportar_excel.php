<?php
ob_start(); // Inicia el búfer de salida
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/models/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

// 1. Obtener los filtros desde la URL (GET) - MODIFICADO
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fechaDesde'] ?? '';
$fechaHasta = $_GET['fechaHasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$id_sede = $_GET['id_sede'] ?? ''; // <-- NUEVA LÍNEA

// ==========================================================
// Consulta SQL - MODIFICADA
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
        (
            SELECT COALESCE(SUM(e.monto), 0)
            FROM egresos e
            WHERE e.recibo_id = r.id
              AND e.tipo = 'servicio' 
        ) AS valor_total_egresos
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
// --- NUEVO: Añadir filtro de Sede a la consulta ---
if (!empty($id_sede)) {
    $where[] = "a.id_sede = ?";
    $params[] = $id_sede;
    $types .= 'i';
}
// --- FIN NUEVO ---

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

// --- NUEVO: Obtener nombre de la Sede (para el título) ---
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
// --- FIN NUEVO ---

// ==========================================================
// Crear el Excel
// ==========================================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reporte de Recibos');

// --- (Todos tus estilos $style_... permanecen igual) ---
$currency_format = '$#,##0';
$style_borde_fino = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
// ... (todos los demás estilos que ya tenías)
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

// --- Títulos Generales (MODIFICADO) ---
$sheet->mergeCells('A1:K1');
$sheet->setCellValue('A1', 'Liquidacion Tramites SyS');
$sheet->getStyle('A1:K1')->applyFromArray($style_titulo_principal);
$sheet->getRowDimension('1')->setRowHeight(30);

$sheet->mergeCells('A2:K2');
// --- MODIFICADO: Usar el nombre de la sede ---
$sheet->setCellValue('A2', 'Oficina: ' . htmlspecialchars($sede_nombre));
$sheet->getStyle('A2:K2')->applyFromArray($style_subtitulo_periodo);
$sheet->getRowDimension('2')->setRowHeight(25);

// Crear texto de período
$periodo = '';
if (!empty($fechaDesde) && !empty($fechaHasta)) {
    $periodo = "Periodo: " . $fechaDesde . " al " . $fechaHasta;
} else if (!empty($fechaDesde)) {
    $periodo = "Desde: " . $fechaDesde;
} else if (!empty($fechaHasta)) {
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

// --- (El resto del archivo: Escribir datos, Totales, Ajustar Ancho y Salida, no cambia) ---
// --- Escribir datos ---
$filaExcel = 6;
$total_valor_servicio = 0;
$total_egresos = 0;
$total_ganancia = 0;

while ($fila = $resultado->fetch_assoc()) {
    $valor_servicio = (float)$fila['valor_servicio'];
    $valor_egresos = (float)$fila['valor_total_egresos'];
    $ganancia_neta = $valor_servicio - $valor_egresos;

    $sheet->fromArray([
        $fila['id'],
        $fila['fecha_tramite'],
        $fila['cliente'],
        $fila['placa'],
        $fila['concepto'],
        $fila['asesor'],
        $valor_servicio,
        $fila['estado'],
        $fila['metodo_pago'],
        $valor_egresos,
        $ganancia_neta
    ], null, "A{$filaExcel}");

    // Sumar a totales
    $total_valor_servicio += $valor_servicio;
    $total_egresos += $valor_egresos;
    $total_ganancia += $ganancia_neta;

    $filaExcel++;
}

// Aplicar estilos a las celdas de datos
$rango_datos = "A6:K" . ($filaExcel - 1);
$sheet->getStyle($rango_datos)->applyFromArray($style_celda_datos);
$sheet->getStyle($rango_datos)->getAlignment()->setWrapText(true); // Ajustar texto

// Aplicar formato de moneda
$sheet->getStyle("G6:G" . ($filaExcel - 1))->getNumberFormat()->setFormatCode($currency_format);
$sheet->getStyle("J6:K" . ($filaExcel - 1))->getNumberFormat()->setFormatCode($currency_format);

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


// Ajustar ancho automático
foreach (range('A', 'K') as $col) {
    if (in_array($col, ['C', 'E'])) {
        $sheet->getColumnDimension($col)->setWidth(35); // Columnas anchas para Cliente y Concepto
    } else if (in_array($col, ['G', 'J', 'K'])) {
        $sheet->getColumnDimension($col)->setWidth(18); // Columnas de dinero
    } else {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
}

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