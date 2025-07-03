<?php
include '../models/conexion.php';

// Obtener lista de clientes
$clientes = $conn->query("SELECT id_cliente, nombre_completo FROM clientes ORDER BY nombre_completo ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Vehículo</title>
    <script>
        function validarFormulario() {
            const placa = document.getElementById("placa").value.trim();
            const cliente = document.getElementById("id_cliente").value;

            let errores = [];

            if (placa === "") {
                errores.push("La placa es obligatoria.");
            }

            if (cliente === "") {
                errores.push("Debes seleccionar un cliente.");
            }

            if (errores.length > 0) {
                alert(errores.join("\n"));
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <h1>Registrar Vehículo</h1>
    <form action="../guardar.php" method="POST" onsubmit="return validarFormulario()">
        <label>Placa:</label><br>
        <input type="text" id="placa" name="placa"><br><br>

        <label>Cliente:</label><br>
        <select name="id_cliente" id="id_cliente">
            <option value="">-- Selecciona un cliente --</option>
            <?php while ($cliente = $clientes->fetch_assoc()): ?>
                <option value="<?= $cliente['id_cliente'] ?>"><?= $cliente['nombre_completo'] ?></option>
            <?php endwhile; ?>
        </select><br><br>

        <button type="submit">Registrar</button>
    </form>
    <br>
    <a href="index.php">← Volver</a>
</body>
</html>
