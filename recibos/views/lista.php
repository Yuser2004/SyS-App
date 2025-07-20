<?php
include __DIR__ . '/../models/conexion.php';

$recibos = $conn->query("
    SELECT r.id, r.fecha_tramite, c.nombre_completo AS cliente, 
           v.placa, a.nombre AS asesor, r.valor_servicio, 
           r.estado, r.metodo_pago, r.concepto_servicio AS concepto,
           COUNT(e.id) AS total_egresos
    FROM recibos r
    LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
    LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
    LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
    LEFT JOIN egresos e ON e.recibo_id = r.id
    GROUP BY r.id
    ORDER BY r.id DESC
");

?>

<link rel="stylesheet" href="css/tabla_estilo.css">
<link rel="stylesheet" href="recibos/public/egresos_modal.css">

<div class="members">
    <a href="#"
        class="btnfos btnfos-3"
        onclick="cargarContenido('recibos/views/crear.php'); return false;"
        title="Registrar Recibo">
        <img src="nuevo_recibo.png" alt="Registrar Recibo" style="width: 35px; height: 35px;">
    </a>

    <h2 class="titulo_lista">LISTA DE RECIBOS</h2>

    <div class="filtros">

        <label>Estado:
        <select id="filtroEstado">
            <option value="">Todos</option>
            <option value="completado">Completado</option>
            <option value="pendiente">Pendiente</option>
            <option value="cancelado">Cancelado</option> 
        </select>
        </label>

        <label>Desde:
            <input type="date" id="fechaDesde">
        </label>

        <label>Hasta:
            <input type="date" id="fechaHasta">
        </label>
        <input type="text" id="buscador" placeholder="Buscar por cliente, asesor o placa...">

    </div>

    <table role="grid">
        <colgroup>
        <col style="width: 40px;">  <!-- Solo para el ícono -->
        <col style="width: 120px;">  <!-- Solo para el ícono -->
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: auto;">
        <col style="width: 60px;">
        <col style="width: 100px;"> <!-- Acciones con botones -->
        </colgroup>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Concepto</th>
                <th>Asesor</th>
                <th>Valor</th>
                <th>Estado</th>
                <th>Método Pago</th>
                <th>Egresos</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="tabla-recibos">
            <?php if ($recibos && $recibos->num_rows > 0): ?>
                <?php while ($recibo = $recibos->fetch_assoc()): ?>
                    <tr class="visible">
                        <td><?= $recibo['id'] ?></td>
                        <td><input type="text" value="<?= date('d/m/Y', strtotime($recibo['fecha_tramite'])) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['cliente']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['placa']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['concepto']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['asesor']) ?>" readonly></td>
                        <td><input type="text" value="$<?= number_format($recibo['valor_servicio'], 0, ',', '.') ?>" readonly></td>
                        <td><input type="text" value="<?= ucfirst($recibo['estado']) ?>" readonly></td>
                        <td><input type="text" value="<?= ucfirst($recibo['metodo_pago']) ?>" readonly></td>
                        <td><input type="text" value="<?= $recibo['total_egresos'] ?>" readonly></td>
                        <td class="acciones">
                            <a href="#"
                            class="btnfos btnfos-3"
                            onclick="cargarContenido('recibos/views/editar.php?id=<?= $recibo['id'] ?>')"
                            title="Editar Recibo">
                                <img src="editar.png" alt="Editar" style="width: 40px; height: 40px;">
                            </a>
                            
<!--                             <a href="#"
                            class="btnfos btnfos-3"
                            onclick="eliminarRecibo(<?= $recibo['id'] ?>)"
                            title="Eliminar Recibo">
                                <img src="eliminar.png" alt="Eliminar" style="width: 40px; height: 40px;">
                            </a> --> <!-- No eliminar recibo, tener en cuenta para un futuro -->
                            
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
            // Crear contenedor temporal
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = html;

            // Extraer el modal
            const modalEl = tempDiv.querySelector(".modal");
            if (modalEl) {
                document.body.appendChild(modalEl);

                // Inicializar y mostrar con Bootstrap
                const modal = new bootstrap.Modal(modalEl);
                modal.show();

                // Remover el modal del DOM al cerrarse
                modalEl.addEventListener('hidden.bs.modal', () => {
                    modalEl.remove();
                });
            } else {
                console.error("No se encontró el modal en el HTML cargado");
            }
        })
        .catch(err => {
            console.error("Error al cargar el modal:", err);
        });
}
</script>
<script>
    // --- OBTENER REFERENCIAS A LOS ELEMENTOS ---
    const filtroEstado = document.getElementById('filtroEstado');
    const fechaDesdeInput = document.getElementById('fechaDesde');
    const fechaHastaInput = document.getElementById('fechaHasta');
    const tablaRecibosBody = document.getElementById('tabla-recibos');
    const filas = tablaRecibosBody.getElementsByTagName('tr');

    // --- FUNCIÓN CENTRAL DE FILTRADO ---
    // Esta función se encarga de aplicar TODOS los filtros a la vez.
    function aplicarFiltros() {
        // 1. Obtener los valores actuales de todos los filtros
        const estadoSeleccionado = filtroEstado.value.toLowerCase().trim();
        
        // Convertimos las fechas de los inputs a objetos Date para poder comparar.
        // Si un input está vacío, su valor será null.
        const fechaDesde = fechaDesdeInput.value ? new Date(fechaDesdeInput.value + 'T00:00:00') : null;
        const fechaHasta = fechaHastaInput.value ? new Date(fechaHastaInput.value + 'T23:59:59') : null;

        // 2. Recorrer todas las filas para decidir si se muestran u ocultan
        for (const fila of filas) {
            // --- Lectura de datos de la fila ---
            const celdaEstado = fila.cells[7];
            const celdaFecha = fila.cells[1];
            
            if (celdaEstado && celdaFecha) {
                // Obtenemos el estado de la fila
                const estadoFila = celdaEstado.querySelector('input').value.toLowerCase().trim();

                // Obtenemos y parseamos la fecha de la fila (formato dd/mm/YYYY)
                const fechaFilaTexto = celdaFecha.querySelector('input').value;
                const partesFecha = fechaFilaTexto.split('/'); // [dd, mm, YYYY]
                const fechaFila = new Date(`${partesFecha[2]}-${partesFecha[1]}-${partesFecha[0]}`);

                // --- Lógica de decisión ---
                // Una fila debe cumplir ambas condiciones para ser visible.
                
                // Condición 1: El estado coincide
                const cumpleEstado = (estadoSeleccionado === "" || estadoFila === estadoSeleccionado);
                
                // Condición 2: La fecha está en el rango
                const cumpleFecha = 
                    (!fechaDesde || fechaFila >= fechaDesde) && 
                    (!fechaHasta || fechaFila <= fechaHasta);

                // Decisión Final: Si cumple ambas, se muestra. Si no, se oculta.
                if (cumpleEstado && cumpleFecha) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }
            }
        }
    }

    // --- AÑADIR LOS EVENT LISTENERS ---
    // Le decimos al navegador que ejecute nuestra función central cuando CUALQUIER filtro cambie.
    filtroEstado.addEventListener('change', aplicarFiltros);
    fechaDesdeInput.addEventListener('change', aplicarFiltros);
    fechaHastaInput.addEventListener('change', aplicarFiltros);

</script>