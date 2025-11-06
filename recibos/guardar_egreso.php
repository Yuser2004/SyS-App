<?php
include __DIR__ . '/models/conexion.php';
// AÑADIDO PARA VER ERRORES (¡Recuerda quitar esto en producción!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==========================================================
// 1. RECIBIR DATOS DEL FORMULARIO (VERSIÓN SIMPLIFICADA)
// ==========================================================
$recibo_id = $_POST['recibo_id'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;
$monto = $_POST['monto'] ?? null;
$forma_pago = $_POST['forma_pago'] ?? null;
$fecha = $_POST['fecha'] ?? null;
$tipo_egreso = $_POST['tipo_egreso'] ?? 'servicio'; // Viene del input oculto

// --- Lógica de Detalle de Pago (Cuentas) ---
$detalle_pago = null; // Inicia como null
if ($forma_pago === 'transferencia') {
    $detalle_pago = $_POST['detalle_pago'] ?? null;
    if ($detalle_pago === '') {
        $detalle_pago = null;
    }
}

// --- Lógica de Préstamos (sede_origen_id, sede_destino_id) ELIMINADA ---


// ==========================================================
// 2. MANEJO DEL ARCHIVO PDF (se mantiene igual)
// ==========================================================
$comprobante_pdf = null;
if (isset($_FILES['comprobante_pdf']) && $_FILES['comprobante_pdf']['error'] === UPLOAD_ERR_OK) {
    $carpetaDestino = __DIR__ . '/../uploads/comprobantes/';
    if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }
    $nombreArchivo = uniqid('comprobante_') . '_' . basename($_FILES['comprobante_pdf']['name']);
    $rutaCompleta = $carpetaDestino . $nombreArchivo;
    if (move_uploaded_file($_FILES['comprobante_pdf']['tmp_name'], $rutaCompleta)) {
        $comprobante_pdf = 'uploads/comprobantes/' . $nombreArchivo;
    } else {
        // Opcional: Manejar error si no se pudo mover el archivo
        // echo "Error al mover el archivo.";
        // exit;
    }
}


// ==========================================================
// 3. PREPARAR E INSERTAR EL EGRESO (VERSIÓN SIMPLIFICADA)
// ==========================================================
$sql = "INSERT INTO egresos 
        (recibo_id, descripcion, monto, tipo, forma_pago, comprobante_pdf, fecha, detalle_pago) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    // Tipos: i=recibo_id, s=descripcion, d=monto, s=tipo, s=forma_pago, s=comprobante_pdf, s=fecha, s=detalle_pago
    $stmt->bind_param(
        "isdsssss",
        $recibo_id,
        $descripcion,
        $monto,
        $tipo_egreso,
        $forma_pago,
        $comprobante_pdf,
        $fecha,
        $detalle_pago 
    );

    if ($stmt->execute()) {
        // Respuesta simple para que el AJAX la lea (si decides usar AJAX después)
        // echo "ok"; 
        
        // O la respuesta que tenías antes si sigues con el envío normal
        echo "<script>alert('Egreso registrado correctamente'); window.history.back();</script>";

    } else {
        echo "Error al guardar: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error al preparar la consulta: " . $conn->error;
}

$conn->close();
?>