document.getElementById("buscador_asesor").addEventListener("input", async function () {
    const query = this.value.trim();

    if (query.length < 2) {
        document.getElementById("resultados_asesor").innerHTML = "";
        return;
    }

    try {
        const resp = await fetch(`recibos/buscar_asesor.php?q=${encodeURIComponent(query)}`);
        const data = await resp.json();

        const contenedor = document.getElementById("resultados_asesor");
        contenedor.innerHTML = "";

        if (data.length === 0) {
            contenedor.innerHTML = "<div class='item'>No se encontraron asesores</div>";
            return;
        }

        data.forEach(asesor => {
            const div = document.createElement("div");
            div.className = "item";
            div.textContent = asesor.nombre;
            div.onclick = () => {
                document.getElementById("buscador_asesor").value = asesor.nombre;
                document.getElementById("id_asesor").value = asesor.id_asesor;
                contenedor.innerHTML = "";
            };
            contenedor.appendChild(div);
        });
    } catch (err) {
        console.error("Error al buscar asesor:", err);
    }
});
