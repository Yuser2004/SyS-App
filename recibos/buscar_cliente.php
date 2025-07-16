<?php
include __DIR__ . '/models/conexion.php';

$q = $_GET['q'] ?? '';
$stmt = $conn->prepare("SELECT id_cliente, nombre_completo, documento FROM clientes WHERE nombre_completo LIKE CONCAT('%', ?, '%') OR documento LIKE CONCAT('%', ?, '%') LIMIT 10");
$stmt->bind_param("ss", $q, $q);
$stmt->execute();

$result = $stmt->get_result();
$clientes = [];

while ($row = $result->fetch_assoc()) {
    $clientes[] = $row;
}

header('Content-Type: application/json');
echo json_encode($clientes);
