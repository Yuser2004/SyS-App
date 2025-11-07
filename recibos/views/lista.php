<?php
include __DIR__ . '/../models/conexion.php';

// --- NUEVO: Obtener la lista de Sedes ---
$sedes_result = $conn->query("SELECT id, nombre FROM sedes ORDER BY nombre");
// --- FIN NUEVO ---

// --- PASO 1: Configurar paginación y filtros ---
$recibos_por_pagina = 25;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual < 1) {
    $pagina_actual = 1;
}
$offset = ($pagina_actual - 1) * $recibos_por_pagina;

// --- MODIFICADO: Añadir id_sede ---
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$fechaDesde = $_GET['fechaDesde'] ?? '';
$fechaHasta = $_GET['fechaHasta'] ?? '';
$id_sede = $_GET['id_sede'] ?? ''; // <-- NUEVA LÍNEA

$where_clauses = [];
$params = [];
$types = '';

if (!empty($busqueda)) {
    $where_clauses[] = "(c.nombre_completo LIKE ? OR v.placa LIKE ? OR a.nombre LIKE ?)";
    $like_param = "%$busqueda%";
    array_push($params, $like_param, $like_param, $like_param);
    $types .= 'sss';
}
if (!empty($estado)) {
    $where_clauses[] = "r.estado = ?";
    $params[] = $estado;
    $types .= 's';
}
if (!empty($fechaDesde)) {
    $where_clauses[] = "r.fecha_tramite >= ?";
    $params[] = $fechaDesde;
    $types .= 's';
}
if (!empty($fechaHasta)) {
    $where_clauses[] = "r.fecha_tramite <= ?";
    $params[] = $fechaHasta;
    $types .= 's';
}
// --- NUEVO: Añadir filtro de Sede a la consulta (usando la tabla 'a' de asesor) ---
if (!empty($id_sede)) {
    $where_clauses[] = "a.id_sede = ?";
    $params[] = $id_sede;
    $types .= 'i'; 
}
// --- FIN NUEVO ---

// --- PASO 2: Contar el total de registros (CON FILTROS) ---
$sql_conteo = "SELECT COUNT(r.id) as total FROM recibos r 
               LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
               LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
               LEFT JOIN asesor a ON r.id_asesor = a.id_asesor"; // <-- JOIN ya existe

if (!empty($where_clauses)) {
    $sql_conteo .= " WHERE " . implode(' AND ', $where_clauses);
}

$stmt_conteo = $conn->prepare($sql_conteo);
if (!empty($types)) {
    // Usamos los mismos parámetros de filtro (sin LIMIT/OFFSET)
    $stmt_conteo->bind_param($types, ...$params);
}
$stmt_conteo->execute();
$resultado_conteo = $stmt_conteo->get_result();
$total_registros = $resultado_conteo->fetch_assoc()['total'] ?? 0;

// --- PASO 3: Calcular el total de páginas ---
$total_paginas = ceil($total_registros / $recibos_por_pagina);
if($total_paginas == 0) {
    $total_paginas = 1; // Para que muestre "Página 1 de 1" si no hay resultados
}

// --- PASO 4: Obtener los registros de la página actual (CON FILTROS Y LÍMITE) ---
$sql = "SELECT r.id, r.fecha_tramite, c.nombre_completo AS cliente, v.placa, a.nombre AS asesor, r.valor_servicio, r.estado, r.metodo_pago, r.concepto_servicio AS concepto, (SELECT SUM(e.monto) FROM egresos e WHERE e.recibo_id = r.id AND e.tipo = 'servicio') AS valor_total_egresos 
        FROM recibos r 
        LEFT JOIN clientes c ON r.id_cliente = c.id_cliente 
        LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo 
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor"; // <-- JOIN ya existe

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql .= " ORDER BY r.id DESC LIMIT ? OFFSET ?";
// Añadir los parámetros de paginación al final
$params[] = $recibos_por_pagina;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$recibos = $stmt->get_result();
?>

<link rel="stylesheet" href="css/tabla_estilo.css">
<link rel="stylesheet" href="recibos/public/egresos_modal.css">
<style>
    /* --- Estilos para el Contenedor de Paginación --- */
