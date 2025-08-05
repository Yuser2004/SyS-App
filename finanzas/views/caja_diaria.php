    <?php
    include __DIR__ . '/../models/conexion.php';

    // Solo obtenemos la lista de sedes para el filtro
    $sedes_result = $conn->query("SELECT id, nombre FROM sedes");

    // Definimos los valores iniciales para los filtros
    $fecha_seleccionada = date('Y-m-d');
    $id_sede_seleccionada = 1; // Sede por defecto
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Caja Diaria</title>
        <style>
            .neutro-monto { color: #6c757d; } /* Un gris oscuro */
            .caja-diaria { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 900px; margin: auto; }
            .caja-header, .caja-resumen, .caja-cierre { padding: 20px; border: 2px solid #ccc; border-radius: 8px; margin-bottom: 20px; background-color: #f9f9f9b6; }
            .filtros { display: flex; gap: 20px; align-items: center; margin-bottom: 20px; }
    .resumen-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }        .resumen-columna h4 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
            .resumen-linea { display: flex; justify-content: space-between; padding: 5px 0; }
            .total { font-weight: bold; border-top: 1px solid #ccc; padding-top: 10px; margin-top: 10px; }
            .ingreso-monto { color: #28a745; }
            .salida-monto { color: #dc3545; }
            .balance-positivo { color: #007bff; }
            .balance-negativo { color: #dc3545; }
            .cierre-form input, .cierre-form textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
            .cierre-form button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
            .caja-cerrada-msg { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; }
            /* --- Estilos para el Contenedor de Filtros --- */
            .filtros {
                display: flex; /* Usamos Flexbox para alinear los elementos */
                flex-wrap: wrap; /* Permite que los filtros pasen a la siguiente línea en pantallas pequeñas */
                align-items: center; /* Centra verticalmente los elementos */
                gap: 15px 25px; /* Espacio vertical y horizontal entre los filtros */
                padding: 20px;
                background-color: #f8f9fa; /* Un fondo gris muy claro */
                border: 1px solid #dee2e6; /* Un borde sutil */
                border-radius: 8px; /* Bordes redondeados */
                margin-bottom: 25px; /* Espacio para separarlo del contenido de abajo */
                box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* Sombra suave para darle profundidad */
            }

            /* --- Estilos para las Etiquetas (Fecha, Sede) --- */
            .filtros label {
                font-weight: 600; /* Texto en semi-negrita */
                color: #495057; /* Un color de texto gris oscuro */
                margin-right: 5px; /* Pequeño espacio entre la etiqueta y el input */
            }

            /* --- Estilos para los Inputs (fecha y select) --- */
            .filtros input[type="date"],
            .filtros select {
                padding: 8px 12px;
                font-size: 16px;
                font-family: inherit; /* Usa la misma fuente que el resto de la página */
                color: #212529;
                background-color: #fff;
                border: 1px solid #ced4da;
                border-radius: 6px; /* Bordes ligeramente más redondeados */
                transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out; /* Transición suave */
                cursor: pointer;
            }

            /* --- Efecto al pasar el mouse por encima --- */
            .filtros input[type="date"]:hover,
            .filtros select:hover {
                border-color: #a7b4c0;
            }

            /* --- Efecto al hacer clic (focus) --- */
            .filtros input[type="date"]:focus,
            .filtros select:focus {
                border-color: #86b7fe; /* Un borde azul claro */
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); /* Un resplandor azul tipo Bootstrap */
                outline: none; /* Quitamos el borde por defecto del navegador */
            }
        </style>
    </head>
    <body>

    <div class="caja-diaria">
    <h2 style="text-align:center; background-color: #007bff; color: white; padding: 10px;">Cierre Diario de Operaciones</h2>    
        <div class="filtros">
            <label for="fecha"><b>Fecha:</b></label>    
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fecha_seleccionada) ?>">
            
            <label for="id_sede"><b>Sede:</b></label>
            <select id="id_sede" name="id_sede">
                <?php while($sede = $sedes_result->fetch_assoc()): ?>
                    <option value="<?= $sede['id'] ?>" <?= ($id_sede_seleccionada == $sede['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sede['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalDevolucionPrestamo">
                + Registrar Devolución
            </button>
        </div>

        <div class="caja-resumen">
            <h3 style="text-align:center;">Movimientos del Día: <span id="titulo-fecha"></span></h3>
            <div class="resumen-linea total">
                <span>SALDO DE APERTURA (Día Anterior)</span>
                <span id="saldo-apertura">$0</span>
            </div>
            <hr>
            <div class="resumen-grid">
                <div class="resumen-columna">
                    <h4>Ingresos</h4>
                    <div class="resumen-linea"><span>Efectivo:</span> <span class="ingreso-monto" id="ingresos-efectivo">$0</span></div>
                    <div class="resumen-linea"><span>Transferencia:</span> <span class="ingreso-monto" id="ingresos-transferencia">$0</span></div>
                    <div class="resumen-linea"><span>Tarjeta:</span> <span class="ingreso-monto" id="ingresos-tarjeta">$0</span></div>
                    <div class="resumen-linea total"><span>Total Ingresos:</span> <span class="ingreso-monto" id="total-ingresos">$0</span></div>
                </div>
                <div class="resumen-columna">
                    <h4>Egresos (Servicios)</h4>
                    <div class="resumen-linea"><span>Efectivo:</span> <span class="salida-monto" id="egresos-efectivo">-$0</span></div>
                    <div class="resumen-linea"><span>Transferencia:</span> <span class="salida-monto" id="egresos-transferencia">-$0</span></div>
                    <div class="resumen-linea"><span>Tarjeta:</span> <span class="salida-monto" id="egresos-tarjeta">-$0</span></div>
                    <div class="resumen-linea total"><span>Total Egresos:</span> <span class="salida-monto" id="total-egresos">-$0</span></div>
                </div>
            </div>
            <hr>
            <hr>
<h4 style="text-align:center; margin-top:20px;">Movimientos no Operativos</h4>
<div class="resumen-linea">
    <span>(+) Préstamos Recibidos de otras Sedes:</span>
    <span class="ingreso-monto" id="total-prestamos-recibidos">$0</span>
</div>
<div class="resumen-linea">
    <span>(+) Devoluciones de Préstamos Recibidas:</span>
    <span class="ingreso-monto" id="total-devoluciones">$0</span>
</div>
<div class="resumen-linea">
    <span>(-) Préstamos Enviados a otras Sedes:</span>
    <span class="salida-monto" id="prestamos-enviados">-$0</span>
</div>
<!-- CÓDIGO NUEVO -->
<div class="resumen-linea">
    <span>(-) Devoluciones Enviadas a otras sedes:</span>
    <span class="salida-monto" id="total-devoluciones-enviadas">-$0</span>
</div>
            <div class="resumen-linea total">
                <span>BALANCE DEL DÍA (Ingresos - Salidas)</span>
                <span id="balance-dia">$0</span> 
            </div>
            <div class="resumen-linea total" style="font-size: 1.2em;">
                <span>SALDO FINAL ESPERADO EN CAJA</span>
                <span id="saldo-final">$0</span><!-- AQUI, DEBES DE HACER QUE SE REFLEJE LAS DEVOLUCIONES DE LAS OTRAS SEDES YA QUE ESTARAN EN LA CAJA. -->
            </div>
        </div>
        
        <div class="caja-cierre" id="seccion-cierre">
            </div>
    </div>
<script>
(function() { // "Burbuja" para evitar conflictos
    const fechaInput = document.getElementById('fecha');
    const sedeInput = document.getElementById('id_sede');
    const seccionCierre = document.getElementById('seccion-cierre');

    if (!fechaInput || !sedeInput || !seccionCierre) return;

    const formatoMoneda = (valor) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(valor);

    async function actualizarCajaDiaria() {
        const fecha = fechaInput.value;
        const idSede = sedeInput.value;

        try {
            const response = await fetch(`finanzas/views/api_caja_diaria.php?fecha=${fecha}&id_sede=${idSede}`);
            if (!response.ok) throw new Error('No se pudo conectar con la API');
            const data = await response.json();

            // Hacemos el código más seguro, proveyendo valores por defecto si algo no llega desde la API
            const ingresos = data.ingresos || { total: 0, desglose: { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 } };
            const egresos = data.egresos || { total: 0, desglose: { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 } };
            const devolucionesRecibidas = data.devoluciones_recibidas ||{ total: 0, desglose: { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 } }; 
            const devolucionesEnviadas = data.devoluciones_enviadas ||{ total: 0, desglose: { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 } }; 
            const prestamosEnviados = data.prestamos_enviados || { total: 0, desglose: { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 } };

            // Actualizar Título y Saldo Apertura
            document.getElementById('titulo-fecha').textContent = new Date(fecha + 'T00:00:00').toLocaleDateString('es-CO');
            document.getElementById('saldo-apertura').textContent = formatoMoneda(data.saldo_apertura);

            // Actualizar Ingresos
            document.getElementById('total-ingresos').textContent = formatoMoneda(ingresos.total);
            document.getElementById('ingresos-efectivo').textContent = formatoMoneda(ingresos.desglose.efectivo);
            document.getElementById('ingresos-transferencia').textContent = formatoMoneda(ingresos.desglose.transferencia);
            document.getElementById('ingresos-tarjeta').textContent = formatoMoneda(ingresos.desglose.tarjeta);

            // Actualizar Egresos
            document.getElementById('total-egresos').textContent = '-' + formatoMoneda(egresos.total);
            document.getElementById('egresos-efectivo').textContent = '-' + formatoMoneda(egresos.desglose.efectivo);
            document.getElementById('egresos-transferencia').textContent = '-' + formatoMoneda(egresos.desglose.transferencia);
            document.getElementById('egresos-tarjeta').textContent = '-' + formatoMoneda(egresos.desglose.tarjeta);

            // Actualizar Movimientos no Operativos
            document.getElementById('total-devoluciones').textContent = formatoMoneda(devolucionesRecibidas.total);
            document.getElementById('prestamos-enviados').textContent = '-' + formatoMoneda(prestamosEnviados.total);
            document.getElementById('total-devoluciones-enviadas').textContent = '-' + formatoMoneda(devolucionesEnviadas.total);
            document.getElementById('prestamos-enviados').textContent = '-' + formatoMoneda(prestamosEnviados.total);

            // Actualizar Balances Finales
            const balanceDiaEl = document.getElementById('balance-dia');
            balanceDiaEl.textContent = formatoMoneda(data.balance_dia);
            balanceDiaEl.className = data.balance_dia >= 0 ? 'balance-positivo' : 'balance-negativo';

            const saldoFinalEl = document.getElementById('saldo-final');
            saldoFinalEl.textContent = formatoMoneda(data.saldo_final_esperado);
            saldoFinalEl.className = data.saldo_final_esperado >= 0 ? 'balance-positivo' : 'balance-negativo';

            // --- ACTUALIZAR SECCIÓN DE CIERRE (CON LÓGICA FINAL) ---
            if (data.cierre_info) {
                seccionCierre.innerHTML = `
                    <h3 style="text-align:center;">Caja Cerrada</h3>
                    <div class="resumen-linea">
                        <span>Conteo Físico Registrado:</span>
                        <strong>${formatoMoneda(data.cierre_info.conteo_efectivo_cierre)}</strong>
                    </div>
                    <div class="resumen-linea">
                        <span>Diferencia de Caja:</span>
                        <strong class="${data.cierre_info.diferencia >= 0 ? 'ingreso-monto' : 'salida-monto'}">${formatoMoneda(data.cierre_info.diferencia)}</strong>
                    </div>
                    <div style="margin-top: 15px;">
                        <strong>Notas del Cierre:</strong>
                        <div style="background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 5px; white-space: pre-wrap;">${data.cierre_info.notas || '<em>Sin notas.</em>'}</div>
                    </div>
                `;
            } else if (data.se_puede_cerrar && data.hubo_movimientos_hoy) {
                seccionCierre.innerHTML = `
                    <h3 style="text-align:center;">Realizar Cierre de Caja</h3>
                    <p style="text-align:center; font-size: 14px;">Al cerrar la caja, se guardará un registro permanente.</p>
                    <form class="cierre-form" method="POST" action="finanzas/views/guardar_cierre.php">
                        <input type="hidden" name="id_sede" value="${idSede}">
                        <input type="hidden" name="fecha_cierre" value="${fecha}">
                        <input type="hidden" name="saldo_apertura" value="${data.saldo_apertura}">
                        <input type="hidden" name="total_ingresos" value="${ingresos.total}">
                        <input type="hidden" name="total_egresos" value="${egresos.total}">
                        <input type="hidden" name="balance_dia" value="${data.balance_dia}">
                        <input type="hidden" name="saldo_final" value="${data.saldo_final_esperado}">
                        <input type="hidden" name="ingresos_efectivo" value="${ingresos.desglose.efectivo}">
                        <input type="hidden" name="egresos_efectivo" value="${egresos.desglose.efectivo}">
                        <input type="hidden" name="prestamos_enviados_efectivo" value="${prestamosEnviados.desglose.efectivo}">
                        
                        <input type="hidden" name="devoluciones_recibidas_efectivo" value="${devolucionesRecibidas.desglose.efectivo}">
                        
                        <label for="conteo_final_total"><b>Valor Total Real en Caja al Cierre (Efectivo + Cuentas):</b></label>
                        <input type="number" step="0.01" name="conteo_final_total" id="conteo_final_total" required>
                        <label for="notas"><b>Notas Adicionales:</b></label>
                        <textarea name="notas" id="notas" rows="3"></textarea>
                        <button type="submit">CERRAR CAJA DEL DÍA</button>
                    </form>
                `;
            } else if (data.se_puede_cerrar && !data.hubo_movimientos_hoy) {
                seccionCierre.innerHTML = `<div class="caja-cerrada-msg" style="background-color: #e2e3e5; color: #383d41; border-color: #d6d8db;">No hay movimientos para cerrar en esta fecha.</div>`;
            } else {
                seccionCierre.innerHTML = `<div class="caja-cerrada-msg" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">${data.mensaje_cierre_bloqueado}</div>`;
            }
        } catch (error) {
            console.error("Error al actualizar la caja diaria:", error);
            seccionCierre.innerHTML = `<div class="caja-cerrada-msg" style="background-color: #f8d7da; color: #721c24;">Error de conexión. No se pudieron cargar los datos.</div>`;
        }
    }
    
    // MANEJADOR DE EVENTOS PARA EL FORMULARIO DE CIERRE
    seccionCierre.addEventListener('submit', async function(event) {
        if (event.target.matches('.cierre-form')) {
            event.preventDefault(); 
            
            const form = event.target;
            const submitButton = form.querySelector('button[type="submit"]');
            
            submitButton.disabled = true;
            submitButton.textContent = 'Guardando...';

            try {
                const formData = new FormData(form);
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData
                });
                const resultText = await response.text();
                if (resultText.trim() === 'ok') {
                    alert('¡Caja cerrada con éxito!');
                    actualizarCajaDiaria(); 
                } else {
                    alert('Error al guardar el cierre:\n' + resultText);
                    submitButton.disabled = false;
                    submitButton.textContent = 'CERRAR CAJA DEL DÍA';
                }
            } catch (error) {
                console.error('Error de red al intentar cerrar la caja:', error);
                alert('Hubo un error de conexión. Inténtalo de nuevo.');
                submitButton.disabled = false;
                submitButton.textContent = 'CERRAR CAJA DEL DÍA';
            }
        }
    });

    // --- INICIO: MANEJADOR PARA EL MODAL DE DEVOLUCIÓN ---
    // Este es el nuevo bloque que se encarga de la magia
    const modalDevolucionEl = document.getElementById('modalDevolucionPrestamo');
    if (modalDevolucionEl) {
        const formDevolucion = modalDevolucionEl.querySelector('form');

        formDevolucion.addEventListener('submit', async function(event) {
            event.preventDefault(); // Evitamos que la página se recargue

            const submitButton = formDevolucion.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Guardando...';

            try {
                const formData = new FormData(formDevolucion);
                const response = await fetch(formDevolucion.action, {
                    method: 'POST',
                    body: formData
                });

                const resultText = await response.text();

                if (response.ok && resultText.trim().toLowerCase().includes('ok')) { // Hacemos la comprobación más flexible
                    alert('¡Devolución registrada con éxito!');
                    
                    const modal = bootstrap.Modal.getInstance(modalDevolucionEl);
                    modal.hide();

                    // ¡ACTUALIZAMOS LA CAJA PARA VER LOS CAMBIOS!
                    actualizarCajaDiaria(); 
                } else {
                    alert('Error al guardar la devolución:\n' + resultText);
                }
            } catch (error) {
                console.error('Error de red al guardar la devolución:', error);
                alert('Hubo un error de conexión. Inténtalo de nuevo.');
            } finally {
                // Reactivamos el botón sin importar el resultado
                submitButton.disabled = false;
                submitButton.textContent = 'Guardar Devolución';
            }
        });
    }
    // --- FIN: MANEJADOR PARA EL MODAL DE DEVOLUCIÓN ---


    // EVENT LISTENERS Y CARGA INICIAL
    fechaInput.addEventListener('change', actualizarCajaDiaria);
    sedeInput.addEventListener('change', actualizarCajaDiaria);

    actualizarCajaDiaria(); // Carga inicial
})();
</script>
    <?php include 'devolucion_modal.php'; ?>
    </body>
    </html>