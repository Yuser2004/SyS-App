<?php
session_start();

// --- ¡IMPORTANTE! ---
// Esta es una verificación SIMPLE. En producción, debes usar una base de datos
// y contraseñas hasheadas con password_hash() y password_verify().

// 1. Define tus usuarios válidos (temporalmente)
$usuarios_validos = [
    'admin' => '123', // Cambia '12345' por una contraseña segura
    'usuario' => '54321'
];

// 2. Obtener datos del formulario
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// 3. Verificar al usuario
if (isset($usuarios_validos[$username]) && $usuarios_validos[$username] === $password) {
    // ¡Éxito! Guardar en la sesión
    $_SESSION['user_logged_in'] = true;
    $_SESSION['username'] = $username;
    
    // Redirigir al index.php
    header("Location: index.php");
    exit;
} else {
    // Error, redirigir de vuelta al login con un mensaje de error
    header("Location: login.php?error=1");
    exit;
}
?>