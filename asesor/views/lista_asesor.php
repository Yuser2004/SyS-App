<?php
include __DIR__ . '/../models/conexion.php';
$asesores = $conn->query("
    SELECT a.id_asesor, a.nombre, s.nombre AS nombre_sede
    FROM asesor a
    JOIN sedes s ON a.id_sede = s.id
");
?>

<link rel="stylesheet" href="css/tabla_estilo.css">

<div class="members">
    <a href="#" class="btnfos btnfos-3" onclick="cargarContenido('asesor/views/crear_asesor.php'); return false;">Registrar Asesor</a>

    <h2 class="titulo_lista">LISTA DE ASESORES</h2>

    <input type="text" id="buscador" placeholder="Buscar por nombre o sede...">

    <table role="grid">
        <thead>
            <tr>
                <th>👤</th>
                <th>Nombre</th>
                <th>Sede</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($asesores && $asesores->num_rows > 0): ?>
                <?php while ($asesor = $asesores->fetch_assoc()): ?>
                    <tr class="visible">
                        <td><img width="40" src="https://img.icons8.com/ios-filled/50/user.png" alt="asesor"></td>
                        <td><input type="text" value="<?= htmlspecialchars($asesor['nombre']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($asesor['nombre_sede']) ?>" readonly></td>
                        <td class="acciones">
                            <a href="#" class="btnfos btnfos-3" onclick="cargarContenido('asesor/views/editar_asesor.php?id=<?= $asesor['id_asesor'] ?>')">Editar</a>
                            <a href="#" class="btnfos btnfos-3" onclick="eliminarAsesor(<?= $asesor['id_asesor'] ?>)">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No hay asesores registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function eliminarAsesor(id) {
    if (!confirm("¿Estás seguro de eliminar este asesor?")) return;

    fetch(`asesor/eliminar_asesor.php?id=${id}`)
        .then(res => res.text())
        .then(resp => {
            if (resp.trim() === "ok") {
                cargarContenido('asesor/views/lista_asesor.php');
            } else {
                alert("Error al eliminar: " + resp);
            }
        })
        .catch(err => alert("Error de red: " + err));
}

inicializarBuscador();
</script>
