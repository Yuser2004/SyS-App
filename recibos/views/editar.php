<?php
include __DIR__ . '/../models/conexion.php';

$id_recibo = intval($_GET['id'] ?? 0);
if ($id_recibo <= 0) {
    echo "ID inválido.";
    exit;
}

// Obtener datos del recibo
$sql = "SELECT id, estado, descripcion_servicio FROM recibos WHERE id = $id_recibo";
$result = $conn->query($sql);
$recibo = $result->fetch_assoc();

if (!$recibo) {
    echo "Recibo no encontrado.";
    exit;
}

$conn->close();
?>

<link rel="stylesheet" href="recibos/public/estilos_form.css">

<div class="login-form">
    <h1>Editar Recibo</h1>

    <form id="form-editar-recibo">
        <input type="hidden" name="id" value="<?= $id_recibo ?>">

        <!-- Estado -->
        <div class="form-input-material">
            <select name="estado" id="estado" required>
                <option value="" disabled hidden>Selecciona un estado</option>
                <option value="pendiente" <?= $recibo['estado'] === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="completado" <?= $recibo['estado'] === 'completado' ? 'selected' : '' ?>>Completado</option>
                <option value="cancelado" <?= $recibo['estado'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
            <label for="estado">Estado</label>
        </div>

        <!-- Descripción -->
        <div class="form-input-material">
            <textarea name="descripcion_servicio" id="descripcion_servicio" rows="4" placeholder=" "><?= htmlspecialchars($recibo['descripcion_servicio']) ?></textarea>
            <label for="descripcion_servicio">Descripción del Servicio</label>
        </div>

        <button type="submit" class="btn">Actualizar Recibo</button>
    </form>

    <button class="btn" onclick="cargarContenido('recibos/views/lista.php')">← Volver</button>

    <script>
        document.getElementById("form-editar-recibo").addEventListener("submit", async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const resp = await fetch("recibos/actualizar.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            console.log("Respuesta:", texto);

            if (texto.trim() === "ok") {
                cargarContenido('recibos/views/lista.php');
            } else {
                alert("Error al actualizar: " + texto);
            }
        });

        // Activar clase visual input-activo
        document.addEventListener("DOMContentLoaded", () => {
            const inputs = document.querySelectorAll(".form-input-material textarea, .form-input-material select");
            inputs.forEach(input => {
                if (input.value.trim() !== "") input.classList.add("input-activo");

                input.addEventListener("input", () => {
                    input.classList.toggle("input-activo", input.value.trim() !== "");
                });

                input.addEventListener("change", () => {
                    input.classList.toggle("input-activo", input.value.trim() !== "");
                });
            });
        });
    </script>
</div>
