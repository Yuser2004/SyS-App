<?php
// Incluir la conexión a la base de datos
include __DIR__ . '/../models/conexion.php';

// Obtener el ID del recibo desde la URL
$recibo_id = intval($_GET['id'] ?? 0);
$recibo = null;
$egresos = [];
$total_egresos = 0;
$utilidad_recibo = 0;

if ($recibo_id > 0) {
    // 1. Consulta para obtener la información principal del recibo y cliente
    $stmt_recibo = $conn->prepare("
        SELECT 
            r.id, r.concepto_servicio, r.valor_servicio, r.fecha_tramite, r.estado, r.metodo_pago, r.descripcion_servicio,
            c.nombre_completo, c.documento, c.telefono, c.ciudad, c.direccion, c.observaciones,
            v.placa,
            a.nombre AS asesor_nombre
        FROM recibos r
        LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
        LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
        WHERE r.id = ?
    ");
    $stmt_recibo->bind_param("i", $recibo_id);
    $stmt_recibo->execute();
    $resultado = $stmt_recibo->get_result();
    if ($resultado->num_rows > 0) {
        $recibo = $resultado->fetch_assoc();
    }
    $stmt_recibo->close();

    // 2. Consulta para obtener todos los egresos asociados a este recibo
    $stmt_egresos = $conn->prepare("SELECT * FROM egresos WHERE recibo_id = ?");
    $stmt_egresos->bind_param("i", $recibo_id);
    $stmt_egresos->execute();
    $egresos = $stmt_egresos->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_egresos->close();

    // 3. Cálculos financieros para este recibo
    foreach ($egresos as $egreso) {
        $total_egresos += $egreso['monto'];
    }
    if ($recibo) {
        $utilidad_recibo = $recibo['valor_servicio'] - $total_egresos;
    }
}

if (!$recibo) {
    die("<h2>Error: Recibo no encontrado.</h2>");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle del Recibo #<?= htmlspecialchars($recibo['id']) ?></title>
    <style>
        .detalle-container { font-family: 'Segoe UI', sans-serif; padding: 20px; max-width: 1200px; margin: auto; }
        .detalle-header { text-align: center; margin-bottom: 20px; }
        .detalle-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card-info { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; }
        .card-info h3 { margin-top: 0; color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .card-info p { margin: 10px 0; line-height: 1.6; }
        .card-info strong { color: #343a40; }
        .card-finanzas { grid-column: 1 / -1; /* Ocupa todo el ancho */ }
        .finanzas-resumen { display: flex; justify-content: space-around; text-align: center; margin: 20px 0; }
        .resumen-numero h4 { margin: 0; color: #6c757d; }
        .resumen-numero .numero { font-size: 3em; font-weight: bold; color: #343a40; }
        .lista-egresos { list-style: none; padding: 0; }
        .lista-egresos li { display: flex; justify-content: space-between; align-items: center; padding: 10px; border-bottom: 1px solid #e9ecef; }
        .lista-egresos li:last-child { border-bottom: none; }
        .btn-comprobante {
            display: inline-block;
            background-color: #007bff; /* Color azul principal */
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: bold;
            border: none;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out, transform 0.1s ease;
        }

        .btn-comprobante:hover {
            background-color: #0056b3; /* Un azul más oscuro al pasar el mouse */
            transform: translateY(-1px); /* Efecto sutil de levantamiento */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* ======================================================= */
        /* NUEVOS ESTILOS PARA LA LISTA DE EGRESOS CON DIVS        */
        /* ======================================================= */

        /* Contenedor principal de la lista de egresos */
        .lista-egresos {
            margin-top: 20px;
            border: 1px solid #e9ecef;
            border-radius: 5px;
        }

        /* Cada fila de egreso (reemplaza a <li>) */
        .item-egreso {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
        }

        /* Quita el borde del último elemento para un look más limpio */
        .item-egreso:last-child {
            border-bottom: none;
        }

        /* Contenedor para descripción y monto */
        .detalle-egreso-info {
            display: flex;
            align-items: center;
            gap: 10px; /* Crea la separación */
        }

        /* Estilo para la descripción */
        .detalle-egreso-descripcion {
            font-weight: bold;
            color: #343a40;
        }

        /* El estilo para el badge rojo no cambia */
        .monto-egreso-badge {
            background-color: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            white-space: nowrap; /* Evita que el monto se parta en dos líneas */
        }
        .utilidad-final { text-align: right; font-size: 1.5em; font-weight: bold; margin-top: 20px; }
        .utilidad-final .positivo { color: #28a745; }
        .utilidad-final .negativo { color: #dc3545; }
        .btn-volver { display: inline-block; margin-top: 30px; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
<div class="detalle-container">
    <div class="detalle-header">
        <h1>Detalle del Recibo #<?= htmlspecialchars($recibo['id']) ?></h1>
    </div>

    <div class="detalle-grid">
        <div class="card-info">
            <h3>Información del Cliente</h3>
            <p><strong>Nombre:</strong> <?= htmlspecialchars($recibo['nombre_completo']) ?></p>
            <p><strong>Documento:</strong> <?= htmlspecialchars($recibo['documento']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($recibo['telefono']) ?></p>
            <p><strong>Dirección:</strong> <?= htmlspecialchars($recibo['direccion']) ?>, <?= htmlspecialchars($recibo['ciudad']) ?></p>
            <p><strong>Observaciones:</strong> <?= htmlspecialchars($recibo['observaciones'] ?: 'N/A') ?></p>
        </div>

        <div class="card-info">
            <h3>Información del Recibo</h3>
            <p><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($recibo['fecha_tramite'])) ?></p>
            <p><strong>Vehículo (Placa):</strong> <?= htmlspecialchars($recibo['placa']) ?></p>
            <p><strong>Asesor:</strong> <?= htmlspecialchars($recibo['asesor_nombre']) ?></p>
            <p><strong>Concepto:</strong> <?= htmlspecialchars($recibo['concepto_servicio']) ?></p>
            <p><strong>Valor del Servicio:</strong> <strong style="color: #28a745;">$<?= number_format($recibo['valor_servicio'], 0, ',', '.') ?></strong></p>
            <p><strong>Método de Pago:</strong> <?= htmlspecialchars(ucfirst($recibo['metodo_pago'])) ?></p>
            <p><strong>Estado:</strong> <?= htmlspecialchars(ucfirst($recibo['estado'])) ?></p>
        </div>

        <div class="card-info card-finanzas">
            <h3>Resumen Financiero del Recibo</h3>
            <div class="finanzas-resumen">
                <div class="resumen-numero">
                    <h4>Egresos Asociados</h4>
                    <p class="numero"><?= count($egresos) ?></p>
                </div>
            </div>

        <div class="lista-egresos">
            <?php if (count($egresos) > 0): ?>
                <?php foreach ($egresos as $egreso): ?>
                    <div class="item-egreso">
                        <span class="detalle-egreso-info">
                            <span class="detalle-egreso-descripcion"><?= htmlspecialchars($egreso['descripcion']) ?>:</span>
                            <span class="monto-egreso-badge">-$<?= number_format($egreso['monto'], 0, ',', '.') ?></span>
                        </span>
                        <?php if (!empty($egreso['comprobante_pdf'])): ?>
                            <a href="<?= htmlspecialchars($egreso['comprobante_pdf']) ?>" target="_blank" class="btn-comprobante">Ver<br>Comprobante</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="item-egreso">No hay egresos registrados para este recibo.</div>
            <?php endif; ?>
        </div>

            <div class="utilidad-final">
                Ganancia Neta del Recibo: 
                <span class="<?= $utilidad_recibo >= 0 ? 'positivo' : 'negativo' ?>">
                    $<?= number_format($utilidad_recibo, 0, ',', '.') ?>
                </span>
            </div>
        </div>
    </div>

    <a href="#" class="btn-volver" onclick="cargarContenido('recibos/views/lista.php'); return false;">← Volver a la Lista</a>
</div>
</body>
</html>