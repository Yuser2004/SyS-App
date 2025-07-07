<?php
include __DIR__ . '/models/conexion.php';

$q = trim($_GET['q'] ?? '');

$resultado = [];

if ($q !== "") {
    $stmt = $conn->prepare("SELECT id_cliente, nombre_completo, documento FROM clientes WHERE nombre_completo LIKE ? OR documento LIKE ? LIMIT 10");
    $like = "%$q%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $resultado[] = $row;
    }

    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($resultado);
?>
