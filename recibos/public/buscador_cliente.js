document.getElementById("buscador_cliente").addEventListener("input", async function () {
    const query = this.value.trim();

    if (query.length < 2) {
        document.getElementById("resultados_cliente").innerHTML = "";
        return;
    }

    try {
        const resp = await fetch(`recibos/buscar_cliente.php?q=${encodeURIComponent(query)}`);
        const data = await resp.json();

        const contenedor = document.getElementById("resultados_cliente");
        contenedor.innerHTML = "";

        if (data.length === 0) {
            contenedor.innerHTML = "<div class='item'>No se encontraron clientes</div>";
            return;
        }

        data.forEach(cliente => {
            const div = document.createElement("div");
            div.className = "item";
            div.textContent = cliente.nombre;
            div.onclick = () => {
                document.getElementById("buscador_cliente").value = cliente.nombre;
                document.getElementById("id_cliente").value = cliente.id;
                contenedor.innerHTML = "";
            };
            contenedor.appendChild(div);
        });
    } catch (err) {
        console.error("Error al buscar cliente:", err);
    }
});
