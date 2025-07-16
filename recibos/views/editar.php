<?php
include __DIR__ . '/../models/conexion.php';

$id_recibo = intval($_GET['id'] ?? 0);
if ($id_recibo <= 0) {
    echo "ID inválido.";
    exit;
}

// Obtener datos del recibo
$sql = "SELECT r.*, c.nombre_completo, c.id_cliente, v.placa, v.id_vehiculo, a.nombre AS nombre_asesor, a.id_asesor
        FROM recibos r
        LEFT JOIN clientes c ON r.id_cliente = c.id_cliente
        LEFT JOIN vehiculo v ON r.id_vehiculo = v.id_vehiculo
        LEFT JOIN asesor a ON r.id_asesor = a.id_asesor
        WHERE r.id = $id_recibo";
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

        <!-- Cliente -->
        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_cliente" placeholder=" " autocomplete="off"
                value="<?= htmlspecialchars($recibo['nombre_completo']) ?>">
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $recibo['id_cliente'] ?>" required>
            <div id="resultados_cliente" class="resultado-autocompletar"></div>
            <label for="buscador_cliente">Cliente</label>
        </div>

        <!-- Vehículo -->
        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_vehiculo" placeholder=" " autocomplete="off"
                value="<?= htmlspecialchars($recibo['placa']) ?>">
            <input type="hidden" id="id_vehiculo" name="id_vehiculo" value="<?= $recibo['id_vehiculo'] ?>" required>
            <div id="resultados_vehiculo" class="resultado-autocompletar"></div>
            <label for="buscador_vehiculo">Vehículo</label>
        </div>

        <!-- Asesor -->
        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_asesor" placeholder=" " autocomplete="off"
                value="<?= htmlspecialchars($recibo['nombre_asesor']) ?>">
            <input type="hidden" id="id_asesor" name="id_asesor" value="<?= $recibo['id_asesor'] ?>">
            <div id="resultados_asesor" class="resultado-autocompletar"></div>
            <label for="buscador_asesor">Asesor</label>
        </div>

        <!-- Concepto -->
        <div class="form-input-material">
            <input type="text" id="concepto_servicio" name="concepto_servicio" placeholder=" "
                value="<?= htmlspecialchars($recibo['concepto_servicio']) ?>" required>
            <label for="concepto_servicio">Concepto del Servicio</label>
        </div>

        <!-- Valor -->
        <div class="form-input-material">
            <input type="number" id="valor_servicio" name="valor_servicio" step="0.01" placeholder=" "
                value="<?= $recibo['valor_servicio'] ?>" required>
            <label for="valor_servicio">Valor del Servicio</label>
        </div>

        <!-- Fecha -->
        <div class="form-input-material">
            <input type="date" id="fecha_tramite" name="fecha_tramite" placeholder=" "
                value="<?= $recibo['fecha_tramite'] ?>" required>
            <label for="fecha_tramite">Fecha de Trámite</label>
        </div>

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

        <!-- Método de pago -->
        <div class="form-input-material">
            <select name="metodo_pago" id="metodo_pago" required>
                <option value="" disabled hidden>Selecciona un método</option>
                <option value="efectivo" <?= $recibo['metodo_pago'] === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                <option value="transferencia" <?= $recibo['metodo_pago'] === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                <option value="tarjeta" <?= $recibo['metodo_pago'] === 'tarjeta' ? 'selected' : '' ?>>Tarjeta</option>
                <option value="otro" <?= $recibo['metodo_pago'] === 'otro' ? 'selected' : '' ?>>Otro</option>
            </select>
            <label for="metodo_pago">Método de pago</label>
        </div>

        <!-- Descripción -->
        <div class="form-input-material">
            <textarea name="descripcion_servicio" id="descripcion_servicio" rows="4"
                placeholder=" "><?= htmlspecialchars($recibo['descripcion_servicio']) ?></textarea>
            <label for="descripcion_servicio">Descripción del Servicio</label>
        </div>

        <button type="submit" class="btn">Actualizar Recibo</button>
    </form>

    <button class="btn" onclick="cargarContenido('recibos/views/lista.php')">← Volver</button>

    <!-- Scripts de autocompletado, validación y envío -->
    <script>
        // Buscador Cliente
        document.getElementById("buscador_cliente").addEventListener("input", async function () {
            const query = this.value.trim();
            const contenedor = document.getElementById("resultados_cliente");
            if (query.length < 2) return contenedor.innerHTML = "";

            try {
                const resp = await fetch(`recibos/buscar_cliente.php?q=${encodeURIComponent(query)}`);
                const data = await resp.json();
                contenedor.innerHTML = data.length === 0
                    ? "<div class='item'>No se encontraron resultados</div>"
                    : "";

                data.forEach(cliente => {
                    const div = document.createElement("div");
                    div.className = "item";
                    div.textContent = `${cliente.nombre_completo} (${cliente.documento})`;
                    div.onclick = () => {
                        document.getElementById("buscador_cliente").value = cliente.nombre_completo;
                        document.getElementById("id_cliente").value = cliente.id_cliente;
                        contenedor.innerHTML = "";
                    };
                    contenedor.appendChild(div);
                });
            } catch (err) {
                console.error("Error al buscar cliente:", err);
            }
        });

        // Buscador Vehículo
        document.getElementById("buscador_vehiculo").addEventListener("input", async function () {
            const query = this.value.trim();
            const contenedor = document.getElementById("resultados_vehiculo");
            if (query.length < 2) return contenedor.innerHTML = "";

            try {
                const resp = await fetch(`recibos/buscar_vehiculo.php?q=${encodeURIComponent(query)}`);
                const data = await resp.json();
                contenedor.innerHTML = data.length === 0
                    ? "<div class='item'>No se encontraron resultados</div>"
                    : "";

                data.forEach(vehiculo => {
                    const div = document.createElement("div");
                    div.className = "item";
                    div.textContent = vehiculo.placa;
                    div.onclick = () => {
                        document.getElementById("buscador_vehiculo").value = vehiculo.placa;
                        document.getElementById("id_vehiculo").value = vehiculo.id_vehiculo;
                        contenedor.innerHTML = "";
                    };
                    contenedor.appendChild(div);
                });
            } catch (err) {
                console.error("Error al buscar vehículo:", err);
            }
        });

        // Buscador Asesor
        document.getElementById("buscador_asesor").addEventListener("input", async function () {
            const query = this.value.trim();
            const contenedor = document.getElementById("resultados_asesor");
            if (query.length < 2) return contenedor.innerHTML = "";

            try {
                const resp = await fetch(`recibos/buscar_asesor.php?q=${encodeURIComponent(query)}`);
                const data = await resp.json();
                contenedor.innerHTML = data.length === 0
                    ? "<div class='item'>No se encontraron resultados</div>"
                    : "";

                data.forEach(asesor => {
                    const div = document.createElement("div");
                    div.className = "item";
                    div.textContent = asesor.nombre;
                    div.onclick = () => {
                        document.getElementById("buscador_asesor").value = asesor.nombre;
                        document.getElementById("id_asesor").value = asesor.id_asesor;
                        contenedor.innerHTML = "";
                    };
                    contenedor.appendChild(div);
                });
            } catch (err) {
                console.error("Error al buscar asesor:", err);
            }
        });

        // Enviar formulario con validación de relación
        document.getElementById("form-editar-recibo").addEventListener("submit", async function (e) {
            e.preventDefault();

            const id_cliente = document.getElementById("id_cliente").value.trim();
            const id_vehiculo = document.getElementById("id_vehiculo").value.trim();

            if (!id_cliente || !id_vehiculo) {
                alert("⚠ Debes seleccionar un cliente y un vehículo.");
                return;
            }

            try {
                const validar = await fetch(`recibos/verificar_relacion_vehiculo.php?id_cliente=${id_cliente}&id_vehiculo=${id_vehiculo}`);
                const estado = (await validar.text()).trim();

                if (estado === "invalido") {
                    const confirmar = confirm("⚠ El vehículo no pertenece al cliente. ¿Deseas continuar?");
                    if (!confirmar) return;
                }

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
            } catch (err) {
                console.error("Error en envío:", err);
                alert("Error inesperado.");
            }
        });

        // Activar clase visual input-activo
        document.addEventListener("DOMContentLoaded", () => {
            const inputs = document.querySelectorAll(".form-input-material input, .form-input-material textarea, .form-input-material select");
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
