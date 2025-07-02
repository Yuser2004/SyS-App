<?php include '../models/conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        h1 {
            text-align: center;
        }
        .btn-agregar {
            display: block;
            width: fit-content;
            margin: 0 auto 20px auto;
            text-decoration: none;
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
        }
        .acciones a {
            margin-right: 10px;
        }
    </style>
</head>
<body>

    <h1>Listado de Clientes</h1>

    <a href="crear.php" class="btn-agregar">➕ Agregar Cliente</a>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Ciudad</th>
                <th>Dirección</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT * FROM clientes";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $row['id_cliente'] ?></td>
                    <td><?= $row['nombre_completo'] ?></td>
                    <td><?= $row['documento'] ?></td>
                    <td><?= $row['telefono'] ?></td>
                    <td><?= $row['ciudad'] ?></td>
                    <td><?= $row['direccion'] ?></td>
                    <td class="acciones">
                        <a href="editar.php?id=<?= $row['id_cliente'] ?>">✏️ Editar</a>
                        <a href="../eliminar.php?id=<?= $row['id_cliente'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">🗑️ Eliminar</a>
                    </td>
                </tr>
            <?php
                endwhile;
            else:
                echo '<tr><td colspan="7">No hay clientes registrados.</td></tr>';
            endif;

            $conn->close();
            ?>
        </tbody>
    </table>

</body>
</html>
