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
            r.id, r.fecha_tramite, r.valor_servicio, r.concepto_servicio, r.metodo_pago,
            c.nombre_completo AS cliente_nombre, c.documento AS cliente_documento, c.telefono AS cliente_celular,
            v.placa,
            a.nombre AS asesor_nombre, a.documento AS asesor_documento
        FROM recibos r
        LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
        LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
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
    :root {
      --morado-principal: #5b3e91;
      --verde-limon: #d0f145;
      --azul-suave: #e6f2fb;
      --borde-suave: #dce9f3;
      --gris-texto: #333;
    }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 800px; margin: 20px auto; padding: 0; background: #f9f9f9; border: 1px solid var(--borde-suave); box-shadow: 0 0 15px rgba(0, 0, 0, 0.05); }
    .header { display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; background: white; border-bottom: 3px solid #5b3e91; }
    .logo-titulo { font-family: 'Alatsi', sans-serif; font-size: 36px; color: #5b3e91; text-shadow: 2px 2px #d0f145; font-weight: bold; }
    .info-izq { flex: 1; }
    .info-centro { flex: 2; text-align: center; }
    .info-der { flex: 1; text-align: right; font-size: 13px; }
    .contacto { font-size: 13px; line-height: 1.4; }
    .nombre-firma { font-family: 'Great Vibes', cursive; font-size: 18px; font-weight: bold; color: #2c2c2c; }
    .subfirma { font-style: italic; font-size: 13px; }
    .contenido { padding: 30px; }
    h2 { text-align: center; color: var(--morado-principal); margin-top: 0; font-size: 1.5em; }
    .seccion { border-radius: 8px; padding: 15px 20px; margin-top: 25px; border: 1px solid var(--borde-suave); }
    .info-cliente { background-color: #f5f7ff; }
    .detalle-servicio { background-color: #f0f8ff; border-top: 3px solid var(--morado-principal); border-bottom: 3px solid var(--morado-principal); }
    .footer { background-color: #eef7fc; text-align: center; color: var(--gris-texto); font-size: 0.9em; margin-top: 40px; padding: 10px; border-top: 1px solid var(--borde-suave); }
    p { margin: 8px 0; font-size: 1rem; }
    strong { color: var(--morado-principal); }
    .resaltado { font-weight: bold; color: var(--morado-principal); }
    .titulo-seccion { margin-bottom: 10px; font-size: 1.2rem; color: var(--morado-principal); border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    .fila-doble { display: flex; gap: 20px; }
    .columna { flex: 1; }
  </style>
</head>
<body>

  <div class="header">
    <div class="info-izq contacto"><div>NIT. 30347736-1</div></div>
    <div class="info-centro">
      <div class="logo-titulo">Seguros & Servicios <span style="font-size: 16px;">S&amp;S</span></div>
      <div class="contacto">
        CELS. CEL 314 7015664 &nbsp;&nbsp; TEL.839 0433<br>
        CALLE 10 No.5-04 NIVEL 2 LA DORADA, CALDAS<br>
        <span style="text-transform: lowercase;">serviciosysegurosla10@hotmail.com</span>
      </div>
    </div>
    <div class="info-der">
      <div><strong>SOAT DE CARROS Y MOTOS</strong></div>
      <div>Trámites de Tránsito<br>y Transporte en todo el país</div>
      <div class="nombre-firma">Alba Nidia Pinzón P.</div>
      <div class="subfirma">Rapidez y Responsabilidad</div>
    </div>
  </div>

  <div class="contenido">
    <h2>Recibo N.º <span class="resaltado"><?= htmlspecialchars($recibo['id']) ?></span></h2>

    <div class="seccion fila-doble">
      <div class="columna info-cliente">
        <div class="titulo-seccion">Datos del Cliente</div>
        <p><strong>Nombre:</strong> <span><?= htmlspecialchars($recibo['cliente_nombre']) ?></span></p>
        <p><strong>Identificación:</strong> <span><?= htmlspecialchars($recibo['cliente_documento']) ?></span></p>
        <p><strong>Celular:</strong> <span><?= htmlspecialchars($recibo['cliente_celular']) ?></span></p>
      </div>

      <div class="columna detalle-servicio">
        <div class="titulo-seccion">Detalle del Servicio</div>
        <p><strong>Placa:</strong> <span><?= htmlspecialchars($recibo['placa']) ?></span></p>
        <p><strong>Concepto:</strong> <span><?= htmlspecialchars($recibo['concepto_servicio']) ?></span></p>
        <p><strong>Valor:</strong> $<span><?= number_format($recibo['valor_servicio'], 0, ',', '.') ?></span></p>
        <p><strong>Valor en letras:</strong> <span><?= NumeroALetras::convertir($recibo['valor_servicio']) ?></span></p>
        <p><strong>Método de Pago:</strong> <span><?= htmlspecialchars(ucfirst($recibo['metodo_pago'])) ?></span></p>
      </div>
    </div>

    <div class="seccion info-cliente">
      <div class="titulo-seccion">Datos del Asesor</div>
      <p><strong>Nombre:</strong> <span><?= htmlspecialchars($recibo['asesor_nombre']) ?></span></p>
      <p><strong>Identificación:</strong> <span><?= htmlspecialchars($recibo['asesor_documento']) ?></span></p>
    </div>

    <div class="seccion footer">
      <p>Gracias por preferirnos</p>
      <p>Fecha del trámite: <span><?= date('d/m/Y', strtotime($recibo['fecha_tramite'])) ?></span></p>
    </div>
  </div>

</body>
</html>