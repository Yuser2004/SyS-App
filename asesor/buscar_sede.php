<?php
include __DIR__ . '/models/conexion.php';
require_once __DIR__ . '/../auth_check.php';

$q = $_GET['q'] ?? '';
$q = $conn->real_escape_string($q);

$res = $conn->query("SELECT id, nombre FROM sedes WHERE nombre LIKE '%$q%' LIMIT 10");

$resultados = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $resultados[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($resultados);
