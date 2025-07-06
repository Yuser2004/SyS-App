<?php
include '../models/conexion.php';
$resultado = $conn->query("
    SELECT v.id_vehiculo, v.placa, c.nombre_completo
    FROM vehiculo v
    INNER JOIN clientes c ON v.id_cliente = c.id_cliente
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vehículos Registrados</title>
</head>
<body>
    <h1>Listado de Vehículos</h1>
    <a href="crear.php">➕ Registrar Vehículo</a>
    <table border="1" cellpadding="5">
        <tr>
            <th>ID</th>
            <th>Placa</th>
            <th>Cliente</th>
            <th>Acciones</th>
        </tr>
        <?php while ($row = $resultado->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id_vehiculo'] ?></td>
            <td><?= $row['placa'] ?></td>
            <td><?= $row['nombre_completo'] ?></td>
            <td>
                <a href="editar.php?id=<?= $row['id_vehiculo'] ?>">✏️ Editar</a>
                <a href="../eliminar.php?id=<?= $row['id_vehiculo'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este vehículo?')">🗑️ Eliminar</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>