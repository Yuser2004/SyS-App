<?php
// finanzas/views/exportar_excel.php
// ¡VERSIÓN 3.0!
// 1. Añadida la columna "Comprobante" con un hipervínculo funcional.
// 2. Cálculo automático de la URL base para que los links funcionen.

// 1. CARGAR LIBRERÍAS Y CONEXIÓN
require __DIR__ . '/../../vendor/autoload.php';
include __DIR__ . '/../models/conexion.php';

// Usar las clases de PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// 2. OBTENER FILTROS DESDE LA URL
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-t');
$sede_id = $_GET['sede_id'] ?? ''; // Vacío para "Todas"

// ==========================================================
// ¡NUEVO! CÁLCULO DE LA URL BASE
// Esto es para que los links "uploads/comprobantes/..." funcionen
// ==========================================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
// $_SERVER['SCRIPT_NAME'] es /SyS-app/finanzas/views/exportar_excel.php (o similar)
// Reemplazamos la parte final para obtener la ruta base del proyecto
$base_path = str_replace('/finanzas/views/exportar_excel.php', '', $_SERVER['SCRIPT_NAME']);
// Asegurarnos que no haya doble barra '//'
if (str_ends_with($base_path, '/')) {
    $base_path = rtrim($base_path, '/'); 
}
// $base_url será algo como "http://localhost/SyS-app/"
$base_url = $protocol . $domain . $base_path . '/';
// ==========================================================


// --- 3. RECOPILACIÓN DE DATOS (Basado en api_reporte.php) ---

// --- 3.A. Preparar filtros y parámetros ---
$params_base = [$fecha_desde, $fecha_hasta];
$types_base = "ss";
$where_sede_recibo_asesor = ""; // Alias 'a' para asesor en recibos
$where_sede_gasto_simple = "";  // Para tabla 'g'

$nombreSede = 'Todas las Sedes'; // Valor por defecto

if (!empty($sede_id)) {
    $params_base[] = $sede_id;
    $types_base .= "i";
    $where_sede_recibo_asesor = " AND a.id_sede = ? ";
    $where_sede_gasto_simple = " AND g.id_sede = ? "; 
    
    // Obtener nombre de la sede para el título
    $stmtSede = $conn->prepare("SELECT nombre FROM sedes WHERE id = ?");
    if ($stmtSede) {
        $stmtSede->bind_param("i", $sede_id);
        $stmtSede->execute();
        $resSede = $stmtSede->get_result();
        if($fila = $resSede->fetch_assoc()) {
            $nombreSede = $fila['nombre'];
        }
        $stmtSede->close();
    }
}

// --- 3.B. Ejecutar Consultas (Ingresos, Egresos, Métodos) ---

// Ingresos Totales:
$sql_total_ingresos = "SELECT SUM(r.valor_servicio) AS total 
                       FROM recibos r
                       LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
                       WHERE r.estado = 'completado' AND r.fecha_tramite BETWEEN ? AND ? $where_sede_recibo_asesor";
$stmt_ingresos = $conn->prepare($sql_total_ingresos);
$stmt_ingresos->bind_param($types_base, ...$params_base);
$stmt_ingresos->execute();
$total_ingresos = (float)($stmt_ingresos->get_result()->fetch_assoc()['total'] ?? 0);
$stmt_ingresos->close();

// Egresos Totales:
$sql_total_egresos = "SELECT SUM(e.monto) AS total 
                      FROM egresos e 
                      JOIN recibos r ON e.recibo_id = r.id
                      LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
                      WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' $where_sede_recibo_asesor";
$stmt_egresos = $conn->prepare($sql_total_egresos);
$stmt_egresos->bind_param($types_base, ...$params_base);
$stmt_egresos->execute();
$total_egresos = (float)($stmt_egresos->get_result()->fetch_assoc()['total'] ?? 0);
$stmt_egresos->close();

