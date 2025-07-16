<!-- vehiculo/views/fragmento_vehiculos.php -->
<div class="members">
    <?php
    include __DIR__ . '/../models/conexion.php';

    $sql = "
        SELECT v.id_vehiculo, v.placa, c.nombre_completo
        FROM vehiculo v
        INNER JOIN clientes c ON v.id_cliente = c.id_cliente
    ";
    $resultado = $conn->query($sql);
    ?>
    <link rel="stylesheet" href="css/tabla_estilo.css">
    <a href="#" class="btnfos btnfos-3" title="Registrar nuevo vehículo" onclick="cargarContenido('vehiculo/views/fragmento_crear.php'); return false;">
        <img src="nuevovehiculo.png" alt="Nuevo vehículo" style="width: 40px; height: 40px;">
    </a>
    <h2 class="titulo_lista">
        LISTA DE VEHICULOS
    </h2>
    <input type="text" id="buscador" placeholder="Buscar por placa o cliente...">

    <table role="grid">
        <colgroup>
        <col style="width: 40px;">  <!-- Solo para el ícono -->
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: 180px;"> <!-- Acciones con botones -->
        </colgroup>
        <thead>
            <tr>
                <th></th>
                <th>Placa</th>
                <th>Cliente</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($resultado && $resultado->num_rows > 0): ?>
                <?php while ($row = $resultado->fetch_assoc()): ?>
                    <tr class="visible"> 
                        <td><img width="50" height="30" src="https://img.icons8.com/glyph-neue/64/car.png" alt="car"/></td>
                        <td><input type="text" value="<?= htmlspecialchars($row['placa']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($row['nombre_completo']) ?>" readonly></td>
                        <td class="acciones">
                            <a href="#" class="btnfos btnfos-3" title="Editar vehículo" onclick="editarVehiculo(<?= $row['id_vehiculo'] ?>); return false;">
                                <img src="editar.png" alt="Editar" style="width: 40px; height: 40px;">
                            </a>
                            <a href="#" class="btnfos btnfos-3" title="Eliminar vehículo" onclick="eliminarVehiculo(<?= $row['id_vehiculo'] ?>); return false;">
                                <img src="eliminar.png" alt="Eliminar" style="width: 40px; height: 40px;">
                            </a>
                        </td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No hay vehículos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function editarVehiculo(id) {
    cargarContenido(`vehiculo/views/fragmento_editar.php?id=${id}`);
}

function eliminarVehiculo(id) {
    if (!confirm("¿Estás seguro de eliminar este vehículo?")) return;

    fetch(`vehiculo/eliminar.php?id=${id}`)
        .then(res => res.text())
        .then(respuesta => {
            const r = respuesta.trim();
            if (r === "ok") {
                cargarContenido('vehiculo/views/lista_vehiculos.php');
            } else if (r === "no-se-puede-eliminar") {
                alert("❌ No se puede eliminar este vehículo porque tiene servicios registrados.");
            } else {
                alert(respuesta);
            }
        })
        .catch(error => alert("Error de red: " + error));
}
inicializarBuscador();
</script>
