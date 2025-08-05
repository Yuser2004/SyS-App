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