// Desglose por Método:
$metodos_pago = ['efectivo', 'transferencia', 'tarjeta', 'otro'];
$desglose_pagos = [];
foreach ($metodos_pago as $metodo) {
    $params_metodo = [$metodo, $fecha_desde, $fecha_hasta];
    $types_metodo = "sss";
    $params_gasto_metodo = [$metodo, $fecha_desde, $fecha_hasta];
    $types_gasto_metodo = "sss";

    if (!empty($sede_id)) {
        $params_metodo[] = $sede_id;
        $types_metodo .= "i";
        $params_gasto_metodo[] = $sede_id;
        $types_gasto_metodo .= "i";
    }
    // Ingresos por método
    $sql_ing_m = "SELECT SUM(r.valor_servicio) AS total 
                  FROM recibos r 
                  LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                  WHERE r.estado = 'completado' AND r.metodo_pago = ? AND r.fecha_tramite BETWEEN ? AND ? $where_sede_recibo_asesor";
    $stmt = $conn->prepare($sql_ing_m);
    $stmt->bind_param($types_metodo, ...$params_metodo);
    $stmt->execute();
    $ingresos_metodo = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    
    // Egresos por método
    $sql_egr_m = "SELECT SUM(e.monto) AS total 
                  FROM egresos e 
                  JOIN recibos r ON e.recibo_id = r.id 
                  LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                  WHERE r.estado = 'completado' AND e.forma_pago = ? AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' $where_sede_recibo_asesor";
    $stmt = $conn->prepare($sql_egr_m);
    $stmt->bind_param($types_metodo, ...$params_metodo);
    $stmt->execute();
    $egresos_metodo = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    
    // Gastos por método
    $sql_gasto_m = "SELECT SUM(g.monto) AS total 
                    FROM gastos_sede g
                    WHERE g.metodo_pago = ? AND g.fecha BETWEEN ? AND ? $where_sede_gasto_simple";
    $stmt = $conn->prepare($sql_gasto_m);
    $stmt->bind_param($types_gasto_metodo, ...$params_gasto_metodo);
    $stmt->execute();
    $gastos_metodo = (float)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    
    // LÓGICA DE DESGLOSE DE CUENTAS (Transferencia)
    $cuentas_detalle = [];
    if ($metodo === 'transferencia') {
        // 1. Ingresos por 'detalle_pago' (de tabla 'recibos')
        $sql_ing_detalle = "SELECT SUM(r.valor_servicio) AS total, r.detalle_pago 
                            FROM recibos r 
                            LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                            WHERE r.estado = 'completado' AND r.metodo_pago = ? AND r.fecha_tramite BETWEEN ? AND ? $where_sede_recibo_asesor 
                            AND r.detalle_pago IS NOT NULL
                            GROUP BY r.detalle_pago";
        $stmt_d = $conn->prepare($sql_ing_detalle);
        $stmt_d->bind_param($types_metodo, ...$params_metodo);
        $stmt_d->execute();
        $res_d = $stmt_d->get_result();
        while ($fila_d = $res_d->fetch_assoc()) {
            $cuentas_detalle[$fila_d['detalle_pago']]['ingresos'] = ($cuentas_detalle[$fila_d['detalle_pago']]['ingresos'] ?? 0) + $fila_d['total'];
        }
        $stmt_d->close();

        // 2. Egresos por 'detalle_pago' (de tabla 'egresos')
        $sql_egr_detalle = "SELECT SUM(e.monto) AS total, e.detalle_pago 
                            FROM egresos e 
                            JOIN recibos r ON e.recibo_id = r.id 
                            LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
                            WHERE r.estado = 'completado' AND e.forma_pago = ? AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' $where_sede_recibo_asesor 
                            AND e.detalle_pago IS NOT NULL
                            GROUP BY e.detalle_pago";
        $stmt_d = $conn->prepare($sql_egr_detalle);
        $stmt_d->bind_param($types_metodo, ...$params_metodo);
        $stmt_d->execute();
        $res_d = $stmt_d->get_result();
        while ($fila_d = $res_d->fetch_assoc()) {
            $cuentas_detalle[$fila_d['detalle_pago']]['salidas'] = ($cuentas_detalle[$fila_d['detalle_pago']]['salidas'] ?? 0) + $fila_d['total'];
        }
        $stmt_d->close();

        // 3. Gastos por 'detalle_pago' (de tabla 'gastos_sede')
        $sql_gasto_detalle = "SELECT SUM(g.monto) AS total, g.detalle_pago 
                              FROM gastos_sede g
                              WHERE g.metodo_pago = ? AND g.fecha BETWEEN ? AND ? $where_sede_gasto_simple 
                              AND g.detalle_pago IS NOT NULL
                              GROUP BY g.detalle_pago";
        $stmt_d = $conn->prepare($sql_gasto_detalle);
        $stmt_d->bind_param($types_gasto_metodo, ...$params_gasto_metodo);
        $stmt_d->execute();
        $res_d = $stmt_d->get_result();
        while ($fila_d = $res_d->fetch_assoc()) {
            $cuentas_detalle[$fila_d['detalle_pago']]['salidas'] = ($cuentas_detalle[$fila_d['detalle_pago']]['salidas'] ?? 0) + $fila_d['total'];
        }
        $stmt_d->close();
    }

    $desglose_pagos[$metodo] = [
        'ingresos' => $ingresos_metodo,
        'salidas' => $egresos_metodo + $gastos_metodo,
        'balance' => $ingresos_metodo - ($egresos_metodo + $gastos_metodo),
        'cuentas' => $cuentas_detalle
    ];
    $stmt->close();
}

