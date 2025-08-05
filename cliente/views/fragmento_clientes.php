<?php
include __DIR__ . '/../models/conexion.php';

// --- 1. Definir parámetros de paginación y búsqueda ---
$registros_por_pagina = 15; // ¿Cuántos clientes mostrar por página?

// Obtenemos el número de página actual. Si no se especifica, es la página 1.
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;

// Obtenemos el término de búsqueda. Usamos htmlspecialchars para seguridad.
$busqueda = isset($_GET['busqueda']) ? htmlspecialchars($_GET['busqueda']) : '';

// Calculamos el OFFSET para la consulta SQL.
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// --- 2. Construir la consulta SQL dinámicamente ---
$sql_base = "FROM clientes";
$params = []; // Parámetros para la consulta preparada
$tipos_params = ''; // Tipos de datos para la consulta preparada (ej: 's' para string)

// Si hay un término de búsqueda, añadimos el WHERE
if (!empty($busqueda)) {
    $sql_base .= " WHERE nombre_completo LIKE ? OR documento LIKE ? OR telefono LIKE ?";
    $like_busqueda = "%{$busqueda}%";
    // Añadimos el parámetro 3 veces, uno para cada campo
    array_push($params, $like_busqueda, $like_busqueda, $like_busqueda);
    $tipos_params .= 'sss';
}

// --- 3. Obtener el total de registros para la paginación ---
$sql_total = "SELECT COUNT(*) as total " . $sql_base;
$stmt_total = $conn->prepare($sql_total);
if (!empty($busqueda)) {
    $stmt_total->bind_param($tipos_params, ...$params);
}
$stmt_total->execute();
$resultado_total = $stmt_total->get_result();
$total_registros = $resultado_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);


// --- 4. Obtener los registros de la página actual ---
$sql_final = "SELECT * " . $sql_base . " LIMIT ? OFFSET ?";
array_push($params, $registros_por_pagina, $offset);
$tipos_params .= 'ii'; // 'i' para integer (LIMIT y OFFSET)

$stmt_final = $conn->prepare($sql_final);
$stmt_final->bind_param($tipos_params, ...$params);
$stmt_final->execute();
$result = $stmt_final->get_result();

?>
<link rel="stylesheet" href="class="btnfos btnfos-3" css/tabla_estilo.css">
<div class="members">
    <a href="#" class="btnfos btnfos-3" onclick="cargarContenido('cliente/views/fragmento_crear.php'); return false;" title="Nuevo cliente">
        <img src="nuevo_cliente.png" alt="Nuevo cliente" style="width: 40px; height: 40px;">
    </a>
    <h2 class="titulo_lista">LISTA DE CLIENTES</h2>
    
    <input type="text" id="buscador" placeholder="Buscar por nombre, documento o teléfono..." value="<?= $busqueda ?>">

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
        <tbody id="contenido-clientes">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr class="visible">
                        <td><img width="50" height="30" src="https://img.icons8.com/ios-filled/50/user-male-circle.png" alt="user-male-circle"/></td>
                        <td><input type="text" value="<?= htmlspecialchars($row['nombre_completo']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($row['documento']) ?>" readonly></td>
                        <td><input type="text" value="<?= htmlspecialchars($row['telefono']) ?>" readonly></td>
                        <td class="acciones">
                            <a href="#" class="btnfos btnfos-3" title="Editar cliente" onclick="editarCliente(<?= $row['id_cliente'] ?>); return false;"><img src="editar.png" alt="Editar" style="width: 40px; height: 40px;"></a>
                            <a href="#" class="btnfos btnfos-3" title="Eliminar cliente" onclick="eliminarCliente(<?= $row['id_cliente'] ?>); return false;"><img src="eliminar.png" alt="Eliminar" style="width: 40px; height: 40px;"></a>
                            <a href="#" class="btnfos btnfos-3" title="Nuevo vehículo" onclick="cargarContenido('vehiculo/views/fragmento_crear.php?id_cliente=<?= $row['id_cliente'] ?>&origen=clientes'); return false;"><img src="nuevovehiculo.png" alt="Nuevo vehículo" style="width: 40px; height: 40px;"></a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No se encontraron clientes con esos criterios.</td></tr>
            <?php endif; $conn->close(); ?>
        </tbody>
    </table>

    <div class="paginacion" id="paginacion-clientes">
        <?php if ($total_paginas > 1): ?>
            <?php if ($pagina_actual > 1): ?>
                <a href="#" onclick="cargarPagina(<?= $pagina_actual - 1 ?>); return false;">&laquo; Anterior</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <a href="#" class="<?= ($i == $pagina_actual) ? 'active' : '' ?>" onclick="cargarPagina(<?= $i ?>); return false;"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="#" onclick="cargarPagina(<?= $pagina_actual + 1 ?>); return false;">Siguiente &raquo;</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<script>
