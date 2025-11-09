<style>
    #gasto-sede-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    #gasto-sede-modal-content {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        position: relative;
    }
    #gasto-sede-modal-content h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    #gasto-sede-modal-close {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
        color: #888;
    }
    #form-gasto-sede .form-group {
        margin-bottom: 15px;
    }
    #form-gasto-sede .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    #form-gasto-sede .form-group input[type="text"],
    #form-gasto-sede .form-group input[type="number"],
    #form-gasto-sede .form-group input[type="date"],
    #form-gasto-sede .form-group select,
    #form-gasto-sede .form-group input[type="file"] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box; /* Importante */
    }
    #asesor-search-container {
        position: relative;
    }
    #asesor-gasto-resultados {
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background: #fff;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 4px 4px;
        max-height: 150px;
        overflow-y: auto;
        z-index: 1001;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
    #asesor-gasto-resultados div {
        padding: 10px;
        cursor: pointer;
    }
    #asesor-gasto-resultados div:hover {
        background: #f0f0f0;
    }
    #form-gasto-sede button {
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    #form-gasto-sede button:hover {
        background-color: #0056b3;
    }
</style>

<div id="gasto-sede-modal-overlay">
    <div id="gasto-sede-modal-content">
        <span id="gasto-sede-modal-close">&times;</span>
        <h2>Registrar Gasto</h2>
        
        <form id="form-gasto-sede" enctype="multipart/form-data">
            
            <input type="hidden" id="gasto-id-sede" name="id_sede">
            
            <!-- Este campo es la "etiqueta" que querías -->
            <div class="form-group">
                <label for="gasto-tipo">Tipo de Gasto</label>
                <select id="gasto-tipo" name="tipo_gasto" required>
                    <!-- 'sede' y 'personal' se guardarán en la nueva columna -->
                    <option value="sede" selected>Gasto de Sede (Operativo)</option> 
                    <option value="personal">Gasto Personal (Asesor)</option>
                </select>
            </div>

            <div class="form-group">
                <label for="gasto-descripcion">Descripción del Gasto</label>
                <input type="text" id="gasto-descripcion" name="descripcion" required>
            </div>
            
            <div class="form-group">
                <label for="gasto-monto">Monto</label>
                <input type="number" id="gasto-monto" name="monto" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="gasto-metodo-pago">Método de Pago</label>
                <select id="gasto-metodo-pago" name="metodo_pago" required>
                    <option value="" disabled selected hidden>Seleccione...</option> 
                    <option value="efectivo">Efectivo</option>
                    <option value="transferencia">Transferencia</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
            </div>

            <div class="form-group" id="gasto-detalle-pago-container" style="display: none;">
                <label for="gasto-detalle-pago">Cuenta de Origen (De dónde salió)</label>
                <select id="gasto-detalle-pago" name="detalle_pago"> 
                    <option value="" disabled selected hidden>Selecciona una cuenta</option>
                    
                    <?php // Usamos la variable $lista_cuentas de reporte.php
                    if (isset($lista_cuentas) && !empty($lista_cuentas)): ?>
                        <?php foreach ($lista_cuentas as $cuenta): ?>
                            <option 
                                class="<?= htmlspecialchars($cuenta['clase_css']) ?>" 
                                value="<?= htmlspecialchars($cuenta['nombre_cuenta']) ?>">
                                <?= htmlspecialchars($cuenta['nombre_cuenta']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>No hay cuentas activas</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="gasto-fecha">Fecha del Gasto</label>
                <input type="date" id="gasto-fecha" name="fecha" required value="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="form-group" id="asesor-search-container">
                <label for="buscar-asesor-gasto">Asesor que Registra</label>
                <input type="text" id="buscar-asesor-gasto" autocomplete="off" placeholder="Escribe para buscar...">
                <input type="hidden" id="id-asesor-gasto" name="id_asesor" required>
                <div id="asesor-gasto-resultados"></div>
            </div>

            <div class="form-group">
                <label for="gasto-comprobante">Comprobante (PDF, JPG, PNG)</label>
                <input type="file" id="gasto-comprobante" name="comprobante" accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <button type="submit" id="btn-guardar-gasto">Guardar Gasto</button>
        </form>
    </div>
</div>