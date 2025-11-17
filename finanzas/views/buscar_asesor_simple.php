<?php
// finanzas/views/buscar_asesor_simple.php
// API simple para buscar asesores sin filtro de sede

// Reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php'; // ../models/conexion.php

// Verificar que $conn existe después del include
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión a la BD: ' . ($conn->connect_error ?? 'Variable $conn no definida')]);
    exit;
}

$term = $_GET['term'] ?? '';

if (empty($term)) {
    echo json_encode([]);
    exit;
}

$term_like = "%" . $term . "%";

// Búsqueda simple por nombre o documento
$sql = "SELECT id_asesor, nombre, documento 
        FROM asesor 
        WHERE nombre LIKE ? OR documento LIKE ?
        LIMIT 10";

$stmt = $conn->prepare($sql);

// Verificar si la preparación de la consulta falló
if ($stmt === false) {
    echo json_encode(['error' => 'Error en la consulta SQL: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ss", $term_like, $term_like);
$stmt->execute();
$result = $stmt->get_result();
$asesores = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// Devolvemos el JSON, incluso si está vacío (ej. [])
echo json_encode($asesores);
exit();
?>