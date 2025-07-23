<?php
// Incluir la conexión a la base de datos
include __DIR__ . '/../models/conexion.php';

// 1. VERIFICAR QUE SE RECIBEN DATOS POR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. OBTENER LOS DATOS DEL FORMULARIO
    $descripcion = $_POST['descripcion'] ?? '';
    $monto = $_POST['monto'] ?? 0;
    $fecha = $_POST['fecha'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo'; // <-- SE AÑADE LA NUEVA VARIABLE

    // 3. VALIDAR QUE LOS DATOS ESENCIALES NO ESTÉN VACÍOS
    if (!empty($descripcion) && $monto > 0 && !empty($fecha) && !empty($tipo)) {
        
        // 4. PREPARAR Y EJECUTAR LA CONSULTA SQL PARA INSERTAR
        // Se añade la columna 'metodo_pago' y un nuevo '?'
        $stmt = $conn->prepare("INSERT INTO gastos (descripcion, monto, fecha, tipo, metodo_pago) VALUES (?, ?, ?, ?, ?)");
        
        // Se actualiza el bind_param para incluir la nueva variable (sdsss)
        $stmt->bind_param("sdsss", $descripcion, $monto, $fecha, $tipo, $metodo_pago);
        
        // 5. VERIFICAR SI LA INSERCIÓN FUE EXITOSA Y RESPONDER
        if ($stmt->execute()) {
            echo "ok"; // Éxito
        } else {
            // Si falla, imprime la razón del error
            echo "Error al guardar en la base de datos: " . $stmt->error;
        }
        $stmt->close();

    } else {
        // Si los datos no son válidos
        echo "Error: Todos los campos son obligatorios.";
    }

} else {
    // Si se intenta acceder al archivo directamente
    echo "Error: Método de solicitud no válido.";
}
?>