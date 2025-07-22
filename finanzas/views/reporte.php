<?php
// El PHP ahora solo define las fechas por defecto
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
        /* Clases de colores para las cajas */
        .ingresos { background-color: #e9f5ec; border-left: 5px solid var(--color-ingresos); } .ingresos .monto { color: var(--color-ingresos); }
        .egresos { background-color: #fbebed; border-left: 5px solid var(--color-egresos); } .egresos .monto { color: var(--color-egresos); }
        .neto { background-color: #e6f2ff; border-left: 5px solid var(--color-neto); } .neto .monto { color: var(--color-neto); }
        .gastos { background-color: #fff0f1; border-left: 5px solid var(--color-gastos); } .gastos .monto { color: var(--color-gastos); }
        .utilidad { background-color: #e0fbf6; border-left: 5px solid var(--color-utilidad); } .utilidad .monto { color: var(--color-utilidad); }
        /* Estilos de tabla */
        .detalle-diario h3 { text-align: center; margin-bottom: 20px; }
        .tabla-detalle { width: 100%; border-collapse: collapse; }
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
        
        <a href="#" class="btn-gestionar-gastos" onclick="cargarContenido('finanzas/views/gestion_gastos.php'); return false;">
            + Gestionar Gastos
        </a>
    </div>

    <h3>Resumen del Período (<span id="rango-fechas-titulo"></span>)</h3>
    <div class="resumen-total">
        <div class="resumen-caja ingresos">
            <h3>Total Ingresos</h3>
            <p class="monto" id="resumen-ingresos">$0</p>
            <p class="porcentaje">(100%)</p>
        </div>
        <div class="resumen-caja egresos">
            <h3>Egresos por Servicio</h3>
            <p class="monto" id="resumen-egresos">$0</p>
            <p class="porcentaje" id="porcentaje-egresos">(0% del Ingreso)</p>
        </div>
        <div class="resumen-caja gastos">
            <h3>Costos y Gastos Fijos</h3>
            <p class="monto" id="resumen-gastos">$0</p>
            <p class="porcentaje" id="porcentaje-gastos">(0% del Ingreso)</p>
        </div>
        <div class="resumen-caja utilidad">
            <h3>Utilidad Real</h3>
            <p class="monto" id="resumen-utilidad">$0</p>
            <p class="porcentaje" id="porcentaje-utilidad">(0% del Ingreso)</p>
        </div>
    </div>

    <div class="detalle-diario">
        <h3>Desglose Diario</h3>
        <table class="tabla-detalle">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Ingresos</th>
                    <th>Egresos</th>
                    <th>Ganancia Neta del Día</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla-detalle">
                </tbody>
        </table>
    </div>
</div>
</body>
</html>