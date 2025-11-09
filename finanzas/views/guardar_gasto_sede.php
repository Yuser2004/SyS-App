<?php
// finanzas/views/guardar_gasto_sede.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php'; 

$respuesta = ['success' => false, 'message' => 'Error desconocido.'];

if (!$conn) {
     $respuesta['message'] = 'Error fatal: No se pudo conectar a la base de datos. Revisa /finanzas/models/conexion.php';
     echo json_encode($respuesta);
     exit;
}

// --- Captura y Depuración de tipo_gasto ---
// Lo capturamos ANTES de la validación para poder mostrarlo
$tipo_gasto = isset($_POST['tipo_gasto']) ? $_POST['tipo_gasto'] : '';

// --- Validación de Datos ---
// ¡MODIFICADO! Añadimos 'tipo_gasto' a la validación
if (empty($_POST['id_sede']) || empty($_POST['id_asesor']) || empty($_POST['descripcion']) || empty($_POST['monto']) || empty($_POST['metodo_pago']) || empty($_POST['fecha']) || empty($tipo_gasto)) {
    // ¡MENSAJE DE DEPURACIÓN!
    $respuesta['message'] = 'Todos los campos son obligatorios. El tipo de gasto recibido fue: "' . $tipo_gasto . '"';
    echo json_encode($respuesta);
    exit;
}

$id_sede = (int)$_POST['id_sede'];
$id_asesor = (int)$_POST['id_asesor'];
$descripcion = trim($_POST['descripcion']);
$monto = (float)$_POST['monto'];
$metodo_pago = $_POST['metodo_pago'];
$fecha = $_POST['fecha'];
$comprobante_url = NULL;

// --- Validación de valor de tipo_gasto ---
if ($tipo_gasto !== 'sede' && $tipo_gasto !== 'personal') {
    // ¡MENSAJE DE DEPURACIÓN!
    $respuesta['message'] = 'Tipo de gasto no válido. Se recibió el valor: "' . $tipo_gasto . '"';
    echo json_encode($respuesta);
    exit;
}

// --- VALIDACIÓN Y CAPTURA DE DETALLE_PAGO ---
$detalle_pago = NULL; 
if ($metodo_pago === 'transferencia') {
    if (empty($_POST['detalle_pago'])) {
        $respuesta['message'] = 'Para pagos por transferencia, debe seleccionar la cuenta de origen.';
        echo json_encode($respuesta);
        exit;
    }
    $detalle_pago = $_POST['detalle_pago'];
}

// --- Manejo de Subida de Archivo ---
if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == 0) {
    
    $target_dir = __DIR__ . "/../../uploads/comprobantes/"; 
    $file_extension = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        $respuesta['message'] = 'Error: Solo se permiten archivos PDF, JPG, JPEG o PNG.';
        echo json_encode($respuesta);
        exit;
    }
    
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
             $respuesta['message'] = 'Error: No se pudo crear el directorio de subida. Revisa permisos en /uploads/';
             echo json_encode($respuesta);
             exit;
        }
    }

    // ¡MODIFICADO! Usamos $tipo_gasto en el nombre del archivo para diferenciarlo
    $safe_filename = "gasto_" . $tipo_gasto . "_" . $id_sede . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $safe_filename;

    if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $target_file)) {
        $comprobante_url = "uploads/comprobantes/" . $safe_filename;
    } else {
        $respuesta['message'] = 'Error al mover el archivo subido. Revisa permisos.';
        echo json_encode($respuesta);
        exit;
    }
}

// --- Inserción en la Base de Datos ---
try {
    
    // ==========================================================
    // ¡MODIFICADO! SQL AHORA INCLUYE 'tipo_gasto'
    // ==========================================================
    $sql = "INSERT INTO gastos_sede (
                id_sede, id_asesor, descripcion, monto, metodo_pago, 
                tipo_gasto, -- Columna añadida
                detalle_pago, 
                fecha, comprobante_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"; // 9 '?'
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $respuesta['message'] = 'Error al preparar la consulta: ' . $conn->error;
        echo json_encode($respuesta);
        exit;
    }

    // ==========================================================
    // ¡MODIFICADO! bind_param AHORA INCLUYE 'tipo_gasto'
    // ==========================================================
    $stmt->bind_param("iisdsssss", // 9 tipos
        $id_sede, 
        $id_asesor, 
        $descripcion, 
        $monto, 
        $metodo_pago, 
        $tipo_gasto, // Variable añadida
        $detalle_pago, 
        $fecha, 
        $comprobante_url
    );

    if ($stmt->execute()) {
        $respuesta['success'] = true;
        // Mensaje mejorado que confirma el tipo
        $respuesta['message'] = 'Gasto (' . $tipo_gasto . ') registrado correctamente.'; 
    } else {
        $respuesta['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
    }
    $stmt->close();

} catch (Exception $e) {
    $respuesta['message'] = 'Excepción: ' . $e->getMessage();
}

$conn->close();
echo json_encode($respuesta);
exit();
?>