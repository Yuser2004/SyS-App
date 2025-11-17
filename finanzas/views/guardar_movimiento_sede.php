<?php
// 1. CONFIGURACIÓN Y CONEXIÓN
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // La respuesta será JSON
include __DIR__ . '/../models/conexion.php';

$response = [
    'success' => false,
    'message' => 'Error desconocido.'
];

// 2. VALIDACIÓN INICIAL
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

// 3. OBTENER Y VALIDAR DATOS DEL FORMULARIO
$monto = $_POST['monto'] ?? 0;
$fecha = $_POST['fecha'] ?? '';
$id_sede_origen = (int)($_POST['id_sede_origen'] ?? 0);
$id_sede_destino = (int)($_POST['id_sede_destino'] ?? 0);
$id_asesor_registra = (int)($_POST['id_asesor_registra'] ?? 0);
$descripcion = $_POST['descripcion'] ?? '';

// --- ¡CORREGIDO! Limpiamos los métodos de pago ---
$metodo_pago_origen = trim($_POST['metodo_pago_origen'] ?? '');
$metodo_pago_destino = trim($_POST['metodo_pago_destino'] ?? '');

$detalle_pago_origen = !empty($_POST['detalle_pago_origen']) ? trim($_POST['detalle_pago_origen']) : null;
$detalle_pago_destino = !empty($_POST['detalle_pago_destino']) ? trim($_POST['detalle_pago_destino']) : null;


// --- Validaciones Cruciales ---
if (empty($monto) || $monto <= 0) {
    $response['message'] = 'El monto debe ser un valor positivo.';
    echo json_encode($response);
    exit;
}
if (empty($fecha)) {
    $response['message'] = 'La fecha es obligatoria.';
    echo json_encode($response);
    exit;
}
if (empty($id_sede_origen) || empty($id_sede_destino)) {
    $response['message'] = 'Debe seleccionar una sede de origen y destino.';
    echo json_encode($response);
    exit;
}
if ($id_sede_origen === $id_sede_destino) {
    $response['message'] = 'La sede de origen y destino no pueden ser la misma.';
    echo json_encode($response);
    exit;
}

// Validación de métodos de pago (ahora con los datos limpios)
if (empty($metodo_pago_origen)) {
    $response['message'] = 'Error: El Método de Salida (Origen) está vacío.';
    echo json_encode($response);
    exit;
}
if (empty($metodo_pago_destino)) {
    $response['message'] = 'Error: El Método de Entrada (Destino) está vacío. Por favor, seleccione uno.';
    echo json_encode($response);
    exit;
}

if (empty($id_asesor_registra)) {
    $response['message'] = 'No se ha identificado al asesor que registra. Por favor, búsquelo y selecciónelo.';
    echo json_encode($response);
    exit;
}
if (empty($descripcion)) {
    $response['message'] = 'La descripción es obligatoria.';
    echo json_encode($response);
    exit;
}

// 4. MANEJO DEL ARCHIVO (COMPROBANTE)
$comprobante_url = null; 

if (isset($_FILES['comprobante']) && $_FILES['comprobante']['error'] === UPLOAD_ERR_OK) {
    $upload_dir_base = 'uploads/movimientos/';
    $upload_dir = __DIR__ . '/../../' . $upload_dir_base;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_info = pathinfo($_FILES['comprobante']['name']);
    $file_extension = strtolower($file_info['extension']);
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

    if (in_array($file_extension, $allowed_extensions)) {
        $new_filename = 'mov_' . $id_sede_origen . '_a_' . $id_sede_destino . '_' . time() . '.' . $file_extension;
        $target_file_path = $upload_dir . $new_filename;

        if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $target_file_path)) {
            $comprobante_url = $upload_dir_base . $new_filename;
        } else {
            $response['message'] = 'Error al mover el archivo comprobante.';
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'Tipo de archivo no permitido. Solo se aceptan: ' . implode(', ', $allowed_extensions);
        echo json_encode($response);
        exit;
    }
}

// 5. INSERCIÓN EN LA BASE DE DATOS
try {
    $sql = "INSERT INTO movimientos_inter_sede (
                monto, 
                fecha, 
                id_sede_origen, 
                metodo_pago_origen, 
                detalle_pago_origen, 
                id_sede_destino, 
                metodo_pago_destino, 
                detalle_pago_destino, 
                id_asesor_registra, 
                descripcion, 
                comprobante_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"; // <-- 11 '?'

    $stmt = $conn->prepare($sql);
    
    // d = double (monto)
    // s = string (fecha)
    // i = integer (id_sede_origen)
    // s = string (metodo_pago_origen)
    // s = string (detalle_pago_origen)
    // i = integer (id_sede_destino)       <-- ¡El #6 es 'i'!
    // s = string (metodo_pago_destino)  <-- ¡El #7 es 's'!
    // s = string (detalle_pago_destino)
    // i = integer (id_asesor_registra)
    // s = string (descripcion)
    // s = string (comprobante_url)
    
    // --- ¡CORREGIDO! Esta es la cadena de 11 letras correcta ---
    $stmt->bind_param("dsississsis", 
        $monto, 
        $fecha, 
        $id_sede_origen, 
        $metodo_pago_origen, 
        $detalle_pago_origen, 
        $id_sede_destino, 
        $metodo_pago_destino, 
        $detalle_pago_destino, 
        $id_asesor_registra, 
        $descripcion, 
        $comprobante_url
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Movimiento registrado con éxito.';
    } else {
        $response['message'] = 'Error al ejecutar la consulta: ' . $stmt->error;
    }

    $stmt->close();

} catch (mysqli_sql_exception $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

$conn->close();

// 6. ENVIAR RESPUESTA JSON
echo json_encode($response);
exit;
?>