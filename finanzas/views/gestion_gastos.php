
<?php
// Incluir la conexión a la base de datos
include __DIR__ . '/../models/conexion.php';

// --- LÓGICA DEL SERVIDOR ---

// 1. MANEJO DE ELIMINACIÓN DE GASTOS
if (isset($_GET['eliminar'])) {
    $id_a_eliminar = intval($_GET['eliminar']);
    if ($id_a_eliminar > 0) {
        $stmt = $conn->prepare("DELETE FROM gastos WHERE id = ?");
        $stmt->bind_param("i", $id_a_eliminar);
        $stmt->execute();
        $stmt->close();
        // Redirigir para limpiar la URL y evitar re-eliminación al recargar
        header("Location: ?vista=finanzas/views/gestion_gastos.php");
        exit();
    }
}

// 2. OBTENER GASTOS DEL MES ACTUAL PARA MOSTRARLOS
$primer_dia_mes = date('Y-m-01');
$ultimo_dia_mes = date('Y-m-t');
$sql_gastos_mes = "SELECT * FROM gastos WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC";
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
        .gestion-gastos h2, .gestion-gastos h3 { text-align: center; color: #fff; }
        .formulario-gastos { max-width: 600px; margin: 0 auto 40px; padding: 20px; border: 1px solid #dee2e6; border-radius: 8px; background-color: #f8f9fa; }
        .form-grupo { margin-bottom: 15px; }
        .form-grupo label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-grupo input, .form-grupo select { width: 100%; padding: 8px; border-radius: 5px; border: 1px solid #ccc; box-sizing: border-box; }
        .form-grupo button { width: 100%; padding: 10px; background-color: #28a745; color: white; font-weight: bold; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .tabla-gastos { width: 100%; border-collapse: collapse; }
        .tabla-gastos th, .tabla-gastos td { border: 1px solid #dee2e6; padding: 12px; }
        .tabla-gastos th { background-color: #f8f9fa; }
        .tabla-gastos td { text-align: left; color: #fff }
        .columna-monto { text-align: right; }
        .columna-acciones { text-align: center; }
        .btn-eliminar { color: #dc3545; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="gestion-gastos">
    <h2>Gestión de Costos y Gastos Fijos</h2>

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
                <label for="tipo">Tipo de Gasto</label>
                <select id="tipo" name="tipo" required>
                    <option value="fijo">Fijo (Ej: Arriendo, Salarios)</option>
                    <option value="variable">Variable (Ej: Comisiones)</option>
                    <option value="secundario">Secundario (Ej: Papelería, Cafetería)</option>
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
        <h3>Gastos Registrados este Mes</h3>
        <table class="tabla-gastos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Tipo</th>
                    <th class="columna-monto">Monto</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($resultado_gastos->num_rows > 0): ?>
                    <?php while($gasto = $resultado_gastos->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($gasto['fecha'])) ?></td>
                            <td><?= htmlspecialchars($gasto['descripcion']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($gasto['tipo'])) ?></td>
                            <td class="columna-monto">$<?= number_format($gasto['monto'], 0, ',', '.') ?></td>
                            <td class="columna-acciones">
                                <a href="?vista=finanzas/views/gestion_gastos.php&eliminar=<?= $gasto['id'] ?>" class="btn-eliminar" onclick="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">
                                    Eliminar
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No hay gastos registrados este mes.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Selecciona el formulario por su nuevo ID
    const formGastos = document.getElementById('form-gastos');

    // Añade un "escuchador" para el evento 'submit'
    if (formGastos) {
        formGastos.addEventListener('submit', function(e) {
            
            // Previene el envío tradicional del formulario que recarga la página
            e.preventDefault();

            // Crea un objeto con los datos del formulario
            const formData = new FormData(formGastos);

            // Envía los datos usando fetch al nuevo archivo
            fetch('finanzas/views/guardar_gasto.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // Espera una respuesta de texto ("ok" o un error)
            .then(data => {
                // Procesa la respuesta
                if (data.trim() === 'ok') {
                    // Si la respuesta es "ok", recarga el contenido para ver el nuevo gasto
                    alert('✅ Gasto guardado exitosamente.');
                    cargarContenido('finanzas/views/gestion_gastos.php');
                } else {
                    // Si hubo un error, muéstralo en una alerta
                    alert('❌ Error: ' + data);
                }
            })
            .catch(error => {
                // Maneja errores de red
                console.error('Error de red:', error);
                alert('❌ Hubo un error de conexión. Inténtalo de nuevo.');
            });
        });
    }
</script>

</body>
</html>
```