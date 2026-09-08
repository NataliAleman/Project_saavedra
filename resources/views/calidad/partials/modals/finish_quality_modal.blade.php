<div id="modalFinalizarCalidad" class="alm-modal" role="dialog" aria-modal="true">

    <div id="finalizar-calidad-modal-content" class="alm-modal-content cal-border-radius-20px cal-overflow-hidden"
        style="max-width: 1720px; width: 97vw; max-height: 96vh; height: 95vh; display: flex; flex-direction: column; margin: auto;">

        <div id="finalizar-calidad-header"
            class="alm-modal-header cal-border-top-left-radius-18px cal-border-top-right-radius-18px cal-position-relative"
            style="padding: 0.9em 2.2em;">
            <div class="div-cerrar">
                <button type="button" class="btn-cerrar" onclick="cerrarModalFinalizarCalidad()">
                    <img src="{{ asset('images/cerrar.png') }}" alt="Cerrar" class="img-cerrar"
                        style="width: 36px !important; height: 36px !important;" />
                </button>
            </div>
            <h3 id="finalizar-calidad-title"
                class="cal-font-size-1-35em cal-margin-0 cal-font-family-quot cal-font-weight-700 cal-color-fff cal-line-height-1-3">
                Finalizar Proceso de Calidad
            </h3>
            <p id="finalizar-calidad-subtitle"
                class="lib-modal-subtitle cal-color-ffffff cal-font-size-0-88em cal-margin-top-2px cal-margin-bottom-0 cal-font-family-quot cal-font-weight-500 cal-opacity-0-9">
            </p>
        </div>

        <div class="alm-modal-body cal-padding-1em-1-6em-1-2em-1-6em cal-background-fafafa cal-font-family-quot"
            style="flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0;">

            <form id="formFinalizarCalidad" enctype="multipart/form-data" 
                style="display: flex; flex-direction: column; flex: 1; min-height: 0;"
                data-email-almacen="{{ env('EMAIL_ALMACEN', 'almacentec@grupoindsaavedra.com') }}"
                data-email-calidad="{{ env('EMAIL_CALIDAD', 'inspecciontec@grupoindsaavedra.com') }}">
                @csrf
                <input type="hidden" id="fc-ot" name="ot" />
                <input type="hidden" id="fc-decision" name="decision" />
                <input type="hidden" id="fc-tipo-modelo" name="tipo_modelo" />
                <input type="hidden" id="fc-tipos-aprobados" name="tipos_aprobados" />
                <input type="hidden" id="fc-tipos-rechazados" name="tipos_rechazados" />

                <div id="fc-prompt-text" class="cal-margin-bottom-12px"></div>

                <div
                    style="display: grid; grid-template-columns: minmax(360px, 1fr) minmax(600px, 1.55fr); gap: 18px; align-items: stretch; flex: 1; min-height: 0;">

                    <!-- Columna Izquierda: Datos de Liberación -->
                    <div
                        style="background: #fff; padding: 14px 16px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: flex-start; height: 100%; box-sizing: border-box;">
                        <h4
                            style="margin-top: 0; margin-bottom: 8px; color: #0284c7; font-size: 1.02em; border-bottom: 2px solid #0284c7; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px;">
                            <img src="{{ asset('images/copia-de-datos.png') }}"
                                style="width: 18px; height: 18px; object-fit: contain;"> Datos de Liberación
                        </h4>

                        <div class="form-group cal-margin-bottom-10px">
                            <label for="fc-destinatario"
                                style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Notificar
                                a Almacén:</label>
                            <input type="text" id="fc-destinatario" name="destinatario" class="form-control"
                                style="font-size: 0.84em; padding: 6px 10px; height: auto;"  required/>
                            <span style="font-size: 0.75em; color: #64748b; margin-top: 2px; display: block;">Separa
                                correos con comas.</span>
                        </div>

                        <div class="form-group cal-margin-bottom-10px">
                            <label for="fc-destinatario-calidad"
                                style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Notificar
                                a Calidad:</label>
                            <input type="text" id="fc-destinatario-calidad" name="destinatario_calidad"
                                class="form-control" style="font-size: 0.84em; padding: 6px 10px; height: auto;"  required/>
                        </div>

                        <div class="form-group cal-margin-bottom-10px">
                            <label id="fc-fecha-label" for="fc-fecha"
                                style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Fecha
                                de Finalización <span class="cal-color-dc2626">*</span>:</label>
                            <input type="date" id="fc-fecha" name="fecha" class="form-control"
                                style="font-size: 0.84em; padding: 6px 10px; height: auto;"  required/>
                        </div>
                    </div>

                    <!-- Columna Derecha (AZUL ICE): Documentos en Servidor -->
                    <div
                        style="background: #f0f7ff; border: 2px solid #0284c7; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.08); display: flex; flex-direction: column; height: 100%; min-height: 0; box-sizing: border-box;">
                        <h4
                            style="margin-top: 0; margin-bottom: 6px; color: #0369a1; font-size: 1.02em; border-bottom: 2px solid #0284c7; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <img src="{{ asset('images/galeria.png') }}"
                                style="width: 18px; height: 18px; object-fit: contain;"> Archivos de Liberación
                            Disponibles
                        </h4>

                        <div style="flex: 1; display: flex; flex-direction: column; min-height: 0;">
                            <div id="fc-server-files-container"
                                style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px; flex: 1; min-height: 0; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                                <div
                                    class="alm-spinner cal-border-top-color-0284c7 cal-display-block cal-margin-10px-auto">
                                </div>
                                <span class="cal-text-align-center cal-color-64748b">Cargando archivos de la
                                    OT...</span>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="form-actions cal-text-align-center"
                    style="margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; flex-shrink: 0;">
                    <button type="submit" id="btn-submit-finalizar-calidad" disabled
                        class="btn-save-preorden cal-font-size-1em cal-padding-10px-36px cal-border-radius-10px cal-font-weight-700"
                        style="display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        Finalizar y Enviar Correo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formFinalizarCalidad");
    if(!form) return;
    const btn = document.getElementById("btn-submit-finalizar-calidad");
    
    function checkFinalizarValidity() {
        if(!btn) return;
        
        let isValid = form.checkValidity();
        
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

    form.addEventListener("input", checkFinalizarValidity);
    form.addEventListener("change", checkFinalizarValidity);
    
    // Check periodically in case form fields are updated programmatically
    setInterval(checkFinalizarValidity, 500);
});
</script>