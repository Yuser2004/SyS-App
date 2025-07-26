<?php

include __DIR__ . '/models/conexion.php';

// 1. Obtener los filtros desde la URL (GET)
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fechaDesde'] ?? '';
$fechaHasta = $_GET['fechaHasta'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';

// ==========================================================
// ESTA ES LA PARTE QUE FALTABA
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
        (SELECT SUM(e.monto) FROM egresos e WHERE e.recibo_id = r.id) AS valor_total_egresos
    FROM recibos r
    LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
    LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
    LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
";
// ==========================================================


// El código que construye el WHERE se mantiene igual
$where = [];
$params = [];
$types = '';

// ... (el resto de tu código para construir el WHERE está perfecto) ...
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

// Ya no se necesita el GROUP BY
$sql .= " ORDER BY r.id DESC";


// El resto del script para ejecutar la consulta y generar el CSV está perfecto.
$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultado = $stmt->get_result();

// 3. Generar el archivo CSV
$nombreArchivo = 'reporte_recibos_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $nombreArchivo);

$output = fopen('php://output', 'w');

// Escribir la fila de encabezados
fputcsv($output, [
    'ID Recibo', 'Fecha', 'Cliente', 'Placa', 'Concepto', 
    'Asesor', 'Valor Servicio', 'Estado', 'Metodo de Pago', 'Valor Total Egresos'
]);

// Escribir los datos
if ($resultado->num_rows > 0) {
    while ($fila = $resultado->fetch_assoc()) {
        fputcsv($output, [
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
        ]);
    }
}

fclose($output);
exit();
?>