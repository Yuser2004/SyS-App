<?php
include '../models/conexion.php';

$id = $_GET['id'];
$resultado = $conn->query("SELECT * FROM vehiculo WHERE id_vehiculo = $id");

if ($resultado->num_rows === 0) {
    echo "Vehículo no encontrado.";
    exit;
}

$vehiculo = $resultado->fetch_assoc();
$clientes = $conn->query("SELECT id_cliente, nombre_completo FROM clientes");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Vehículo</title>
    <script>
        function validarFormulario() {
            const placa = document.getElementById('placa').value.trim();
            if (placa === "") {
                alert("La placa no puede estar vacía.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <h1>Editar Vehículo</h1>
    <form action="../actualizar.php" method="POST" onsubmit="return validarFormulario()">
        <input type="hidden" name="id_vehiculo" value="<?= $vehiculo['id_vehiculo'] ?>">

        <label>Placa:</label><br>
        <input type="text" name="placa" id="placa" value="<?= $vehiculo['placa'] ?>" required><br><br>

        <label>Cliente:</label><br>
        <select name="id_cliente" required>
            <option value="">Seleccione un cliente</option>
            <?php while ($cliente = $clientes->fetch_assoc()): ?>
                <option value="<?= $cliente['id_cliente'] ?>" <?= $vehiculo['id_cliente'] == $cliente['id_cliente'] ? 'selected' : '' ?>>
                    <?= $cliente['nombre_completo'] ?>
                </option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Actualizar</button>
    </form>
    <br>
    <a onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">← Volver</a>
</body>
</html>