// Desglose Diario:
$sql_detalle_diario_base = "
    SELECT fecha, SUM(ingreso) AS ingresos_diarios, SUM(egreso) AS egresos_diarios, SUM(gasto) AS gastos_diarios
    FROM (
        SELECT DATE(r.fecha_tramite) AS fecha, r.valor_servicio AS ingreso, 0 AS egreso, 0 AS gasto
        FROM recibos r 
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE r.estado = 'completado' AND r.fecha_tramite BETWEEN ? AND ? %s

        UNION ALL

        SELECT DATE(e.fecha) AS fecha, 0 AS ingreso, e.monto AS egreso, 0 AS gasto
        FROM egresos e 
        JOIN recibos r ON e.recibo_id = r.id 
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor 
        WHERE r.estado = 'completado' AND e.fecha BETWEEN ? AND ? AND e.tipo = 'servicio' %s

        UNION ALL

        SELECT DATE(g.fecha) AS fecha, 0 AS ingreso, 0 AS egreso, g.monto AS gasto
        FROM gastos_sede g
        WHERE g.fecha BETWEEN ? AND ? %s
    ) AS transacciones
    GROUP BY fecha ORDER BY fecha ASC
";
$params_detalle = [$fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta, $fecha_desde, $fecha_hasta];
$types_detalle = "ssssss";
$where_sede_r = ''; $where_sede_e = ''; $where_sede_g = '';
if (!empty($sede_id)) {
    $where_sede_r = " AND a.id_sede = ? ";
    $where_sede_e = " AND a.id_sede = ? ";
    $where_sede_g = " AND g.id_sede = ? ";
    array_push($params_detalle, $sede_id, $sede_id, $sede_id);
    $types_detalle .= "iii";
}
$sql_detalle_diario = sprintf($sql_detalle_diario_base, $where_sede_r, $where_sede_e, $where_sede_g);
$stmt_detalle = $conn->prepare($sql_detalle_diario);
$stmt_detalle->bind_param($types_detalle, ...$params_detalle);
$stmt_detalle->execute();
$resultado_detalle = $stmt_detalle->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_detalle->close();

