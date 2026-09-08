    <div id="modalEnviarScar" class="alm-modal" role="dialog" aria-modal="true">

        <div class="alm-modal-content lib-modal-content cal-max-width-1100px">
            <div class="alm-modal-header lib-modal-header lib-modal-header-rechazo" id="env-scar-header">
                <div class="div-cerrar">
                    <button type="button" class="btn-cerrar" onclick="cerrarModalEnviarScar()">
                        <img class="img-cerrar" src="{{ asset('images/cerrar.png') }}" alt="Cerrar" />
                    </button>
                </div>
                <h3 class="cal-color-ffffff">Enviar Alerta SCAR al Proveedor</h3>
                <p id="env-scar-modal-subtitle"
                    class="lib-modal-subtitle cal-color-ffd1d1 cal-font-size-0-9em cal-margin-top-4px cal-margin-bottom-0">
                </p>
            </div>

            <div class="alm-modal-body lib-modal-body">

                <form id="formEnviarScar" enctype="multipart/form-data" autocomplete="off">
                    <input type="hidden" id="env-scar-ot" name="ot" />

                    {{-- Destinatario Removido para uso de .env --}}

                    {{-- Fecha Compromiso --}}
                    <div class="form-group cal-margin-bottom-16px">
                        <label for="env-scar-fecha-compromiso"
                            class="cal-font-weight-700 cal-color-334155 cal-display-block cal-margin-bottom-4px">
                            Fecha Compromiso de Devolución (Obligatoria):
                        </label>
                        <input type="date" id="env-scar-fecha-compromiso" name="fecha_compromiso" class="form-control"
                            required />
                    </div>

                    {{-- SCAR Firmado --}}
                    <div class="form-group cal-margin-bottom-20px">
                        <label for="env-scar-pdf-firmado"
                            class="cal-font-weight-700 cal-color-9c0300 cal-display-block cal-margin-bottom-8px">Subir SCAR
                            Firmado Físicamente (PDF Obligatorio):
                            <span class="cal-color-9c0300">*</span></label>
                        <div
                            class="custom-file-dropzone cal-border-2px-dashed-dc2626 cal-background-fef2f2 cal-min-height-80px cal-position-relative cal-border-radius-12px cal-display-flex cal-flex-direction-column cal-align-items-center cal-justify-content-center cal-padding-12px cal-cursor-pointer">
                            <input type="file" id="env-scar-pdf-firmado" name="pdf_firmado"
                                class="custom-file-input cal-position-absolute cal-width-100pct cal-height-100pct cal-opacity-0 cal-cursor-pointer"
                                onchange="
                                                    handleAlertaFileChange(
                                                        this,
                                                        'env-scar-pdf-text',
                                                        'pdf',
                                                    )
                                                " />
                            <div
                                class="dropzone-content cal-display-flex cal-flex-direction-column cal-align-items-center cal-pointer-events-none">
                                <img src="{{ asset('images/pdf.png') }}"
                                    class="cal-width-24px cal-height-24px cal-margin-bottom-4px" alt="PDF" />
                                <span id="env-scar-pdf-text"
                                    class="cal-font-weight-700 cal-color-dc2626 cal-font-size-0-85em cal-text-align-center cal-font-family-quot">Seleccionar
                                    o arrastrar PDF *</span>
                                <span
                                    class="cal-font-size-0-7em cal-color-64748b cal-margin-top-2px cal-font-family-quot">Solo
                                    archivos PDF</span>
                            </div>
                        </div>
                    </div>

                    {{-- Archivos de la OT disponibles --}}
                    <div class="form-group cal-margin-bottom-20px">
                        <label>Archivos de la OT disponibles para adjuntar:</label>
                        <div id="env-scar-server-files-container"
                            class="cal-background-f8fafc cal-border-1px-solid-e2e8f0 cal-border-radius-12px cal-padding-15px cal-max-height-420px cal-overflow-y-auto cal-display-flex cal-flex-direction-column cal-gap-15px">

                            <div
                                class="alm-spinner cal-border-top-color-9c0300 cal-display-block cal-margin-10px-auto cal-grid-column-1-1">
                            </div>
                            <span class="cal-text-align-center cal-color-64748b cal-grid-column-1-1">Cargando archivos de
                                la
                                OT...</span>
                        </div>
                    </div>

                    {{-- Evidencia adicional --}}
                    <div class="form-group cal-margin-bottom-30px">
                        <label
                            class="custom-file-upload-label cal-font-weight-700 cal-color-9c0300 cal-display-block cal-margin-bottom-8px">Subir
                            Evidencia Adicional al Envío (Imágenes o PDFs
                            adicionales):</label>
                        <div
                            class="custom-file-dropzone cal-border-2px-dashed-9c0300 cal-background-fff8f8 cal-min-height-80px cal-position-relative cal-border-radius-12px cal-display-flex cal-flex-direction-column cal-align-items-center cal-justify-content-center cal-padding-12px cal-cursor-pointer">
                            <input type="file" id="env-scar-archivos-adicionales" name="archivos_adicionales[]"
                                class="custom-file-input cal-position-absolute cal-width-100pct cal-height-100pct cal-opacity-0 cal-cursor-pointer" />
                            <div class="dropzone-content">
                                <img src="{{ asset('images/anadir.png') }}"
                                    class="dropzone-icon cal-width-24px cal-height-24px cal-margin-bottom-4px cal-object-fit-contain" />
                                <span
                                    class="dropzone-text cal-font-weight-700 cal-color-9c0300 cal-font-size-0-85em cal-text-align-center cal-font-family-quot">Arrastra
                                    archivos aquí o haz clic para
                                    buscar</span>
                                <span
                                    class="dropzone-subtext cal-font-size-0-7em cal-color-64748b cal-margin-top-2px cal-font-family-quot">Imágenes,
                                    PDF, ZIP</span>
                            </div>
                        </div>
                        <div id="env-scar-archivos-adicionales-list"
                            class="cal-margin-top-10px cal-display-flex cal-flex-wrap-wrap cal-gap-8px"></div>
                    </div>

                    {{-- Boton de Envio --}}
                    <div class="form-actions cal-text-align-center cal-margin-top-20px">
                        <button type="submit" id="btn-submit-scar" disabled
                            class="btn-lib-send cal-background-linear-gradient-135deg-9c0300-7a0200 cal-box-shadow-0-4px-15px-rgba-156-3-0-0-3">
                            Enviar Alerta SCAR al Proveedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formEnviarScar");
    if(!form) return;
    const btn = document.getElementById("btn-submit-scar");
    
    function checkScarValidity() {
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

    form.addEventListener("input", checkScarValidity);
    form.addEventListener("change", checkScarValidity);
    
    setInterval(checkScarValidity, 500);
});
</script>
