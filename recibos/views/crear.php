    <?php
    include __DIR__ . '/../models/conexion.php';

    // Recibir el origen. Si no se especifica, el valor por defecto es 'recibos'.
    $origen = $_GET['origen'] ?? 'recibos';

    // Recibir los IDs desde la URL, si existen
    $id_cliente = intval($_GET['id_cliente'] ?? 0);
    $id_vehiculo = intval($_GET['id_vehiculo'] ?? 0);
    $id_asesor = 0; // O intval($_GET['id_asesor'] ?? 0); si alguna vez lo pasas por URL

    $cliente_nombre = '';   
    $vehiculo_placa = '';

    // Definir la ruta de regreso según el origen
    $ruta_volver = ($origen === 'vehiculos') 
        ? 'vehiculo/views/lista_vehiculos.php' 
        : 'recibos/views/lista.php';

    // Si se recibió un ID de cliente, buscar su nombre
    if ($id_cliente > 0) {
        $res_cliente = $conn->query("SELECT nombre_completo FROM clientes WHERE id_cliente = $id_cliente");
        if ($res_cliente && $res_cliente->num_rows > 0) {
            $cliente = $res_cliente->fetch_assoc();
            $cliente_nombre = $cliente['nombre_completo'] ?? '';
        }
    }

    // Si se recibió un ID de vehículo, buscar su placa
    if ($id_vehiculo > 0) {
        $res_vehiculo = $conn->query("SELECT placa FROM vehiculo WHERE id_vehiculo = $id_vehiculo");
        if ($res_vehiculo && $res_vehiculo->num_rows > 0) {
            $vehiculo = $res_vehiculo->fetch_assoc();
            $vehiculo_placa = $vehiculo['placa'] ?? '';
        }
    }
    ?>

    <link rel="stylesheet" href="recibos/public/estilos_form.css">

    <div class="login-form">
        <h1>Registrar Recibo</h1>
        <form id="form-crear-recibo">

            <!-- Cliente -->
            <div class="form-input-material">
                <label for="buscador_cliente">Cliente</label>
                <input type="text" id="buscador_cliente" autocomplete="off"
                    value="<?= htmlspecialchars($cliente_nombre) ?>">
                <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $id_cliente ?>" required>
                <div id="resultados_cliente" class="resultado-autocompletar"></div>
            </div>

            <!-- Vehículo -->
            <div class="form-input-material">
                <label for="buscador_vehiculo">Vehículo</label>
                <input type="text" id="buscador_vehiculo" autocomplete="off"
                    value="<?= htmlspecialchars($vehiculo_placa) ?>">
                <input type="hidden" id="id_vehiculo" name="id_vehiculo" value="<?= $id_vehiculo ?>" required>
                <div id="resultados_vehiculo" class="resultado-autocompletar"></div>
            </div>  

            <!-- Asesor -->
            <div class="form-input-material">
                <label for="buscador_asesor">Asesor</label>
                <input type="text" id="buscador_asesor" autocomplete="off" >
                <input type="hidden" id="id_asesor" name="id_asesor" value="<?= $id_asesor ?>">
                <div id="resultados_asesor" class="resultado-autocompletar"></div>
            </div>

            <!-- Concepto -->
            <div class="form-input-material">
                <label for="concepto_servicio">Concepto del Servicio</label>
                <input type="text" id="concepto_servicio" name="concepto_servicio" required>
            </div>

            <!-- Valor -->
            <div class="form-input-material">
                <label for="valor_visible">Valor del Servicio</label>
                
                <input type="text" id="valor_visible" inputmode="numeric">
                
                <input type="hidden" id="valor_servicio" name="valor_servicio" required>
            </div>

            <!-- Estado -->
            <div class="form-input-material">
                            <label for="estado">Estado</label>

                <select name="estado" id="estado" required>
                    <option value="" disabled selected hidden>Selecciona un estado</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="completado">Completado</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>

            <!-- Método de pago -->
            <div class="form-input-material">
                <label for="metodo_pago">Método de pago</label>
                <select name="metodo_pago" id="metodo_pago" required>
                    <option value="" disabled selected hidden>Selecciona un método</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
            </div>

            <div class="form-input-material" id="detalle_pago_container" style="display: none;">
                <label for="detalle_pago">Cuenta de Destino</label>
                <select name="detalle_pago" id="detalle_pago">
                    <option value="" disabled selected hidden>Selecciona una cuenta</option>
                    <option class="opt-daviplata" value="Daviplata">Daviplata</option>
                    <option class="opt-davivienda" value="Ahorro a la mano">Davivienda</option>
                    <option class="opt-nequi" value="Nequi">Nequi</option>
                    <option class="opt-bancolombia" value="Bancolombia">Bancolombia</option>
                </select>
            </div>
            <!-- Descripción -->
            <div class="form-input-material">
                <textarea name="descripcion_servicio" id="descripcion_servicio" rows="4" placeholder=" "></textarea>
                <label for="descripcion_servicio">Descripción del Servicio</label>
            </div>

            <button type="submit" id="btnGuardarRecibo" class="btn">Guardar Recibo</button>
        </form>

        <button class="btn" onclick="cargarContenido('<?= $ruta_volver ?>')">← Volver</button>
