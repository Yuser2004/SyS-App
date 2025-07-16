<!-- Modal -->
<div class="modal fade" id="modalAgregarEgreso" tabindex="-1" aria-labelledby="modalAgregarEgresoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content login-form custom-modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalAgregarEgresoLabel">Registrar Egreso</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form action="recibos/guardar_egreso.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
            <?php $recibo_id = $_GET['id'] ?? 0; ?>
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
            <input type="date" name="fecha" id="fecha" class="form-control custom-input" required>
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

