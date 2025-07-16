<?php
include __DIR__ . '/models/conexion.php';


$recibo_id = $_POST['recibo_id'] ?? null;
$descripcion = $_POST['descripcion'] ?? null;
$monto = $_POST['monto'] ?? null;
$forma_pago = $_POST['forma_pago'] ?? null;
$fecha = $_POST['fecha'] ?? null;

// Manejo del archivo PDF (opcional)
$comprobante_pdf = null;

if (isset($_FILES['comprobante_pdf']) && $_FILES['comprobante_pdf']['error'] === UPLOAD_ERR_OK) {
    $carpetaDestino = __DIR__ . '/../uploads/comprobantes/';
    if (!file_exists($carpetaDestino)) {
        mkdir($carpetaDestino, 0777, true);
    }

    $nombreArchivo = uniqid('comprobante_') . '.pdf';
    $rutaCompleta = $carpetaDestino . $nombreArchivo;

    if (move_uploaded_file($_FILES['comprobante_pdf']['tmp_name'], $rutaCompleta)) {
        // Guardamos solo el nombre, no la ruta completa
        $comprobante_pdf = 'uploads/comprobantes/' . $nombreArchivo;
    } else {
        echo "Error al mover el archivo.";
        exit;
    }
}

// Preparar e insertar el egreso
$sql = "INSERT INTO egresos (recibo_id, descripcion, monto, forma_pago, comprobante_pdf, fecha) 
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param(
        "isdsss",
        $recibo_id,
        $descripcion,
        $monto,
        $forma_pago,
        $comprobante_pdf,
        $fecha
    );

    if ($stmt->execute()) {
        echo "<script>alert('Egreso registrado correctamente'); window.location.href = '../index.php';</script>";
    } else {
        echo "Error al guardar: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "Error al preparar la consulta: " . $conn->error;
}

$conn->close();
?>