// --- 3.C. ¡CONSULTA MODIFICADA! para las tablas de la derecha ---
$gastos_sede_array = [];
$gastos_personal_array = [];
$total_gastos_sede = 0;
$total_gastos_personal = 0;

$params_gasto_detalle = [$fecha_desde, $fecha_hasta];
$types_gasto_detalle = "ss";
if (!empty($sede_id)) {
    $params_gasto_detalle[] = $sede_id;
    $types_gasto_detalle .= "i";
}

// ==========================================================
// ¡MODIFICADO! Se añadió g.comprobante_url
// ==========================================================
$sql_gastos_detalle = "SELECT g.fecha, g.descripcion, g.monto, g.tipo_gasto, a.nombre AS asesor_nombre, g.comprobante_url
                       FROM gastos_sede g
                       LEFT JOIN asesor a ON g.id_asesor = a.id_asesor
                       WHERE g.fecha BETWEEN ? AND ? $where_sede_gasto_simple
                       ORDER BY g.fecha ASC";
$stmt_gastos_detalle = $conn->prepare($sql_gastos_detalle);
$stmt_gastos_detalle->bind_param($types_gasto_detalle, ...$params_gasto_detalle);
$stmt_gastos_detalle->execute();
$res_gastos_detalle = $stmt_gastos_detalle->get_result();

while ($fila = $res_gastos_detalle->fetch_assoc()) {
    $monto = (float)$fila['monto'];
    // $fila ahora también contiene 'comprobante_url'
    if ($fila['tipo_gasto'] == 'sede') {
        $gastos_sede_array[] = $fila;
        $total_gastos_sede += $monto;
    } else { // Asumimos 'personal'
        $gastos_personal_array[] = $fila;
        $total_gastos_personal += $monto;
    }
}
$stmt_gastos_detalle->close();
$conn->close(); 

// --- 4. CREAR EL ARCHIVO EXCEL ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Reporte Financiero');

// Alto de Fila Vertical
$sheet->getDefaultRowDimension()->setRowHeight(22);
$sheet->getRowDimension('1')->setRowHeight(30);
$sheet->getRowDimension('2')->setRowHeight(25); 

// --- Definición de Estilos ---
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '004A99']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER]
];
$titleStyle = [
    'font' => ['bold' => true, 'size' => 16],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];
$subtitleStyle = [
    'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '333333']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
];
$totalRowStyle = [
    'font' => ['bold' => true],
    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]]
];
$positiveStyle = ['font' => ['color' => ['rgb' => '008000']]];
$negativeStyle = ['font' => ['color' => ['rgb' => 'FF0000']]];
// ==========================================================
// ¡NUEVO ESTILO! Para links
// ==========================================================
$linkStyle = [
    'font' => ['color' => ['rgb' => '0000FF'], 'underline' => 'single']
];
// ==========================================================
$currencyFormat = '"$"#,##0';
$rightAlign = ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]];
$dataRowStyle = ['alignment' => ['vertical' => Alignment::VERTICAL_CENTER]];
$tableBorderStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['rgb' => '999999'],
        ],
    ],
];
$subCuentaStyle = [
    'font' => ['size' => 10, 'italic' => true, 'color' => ['rgb' => '333333']],
    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_LEFT],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']]
];

// --- 5. POBLAR EL EXCEL ---

// Título y Filtros
$sheet->mergeCells('A1:E1'); 
$sheet->setCellValue('A1', 'Reporte Financiero SyS');
$sheet->getStyle('A1')->applyFromArray($titleStyle);
$sheet->mergeCells('A2:E2'); 
$sheet->setCellValue('A2', "Período: " . date("d/m/Y", strtotime($fecha_desde)) . " al " . date("d/m/Y", strtotime($fecha_hasta)) . " | Sede: " . htmlspecialchars($nombreSede));
$sheet->getStyle('A2')->applyFromArray($subtitleStyle);

