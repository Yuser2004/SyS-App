<?php
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/models/conexion.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// 1. Obtener los filtros desde la URL (GET)
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fechaDesde'] ?? '';
$fechaHasta = $_GET['fechaHasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// ==========================================================
// Consulta SQL
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

// ==========================================================
// Crear el Excel
// ==========================================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$headers = [
    'ID Recibo', 'Fecha', 'Cliente', 'Placa', 'Concepto', 
    'Asesor', 'Valor Servicio', 'Estado', 'Método de Pago', 'Valor Total Egresos'
];
$sheet->fromArray($headers, null, 'A1');

// Aplicar estilo al encabezado
$sheet->getStyle('A1:J1')->getFill()
    ->setFillType(Fill::FILL_SOLID)
    ->getStartColor()->setRGB('DCE6F1'); // azul suave

$sheet->getStyle('A1:J1')->getFont()->setBold(true);

// Escribir datos
$filaExcel = 2;
while ($fila = $resultado->fetch_assoc()) {
    // Calcular ganancia neta
    $sheet->fromArray([
        $fila['id'],
        $fila['fecha_tramite'],
        $fila['cliente'],
        $fila['placa'],
        $fila['concepto'],
        $fila['asesor'],
        $fila['valor_servicio'],
        $fila['estado'],
        $fila['metodo_pago'],
        $fila['valor_total_egresos'] ?? 0
    ], null, "A{$filaExcel}");
    $filaExcel++;
}

// Ajustar ancho automático
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ==========================================================
// Salida del archivo Excel
// ==========================================================
$nombreArchivo = 'reporte_recibos_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$nombreArchivo\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>
