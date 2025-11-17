<?php
include __DIR__ . '/../models/conexion.php';
require_once __DIR__ . '/../../auth_check.php';

$id = $_GET['id'];
$resultado = $conn->query("SELECT * FROM clientes WHERE id_cliente = $id");

if ($resultado->num_rows === 0) {
    echo "<p>Cliente no encontrado.</p>";
    exit;
}

$cliente = $resultado->fetch_assoc();
?>
<link rel="stylesheet" href="cliente/public/css/estilos_form.css">

<div class="login-form">
    <h1>Editar Cliente</h1>
    <form id="form-editar">
        <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">

        <div class="form-input-material">
            <input type="text" id="nombre_completo" name="nombre_completo" value="<?= htmlspecialchars($cliente['nombre_completo']) ?>" placeholder=" " required>
            <label for="nombre_completo">Nombre Completo</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="documento" name="documento" value="<?= htmlspecialchars($cliente['documento']) ?>" placeholder=" " required>
            <label for="documento">Documento</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="telefono" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>" placeholder=" " required>
            <label for="telefono">Teléfono</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="ciudad" name="ciudad" value="<?= htmlspecialchars($cliente['ciudad']) ?>" placeholder=" " required>
            <label for="ciudad">Ciudad</label>
        </div>

        <div class="form-input-material">
            <input type="text" id="direccion" name="direccion" value="<?= htmlspecialchars($cliente['direccion']) ?>" placeholder=" " required>
            <label for="direccion">Dirección</label>
        </div>

        <div class="form-input-material">
            <textarea id="observaciones" name="observaciones" placeholder=" " rows="4"><?= htmlspecialchars($cliente['observaciones']) ?></textarea>
            <label for="observaciones">Observaciones</label>
        </div>

        <button type="submit" class="btn">Actualizar</button>
    </form>

    <button class="btn" onclick="cargarContenido('cliente/views/fragmento_clientes.php')">← Volver</button>

    <script>
    function validarFormulario() {
        const nombre = document.getElementById('nombre_completo').value.trim();
        const documento = document.getElementById('documento').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const ciudad = document.getElementById('ciudad').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const id_cliente = document.querySelector('input[name="id_cliente"]').value;

        let errores = [];
        const soloLetras = /^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/;
        const soloNumeros = /^\d+$/;

        if (!soloLetras.test(nombre)) {
            errores.push("El nombre solo debe contener letras y espacios.");
        }

        if (!/^[a-zA-Z0-9\-]+$/.test(documento)) {
            errores.push("El documento solo puede contener letras, números y guiones.");
        }

        if (!soloNumeros.test(telefono) || telefono.length !== 10) {
            errores.push("El teléfono debe contener exactamente 10 dígitos.");
        }

        if (!soloLetras.test(ciudad)) {
            errores.push("La ciudad solo debe contener letras.");
        }

        if (direccion === "") {
            errores.push("La dirección no puede estar vacía.");
        }

        if (errores.length > 0) {
            alert(errores.join("\n"));
            return false;
        }

        // Verificar duplicado por AJAX
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "cliente/verificar_documento.php?documento=" + documento + "&id=" + id_cliente, false);
        xhr.send();

        if (xhr.responseText === "existe") {
            alert("Ya existe otro cliente con ese número de documento.");
            return false;
        }

        return true;
    }

    document.getElementById("form-editar").addEventListener("submit", async function (e) {
        e.preventDefault();
        if (!validarFormulario()) return;

        const formData = new FormData(this);

        try {
            const resp = await fetch("cliente/actualizar.php", {
                method: "POST",
                body: formData
            });

            const texto = await resp.text();
            if (texto.trim() === "ok") {
                cargarContenido('cliente/views/fragmento_clientes.php');
            } else {
                alert("Error al actualizar: " + texto);
            }
        } catch (error) {
            alert("Error de red: " + error);
        }
    });
    </script>
</div>
