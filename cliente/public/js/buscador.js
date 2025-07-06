function inicializarBuscador() {
    const buscador = document.getElementById('buscador');
    if (!buscador) return;

    const filas = document.querySelectorAll('table tbody tr');

    buscador.addEventListener('input', function () {
        const valorBuscado = this.value.toLowerCase();

        filas.forEach(fila => {
            const inputs = fila.querySelectorAll('input');
            let textoFila = '';
            inputs.forEach(input => textoFila += input.value.toLowerCase() + ' ');
            fila.classList.toggle('oculto', !textoFila.includes(valorBuscado));
            fila.classList.toggle('visible', textoFila.includes(valorBuscado));
        });
    });
}
