<?php
// finanzas/views/guardar_devolucion.php
// Incluimos la conexión a la base de datos.
include __DIR__ . '/../models/conexion.php';

// Nos aseguramos de que la petición sea de tipo POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /SyS-app/');
    exit();
}

// Recibir y validar datos del formulario.
$id_sede_receptora = intval($_POST['id_sede_receptora'] ?? 0);
$id_sede_origen = intval($_POST['id_sede_origen'] ?? 0);
$monto = floatval($_POST['monto'] ?? 0);
$metodo_pago = strtolower(trim($_POST['metodo_pago'] ?? ''));
$fecha = $_POST['fecha'] ?? null;
$concepto = $_POST['concepto'] ?? '';

// Normalizar método de pago
$mapa_metodos = [
    '0' => 'efectivo',
    '1' => 'transferencia',
    '2' => 'tarjeta',
    'efectivo' => 'efectivo',
    'transferencia' => 'transferencia',
    'tarjeta' => 'tarjeta'
];
if (!isset($mapa_metodos[$metodo_pago])) {
    $metodo_pago = 'otro';
} else {
    $metodo_pago = $mapa_metodos[$metodo_pago];
}

// Validar campos obligatorios
if (!$id_sede_receptora || !$id_sede_origen || $monto <= 0 || !$fecha) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "error",
        "message" => "Faltan datos obligatorios o monto inválido."
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Preparamos la consulta para insertar los datos.
$stmt = $conn->prepare("
    INSERT INTO devoluciones_prestamos 
    (fecha, monto, id_sede_receptora, id_sede_origen, metodo_pago, concepto)
    VALUES (?, ?, ?, ?, ?, ?)
");

if ($stmt === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "error",
        "message" => "Error en la preparación de la consulta."
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Vinculamos los parámetros para evitar inyección SQL.
// tipos: s (string) d (double) i (int) i (int) s (string) s (string) => "sdiiss"
$stmt->bind_param("sdiiss",
    $fecha,
    $monto,
    $id_sede_receptora,
    $id_sede_origen,
    $metodo_pago,
    $concepto
);

// Ejecutamos la consulta.
if ($stmt->execute()) {
    // --- RESPUESTA JSON LIMPIA (SIN JS/HTML ECHO) ---
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "ok",
        "message" => "Devolución registrada correctamente"
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar la devolución: " . $stmt->error
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// Cerrar
$stmt->close();
$conn->close();
exit();
