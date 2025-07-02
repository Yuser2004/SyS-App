<?php include '../models/conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cliente</title>
</head>
<body>
    <h1>Registrar Nuevo Cliente</h1>
    <form action="../guardar.php" method="POST">
        <label for="nombre_completo">Nombre completo:</label><br>
        <input type="text" id="nombre_completo" name="nombre_completo" required><br><br>

        <label for="documento">Documento:</label><br>
        <input type="text" id="documento" name="documento" required><br><br>

        <label for="telefono">Teléfono:</label><br>
        <input type="text" id="telefono" name="telefono" required><br><br>

        <label for="ciudad">Ciudad:</label><br>
        <input type="text" id="ciudad" name="ciudad" required><br><br>

        <label for="direccion">Dirección:</label><br>
        <input type="text" id="direccion" name="direccion" required><br><br>

        <button type="submit">Guardar Cliente</button>
    </form>

    <br>
    <a href="index.php">← Volver al listado</a>
</body>
</html>