// --- Resumen (Las 4 "cajas") ---
$sheet->getRowDimension('4')->setRowHeight(25);
$sheet->setCellValue('A4', 'Total Ingresos');
$sheet->setCellValue('B4', 'Egresos Servicio');
$sheet->setCellValue('C4', 'Gastos Sede');
$sheet->setCellValue('D4', 'Gastos Personal');
$sheet->setCellValue('E4', 'UTILIDAD REAL');
$sheet->getStyle('A4:E4')->applyFromArray($headerStyle);
$sheet->setCellValue('A5', $total_ingresos);
$sheet->setCellValue('B5', -$total_egresos);
$sheet->setCellValue('C5', -$total_gastos_sede);
$sheet->setCellValue('D5', -$total_gastos_personal);
$utilidad = $total_ingresos - $total_egresos - $total_gastos_sede - $total_gastos_personal;
$sheet->setCellValue('E5', $utilidad);
$sheet->getStyle('A5:E5')->getNumberFormat()->setFormatCode($currencyFormat);
$sheet->getStyle('A5')->applyFromArray($positiveStyle);
$sheet->getStyle('B5:D5')->applyFromArray($negativeStyle);
$sheet->getStyle('E5')->applyFromArray($utilidad >= 0 ? $positiveStyle : $negativeStyle)->getFont()->setBold(true);
$sheet->getStyle('A4:E5')->applyFromArray($tableBorderStyle); 

