<?php
    // --- AÑADE ESTAS 3 LÍNEAS ---
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    // --- FIN ---
    include __DIR__ . '/../models/conexion.php';

    // 1. Consulta para el filtro de sedes
    $sedes_result = $conn->query("SELECT id, nombre FROM sedes ORDER BY nombre");
    
    // 2. Segunda consulta de sedes para pasarla al modal
    $sedes_result_para_movimiento = $conn->query("SELECT id, nombre FROM sedes ORDER BY nombre");

    // --- ¡NUEVO! ---
    // 3. Hacemos una TERCERA consulta para la lista de cuentas
    $cuentas_result = $conn->query("SELECT id, nombre_cuenta 
                                    FROM cuentas_bancarias 
                                    WHERE activa = 1 
                                    ORDER BY nombre_cuenta");
    $cuentas_para_modal = [];
    if ($cuentas_result->num_rows > 0) {
        while($row = $cuentas_result->fetch_assoc()) {
            $cuentas_para_modal[] = $row;
        }
    }
    // --- FIN NUEVO ---

    // Definimos los valores iniciales para los filtros
    $fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d'); // ¡NUEVO! Usar $_GET si existe
    $id_sede_seleccionada = $_GET['id_sede'] ?? 1; // ¡NUEVO! Usar $_GET si existe, Sede por defecto
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
            .resumen-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }     .resumen-columna h4 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
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
            
            /* --- NUEVO: Estilo para el desglose de cuentas --- */
            .desglose-linea {
                font-size: 0.9em;
                padding-left: 15px;
                color: #555;
                font-style: italic;
            }
            .desglose-linea span:first-child {
                padding-left: 10px; /* Indentación */
            }
            /* --- Estilo para el botón de exportar --- */
            .btn-exportar {
                padding: 8px 12px;
                font-size: 16px;
                background-color: #198754; /* Verde */
                color: white;
                border-radius: 6px;
                text-decoration: none;
                font-weight: 600;
                cursor: pointer; /* Añadido */
            }

            /* ============================================== */
            /* === NUEVO: ESTILOS PARA EL MODAL DE EXPORTAR === */
            /* ============================================== */
            .modal {
                display: none; 
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.4);
                padding-top: 60px;
            }
            .modal-content {
                background-color: #fefefe;
                margin: 5% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 400px;
                border-radius: 8px;
                box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
            }
            .modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #ddd;
                padding-bottom: 10px;
            }
            .modal-header h2 { margin: 0; font-size: 1.2rem; }
            .close-modal {
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }
            .modal-body { padding-top: 15px; }
            .modal-body label { display: block; margin-bottom: 5px; font-weight: bold; }
            .modal-body input[type="date"] {
                width: 100%;
                padding: 8px;
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-sizing: border-box; /* Importante */
            }
            #btn-exportar-rango-modal {
                background-color: #198754; /* Verde */
                color: white;
                padding: 10px 15px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
                font-size: 1rem;
                font-weight: 600;
            }
            #btn-exportar-rango-modal:hover { background-color: #157347; }
            /* === FIN DE ESTILOS DEL MODAL === */

            /* --- ¡NUEVO! Estilos para botones de acción --- */
            .btn-accion {
                padding: 8px 12px;
                border: none;
                border-radius: 6px;
                color: white;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
                font-size: 16px;
                margin-left: 10px; /* Espacio entre botones */
            }
            .btn-movimiento {
                background-color: #17a2b8; /* Cyan */
            }
            .btn-movimiento:hover {
                background-color: #138496;
            }
            /* --- FIN NUEVO --- */
        </style>
    </head>
    <body>

    <div class="caja-diaria">
    <h2 style="text-align:center; background-color: #007bff; color: white; padding: 10px;">Cierre Diario de Operaciones</h2>     
        <div class="filtros">
            <label for="fecha"><b>Fecha:</b></label>     
            <input type="date" id="fecha" name="fecha" value="<?= htmlspecialchars($fecha_seleccionada) ?>">
            
            <label for="id_sede"><b>Sede:</b></label> <select id="id_sede" name="id_sede">
                <?php while($sede = $sedes_result->fetch_assoc()): ?>
                    <option value="<?= $sede['id'] ?>" <?= ($id_sede_seleccionada == $sede['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($sede['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            
            <a id="btn-exportar-detalle" href="#" onclick="event.preventDefault()" class="btn-accion btn-exportar">
                Exportar Reporte Caja
            </a>
            
            <a id="btn-abrir-modal-movimiento" href="#" onclick="event.preventDefault()" class="btn-accion btn-movimiento">
                + Movimiento
            </a>
        </div>

        <div class="caja-resumen">
            <h3 style="text-align:center;">Movimientos del Día: <span id="titulo-fecha"></span></h3>
            <div class="resumen-linea total">
                <span>SALDO DE APERTURA (Ultimo Cierre)</span>
                <span id="saldo-apertura">$0</span>
            </div>

            <div class="resumen-linea">
                <span>Apertura en Efectivo:</span>
                <span class="neutro-monto" id="apertura-efectivo">$0</span>
            </div>
            <div class="resumen-linea">
                <span>Apertura en Transferencia:</span>
                <span class="neutro-monto" id="apertura-transferencia">$0</span>
            </div>
            <hr>

            <hr>
            <div class="resumen-grid">
                <div class="resumen-columna">
                    <h4>Ingresos</h4>
                    <div class="resumen-linea"><span>Efectivo:</span> <span class="ingreso-monto" id="ingresos-efectivo">$0</span></div>
                    
                    <div class="resumen-linea"><span>&nbsp;&nbsp;↳ Entr. Internas:</span> <span class="ingreso-monto" id="entradas-efectivo">$0</span></div>

                    <div class="resumen-linea"><span>Transferencia:</span> <span class="ingreso-monto" id="ingresos-transferencia">$0</span></div>
                    <div id="ingresos-transferencia-desglose"></div> 
                    
                    <div class="resumen-linea"><span>&nbsp;&nbsp;↳ Entr. Internas:</span> <span class="ingreso-monto" id="entradas-transferencia">$0</span></div>

                    <div class="resumen-linea"><span>Tarjeta:</span> <span class="ingreso-monto" id="ingresos-tarjeta">$0</span></div>
                    <div class="resumen-linea total"><span>Total Ingresos:</span> <span class="ingreso-monto" id="total-ingresos">$0</span></div>
                </div>
                <div class="resumen-columna">
                    <h4>Egresos (Servicios)</h4>
                    <div class="resumen-linea"><span>Efectivo:</span> <span class="salida-monto" id="egresos-efectivo">-$0</span></div>

                    <div class="resumen-linea"><span>&nbsp;&nbsp;↳ Sal. Internas:</span> <span class="salida-monto" id="salidas-efectivo">-$0</span></div>

                    <div class="resumen-linea"><span>Transferencia:</span> <span class="salida-monto" id="egresos-transferencia">-$0</span></div>
                    <div id="egresos-transferencia-desglose"></div>
                    
                    <div class="resumen-linea"><span>&nbsp;&nbsp;↳ Sal. Internas:</span> <span class="salida-monto" id="salidas-transferencia">-$0</span></div>

                    <div class="resumen-linea"><span>Tarjeta:</span> <span class="salida-monto" id="egresos-tarjeta">-$0</span></div>
                    <div class="resumen-linea total"><span>Total Egresos:</span> <span class="salida-monto" id="total-egresos">-$0</span></div>
                </div>
                <div class="resumen-columna">
                    <h4>Balance</h4>
                    <div class="resumen-linea"><span>Efectivo:</span> <span class="balance-monto" id="balance-efectivo">$0</span></div>
                    <div class="resumen-linea"><span>Transferencia:</span> <span class="balance-monto" id="balance-transferencia">$0</span></div>
                    <div id="balance-transferencia-desglose"></div>
                    <div class="resumen-linea"><span>Tarjeta:</span> <span class="balance-monto" id="balance-tarjeta">$0</span></div>
                    <div class="resumen-linea total"><span>Total Balance:</span> <span class="balance-monto" id="total-balance">$0</span></div>
                </div>
            </div>

            <hr>
            <hr>
            
            <div class="resumen-linea total">
                <span>BALANCE DEL DÍA (Ingresos - Salidas)</span>
                <span id="balance-dia">$0</span> 
            </div>
            <div class="resumen-linea total" style="font-size: 1.2em;">
                <span>SALDO FINAL ESPERADO EN CAJA</span>
                <span id="saldo-final">$0</span>
            </div>       
        <div class="caja-cierre" id="seccion-cierre">
            </div>
    </div>

    <div id="modalRangoFechas" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Exportar Reporte por Rango</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <label for="fecha_inicio_modal">Fecha de Inicio:</label>
                <input type="date" id="fecha_inicio_modal" value="<?php echo date('Y-m-d'); ?>">
                
                <label for="fecha_fin_modal">Fecha de Fin:</label>
                <input type="date" id="fecha_fin_modal" value="<?php echo date('Y-m-d'); ?>">
                
                <button id="btn-exportar-rango-modal">Generar y Descargar Excel</button>
            </div>
        </div>
    </div>
    
    <?php
    // Pasamos ambas variables al modal
    if (isset($sedes_result_para_movimiento) && isset($cuentas_para_modal)) {
        include __DIR__ . '/movimiento_sede_modal.php';
    }
    ?>
    <script>
(function() {
    const fechaInput = document.getElementById('fecha');
    const sedeInput = document.getElementById('id_sede');
    const seccionCierre = document.getElementById('seccion-cierre');
    const exportButton = document.getElementById('btn-exportar-detalle');
    
    const modal = document.getElementById('modalRangoFechas');
    const closeModalSpan = document.querySelector('.close-modal');
    const fechaInicioModal = document.getElementById('fecha_inicio_modal');
    const fechaFinModal = document.getElementById('fecha_fin_modal');
    const btnExportarRangoModal = document.getElementById('btn-exportar-rango-modal');
    
    // --- Referencias para el MODAL DE MOVIMIENTO ---
    const btnAbrirModalMov = document.getElementById('btn-abrir-modal-movimiento');
    const modalOverlayMov = document.getElementById('movimiento-sede-modal-overlay');
    const modalCloseMov = document.getElementById('movimiento-sede-modal-close');
    const formMovimiento = document.getElementById('form-movimiento-sede');
    const inputAsesorNombreMov = document.getElementById('buscar-asesor-movimiento');
    const inputAsesorIdMov = document.getElementById('id-asesor-movimiento');
    const resultadosDivMov = document.getElementById('asesor-movimiento-resultados');
    
    // --- ¡NUEVO! Referencias para Cuentas Bancarias ---
    const metodoOrigenSelect = document.getElementById('mov-metodo-pago-origen');
    const metodoDestinoSelect = document.getElementById('mov-metodo-pago-destino');
    const detalleOrigenInput = document.getElementById('mov-detalle-pago-origen');
    const detalleDestinoInput = document.getElementById('mov-detalle-pago-destino');
    const grupoCuentaOrigen = document.getElementById('grupo-cuenta-origen');
    const grupoCuentaDestino = document.getElementById('grupo-cuenta-destino');
    const cuentaOrigenSelect = document.getElementById('mov-cuenta-origen');
    const cuentaDestinoSelect = document.getElementById('mov-cuenta-destino');
    const sedeOrigenSelect = document.getElementById('mov-id-sede-origen');
    const sedeDestinoSelect = document.getElementById('mov-id-sede-destino');
    // --- FIN NUEVO ---


    // Chequeos de elementos
    if (!fechaInput || !sedeInput || !seccionCierre || !exportButton) {
        console.error("Faltan elementos JS: fecha, sede, cierre o exportButton");
        return;
    }
    if (!modal || !closeModalSpan || !fechaInicioModal || !fechaFinModal || !btnExportarRangoModal) {
         console.error("Faltan elementos JS del modal de exportar");
         return;
    }
    // Verificamos todos los elementos del modal de movimiento
    if (!btnAbrirModalMov || !modalOverlayMov || !modalCloseMov || !formMovimiento ||
        !metodoOrigenSelect || !metodoDestinoSelect || !detalleOrigenInput || !detalleDestinoInput ||
        !grupoCuentaOrigen || !grupoCuentaDestino || !cuentaOrigenSelect || !cuentaDestinoSelect ||
        !sedeOrigenSelect || !sedeDestinoSelect) {
        console.warn("Faltan elementos JS del modal de movimiento. Asegúrate de incluir 'movimiento_sede_modal.php' correctamente.");
    }

    const formatoMoneda = (valor) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(valor);

    // Función para generar desglose (sin cambios)
    const generarHtmlDesglose = (cuentasObjeto, contexto = 'ingreso') => {
        if (!cuentasObjeto || Object.keys(cuentasObjeto).length === 0) {
           return '';
        }
        let html = '';
        for (const [cuenta, monto] of Object.entries(cuentasObjeto)) {
           if (monto === 0) continue; 
           let montoFormateado = '';
           let claseMonto = 'neutro-monto';
           if (contexto === 'ingreso') {
               claseMonto = 'ingreso-monto';
               montoFormateado = formatoMoneda(monto);
           } else if (contexto === 'egreso') {
               claseMonto = 'salida-monto';
               montoFormateado = '-' + formatoMoneda(monto);
           } else if (contexto === 'balance') {
               claseMonto = monto >= 0 ? 'balance-positivo' : 'balance-negativo';
               montoFormateado = formatoMoneda(monto);
           }
           html += `
                <div class="resumen-linea desglose-linea">
                    <span>&hookrightarrow; ${cuenta}</span>
                    <span class="${claseMonto}">${montoFormateado}</span>
                </div>`;
        }
        return html;
    };


    async function actualizarCajaDiaria() {
        const fecha = fechaInput.value;
        const idSede = sedeInput.value;

        try {
            // --- ¡CORREGIDO! Añadimos "cache buster" ---
            const cacheBuster = '&v=' + new Date().getTime();
            const response = await fetch(`finanzas/views/api_caja_diaria.php?fecha=${fecha}&id_sede=${idSede}${cacheBuster}`);
            
            if (!response.ok) throw new Error('No se pudo conectar con la API');
            
            // --- Log de depuración (puedes quitarlo después) ---
            const responseText = await response.text();
            let data;
            try {
                 data = JSON.parse(responseText);
            } catch (jsonError) {
                console.error("Error al parsear JSON:", jsonError);
                console.error("Texto recibido de la API:", responseText);
                seccionCierre.innerHTML = `<div class="caja-cerrada-msg" style="background-color: #f8d7da; color: #721c24;">Error de API. Respuesta no es JSON.</div>`;
                return;
            }
            // --- Fin depuración ---

            // --- ¡MODIFICADO! Obtenemos los nuevos desgloses ---
            const ingresos = data.ingresos?.desglose;
            const egresos = data.egresos?.desglose;
            const entradas = data.entradas_internas?.desglose; // ¡NUEVO!
            const salidas = data.salidas_internas?.desglose;   // ¡NUEVO!
            const balance = data.balance_dia?.desglose;
            const saldoApertura = data.saldo_apertura?.desglose || { efectivo: 0, transferencia: 0 };

            document.getElementById('titulo-fecha').textContent = new Date(fecha + 'T05:00:00').toLocaleDateString('es-CO');
            
            document.getElementById('saldo-apertura').textContent = formatoMoneda(data.saldo_apertura.total || 0);
            document.getElementById('apertura-efectivo').textContent = formatoMoneda(saldoApertura.efectivo);
            document.getElementById('apertura-transferencia').textContent = formatoMoneda(saldoApertura.transferencia);
            
            const aperturaTarjetaEl = document.getElementById('apertura-tarjeta');
            if (aperturaTarjetaEl) aperturaTarjetaEl.closest('.resumen-linea').remove(); // Limpia HTML viejo

            // --- Ingresos (¡MODIFICADO!) ---
            const totalIngresosVisual = (data.ingresos.total || 0) + (data.entradas_internas?.total || 0);
            document.getElementById('total-ingresos').textContent = formatoMoneda(totalIngresosVisual);
            document.getElementById('ingresos-efectivo').textContent = formatoMoneda(ingresos?.efectivo || 0);
            document.getElementById('ingresos-transferencia').textContent = formatoMoneda(ingresos?.transferencia || 0);
            document.getElementById('ingresos-tarjeta').textContent = formatoMoneda(ingresos?.tarjeta || 0);
            document.getElementById('ingresos-transferencia-desglose').innerHTML = generarHtmlDesglose(ingresos?.transferencias, 'ingreso');
            // ¡NUEVO! Pintar entradas internas
            document.getElementById('entradas-efectivo').textContent = formatoMoneda(entradas?.efectivo || 0);
            document.getElementById('entradas-transferencia').textContent = formatoMoneda(entradas?.transferencia || 0);


            // --- Egresos (¡MODIFICADO!) ---
            const totalEgresosVisual = (data.egresos.total || 0) + (data.salidas_internas?.total || 0);
            document.getElementById('total-egresos').textContent = '-' + formatoMoneda(totalEgresosVisual);
            document.getElementById('egresos-efectivo').textContent = '-' + formatoMoneda(egresos?.efectivo || 0);
            document.getElementById('egresos-transferencia').textContent = '-' + formatoMoneda(egresos?.transferencia || 0);
            document.getElementById('egresos-tarjeta').textContent = '-' + formatoMoneda(egresos?.tarjeta || 0);
            document.getElementById('egresos-transferencia-desglose').innerHTML = generarHtmlDesglose(egresos?.transferencias, 'egreso');
            // ¡NUEVO! Pintar salidas internas
            document.getElementById('salidas-efectivo').textContent = '-' + formatoMoneda(salidas?.efectivo || 0);
            document.getElementById('salidas-transferencia').textContent = '-' + formatoMoneda(salidas?.transferencia || 0);

            
            // --- Balance (El balance de la API ya es correcto) ---
            const totalBalanceOperativo = data.balance_dia.total || 0;
            document.getElementById('balance-efectivo').textContent = formatoMoneda(balance?.efectivo || 0);
            document.getElementById('balance-efectivo').className = (balance?.efectivo || 0) >= 0 ? 'balance-positivo' : 'balance-negativo';
            
            document.getElementById('balance-transferencia').textContent = formatoMoneda(balance?.transferencia || 0);
            document.getElementById('balance-transferencia').className = (balance?.transferencia || 0) >= 0 ? 'balance-positivo' : 'balance-negativo';

            document.getElementById('balance-tarjeta').textContent = formatoMoneda(balance?.tarjeta || 0);
            document.getElementById('balance-tarjeta').className = (balance?.tarjeta || 0) >= 0 ? 'balance-positivo' : 'balance-negativo';
            
            document.getElementById('total-balance').textContent = formatoMoneda(totalBalanceOperativo);
            document.getElementById('total-balance').className = totalBalanceOperativo >= 0 ? 'balance-positivo' : 'balance-negativo';
            
            document.getElementById('balance-transferencia-desglose').innerHTML = generarHtmlDesglose(balance?.transferencias, 'balance');
            
            
            // --- Saldos finales (La API ya incluye todo) ---
            const balanceDiaEl = document.getElementById('balance-dia');
            balanceDiaEl.textContent = formatoMoneda(data.balance_dia.total || 0);
            balanceDiaEl.className = data.balance_dia.total >= 0 ? 'balance-positivo' : 'balance-negativo';

            const saldoFinalEl = document.getElementById('saldo-final');
            saldoFinalEl.textContent = formatoMoneda(data.saldo_final_esperado.total || 0);
            saldoFinalEl.className = data.saldo_final_esperado.total >= 0 ? 'balance-positivo' : 'balance-negativo';

            // --- ACTUALIZAR SECCIÓN DE CIERRE (¡MODIFICADO!) ---
            if (data.cierre_info) {
                // (Tu HTML de "Caja Cerrada" está perfecto)
                const conteoRegistrado = 
                        (parseFloat(data.cierre_info.conteo_efectivo_cierre || 0) +
                        parseFloat(data.cierre_info.conteo_transferencia_cierre || 0));
                seccionCierre.innerHTML = `
                    <h3 style="text-align:center;">Caja Cerrada</h3>
                    <div class="caja-cerrada-msg">
                        <p><strong>Cierre del día ${new Date(data.cierre_info.fecha + 'T05:00:00').toLocaleDateString('es-CO')}:</strong></p>
                        <div class="resumen-linea">
                            <span>Saldo Esperado:</span>
                            <strong>${formatoMoneda(data.cierre_info.saldo_final)}</strong>
                        </div>
                        <div class="resumen-linea">
                            <span>Conteo Registrado:</span>
                            <strong>${formatoMoneda(conteoRegistrado)}</strong>
                        </div>
                        <div class="resumen-linea">
                            <span>Diferencia de Caja:</span>
                            <strong class="${data.cierre_info.diferencia >= 0 ? 'ingreso-monto' : 'salida-monto'}">
                                ${formatoMoneda(data.cierre_info.diferencia)}
                            </strong>
                        </div>
                        <div style="margin-top: 15px;">
                            <strong>Notas del Cierre:</strong>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 10px; border-radius: 5px; margin-top: 5px; white-space: pre-wrap;">
                                ${data.cierre_info.notas || '<em>Sin notas.</em>'}
                            </div>
                        </div>
                    </div>
                `;
            }
            else if (data.se_puede_cerrar && data.hubo_movimientos_hoy) {
                // Rellenar formulario con los datos correctos de la API
                const saldoFinalEfectivo = data.saldo_final_esperado.desglose.efectivo || 0;
                const saldoFinalTransferencia = data.saldo_final_esperado.desglose.transferencia || 0;
                
                seccionCierre.innerHTML = `
                    <h3 style="text-align:center;">Realizar Cierre de Caja</h3>
                    <p style="text-align:center; font-size: 14px;">Al cerrar la caja, se guardará un registro permanente.</p>
                    <form class="cierre-form" method="POST" action="finanzas/views/guardar_cierre.php">
                        <input type="hidden" name="id_sede" value="${idSede}">
                        <input type="hidden" name="fecha_cierre" value="${fecha}">

                        <input type="hidden" name="saldo_apertura_efectivo" value="${saldoApertura.efectivo}">
                        <input type="hidden" name="saldo_apertura_transferencia" value="${saldoApertura.transferencia}">
                        <input type="hidden" name="saldo_apertura" value="${data.saldo_apertura.total}">

                        <input type="hidden" name="total_ingresos" value="${data.ingresos.total}">
                        <input type="hidden" name="total_egresos" value="${data.egresos.total}">
                        
                        <input type="hidden" name="total_entradas_internas" value="${data.entradas_internas?.total || 0}">
                        <input type="hidden" name="total_salidas_internas" value="${data.salidas_internas?.total || 0}">
                        
                        <input type="hidden" name="balance_dia" value="${data.balance_dia.total}">
                        <input type="hidden" name="saldo_final" value="${data.saldo_final_esperado.total || 0}">

                        <label for="conteo_efectivo"><b>Conteo Real en Efectivo:</b></label>
                        <input type="number" step="0.01" name="conteo_efectivo" id="conteo_efectivo" value="${saldoFinalEfectivo}" required>

                        <label for="conteo_transferencia"><b>Conteo Real en Transferencia:</b></label>
                        <input type="number" step="0.01" name="conteo_transferencia" id="conteo_transferencia" value="${saldoFinalTransferencia}" required>

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

    // Lógica de envío del formulario de cierre (Sin cambios)
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

    // Event Listeners de Filtros
    fechaInput.addEventListener('change', actualizarCajaDiaria);
    sedeInput.addEventListener('change', actualizarCajaDiaria);

    // --- Lógica del Modal de Exportar (Sin cambios) ---
    exportButton.addEventListener('click', function() {
        const fechaPrincipal = fechaInput.value;
        fechaInicioModal.value = fechaPrincipal;
        fechaFinModal.value = fechaPrincipal;
        modal.style.display = 'block';
    });
    closeModalSpan.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    });
    btnExportarRangoModal.addEventListener('click', function() {
        const fechaInicio = fechaInicioModal.value;
        const fechaFin = fechaFinModal.value;
        const idSede = sedeInput.value; 
        if (!fechaInicio || !fechaFin) {
            alert('Por favor, seleccione ambas fechas.');
            return;
        }
        if (fechaFin < fechaInicio) {
            alert('La fecha de fin no puede ser anterior a la fecha de inicio.');
            return;
        }
        const url = `finanzas/views/exportar_detalle_caja.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&id_sede=${idSede}`;
        window.open(url, '_blank');
        modal.style.display = 'none';
    });
    
    // --- ¡INICIO! LÓGICA COMPLETA DEL MODAL DE MOVIMIENTOS ---
    
    // --- LÓGICA PARA MOSTRAR/OCULTAR CUENTAS BANCARIAS ---

    /**
     * Muestra u oculta el <select> de cuentas basado en el método de pago.
     */
    function toggleCuentaSelect(metodo, grupoCuenta, inputDetalle, selectCuenta) {
        if (metodo === 'transferencia') {
            grupoCuenta.style.display = 'block'; // Muestra el select de cuentas
            selectCuenta.required = true; // Hacemos el select de cuenta requerido
            // Limpiamos el valor al cambiar
            selectCuenta.selectedIndex = 0;
            inputDetalle.value = ''; 
        } else {
            grupoCuenta.style.display = 'none'; // Oculta el select de cuentas
            selectCuenta.required = false; // Ya no es requerido
            // Si es efectivo, guardamos "Efectivo", si no (es "otro") lo dejamos vacío
            inputDetalle.value = (metodo === 'efectivo') ? 'Efectivo' : ''; 
        }
    }

    // --- CONECTAR LOS EVENTOS ---

    // 1. Al cambiar el MÉTODO DE PAGO (Origen)
    if (metodoOrigenSelect) {
        metodoOrigenSelect.addEventListener('change', function() {
            toggleCuentaSelect(this.value, grupoCuentaOrigen, detalleOrigenInput, cuentaOrigenSelect);
        });
    }

    // 2. Al cambiar el MÉTODO DE PAGO (Destino)
    if (metodoDestinoSelect) {
        metodoDestinoSelect.addEventListener('change', function() {
            toggleCuentaSelect(this.value, grupoCuentaDestino, detalleDestinoInput, cuentaDestinoSelect);
        });
    }

    // 3. Al seleccionar una CUENTA (Origen) -> Copiamos el *nombre* al input oculto
    if (cuentaOrigenSelect) {
        cuentaOrigenSelect.addEventListener('change', function() {
            if (this.selectedIndex > 0) {
                // Copia el texto (ej: "Bancolombia 123") al input oculto
                detalleOrigenInput.value = this.options[this.selectedIndex].text.trim();
            }
        });
    }

    // 4. Al seleccionar una CUENTA (Destino) -> Copiamos el *nombre* al input oculto
    if (cuentaDestinoSelect) {
        cuentaDestinoSelect.addEventListener('change', function() {
            if (this.selectedIndex > 0) {
                // Copia el texto (ej: "Davivienda 456") al input oculto
                detalleDestinoInput.value = this.options[this.selectedIndex].text.trim();
            }
        });
    }
    
    // --- FIN LÓGICA CUENTAS ---


    // --- LÓGICA ABRIR/CERRAR MODAL MOVIMIENTO ---
    if (btnAbrirModalMov) {
        btnAbrirModalMov.addEventListener('click', () => {
            const sedeActualID = sedeInput.value;
            
            if(formMovimiento) formMovimiento.reset();
            
            // Resetea los campos de cuenta y método
            if (grupoCuentaOrigen) grupoCuentaOrigen.style.display = 'none';
            if (grupoCuentaDestino) grupoCuentaDestino.style.display = 'none';
            if (cuentaOrigenSelect) cuentaOrigenSelect.required = false;
            if (cuentaDestinoSelect) cuentaDestinoSelect.required = false;

            if (sedeOrigenSelect) {
                sedeOrigenSelect.value = sedeActualID;
            }
            if(inputAsesorIdMov) inputAsesorIdMov.value = '';
            
            // Pone la fecha y hora actual
            const fechaHoraActual = new Date().toISOString().slice(0, 16);
            const inputFechaMov = document.getElementById('mov-fecha');
            if(inputFechaMov) inputFechaMov.value = fechaHoraActual;

            if (modalOverlayMov) modalOverlayMov.style.display = 'flex';
        });
    }

    // Cerrar Modal
    if (modalCloseMov) {
        modalCloseMov.onclick = () => { if(modalOverlayMov) modalOverlayMov.style.display = 'none'; };
    }
    if (modalOverlayMov) {
        modalOverlayMov.onclick = (e) => {
            if (e.target === modalOverlayMov) modalOverlayMov.style.display = 'none';
        };
    }

    // Búsqueda en vivo de Asesor (Movimientos)
    if (inputAsesorNombreMov) {
        inputAsesorNombreMov.addEventListener('keyup', function() {
            const term = inputAsesorNombreMov.value;
            
            if (term.length < 2) {
                if (resultadosDivMov) {
                    resultadosDivMov.innerHTML = '';
                    resultadosDivMov.style.display = 'none';
                }
                return;
            }

            // Llama a la API simple
            fetch(`finanzas/views/buscar_asesor_simple.php?term=${term}`)
                .then(response => response.json())
                .then(data => {
                    if (!resultadosDivMov) return;
                    resultadosDivMov.innerHTML = '';
                    if (data.length > 0) {
                        resultadosDivMov.style.display = 'block';
                        data.forEach(asesor => {
                            const div = document.createElement('div');
                            div.textContent = `${asesor.nombre} (${asesor.documento || 'N/A'})`;
                            div.dataset.id = asesor.id_asesor;
                            div.addEventListener('click', function() {
                                inputAsesorNombreMov.value = this.textContent;
                                inputAsesorIdMov.value = this.dataset.id;
                                resultadosDivMov.innerHTML = '';
                                resultadosDivMov.style.display = 'none';
                            });
                            resultadosDivMov.appendChild(div);
                        });
                    } else {
                        resultadosDivMov.style.display = 'none';
                    }
                });
        });
    }

    // Enviar formulario de Movimiento (CON VALIDACIÓN COMPLETA)
    if (formMovimiento) {
        formMovimiento.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // --- ¡INICIO DE VALIDACIÓN MANUAL! ---
            if (!sedeOrigenSelect.value) {
                alert('Por favor, selecciona una Sede de Origen.');
                sedeOrigenSelect.focus();
                return;
            }
            if (!metodoOrigenSelect.value) {
                alert('Por favor, selecciona un Método de Salida (Origen).');
                metodoOrigenSelect.focus();
                return;
            }
            if (metodoOrigenSelect.value === 'transferencia' && !cuentaOrigenSelect.value) {
                alert('Por favor, selecciona una Cuenta de Origen.');
                cuentaOrigenSelect.focus();
                return;
            }
            if (!sedeDestinoSelect.value) {
                alert('Por favor, selecciona una Sede de Destino.');
                sedeDestinoSelect.focus();
                return;
            }
            if (sedeOrigenSelect.value === sedeDestinoSelect.value) {
                alert('La Sede de Origen y Destino no pueden ser la misma.');
                sedeDestinoSelect.focus();
                return;
            }
            if (!metodoDestinoSelect.value) {
                alert('Por favor, selecciona un Método de Entrada (Destino).');
                metodoDestinoSelect.focus();
                return;
            }
            if (metodoDestinoSelect.value === 'transferencia' && !cuentaDestinoSelect.value) {
                alert('Por favor, selecciona una Cuenta de Destino.');
                cuentaDestinoSelect.focus();
                return;
            }
            if (!inputAsesorIdMov.value) {
                alert('Por favor, selecciona un asesor de la lista.');
                inputAsesorNombreMov.focus();
                return;
            }
            // --- ¡FIN DE VALIDACIÓN! ---


            const formData = new FormData(formMovimiento);
            const btnGuardar = document.getElementById('btn-guardar-movimiento');
            btnGuardar.textContent = 'Guardando...';
            btnGuardar.disabled = true;

            fetch('finanzas/views/guardar_movimiento_sede.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Log de depuración (puedes quitarlo después)
                console.log('Respuesta del servidor al GUARDAR:', data);

                if (data.success) {
                    alert('Movimiento guardado correctamente.');
                    formMovimiento.reset();
                    if(modalOverlayMov) modalOverlayMov.style.display = 'none';
                    // ¡Recarga los datos de la caja!
                    actualizarCajaDiaria(); 
                } else {
                    alert('Error al guardar: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error en fetch guardar_movimiento:', error);
                alert('Error de conexión al guardar el movimiento.');
            })
            .finally(() => {
                btnGuardar.textContent = 'Guardar Movimiento';
                btnGuardar.disabled = false;
            });
        });
    }
    // --- FIN LÓGICA MODAL MOVIMIENTO ---

    // Carga inicial de datos
    actualizarCajaDiaria();

})();
</script>
    </body>
    </html>