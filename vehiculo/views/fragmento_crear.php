<link rel="stylesheet" href="vehiculo/public/css/estilos_form.css">

<div class="login-form">
    <h1>Registrar Vehículo</h1>
    <form id="form-crear-vehiculo">
        <div class="form-input-material">
            <input type="text" id="placa" name="placa" placeholder=" " required>
            <label for="placa">Placa</label>
        </div>

        <div class="form-input-material">
            <select id="id_cliente" name="id_cliente" required>
                <option value="">-- Selecciona un cliente --</option>
                <?php
                include __DIR__ . '/../models/conexion.php';
                $clientes = $conn->query("SELECT id_cliente, nombre_completo FROM clientes ORDER BY nombre_completo ASC");
                while ($cliente = $clientes->fetch_assoc()):
                ?>
                    <option value="<?= $cliente['id_cliente'] ?>"><?= htmlspecialchars($cliente['nombre_completo']) ?></option>
                <?php endwhile; ?>
            </select>
            <label for="id_cliente">Cliente</label>
        </div>

        <button type="submit" class="btn">Guardar</button>
    </form>

    <button class="btn" onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">← Volver</button>

    <script>
        function validarFormularioVehiculo() {
            const placa = document.getElementById("placa").value.trim();
            const cliente = document.getElementById("id_cliente").value;

            let errores = [];

            if (placa === "") errores.push("La placa es obligatoria.");
            if (cliente === "") errores.push("Debes seleccionar un cliente.");

            if (errores.length > 0) {
                alert(errores.join("\n"));
                return false;
            }

            return true;
        }

        document.getElementById("form-crear-vehiculo").addEventListener("submit", async function (e) {
            e.preventDefault();

            if (!validarFormularioVehiculo()) return;

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
    </script>
</div>
