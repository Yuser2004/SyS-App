<?php
include __DIR__ . '/models/conexion.php';

$q = trim($_GET['q'] ?? '');
$resultados = [];

if ($q !== '') {
    $like = "%$q%";
    $stmt = $conn->prepare("SELECT id_asesor, nombre FROM asesor WHERE nombre LIKE ? LIMIT 10");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $resultados[] = [
            'id_asesor' => $row['id_asesor'],
            'nombre' => $row['nombre']
        ];
    }
    $stmt->close();
}

header('Content-Type: application/json');
echo json_encode($resultados);