.paginacion {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 15px; /* Espacio entre los elementos */
    margin-top: 25px;
    padding-bottom: 20px;
    font-family: 'Segoe UI', sans-serif;
}

/* --- Estilo para los botones "Anterior" y "Siguiente" --- */
.paginacion .btn-paginacion {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    background-color: #ffffff;
    color: #007bff;
    text-decoration: none;
    border-radius: 5px;
    font-weight: 600;
    transition: background-color 0.2s, color 0.2s;
}

.paginacion .btn-paginacion:hover {
    background-color: #007bff;
    color: #ffffff;
}

/* --- Estilo para el texto "Página X de Y" --- */
.paginacion .info-pagina {
    font-size: 16px;
    color: #6c757d;
    font-weight: 500;
}
</style>
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
        <!-- NUEVO: Filtro de Sede -->
        <label>Sede:
            <select id="filtroSede" class="filtro-item">
                <option value="">Todas</option>
                <?php
                if ($sedes_result->num_rows > 0) {
                    while($sede = $sedes_result->fetch_assoc()) {
                        // Marcar como seleccionado si el id_sede de la URL coincide
                        $selected = ($id_sede == $sede['id']) ? 'selected' : '';
                        echo "<option value=\"{$sede['id']}\" $selected>" . htmlspecialchars($sede['nombre']) . "</option>";
                    }
                }
                ?>
            </select>
        </label>
        <!-- FIN NUEVO -->

        <label>Estado:
            <select id="filtroEstado" class="filtro-item">
                <option value="">Todos</option>
                <option value="completado" <?= ($estado == 'completado') ? 'selected' : '' ?>>Completado</option>
                <option value="pendiente" <?= ($estado == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="cancelado" <?= ($estado == 'cancelado') ? 'selected' : '' ?>>Cancelado</option> 
            </select>
        </label>

        <label>Desde:
            <input type="date" id="fechaDesde" class="filtro-item" value="<?= htmlspecialchars($fechaDesde) ?>">
        </label>

        <label>Hasta:
            <input type="date" id="fechaHasta" class="filtro-item" value="<?= htmlspecialchars($fechaHasta) ?>">
        </label>
        
        <input type="text" id="buscador" class="filtro-item" placeholder="Buscar por cliente, asesor o placa..." value="<?= htmlspecialchars($busqueda) ?>">
        
    </div>

    <table role="grid">
        <colgroup>
        <col style="width: 40px;">  <!-- # -->
        <col style="width: 120px;"> <!-- Fecha -->
        <col style="width: auto;"> <!-- Cliente -->
        <col style="width: auto;"> <!-- Vehículo -->
        <col style="width: auto;"> <!-- Concepto -->
        <col style="width: auto;"> <!-- Asesor -->
        <col style="width: auto;"> <!-- Valor -->
        <col style="width: auto;"> <!-- Estado -->
        <col style="width: auto;"> <!-- Método Pago -->
        <col style="width: 60px;"> <!-- Egresos -->
        <col style="width: 180px;"> <!-- Acciones -->
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
                <tr><td colspan="11">No hay recibos registrados con los filtros seleccionados.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="paginacion">
        <?php
        // Para mantener los filtros al cambiar de página
        $parametros_url = http_build_query([
            'busqueda' => $busqueda,
            'estado' => $estado,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'id_sede' => $id_sede // <-- AÑADIDO
        ]);
        ?>

        <?php if ($pagina_actual > 1): ?>
            <a href="#" class="btn-paginacion" onclick="cargarContenido('recibos/views/lista.php?pagina=<?= $pagina_actual - 1 ?>&<?= $parametros_url ?>'); return false;">
                &laquo; Anterior
            </a>
        <?php endif; ?>

        <span class="info-pagina">
            Página <?= $pagina_actual ?> de <?= $total_paginas ?>
        </span>

        <?php if ($pagina_actual < $total_paginas): ?>
            <a href="#" class="btn-paginacion" onclick="cargarContenido('recibos/views/lista.php?pagina=<?= $pagina_actual + 1 ?>&<?= $parametros_url ?>'); return false;">
                Siguiente &raquo;
            </a>
        <?php endif; ?>
    </div>
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
                aplicarFiltrosServidor(); // Recargar con filtros actuales
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
        const url = `recibos/views/impresion.php?id=${id}`;
        window.open(url, '_blank');
    }

    // =======================================================
    //  LÓGICA UNIFICADA DE FILTROS (MODIFICADA)
    // =======================================================

    // --- Referencias a TODOS los elementos de filtro ---
    var filtroSede = document.getElementById('filtroSede');
    var filtroEstado = document.getElementById('filtroEstado');
    var fechaDesdeInput = document.getElementById('fechaDesde');
    var fechaHastaInput = document.getElementById('fechaHasta');
    var buscadorInput = document.getElementById('buscador');
    // (ya no necesitamos 'tablaRecibosBody' o 'filas' para esto)

    // --- NUEVA FUNCIÓN PARA RECARGAR LA VISTA CON FILTROS ---
    function aplicarFiltrosServidor() {
        const busqueda = buscadorInput.value;
        const estado = filtroEstado.value;
        const fechaDesde = fechaDesdeInput.value;
        const fechaHasta = fechaHastaInput.value;
        const idSede = filtroSede.value; // <-- NUEVO

        const params = new URLSearchParams({
            busqueda: busqueda,
            estado: estado,
            fechaDesde: fechaDesde,
            fechaHasta: fechaHasta,
            id_sede: idSede // <-- NUEVO
        });

        // Recargar el contenido de la lista con los filtros
        // Mantenemos la página 1 para la nueva búsqueda
        cargarContenido(`recibos/views/lista.php?pagina=1&${params.toString()}`);
    }
    
    // --- Añadir los Event Listeners a TODOS los filtros ---
    // (Estos reemplazarán tu antigua función aplicarFiltros)
    filtroSede.addEventListener('change', aplicarFiltrosServidor);
    filtroEstado.addEventListener('change', aplicarFiltrosServidor);
    fechaDesdeInput.addEventListener('change', aplicarFiltrosServidor);
    fechaHastaInput.addEventListener('change', aplicarFiltrosServidor);
    // Para el buscador, 'input' puede ser muy pesado en el servidor. 
    // 'change' (cuando pierde el foco) o 'keydown' (al presionar Enter) es más ligero.
    // Usaremos 'input' como lo tenías, pero tenlo en cuenta si se vuelve lento.
    buscadorInput.addEventListener('input', aplicarFiltrosServidor); 
    

    // --- MODIFICADA: exportarAExcel() ---
    function exportarAExcel() {
        // Obtenemos los valores actuales de los filtros
        const estado = document.getElementById('filtroEstado').value;
        const fechaDesde = document.getElementById('fechaDesde').value;
        const fechaHasta = document.getElementById('fechaHasta').value;
        const busqueda = document.getElementById('buscador').value;
        const idSede = document.getElementById('filtroSede').value; // <-- AÑADIDO

        // Construimos la URL con los parámetros
        const params = new URLSearchParams({
            estado: estado,
            fechaDesde: fechaDesde,
            fechaHasta: fechaHasta,
            busqueda: busqueda,
            id_sede: idSede // <-- AÑADIDO
        });

        const url = `recibos/exportar_excel.php?${params.toString()}`;

        // Abrimos la URL en una nueva pestaña para iniciar la descarga
        window.open(url, '_blank');
    }
</script>
<script>
    // --- (Tu script de listener delegado no cambia) ---
    console.log("DEBUG: Script principal cargado. Configurando listeners delegados...");
    document.addEventListener('change', function(e) {
        if (e.target && e.target.id === 'forma_pago_egreso') {
            console.log("DEBUG: (Delegado) Cambió 'forma_pago_egreso'. Valor:", e.target.value);
            const detallePagoContainer = document.getElementById('detalle_pago_container_egreso');
            const detallePagoSelect = document.getElementById('detalle_pago_egreso');
            if (detallePagoContainer && detallePagoSelect) {
                let esTransferencia = e.target.value === 'transferencia';
                detallePagoContainer.style.display = esTransferencia ? 'block' : 'none';
                detallePagoSelect.required = esTransferencia;
                if (!esTransferencia) {
                    detallePagoSelect.value = '';
                }
            } else {
                console.error("DEBUG: (Delegado) No se encontró el container o el select de detalle de pago.");
            }
        }
    });
</script>