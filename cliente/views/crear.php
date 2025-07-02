<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cliente</title>
</head>
<body>
    <h1>Agregar Cliente</h1>
    <form action="../guardar.php" method="POST" onsubmit="return validarFormulario()">
        <label>Nombre Completo:</label><br>
        <input type="text" id="nombre_completo" name="nombre_completo"><br><br>

        <label>Documento:</label><br>
        <input type="text" id="documento" name="documento"><br><br>

        <label>Teléfono:</label><br>
        <input type="text" id="telefono" name="telefono"><br><br>

        <label>Ciudad:</label><br>
        <input type="text" id="ciudad" name="ciudad"><br><br>

        <label>Dirección:</label><br>
        <input type="text" id="direccion" name="direccion"><br><br>
        
        <label>Observaciones:</label><br>
        <textarea name="observaciones" rows="4" cols="50"></textarea><br><br>

        <button type="submit">Guardar</button>
    </form>
    <br>
    <a href="index.php">← Volver</a>
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

            // Validar documento duplicado con AJAX
            const xhr = new XMLHttpRequest();
            xhr.open("GET", "../verificar_documento.php?documento=" + encodeURIComponent(documento) + "&id=0", false); // síncrono
            xhr.send();

            if (xhr.responseText === "existe") {
                alert("Ya existe un cliente con ese número de documento.");
                return false;
            }

            return true;
        }
        </script>

</body>
</html>
