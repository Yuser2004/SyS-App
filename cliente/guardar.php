    <?php
    include __DIR__ . '/models/conexion.php';
    $nombre = trim($_POST['nombre_completo']);
    $documento = trim($_POST['documento']);
    $telefono = trim($_POST['telefono']);
    $ciudad = trim($_POST['ciudad']);
    $direccion = trim($_POST['direccion']);
    $observaciones = trim($_POST['observaciones']);
    $errores = [];

    // Validar
    if (empty($nombre) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $nombre)) {
        $errores[] = "El nombre debe contener solo letras y espacios.";
    }

    if (!preg_match("/^[a-zA-Z0-9\-]+$/", $documento)) {
        $errores[] = "El documento solo puede contener letras, números y guiones.";
    }

    if (!preg_match("/^\d{10}$/", $telefono)) {
        $errores[] = "El teléfono debe contener exactamente 10 dígitos.";
    }

    if (empty($ciudad) || !preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/", $ciudad)) {
        $errores[] = "La ciudad debe contener solo letras.";
    }

    if (empty($direccion)) {
        $errores[] = "La dirección es obligatoria.";
    }

    // Si hay errores, responder con el primer error
    if (!empty($errores)) {
        echo $errores[0];
        exit;
    }

    // Verificar documento duplicado
    $stmt = $conn->prepare("SELECT id_cliente FROM clientes WHERE documento = ?");
    $stmt->bind_param("s", $documento);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Ya existe un cliente con ese número de documento.";
        exit;
    }
    $stmt->close();

    // Insertar datos
    $stmt = $conn->prepare("INSERT INTO clientes (nombre_completo, documento, telefono, ciudad, direccion, observaciones)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $documento, $telefono, $ciudad, $direccion, $observaciones);

    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "Error al guardar: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
    ?>
