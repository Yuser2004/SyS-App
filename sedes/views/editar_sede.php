<?php
include __DIR__ . '/../models/conexion.php';

$id = $_GET['id'];
$resultado = $conn->query("SELECT * FROM sedes WHERE id = $id");

if ($resultado->num_rows === 0) {
    echo "<p>Sede no encontrada.</p>";
    exit;
}

$sede = $resultado->fetch_assoc();
?>

<link rel="stylesheet" href="cliente/public/css/estilos_form.css">

<div class="login-form">
    <h1>Editar Sede</h1>
    <form id="form-editar">
        <input type="hidden" name="id" value="<?= $sede['id'] ?>">

        <div class="form-input-material">
            <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($sede['nombre']) ?>" placeholder=" " required>
            <label for="nombre">Nombre de la sede</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($sede['direccion']) ?>" placeholder=" " required>
            <label for="direccion">Dirección</label>
        </div>

        <button type="submit" class="btn">Actualizar</button>
    </form>

    <button class="btn" onclick="cargarContenido('sedes/views/lista_sedes.php')">← Volver</button>

    <script>
    function validarFormularioSede() {
        const nombre = document.getElementById('nombre').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const errores = [];

        if (nombre === "") {
            errores.push("El nombre de la sede no puede estar vacío.");
        }

        if (!/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑ\s]+$/.test(nombre)) {
            errores.push("El nombre solo puede contener letras, números y espacios.");
        }

        if (direccion === "") {
            errores.push("La dirección no puede estar vacía.");
        }

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return false;
        }

        return true;
    }

    document.getElementById("form-editar").addEventListener("submit", async function (e) {
        e.preventDefault();

        if (!validarFormularioSede()) return;

        const formData = new FormData(this);

        try {
            const resp = await fetch("sedes/actualizar_sede.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            if (texto.trim() === "ok") {
                cargarContenido('sedes/views/lista_sedes.php');
            } else {
                alert("Error al actualizar: " + texto);
            }
        } catch (error) {
            alert("Error de red: " + error);
        }
    });
    </script>
</div>
