<div class="members"><?php include __DIR__ . '/../models/conexion.php'; ?>

<a href="#" class="btnfos btnfos-3" onclick="cargarContenido('cliente/views/fragmento_crear.php'); return false;" title="Nuevo cliente">
    <img src="nuevo_cliente.png" alt="Nuevo cliente" style="width: 40px; height: 40px;">
</a>

            <h2 class="titulo_lista">
                    LISTA DE CLIENTES
            </h2>
    <input type="text" id="buscador" placeholder="Buscar por nombre, documento o teléfono...">

    <table role="grid">
        <colgroup>
        <col style="width: 40px;">  <!-- Solo para el ícono -->
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: 180px;"> <!-- Acciones con botones -->
        </colgroup>

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
                    <td><img width="50" height="30" src="https://img.icons8.com/ios-filled/50/user-male-circle.png" alt="user-male-circle"/></td>
                    <td><input type="text" value="<?= htmlspecialchars($row['nombre_completo']) ?>" readonly></td>
                    <td><input type="text" value="<?= htmlspecialchars($row['documento']) ?>" readonly></td>
                    <td><input type="text" value="<?= htmlspecialchars($row['telefono']) ?>" readonly></td>
                    <td class="acciones">
                        <a href="#" class="btnfos btnfos-3" title="Editar cliente" onclick="editarCliente(<?= $row['id_cliente'] ?>); return false;">
                            <img src="editar.png" alt="Editar" style="width: 40px; height: 40px;">
                        </a>
                        <a href="#" class="btnfos btnfos-3" title="Eliminar cliente" onclick="eliminarCliente(<?= $row['id_cliente'] ?>); return false;">
                            <img src="eliminar.png" alt="Eliminar" style="width: 40px; height: 40px;">
                        </a>
                        <a href="#" class="btnfos btnfos-3" title="Nuevo vehículo" onclick="cargarContenido('vehiculo/views/fragmento_crear.php?id_cliente=<?= $row['id_cliente'] ?>&origen=clientes'); return false;">
                            <img src="nuevovehiculo.png" alt="Nuevo vehículo" style="width: 40px; height: 40px;">
                        </a>
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
                    cargarContenido('cliente/views/fragmento_clientes.php');
                } else if (resp.trim() === "no-se-puede-eliminar") {
                    alert("❌ No se puede eliminar este cliente porque tiene servicios registrados.");
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
