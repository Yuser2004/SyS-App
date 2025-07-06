<?php
include __DIR__ . '/../models/conexion.php';


$id = $_GET['id'];
$resultado = $conn->query("SELECT * FROM clientes WHERE id_cliente = $id");

if ($resultado->num_rows === 0) {
    echo "<p>Cliente no encontrado.</p>";
    exit;
}

$cliente = $resultado->fetch_assoc();
?>

<h1>Editar Cliente</h1>
<form id="form-editar">
    <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">

    <label>Nombre Completo:</label><br>
    <input type="text" id="nombre_completo" name="nombre_completo" value="<?= $cliente['nombre_completo'] ?>"><br><br>

    <label>Documento:</label><br>
    <input type="text" id="documento" name="documento" value="<?= $cliente['documento'] ?>"><br><br>

    <label>Teléfono:</label><br>
    <input type="text" id="telefono" name="telefono" value="<?= $cliente['telefono'] ?>"><br><br>

    <label>Ciudad:</label><br>
    <input type="text" id="ciudad" name="ciudad" value="<?= $cliente['ciudad'] ?>"><br><br>

    <label>Dirección:</label><br>
    <input type="text" id="direccion" name="direccion" value="<?= $cliente['direccion'] ?>"><br><br>

    <label>Observaciones:</label><br>
    <textarea name="observaciones" rows="4" cols="50"><?= $cliente['observaciones'] ?></textarea><br><br>

    <button type="submit">Actualizar</button>
</form>
<br>
<button onclick="cargarContenido('cliente/views/fragmento_clientes.php')">← Volver</button>

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

    // Verificar duplicado por AJAX antes de enviar
    const xhr = new XMLHttpRequest();
    xhr.open("GET", "cliente/verificar_documento.php?documento=" + documento + "&id=" + id_cliente, false); // síncrono
    xhr.send();

    if (xhr.responseText === "existe") {
        alert("Ya existe otro cliente con ese número de documento.");
        return false;
    }

    return true;
}
</script>
<script>
document.getElementById("form-editar").addEventListener("submit", async function (e) {
    e.preventDefault(); // evita que recargue

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

