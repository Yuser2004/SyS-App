<?php
session_start();
// Si el usuario ya está logueado, redirigirlo al index.
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: index.php");
    exit;
}
$error = $_GET['error'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - SyS-app</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            /* 1. ¡FONDO MODIFICADO! Gradiente azulado más profesional */
            background: linear-gradient(135deg, #f0f7ff 0%, #d9e8ff 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            color: #333;
        }

        /* 2. Tarjeta de Login (mejorada) */
        .login-container {
            background-color: #ffffff;
            padding: 2.5rem 3rem;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border-top: 5px solid #007bff; /* Toque de color */
        }

        /* 3. Header con el logo de la "empresa" */
        .login-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 2rem;
        }
        .login-header svg { /* El icono SVG del escudo */
            width: 50px;
            height: 50px;
            fill: #007bff;
            margin-bottom: 0.5rem;
        }
        .login-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
        }

        /* 4. Formulario Profesional (con iconos) */
        .input-group {
            margin-bottom: 1.5rem;
            text-align: left;
        }
        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }
        
        /* Contenedor para el icono y el input */
        .input-field {
            position: relative;
        }
        
        /* Estilo de los iconos izquierdos (usuario, candado) */
        .input-field .icon-left {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            fill: #aaa;
            transition: fill 0.2s ease;
        }
        
        /* ¡NUEVO! Estilo del icono derecho (ojo) */
        .input-field .icon-right {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px; /* Un poco más grande para que sea fácil de presionar */
            height: 18px;
            fill: #aaa;
            cursor: pointer;
            transition: fill 0.2s ease;
        }
        .input-field .icon-right:hover {
            fill: #555;
        }
        
        /* ¡INPUT MODIFICADO! Padding en AMBOS lados para los iconos */
        .input-field input {
            width: 100%;
            padding: 0.8rem 40px 0.8rem 40px; /* 40px en izq y der */
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box; 
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        /* Efecto de Foco: resalta el borde y el icono izquierdo */
        .input-field:focus-within .icon-left {
            fill: #007bff;
        }
        .input-field input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        /* 5. Botón de Login (mejorado) */
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            border: none;
            border-radius: 8px;
            background-color: #007bff;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }
        .btn-login:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 123, 255, 0.4);
        }
        .btn-login:active {
            transform: translateY(0);
        }

        /* 6. Mensaje de Error (sin cambios, ya estaba bien) */
        .error-message {
            background-color: #fbebed;
            color: #dc3545;
            border: 1px solid #f5c6cb;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        
        <div class="login-header">
            <!-- Icono SVG de Escudo (logo) -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2.016c-3.31 0-6 2.69-6 6v3c0 4.42 4.4 7.29 5.42 7.78.32.15.68.15 1.01 0C13.6 18.3 18 15.42 18 11.016v-3c0-3.31-2.69-6-6-6zm0 1.5c2.49 0 4.5 2.01 4.5 4.5v3c0 3.32-3.34 5.56-4.32 5.96-.06.03-.12.03-.18.03s-.12 0-.18-.03C10.84 16.58 7.5 14.34 7.5 11.016v-3c0-2.49 2.01-4.5 4.5-4.5z"/></svg>
            <h2>Bienvenido a SyS</h2>
        </div>
        
        <?php if ($error === '1'): ?>
            <div class="error-message">
                Usuario o contraseña incorrectos.
            </div>
        <?php endif; ?>

        <form action="login_process.php" method="POST">
            <div class="input-group">
                <label for="username">Usuario</label>
                <div class="input-field">
                    <!-- Icono SVG de Usuario -->
                    <svg class="icon-left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <input type="text" id="username" name="username" required>
                </div>
            </div>
            <div class="input-group">
                <label for="password">Contraseña</label>
                <div class="input-field">
                    <!-- Icono SVG de Candado -->
                    <svg class="icon-left" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zM9 8V6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9z"/></svg>
                    <input type="password" id="password" name="password" required>
                    <!-- ¡NUEVO! Icono de Ojo para mostrar/ocultar contraseña -->
                    <svg id="toggle-password" class="icon-right" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 9c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm0-5c-.83 0-1.5.67-1.5 1.5S11.17 12 12 12s1.5-.67 1.5-1.5S12.83 8.5 12 8.5z"/>
                    </svg>
                </div>
            </div>
            <button type="submit" class="btn-login">Ingresar</button>
        </form>
    </div>

<!-- ========================================================== -->
<!-- ¡NUEVO SCRIPT! Para la funcionalidad de "mostrar contraseña" -->
<!-- ========================================================== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        // SVG Path para "ojo tachado"
        const eyeOffPath = "M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L21.73 23 23 21.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z";
        // SVG Path para "ojo normal"
        const eyeOnPath = "M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5C21.27 7.61 17 4.5 12 4.5zm0 9c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm0-5c-.83 0-1.5.67-1.5 1.5S11.17 12 12 12s1.5-.67 1.5-1.5S12.83 8.5 12 8.5z";

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                // Comprobar el tipo de input
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Cambiar el icono SVG
                if (type === 'text') {
                    // Si es texto, mostrar "ojo tachado"
                    togglePassword.querySelector('path').setAttribute('d', eyeOffPath);
                } else {
                    // Si es contraseña, mostrar "ojo normal"
                    togglePassword.querySelector('path').setAttribute('d', eyeOnPath);
                }
            });
        }
    });
</script>

</body>
</html>