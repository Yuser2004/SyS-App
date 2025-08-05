<?php
include __DIR__ . '/models/conexion.php';

// ==========================================================
// 1. RECIBIR DATOS DEL FORMULARIO
// ==========================================================
$recibo_id = $_POST['recibo_id'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;
$monto = $_POST['monto'] ?? null;
$forma_pago = $_POST['forma_pago'] ?? null;
$fecha = $_POST['fecha'] ?? null;
$tipo = $_POST['tipo_egreso'] ?? 'servicio';

// La sede origen viene del formulario si es un préstamo
$sede_origen_id = ($tipo === 'prestamo') ? ($_POST['sede_origen_id'] ?? null) : null;


// ==========================================================
// 2. DETERMINAR LA SEDE DESTINO (AUTOMÁTICAMENTE)
// ==========================================================
$sede_destino_id = null;
if ($tipo === 'prestamo' && $recibo_id) {
    // Buscamos la sede del asesor al que pertenece el RECIBO
    $stmt_destino = $conn->prepare(
        "SELECT a.id_sede FROM recibos r 
         JOIN asesor a ON r.id_asesor = a.id_asesor 
         WHERE r.id = ?"
    );
    $stmt_destino->bind_param("i", $recibo_id);
    $stmt_destino->execute();
    $resultado_destino = $stmt_destino->get_result();
    if ($fila = $resultado_destino->fetch_assoc()) {
        $sede_destino_id = $fila['id_sede'];
    }
    $stmt_destino->close();
}


// ==========================================================
// 3. MANEJO DEL ARCHIVO PDF (se mantiene igual)
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
        echo "Error al mover el archivo.";
        exit;
    }
}


// ==========================================================
// 4. PREPARAR E INSERTAR EL EGRESO
// ==========================================================
$sql = "INSERT INTO egresos 
        (recibo_id, descripcion, monto, forma_pago, comprobante_pdf, fecha, tipo, sede_origen_id, sede_destino_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        "isdssssii",
        $recibo_id,
        $descripcion,
        $monto,
        $forma_pago,
        $comprobante_pdf,
        $fecha,
        $tipo,
        $sede_origen_id,     // Sede seleccionada en el formulario
        $sede_destino_id     // Sede calculada a partir del recibo
    );

    if ($stmt->execute()) {
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