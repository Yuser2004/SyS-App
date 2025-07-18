<?php
include __DIR__ . '/../models/conexion.php';

$origen = $_GET['origen'] ?? 'recibos';
$id_cliente = intval($_GET['id_cliente'] ?? 0);
$cliente_nombre = '';
$vehiculo_nombre = '';
$asesor_nombre = '';
$id_vehiculo = 0;
$id_asesor = 0;

if ($id_cliente > 0) {
    $res = $conn->query("SELECT nombre_completo FROM clientes WHERE id_cliente = $id_cliente");
    if ($res) {
        $cliente = $res->fetch_assoc();
        $cliente_nombre = $cliente['nombre_completo'] ?? '';
    }
}
?>

<link rel="stylesheet" href="recibos/public/estilos_form.css">

<div class="login-form">
    <h1>Registrar Recibo</h1>
    <form id="form-crear-recibo">

        <!-- Cliente -->
        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_cliente" placeholder=" " autocomplete="off"
                value="<?= htmlspecialchars($cliente_nombre) ?>">
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $id_cliente ?>" required>
            <div id="resultados_cliente" class="resultado-autocompletar"></div>
            <label for="buscador_cliente">Cliente</label>
        </div>


        <!-- Vehículo -->
        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_vehiculo" autocomplete="off" placeholder=" " >
            <input type="hidden" id="id_vehiculo" name="id_vehiculo" value="<?= $id_vehiculo ?>" required>
            <div id="resultados_vehiculo" class="resultado-autocompletar"></div>
            <label for="buscador_vehiculo">Vehículo</label>
        </div>

        <!-- Asesor -->
        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_asesor" autocomplete="off" placeholder=" " >
            <input type="hidden" id="id_asesor" name="id_asesor" value="<?= $id_asesor ?>">
            <div id="resultados_asesor" class="resultado-autocompletar"></div>
            <label for="buscador_asesor">Asesor</label>
        </div>

        <!-- Concepto -->
        <div class="form-input-material">
            <input type="text" id="concepto_servicio" name="concepto_servicio" placeholder=" " required>
            <label for="concepto_servicio">Concepto del Servicio</label>
        </div>

        <!-- Valor -->
        <div class="form-input-material">
            <input type="number" id="valor_servicio" name="valor_servicio" step="0.01" placeholder=" " required>
            <label for="valor_servicio">Valor del Servicio</label>
        </div>

        <!-- Fecha -->
        <div class="form-input-material">
            <input type="date" id="fecha_tramite" name="fecha_tramite" placeholder=" " required>
            <label for="fecha_tramite">Fecha de Trámite</label>
        </div>

        <!-- Estado -->
        <div class="form-input-material">
            <select name="estado" id="estado" required>
                <option value="" disabled selected hidden>Selecciona un estado</option>
                <option value="pendiente">Pendiente</option>
                <option value="completado">Completado</option>
                <option value="cancelado">Cancelado</option>
            </select>
            <label for="estado">Estado</label>
        </div>

        <!-- Método de pago -->
        <div class="form-input-material">
            <select name="metodo_pago" id="metodo_pago" required>
                <option value="" disabled selected hidden>Selecciona un método</option>
                <option value="efectivo">Efectivo</option>
                <option value="transferencia">Transferencia</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otro">Otro</option>
            </select>
            <label for="metodo_pago">Método de pago</label>
        </div>

        <!-- Descripción -->
        <div class="form-input-material">
            <textarea name="descripcion_servicio" id="descripcion_servicio" rows="4" placeholder=" "></textarea>
            <label for="descripcion_servicio">Descripción del Servicio</label>
        </div>

        <button type="submit" class="btn">Guardar Recibo</button>
    </form>

    <button class="btn" onclick="cargarContenido('recibos/views/lista.php')">← Volver</button>

    <script>
        // Buscador de Cliente
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

        // Buscador de Vehículo
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

        // Buscador de Asesor
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

document.getElementById("form-crear-recibo").addEventListener("submit", async function (e) {
    e.preventDefault();

    const id_cliente = document.getElementById("id_cliente").value.trim();
    const id_vehiculo = document.getElementById("id_vehiculo").value.trim();

    console.log("🟡 Cliente ID:", id_cliente);
    console.log("🟡 Vehículo ID:", id_vehiculo);

    // Validar si los campos están vacíos
    if (!id_cliente || !id_vehiculo) {
        alert("⚠ Debes seleccionar un cliente y un vehículo.");
        return;
    }

    try {
        const validacion = await fetch(`recibos/verificar_relacion_vehiculo.php?id_cliente=${id_cliente}&id_vehiculo=${id_vehiculo}`);
        const estado = (await validacion.text()).trim();
        console.log("🔵 Respuesta de validación:", estado);

        if (estado === "invalido") {
            const confirmar = confirm("⚠ El vehículo seleccionado no pertenece al cliente. ¿Deseas continuar de todos modos?");
            if (!confirmar) {
                console.log("🔴 El usuario canceló el envío porque la relación es inválida.");
                return;
            }
        }

        // Enviar si todo está bien o el usuario aceptó continuar
        const formData = new FormData(this);

        const resp = await fetch("recibos/guardar.php", {
            method: "POST",
            body: formData
        });

        const texto = await resp.text();
        console.log("🟢 Respuesta de guardar.php:", texto);

        if (texto.trim() === "ok") {
            cargarContenido('recibos/views/lista.php');
        } else {
            alert("Error al guardar: " + texto);
        }
    } catch (err) {
        console.error("🔥 Error en el proceso:", err);
        alert("Error inesperado en la solicitud.");
    }
});
</script>

    <script>
document.addEventListener("DOMContentLoaded", () => {
  const inputs = document.querySelectorAll(".form-input-material input[type='text'], .form-input-material input[type='number'], .form-input-material input[type='date'], .form-input-material textarea, .form-input-material select");

  inputs.forEach(input => {
    // Activar clase si el campo ya tiene valor (por value=)
    if (input.value.trim() !== "") {
      input.classList.add("input-activo");
    }

    // Escuchar cambios para activar o quitar clase
    input.addEventListener("input", () => {
      if (input.value.trim() !== "") {
        input.classList.add("input-activo");
      } else {
        input.classList.remove("input-activo");
      }
    });

    // También aplicar al cambiar select manualmente
    input.addEventListener("change", () => {
      if (input.value.trim() !== "") {
        input.classList.add("input-activo");
      } else {
        input.classList.remove("input-activo");
      }
    });
  });
});
</script>

</div>
