<div id="modalEnviarPreOrden" class="alm-modal">
    <div class="alm-modal-content"
        style="max-width: 1720px; width: 97vw; max-height: 96vh; height: 95vh; display: flex; flex-direction: column; margin: auto;">
        <div class="alm-modal-header" style="padding: 0.9em 2.2em;">
            <div class="div-cerrar">
                <button type="button" class="btn-cerrar" onclick="cerrarModalEnviarPreOrden()">
                    <img class="img-cerrar" src="{{ asset('images/cerrar.png') }}" style="width: 36px !important; height: 36px !important;">
                </button>
            </div>
            <h3>Enviar Pre-Orden por Correo</h3>
            <p id="env-po-modal-subtitle"
                class="lib-modal-subtitle alm-color-bae6fd alm-font-size-0-88em alm-margin-top-2px alm-margin-bottom-0">
            </p>
        </div>
        <div class="alm-modal-body alm-padding-1em-1-6em-1-2em-1-6em"
            style="flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0;">
            <form id="formEnviarPreOrden" enctype="multipart/form-data"
                style="display: flex; flex-direction: column; flex: 1; min-height: 0;"
                data-email-modelo="{{ env('EMAIL_PROVEEDOR_MODELOS', 'produccion@ssmetalf.mx,asistenteprod@ssmetalf.mx') }}"
                data-email-casting="{{ env('EMAIL_PRODUCCION_SS', 'produccion@ssmetalf.mx,laboratorio@ssmetalf.mx') }}"
                data-email-calidad="{{ env('EMAIL_CALIDAD', 'inspecciontec@grupoindsaavedra.com') }}"
                data-email-jacarandas="{{ env('EMAIL_PRODUCCION_JACARANDAS', 'ventas_jacarandas@prodigy.net.mx,requisicionestec@grupoindsaavedra.com') }}">
                <input type="hidden" id="env-ot" name="ot">
                <input type="hidden" id="env-tipo" name="tipo" value="modelo">

                <div
                    style="display: grid; grid-template-columns: minmax(360px, 1fr) minmax(600px, 1.55fr); gap: 18px; align-items: stretch; flex: 1; min-height: 0;">

                    <!-- Columna Izquierda: Información de Envío + Botón Verde de Selección -->
                    <div style="display: flex; flex-direction: column; gap: 12px; min-height: 0;">

                        <!-- Bloque 1: Formulario de Envío -->
                        <div
                            style="background: #fff; padding: 14px 16px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                            <h4
                                style="margin-top: 0; margin-bottom: 8px; color: #033966; font-size: 1em; border-bottom: 2px solid #033966; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px;">
                                <img src="{{ asset('images/enviando.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Información del Envío
                            </h4>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="form-group" id="div-env-destinatario">
                                    <label for="env-destinatario"
                                        style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Notificar
                                        a Proveedor:</label>
                                    <input type="text" id="env-destinatario" name="destinatario" class="form-control"
                                        style="font-size: 0.84em; padding: 6px 10px; height: auto;" required>
                                </div>

                                <div class="form-group" id="div-env-destinatario-calidad">
                                    <label for="env-destinatario-calidad"
                                        style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Notificar
                                        a Calidad:</label>
                                    <input type="text" id="env-destinatario-calidad" name="destinatario_calidad"
                                        class="form-control"
                                        style="font-size: 0.84em; padding: 6px 10px; height: auto;" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 6px;">
                                <label for="env-fecha-entrega"
                                    style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Fecha
                                    de Entrega acordada:</label>
                                <input type="date" id="env-fecha-entrega" name="fecha_entrega" class="form-control"
                                    style="font-size: 0.84em; padding: 6px 10px; height: auto;" required>
                            </div>

                            <div class="form-group" style="margin-top: 6px; margin-bottom: 0;">
                                <label
                                    style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Pre-órdenes
                                    pendientes por enviar:</label>
                                <div id="env-pending-preordenes-container"
                                    style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; max-height: 100px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                                </div>
                            </div>
                        </div>

                        <!-- Bloque 2 (VERDE): SOLO el Botón Dropzone de Selección -->
                        <div
                            style="background: #f0fdf4; border: 2px solid #16a34a; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.08);">
                            <h4
                                style="margin-top: 0; margin-bottom: 6px; color: #15803d; font-size: 0.96em; border-bottom: 1.5px solid #16a34a; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px;">
                                <img src="{{ asset('images/anadir.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Subir Nuevos Archivos
                            </h4>

                            <div class="custom-file-dropzone"
                                style="border: 2px dashed #16a34a; background: #ffffff; padding: 12px 14px; border-radius: 10px; text-align: center; cursor: pointer; position: relative;">
                                <input type="file" id="env-archivos-adicionales" name="archivos_adicionales[]"
                                    class="custom-file-input"
                                    style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;"
                                    multiple>
                                <div class="dropzone-content"
                                    style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <img src="{{ asset('images/anadir.png') }}"
                                        style="width: 22px; height: 22px; object-fit: contain;">
                                    <span style="font-weight: 700; color: #15803d; font-size: 0.86em;">Arrastrar
                                        adicionales aquí (PDFs o imágenes)</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Columna Derecha: 2 Sub-contenedores bien diferenciados -->
                    <div style="display: flex; flex-direction: column; gap: 14px; height: 100%; min-height: 0; box-sizing: border-box;">
                        
                        <!-- Sub-contenedor 1 (AZUL ICE): Archivos y Dibujos de la OT Disponibles -->
                        <div style="background: #f0f7ff; border: 2px solid #0284c7; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.08); flex: 1.45; display: flex; flex-direction: column; min-height: 0;">
                            <h4 style="margin-top: 0; margin-bottom: 6px; color: #0369a1; font-size: 1.02em; border-bottom: 2px solid #0284c7; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                <img src="{{ asset('images/galeria.png') }}" style="width: 18px; height: 18px; object-fit: contain;"> Archivos y Dibujos de la OT Disponibles
                            </h4>

                            <div id="env-server-files-container" style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px; flex: 1; max-height: 380px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                                <div class="alm-spinner alm-border-top-color-033966 alm-display-block alm-margin-10px-auto"></div>
                            </div>
                        </div>

                        <!-- Sub-contenedor 2 (VERDE ESMERALDA): Nuevos Archivos Adjuntados (Coincide en color with left button) -->
                        <div style="background: #f0fdf4; border: 2px solid #16a34a; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.08); flex: 1; display: flex; flex-direction: column; min-height: 0;">
                            <h4 style="margin-top: 0; margin-bottom: 6px; color: #15803d; font-size: 0.98em; border-bottom: 1.5px solid #16a34a; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                <img src="{{ asset('images/anadir.png') }}" style="width: 16px; height: 16px; object-fit: contain;"> Nuevos Archivos Adjuntados
                            </h4>

                            <div id="env-archivos-adicionales-list" style="background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px; flex: 1; max-height: 250px; min-height: 140px; overflow-y: auto; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start;"></div>
                        </div>

                    </div>

                </div>

                <div class="form-actions"
                    style="text-align: center; margin-top: 10px; padding-top: 8px; flex-shrink: 0;">
                    <button type="submit" id="btn-submit-envio" class="btn-save-preorden" disabled
                        style="background: linear-gradient(135deg, #033966, #022340); box-shadow: 0 4px 15px rgba(3, 57, 102, 0.35); padding: 11px 44px; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer; font-size: 1.05em; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        Enviar Correo con Adjuntos
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formEnviarPreOrden");
    if(!form) return;
    const btn = document.getElementById("btn-submit-envio");
    
    function checkEnvioValidity() {
        if(!btn) return;
        
        let isValid = form.checkValidity();
        
        // Verificar que hay al menos una pre-orden seleccionada
        const pendingChecked = form.querySelectorAll('input[name="pre_orden_ids[]"]:checked').length > 0;
        if (!pendingChecked) isValid = false;
        
        // Verificar que hay al menos un archivo adjunto (ya sea de los disponibles en el servidor o subidos)
        const serverFilesChecked = form.querySelectorAll('input[name="archivos_seleccionados[]"]:checked').length > 0;
        const adicionalesCount = window.adicionalesSelectedFiles ? window.adicionalesSelectedFiles.length : 0;
        
        if (!serverFilesChecked && adicionalesCount === 0) isValid = false;
        
        if (isValid) {
            btn.disabled = false;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
        } else {
            btn.disabled = true;
            btn.style.opacity = "0.6";
            btn.style.cursor = "not-allowed";
        }
    }

    form.addEventListener("input", checkEnvioValidity);
    form.addEventListener("change", checkEnvioValidity);
    
    // Check periodically in case files are loaded dynamically or checkmarks changed by JS
    setInterval(checkEnvioValidity, 500);
});
</script>
