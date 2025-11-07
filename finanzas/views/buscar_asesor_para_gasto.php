<?php
// finanzas/views/buscar_asesor_para_gasto.php
header('Content-Type: application/json');
include __DIR__ . '/../models/conexion.php';

$term = $_GET['term'] ?? '';
$sede_id = $_GET['sede_id'] ?? 0; // Recibimos la sede_id

if (empty($term) || empty($sede_id)) {
    echo json_encode([]);
    exit;
}

$term_like = "%" . $term . "%";
$sede_id_int = (int)$sede_id;

// Asegúrate que las columnas 'id_asesor', 'nombre' y 'id_sede' existan en tu tabla 'asesor'
$sql = "SELECT id_asesor, nombre 
        FROM asesor 
        WHERE nombre LIKE ? AND id_sede = ?
        LIMIT 10";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $term_like, $sede_id_int);
$stmt->execute();
$result = $stmt->get_result();
$asesores = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

echo json_encode($asesores);
exit();