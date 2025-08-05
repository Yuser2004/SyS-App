<?php
include __DIR__ . '/../models/conexion.php';
// Ahora que $conn existe, esta parte funcionará
$sql_sedes = "SELECT id, nombre FROM sedes ORDER BY nombre";
$resultado_sedes = $conn->query($sql_sedes);
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
            --color-utilidad: #17a2b8; --color-gastos: #ff6347; --fondo-header: #f8f9fa; --borde: #dee2e6;
        }
        .reporte-finanzas { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #fff; }
        .reporte-finanzas h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-fechas { display: flex; justify-content: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px; align-items: center; }
        .form-fechas label { font-weight: bold; }
        .form-fechas input[type="date"] { padding: 8px 12px; border-radius: 5px; border: 1px solid var(--borde); font-size: 14px; }
        .btn-gestionar-gastos { background-color: #6c757d; color: white; text-decoration: none; font-weight: bold; padding: 8px 12px; border-radius: 5px; border: 1px solid var(--borde); font-size: 14px; }
        .resumen-total { display: flex; flex-wrap: wrap; justify-content: center; text-align: center; margin-bottom: 40px; gap: 20px; }
        .resumen-caja { padding: 20px; border-radius: 8px; flex: 1; min-width: 220px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .resumen-caja h3 { margin: 0 0 10px; font-size: 1.1em; }
        .resumen-caja .monto { font-size: 1.8em; font-weight: bold; }
        .resumen-caja .porcentaje { font-size: 0.8em; font-weight: bold; margin-top: 5px; opacity: 0.8; color: inherit; }
        .ingresos { background-color: #e9f5ec; border-left: 5px solid var(--color-ingresos); } .ingresos .monto { color: var(--color-ingresos); }
        .egresos { background-color: #fbebed; border-left: 5px solid var(--color-egresos); } .egresos .monto { color: var(--color-egresos); }
        .gastos { background-color: #fff0f1; border-left: 5px solid var(--color-gastos); } .gastos .monto { color: var(--color-gastos); }
        .utilidad { background-color: #e0fbf6; border-left: 5px solid var(--color-utilidad); } .utilidad .monto { color: var(--color-utilidad); }
        .tabla-detalle { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .tabla-detalle th, .tabla-detalle td { border: 1px solid var(--borde); padding: 12px; text-align: right; }
        .tabla-detalle th { background-color: var(--fondo-header); font-weight: bold; }
        .tabla-detalle tbody tr:nth-child(even) { background-color: #f9f9f9; }
        .tabla-detalle td:first-child { text-align: left; }
        .monto-ingreso { color: var(--color-ingresos); font-weight: 500; }
        .monto-egreso { color: var(--color-egresos); font-weight: 500; }
        .monto-neto { color: var(--color-neto); font-weight: bold; }
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
            if ($resultado_sedes->num_rows > 0) {
                while($sede = $resultado_sedes->fetch_assoc()) {
                    echo '<option value="' . htmlspecialchars($sede['id']) . '">' . htmlspecialchars($sede['nombre']) . '</option>';
                }
            }
            ?>
        </select>
<!--         <a href="#" class="btn-gestionar-gastos" onclick="cargarContenido('finanzas/views/gestion_gastos.php'); return false;">
            + Gestionar Gastos
        </a> -->
    </div>

    <h3>Resumen del Período (<span id="rango-fechas-titulo"></span>)</h3>
    <div class="resumen-total">
        <div class="resumen-caja ingresos">
            <h3>Total Ingresos</h3><p class="monto" id="resumen-ingresos">$0</p><p class="porcentaje">(100%)</p>
        </div>
        <div class="resumen-caja egresos">
            <h3>Egresos por Servicio</h3><p class="monto" id="resumen-egresos">$0</p><p class="porcentaje" id="porcentaje-egresos">(0% del Ingreso)</p>
        </div>
        <div class="resumen-caja utilidad">
            <h3>Utilidad Real</h3><p class="monto" id="resumen-utilidad">$0</p><p class="porcentaje" id="porcentaje-utilidad">(0% del Ingreso)</p>
        </div>
    </div>
    <div class="desglose-pagos">
        <h3>Desglose por Método de Pago</h3>
        <table class="tabla-detalle">
            <thead>
                <tr>
                    <th>Método de Pago</th><th>Ingresos</th><th>Egresos por Servicio</th><th>Balance</th> </tr>
            </thead>
            <tbody id="cuerpo-tabla-pagos"></tbody>
        </table>
    </div>

    <div class="detalle-diario">
        <h3>Desglose Diario</h3>
        <table class="tabla-detalle">
            <thead>
                <tr>
                    <th>Fecha</th> <th>Ingresos</th> <th>Egresos</th> <th>Utilidad Neta del Día</th> </tr>
            </thead>
            <tbody id="cuerpo-tabla-detalle"></tbody>
        </table>
    </div>
</div>

<script>
(function() {
    // 1. OBTENER REFERENCIAS A LOS ELEMENTOS DEL DOM
    const fechaDesdeInput = document.getElementById('fecha_desde');
    const fechaHastaInput = document.getElementById('fecha_hasta');
    const filtroSede = document.getElementById('filtro_sede'); // <-- AÑADIDO

    // Referencias a los elementos que se van a actualizar
    const rangoFechasTitulo = document.getElementById('rango-fechas-titulo');
    const resumenIngresos = document.getElementById('resumen-ingresos');
    const resumenEgresos = document.getElementById('resumen-egresos');
    // const resumenGastos = document.getElementById('resumen-gastos'); // <-- ELIMINADO
    const resumenUtilidad = document.getElementById('resumen-utilidad');
    const porcentajeEgresos = document.getElementById('porcentaje-egresos');
    // const porcentajeGastos = document.getElementById('porcentaje-gastos'); // <-- ELIMINADO
    const porcentajeUtilidad = document.getElementById('porcentaje-utilidad');
    const cuerpoTablaDetalle = document.getElementById('cuerpo-tabla-detalle');
    const cuerpoTablaPagos = document.getElementById('cuerpo-tabla-pagos');

    // Formateador de moneda colombiana
    const formatoMoneda = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });

    // 2. FUNCIÓN PRINCIPAL PARA ACTUALIZAR EL REPORTE
    async function actualizarReporte() {
        // Obtenemos los valores de todos los filtros
        const fechaDesde = fechaDesdeInput.value;
        const fechaHasta = fechaHastaInput.value;
        const sedeId = filtroSede.value; // <-- AÑADIDO: Obtenemos el ID de la sede

        // Construimos la URL para la API, incluyendo el nuevo parámetro `sede_id`
        const url = `finanzas/views/api_reporte.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}&sede_id=${sedeId}`;
        
        // Hacemos la llamada a la API
        const response = await fetch(url);
        const data = await response.json();

        // 3. ACTUALIZAR LAS CAJAS DE RESUMEN
        // Leemos los datos del JSON (ya no se usa 'gastos')
        const ingresos = parseFloat(data.resumen.total_ingresos) || 0;
        const egresos = parseFloat(data.resumen.total_egresos) || 0;
        const utilidad = parseFloat(data.resumen.utilidad_final) || 0;

        // Actualizamos el contenido en el HTML
        resumenIngresos.textContent = formatoMoneda.format(ingresos);
        resumenEgresos.textContent = formatoMoneda.format(egresos);
        // resumenGastos.textContent = formatoMoneda.format(gastos); // <-- ELIMINADO
        resumenUtilidad.textContent = formatoMoneda.format(utilidad);
        
        // Calculamos y actualizamos los porcentajes
        if (ingresos > 0) {
            porcentajeEgresos.textContent = `(${(egresos / ingresos * 100).toFixed(1)}% del Ingreso)`;
            // porcentajeGastos.textContent = `(${(gastos / ingresos * 100).toFixed(1)}% del Ingreso)`; // <-- ELIMINADO
            porcentajeUtilidad.textContent = `(${(utilidad / ingresos * 100).toFixed(1)}% del Ingreso)`;
        } else {
            porcentajeEgresos.textContent = '(0% del Ingreso)';
            // porcentajeGastos.textContent = '(0% del Ingreso)'; // <-- ELIMINADO
            porcentajeUtilidad.textContent = '(0% del Ingreso)';
        }

        // Actualizamos el título con el rango de fechas
        const fechaDesdeFormato = new Date(fechaDesde + 'T00:00:00').toLocaleDateString('es-CO');
        const fechaHastaFormato = new Date(fechaHasta + 'T00:00:00').toLocaleDateString('es-CO');
        rangoFechasTitulo.textContent = `${fechaDesdeFormato} - ${fechaHastaFormato}`;
        
        // 4. ACTUALIZAR LA TABLA DE DESGLOSE POR MÉTODO DE PAGO
        cuerpoTablaPagos.innerHTML = '';
        const desglosePagos = data.desglose_pagos;
        for (const metodo in desglosePagos) {
            const item = desglosePagos[metodo];
            const nombreMetodo = metodo.charAt(0).toUpperCase() + metodo.slice(1);
            cuerpoTablaPagos.innerHTML += `
                <tr>
                    <td><strong>${nombreMetodo}</strong></td>
                    <td class="monto-ingreso">${formatoMoneda.format(item.ingresos)}</td>
                    <td class="monto-egreso">${formatoMoneda.format(item.salidas)}</td>
                    <td class="monto-neto">${formatoMoneda.format(item.balance)}</td>
                </tr>
            `;
        }

        // 5. ACTUALIZAR LA TABLA DE DESGLOSE DIARIO
        cuerpoTablaDetalle.innerHTML = '';
        if (data.detalle && data.detalle.length > 0) {
            data.detalle.forEach(fila => {
                const gananciaDiaria = (parseFloat(fila.ingresos_diarios) || 0) - (parseFloat(fila.egresos_diarios) || 0);
                const fechaFormato = new Date(fila.fecha + 'T00:00:00').toLocaleDateString('es-CO');
                cuerpoTablaDetalle.innerHTML += `
                    <tr>
                        <td>${fechaFormato}</td>
                        <td class="monto-ingreso">${formatoMoneda.format(fila.ingresos_diarios)}</td>
                        <td class="monto-egreso">${formatoMoneda.format(fila.egresos_diarios)}</td>
                        <td class="monto-neto">${formatoMoneda.format(gananciaDiaria)}</td>
                    </tr>
                `;
            });
        } else {
            cuerpoTablaDetalle.innerHTML = '<tr><td colspan="4" style="text-align: center;">No hay transacciones en el período seleccionado.</td></tr>';
        }
    }

    // 6. ASIGNAR EVENTOS A LOS FILTROS
    // La función se ejecutará cada vez que cambie una fecha o la sede
    fechaDesdeInput.addEventListener('change', actualizarReporte);
    fechaHastaInput.addEventListener('change', actualizarReporte);
    filtroSede.addEventListener('change', actualizarReporte); // <-- AÑADIDO

    // 7. CARGA INICIAL DEL REPORTE
    // Se ejecuta una vez cuando la página carga por primera vez
    actualizarReporte();
})();
</script>

</body>
</html>