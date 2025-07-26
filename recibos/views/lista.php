<?php
include __DIR__ . '/../models/conexion.php';
$recibos = $conn->query("
    SELECT 
        r.id, 
        r.fecha_tramite, 
        c.nombre_completo AS cliente, 
        v.placa, 
        a.nombre AS asesor, 
        r.valor_servicio, 
        r.estado, 
        r.metodo_pago, 
        r.concepto_servicio AS concepto,
        -- LA CORRECCIÓN CLAVE: Usamos una subconsulta para sumar los egresos de forma segura
        (SELECT SUM(e.monto) FROM egresos e WHERE e.recibo_id = r.id) AS valor_total_egresos
    FROM recibos r
    LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
    LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
    LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
    -- Ya no se necesita el JOIN a egresos ni el GROUP BY aquí
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
    <a href="#"
        class="btnfos btnfos-3"
        onclick="exportarAExcel(); return false;"
        title="Exportar a Excel">
        <img src="excel.png" alt="Exportar a Excel" style="width: 35px; height: 35px;">
    </a>
    <h2 class="titulo_lista">LISTA DE RECIBOS</h2>

    <div class="filtros">
        <label>Estado:
            <select id="filtroEstado" class="filtro-item">
                <option value="">Todos</option>
                <option value="completado">Completado</option>
                <option value="pendiente">Pendiente</option>
                <option value="cancelado">Cancelado</option> 
            </select>
        </label>

        <label>Desde:
            <input type="date" id="fechaDesde" class="filtro-item">
        </label>

        <label>Hasta:
            <input type="date" id="fechaHasta" class="filtro-item">
        </label>
        
        <input type="text" id="buscador" class="filtro-item" placeholder="Buscar por cliente, asesor o placa...">
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
        <col style="width: 180px;"> <!-- Acciones con botones -->
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
                        
                        <td><input type="text" value="<?= !empty($recibo['fecha_tramite']) ? date('d/m/Y', strtotime($recibo['fecha_tramite'])) : 'N/A' ?>" readonly></td>
                        
                        <td><input type="text" value="<?= htmlspecialchars($recibo['cliente'] ?? 'Sin Cliente') ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['placa'] ?? 'Sin Placa') ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['concepto'] ?? '') ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($recibo['asesor'] ?? 'Sin Asesor') ?>" readonly></td>
                        <td><input type="text" value="$<?= number_format($recibo['valor_servicio'] ?? 0, 0, ',', '.') ?>" readonly></td>
                        <td><input type="text" value="<?= ucfirst($recibo['estado'] ?? '') ?>" readonly></td>
                        <td><input type="text" value="<?= ucfirst($recibo['metodo_pago'] ?? '') ?>" readonly></td>
                        <td><input type="text" value="$<?= number_format($recibo['valor_total_egresos'] ?? 0, 0, ',', '.') ?>" readonly></td>
                        
                        <td class="acciones">
                            <a href="#" class="btnfos btnfos-3" onclick="cargarContenido('recibos/views/editar.php?id=<?= $recibo['id'] ?>')" title="Editar Recibo">
                                <img src="editar.png" alt="Editar" style="width: 40px; height: 40px;">
                            </a>
                            <a href="#" class="btnfos btnfos-3" onclick="verFactura(<?= $recibo['id'] ?>)" title="Imprimir Recibo">
                                <img src="verfactura.jpg" alt="Factura" style="width: 40px; height: 40px;">
                            </a>
                            <a href="#" class="btnfos btnfos-3" onclick="verEgresos(<?= $recibo['id'] ?>)" title="Añadir Egreso">
                                <img src="finanzas.png" alt="Egresos" style="width: 40px; height: 40px;">
                            </a>
                            <a href="#" class="btnfos btnfos-3" title="Ver Recibo" onclick="cargarContenido('recibos/views/detalle_recibo.php?id=<?= $recibo['id'] ?>'); return false;">
                                <img src="ver_recibo.jpeg" alt="Ver Detalle" style="width: 40px; height: 40px;">
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="11">No hay recibos registrados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
    // =======================================================
    //  FUNCIONES PARA MODALES Y ACCIONES
    // =======================================================

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
                const tempDiv = document.createElement("div");
                tempDiv.innerHTML = html;
                const modalEl = tempDiv.querySelector(".modal");
                if (modalEl) {
                    document.body.appendChild(modalEl);
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                    modalEl.addEventListener('hidden.bs.modal', () => modalEl.remove());
                }
            });
    }


        function verFactura(id) {
            // La ruta al nuevo archivo de impresión.
            const url = `recibos/views/impresion.php?id=${id}`;
            
            // Abre la URL en una nueva pestaña del navegador.
            window.open(url, '_blank');
        }

    // =======================================================
    //  LÓGICA UNIFICADA DE FILTROS
    // =======================================================

    // --- Referencias a TODOS los elementos de filtro ---
    var filtroEstado = document.getElementById('filtroEstado');
    var fechaDesdeInput = document.getElementById('fechaDesde');
    var fechaHastaInput = document.getElementById('fechaHasta');
    var buscadorInput = document.getElementById('buscador'); // El nuevo buscador
    var tablaRecibosBody = document.getElementById('tabla-recibos');
    var filas = tablaRecibosBody.getElementsByTagName('tr');

    function aplicarFiltros() {
        const estadoSeleccionado = filtroEstado.value.toLowerCase().trim();
        const textoBusqueda = buscadorInput.value.toLowerCase().trim();

        // 1. Obtenemos las fechas como texto en formato YYYY-MM-DD
        const fechaDesdeTexto = fechaDesdeInput.value;
        const fechaHastaTexto = fechaHastaInput.value;

        for (const fila of filas) {
            const celdaFecha = fila.cells[1];
            const celdaCliente = fila.cells[2];
            const celdaPlaca = fila.cells[3];
            const celdaAsesor = fila.cells[5];
            const celdaEstado = fila.cells[7];

            if (celdaCliente && celdaPlaca && celdaAsesor && celdaEstado && celdaFecha) {
                const estadoFila = celdaEstado.querySelector('input').value.toLowerCase().trim();
                const cumpleEstado = (estadoSeleccionado === "" || estadoFila === estadoSeleccionado);
                
                // 2. Convertimos la fecha de la fila a formato YYYY-MM-DD
                const partesFecha = celdaFecha.querySelector('input').value.split('/');
                const fechaFilaTexto = `${partesFecha[2]}-${partesFecha[1]}-${partesFecha[0]}`;
                
                // 3. Comparamos las fechas como texto simple
                const cumpleFecha = (!fechaDesdeTexto || fechaFilaTexto >= fechaDesdeTexto) && (!fechaHastaTexto || fechaFilaTexto <= fechaHastaTexto);
                
                const textoFila = `${celdaCliente.querySelector('input').value} ${celdaPlaca.querySelector('input').value} ${celdaAsesor.querySelector('input').value}`.toLowerCase();
                const cumpleBusqueda = textoFila.includes(textoBusqueda);

                if (cumpleEstado && cumpleFecha && cumpleBusqueda) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }
            }
        }
    }
    // --- Añadir los Event Listeners a TODOS los filtros ---
    filtroEstado.addEventListener('change', aplicarFiltros);
    fechaDesdeInput.addEventListener('change', aplicarFiltros);
    fechaHastaInput.addEventListener('change', aplicarFiltros);
    buscadorInput.addEventListener('input', aplicarFiltros); // 'input' es mejor para búsqueda en vivo
    function exportarAExcel() {
        // Obtenemos los valores actuales de los filtros
        const estado = document.getElementById('filtroEstado').value;
        const fechaDesde = document.getElementById('fechaDesde').value;
        const fechaHasta = document.getElementById('fechaHasta').value;
        const busqueda = document.getElementById('buscador').value;

        // Construimos la URL con los parámetros
        const params = new URLSearchParams({
            estado: estado,
            fechaDesde: fechaDesde,
            fechaHasta: fechaHasta,
            busqueda: busqueda
        });

        const url = `recibos/exportar_excel.php?${params.toString()}`;

        // Abrimos la URL en una nueva pestaña para iniciar la descarga
        window.open(url, '_blank');
    }
</script>