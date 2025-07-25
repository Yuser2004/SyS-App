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
        .caja-diaria { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 900px; margin: auto; }
        .caja-header, .caja-resumen, .caja-cierre { padding: 20px; border: 2px solid #ccc; border-radius: 8px; margin-bottom: 20px; background-color: #f9f9f9b6; }
        .filtros { display: flex; gap: 20px; align-items: center; margin-bottom: 20px; }
        .resumen-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .resumen-columna h4 { border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .resumen-linea { display: flex; justify-content: space-between; padding: 5px 0; }
        .total { font-weight: bold; border-top: 1px solid #ccc; padding-top: 10px; margin-top: 10px; }
        .ingreso-monto { color: #28a745; }
        .salida-monto { color: #dc3545; }
        .balance-positivo { color: #007bff; }
        .balance-negativo { color: #dc3545; }
        .cierre-form input, .cierre-form textarea { width: 100%; padding: 8px; margin-bottom: 10px; }
        .cierre-form button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .caja-cerrada-msg { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; }
    </style>
</head>
<body>

<div class="caja-diaria">
    <h2 style="text-align:center;">Cierre Diario de Operaciones</h2>
    
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
            <div class="resumen-columna">
                <h4>Gastos Operativos</h4>
                <div class="resumen-linea"><span>Efectivo:</span> <span class="salida-monto" id="gastos-efectivo">-$0</span></div>
                <div class="resumen-linea"><span>Transferencia:</span> <span class="salida-monto" id="gastos-transferencia">-$0</span></div>
                <div class="resumen-linea"><span>Tarjeta:</span> <span class="salida-monto" id="gastos-tarjeta">-$0</span></div>
                <div class="resumen-linea total"><span>Total Gastos:</span> <span class="salida-monto" id="total-gastos">-$0</span></div>
            </div>
        </div>
        <hr>
        <div class="resumen-linea total">
            <span>BALANCE DEL DÍA (Ingresos - Salidas)</span>
            <span id="balance-dia">$0</span>
        </div>
        <div class="resumen-linea total" style="font-size: 1.2em;">
            <span>SALDO FINAL ESPERADO EN CAJA</span>
            <span id="saldo-final">$0</span>
        </div>
    </div>
    
    <div class="caja-cierre" id="seccion-cierre">
        </div>
</div>

<script>
(function() { // "Burbuja" para evitar conflictos
    const fechaInput = document.getElementById('fecha');
    const sedeInput = document.getElementById('id_sede');

    if (!fechaInput || !sedeInput) return;

    const formatoMoneda = (valor) => new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 0 }).format(valor);

    async function actualizarCajaDiaria() {
        const fecha = fechaInput.value;
        const idSede = sedeInput.value;

        const response = await fetch(`finanzas/views/api_caja_diaria.php?fecha=${fecha}&id_sede=${idSede}`);
        const data = await response.json();

        // --- 1. ACTUALIZAR TÍTULO Y DATOS NUMÉRICOS ---
        document.getElementById('titulo-fecha').textContent = new Date(fecha + 'T00:00:00').toLocaleDateString('es-CO');
        document.getElementById('saldo-apertura').textContent = formatoMoneda(data.saldo_apertura);
        document.getElementById('total-ingresos').textContent = formatoMoneda(data.ingresos.total);
        document.getElementById('total-egresos').textContent = '-' + formatoMoneda(data.egresos.total);
        document.getElementById('total-gastos').textContent = '-' + formatoMoneda(data.gastos.total);
        document.getElementById('ingresos-efectivo').textContent = formatoMoneda(data.ingresos.desglose.efectivo);
        document.getElementById('ingresos-transferencia').textContent = formatoMoneda(data.ingresos.desglose.transferencia);
        document.getElementById('ingresos-tarjeta').textContent = formatoMoneda(data.ingresos.desglose.tarjeta);
        document.getElementById('egresos-efectivo').textContent = '-' + formatoMoneda(data.egresos.desglose.efectivo);
        document.getElementById('egresos-transferencia').textContent = '-' + formatoMoneda(data.egresos.desglose.transferencia);
        document.getElementById('egresos-tarjeta').textContent = '-' + formatoMoneda(data.egresos.desglose.tarjeta);
        document.getElementById('gastos-efectivo').textContent = '-' + formatoMoneda(data.gastos.desglose.efectivo);
        document.getElementById('gastos-transferencia').textContent = '-' + formatoMoneda(data.gastos.desglose.transferencia);
        document.getElementById('gastos-tarjeta').textContent = '-' + formatoMoneda(data.gastos.desglose.tarjeta);

        const balanceDiaEl = document.getElementById('balance-dia');
        balanceDiaEl.textContent = formatoMoneda(data.balance_dia);
        balanceDiaEl.className = data.balance_dia >= 0 ? 'balance-positivo total resumen-linea' : 'balance-negativo total resumen-linea';

        const saldoFinalEl = document.getElementById('saldo-final');
        saldoFinalEl.textContent = formatoMoneda(data.saldo_final_esperado);
        saldoFinalEl.className = data.saldo_final_esperado >= 0 ? 'balance-positivo total resumen-linea' : 'balance-negativo total resumen-linea';

        // --- 2. ACTUALIZAR SECCIÓN DE CIERRE (Lógica corregida, no duplicada) ---
        const seccionCierre = document.getElementById('seccion-cierre');
        if (data.caja_cerrada) {
            seccionCierre.innerHTML = `<div class="caja-cerrada-msg">La caja para esta fecha y sede ya fue cerrada.</div>`;
        } else if (data.se_puede_cerrar) {
            seccionCierre.innerHTML = `
                <h3 style="text-align:center;">Realizar Cierre de Caja</h3>
                <p style="text-align:center; font-size: 14px;">Al cerrar la caja, se guardará un registro permanente.</p>
                <form class="cierre-form" method="POST" action="finanzas/views/guardar_cierre.php">
                    <input type="hidden" name="id_sede" value="${idSede}">
                    <input type="hidden" name="fecha_cierre" value="${fecha}">
                    <input type="hidden" name="saldo_apertura" value="${data.saldo_apertura}">
                    <input type="hidden" name="total_ingresos" value="${data.ingresos.total}">
                    <input type="hidden" name="total_egresos" value="${data.egresos.total}">
                    <input type="hidden" name="total_gastos" value="${data.gastos.total}">
                    <input type="hidden" name="balance_dia" value="${data.balance_dia}">
                    <input type="hidden" name="saldo_final" value="${data.saldo_final_esperado}">
                    <label for="conteo_efectivo"><b>Conteo Físico de Efectivo al Cierre:</b></label>
                    <input type="number" step="0.01" name="conteo_efectivo" id="conteo_efectivo" required>
                    <label for="notas"><b>Notas Adicionales:</b></label>
                    <textarea name="notas" id="notas" rows="3"></textarea>
                    <button type="submit">CERRAR CAJA DEL DÍA</button>
                </form>
            `;
        } else {
            seccionCierre.innerHTML = `<div class="caja-cerrada-msg" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb;">${data.mensaje_cierre_bloqueado}</div>`;
        }
    }

    fechaInput.addEventListener('change', actualizarCajaDiaria);
    sedeInput.addEventListener('change', actualizarCajaDiaria);

    actualizarCajaDiaria();
})();
</script>

</body>
</html>