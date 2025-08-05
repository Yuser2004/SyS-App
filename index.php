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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
            min-height: 600px;
            min-width: 220px;
            background-color: #01458a;
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
        <header class="hero-header" id="mainHeader">
            <div class="overlay">
                <img src="header.jpeg" alt="Logo de Seguros y Servicios" class="hero-image">
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
                        <span onclick="cargarContenido('asesor/views/lista_asesor.php')">
                            <img src="asesorlogo.png" alt="Asesor" style="width: 60px; height: 40px; margin-right: 8px; vertical-align: middle;">Asesores
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('finanzas/views/reporte.php')">
                            <img src="finanzalogo.png" alt="Finanzas" style="width: 60px; height: 40px; margin-right: 8px; vertical-align: middle;">Finanzas
                        </span>
                    </li>
                    <li>
                        <span onclick="cargarContenido('finanzas/views/caja_diaria.php')">
                            <img src="caja-diaria.png" alt="Caja Diaria" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                            Caja Diaria
                        </span>
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
            // 1. LIMPIA los scripts de la vista anterior.
            // Busca todos los scripts que hemos añadido dinámicamente y los elimina.
            document.querySelectorAll('script[data-vista-dinamica]').forEach(script => {
                script.remove();
            });

            fetch(ruta)
                .then(res => {
                    if (!res.ok) {
                        throw new Error(`Error HTTP! estado: ${res.status}`);
                    }
                    return res.text();
                })
                .then(html => {
                    const contenedor = document.getElementById('contenido');
                    contenedor.innerHTML = html;

                    // 2. AÑADE y ejecuta los scripts de la nueva vista.
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    
                    tempDiv.querySelectorAll('script').forEach(scriptViejo => {
                        const nuevoScript = document.createElement('script');
                        
                        // Le ponemos una 'etiqueta' para poder encontrarlo y borrarlo después.
                        nuevoScript.setAttribute('data-vista-dinamica', 'true');
                        
                        // Copiamos el contenido del script.
                        nuevoScript.textContent = scriptViejo.textContent;
                        
                        // Lo añadimos al final del body para que se ejecute.
                        document.body.appendChild(nuevoScript);
                    });
                })
                .catch(error => console.error('Error al cargar contenido:', error));
        }

document.addEventListener('change', function(event) {
    // Verificamos si el elemento que cambió es nuestro select 'tipo_egreso'
    if (event.target && event.target.id === 'tipo_egreso') {
        
        const tipoEgresoSelect = event.target;
        const camposPrestamoDiv = document.getElementById('campos_prestamo_div');
        const selectSedeOrigen = document.getElementById('sede_origen_id');

        if (!camposPrestamoDiv || !selectSedeOrigen) {
            return; // Salimos si no se encuentran los elementos
        }

        if (tipoEgresoSelect.value === 'prestamo') {
            camposPrestamoDiv.style.display = 'block';
            selectSedeOrigen.required = true; // El origen es obligatorio
        } else {
            camposPrestamoDiv.style.display = 'none';
            selectSedeOrigen.required = false; // Ya no es obligatorio
            selectSedeOrigen.value = ''; // Limpiar el valor
        }
    }
});

        // Carga inicial al abrir la aplicación por primera vez
        document.addEventListener('DOMContentLoaded', function() {
            cargarContenido('recibos/views/lista.php'); // O la vista que prefieras por defecto
        });
        </script>
     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>