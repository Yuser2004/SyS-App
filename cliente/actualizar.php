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

// Validar teléfono: solo números y exactamente 10 dígitos
if (!preg_match("/^\d{10}$/", $telefono)) {
    $errores[] = "El teléfono debe contener solo numeros y exactamente 10 dígitos";
}

// Validar ciudad: solo letras y espacios
if (empty($ciudad) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $ciudad)) {
    $errores[] = "La ciudad debe contener solo letras.";
}

// Dirección no vacía
if (empty($direccion)) {
    $errores[] = "La dirección es obligatoria.";
}

if (!empty($errores)) {
    echo implode("\n", $errores); // envia texto simple
    $conn->close();
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
    echo "ok"; // JS busca este "ok"
} else {
    echo "Error al actualizar: " . $conn->error;
}

$conn->close(); // Cierre manual de conexión
?>