(function() {
    // ---- INICIO DEL MÓDULO PRIVADO ----

    // 1. FUNCIÓN DE ACTUALIZACIÓN PARCIAL
    // Esta es la nueva función clave. En lugar de reemplazar todo,
    // busca el contenido nuevo y lo inyecta en las secciones correctas.
    async function actualizarContenidoParcial(ruta) {
        try {
            const respuesta = await fetch(ruta);
            if (!respuesta.ok) throw new Error('Error en la red');
            
            const html = await respuesta.text();

            // Usamos DOMParser para convertir el texto HTML en un documento DOM manejable
            const parser = new DOMParser();
            const docNuevo = parser.parseFromString(html, 'text/html');

            // Encontramos los elementos que queremos actualizar en la página actual
            const contenidoActual = document.getElementById('contenido-clientes');
            const paginacionActual = document.getElementById('paginacion-clientes');

            // Encontramos los elementos correspondientes en el contenido nuevo
            const contenidoNuevo = docNuevo.getElementById('contenido-clientes');
            const paginacionNueva = docNuevo.getElementById('paginacion-clientes');

            // Si los elementos existen, actualizamos su contenido interno
            if (contenidoActual && contenidoNuevo) {
                contenidoActual.innerHTML = contenidoNuevo.innerHTML;
            }
            if (paginacionActual && paginacionNueva) {
                paginacionActual.innerHTML = paginacionNueva.innerHTML;
            }

        } catch (error) {
            console.error("Error al actualizar el contenido:", error);
        }
    }

    // 2. FUNCIÓN DE 'DEBOUNCE' (sin cambios)
    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    // 3. LÓGICA DE BÚSQUEDA Y EVENTOS
    const buscadorInput = document.getElementById('buscador');
    if (!buscadorInput) return;
    
    // La búsqueda ahora llama a la nueva función de actualización parcial
    const realizarBusqueda = () => {
        const termino = buscadorInput.value;
        const ruta = `cliente/views/fragmento_clientes.php?pagina=1&busqueda=${encodeURIComponent(termino)}`;
        actualizarContenidoParcial(ruta); // <-- CAMBIO IMPORTANTE
    };

    buscadorInput.addEventListener('input', debounce(realizarBusqueda, 300));
    
    // Mantenemos el foco en el input después de que se inicializa todo
    buscadorInput.focus();
    // Movemos el cursor al final del texto existente
    buscadorInput.setSelectionRange(buscadorInput.value.length, buscadorInput.value.length);

    // 4. FUNCIONES GLOBALES PARA 'onclick'
    // Se asignan a 'window' para que el HTML pueda encontrarlas.
    
    // La paginación AHORA TAMBIÉN usa la actualización parcial para una experiencia fluida
    window.cargarPagina = function(pagina) {
        const busquedaActual = buscadorInput.value;
        const ruta = `cliente/views/fragmento_clientes.php?pagina=${pagina}&busqueda=${encodeURIComponent(busquedaActual)}`;
        actualizarContenidoParcial(ruta); // <-- CAMBIO IMPORTANTE
        return false;
    }

    window.editarCliente = function(id) {
        cargarContenido(`cliente/views/fragmento_editar.php?id=${id}`);
    }

    window.eliminarCliente = function(id) {
        if (confirm("¿Seguro que deseas eliminar este cliente?")) {
            fetch(`cliente/eliminar.php?id=${id}`)
                .then(res => res.text())
                .then(resp => {
                    if (resp.trim() === "ok") {
                        realizarBusqueda(); 
                    } else if (resp.trim() === "no-se-puede-eliminar") {
                        alert("❌ No se puede eliminar este cliente porque tiene servicios registrados.");
                    } else {
                        alert("Error al eliminar: " + resp);
                    }
                })
                .catch(error => console.error("Error en la solicitud:", error));
        }
    }

})(); // ---- FIN DEL MÓDULO PRIVADO ----
</script>