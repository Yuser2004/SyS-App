<style>
    /* Estilos del modal (sin cambios) */
    #movimiento-sede-modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.6); display: none;
        justify-content: center; align-items: center; z-index: 1050;
    }
    #movimiento-sede-modal-content {
        background: #fff; padding: 25px; border-radius: 8px;
        width: 90%; max-width: 600px; box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        position: relative; max-height: 90vh; overflow-y: auto;
    }
    #movimiento-sede-modal-content h2 { 
        margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 15px; 
    }
    #movimiento-sede-modal-close {
        position: absolute; top: 15px; right: 20px;
        font-size: 28px; font-weight: bold; cursor: pointer; color: #888;
    }
    #form-movimiento-sede .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    #form-movimiento-sede .form-group { margin-bottom: 15px; }
    #form-movimiento-sede .form-group.full-width { grid-column: 1 / -1; }
    #form-movimiento-sede .form-group label { 
        display: block; margin-bottom: 5px; font-weight: 600; 
    }
    #form-movimiento-sede .form-group input[type="text"],
    #form-movimiento-sede .form-group input[type="number"],
    #form-movimiento-sede .form-group input[type="datetime-local"],
    #form-movimiento-sede .form-group select,
    #form-movimiento-sede .form-group textarea,
    #form-movimiento-sede .form-group input[type="file"] {
        width: 100%; padding: 8px; border: 1px solid #ccc;
        border-radius: 4px; box-sizing: border-box;
    }
    #form-movimiento-sede textarea { min-height: 80px; }
    #form-movimiento-sede fieldset {
        border: 1px solid #007bff; border-radius: 8px; padding: 15px;
        margin-bottom: 15px;
    }
    #form-movimiento-sede fieldset legend {
        font-weight: bold; color: #007bff; padding: 0 10px;
        font-size: 1.1em;
    }
    #form-movimiento-sede button {
        background-color: #007bff; color: white; padding: 12px 20px;
        border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        width: 100%; font-weight: bold;
    }
    #form-movimiento-sede button:hover { background-color: #0056b3; }

    /* Búsqueda de asesor (sin cambios) */
    #asesor-movimiento-search-container { position: relative; }
    #asesor-movimiento-resultados {
        position: absolute; top: 100%; left: 0; width: 100%;
        background: #fff; border: 1px solid #ddd; border-top: none;
        border-radius: 0 0 4px 4px; max-height: 150px;
        overflow-y: auto; z-index: 1051; box-shadow: 0 3px 6px rgba(0,0,0,0.1);
        display: none;
    }
    #asesor-movimiento-resultados div { padding: 10px; cursor: pointer; }
    #asesor-movimiento-resultados div:hover { background: #f0f0f0; }

    @media (max-width: 600px) {
        #form-movimiento-sede .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div id="movimiento-sede-modal-overlay">
    <div id="movimiento-sede-modal-content">
        <span id="movimiento-sede-modal-close">&times;</span>
        <h2>Registrar Movimiento entre Sedes</h2>
        
        <form id="form-movimiento-sede" enctype="multipart/form-data">
            
            <div class="form-grid">
                <fieldset>
                    <legend>Origen (Desde)</legend>
                    <div class="form-group">
                        <label for="mov-id-sede-origen">Sede Origen</label>
                        <select id="mov-id-sede-origen" name="id_sede_origen" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <?php
                            // Re-usamos la consulta de sedes del archivo principal
                            if (isset($sedes_result_para_movimiento) && $sedes_result_para_movimiento->num_rows > 0) {
                                $sedes_result_para_movimiento->data_seek(0); // Reiniciar puntero
                                while($sede_mov = $sedes_result_para_movimiento->fetch_assoc()) {
                                    echo '<option value="' . $sede_mov['id'] . '">' . htmlspecialchars($sede_mov['nombre']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mov-metodo-pago-origen">Método de Salida</label>
                        <select id="mov-metodo-pago-origen" name="metodo_pago_origen" required>
                            <option value="" disabled selected>Seleccione método...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <input type="hidden" id="mov-detalle-pago-origen" name="detalle_pago_origen">
                    <div class="form-group" id="grupo-cuenta-origen" style="display: none;">
                        <label for="mov-cuenta-origen">Cuenta de Origen</label>
                        <select id="mov-cuenta-origen">
                            <option value="" disabled selected>Seleccione cuenta...</option>
                            <?php foreach ($cuentas_para_modal as $cuenta): ?>
                                <option value="<?= $cuenta['id'] ?>">
                                    <?= htmlspecialchars($cuenta['nombre_cuenta']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    </fieldset>

                <fieldset>
                    <legend>Destino (Hacia)</legend>
                    <div class="form-group">
                        <label for="mov-id-sede-destino">Sede Destino</label>
                        <select id="mov-id-sede-destino" name="id_sede_destino" required>
                            <option value="" disabled selected>Seleccione...</option>
                            <?php
                            // Re-usamos la consulta de sedes
                            if (isset($sedes_result_para_movimiento) && $sedes_result_para_movimiento->num_rows > 0) {
                                $sedes_result_para_movimiento->data_seek(0); // Reiniciar puntero
                                while($sede_mov = $sedes_result_para_movimiento->fetch_assoc()) {
                                    echo '<option value="' . $sede_mov['id'] . '">' . htmlspecialchars($sede_mov['nombre']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="mov-metodo-pago-destino">Método de Entrada</label>
                        <select id="mov-metodo-pago-destino" name="metodo_pago_destino" required>
                            <option value="" disabled selected>Seleccione método...</option>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>

                    <input type="hidden" id="mov-detalle-pago-destino" name="detalle_pago_destino">
                    <div class="form-group" id="grupo-cuenta-destino" style="display: none;">
                        <label for="mov-cuenta-destino">Cuenta de Destino</label>
                        <select id="mov-cuenta-destino">
                            <option value="" disabled selected>Seleccione cuenta...</option>
                            <?php foreach ($cuentas_para_modal as $cuenta): ?>
                                <option value="<?= $cuenta['id'] ?>">
                                    <?= htmlspecialchars($cuenta['nombre_cuenta']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    </fieldset>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="mov-monto">Monto</label>
                    <input type="number" id="mov-monto" name="monto" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="mov-fecha">Fecha y Hora</label>
                    <input type="datetime-local" id="mov-fecha" name="fecha" required 
                           value="<?php echo date('Y-m-d\TH:i'); ?>">
                </div>
            </div>

            <div class="form-group full-width" id="asesor-movimiento-search-container">
                <label for="buscar-asesor-movimiento">Asesor que Registra</label>
                <input type="text" id="buscar-asesor-movimiento" autocomplete="off" placeholder="Escribe para buscar asesor...">
                <input type="hidden" id="id-asesor-movimiento" name="id_asesor_registra" required>
                <div id="asesor-movimiento-resultados"></div>
            </div>

            <div class="form-group full-width">
                <label for="mov-descripcion">Descripción</label>
                <textarea id="mov-descripcion" name="descripcion" placeholder="Ej: Préstamo para base de caja Sede..." required></textarea>
            </div>

            <div class="form-group full-width">
                <label for="mov-comprobante">Comprobante (Opcional)</label>
                <input type="file" id="mov-comprobante" name="comprobante" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <button type="submit" id="btn-guardar-movimiento">Guardar Movimiento</button>
        </form>
    </div>
</div>