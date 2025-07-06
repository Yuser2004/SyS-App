<div class="members"><?php include __DIR__ . '/../models/conexion.php'; ?>

    <a href="cliente/views/crear.php" class="btn-agregar">➕ Agregar Cliente</a>
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
                    <td><img src="https://assets.codepen.io/605876/cropped-headshot--saturated-low-res.jpg" alt="avatar"/></td>
                    <td><input type="text" value="<?= htmlspecialchars($row['nombre_completo']) ?>" readonly></td>
                    <td><input type="text" value="<?= htmlspecialchars($row['documento']) ?>" readonly></td>
                    <td><input type="text" value="<?= htmlspecialchars($row['telefono']) ?>" readonly></td>
                    <td class="acciones">
                        <a href="#" onclick="editarCliente(<?= $row['id_cliente'] ?>); return false;">✏️ Editar</a>
                        <a href="#" onclick="eliminarCliente(<?= $row['id_cliente'] ?>); return false;">🗑️ Eliminar</a>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="5">No hay clientes registrados.</td></tr>
            <?php endif; $conn->close(); ?>
        </tbody>
    </table>
</div>
<script>
function editarCliente(id) {
    cargarContenido(`cliente/views/fragmento_editar.php?id=${id}`);
}
function eliminarCliente(id) {
    if (confirm("¿Seguro que deseas eliminar este cliente?")) {
        fetch(`cliente/eliminar.php?id=${id}`)
            .then(res => res.text())
            .then(resp => {
                if (resp.trim() === "ok") {
                    // Recargar el fragmento de clientes
                    cargarContenido('cliente/views/fragmento_clientes.php');
                } else {
                    alert("Error al eliminar: " + resp);
                }
            })
            .catch(error => {
                alert("Error en la solicitud: " + error);
            });
    }
}
</script>
<script>
    inicializarBuscador();
</script>
