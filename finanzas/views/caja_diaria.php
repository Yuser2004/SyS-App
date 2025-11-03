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
            .resumen-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }        .resumen-columna h4 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
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
            
            <label for="id_sede "><b>Sede:</b></label>
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
            <div class="resumen-linea">
                <span>Apertura en Tarjeta:</span>
                <span class="neutro-monto" id="apertura-tarjeta">$0</span>
            </div>
            <hr>

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
                <div class="resumen-columna">
                    <h4>Balance</h4>
                    <div class="resumen-linea"><span>Efectivo:</span> <span class="balance-monto" id="balance-efectivo">$0</span></div>
                    <div class="resumen-linea"><span>Transferencia:</span> <span class="balance-monto" id="balance-transferencia">$0</span></div>
                    <div class="resumen-linea"><span>Tarjeta:</span> <span class="balance-monto" id="balance-tarjeta">$0</span></div>
                    <div class="resumen-linea total"><span>Total Balance:</span> <span class="balance-monto" id="total-balance">$0</span></div>
                </div>
            </div>

            <hr>
            <hr>
<!-- CÓDIGO NUEVO CON BOTÓN -->
<h4 style="text-align:center; margin-top:20px; display: flex; align-items: center; justify-content: center; gap: 10px;">
    <span>Movimientos no Operativos</span>
    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle" data-bs-toggle="modal" data-bs-target="#infoMovimientosModal" title="¿Qué significa esto?" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; padding: 0; font-weight: bold;">
        ?
    </button>
</h4>
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
                <span id="saldo-final">$0</span>
            </div>        
        <div class="caja-cierre" id="seccion-cierre">
            /div>
    </div>
