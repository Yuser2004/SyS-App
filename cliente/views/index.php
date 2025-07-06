<?php include '../models/conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Clientes</title>
    <link rel="stylesheet" href="../public/css/tabla_estilo.css">
</head>
<body>
    <h1>Clientes</h1>

    <div class="members">
        <a href="crear.php" class="btn-agregar">➕ Agregar Cliente</a>
        <input type="text" id="buscador" placeholder="Buscar por nombre, documento o teléfono...">

        <table role="grid">
            <thead>
                <tr>
                    <th></th>
                    <th>Nombre Completo</th>
                    <th>Documento</th>
                    <th>Teléfono</th>
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
                    <tr class="visible">
                        <td>
                            <img src="https://assets.codepen.io/605876/cropped-headshot--saturated-low-res.jpg" alt="avatar"/>
                        </td>
                        <td>
                            <input aria-label="nombre" type="text" value="<?= htmlspecialchars($row['nombre_completo']) ?>" readonly>
                        </td>
                        <td>
                            <input aria-label="documento" type="text" value="<?= htmlspecialchars($row['documento']) ?>" readonly>
                        </td>
                        <td>
                            <input aria-label="telefono" type="text" value="<?= htmlspecialchars($row['telefono']) ?>" readonly>
                        </td>
                        <td class="acciones">
                            <a href="editar.php?id=<?= $row['id_cliente'] ?>">✏️ Editar</a>
                            <a href="../eliminar.php?id=<?= $row['id_cliente'] ?>" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">🗑️ Eliminar</a>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                    echo '<tr><td colspan="5">No hay clientes registrados.</td></tr>';
                endif;
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const buscador = document.getElementById('buscador');
    const filas = document.querySelectorAll('table tbody tr');

    buscador.addEventListener('input', function () {
        const valorBuscado = this.value.toLowerCase();

        filas.forEach(fila => {
            const inputsDeLaFila = fila.querySelectorAll('input');
            if (inputsDeLaFila.length === 0) return;

            let textoDeLaFila = '';
            inputsDeLaFila.forEach(input => {
                textoDeLaFila += input.value.toLowerCase() + ' ';
            });

            const coincidencia = textoDeLaFila.includes(valorBuscado);
            fila.classList.remove('visible', 'oculto');
            fila.classList.add(coincidencia ? 'visible' : 'oculto');
        });
    });
});
    </script>
</body>
</html>