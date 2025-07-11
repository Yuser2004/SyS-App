<?php
include __DIR__ . '/../models/conexion.php';

$nombre = trim($_GET['nombre'] ?? '');
$id = intval($_GET['id'] ?? 0);

if ($nombre === '') {
    echo 'vacio';
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM sedes WHERE nombre = ?" . ($id > 0 ? " AND id != ?" : ""));
if ($id > 0) {
    $stmt->bind_param("si", $nombre, $id);
} else {
    $stmt->bind_param("s", $nombre);
}
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

echo ($res['total'] > 0) ? 'existe' : 'ok';
