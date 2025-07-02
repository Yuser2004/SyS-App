<?php
include 'models/conexion.php';

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

// Validar documento como número o nit
if (!preg_match("/^[a-zA-Z0-9\-]+$/", $documento)) {
    $errores[] = "El documento solo puede contener letras, números y guiones.";
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
    echo "<a href='views/crear.php'>← Volver al formulario</a>";
    exit;
}
$verificar = $conn->query("SELECT id_cliente FROM clientes WHERE documento = '$documento'");
if ($verificar->num_rows > 0) {
    echo "<p style='color:red;'>Ya existe un cliente con ese número de documento.</p>";
    echo "<a href='views/crear.php'>← Volver</a>";
    exit;
}

// Insertar si todo está bien
$sql = "INSERT INTO clientes (nombre_completo, documento, telefono, ciudad, direccion, observaciones)
        VALUES ('$nombre', '$documento', '$telefono', '$ciudad', '$direccion', '$observaciones')";


if ($conn->query($sql)) {
    header("Location: views/index.php");
} else {
    echo "Error al guardar: " . $conn->error;
}
$conn->close();
?>

