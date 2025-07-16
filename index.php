    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>SyS Aplicación</title>
        
        <!-- Estilos -->
        <link rel="stylesheet" href="css/tabla_estilo.css">
        <link rel="stylesheet" href="css/header.css">
        <link rel="stylesheet" href="css/botones.css">
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">

        <!-- Scripts -->
        <script src="cliente/public/js/buscador.js" defer></script>

        <style>
        /* Layout principal */
        .main-layout {
            display: flex;
            flex-direction: row;
            height: calc(100vh - 300px); /* deja si funciona para ti */
        }
        .menu-lateral {
            width: 250px;
            min-width: 220px;
            background-image: linear-gradient(135deg, #9f05ff 10%, #fd5e08 100%);
            border-right: 1px solid #ccc;
            padding: 10px;
            overflow-y: none;
        }


        /* Responsivo */
        @media (max-width: 768px) {
            .main-layout {
                flex-direction: column;
                height: auto;
            }

            .menu-lateral {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid #ccc;
            }
        }
        </style>
    </head>

    <body>
        <!-- HEADER -->
        <header class="hero-header" id="mainHeader">
            <div class="overlay">
                <h2 class="hero-title">Seguros y Servicios</h2>
                <h3 class="hero-subtitle">Rapidéz y Responsabilidad</h3>
                <br>
            </div>
        </header>


        <!-- CONTENIDO PRINCIPAL: MENÚ + CONTENIDO -->
        <div class="main-layout">
            <!-- MENÚ LATERAL -->
            <nav class="menu-lateral">
                <ul>
                    <li>
                        <span onclick="cargarContenido('recibos/views/lista.php')">
                            <img src="recibo.png" alt="Recibos" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Recibos
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('cliente/views/fragmento_clientes.php')">
                            <img src="cliente.png" alt="Clientes" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Clientes
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">
                            <img src="coche.png" alt="Vehículos" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Vehículos
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('sedes/views/lista_sedes.php')">
                            <img src="hogar.png" alt="Sedes" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Sedes
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('asesor/views/lista_asesor.php')">👤 Asesores</span>
                    </li>
                </ul>
            </nav>

            <!-- CONTENIDO DINÁMICO -->
            <main class="cuerpo" id="contenido">
                <!-- Aquí se cargará dinámicamente el contenido -->
            </main>
        </div>

        <!-- SCRIPT PARA CARGA DINÁMICA -->
        <script>
            function cargarContenido(ruta) {
                fetch(ruta)
                    .then(res => res.text())
                    .then(html => {
                        const contenedor = document.getElementById('contenido');
                        contenedor.innerHTML = html;

                        // Ejecutar scripts embebidos del fragmento
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        const scripts = tempDiv.querySelectorAll('script');
                        scripts.forEach(script => {
                            const nuevoScript = document.createElement('script');
                            if (script.src) {
                                nuevoScript.src = script.src;
                            } else {
                                nuevoScript.textContent = script.textContent;
                            }
                            document.body.appendChild(nuevoScript);
                        });
                    })
                    .catch(error => console.error('Error al cargar contenido:', error));
            }
        </script>

    </body>
    </html>
