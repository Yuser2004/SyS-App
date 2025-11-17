<?php
include __DIR__ . '/../models/conexion.php';
require_once __DIR__ . '/../../auth_check.php';
$id_sede = intval($_GET['id_sede'] ?? 0);
$sede_nombre = '';

if ($id_sede > 0) {
    $res = $conn->query("SELECT nombre FROM sedes WHERE id = $id_sede");
    $sede_nombre = $res ? ($res->fetch_assoc()['nombre'] ?? '') : '';
}
?>

<link rel="stylesheet" href="css/estilos_form.css">

<div class="login-form">
    <h1>Registrar Asesor</h1>
    <form id="form-crear-asesor">
        <div class="form-input-material">
            <input type="text" id="nombre" name="nombre" placeholder=" " required>
            <label for="nombre">Nombre del Asesor</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="documento" name="documento" placeholder=" " required>
            <label for="documento">Documento</label>
        </div>

        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_sede" placeholder="Buscar sede por nombre..." autocomplete="off"
                   value="<?= htmlspecialchars($sede_nombre) ?>" <?= $id_sede > 0 ? 'readonly' : '' ?>>
            <input type="hidden" id="id_sede" name="id_sede" value="<?= $id_sede ?>" required>
            <div id="resultados_sede" class="resultado-autocompletar"></div>
            <label for="buscador_sede">Sede</label>
        </div>

        <button type="submit" class="btn">Guardar</button>
    </form>

    <button class="btn" onclick="cargarContenido('asesor/views/lista_asesor.php')">← Volver</button>

    <script>
    function validarFormularioAsesor() {
        const nombre = document.getElementById("nombre").value.trim();
        const documento = document.getElementById("documento").value.trim();
        const id_sede = document.getElementById("id_sede").value;

        let errores = [];
        if (nombre === "") errores.push("El nombre es obligatorio.");
        if (documento === "") errores.push("El documento es obligatorio.");
        if (id_sede === "") errores.push("Debes seleccionar una sede.");

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return false;
        }

        return true;
    }

    document.getElementById("form-crear-asesor").addEventListener("submit", async function (e) {
        e.preventDefault();
        if (!validarFormularioAsesor()) return;

        // Verificar nombre duplicado
        const nombre = document.getElementById("nombre").value.trim();
        const resp = await fetch(`asesor/verificar_nombre.php?nombre=${encodeURIComponent(nombre)}&id=0`);
        const texto = await resp.text();

        if (texto.trim() === "existe") {
            alert("Ya existe un asesor con ese nombre.");
            return;
        }

        const formData = new FormData(this);

        try {
            const resp = await fetch("asesor/guardar_asesor.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            if (texto.trim() === "ok") {
                cargarContenido('asesor/views/lista_asesor.php');
            } else {
                alert("Error al guardar: " + texto);
            }
        } catch (error) {
            alert("Error de red: " + error);
        }
    });

    document.getElementById("buscador_sede").addEventListener("input", async function () {
        const query = this.value.trim();

        if (query.length < 2) {
            document.getElementById("resultados_sede").innerHTML = "";
            return;
        }

        try {
            const resp = await fetch(`asesor/buscar_sede.php?q=${encodeURIComponent(query)}`);
            const data = await resp.json();

            const contenedor = document.getElementById("resultados_sede");
            contenedor.innerHTML = "";

            if (data.length === 0) {
                contenedor.innerHTML = "<div class='item'>No se encontraron sedes</div>";
                return;
            }

            data.forEach(sede => {
                const div = document.createElement("div");
                div.className = "item";
                div.textContent = sede.nombre;
                div.onclick = () => {
                    document.getElementById("buscador_sede").value = sede.nombre;
                    document.getElementById("id_sede").value = sede.id;
                    contenedor.innerHTML = "";
                };
                contenedor.appendChild(div);
            });
        } catch (err) {
            console.error("Error al buscar sede:", err);
        }
    });
    </script>
</div>
