<?php
// finanzas/views/guardar_devolucion.php

// Incluimos la conexión a la base de datos.
include __DIR__ . '/../models/conexion.php';

// Nos aseguramos de que la petición sea de tipo POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recibir y validar datos del formulario.
    $id_sede_receptora = intval($_POST['id_sede_receptora']);
    $id_sede_origen = intval($_POST['id_sede_origen']);
    $monto = floatval($_POST['monto']);
    $metodo_pago = $_POST['metodo_pago'];
    $fecha = $_POST['fecha'];
    $concepto = $_POST['concepto'] ?? ''; // Asignar un valor por defecto si no se envía.

    // Validar que los campos obligatorios no estén vacíos.
    if (empty($id_sede_receptora) || empty($id_sede_origen) || empty($monto) || empty($metodo_pago) || empty($fecha)) {
        // En caso de error, es mejor enviar un texto plano que el JavaScript pueda mostrar.
        http_response_code(400); // Bad Request
        die("Error: Faltan datos obligatorios.");
    }

    // Preparamos la consulta para insertar los datos.
    $stmt = $conn->prepare("
        INSERT INTO devoluciones_prestamos 
        (fecha, monto, id_sede_receptora, id_sede_origen, metodo_pago, concepto)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    // Vinculamos los parámetros para evitar inyección SQL.
    $stmt->bind_param("sdiiis", 
        $fecha, 
        $monto, 
        $id_sede_receptora, 
        $id_sede_origen, 
        $metodo_pago, 
        $concepto
    );

    // Ejecutamos la consulta.
    if ($stmt->execute()) {
        // ¡CAMBIO CLAVE!
        // Si la inserción es exitosa, simplemente respondemos con "ok".
        // El JavaScript del frontend se encargará de la alerta y la recarga.
        echo "ok";
    } else {
        // Si hay un error, lo enviamos como texto para que la alerta lo muestre.
        http_response_code(500); // Internal Server Error
        echo "Error al guardar la devolución: " . $stmt->error;
    }
    
    // Cerramos la sentencia y la conexión.
    $stmt->close();
    $conn->close();
    exit();

} else {
    // Si alguien intenta acceder a este archivo directamente por URL, lo redirigimos.
    header("Location: /SyS-app/");
    exit();
}
?>  