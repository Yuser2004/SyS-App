<div class="members">
<?php
include __DIR__ . '/../models/conexion.php';
$sedes = $conn->query("
    SELECT s.*, COUNT(a.id_asesor) AS total_asesores
    FROM sedes s
    LEFT JOIN asesor a ON a.id_sede = s.id
    GROUP BY s.id
");
?>

<link rel="stylesheet" href="css/tabla_estilo.css">

<a href="#" title="Añadir Sede" class="btnfos btnfos-3" onclick="cargarContenido('sedes/views/crear_sede.php'); return false;">
    <img src="cliente.png" alt="Añadir Asesor" style="width: 35px; height: 35px;">
</a> 
<h2 class="titulo_lista">LISTA DE SEDES</h2>

<table role="grid">
    <thead>
        <tr>
            <th></th>
            <th>Nombre</th>
            <th>Dirección</th>
            <th>Asesores</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($sedes && $sedes->num_rows > 0): ?>
            <?php while ($sede = $sedes->fetch_assoc()): ?>
                <tr class="visible">
                    <td><img width="40" src="https://img.icons8.com/ios-filled/50/building.png" alt="sede"></td>
                    <td><input type="text" value="<?= htmlspecialchars($sede['nombre']) ?>" readonly></td>
                    <td><input type="text" value="<?= htmlspecialchars($sede['direccion']) ?>" readonly></td>
                    <td><input type="text" value="<?= $sede['total_asesores'] ?>" readonly></td>   
                    <td class="acciones">
                        <a href="#" title="Editar Sede" class="btnfos btnfos-3" onclick="cargarContenido('sedes/views/editar_sede.php?id=<?= $sede['id'] ?>'); return false;">
                            <img src="editar.png" alt="Editar" style="width: 40px; height: 40px;">
                        </a>
                        <a href="#" title="Eliminar Sede" class="btnfos btnfos-3" onclick="eliminarSede(<?= $sede['id'] ?>); return false;">
                            <img src="eliminar.png" alt="Eliminar" style="width: 40px; height: 40px;">
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4">No hay sedes registradas.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<script>
function eliminarSede(id) {
    if (!confirm("¿Estás seguro de eliminar esta sede?")) return;

    fetch(`sedes/eliminar_sede.php?id=${id}`)
        .then(res => res.text())
        .then(resp => {
            if (resp.trim() === "ok") {
                cargarContenido('sedes/views/lista_sedes.php');
            } else {
                alert("Error al eliminar: " + resp);
            }
        })
        .catch(err => alert("Error de red: " + err));
}

inicializarBuscador();
</script>
