<?php
// finanzas/views/guardar_gasto_sede.php
header('Content-Type: application/json');

// ==========================================================
// ¡RUTA CORREGIDA!
// Debe ser '../models/' para subir a /finanzas y entrar a /models
// ==========================================================
include __DIR__ . '/../models/conexion.php'; 

$respuesta = ['success' => false, 'message' => 'Error desconocido.'];

// ==========================================================
// ¡NUEVO! VERIFICAR CONEXIÓN
// ==========================================================
if (!$conn) {
     $respuesta['message'] = 'Error fatal: No se pudo conectar a la base de datos. Revisa /finanzas/models/conexion.php';
     echo json_encode($respuesta);
     exit;
}

// --- Validación de Datos ---
if (empty($_POST['id_sede']) || empty($_POST['id_asesor']) || empty($_POST['descripcion']) || empty($_POST['monto']) || empty($_POST['metodo_pago']) || empty($_POST['fecha'])) {
    $respuesta['message'] = 'Todos los campos marcados son obligatorios.';
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

// ==========================================================
// VALIDACIÓN Y CAPTURA DE DETALLE_PAGO
// ==========================================================
$detalle_pago = NULL; // Por defecto es NULL (para efectivo, tarjeta, etc.)

// Si el método es 'transferencia', el detalle es obligatorio
if ($metodo_pago === 'transferencia') {
    if (empty($_POST['detalle_pago'])) {
        $respuesta['message'] = 'Para pagos por transferencia, debe seleccionar la cuenta de origen.';
        echo json_encode($respuesta);
        exit;
    }
    // Si no está vacío, asignamos el valor
    $detalle_pago = $_POST['detalle_pago'];
}
// ==========================================================
// FIN DE LA LÓGICA
// ==========================================================


// --- Manejo de Subida de Archivo ---
if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] == 0) {
    // ==========================================================
    // ¡RUTA CORREGIDA Y ROBUSTA!
    // Sube 2 niveles (desde /finanzas/views) hasta la raíz de /SyS-app
    // Y luego entra a /uploads/comprobantes/
    // ==========================================================
    $target_dir = __DIR__ . "/../../uploads/comprobantes/"; 
    
    // Crear un nombre de archivo único
    $file_extension = pathinfo($_FILES['comprobante']['name'], PATHINFO_EXTENSION);
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        $respuesta['message'] = 'Error: Solo se permiten archivos PDF, JPG, JPEG o PNG.';
        echo json_encode($respuesta);
        exit;
    }
    
    // Asegurarse de que el directorio exista
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0755, true)) {
             $respuesta['message'] = 'Error: No se pudo crear el directorio de subida. Revisa permisos en /uploads/';
             echo json_encode($respuesta);
             exit;
        }
    }

    // Nombre único: gasto_sede_timestamp.ext
    $safe_filename = "gasto_" . $id_sede . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $safe_filename;

    if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $target_file)) {
        // Guardamos la ruta relativa desde la raíz del proyecto (SyS-app/)
        $comprobante_url = "uploads/comprobantes/" . $safe_filename;
    } else {
        $respuesta['message'] = 'Error al mover el archivo subido. Revisa permisos.';
        echo json_encode($respuesta);
        exit;
    }
}

// --- Inserción en la Base de Datos ---
try {
    
    // SQL AHORA INCLUYE 'detalle_pago'
    $sql = "INSERT INTO gastos_sede (
                id_sede, id_asesor, descripcion, monto, metodo_pago, 
                detalle_pago, -- Columna añadida
                fecha, comprobante_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"; // 8 '?'
    
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        // Capturar error de preparación
        $respuesta['message'] = 'Error al preparar la consulta: ' . $conn->error;
        echo json_encode($respuesta);
        exit;
    }

    // bind_param AHORA INCLUYE 'detalle_pago'
    $stmt->bind_param("iisdssss", // 8 tipos
        $id_sede, 
        $id_asesor, 
        $descripcion, 
        $monto, 
        $metodo_pago, 
        $detalle_pago, // Variable añadida
        $fecha, 
        $comprobante_url
    );

    if ($stmt->execute()) {
        $respuesta['success'] = true;
        $respuesta['message'] = 'Gasto registrado correctamente.';
    } else {
        // Capturar error de ejecución
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