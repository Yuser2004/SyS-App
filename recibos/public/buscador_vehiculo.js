document.getElementById("buscador_vehiculo").addEventListener("input", async function () {
    const query = this.value.trim();

    if (query.length < 2) {
        document.getElementById("resultados_vehiculo").innerHTML = "";
        return;
    }

    try {
        const resp = await fetch(`recibos/buscar_vehiculo.php?q=${encodeURIComponent(query)}`);
        const data = await resp.json();

        const contenedor = document.getElementById("resultados_vehiculo");
        contenedor.innerHTML = "";

        if (data.length === 0) {
            contenedor.innerHTML = "<div class='item'>No se encontraron vehículos</div>";
            return;
        }

        data.forEach(item => {
            const div = document.createElement("div");
            div.className = "item";
            div.textContent = item.descripcion;
            div.onclick = () => {
                document.getElementById("buscador_vehiculo").value = item.descripcion;
                document.getElementById("id_vehiculo").value = item.id_vehiculo;
                contenedor.innerHTML = "";
            };
            contenedor.appendChild(div);
        });
    } catch (err) {
        console.error("Error al buscar vehículo:", err);
    }
});
