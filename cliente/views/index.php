<?php include '../models/conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
</head>
<body>

    <h1>Listado de Clientes</h1>

    <a href="crear.php" class="btn-agregar">➕ Agregar Cliente</a>
    <input type="text" id="buscador" placeholder="Buscar por nombre, documento o teléfono..." style="display:block; margin: 0 auto 20px auto; padding: 10px; width: 300px;">

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Ciudad</th>
                <th>Dirección</th>
                <th>Observaciones</th>
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
                    <td><?= $row['observaciones'] ?></td>
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
    <script>
        const buscador = document.getElementById('buscador');
        const filas = document.querySelectorAll('table tbody tr');

        buscador.addEventListener('input', function () {
            const valor = this.value.toLowerCase();

            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(valor) ? '' : 'none';
            });
        });
    </script>

</body>
</html>
