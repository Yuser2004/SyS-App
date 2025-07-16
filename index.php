<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SyS Aplicación</title>
    <link rel="stylesheet" href="css/botones.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/tabla_estilo.css">
    <script src="cliente/public/js/buscador.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&display=swap" rel="stylesheet">

</head>
<!-- <header class="hero-header">
    <div class="overlay">
        <h1 class="hero-title">Simply The Best</h1>
        <h3 class="hero-subtitle">Reasons for Choosing US</h3>
        <p class="hero-text">Lorem ipsum dolor sit amet consectetur...</p>
        <br>
        <button class="hero-button">READ MORE</button>
    </div>
</header>
 -->

<body>
    <div class="contenedor-del-contenedor" >
        <div  class="contenedor-menu">
            <ul>
                <li>
                <span onclick="cargarContenido('cliente/views/fragmento_clientes.php')">
                    <img src="cliente.png" alt="Inicio" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">Clientes    
                </span>
                </li>
                <li>
                    <span onclick="cargarContenido('vehiculo/views/lista_vehiculos.php')">
                        <img src="coche.png" alt="Automoviles" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">Vehiculos
                    </span>
                </li>
                <li>
                    <span onclick="cargarContenido('sedes/views/lista_sedes.php')">
                        <img src="hogar.png" alt="Sedes" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">Sedes
                    </span>
                </li>
                <li onclick="cargarContenido('asesor/views/lista_asesor.php')">
                    <span>👤 Asesores</span>
                </li>
                <li>
                    <span onclick="cargarContenido('recibos/views/lista.php')">
                        <img src="recibo.png" alt="Recibos" style="width: 40px; height: 40px; margin-right: 8px; vertical-align: middle;">
                        Recibos
                    </span>
                </li>
            </ul>
        </div>
    </div>


    <div class="cuerpo" id="contenido">
        <!-- Aquí se cargará dinámicamente el contenido -->
    </div>

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
