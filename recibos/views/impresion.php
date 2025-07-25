<?php
// =======================================================
//  1. CÓDIGO PHP PARA OBTENER LOS DATOS DEL RECIBO
// =======================================================

// Incluir la conexión y el helper
include __DIR__ . '/../models/conexion.php'; 
include __DIR__ . '/../../helpers/NumeroALetras.php';

// Obtener el ID del recibo desde la URL
$recibo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$recibo = null;

if ($recibo_id > 0) {
    // Consulta para obtener todos los datos necesarios
    $stmt = $conn->prepare("
        SELECT 
            r.id, r.fecha_tramite, r.valor_servicio, r.concepto_servicio, r.metodo_pago, r.detalle_pago,
            c.nombre_completo AS cliente_nombre, 
            c.documento AS cliente_documento, 
            c.telefono AS cliente_celular, 
            c.direccion AS cliente_direccion, 
            c.ciudad AS cliente_ciudad,
            a.nombre AS asesor_nombre,      -- Nombre del Asesor
            s.direccion AS sede_direccion  -- Dirección de la Sede
        FROM recibos r
        LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor -- Unimos con Asesor
        LEFT JOIN sedes s ON a.id_sede = s.id           -- Unimos con Sedes
        WHERE r.id = ?
    ");
    $stmt->bind_param("i", $recibo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $recibo = $result->fetch_assoc();
    }
    $stmt->close();
}

// Si no se encuentra el recibo, mostrar un mensaje y salir
if (!$recibo) {
    die("Error: No se encontró un recibo con el ID proporcionado.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo N.º <?= htmlspecialchars($recibo['id']) ?></title>
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
        }
        .recibo-container {
            border: 2px solid #4CAF50;
        }

        /* --- Estilos para el Encabezado --- */
        .recibo-header {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 2px solid #4CAF50;
        }
        .header-col {
            flex: 1;
            padding: 0 10px;
        }
        .header-col.col-izquierda {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
        }
        .col-izquierda .logo {
            max-width: 150px;
            margin-bottom: 0;
        }
        .titulo-empresa {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #0077be;
            text-shadow:
                -1px -1px 0 #2E8B57,  
                 1px -1px 0 #2E8B57,
                -1px  1px 0 #2E8B57,
                 1px  1px 0 #2E8B57;
        }
        .col-centro {
            text-align: center;
            font-size: 11px;
            line-height: 1.3;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
        }
        .col-centro p { margin: 2px 0; }
        .nombre-cursiva { font-family: 'Brush Script MT', cursive; font-size: 18px; }
        .col-derecha { text-align: center; }
        .col-derecha h2 { margin: 0 0 10px 0; color: red; font-size: 20px; }
        .recibo-info { font-weight: bold; }

        /* --- Estilos para el Cuerpo del Recibo --- */
        .recibo-body-container {
            padding: 10px;
        }
        .recibo-body {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        .recibo-body td {
            border: 1px solid #4CAF50; /* Líneas verdes */
            padding: 4px 8px;
            vertical-align: top;
        }
        .label {
            display: block;
            font-size: 10px;
            color: #555;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .data { font-weight: bold; }
        .valor-cell { text-align: right; }
        .data-valor { font-weight: bold; font-size: 16px; }
        .forma-pago div { display: flex; gap: 20px; margin-top: 5px; }
        .fila-final td { height: 40px; }
/* --- ESTILOS MEJORADOS PARA LA IMPRESIÓN EN MEDIA HOJA --- */

@page {
    size: letter portrait; /* Tamaño carta, en orientación vertical */
    margin: 0;
}

/* --- ESTILOS SIMPLIFICADOS PARA LA IMPRESIÓN --- */
@media print {
    .acciones-container {
        display: none;
    }

    body {
        margin: 0;
        padding: 0;
    }
}
    </style>
</head>
<body>

<div class="recibo-container">
    <div class="recibo-header">
        <div class="header-col col-izquierda">
            <img src="/SyS-app/SySlogo.png" alt="Logo S&S" class="logo">
            <h1 class="titulo-empresa">Seguros & Servicios</h1>
        </div>
        <div class="header-col col-centro">
            <p><strong>NIT. 30347736-1</strong></p>
            <p>CELS. 314 7015664</p>
            <p><?= htmlspecialchars($recibo['sede_direccion']) ?></p>
            <p>serviciosysegurosla10@hotmail.com</p>
            <hr style="border-color: #eee; margin: 5px 0;">
            <p>SOAT DE CARROS Y MOTOS</p>
            <p>Trámites de Tránsito y Transporte</p>
            <p class="nombre-cursiva">Alba Nidia Pinzón P.</p>
        </div>
        <div class="header-col col-derecha">
            <h2>RECIBO DE CAJA</h2>
            <p class="recibo-info">No. <?= htmlspecialchars($recibo['id']) ?></p>
            <p class="recibo-info">Fecha: <?= date('d/m/Y', strtotime($recibo['fecha_tramite'])) ?></p>
        </div>
    </div>

    <div class="recibo-body-container">
        <table class="recibo-body">
            <tr>
                <td colspan="3">
                    <span class="label">Ciudad y Fecha:</span>
                    <span class="data"><?= htmlspecialchars($recibo['cliente_ciudad'] ?? 'La Dorada') ?>, <?= date('d/m/Y', strtotime($recibo['fecha_tramite'])) ?></span>
                </td>
                <td class="valor-cell">
                    <span class="label">Valor $</span>
                    <span class="data-valor">$<?= number_format($recibo['valor_servicio'], 0, ',', '.') ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="label">Recibido de:</span>
                    <span class="data"><?= htmlspecialchars($recibo['cliente_nombre']) ?></span>
                </td>
                <td>
                    <span class="label">C.C.</span>
                    <span class="data"><?= htmlspecialchars($recibo['cliente_documento']) ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <span class="label">Dirección:</span>
                    <span class="data"><?= htmlspecialchars($recibo['cliente_direccion']) ?></span>
                </td>
                <td>
                    <span class="label">Teléfono:</span>
                    <span class="data"><?= htmlspecialchars($recibo['cliente_celular']) ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">La Suma de:</span>
                    <span class="data"><?= NumeroALetras::convertir($recibo['valor_servicio']) ?> MCTE.</span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">Por Concepto de:</span>
                    <span class="data"><?= htmlspecialchars($recibo['concepto_servicio']) ?></span>
                </td>
            </tr>
            <tr>
                <td colspan="4">
                    <span class="label">Nombre de Cuenta:</span>
                    <?php if ($recibo['metodo_pago'] == 'transferencia' && !empty($recibo['detalle_pago'])): ?>
                        <span class="data"><?= htmlspecialchars($recibo['detalle_pago']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td colspan="4" class="forma-pago">
                    <span class="label">Forma de Pago:</span>
                    <div>
                        <input type="checkbox" <?= ($recibo['metodo_pago'] == 'efectivo') ? 'checked' : '' ?> disabled> EFECTIVO
                        <input type="checkbox" <?= ($recibo['metodo_pago'] == 'transferencia') ? 'checked' : '' ?> disabled> TRANSACCION
                        <input type="checkbox" <?= ($recibo['metodo_pago'] == 'tarjeta') ? 'checked' : '' ?> disabled> T. CRÉDITO
                    </div>
                </td>
            </tr>
            <tr class="fila-final">
                <td colspan="3">
                    <span class="label">Observaciones:</span>
                </td>
                <td>
                    <span class="label">Firma y Sello:</span>
                </td>
            </tr>
        </table>
    </div>
</div>
<div class="acciones-container">
    <button class="btn-accion" onclick="imprimirRecibo()">Imprimir</button>
    <button class="btn-accion" onclick="guardarComoPDF()">Guardar como PDF</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
    // Función para imprimir solo el recibo
    function imprimirRecibo() {
        window.print();
    }

    // Función para guardar el recibo como PDF
    function guardarComoPDF() {
        // Oculta los botones para que no salgan en la captura
        document.querySelector('.acciones-container').style.display = 'none';

        const { jsPDF } = window.jspdf;
        const reciboElemento = document.querySelector('.recibo-container');

        html2canvas(reciboElemento, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF({
                orientation: 'landscape', // horizontal
                unit: 'pt',
                format: [canvas.width, canvas.height]
            });
            
            pdf.addImage(imgData, 'PNG', 0, 0, canvas.width, canvas.height);
            pdf.save(`recibo-<?= htmlspecialchars($recibo['id']) ?>.pdf`);
            
            // Vuelve a mostrar los botones después de generar el PDF
            document.querySelector('.acciones-container').style.display = 'block';
        });
    }
</script>

</body>
</html>