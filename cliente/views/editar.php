<?php
include '../models/conexion.php';

$id = $_GET['id'];

$sql = "SELECT * FROM clientes WHERE id_cliente = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();

if (!$cliente) {
    echo "Cliente no encontrado.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
</head>
<body>
    <h1>Editar Cliente</h1>
    <form action="../actualizar.php" method="POST">
        <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">

        <label for="nombre_completo">Nombre completo:</label><br>
        <input type="text" id="nombre_completo" name="nombre_completo" value="<?= $cliente['nombre_completo'] ?>" required><br><br>

        <label for="documento">Documento:</label><br>
        <input type="text" id="documento" name="documento" value="<?= $cliente['documento'] ?>" required><br><br>

        <label for="telefono">Teléfono:</label><br>
        <input type="text" id="telefono" name="telefono" value="<?= $cliente['telefono'] ?>" required><br><br>

        <label for="ciudad">Ciudad:</label><br>
        <input type="text" id="ciudad" name="ciudad" value="<?= $cliente['ciudad'] ?>" required><br><br>

        <label for="direccion">Dirección:</label><br>
        <input type="text" id="direccion" name="direccion" value="<?= $cliente['direccion'] ?>" required><br><br>

        <button type="submit">Actualizar Cliente</button>
    </form>

    <br>
    <a href="index.php">← Volver al listado</a>
</body>
</html>
