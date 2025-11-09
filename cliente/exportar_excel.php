<?php
// /cliente/exportar_excel.php
// Este script genera el reporte de clientes

ob_start(); // Prevenir cualquier salida de texto accidental

// 1. Cargar Vendor y Conexión
// Sube de /cliente a /SyS-app, luego baja a /vendor
require __DIR__ . '/../vendor/autoload.php'; 
// Baja de /cliente a /cliente/models/
include __DIR__ . '/models/conexion.php'; 

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

// 2. Obtener Filtros
$busqueda = $_GET['busqueda'] ?? '';

// 3. Construir SQL (basado en fragmento_clientes.php)
// Usamos todos los campos que pediste
$sql_base = "FROM clientes";
$params = [];
$tipos_params = '';

// Si hay un término de búsqueda, añadimos el WHERE
if (!empty($busqueda)) {
    $sql_base .= " WHERE nombre_completo LIKE ? OR documento LIKE ? OR telefono LIKE ?";
    $like_busqueda = "%{$busqueda}%";
    array_push($params, $like_busqueda, $like_busqueda, $like_busqueda);
    $tipos_params .= 'sss';
}

// ¡SIN LIMIT y SIN OFFSET para exportar TODO!
$sql_final = "SELECT id_cliente, nombre_completo, documento, telefono, ciudad, direccion, observaciones " 
             . $sql_base . " ORDER BY nombre_completo ASC";

$stmt = $conn->prepare($sql_final);
if (!empty($busqueda)) {
    $stmt->bind_param($tipos_params, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// 4. Crear el Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Lista de Clientes');

// 5. Definir Estilos Profesionales
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A99']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];
$titleStyle = [
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
];
$borderStyle = [
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '999999']],
    ],
];
$dataRowStyle = [
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
];

// 6. Escribir Encabezados y Título
$sheet->mergeCells('A1:G1');
$sheet->setCellValue('A1', 'Lista de Clientes');
$sheet->getStyle('A1')->applyFromArray($titleStyle);
$sheet->getRowDimension('1')->setRowHeight(30);
$startRow = 3; // Fila donde empiezan los encabezados

// Si hay búsqueda, añadir un subtítulo
if (!empty($busqueda)) {
    $sheet->mergeCells('A2:G2');
    $sheet->setCellValue('A2', 'Filtro de búsqueda: "' . htmlspecialchars($busqueda) . '"');
    $sheet->getStyle('A2')->getFont()->setItalic(true);
    $startRow = 4; // Bajar los encabezados si hay subtítulo
}

$headers = [
    'ID Cliente', 'Nombre Completo', 'Documento', 'Teléfono', 'Ciudad', 'Dirección', 'Observaciones'
];
$sheet->fromArray($headers, null, 'A'.$startRow);
$sheet->getStyle('A'.$startRow.':G'.$startRow)->applyFromArray($headerStyle);
$sheet->getRowDimension($startRow)->setRowHeight(25);

// 7. Escribir Datos
$row = $startRow + 1; // Empezar datos en la fila siguiente
while ($fila = $resultado->fetch_assoc()) {
    $sheet->fromArray([
        $fila['id_cliente'],
        $fila['nombre_completo'],
        $fila['documento'],
        $fila['telefono'],
        $fila['ciudad'],
        $fila['direccion'],
        $fila['observaciones']
    ], null, 'A'.$row);
    
    // Forzar documento y teléfono como TEXTO para que no se corrompan
    $sheet->getCell('C'.$row)->setValueExplicit($fila['documento'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->getCell('D'.$row)->setValueExplicit($fila['telefono'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    
    // Aplicar estilo de centrado vertical
    $sheet->getStyle('A'.$row.':G'.$row)->applyFromArray($dataRowStyle);
    $sheet->getRowDimension($row)->setRowHeight(20); // Alto de fila de datos
    $row++;
}
$lastRow = $row - 1;

// 8. Aplicar Bordes y Ancho de Columnas
if ($lastRow >= $startRow + 1) { // Solo si hay datos
    $sheet->getStyle('A'.$startRow.':G'.$lastRow)->applyFromArray($borderStyle);
    $sheet->getStyle('F'.($startRow+1).':G'.$lastRow)->getAlignment()->setWrapText(true); // Ajustar texto en dirección y obs.
}

$sheet->getColumnDimension('A')->setWidth(10); // ID
$sheet->getColumnDimension('B')->setWidth(35); // Nombre
$sheet->getColumnDimension('C')->setWidth(20); // Documento
$sheet->getColumnDimension('D')->setWidth(20); // Teléfono
$sheet->getColumnDimension('E')->setWidth(20); // Ciudad
$sheet->getColumnDimension('F')->setWidth(40); // Dirección
$sheet->getColumnDimension('G')->setWidth(50); // Observaciones

// 9. Enviar al Navegador
$conn->close();
ob_clean(); // Limpiar el búfer antes de la salida
$fileName = "Reporte_Clientes_" . date('Y-m-d') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>