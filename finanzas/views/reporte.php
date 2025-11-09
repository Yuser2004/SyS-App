<?php
// 1. CONEXIÓN (Debe ir primero)
include __DIR__ . '/../models/conexion.php';

// 2. CONSULTA DE SEDES
$sql_sedes = "SELECT id, nombre FROM sedes ORDER BY nombre";
$resultado_sedes = $conn->query($sql_sedes);

// 3. CONSULTA DE CUENTAS (AÑADIDO)
// Esta variable $lista_cuentas estará disponible para el include del modal
$lista_cuentas = [];
if ($conn) {
    $cuentas_result = $conn->query("
        SELECT nombre_cuenta, clase_css 
        FROM cuentas_bancarias 
        WHERE activa = 1 
        ORDER BY nombre_cuenta ASC
    ");
    if ($cuentas_result) {
        while ($fila = $cuentas_result->fetch_assoc()) {
            $lista_cuentas[] = $fila;
        }
    }
}

// 4. FECHAS POR DEFECTO
$fecha_desde = date('Y-m-01');
$fecha_hasta = date('Y-m-t');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero</title>
    <style>
        :root {
            --color-ingresos: #28a745; --color-egresos: #dc3545; --color-neto: #007bff;
            --color-utilidad: #17a2b8; --color-gastos: #ff6347; /* Color para gastos */
            --fondo-header: #f8f9fa; --borde: #dee2e6;
            --color-excel: #1D6F42; /* Verde Excel */
        }
        .reporte-finanzas { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #fff; }
        .reporte-finanzas h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-fechas { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; align-items: center; }
        .form-fechas label { font-weight: bold; }
        .form-fechas input[type="date"], .form-fechas select { padding: 8px 12px; border-radius: 5px; border: 1px solid var(--borde); font-size: 14px; }
        
        /* Estilo base para botones */
        .btn-accion {
            border: none; padding: 8px 12px;
            border-radius: 5px; font-size: 14px; cursor: pointer; height: 38px;
            color: white; font-weight: 500;
        }
        .btn-registrar-gasto {
            background-color: var(--color-neto);
        }
        .btn-registrar-gasto:hover { background-color: #0056b3; }

        /* ¡NUEVO! Botón de Excel */
        .btn-exportar-excel {
            background-color: var(--color-excel);
        }
        .btn-exportar-excel:hover { background-color: #165934; }


        .resumen-total { display: flex; flex-wrap: wrap; justify-content: center; text-align: center; margin-bottom: 40px; gap: 20px; }
        .resumen-caja { padding: 20px; border-radius: 8px; flex: 1; min-width: 200px; max-width: 240px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .resumen-caja h3 { margin: 0 0 10px; font-size: 1.1em; }
        .resumen-caja .monto { font-size: 1.8em; font-weight: bold; }
        .resumen-caja .porcentaje { font-size: 0.8em; font-weight: bold; margin-top: 5px; opacity: 0.8; color: inherit; }
        .ingresos { background-color: #e9f5ec; border-left: 5px solid var(--color-ingresos); } .ingresos .monto { color: var(--color-ingresos); }
        .egresos { background-color: #fbebed; border-left: 5px solid var(--color-egresos); } .egresos .monto { color: var(--color-egresos); }
        .gastos { background-color: #fff0ed; border-left: 5px solid var(--color-gastos); } .gastos .monto { color: var(--color-gastos); }
        .utilidad { background-color: #e0fbf6; border-left: 5px solid var(--color-utilidad); } .utilidad .monto { color: var(--color-utilidad); }
        .tabla-detalle { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla-detalle th, .tabla-detalle td { border: 1px solid var(--borde); padding: 12px; text-align: right; }
        .tabla-detalle th { background-color: var(--fondo-header); font-weight: bold; }
        .tabla-detalle tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .tabla-detalle td:first-child { text-align: left; }
        .monto-ingreso { color: var(--color-ingresos); font-weight: 500; }
        .monto-egreso { color: var(--color-egresos); font-weight: 500; }
        .monto-gasto { color: var(--color-gastos); font-weight: 500; }
        .monto-neto { color: var(--color-neto); font-weight: bold; }
        
        /* ¡NUEVO! Estilo para la fila de detalle de cuenta */
        .fila-cuenta-detalle td {
            font-size: 0.9em;
            color: #555;
            background-color: #fafafa;
            border-bottom: 1px dotted #ddd;
        }
        .fila-cuenta-detalle td:first-child {
             padding-left: 30px; /* Indentado */
        }

    </style>
</head>
<body>

<div class="reporte-finanzas">
    <h2>Reporte Financiero</h2>
    
    <div class="form-fechas">
        <label for="fecha_desde">Desde:</label>
        <input type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($fecha_desde) ?>">
        
        <label for="fecha_hasta">Hasta:</label>
        <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($fecha_hasta) ?>">

        <label for="filtro_sede">Sede:</label>
        <select id="filtro_sede" name="sede"> 
            <option value="">Todas las Sedes</option>
            <?php
            if ($resultado_sedes && $resultado_sedes->num_rows > 0) {
                while($sede = $resultado_sedes->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($sede['id']) . '">' . htmlspecialchars($sede['nombre']) . '</option>';
                }
            }
            ?>
        </select>
        
        <button type="button" id="btn-abrir-modal-gasto" class="btn-accion btn-registrar-gasto">
            + Registrar Gasto
        </button>
        
        <button type="button" id="btn-exportar" class="btn-accion btn-exportar-excel">
            Exportar a Excel
        </button>

    </div>

    <h3>Resumen del Período (<span id="rango-fechas-titulo"></span>)</h3>
    <div class="resumen-total">
        <div class="resumen-caja ingresos">
            <h3>Total Ingresos</h3><p class="monto" id="resumen-ingresos">$0</p><p class="porcentaje">(100%)</p>
        </div>
        <div class="resumen-caja egresos">
            <h3>Egresos por Servicio</h3><p class="monto" id="resumen-egresos">$0</p><p class="porcentaje" id="porcentaje-egresos">(0%)</p>
        </div>
        <div class="resumen-caja gastos">
            <h3>Gastos de Sede</h3><p class="monto" id="resumen-gastos">$0</p><p class="porcentaje" id="porcentaje-gastos">(0%)</p>
        </div>
        <div class="resumen-caja utilidad">
            <h3>Utilidad Real</h3><p class="monto" id="resumen-utilidad">$0</p><p class="porcentaje" id="porcentaje-utilidad">(0%)</p>
        </div>
    </div>
    
    <div class="desglose-pagos">
        <h3>Desglose por Método de Pago</h3>
        <table class="tabla-detalle">
            <thead>
                <tr>
                    <th>Método de Pago</th><th>Ingresos</th><th>Salidas (Egresos + Gastos)</th><th>Balance</th> </tr>
            </thead>
            <tbody id="cuerpo-tabla-pagos"></tbody>
        </table>
    </div>

    <div class="detalle-diario">
        <h3>Desglose Diario</h3>
        <table class="tabla-detalle">
            <thead>
                <tr>
                    <th>Fecha</th> 
                    <th>Ingresos</th> 
                    <th>Egresos</th> 
                    <th>Gastos</th>
                    <th>Utilidad Neta del Día</th> 
                </tr>
            </thead>
            <tbody id="cuerpo-tabla-detalle"></tbody>
        </table>
    </div>
</div> 

<?php
// Incluimos el modal (que ahora es SOLO HTML y CSS)
include __DIR__ . '/gastos_sede_modal.php';
?>

<script>
// Usamos una función autoejecutable para proteger el scope
(function() {
    
    // --- 1. OBTENER REFERENCIAS A LOS ELEMENTOS DEL DOM (REPORTE) ---
    const fechaDesdeInput = document.getElementById('fecha_desde');
    const fechaHastaInput = document.getElementById('fecha_hasta');
    const filtroSede = document.getElementById('filtro_sede');
    
    const rangoFechasTitulo = document.getElementById('rango-fechas-titulo');
    const resumenIngresos = document.getElementById('resumen-ingresos');
    const resumenEgresos = document.getElementById('resumen-egresos');
    const resumenGastos = document.getElementById('resumen-gastos');
    const resumenUtilidad = document.getElementById('resumen-utilidad');
    const porcentajeEgresos = document.getElementById('porcentaje-egresos');
    const porcentajeGastos = document.getElementById('porcentaje-gastos');
    const porcentajeUtilidad = document.getElementById('porcentaje-utilidad');
    const cuerpoTablaDetalle = document.getElementById('cuerpo-tabla-detalle');
    const cuerpoTablaPagos = document.getElementById('cuerpo-tabla-pagos');
    
    const formatoMoneda = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });

    // --- 2. OBTENER REFERENCIAS A LOS ELEMENTOS DEL DOM (MODAL) ---
    const btnAbrirModalGasto = document.getElementById('btn-abrir-modal-gasto');
    const modalOverlay = document.getElementById('gasto-sede-modal-overlay');
    const inputSedeIdModal = document.getElementById('gasto-id-sede');
    const modalClose = document.getElementById('gasto-sede-modal-close');
    const formGastoSede = document.getElementById('form-gasto-sede');
    const inputAsesorNombre = document.getElementById('buscar-asesor-gasto');
    const inputAsesorId = document.getElementById('id-asesor-gasto');
    const resultadosDiv = document.getElementById('asesor-gasto-resultados');
    const selectTipoGasto = document.getElementById('gasto-tipo'); 

    // --- LÓGICA PARA EL BOTÓN DE EXCEL ---
    const btnExportar = document.getElementById('btn-exportar');
    if (btnExportar) {
        btnExportar.addEventListener('click', function() {
            const fechaDesde = fechaDesdeInput.value;
            const fechaHasta = fechaHastaInput.value;
            const sedeId = filtroSede.value;
            const url = `finanzas/views/exportar_excel.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&sede_id=${sedeId}`;
            window.open(url, '_blank');
        });
    }

    // --- 3. LÓGICA DEL MODAL (CONSOLIDADA) ---
    if (modalClose) {
        modalClose.onclick = () => modalOverlay.style.display = 'none';
    }
    if (modalOverlay) {
        modalOverlay.onclick = (e) => {
            if (e.target === modalOverlay) {
                modalOverlay.style.display = 'none';
            }
        };
    }
    
    const metodoPagoSelectGasto = document.getElementById('gasto-metodo-pago');
    const detallePagoContainerGasto = document.getElementById('gasto-detalle-pago-container');
    const detallePagoSelectGasto = document.getElementById('gasto-detalle-pago');

    if (metodoPagoSelectGasto && detallePagoContainerGasto && detallePagoSelectGasto) {
        
        metodoPagoSelectGasto.addEventListener('change', function() {
            let esTransferencia = this.value === 'transferencia';
            detallePagoContainerGasto.style.display = esTransferencia ? 'block' : 'none';
            detallePagoSelectGasto.required = esTransferencia; 
            if (!esTransferencia) {
                detallePagoSelectGasto.value = ''; 
            }
        });
    } else {
        console.error("Error: No se encontraron los elementos del formulario de pago de gasto.");
    }

    if (inputAsesorNombre) {
        inputAsesorNombre.addEventListener('keyup', function() {
            const term = inputAsesorNombre.value;
            const sedeId = inputSedeIdModal.value;
            
            if (term.length < 2) {
                if(resultadosDiv) {
                   resultadosDiv.innerHTML = '';
                   resultadosDiv.style.display = 'none';
                }
                return;
            }
            if (!sedeId) {
                console.error('No hay ID de sede en el modal para buscar asesor.');
                return;
            }

            fetch(`finanzas/views/buscar_asesor_para_gasto.php?term=${term}&sede_id=${sedeId}`)
                .then(response => response.json())
                .then(data => {
                    if(!resultadosDiv) return;
                    resultadosDiv.innerHTML = '';
                    if (data.length > 0) {
                        resultadosDiv.style.display = 'block';
                        data.forEach(asesor => {
                            const div = document.createElement('div');
                            div.textContent = asesor.nombre;
                            div.dataset.id = asesor.id_asesor;
                            div.addEventListener('click', function() {
                                inputAsesorNombre.value = this.textContent;
                                inputAsesorId.value = this.dataset.id;
                                resultadosDiv.innerHTML = '';
                                resultadosDiv.style.display = 'none';
                            });
                            resultadosDiv.appendChild(div);
                        });
                    } else {
                        resultadosDiv.style.display = 'none';
                    }
                })
                .catch(err => {
                   console.error("Error en fetch buscar_asesor:", err);
                   if(resultadosDiv) resultadosDiv.style.display = 'none';
                });
        });
    }

    if (formGastoSede) {
        formGastoSede.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!inputSedeIdModal.value) {
                alert('Error: No se ha seleccionado una sede.');
                return;
            }
            if (!inputAsesorId.value) {
                alert('Por favor, selecciona un asesor de la lista.');
                return;
            }

            const urlDestino = 'finanzas/views/guardar_gasto_sede.php'; // Siempre el mismo archivo
            const formData = new FormData(formGastoSede);
            
            const btn = document.getElementById('btn-guardar-gasto');
            btn.textContent = 'Guardando...';
            btn.disabled = true;

            fetch(urlDestino, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
                }
                return response.text(); 
            })
            .then(text => {
                // console.log("Respuesta cruda del servidor:", text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error("Respuesta no es JSON. Error PHP: " + text);
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message); // El PHP dirá "Gasto (sede) registrado"
                    formGastoSede.reset();
                    if (detallePagoContainerGasto) {
                         detallePagoContainerGasto.style.display = 'none';
                    }
                    modalOverlay.style.display = 'none';
                    actualizarReporte(); 
                } else {
                    alert('Error al guardar: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error en fetch guardar_gasto:', error);
                alert('Error crítico: ' + error.message);
            })
            .finally(() => {
                btn.textContent = 'Guardar Gasto';
                btn.disabled = false;
            });
        });
    }

    // --- 4. FUNCIÓN PRINCIPAL PARA ACTUALIZAR EL REPORTE ---
    async function actualizarReporte() {
        const fechaDesde = fechaDesdeInput.value;
        const fechaHasta = fechaHastaInput.value;
        const sedeId = filtroSede.value; 
        
        const url = `finanzas/views/api_reporte.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&sede_id=${sedeId}`;
        
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Error en la respuesta de la API: ${response.statusText}`);
            }
            const data = await response.json();

            // Resumen de cajas
            const ingresos = parseFloat(data.resumen.total_ingresos) || 0;
            const egresos = parseFloat(data.resumen.total_egresos) || 0;
            const gastos = parseFloat(data.resumen.total_gastos) || 0;
            const utilidad = parseFloat(data.resumen.utilidad_final) || 0;

            resumenIngresos.textContent = formatoMoneda.format(ingresos);
            resumenEgresos.textContent = formatoMoneda.format(egresos);
            resumenGastos.textContent = formatoMoneda.format(gastos);
            resumenUtilidad.textContent = formatoMoneda.format(utilidad);
            
            if (ingresos > 0) {
                porcentajeEgresos.textContent = `(${(egresos / ingresos * 100).toFixed(1)}% del Ingreso)`;
                porcentajeGastos.textContent = `(${(gastos / ingresos * 100).toFixed(1)}% del Ingreso)`;
                porcentajeUtilidad.textContent = `(${(utilidad / ingresos * 100).toFixed(1)}% del Ingreso)`;
            } else {
                porcentajeEgresos.textContent = '(0%)';
                porcentajeGastos.textContent = '(0%)';
                porcentajeUtilidad.textContent = '(0%)';
            }

            // Rango de fechas
            const fechaDesdeFormato = new Date(fechaDesde + 'T05:00:00').toLocaleDateString('es-CO', {day: '2-digit', month: 'short', year: 'numeric'});
            const fechaHastaFormato = new Date(fechaHasta + 'T05:00:00').toLocaleDateString('es-CO', {day: '2-digit', month: 'short', year: 'numeric'});
            rangoFechasTitulo.textContent = `${fechaDesdeFormato} - ${fechaHastaFormato}`;
            
            // ==========================================================
            // ¡MODIFICADO! Lógica para Desglose de Pagos (con cuentas)
            // ==========================================================
            cuerpoTablaPagos.innerHTML = '';
            for (const metodo in data.desglose_pagos) {
                const item = data.desglose_pagos[metodo];
                const nombreMetodo = metodo.charAt(0).toUpperCase() + metodo.slice(1);
                
                // Fila principal del método
                cuerpoTablaPagos.innerHTML += `
                    <tr>
                        <td><strong>${nombreMetodo}</strong></td>
                        <td class="monto-ingreso">${formatoMoneda.format(item.ingresos)}</td>
                        <td class="monto-egreso">${formatoMoneda.format(item.salidas)}</td> 
                        <td class="monto-neto">${formatoMoneda.format(item.balance)}</td>
                    </tr>
                `;

                // ¡NUEVO! Bucle para las cuentas de transferencia
                if (metodo === 'transferencia' && item.cuentas) {
                    for (const nombreCuenta in item.cuentas) {
                        const cuenta = item.cuentas[nombreCuenta];
                        const ingresosCuenta = cuenta.ingresos || 0;
                        const salidasCuenta = cuenta.salidas || 0;
                        
                        // Solo mostramos si hay movimiento
                        if (ingresosCuenta > 0 || salidasCuenta > 0) {
                            const balanceCuenta = ingresosCuenta - salidasCuenta;

                            // Añadir una fila indentada para la cuenta
                            cuerpoTablaPagos.innerHTML += `
                                <tr class="fila-cuenta-detalle">
                                    <td style="padding-left: 30px;">↪ ${nombreCuenta}</td>
                                    <td class="monto-ingreso">${formatoMoneda.format(ingresosCuenta)}</td>
                                    <td class="monto-egreso">${formatoMoneda.format(salidasCuenta * -1)}</td>
                                    <td class="monto-neto">${formatoMoneda.format(balanceCuenta)}</td>
                                </tr>
                            `;
                        }
                    }
                }
            }
            // ==========================================================
            
            // ==========================================================
            // ¡CORREGIDO! Lógica para Desglose Diario
            // ==========================================================
            cuerpoTablaDetalle.innerHTML = '';
            if (data.detalle && data.detalle.length > 0) {
                data.detalle.forEach(fila => {
                    const ingresosDia = parseFloat(fila.ingresos_diarios) || 0;
                    const egresosDia = parseFloat(fila.egresos_diarios) || 0;
                    const gastosDia = parseFloat(fila.gastos_diarios) || 0;
                    const gananciaDiaria = ingresosDia - egresosDia - gastosDia; 
                    
                    // Asegurarse de que la fecha se maneja correctamente
                    // Añadimos 'T05:00:00' para evitar problemas de zona horaria (UTC vs local)
                    const fechaFormato = new Date(fila.fecha + 'T05:00:00').toLocaleDateString('es-CO', {day: '2-digit', month: 'long'});
                    
                    cuerpoTablaDetalle.innerHTML += `
                        <tr>
                            <td>${fechaFormato}</td>
                            <td class="monto-ingreso">${formatoMoneda.format(ingresosDia)}</td>
                            <td class="monto-egreso">${formatoMoneda.format(egresosDia * -1)}</td>
                            <td class="monto-gasto">${formatoMoneda.format(gastosDia * -1)}</td>
                            <td class="monto-neto">${formatoMoneda.format(gananciaDiaria)}</td>
                        </tr>
                    `;
                });
            } else {
                cuerpoTablaDetalle.innerHTML = '<tr><td colspan="5" style="text-align: center;">No hay transacciones en el período seleccionado.</td></tr>';
            }
            // ==========================================================

        } catch (error) {
            console.error('Error al actualizar el reporte:', error);
            // Mostrar error en ambas tablas
            cuerpoTablaPagos.innerHTML = `<tr><td colspan="4" style="text-align: center; color: red;">Error al cargar datos: ${error.message}</td></tr>`;
            cuerpoTablaDetalle.innerHTML = `<tr><td colspan="5" style="text-align: center; color: red;">Error al cargar datos: ${error.message}</td></tr>`;
        }
    }

    // --- 5. ASIGNAR EVENTOS A LOS FILTROS ---
    fechaDesdeInput.addEventListener('change', actualizarReporte);
    fechaHastaInput.addEventListener('change', actualizarReporte);
    filtroSede.addEventListener('change', actualizarReporte);

    // --- 6. LÓGICA PARA ABRIR EL MODAL ---
    if (btnAbrirModalGasto) {
        btnAbrirModalGasto.addEventListener('click', function() {
            const sedeSeleccionadaId = filtroSede.value; 

            if (!sedeSeleccionadaId || sedeSeleccionadaId === '0' || sedeSeleccionadaId === '') {
                alert('Por favor, selecciona una sede en el filtro antes de registrar un gasto.');
                return;
            }

            inputSedeIdModal.value = sedeSeleccionadaId;
            if (formGastoSede) formGastoSede.reset();
            
            if (selectTipoGasto) {
                selectTipoGasto.value = 'sede'; 
            }
            if (detallePagoContainerGasto) {
                detallePagoContainerGasto.style.display = 'none';
            }

            if (inputAsesorId) inputAsesorId.value = '';
            const inputFecha = document.getElementById('gasto-fecha');
            if (inputFecha) inputFecha.value = new Date().toISOString().split('T')[0];
            if (modalOverlay) modalOverlay.style.display = 'flex';
        });
    }

    // --- 7. CARGA INICIAL DEL REPORTE ---
    actualizarReporte();

})(); // Fin de la función autoejecutable
</script>

</body>
</html>