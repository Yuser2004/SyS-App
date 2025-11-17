<?php
include __DIR__ . '/../models/conexion.php';
require_once __DIR__ . '/../../auth_check.php';
$id = intval($_GET['id'] ?? 0);
$res = $conn->query("SELECT * FROM asesor WHERE id_asesor = $id");

if ($res->num_rows === 0) {
    echo "<p>Asesor no encontrado.</p>";
    exit;
}

$asesor = $res->fetch_assoc();

// Obtener nombre de la sede asociada
$sede = $conn->query("SELECT nombre FROM sedes WHERE id = {$asesor['id_sede']}")->fetch_assoc();
$sede_nombre = $sede['nombre'] ?? '';
?>

<link rel="stylesheet" href="css/estilos_form.css">

<div class="login-form">
    <h1>Editar Asesor</h1>
    <form id="form-editar-asesor">
        <input type="hidden" name="id_asesor" value="<?= $asesor['id_asesor'] ?>">

        <div class="form-input-material">
            <input type="text" id="nombre" name="nombre" placeholder=" " value="<?= htmlspecialchars($asesor['nombre']) ?>" required>
            <label for="nombre">Nombre</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="documento" name="documento" placeholder=" " value="<?= htmlspecialchars($asesor['documento']) ?>" required>
            <label for="documento">Documento</label>
        </div>

        <div class="form-input-material" style="position: relative;">
            <input type="text" id="buscador_sede" placeholder="Buscar sede por nombre..." autocomplete="off"
                value="<?= htmlspecialchars($sede_nombre) ?>">
            <input type="hidden" id="id_sede" name="id_sede" value="<?= $asesor['id_sede'] ?>" required>
            <div id="resultados_sede" class="resultado-autocompletar"></div>
            <label for="buscador_sede">Sede</label>
        </div>

        <button type="submit" class="btn">Actualizar</button>
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
        if (id_sede === "") errores.push("Debes seleccionar una sede desde la lista.");

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return false;
        }

        return true;
    }

    document.getElementById("buscador_sede").addEventListener("input", async function () {
        const query = this.value.trim();
        const idInput = document.getElementById("id_sede");
        idInput.value = ""; // reset id al escribir

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
                    idInput.value = sede.id;
                    contenedor.innerHTML = "";
                };
                contenedor.appendChild(div);
            });
        } catch (err) {
            console.error("Error al buscar sede:", err);
        }
    });

    document.getElementById("form-editar-asesor").addEventListener("submit", async function (e) {
        e.preventDefault();

        if (!validarFormularioAsesor()) return;

        const formData = new FormData(this);

        try {
            const resp = await fetch("asesor/actualizar_asesor.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            if (texto.trim() === "ok") {
                cargarContenido('asesor/views/lista_asesor.php');
            } else {
                alert("Error al actualizar: " + texto);
            }
        } catch (err) {
            alert("Error de red: " + err);
        }
    });
    </script>
</div>
