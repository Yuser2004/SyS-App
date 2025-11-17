<?php
session_start();
session_unset();
session_destroy();

// Construir la URL base de forma dinámica
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_path = '/SyS-app'; // Ajusta si es necesario

header('Location: ' . $protocol . '://' . $host . $base_path . '/login.php');
exit;
?>