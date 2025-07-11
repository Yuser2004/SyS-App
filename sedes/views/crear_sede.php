<?php
//  crear el archivo verificar_nombre.php como se explicó antes.
?>
<link rel="stylesheet" href="css/estilos_form.css">

<div class="login-form">
    <h1>Registrar Sede</h1>
    <form id="form-crear-sede">
        <div class="form-input-material">
            <input type="text" name="nombre" id="nombre" placeholder=" " required>
            <label for="nombre">Nombre de la sede</label>
        </div>
        <div class="form-input-material">
            <input type="text" name="direccion" id="direccion" placeholder=" " required>
            <label for="direccion">Dirección</label>
        </div>

        <button type="submit" class="btn">Guardar</button>
    </form>

    <button class="btn" onclick="cargarContenido('sedes/views/lista_sedes.php')">← Volver</button>

    <script>
    document.getElementById("form-crear-sede").addEventListener("submit", async function (e) {
        e.preventDefault();

        const nombre = document.getElementById("nombre").value.trim();
        const direccion = document.getElementById("direccion").value.trim();
        let errores = [];

        if (nombre === "") errores.push("El nombre de la sede es obligatorio.");
        if (direccion === "") errores.push("La dirección es obligatoria.");

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return;
        }

        // Verificar si ya existe una sede con ese nombre
        try {
            const verificar = await fetch(`sedes/verificar_nombre.php?nombre=${encodeURIComponent(nombre)}&id=0`);
            const respuesta = await verificar.text();

            if (respuesta.trim() === "existe") {
                alert("Ya existe una sede con ese nombre.");
                return;
            }

            // Si pasa la validación, enviamos el formulario
            const formData = new FormData(this);
            const resp = await fetch("sedes/guardar_sede.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            if (texto.trim() === "ok") {
                cargarContenido('sedes/views/lista_sedes.php');
            } else {
                alert("Error al guardar: " + texto);
            }

        } catch (err) {
            alert("Error de red: " + err);
        }
    });
    </script>
</div>
