<?php
include __DIR__ . '/../models/conexion.php';

$id = intval($_GET['id'] ?? 0);
$vehiculo = $conn->query("SELECT * FROM vehiculo WHERE id_vehiculo = $id")->fetch_assoc();
?>

<link rel="stylesheet" href="css/estilos_form.css">

<div class="login-form">
    <h1>Editar Vehículo</h1>
    <form id="form-editar-vehiculo">
        <input type="hidden" name="id_vehiculo" value="<?= $vehiculo['id_vehiculo'] ?>">

        <div class="form-input-material">
            <input type="text" id="placa" name="placa" placeholder=" " value="<?= htmlspecialchars($vehiculo['placa']) ?>" required>
            <label for="placa">Placa</label>
        </div>

        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_cliente" placeholder="Buscar cliente por nombre o documento..." autocomplete="off"
                value="<?php
                    $cliente = $conn->query("SELECT nombre_completo FROM clientes WHERE id_cliente = {$vehiculo['id_cliente']}")->fetch_assoc();
                    echo htmlspecialchars($cliente['nombre_completo'] ?? '');
                ?>">
            <input type="hidden" id="id_cliente" name="id_cliente" value="<?= $vehiculo['id_cliente'] ?>" required>
            <div id="resultados_cliente" class="resultado-autocompletar"></div>
            <label for="buscador_cliente">Cliente</label>
        </div>

        <button type="submit" class="btn">Actualizar</button>
    </form>

    <button class="btn" onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">← Volver</button>

<script>
    function validarFormularioVehiculo() {
        let placa = document.getElementById("placa").value.trim().replace(/\s+/g, "").toUpperCase(); // Limpiar espacios y mayúsculas
        document.getElementById("placa").value = placa; // Actualiza el campo con el valor limpio

        const cliente = parseInt(document.getElementById("id_cliente").value);
        let errores = [];

        // Validación de placa
        if (placa === "") {
            errores.push("La placa es obligatoria.");
        } else {
            if (placa.length !== 6) {
                errores.push("La placa debe tener exactamente 6 caracteres.");
            }

            if (!/^[A-Z0-9]+$/.test(placa)) {
                errores.push("La placa solo debe contener letras y números, sin símbolos ni espacios.");
            }

            if (!/[A-Z]/.test(placa) || !/[0-9]/.test(placa)) {
                errores.push("La placa debe contener al menos una letra y un número.");
            }
        }

        // Validación de cliente
        if (isNaN(cliente) || cliente <= 0) {
            errores.push("Debes seleccionar un cliente desde la lista.");
        }

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return false;
        }

        return true;
    }


    // Limpiar id_cliente si se borra el texto del buscador
    document.getElementById("buscador_cliente").addEventListener("input", async function () {
        const query = this.value.trim();
        const idClienteInput = document.getElementById("id_cliente");
        idClienteInput.value = ""; // siempre se limpia al escribir

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
                    idClienteInput.value = cliente.id_cliente;
                    contenedor.innerHTML = "";
                };
                contenedor.appendChild(div);
            });
        } catch (err) {
            console.error("Error al buscar:", err);
        }
    });

    document.getElementById("form-editar-vehiculo").addEventListener("submit", async function (e) {
        e.preventDefault();

        if (!validarFormularioVehiculo()) return;

        const placa = document.getElementById("placa").value.trim();
        const idVehiculo = document.querySelector("[name='id_vehiculo']").value;

        try {
            console.log("Verificando placa:", placa, "ID actual:", idVehiculo);

            const respuesta = await fetch(`vehiculo/verificar_placa.php?placa=${encodeURIComponent(placa)}&id=${idVehiculo}`);
            const resultado = (await respuesta.text()).trim();

            console.log("Respuesta de verificar_placa.php:", resultado);

            if (resultado !== "ok") {
                alert("⚠️ " + resultado);
                return;
            }

            const formData = new FormData(this);

            const respuestaGuardar = await fetch("vehiculo/guardar.php", {
                method: "POST",
                body: formData
            });

            const texto = (await respuestaGuardar.text()).trim();
            console.log("Respuesta de guardar.php:", texto);

            if (texto === "ok") {
                cargarContenido('vehiculo/views/lista_vehiculos.php');
            } else {
                alert("❌ Error al actualizar: " + texto);
            }
        } catch (error) {
            alert("❌ Error de red: " + error);
        }
    });
</script>

</div>
