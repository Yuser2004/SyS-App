<div class="modal fade" id="modalAgregarEgreso" tabindex="-1" aria-labelledby="modalAgregarEgresoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content login-form custom-modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarEgresoLabel">Registrar Egreso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form action="recibos/guardar_egreso.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <?php 
                        include __DIR__ . '/../models/conexion.php';
                        $recibo_id = $_GET['id'] ?? 0;

                        // --- NUEVO: OBTENER DATOS DE LA SEDE DESTINO PARA MOSTRARLA ---
                        $nombre_sede_destino = "No disponible";
                        if ($recibo_id && $conn) {
                            $stmt_destino_info = $conn->prepare(
                                "SELECT s.nombre FROM sedes s
                                 JOIN asesor a ON s.id = a.id_sede
                                 JOIN recibos r ON a.id_asesor = r.id_asesor
                                 WHERE r.id = ?"
                            );
                            $stmt_destino_info->bind_param("i", $recibo_id);
                            $stmt_destino_info->execute();
                            $resultado_info = $stmt_destino_info->get_result();
                            if ($fila_info = $resultado_info->fetch_assoc()) {
                                $nombre_sede_destino = $fila_info['nombre'];
                            }
                            $stmt_destino_info->close();
                        }
                    ?>
                    <input type="hidden" name="recibo_id" value="<?= $recibo_id ?>">

                    <div class="form-group mb-3">
                        <label for="descripcion">Descripción</label>
                        <textarea name="descripcion" id="descripcion" class="form-control custom-input" rows="3" required></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="monto">Monto</label>
                        <input type="number" step="0.01" name="monto" id="monto" class="form-control custom-input" required>
                    </div>

                    <div class="form-group mb-3">
                        <label for="tipo_egreso">Tipo de Egreso</label>
                        <select id="tipo_egreso" name="tipo_egreso" class="form-control custom-input" required>
                            <option value="servicio" selected>Egreso del Servicio</option>
                            <option value="prestamo">Préstamo a otra Sede</option>
                        </select>
                    </div>

                    <div id="campos_prestamo_div" style="display:none;">
                        <div class="form-group mb-3">
                            <label for="sede_origen_id">Sede Origen (De dónde sale el dinero)</label>
                            <select id="sede_origen_id" name="sede_origen_id" class="form-control custom-input">
                                <option value="">Seleccione una sede...</option>
                                <?php
                                    $sql_sedes = "SELECT id, nombre FROM sedes ORDER BY nombre";
                                    if ($conn && $resultado_sedes = $conn->query($sql_sedes)) {
                                        while($sede = $resultado_sedes->fetch_assoc()) {
                                           echo "<option value='" . htmlspecialchars($sede['id']) . "'>" . htmlspecialchars($sede['nombre']) . "</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                             <label>Sede Destino (A quien se le hace el favor)</label>
                             <input type="text" class="form-control custom-input" value="<?= htmlspecialchars($nombre_sede_destino) ?>" readonly>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="forma_pago">Forma de pago</label>
                        <select name="forma_pago" id="forma_pago" class="form-control custom-input" required>
                            <option value="">Seleccione...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
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
        </div>
    </div>
</div>