<?php
include __DIR__ . '/../models/conexion.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo "<p>Recibo no encontrado.</p>";
    exit;
}

$recibo = $conn->query("SELECT * FROM recibos WHERE id = $id")->fetch_assoc();

if (!$recibo) {
    echo "<p>No se encontró el recibo.</p>";
    exit;
}

$clientes = $conn->query("SELECT id_cliente, nombre_completo FROM clientes");
$vehiculos = $conn->query("SELECT id_vehiculo, placa FROM vehiculo");
$asesores = $conn->query("SELECT id_asesor, nombre FROM asesor");
?>

<link rel="stylesheet" href="cliente/public/css/estilos_form.css">

<div class="login-form">
    <h1>Editar Recibo</h1>

    <form id="form-editar-recibo">
        <input type="hidden" name="id" value="<?= $recibo['id'] ?>">

        <div class="form-input-material">
            <label for="id_cliente">Cliente:</label>
            <select id="id_cliente" name="id_cliente" required>
                <option value="">Seleccione</option>
                <?php while ($c = $clientes->fetch_assoc()): ?>
                    <option value="<?= $c['id_cliente'] ?>" <?= $recibo['id_cliente'] == $c['id_cliente'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre_completo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-input-material">
            <label for="id_vehiculo">Vehículo:</label>
            <select id="id_vehiculo" name="id_vehiculo" required>
                <option value="">Seleccione</option>
                <?php while ($v = $vehiculos->fetch_assoc()): ?>
                    <option value="<?= $v['id_vehiculo'] ?>" <?= $recibo['id_vehiculo'] == $v['id_vehiculo'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['placa']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-input-material">
            <label for="id_asesor">Asesor:</label>
            <select id="id_asesor" name="id_asesor" required>
                <option value="">Seleccione</option>
                <?php while ($a = $asesores->fetch_assoc()): ?>
                    <option value="<?= $a['id_asesor'] ?>" <?= $recibo['id_asesor'] == $a['id_asesor'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($a['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-input-material">
            <input type="number" name="valor_servicio" id="valor_servicio" value="<?= $recibo['valor_servicio'] ?>" required placeholder=" ">
            <label for="valor_servicio">Valor del Servicio</label>
        </div>

        <div class="form-input-material">
            <label for="estado">Estado:</label>
            <select name="estado" id="estado" required>
                <option value="pendiente" <?= $recibo['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="pagado" <?= $recibo['estado'] === 'pagado' ? 'selected' : '' ?>>Pagado</option>
            </select>
        </div>

        <div class="form-input-material">
            <label for="metodo_pago">Método de Pago:</label>
            <select name="metodo_pago" id="metodo_pago" required>
                <option value="efectivo" <?= $recibo['metodo_pago'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                <option value="transferencia" <?= $recibo['metodo_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                <option value="otro" <?= $recibo['metodo_pago'] === 'otro' ? 'selected' : '' ?>>Otro</option>
            </select>
        </div>

        <div class="form-input-material">
            <input type="text" name="concepto" id="concepto" value="<?= htmlspecialchars($recibo['concepto_servicio']) ?>" required placeholder=" ">
            <label for="concepto">Concepto del Servicio</label>
        </div>

        <button type="submit" class="btn">Actualizar</button>
    </form>

    <button class="btn" onclick="cargarContenido('recibos/views/lista.php')">← Volver</button>

    <script>
    document.getElementById("form-editar-recibo").addEventListener("submit", async function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        try {
            const res = await fetch("recibos/actualizar.php", {
                method: "POST",
                body: formData
            });

            const text = await res.text();

            if (text.trim() === "ok") {
                cargarContenido('recibos/views/lista.php');
            } else {
                alert("Error: " + text);
            }
        } catch (err) {
            alert("Error de red: " + err);
        }
    });
    </script>
</div>
