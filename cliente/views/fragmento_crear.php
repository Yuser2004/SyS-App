<link rel="stylesheet" href="cliente/public/css/estilos_form.css">
<div class="login-form">
    <h1>Agregar Cliente</h1>
    <form id="form-crear">
        <div class="form-input-material">
            <input type="text" id="nombre_completo" name="nombre_completo" placeholder=" " required>
            <label for="nombre_completo">Nombre Completo</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="documento" name="documento" placeholder=" " required>
            <label for="documento">Documento</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="telefono" name="telefono" placeholder=" " required>
            <label for="telefono">Teléfono</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="ciudad" name="ciudad" placeholder=" " required>
            <label for="ciudad">Ciudad</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="direccion" name="direccion" placeholder=" " required>
            <label for="direccion">Dirección</label>
        </div>

        <div class="form-input-material">
            <textarea id="observaciones" name="observaciones" placeholder=" " rows="4"></textarea>
            <label for="observaciones">Observaciones</label>
        </div>

        <button type="submit" class="btn">Guardar</button>
    </form>

    <button class="btn" onclick="cargarContenido('cliente/views/fragmento_clientes.php')">← Volver</button>

    <script>
    function validarFormulario() {
        const nombre = document.getElementById('nombre_completo').value.trim();
        const documento = document.getElementById('documento').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const ciudad = document.getElementById('ciudad').value.trim();
        const direccion = document.getElementById('direccion').value.trim();

        let errores = [];
        const regexLetras = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;

        if (!regexLetras.test(nombre)) {
            errores.push("El nombre solo debe contener letras y espacios.");
        }

        if (!regexLetras.test(ciudad)) {
            errores.push("La ciudad solo debe contener letras y espacios.");
        }

        if (!/^[a-zA-Z0-9\-]+$/.test(documento)) {
            errores.push("El documento solo puede contener letras, números y guiones.");
        }

        if (!/^\d{10}$/.test(telefono)) {
            errores.push("El teléfono debe contener exactamente 10 dígitos.");
        }

        if (direccion === "") {
            errores.push("La dirección no puede estar vacía.");
        }

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return false;
        }

        // Verificar documento duplicado con AJAX
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "cliente/verificar_documento.php?documento=" + encodeURIComponent(documento) + "&id=0", false);
        xhr.send();

        if (xhr.responseText === "existe") {
            alert("Ya existe un cliente con ese número de documento.");
            return false;
        }

        return true;
    }

    document.getElementById("form-crear").addEventListener("submit", async function (e) {
        e.preventDefault(); // evitar recarga

        if (!validarFormulario()) return;

        const formData = new FormData(this);

        try {
            const resp = await fetch("cliente/guardar.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            if (texto.trim() === "ok") {
                cargarContenido('cliente/views/fragmento_clientes.php');
            } else {
                alert("Error al guardar: " + texto);
            }
        } catch (error) {
            alert("Error de red: " + error);
        }
    });
    </script>
</div>
