    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>SyS Aplicación</title>
        
        <!-- Estilos -->
        <link rel="stylesheet" href="css/tabla_estilo.css">
        <link rel="stylesheet" href="css/header.css">
        <link rel="stylesheet" href="css/botones.css">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Scripts -->
        <script src="cliente/public/js/buscador.js" defer></script>

        <style>
        /* Layout principal */
        .main-layout {
            display: flex;
            flex-direction: row;
            height: calc(100vh - 300px); /* deja si funciona para ti */
        }
        .menu-lateral {
            width: 250px;
            min-width: 220px;
            background-color: #080b40;
            border-right: 1px solid #ccc;
            padding: 10px;
            overflow-y: none;
        }


        /* Responsivo */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
                height: auto;
            }

            .menu-lateral {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ccc;
            }
        }
        </style>
    </head>

    <body>
        <!-- HEADER -->
        <header class="hero-header" id="mainHeader">
            <div class="overlay">
                <h2 class="hero-title">Seguros y Servicios</h2>
                <h3 class="hero-subtitle">Rapidéz y Responsabilidad</h3>
                <br>
            </div>
        </header>


        <!-- CONTENIDO PRINCIPAL: MENÚ + CONTENIDO -->
        <div class="main-layout">
            <!-- MENÚ LATERAL -->
            <nav class="menu-lateral">
                <ul>
                    <li>
                        <span onclick="cargarContenido('recibos/views/lista.php')">
                            <img src="recibo.png" alt="Recibos" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Recibos
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('cliente/views/fragmento_clientes.php')">
                            <img src="cliente.png" alt="Clientes" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Clientes
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">
                            <img src="coche.png" alt="Vehículos" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Vehículos
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('sedes/views/lista_sedes.php')">
                            <img src="hogar.png" alt="Sedes" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Sedes
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('asesor/views/lista_asesor.php')">👤 Asesores</span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('finanzas/views/reporte.php')">Finanzas</span>
                    </li>
                </ul>
            </nav>

            <!-- CONTENIDO DINÁMICO -->
            <main class="cuerpo" id="contenido">
                <!-- Aquí se cargará dinámicamente el contenido -->
            </main>
        </div>

        <!-- SCRIPT PARA CARGA DINÁMICA -->
        <script>
            function cargarContenido(ruta) {
                fetch(ruta)
                    .then(res => res.text())
                    .then(html => {
                        const contenedor = document.getElementById('contenido');
                        contenedor.innerHTML = html;
                        inicializarReporteFinanciero();
                    })
                    .catch(error => console.error('Error al cargar contenido:', error));
            }
        </script>
        <script>
            function inicializarReporteFinanciero() {
                // Busca los elementos del reporte en la página
                const fechaDesdeInput = document.getElementById('fecha_desde');
                const fechaHastaInput = document.getElementById('fecha_hasta');

                // Si los elementos no existen (porque no estamos en la vista de reporte), no hace nada.
                if (!fechaDesdeInput || !fechaHastaInput) {
                    return;
                }

                const formatoMoneda = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 });

                async function actualizarReporte() {
                    // Referencias a los elementos que se van a actualizar
                    const rangoFechasTitulo = document.getElementById('rango-fechas-titulo');
                    const resumenIngresos = document.getElementById('resumen-ingresos');
                    const resumenEgresos = document.getElementById('resumen-egresos');
                    const resumenGastos = document.getElementById('resumen-gastos');
                    const resumenUtilidad = document.getElementById('resumen-utilidad');
                    const porcentajeEgresos = document.getElementById('porcentaje-egresos');
                    const porcentajeGastos = document.getElementById('porcentaje-gastos');
                    const porcentajeUtilidad = document.getElementById('porcentaje-utilidad');
                    const cuerpoTablaDetalle = document.getElementById('cuerpo-tabla-detalle');

                    const fechaDesde = fechaDesdeInput.value;
                    const fechaHasta = fechaHastaInput.value;

                    const response = await fetch(`finanzas/views/api_reporte.php?fecha_desde=${fechaDesde}&fecha_hasta=${fechaHasta}`);
                    const data = await response.json();

                    // Lógica para actualizar el HTML (sin cambios)
                    const ingresos = data.resumen.total_ingresos;
                    resumenIngresos.textContent = formatoMoneda.format(ingresos);
                    resumenEgresos.textContent = formatoMoneda.format(data.resumen.total_egresos);
                    resumenGastos.textContent = formatoMoneda.format(data.resumen.total_gastos);
                    resumenUtilidad.textContent = formatoMoneda.format(data.resumen.utilidad_final);
                    
                    if (ingresos > 0) {
                        porcentajeEgresos.textContent = `(${(data.resumen.total_egresos / ingresos * 100).toFixed(1)}% del Ingreso)`;
                        porcentajeGastos.textContent = `(${(data.resumen.total_gastos / ingresos * 100).toFixed(1)}% del Ingreso)`;
                        porcentajeUtilidad.textContent = `(${(data.resumen.utilidad_final / ingresos * 100).toFixed(1)}% del Ingreso)`;
                    } else {
                        porcentajeEgresos.textContent = '(0% del Ingreso)';
                        porcentajeGastos.textContent = '(0% del Ingreso)';
                        porcentajeUtilidad.textContent = '(0% del Ingreso)';
                    }

                    const fechaDesdeFormato = new Date(fechaDesde + 'T00:00:00').toLocaleDateString('es-CO');
                    const fechaHastaFormato = new Date(fechaHasta + 'T00:00:00').toLocaleDateString('es-CO');
                    rangoFechasTitulo.textContent = `${fechaDesdeFormato} - ${fechaHastaFormato}`;
                    
                    cuerpoTablaDetalle.innerHTML = '';
                    if (data.detalle.length > 0) {
                        data.detalle.forEach(fila => {
                            const gananciaDiaria = fila.ingresos_diarios - fila.egresos_diarios;
                            const fechaFormato = new Date(fila.fecha + 'T00:00:00').toLocaleDateString('es-CO');
                            cuerpoTablaDetalle.innerHTML += `<tr><td>${fechaFormato}</td><td class="monto-ingreso">${formatoMoneda.format(fila.ingresos_diarios)}</td><td class="monto-egreso">${formatoMoneda.format(fila.egresos_diarios)}</td><td class="monto-neto">${formatoMoneda.format(gananciaDiaria)}</td></tr>`;
                        });
                    } else {
                        cuerpoTablaDetalle.innerHTML = '<tr><td colspan="4" style="text-align: center;">No hay transacciones en el período seleccionado.</td></tr>';
                    }
                }

                // Añadimos los escuchadores de eventos
                fechaDesdeInput.addEventListener('change', actualizarReporte);
                fechaHastaInput.addEventListener('change', actualizarReporte);

                // Llamamos a la función una vez para cargar los datos iniciales
                actualizarReporte();
            }
        </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    </body>
    </html>