<script>
    // Esta función envolverá toda nuestra lógica para asegurar que el DOM esté listo.
    function inicializarFormularioRecibo() {

        // --- DEFINICIÓN DE FUNCIONES AUXILIARES ---
        function debounce(func, delay = 300) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), delay);
            };
        }

        function setupAutocomplete(inputId, resultsId, hiddenId, url, formatResult, onSelect) {
            const input = document.getElementById(inputId);
            const resultsContainer = document.getElementById(resultsId);
            if (!input || !resultsContainer) return;

            const debouncedFetch = debounce(async (query) => {
                if (query.length < 2) {
                    resultsContainer.innerHTML = "";
                    return;
                }
                try {
                    const response = await fetch(`${url}?q=${encodeURIComponent(query)}`);
                    const data = await response.json();
                    resultsContainer.innerHTML = "";
                    if (data.length === 0) {
                        resultsContainer.innerHTML = "<div class='item'>No se encontraron resultados</div>";
                        return;
                    }
                    data.forEach(item => {
                        const div = document.createElement("div");
                        div.className = "item";
                        div.textContent = formatResult(item);
                        div.onclick = () => onSelect(item, input, document.getElementById(hiddenId), resultsContainer);
                        resultsContainer.appendChild(div);
                    });
                } catch (err) {
                    console.error(`Error en autocompletar para ${inputId}:`, err);
                    resultsContainer.innerHTML = "<div class='item' style='color:red;'>Error al buscar</div>";
                }
            });
            input.addEventListener("input", function() { debouncedFetch(this.value.trim()); });
        }

        // --- INICIALIZACIÓN DE COMPONENTES ---
        setupAutocomplete('buscador_cliente', 'resultados_cliente', 'id_cliente', 'recibos/buscar_cliente.php', 
            (item) => `${item.nombre_completo} (${item.documento})`,
            (item, input, hidden, results) => {
                input.value = item.nombre_completo;
                hidden.value = item.id_cliente;
                results.innerHTML = "";
            }
        );

        setupAutocomplete('buscador_vehiculo', 'resultados_vehiculo', 'id_vehiculo', 'recibos/buscar_vehiculo.php', 
            (item) => item.placa,
            (item, input, hidden, results) => {
                input.value = item.placa;
                hidden.value = item.id_vehiculo;
                results.innerHTML = "";
            }
        );

        setupAutocomplete('buscador_asesor', 'resultados_asesor', 'id_asesor', 'recibos/buscar_asesor.php', 
            (item) => item.nombre,
            (item, input, hidden, results) => {
                input.value = item.nombre;
                hidden.value = item.id_asesor;
                results.innerHTML = "";
            }
        );

        const formCrearRecibo = document.getElementById("form-crear-recibo");
        const btnGuardar = document.getElementById("btnGuardarRecibo");

        if (formCrearRecibo && btnGuardar) {
            formCrearRecibo.addEventListener("submit", async function(e) {
                e.preventDefault();
                btnGuardar.disabled = true;
                btnGuardar.textContent = 'Guardando...';

                const id_cliente = document.getElementById("id_cliente").value.trim();
                const id_vehiculo = document.getElementById("id_vehiculo").value.trim();

                if (!id_cliente || !id_vehiculo) {
                    alert("⚠ Debes seleccionar un cliente y un vehículo.");
                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar Recibo';
                    return;
                }
                try {
                    const validacion = await fetch(`recibos/verificar_relacion_vehiculo.php?id_cliente=${id_cliente}&id_vehiculo=${id_vehiculo}`);
                    const estado = (await validacion.text()).trim();
                    
                    if (estado === "invalido") {
                        if (!confirm("⚠ El vehículo seleccionado no pertenece al cliente. ¿Deseas continuar de todos modos?")) {
                            btnGuardar.disabled = false;
                            btnGuardar.textContent = 'Guardar Recibo';
                            return;
                        }
                    }
                    
                    const formData = new FormData(formCrearRecibo);
                    const resp = await fetch("recibos/guardar.php", {
                        method: "POST",
                        body: formData
                    });
                    const texto = await resp.text();

                    if (texto.trim() === "ok") {
                        cargarContenido('recibos/views/lista.php');
                    } else {
                        alert("Error al guardar: " + texto);
                        btnGuardar.disabled = false;
                        btnGuardar.textContent = 'Guardar Recibo';
                    }
                } catch (err) {
                    alert("Error inesperado en la solicitud.");
                    btnGuardar.disabled = false;
                    btnGuardar.textContent = 'Guardar Recibo';
                    console.error("🔥 Error en el proceso:", err);
                }
            });
        }

        var inputValorVisible = document.getElementById('valor_visible');
        var inputValorReal = document.getElementById('valor_servicio');
        if(inputValorVisible && inputValorReal) {
            inputValorVisible.addEventListener('input', function(e) {
                let val = e.target.value.replace(/[^\d]/g, '');
                inputValorReal.value = val;
                e.target.value = val ? new Intl.NumberFormat('es-CO').format(val) : '';
            });
        }
        
        const metodoPagoSelect = document.getElementById('metodo_pago');
        const detallePagoContainer = document.getElementById('detalle_pago_container');
        if (metodoPagoSelect && detallePagoContainer) {
            metodoPagoSelect.addEventListener('change', function() {
                let esTransferencia = this.value === 'transferencia';
                detallePagoContainer.style.display = esTransferencia ? 'block' : 'none';
                document.getElementById('detalle_pago').required = esTransferencia;
                if (!esTransferencia) {
                    document.getElementById('detalle_pago').value = '';
                }
            });
        }
    }

    // --- PUNTO DE ENTRADA: LLAMAMOS A LA FUNCIÓN PRINCIPAL ---
    inicializarFormularioRecibo();
</script>
<style>
    /* Daviplata (Rojo suave) */
    .opt-daviplata { background-color: #ffebee; color: #e30a0aff; }
    select#detalle_pago.opt-daviplata { background-color: #ffebee; color: #c62828; font-weight: bold; }

    /* Davivienda (Rojo/Naranja suave) */
    .opt-davivienda { background-color: #fff3e0; color: #ef6c00; }
    select#detalle_pago.opt-davivienda { background-color: #fff3e0; color: #ef6c00; font-weight: bold; }

    /* Nequi (Morado suave) */
    .opt-nequi { background-color: #f3e5f5; color: #2a11cdff; }
    select#detalle_pago.opt-nequi { background-color: #f3e5f5; color: #6a1b9a; font-weight: bold; }

    /* Bancolombia (Amarillo/Negro suave) */
    .opt-bancolombia { background-color: #fffde7; color: #f57f17; }
    select#detalle_pago.opt-bancolombia { background-color: #fffde7; color: #f57f17; font-weight: bold; }
</style>
    </div>