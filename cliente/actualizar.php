<?php
include 'models/conexion.php';
$id = intval($_POST['id_cliente']); // Forzamos a que sea número entero
$nombre = trim($_POST['nombre_completo']);
$documento = trim($_POST['documento']);
$telefono = trim($_POST['telefono']);
$ciudad = trim($_POST['ciudad']);
$direccion = trim($_POST['direccion']);
$observaciones = trim($_POST['observaciones']);

$errores = [];

// Validar solo letras y espacios en nombre
if (empty($nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $nombre)) {
    $errores[] = "El nombre debe contener solo letras y espacios.";
}

// Validar documento como número
if (!is_numeric($documento)) {
    $errores[] = "El documento debe ser un número.";
}

// Validar teléfono: solo números y exactamente 10 dígitos
if (!preg_match("/^\d{10}$/", $telefono)) {
    $errores[] = "El teléfono debe contener exactamente 10 dígitos.";
}

// Validar ciudad: solo letras y espacios
if (empty($ciudad) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $ciudad)) {
    $errores[] = "La ciudad debe contener solo letras.";
}

// Dirección no vacía
if (empty($direccion)) {
    $errores[] = "La dirección es obligatoria.";
}

// Mostrar errores si existen
if (!empty($errores)) {
    foreach ($errores as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    echo "<a href='views/editar.php?id=$id'>← Volver al formulario</a>";
    $conn->close(); // Cierra la conexión antes de salir
    exit;
}

// Si no hay errores, actualizar
$sql = "UPDATE clientes SET 
            nombre_completo='$nombre', 
            documento='$documento', 
            telefono='$telefono', 
            ciudad='$ciudad', 
            direccion='$direccion',
            observaciones='$observaciones'
        WHERE id_cliente='$id'";

if ($conn->query($sql)) {
    header("Location: views/index.php");
} else {
    echo "Error al actualizar: " . $conn->error;
}

$conn->close(); // Cierre manual de conexión
?>