// --- Desglose Diario (Tabla Izquierda) ---
$row = 8;
$row_diario_start = $row;
$sheet->setCellValue('A'.$row, 'Desglose Diario');
$sheet->mergeCells('A'.$row.':E'.$row); 
$sheet->getStyle('A'.$row)->applyFromArray($titleStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$row++;
$sheet->getRowDimension($row)->setRowHeight(25); 
$sheet->setCellValue('A'.$row, 'Fecha');
$sheet->setCellValue('B'.$row, 'Ingresos');
$sheet->setCellValue('C'.$row, 'Egresos');
$sheet->setCellValue('D'.$row, 'Gastos (Sede+Personal)');
$sheet->setCellValue('E'.$row, 'Utilidad Neta del Día');
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($headerStyle);
$row++;

$totalDiaIng = $totalDiaEgr = $totalDiaGasto = 0;
foreach($resultado_detalle as $dia) {
    $ing = (float)($dia['ingresos_diarios'] ?? 0);
    $egr = (float)($dia['egresos_diarios'] ?? 0);
    $gasto = (float)($dia['gastos_diarios'] ?? 0);
    $neto = $ing - $egr - $gasto;
    
    $sheet->setCellValue('A'.$row, date("d/m/Y", strtotime($dia['fecha'])));
    $sheet->setCellValue('B'.$row, $ing);
    $sheet->setCellValue('C'.$row, -$egr);
    $sheet->setCellValue('D'.$row, -$gasto);
    $sheet->setCellValue('E'.$row, $neto);
    
    $sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($dataRowStyle);
    $sheet->getStyle('B'.$row.':E'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('B'.$row)->applyFromArray($positiveStyle);
    $sheet->getStyle('C'.$row.':D'.$row)->applyFromArray($negativeStyle);
    $sheet->getStyle('E'.$row)->applyFromArray($neto >= 0 ? $positiveStyle : $negativeStyle);
    
    $totalDiaIng += $ing; $totalDiaEgr += $egr; $totalDiaGasto += $gasto;
    $row++;
}
// Total Desglose Diario
$sheet->setCellValue('A'.$row, 'Total');
$sheet->setCellValue('B'.$row, $totalDiaIng);
$sheet->setCellValue('C'.$row, -$totalDiaEgr);
$sheet->setCellValue('D'.$row, -$totalDiaGasto);
$sheet->setCellValue('E'.$row, $totalDiaIng - $totalDiaEgr - $totalDiaGasto);
$sheet->getStyle('A'.$row.':E'.$row)->applyFromArray($totalRowStyle)->applyFromArray($dataRowStyle);
$sheet->getStyle('B'.$row.':E'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
$row_diario_end = $row; 

// --- Desglose por Método (Debajo de Diario) ---
$row += 3;
$row_metodo_start = $row;
$sheet->setCellValue('A'.$row, 'Desglose por Método de Pago');
$sheet->mergeCells('A'.$row.':D'.$row); 
$sheet->getStyle('A'.$row)->applyFromArray($titleStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$row++;
$sheet->getRowDimension($row)->setRowHeight(25);
$sheet->setCellValue('A'.$row, 'Método');
$sheet->setCellValue('B'.$row, 'Ingresos');
$sheet->setCellValue('C'.$row, 'Salidas (Egresos + Gastos)');
$sheet->setCellValue('D'.$row, 'Balance');
$sheet->getStyle('A'.$row.':D'.$row)->applyFromArray($headerStyle);
$row++;

foreach($desglose_pagos as $metodo => $montos) {
    $ing = $montos['ingresos'];
    $sal = $montos['salidas'];
    $bal = $montos['balance'];
    $sheet->setCellValue('A'.$row, ucfirst($metodo));
    $sheet->setCellValue('B'.$row, $ing);
    $sheet->setCellValue('C'.$row, -$sal);
    $sheet->setCellValue('D'.$row, $bal);
    $sheet->getStyle('A'.$row.':D'.$row)->applyFromArray($dataRowStyle);
    $sheet->getStyle('B'.$row.':D'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
    $sheet->getStyle('B'.$row)->applyFromArray($positiveStyle);
    $sheet->getStyle('C'.$row)->applyFromArray($negativeStyle);
    $sheet->getStyle('D'.$row)->applyFromArray($bal >= 0 ? $positiveStyle : $negativeStyle);
    
    // Bucle para las cuentas de transferencia
    if ($metodo === 'transferencia' && !empty($montos['cuentas'])) {
        foreach ($montos['cuentas'] as $nombreCuenta => $cuenta) {
            $row++; // Fila nueva para la sub-cuenta
            $ingresosCuenta = $cuenta['ingresos'] ?? 0;
            $salidasCuenta = $cuenta['salidas'] ?? 0;
            $balanceCuenta = $ingresosCuenta - $salidasCuenta;

            if ($ingresosCuenta > 0 || $salidasCuenta > 0) {
                $sheet->setCellValue('A'.$row, '     ↪ ' . $nombreCuenta);
                $sheet->setCellValue('B'.$row, $ingresosCuenta);
                $sheet->setCellValue('C'.$row, -$salidasCuenta);
                $sheet->setCellValue('D'.$row, $balanceCuenta);
                
                $sheet->getStyle('A'.$row.':D'.$row)->applyFromArray($subCuentaStyle);
                $sheet->getStyle('A'.$row)->getAlignment()->setIndent(1);
                $sheet->getStyle('B'.$row.':D'.$row)->getNumberFormat()->setFormatCode($currencyFormat);
                $sheet->getStyle('B'.$row)->applyFromArray($positiveStyle);
                $sheet->getStyle('C'.$row)->applyFromArray($negativeStyle);
                $sheet->getStyle('D'.$row)->applyFromArray($balanceCuenta >= 0 ? $positiveStyle : $negativeStyle);
            }
        }
    }   
    $row++;
}
$row_metodo_end = $row - 1;

// --- Tablas Derecha (Gastos) ---
$colDerecha = 'G';
$rowDerecha = 8;
$row_gasto_p_start = $rowDerecha;

// --- Tabla Gastos Personales (Arriba-Derecha) ---
// ==========================================================
// ¡MODIFICADO! Merge hasta K
// ==========================================================
$sheet->setCellValue($colDerecha.$rowDerecha, 'Gastos de Personal');
$sheet->mergeCells($colDerecha.$rowDerecha.':K'.$rowDerecha); // G a K
$sheet->getStyle($colDerecha.$rowDerecha)->applyFromArray($titleStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$rowDerecha++;
$sheet->getRowDimension($rowDerecha)->setRowHeight(25);
$sheet->setCellValue('G'.$rowDerecha, 'Asesor');
$sheet->setCellValue('H'.$rowDerecha, 'Fecha');
$sheet->setCellValue('I'.$rowDerecha, 'Descripción');
$sheet->setCellValue('J'.$rowDerecha, 'Monto');
$sheet->setCellValue('K'.$rowDerecha, 'Comprobante'); // ¡NUEVA COLUMNA!
$sheet->getStyle('G'.$rowDerecha.':K'.$rowDerecha)->applyFromArray($headerStyle); // G a K
$rowDerecha++;
foreach($gastos_personal_array as $gasto) {
    $sheet->setCellValue('G'.$rowDerecha, $gasto['asesor_nombre']);
    $sheet->setCellValue('H'.$rowDerecha, date("d/m/Y", strtotime($gasto['fecha'])));
    $sheet->setCellValue('I'.$rowDerecha, $gasto['descripcion']);
    $sheet->setCellValue('J'.$rowDerecha, -$gasto['monto']);
    
    // ==========================================================
    // ¡NUEVO! Lógica para añadir Hipervínculo
    // ==========================================================
    $url = $gasto['comprobante_url'];
    if (!empty($url)) {
        $full_url = $base_url . ltrim($url, '/'); // Construye URL completa
        $sheet->setCellValue('K'.$rowDerecha, 'Ver Comprobante');
        $sheet->getCell('K'.$rowDerecha)->getHyperlink()->setUrl($full_url);
        $sheet->getStyle('K'.$rowDerecha)->applyFromArray($linkStyle);
    } else {
        $sheet->setCellValue('K'.$rowDerecha, 'N/A');
    }
    // ==========================================================

    $sheet->getStyle('G'.$rowDerecha.':K'.$rowDerecha)->applyFromArray($dataRowStyle);
    $sheet->getStyle('J'.$rowDerecha)->getNumberFormat()->setFormatCode($currencyFormat)->applyFromArray($negativeStyle);
    $sheet->getStyle('K'.$rowDerecha)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar el link
    $rowDerecha++;
}
// Total Gastos Personales
$sheet->setCellValue('I'.$rowDerecha, 'Total Personal');
$sheet->setCellValue('J'.$rowDerecha, -$total_gastos_personal);
$sheet->getStyle('I'.$rowDerecha.':J'.$rowDerecha)->applyFromArray($totalRowStyle)->applyFromArray($dataRowStyle);
$sheet->getStyle('I'.$rowDerecha)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('J'.$rowDerecha)->getNumberFormat()->setFormatCode($currencyFormat);
$row_gasto_p_end = $rowDerecha;


// --- Tabla Gastos de Sede (Abajo-Derecha) ---
$rowDerecha += 3;
$row_gasto_s_start = $rowDerecha;
// ==========================================================
// ¡MODIFICADO! Merge hasta K
// ==========================================================
$sheet->setCellValue($colDerecha.$rowDerecha, 'Desglose de Gastos de Sede');
$sheet->mergeCells($colDerecha.$rowDerecha.':K'.$rowDerecha); // G a K
$sheet->getStyle($colDerecha.$rowDerecha)->applyFromArray($titleStyle)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$rowDerecha++;
$sheet->getRowDimension($rowDerecha)->setRowHeight(25);
$sheet->setCellValue('G'.$rowDerecha, 'Asesor');
$sheet->setCellValue('H'.$rowDerecha, 'Fecha');
$sheet->setCellValue('I'.$rowDerecha, 'Descripción');
$sheet->setCellValue('J'.$rowDerecha, 'Monto');
$sheet->setCellValue('K'.$rowDerecha, 'Comprobante'); // ¡NUEVA COLUMNA!
$sheet->getStyle('G'.$rowDerecha.':K'.$rowDerecha)->applyFromArray($headerStyle); // G a K
$rowDerecha++;
foreach($gastos_sede_array as $gasto) {
    $sheet->setCellValue('G'.$rowDerecha, $gasto['asesor_nombre']);
    $sheet->setCellValue('H'.$rowDerecha, date("d/m/Y", strtotime($gasto['fecha'])));
    $sheet->setCellValue('I'.$rowDerecha, $gasto['descripcion']);
    $sheet->setCellValue('J'.$rowDerecha, -$gasto['monto']);
    
    // ==========================================================
    // ¡NUEVO! Lógica para añadir Hipervínculo
    // ==========================================================
    $url = $gasto['comprobante_url'];
    if (!empty($url)) {
        $full_url = $base_url . ltrim($url, '/'); // Construye URL completa
        $sheet->setCellValue('K'.$rowDerecha, 'Ver Comprobante');
        $sheet->getCell('K'.$rowDerecha)->getHyperlink()->setUrl($full_url);
        $sheet->getStyle('K'.$rowDerecha)->applyFromArray($linkStyle);
    } else {
        $sheet->setCellValue('K'.$rowDerecha, 'N/A');
    }
    // ==========================================================

    $sheet->getStyle('G'.$rowDerecha.':K'.$rowDerecha)->applyFromArray($dataRowStyle);
    $sheet->getStyle('J'.$rowDerecha)->getNumberFormat()->setFormatCode($currencyFormat)->applyFromArray($negativeStyle);
    $sheet->getStyle('K'.$rowDerecha)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Centrar el link
    $rowDerecha++;
}
// Total Gastos Sede
$sheet->setCellValue('I'.$rowDerecha, 'Total Sede');
$sheet->setCellValue('J'.$rowDerecha, -$total_gastos_sede);
$sheet->getStyle('I'.$rowDerecha.':J'.$rowDerecha)->applyFromArray($totalRowStyle)->applyFromArray($dataRowStyle);
$sheet->getStyle('I'.$rowDerecha)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
$sheet->getStyle('J'.$rowDerecha)->getNumberFormat()->setFormatCode($currencyFormat);
$row_gasto_s_end = $rowDerecha;


// --- 6. APLICAR BORDES A TODAS LAS TABLAS ---
$sheet->getStyle('A'.$row_diario_start.':E'.$row_diario_end)->applyFromArray($tableBorderStyle);
$sheet->getStyle('A'.$row_metodo_start.':D'.$row_metodo_end)->applyFromArray($tableBorderStyle);
// ==========================================================
// ¡MODIFICADO! Bordes hasta K
// ==========================================================
$sheet->getStyle('G'.$row_gasto_p_start.':K'.$row_gasto_p_end)->applyFromArray($tableBorderStyle); // Gasto Personal
$sheet->getStyle('G'.$row_gasto_s_start.':K'.$row_gasto_s_end)->applyFromArray($tableBorderStyle); // Gasto Sede
// ==========================================================


// --- 7. ANCHO DE COLUMNAS (CORREGIDO) ---
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(25);
$sheet->getColumnDimension('D')->setWidth(25);
$sheet->getColumnDimension('E')->setWidth(25);
$sheet->getColumnDimension('F')->setWidth(5); 
$sheet->getColumnDimension('G')->setWidth(30);
$sheet->getColumnDimension('H')->setWidth(20);
$sheet->getColumnDimension('I')->setWidth(45);
$sheet->getColumnDimension('J')->setWidth(25);
// ==========================================================
// ¡NUEVO! Ancho para columna K
// ==========================================================
$sheet->getColumnDimension('K')->setWidth(25); // Ancho para "Ver Comprobante"
// ==========================================================

// 8. ENVIAR EL ARCHIVO AL NAVEGADOR
$fileName = "Reporte_Financiero_" . $fecha_desde . "_a_" . $fecha_hasta . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $fileName . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>