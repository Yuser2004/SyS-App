<?php
include __DIR__ . '/../models/conexion.php';

$recibos = $conn->query("
    SELECT r.id, r.fecha_tramite, c.nombre_completo AS cliente, 
           v.placa, a.nombre AS asesor, r.valor_servicio, 
           r.estado, r.metodo_pago, r.concepto_servicio AS concepto
    FROM recibos r
    LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
    LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
    LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
    ORDER BY r.id DESC
");
?>

<link rel="stylesheet" href="css/tabla_estilo.css">

<div class="members">
    <a href="#"
        class="btnfos btnfos-3"
        onclick="cargarContenido('recibos/views/crear.php'); return false;"
        title="Registrar Recibo">
        <img src="nuevo_recibo.png" alt="Registrar Recibo" style="width: 35px; height: 35px;">
    </a>

    <h2 class="titulo_lista">LISTA DE RECIBOS</h2>

    <input type="text" id="buscador" placeholder="Buscar por cliente, asesor o placa...">

    <table role="grid">
        <colgroup>
        <col style="width: 40px;">  <!-- Solo para el ícono -->
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: 180px;"> <!-- Acciones con botones -->
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Concepto</th>
                <th>Asesor</th>
                <th>Valor</th>
                <th>Estado</th>
                <th>Método Pago</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-recibos">
            <?php if ($recibos && $recibos->num_rows > 0): ?>
                <?php while ($recibo = $recibos->fetch_assoc()): ?>
                    <tr class="visible">
                        <td><?= $recibo['id'] ?></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['cliente']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['placa']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['concepto']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['asesor']) ?>" readonly></td>
                        <td><input type="text" value="$<?= number_format($recibo['valor_servicio'], 0, ',', '.') ?>" readonly></td>
                        <td><input type="text" value="<?= ucfirst($recibo['estado']) ?>" readonly></td>
                        <td><input type="text" value="<?= ucfirst($recibo['metodo_pago']) ?>" readonly></td>
                        <td class="acciones">
                            <a href="#"
                            class="btnfos btnfos-3"
                            onclick="cargarContenido('recibos/views/editar.php?id=<?= $recibo['id'] ?>')"
                            title="Editar Recibo">
                                <img src="editar.png" alt="Editar" style="width: 40px; height: 40px;">
                            </a>
                            
                            <a href="#"
                            class="btnfos btnfos-3"
                            onclick="eliminarRecibo(<?= $recibo['id'] ?>)"
                            title="Eliminar Recibo">
                                <img src="eliminar.png" alt="Eliminar" style="width: 40px; height: 40px;">
                            </a>
                            
                            <a href="#"
                            class="btnfos btnfos-3"
                            onclick="verEgresos(<?= $recibo['id'] ?>)"
                            title="Ver Egresos del Recibo">
                                <img src="finanzas.png" alt="Egresos" style="width: 40px; height: 40px;">
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="8">No hay recibos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function eliminarRecibo(id) {
    if (!confirm("¿Estás seguro de eliminar este recibo?")) return;

    fetch("recibos/eliminar.php", {
        method: "POST",
        body: new URLSearchParams({ id })
    })
    .then(res => res.text())
    .then(resp => {
        if (resp.trim() === "ok") {
            cargarContenido('recibos/views/lista.php');
        } else {
            alert("Error al eliminar: " + resp);
        }
    })
    .catch(err => alert("Error de red: " + err));
}


function verEgresos(id) {
    fetch(`recibos/views/egresos_modal.php?id=${id}`)
        .then(res => res.text())
        .then(html => {
            const modal = document.createElement("div");
            modal.innerHTML = html;
            document.body.appendChild(modal);
        });
}

// Buscador en vivo
document.getElementById("buscador").addEventListener("input", function () {
    const filtro = this.value.toLowerCase();
    const filas = document.querySelectorAll("#tabla-recibos tr");

    filas.forEach(fila => {
        const textoFila = fila.innerText.toLowerCase();
        fila.classList.toggle("visible", textoFila.includes(filtro));
    });
});
</script>
