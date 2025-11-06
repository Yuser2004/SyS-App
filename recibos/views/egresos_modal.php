<div class="modal fade" id="modalAgregarEgreso" tabindex="-1" aria-labelledby="modalAgregarEgresoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content login-form custom-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarEgresoLabel">Registrar Egreso del Servicio</h5> <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="recibos/guardar_egreso.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php 
                        include_once __DIR__ . '/../models/conexion.php'; // Usar include_once por si acaso
                        $recibo_id = $_GET['id'] ?? 0;

                        // --- Cargar la lista de cuentas bancarias ---
                        $lista_cuentas = [];
                        if ($conn) {
                            $cuentas_result = $conn->query("
                                SELECT nombre_cuenta, clase_css 
                                FROM cuentas_bancarias 
                                WHERE activa = 1 
                                ORDER BY nombre_cuenta ASC
                            ");
                            if ($cuentas_result) {
                                while ($fila = $cuentas_result->fetch_assoc()) {
                                    $lista_cuentas[] = $fila;
                                }
                            }
                        }
                    ?>
                    
                    <input type="hidden" name="recibo_id" value="<?= $recibo_id ?>">
                    <input type="hidden" name="tipo_egreso" value="servicio">


                    <div class="form-group mb-3">
                        <label for="descripcion">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control custom-input" rows="3" required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="monto_egreso">Monto</label> 
                        <input type="number" step="0.01" name="monto" id="monto_egreso" class="form-control custom-input" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="forma_pago_egreso">Forma de pago</label> 
                        <select name="forma_pago" id="forma_pago_egreso" class="form-control custom-input" required> 
                            <option value="">Seleccione...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <div class="form-input-material" id="detalle_pago_container_egreso" style="display: none;"> 
                        <label for="detalle_pago_egreso">Cuenta de Origen (De dónde salió)</label> 
                        <select name="detalle_pago" id="detalle_pago_egreso"> 
                            <option value="" disabled selected hidden>Selecciona una cuenta</option>
                            
                            <?php foreach ($lista_cuentas as $cuenta): ?>
                                <option 
                                    class="<?= htmlspecialchars($cuenta['clase_css']) ?>" 
                                    value="<?= htmlspecialchars($cuenta['nombre_cuenta']) ?>">
                                    <?= htmlspecialchars($cuenta['nombre_cuenta']) ?>
                                </option>
                            <?php endforeach; ?>
                            
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="comprobante_pdf">Comprobante PDF (opcional)</label>
                        <input type="file" name="comprobante_pdf" id="comprobante_pdf" class="form-control custom-input" accept="application/pdf">
                    </div>
                    <div class="form-group mb-3">
                        <label for="fecha">Fecha</label>
                        <input type="date" name="fecha" id="fecha" class="form-control custom-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>

                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn custom-btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn custom-btn-primary">Guardar Egreso</button>
                </div>
            </form>

            <script>
                (function() {
                    // Lógica para mostrar/ocultar el detalle de pago (cuentas)
                    const metodoPagoSelect = document.getElementById('forma_pago_egreso');
                    const detallePagoContainer = document.getElementById('detalle_pago_container_egreso');
                    const detallePagoSelect = document.getElementById('detalle_pago_egreso');

                    if (metodoPagoSelect && detallePagoContainer && detallePagoSelect) {
                        metodoPagoSelect.addEventListener('change', function() {
                            let esTransferencia = this.value === 'transferencia';
                            detallePagoContainer.style.display = esTransferencia ? 'block' : 'none';
                            // Hacemos el select de cuenta requerido SÓLO si es transferencia
                            detallePagoSelect.required = esTransferencia; 
                            
                            if (!esTransferencia) {
                                detallePagoSelect.value = ''; // Limpiar si no es transferencia
                            }
                        });
                    }
                })();
            </script>
            </div>
    </div>
</div>