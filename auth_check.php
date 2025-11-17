<?php
// auth_check.php - El Guardián
// Este script se incluirá al inicio de CADA PÁGINA protegida.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Comprobar si el usuario está logueado
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    
    // 2. Diferenciar entre una solicitud normal y una solicitud AJAX (fetch)
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

    if ($is_ajax) {
        // Si es AJAX (como api_reporte.php), no redirigir.
        // Enviar un error 401 (No Autorizado) que el JavaScript pueda entender.
        http_response_code(401); 
        echo json_encode(['error' => 'Sesión expirada. Por favor, inicie sesión de nuevo.']);
    } else {
        // Si es una carga de página normal, redirigir al login.
        
        // Construir la URL base de forma dinámica
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        
        // Asumimos que la app está en /SyS-app/
        // Si está en la raíz, cambia esto a ''
        $base_path = '/SyS-app'; 
        
        header('Location: ' . $protocol . '://' . $host . $base_path . '/login.php');
    }
    exit;
}

// Si la sesión existe, simplemente continuamos cargando el script...
?>