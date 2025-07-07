<?php
include __DIR__ . '/../models/conexion.php';
$origen = $_GET['origen'] ?? 'vehiculos'; // valor por defecto
$id_cliente = intval($_GET['id_cliente'] ?? 0);
$cliente_nombre = '';

if ($id_cliente > 0) {
    $cliente = $conn->query("SELECT nombre_completo FROM clientes WHERE id_cliente = $id_cliente")->fetch_assoc();
    $cliente_nombre = $cliente['nombre_completo'] ?? '';
}
?>

<link rel="stylesheet" href="css/estilos_form.css">

<div class="login-form">
    <h1>Registrar Vehículo</h1>
    <form id="form-crear-vehiculo">
        <div class="form-input-material">
            <input type="text" id="placa" name="placa" placeholder=" " required>
            <label for="placa">Placa</label>
        </div>

        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_cliente" placeholder="Buscar cliente por nombre o documento..." autocomplete="off"
                value="<?= htmlspecialchars($cliente_nombre) ?>" <?= $id_cliente > 0 ? 'readonly' : '' ?>>
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $id_cliente ?>" required>
            <div id="resultados_cliente" class="resultado-autocompletar"></div>
            <label for="buscador_cliente">Cliente</label>
        </div>

        <button type="submit" class="btn">Guardar</button>
    </form>

        <?php if ($origen === 'clientes'): ?>
            <button class="btn" onclick="cargarContenido('cliente/views/fragmento_clientes.php')">← Volver</button>
        <?php else: ?>
            <button class="btn" onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">← Volver</button>
        <?php endif; ?>


    <script>
        function validarFormularioVehiculo() {
            let placa = document.getElementById("placa").value.trim().replace(/\s+/g, "").toUpperCase(); // Elimina espacios y convierte a mayúsculas
            document.getElementById("placa").value = placa; // Actualiza el campo con el valor limpio

            const cliente = document.getElementById("id_cliente").value;
            let errores = [];

            // Validaciones
            if (placa === "") {
                errores.push("La placa es obligatoria.");
            } else {
                // Validar longitud (exactamente 6 caracteres)
                if (placa.length < 6 || placa.length > 6) {
                    errores.push("La placa debe tener exactamente 6 caracteres.");
                }

                // Validar solo letras y números
                if (!/^[A-Z0-9]+$/.test(placa)) {
                    errores.push("La placa solo debe contener letras y números, sin símbolos ni espacios.");
                }

                // Debe contener al menos una letra y un número
                if (!/[A-Z]/.test(placa) || !/[0-9]/.test(placa)) {
                    errores.push("La placa debe contener al menos una letra y un número.");
                }
            }

            if (cliente === "") {
                errores.push("Debes seleccionar un cliente.");
            }

            if (errores.length > 0) {
                alert(errores.join("\n"));
                return false;
            }

            return true;
        }


        async function verificarPlacaDuplicada(placa) {
            const resp = await fetch(`vehiculo/verificar_placa.php?placa=${encodeURIComponent(placa)}&id=0`);
            const texto = await resp.text();
            return texto.trim() === "existe";
        }

        document.getElementById("form-crear-vehiculo").addEventListener("submit", async function (e) {
            e.preventDefault();

            if (!validarFormularioVehiculo()) return;

            const placa = document.getElementById("placa").value.trim();
            const esDuplicada = await verificarPlacaDuplicada(placa);

            if (esDuplicada) {
                alert("Ya existe un vehículo con esa placa.");
                return;
            }

            const formData = new FormData(this);

            try {
                const resp = await fetch("vehiculo/guardar.php", {
                    method: "POST",
                    body: formData
                });

                const texto = await resp.text();
                if (texto.trim() === "ok") {
                    cargarContenido('vehiculo/views/lista_vehiculos.php');
                } else {
                    alert("Error al guardar: " + texto);
                }
            } catch (error) {
                alert("Error de red: " + error);
            }
        });

        document.getElementById("buscador_cliente").addEventListener("input", async function () {
            const query = this.value.trim();

            if (query.length < 2) {
                document.getElementById("resultados_cliente").innerHTML = "";
                return;
            }

            try {
                const resp = await fetch(`vehiculo/buscar_cliente.php?q=${encodeURIComponent(query)}`);
                const data = await resp.json();

                const contenedor = document.getElementById("resultados_cliente");
                contenedor.innerHTML = "";

                if (data.length === 0) {
                    contenedor.innerHTML = "<div class='item'>No se encontraron resultados</div>";
                    return;
                }

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
                console.error("Error al buscar:", err);
            }
        });
    </script>
</div>
