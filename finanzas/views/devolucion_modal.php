<?php
// Este archivo solo necesita la conexión para listar las sedes
include __DIR__ . '/../models/conexion.php';
?>

<div class="modal fade" id="modalDevolucionPrestamo" tabindex="-1" aria-labelledby="modalDevolucionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content login-form custom-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDevolucionLabel">Registrar Devolución de Préstamo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="finanzas/views/guardar_devolucion.php" method="POST">
                <div class="modal-body">

                    <div class="form-group mb-3">
                        <label for="id_sede_receptora">Sede que RECIBE el dinero</label>
                        <select name="id_sede_receptora" class="form-control custom-input" required>
                            <option value="">Seleccione...</option>
                            <?php
                                $sedes_result = $conn->query("SELECT id, nombre FROM sedes ORDER BY nombre");
                                while($sede = $sedes_result->fetch_assoc()) {
                                    echo "<option value='{$sede['id']}'>{$sede['nombre']}</option>";
                                }
                                $sedes_result->data_seek(0); // Reinicia el puntero para el siguiente select
                            ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="id_sede_origen">Sede que DEVUELVE el dinero</label>
                        <select name="id_sede_origen" class="form-control custom-input" required>
                            <option value="">Seleccione...</option>
                            <?php
                                while($sede = $sedes_result->fetch_assoc()) {
                                    echo "<option value='{$sede['id']}'>{$sede['nombre']}</option>";
                                }
                            ?>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="monto">Monto Devuelto</label>
                        <input type="number" step="0.01" name="monto" class="form-control custom-input" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="metodo_pago">Método de Devolución</label>
                            <select name="metodo_pago" class="form-control custom-input" required>
                                <option value="">Seleccione...</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="otro">Otro</option>
                            </select>
                    </div>

                    <div class="form-group mb-3">
                        <label for="fecha">Fecha de Devolución</label>
                        <input type="date" name="fecha" class="form-control custom-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="concepto">Concepto / Notas (opcional)</label>
                        <textarea name="concepto" class="form-control custom-input" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn custom-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn custom-btn-primary">Guardar Devolución</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
/*
  Script que intercepta el submit del form del modal,
  hace fetch() y espera JSON. Muestra alert, cierra modal,
  resetea el form y refresca la vista de caja.
  Ejecuta inmediatamente (no depende de DOMContentLoaded).
*/
(function() {
    const modalForm = document.querySelector('#modalDevolucionPrestamo form');
    if (!modalForm) return;

    modalForm.addEventListener('submit', async function(event) {
        event.preventDefault(); // IMPRESCINDIBLE: evita que el navegador navegue a la URL del action

        const submitBtn = modalForm.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : null;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Guardando...';
        }

        try {
            const resp = await fetch(modalForm.action, {
                method: 'POST',
                body: new FormData(modalForm),
                credentials: 'same-origin'
            });

            // Intentamos parsear JSON (si no es JSON, entrará al catch)
            const data = await resp.json();

            if (resp.ok && data && data.status === 'ok') {
                // Mostrar alert (estado)
                alert(data.message || 'Devolución registrada correctamente.');

                // Cerrar modal (Bootstrap 5)
                const modalEl = document.getElementById('modalDevolucionPrestamo');
                const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modalInstance.hide();

                // Limpiar formulario
                modalForm.reset();

                // Refrescar caja diaria:
                // si existe la función actualizarCajaDiaria(), la llamamos;
                // si no, usamos cargarContenido('finanzas/views/caja_diaria.php');
                if (typeof actualizarCajaDiaria === 'function') {
                    actualizarCajaDiaria();
                } else if (typeof cargarContenido === 'function') {
                    cargarContenido('finanzas/views/caja_diaria.php');
                }

            } else {
                // Mostrar mensaje de error devuelto por PHP (o genérico)
                const msg = (data && data.message) ? data.message : 'Error al guardar la devolución.';
                alert(msg);
            }
        } catch (err) {
            console.error('Error en fetch/save devolucion:', err);
            alert('Error de red o servidor: ' + (err.message || err));
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        }
    });
})();
</script>
