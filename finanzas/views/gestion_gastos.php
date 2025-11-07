<!-- <?php
// Incluir la conexión a la base de datos
include __DIR__ . '/../models/conexion.php';

// --- NUEVO: Obtener sedes para el formulario
$sedes_result_form = $conn->query("SELECT id, nombre FROM sedes ORDER BY nombre");

// OBTENER GASTOS DEL MES ACTUAL PARA MOSTRARLOS
$primer_dia_mes = date('Y-m-01');
$ultimo_dia_mes = date('Y-m-t');
// Se une con la tabla sedes para obtener el nombre de la sede
$sql_gastos_mes = "
    SELECT g.*, s.nombre AS nombre_sede 
    FROM gastos g
    LEFT JOIN sedes s ON g.id_sede = s.id
    WHERE g.fecha BETWEEN ? AND ? 
    ORDER BY g.fecha DESC
";
$stmt_gastos = $conn->prepare($sql_gastos_mes);
$stmt_gastos->bind_param("ss", $primer_dia_mes, $ultimo_dia_mes);
$stmt_gastos->execute();
$resultado_gastos = $stmt_gastos->get_result();
$stmt_gastos->close();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Gastos</title>
    <style>
        .gestion-gastos { font-family: 'Segoe UI', sans-serif; padding: 20px; }
        .gestion-gastos h2, .gestion-gastos h3 { text-align: center; color: #333; }
        .formulario-gastos { max-width: 600px; margin: 0 auto 40px; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #f8f9fa; }
        .form-grupo { margin-bottom: 15px; }
        .form-grupo label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-grupo input, .form-grupo select { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        .form-grupo button { width: 100%; padding: 10px; background-color: #28a745; color: white; font-weight: bold; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .tabla-gastos { width: 100%; border-collapse: collapse; }
        .tabla-gastos th, .tabla-gastos td { border: 1px solid #dee2e6; padding: 12px; }
        .tabla-gastos th { background-color: #f8f9fa; }
        .tabla-gastos td { text-align: left; }
        .columna-monto { text-align: right; }
        .columna-acciones { text-align: center; }
        .btn-eliminar { color: #dc3545; text-decoration: none; font-weight: bold; cursor: pointer; }
    </style>
</head>
<body>

<div class="gestion-gastos">
    <h2 style="color:#333;">Gestión de Costos y Gastos Fijos</h2>

    <div class="formulario-gastos">
        <h3>Registrar Nuevo Gasto</h3>
        <form id="form-gastos" method="POST" action="finanzas/views/guardar_gasto.php">
            <div class="form-grupo">
                <label for="descripcion">Descripción del Gasto</label>
                <input type="text" id="descripcion" name="descripcion" placeholder="Ej: Arriendo de oficina" required>
            </div>
            <div class="form-grupo">
                <label for="monto">Monto</label>
                <input type="number" id="monto" name="monto" placeholder="Ej: 1500000" step="0.01" required>
            </div>
            <div class="form-grupo">
                <label for="id_sede">Asignar Gasto a Sede</label>
                <select id="id_sede" name="id_sede" required>
                    <option value="" disabled selected>Selecciona una sede</option>
                    <?php mysqli_data_seek($sedes_result_form, 0); // Reiniciar puntero para reusar la consulta ?>
                    <?php while($sede = $sedes_result_form->fetch_assoc()): ?>
                        <option value="<?= $sede['id'] ?>"><?= htmlspecialchars($sede['nombre']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-grupo">
                <label for="tipo">Tipo de Gasto</label>
                <select id="tipo" name="tipo" required>
                    <option value="fijo">Fijo (Ej: Arriendo, Salarios)</option>
                    <option value="variable">Variable (Ej: Comisiones)</option>
                    <option value="secundario">Secundario (Ej: Papelería, Cafetería)</option>
                </select>
            </div>
            <div class="form-grupo">
                <label for="metodo_pago">Método de Pago</label>
                <select id="metodo_pago" name="metodo_pago" required>
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="form-grupo">
                <label for="fecha">Fecha del Gasto</label>
                <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-grupo">
                <button type="submit">Guardar Gasto</button>
            </div>
        </form>
    </div>

    <div class="lista-gastos">
        <h3 style="color:#333;">Gastos Registrados este Mes</h3>
        <table class="tabla-gastos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Sede</th> <th>Descripción</th>
                    <th>Tipo</th>
                    <th>Método Pago</th>
                    <th class="columna-monto">Monto</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_gastos->num_rows > 0): ?>
                    <?php while($gasto = $resultado_gastos->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($gasto['fecha'])) ?></td>
                            <td><?= htmlspecialchars($gasto['nombre_sede'] ?? 'N/A') ?></td> <td><?= htmlspecialchars($gasto['descripcion']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($gasto['tipo'])) ?></td>
                            <td><?= htmlspecialchars(ucfirst($gasto['metodo_pago'])) ?></td>
                            <td class="columna-monto">$<?= number_format($gasto['monto'], 0, ',', '.') ?></td>
                            <td class="columna-acciones">
                                <a href="#" class="btn-eliminar" onclick="eliminarGasto(<?= $gasto['id'] ?>); return false;">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No hay gastos registrados este mes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // ... tu script existente para guardar y eliminar ...
</script>

</body>
</html> -->