<script>
(function() {
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

            // Valores por defecto para prevenir errores si la API no devuelve algo
            const ingresos = data.ingresos?.desglose || { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 };
            const egresos = data.egresos?.desglose || { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 };
            const prestamosEnviados = data.prestamos_enviados?.desglose || { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 };
            const prestamosRecibidos = data.prestamos_recibidos?.desglose || { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 };
            const devolucionesRecibidas = data.devoluciones_recibidas?.desglose || { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 };
            const devolucionesEnviadas = data.devoluciones_enviadas?.desglose || { efectivo: 0, transferencia: 0, tarjeta: 0, otro: 0 };
            const saldoApertura = data.saldo_apertura?.desglose || { efectivo: 0, transferencia: 0 };

            // --- ACTUALIZAR RESUMEN GENERAL ---
            document.getElementById('titulo-fecha').textContent = new Date(fecha + 'T00:00:00').toLocaleDateString('es-CO');
            
            // Saldos de Apertura (corregido)
            document.getElementById('saldo-apertura').textContent = formatoMoneda(data.saldo_apertura.total || 0);
            document.getElementById('apertura-efectivo').textContent = formatoMoneda(saldoApertura.efectivo);
            document.getElementById('apertura-transferencia').textContent = formatoMoneda(saldoApertura.transferencia);

            // Ingresos
            document.getElementById('total-ingresos').textContent = formatoMoneda(data.ingresos.total || 0);
            document.getElementById('ingresos-efectivo').textContent = formatoMoneda(ingresos.efectivo);
            document.getElementById('ingresos-transferencia').textContent = formatoMoneda(ingresos.transferencia);
            document.getElementById('ingresos-tarjeta').textContent = formatoMoneda(ingresos.tarjeta);

            // Egresos
            document.getElementById('total-egresos').textContent = '-' + formatoMoneda(data.egresos.total || 0);
            document.getElementById('egresos-efectivo').textContent = '-' + formatoMoneda(egresos.efectivo);
            document.getElementById('egresos-transferencia').textContent = '-' + formatoMoneda(egresos.transferencia);
            document.getElementById('egresos-tarjeta').textContent = '-' + formatoMoneda(egresos.tarjeta);

            // Balance Operativo (Ingresos - Egresos)
            const balanceOperativoEfectivo = ingresos.efectivo - egresos.efectivo;
            const balanceOperativoTransferencia = ingresos.transferencia - egresos.transferencia;
            const balanceOperativoTarjeta = ingresos.tarjeta - egresos.tarjeta;
            const totalBalanceOperativo = data.ingresos.total - data.egresos.total;
            
            document.getElementById('balance-efectivo').textContent = formatoMoneda(balanceOperativoEfectivo);
            document.getElementById('balance-transferencia').textContent = formatoMoneda(balanceOperativoTransferencia);
            document.getElementById('balance-tarjeta').textContent = formatoMoneda(balanceOperativoTarjeta);
            document.getElementById('total-balance').textContent = formatoMoneda(totalBalanceOperativo);

            document.getElementById('balance-efectivo').className = balanceOperativoEfectivo >= 0 ? 'balance-positivo' : 'balance-negativo';
            document.getElementById('balance-transferencia').className = balanceOperativoTransferencia >= 0 ? 'balance-positivo' : 'balance-negativo';
            document.getElementById('balance-tarjeta').className = balanceOperativoTarjeta >= 0 ? 'balance-positivo' : 'balance-negativo';
            document.getElementById('total-balance').className = totalBalanceOperativo >= 0 ? 'balance-positivo' : 'balance-negativo';
            
            // Movimientos no Operativos (corregido)
            document.getElementById('total-prestamos-recibidos').textContent = formatoMoneda(data.prestamos_recibidos.total || 0);
            document.getElementById('total-devoluciones').textContent = formatoMoneda(data.devoluciones_recibidas.total || 0);
            document.getElementById('prestamos-enviados').textContent = '-' + formatoMoneda(data.prestamos_enviados.total || 0);
            document.getElementById('total-devoluciones-enviadas').textContent = '-' + formatoMoneda(data.devoluciones_enviadas.total || 0);

            // Saldos finales (corregido)
            const balanceDiaEl = document.getElementById('balance-dia');
            balanceDiaEl.textContent = formatoMoneda(data.balance_dia.total || 0);
            balanceDiaEl.className = data.balance_dia.total >= 0 ? 'balance-positivo' : 'balance-negativo';

            const saldoFinalEl = document.getElementById('saldo-final');
            saldoFinalEl.textContent = formatoMoneda(data.saldo_final_esperado.total || 0);
            saldoFinalEl.className = data.saldo_final_esperado.total >= 0 ? 'balance-positivo' : 'balance-negativo';

            // --- ACTUALIZAR SECCIÓN DE CIERRE ---
            if (data.cierre_info) {
                const conteoRegistrado = 
                    (parseFloat(data.cierre_info.saldo_cierre_efectivo || 0) + 
                    parseFloat(data.cierre_info.saldo_cierre_transferencia || 0));

                seccionCierre.innerHTML = `
                    <h3 style="text-align:center;">Caja Cerrada</h3>
                    <div class="caja-cerrada-msg">
                        <p><strong>Cierre del día ${new Date(data.cierre_info.fecha + 'T00:00:00').toLocaleDateString('es-CO')}:</strong></p>
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
                seccionCierre.innerHTML = `
                    <h3 style="text-align:center;">Realizar Cierre de Caja</h3>
                    <p style="text-align:center; font-size: 14px;">Al cerrar la caja, se guardará un registro permanente.</p>
                    <form class="cierre-form" method="POST" action="finanzas/views/guardar_cierre.php">
                        <input type="hidden" name="id_sede" value="${idSede}">
                        <input type="hidden" name="fecha_cierre" value="${fecha}">

                        <!-- Apertura -->
                        <input type="hidden" name="saldo_apertura_efectivo" value="${saldoApertura.efectivo}">
                        <input type="hidden" name="saldo_apertura_transferencia" value="${saldoApertura.transferencia}">
                        <input type="hidden" name="saldo_apertura" value="${data.saldo_apertura.total}">

                        <!-- Totales del día -->
                        <input type="hidden" name="total_ingresos" value="${data.ingresos.total}">
                        <input type="hidden" name="total_egresos" value="${data.egresos.total}">
                        <input type="hidden" name="balance_dia" value="${data.balance_dia.total}">
                        <input type="hidden" name="saldo_final" value="${data.saldo_final || 0}">

                        <!-- Conteo real -->
                        <label for="conteo_efectivo"><b>Conteo Real en Efectivo:</b></label>
                        <input type="number" step="0.01" name="conteo_efectivo" id="conteo_efectivo" value="${data.saldo_final_esperado.desglose.efectivo}" required>

                        <label for="conteo_transferencia"><b>Conteo Real en Transferencia:</b></label>
                        <input type="number" step="0.01" name="conteo_transferencia" id="conteo_transferencia" value="${data.saldo_final_esperado.desglose.transferencia}" required>

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

    fechaInput.addEventListener('change', actualizarCajaDiaria);
    sedeInput.addEventListener('change', actualizarCajaDiaria);
    actualizarCajaDiaria();
})();
</script>
<?php include 'devolucion_modal.php'; ?>
<!-- Modal de Información de Movimientos -->
<div class="modal fade" id="infoMovimientosModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoModalLabel">Guía de Movimientos no Operativos</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="lead">Cada movimiento se ve desde la perspectiva de <strong>tu sede actual</strong>.</p>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" style="font-size: 0.9rem;">
              <thead class="table-dark">
                <tr>
                  <th style="width: 25%;">Concepto en TU Caja</th>
                  <th>¿Qué significa?</th>
                  <th style="width: 15%;" class="text-center">¿Cómo afecta a TU dinero?</th>
                </tr>
              </thead>
              <tbody>
                <tr style="vertical-align: middle;">
                  <td><strong>(+) Préstamos Recibidos</strong></td>
                  <td>Otra sede te presta dinero a ti.</td>
                  <td class="text-center"><span class="badge bg-success">ENTRA</span></td>
                </tr>
                <tr style="vertical-align: middle;">
                  <td><strong>(+) Devoluciones Recibidas</strong></td>
                  <td>Te devuelven un dinero que tú habías prestado anteriormente.</td>
                  <td class="text-center"><span class="badge bg-success">ENTRA</span></td>
                </tr>
                <tr style="vertical-align: middle;">
                  <td><strong>(-) Préstamos Enviados</strong></td>
                  <td>Tú le prestas dinero a otra sede.</td>
                  <td class="text-center"><span class="badge bg-danger">SALE</span></td>
                </tr>
                <tr style="vertical-align: middle;">
                  <td><strong>(-) Devoluciones Enviadas</strong></td>
                  <td>Tú devuelves un dinero que te habían prestado anteriormente.</td>
                  <td class="text-center"><span class="badge bg-danger">SALE</span></td>
                </tr>
              </tbody>
            </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Entendido</button>
      </div>
    </div>
  </div>
</div>
    </body>
    </html>