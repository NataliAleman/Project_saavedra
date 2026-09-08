<div id="modalEnviarAlertaLiberacion" class="alm-modal" role="dialog" aria-modal="true">

    <div class="alm-modal-content cal-max-width-1500px cal-width-96vw cal-border-radius-20px">

        <div
            class="alm-modal-header cal-padding-2-5em-3em-2-2em cal-border-top-left-radius-18px cal-border-top-right-radius-18px">
            <div class="div-cerrar">
                @include('layouts.partials.close_button', [
                    'onclick' => 'cerrarModalEnviarAlertaLiberacion()',
                ])
            </div>
            <h3 id="alerta-lib-title"
                class="cal-font-size-2-2em cal-margin-0 cal-font-family-quot cal-font-weight-700 cal-color-fff">
                Enviar Alerta de Liberación
            </h3>
            <p id="alerta-lib-subtitle"
                class="lib-modal-subtitle cal-color-bae6fd cal-font-size-1-15em cal-margin-top-8px cal-margin-bottom-0 cal-font-family-quot cal-font-weight-500">
            </p>
        </div>

        <div class="alm-modal-body cal-padding-3em-3-5em">

            <form id="formEnviarAlertaLiberacion" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="al-ot" name="ot" />
                <input type="hidden" id="al-decision" name="decision" />
                <input type="hidden" id="al-tipo-modelo" name="tipo_modelo" />
                <p class="cal-margin-bottom-28px cal-font-family-quot cal-font-weight-500 cal-line-height-1-6 cal-color-334155 cal-font-size-1-3em"
                    id="al-prompt-text"></p>

                {{-- FECHA --}}
                <div class="form-group cal-margin-bottom-28px">
                    <label id="al-fecha-label" for="al-fecha"
                        class="cal-font-weight-700 cal-color-334155 cal-display-block cal-margin-bottom-10px cal-font-family-quot cal-font-size-1-2em">
                        Fecha de Emisión / Entrega
                        <span class="cal-color-9c0300">*</span>
                    </label>
                    <input type="date" id="al-fecha" name="fecha"
                        class="form-control cal-font-family-quot cal-font-size-1-15em cal-padding-14px-20px cal-height-auto cal-border-radius-10px"
                        required />
                </div>

                {{-- -•--•--•- LAYOUT DUAL: Aprobados (izq) + Rechazados (der) si hay ambos, o uno solo al 100%
                -•--•--•- --}}
                <div id="al-dual-layout"
                    class="cal-display-flex cal-gap-32px cal-align-items-stretch cal-margin-top-32px">

                    {{-- ── COLUMNA APROBADOS ── --}}
                    <div id="al-col-aprobados" class="cal-flex-1 cal-width-100pct cal-display-none">
                        <div
                            class="cal-border-2-5px-solid-059669 cal-border-radius-18px cal-overflow-hidden cal-box-shadow-0-8px-25px-rgba-5-150-105-0-12">

                            {{-- Header Aprobados --}}
                            <div
                                class="cal-background-linear-gradient-135deg-059669-047857 cal-padding-20px-24px cal-display-flex cal-align-items-center cal-gap-14px">
                                <img src="{{ asset('images/aprobado.png') }}"
                                    class="cal-width-36px cal-height-36px cal-object-fit-contain" alt="" />
                                <div>
                                    <div
                                        class="cal-font-weight-800 cal-font-size-1-35em cal-color-fff cal-font-family-quot">
                                        Documentos Aprobados
                                    </div>
                                    <div id="al-aprobados-tipos-label"
                                        class="cal-font-size-0-95em cal-color-a7f3d0 cal-font-family-quot">
                                        —
                                    </div>
                                </div>
                            </div>
                            <div class="cal-padding-24px">

                                {{-- Archivos del servidor — Aprobados --}}
                                <label
                                    class="cal-font-weight-700 cal-color-059669 cal-font-size-1-15em cal-margin-bottom-12px cal-display-block cal-font-family-quot">Archivos
                                    en servidor (selecciona los
                                    que deseas adjuntar):</label>
                                <div id="al-server-files-aprobados"
                                    class="cal-background-f0fdf4 cal-border-1-8px-solid-bbf7d0 cal-border-radius-14px cal-padding-20px cal-max-height-280px cal-overflow-y-auto cal-display-grid cal-grid-template-columns-repeat-auto-fill-minmax-180px-1fr cal-gap-12px cal-justify-items-center">
                                    <div
                                        class="cal-text-align-center cal-color-64748b cal-grid-column-1-1 cal-padding-12px cal-font-style-italic cal-font-size-0-95em cal-font-family-quot">
                                        Cargando archivos...
                                    </div>
                                </div>

                                {{-- Upload Firmados — por Modelo (Aprobados) --}}
                                <div class="cal-margin-top-24px">
                                    <label
                                        class="cal-font-weight-700 cal-color-059669 cal-font-size-1-15em cal-display-block cal-margin-bottom-10px cal-font-family-quot">
                                        Subir Formato F-CCL-LDM Firmado (por
                                        modelo):
                                    </label>
                                    <p
                                        class="cal-font-size-0-9em cal-color-64748b cal-margin-bottom-14px cal-font-family-quot cal-line-height-1-5">
                                        Selecciona el tipo de modelo y luego sube el formato de liberación
                                        <strong>aprobado y firmado</strong> correspondiente.
                                    </p>
                                    <div id="al-upload-aprobados-rows"
                                        class="cal-display-flex cal-flex-direction-column cal-gap-14px"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ── COLUMNA RECHAZADOS ── --}}
                    <div id="al-col-rechazados" class="cal-flex-1 cal-width-100pct cal-display-none">
                        <div
                            class="cal-border-2-5px-solid-dc2626 cal-border-radius-18px cal-overflow-hidden cal-box-shadow-0-8px-25px-rgba-220-38-38-0-12">

                            {{-- Header Rechazados --}}
                            <div
                                class="cal-background-linear-gradient-135deg-dc2626-b91c1c cal-padding-20px-24px cal-display-flex cal-align-items-center cal-gap-14px">
                                <img src="{{ asset('images/Rechazado.png') }}"
                                    class="cal-width-36px cal-height-36px cal-object-fit-contain" alt="" />
                                <div>
                                    <div
                                        class="cal-font-weight-800 cal-font-size-1-35em cal-color-fff cal-font-family-quot">
                                        Documentos Rechazados
                                    </div>
                                    <div id="al-rechazados-tipos-label"
                                        class="cal-font-size-0-95em cal-color-fecaca cal-font-family-quot">
                                        —
                                    </div>
                                </div>
                            </div>
                            <div class="cal-padding-24px">

                                {{-- Archivos del servidor — Rechazados --}}
                                <label
                                    class="cal-font-weight-700 cal-color-dc2626 cal-font-size-1-15em cal-margin-bottom-12px cal-display-block cal-font-family-quot">Archivos
                                    en servidor (selecciona los
                                    que deseas adjuntar):</label>
                                <div id="al-server-files-rechazados"
                                    class="cal-background-fef2f2 cal-border-1-8px-solid-fecaca cal-border-radius-14px cal-padding-20px cal-max-height-280px cal-overflow-y-auto cal-display-grid cal-grid-template-columns-repeat-auto-fill-minmax-180px-1fr cal-gap-12px cal-justify-items-center">
                                    <div
                                        class="cal-text-align-center cal-color-64748b cal-grid-column-1-1 cal-padding-12px cal-font-style-italic cal-font-size-0-95em cal-font-family-quot">
                                        Cargando archivos...
                                    </div>
                                </div>

                                {{-- Upload Firmados — por Modelo (Rechazados) --}}
                                <div class="cal-margin-top-24px">
                                    <label
                                        class="cal-font-weight-700 cal-color-dc2626 cal-font-size-1-15em cal-display-block cal-margin-bottom-10px cal-font-family-quot">
                                        Subir Formato F-CCL-LDM de Rechazo +
                                        SCAR Firmado (por modelo):
                                    </label>
                                    <p
                                        class="cal-font-size-0-9em cal-color-64748b cal-margin-bottom-14px cal-font-family-quot cal-line-height-1-5">
                                        Selecciona el tipo de modelo y luego sube el <strong>formato de liberación
                                            rechazado</strong> y el <strong>SCAR firmado</strong> correspondiente.</p>
                                    <div id="al-upload-rechazados-rows"
                                        class="cal-display-flex cal-flex-direction-column cal-gap-14px"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- fin dual-layout --}}
                <div class="form-actions cal-text-align-center cal-margin-top-40px cal-margin-bottom-12px">
                    <button type="submit" id="btn-submit-alerta-liberacion" disabled
                        class="btn-save-preorden cal-font-size-1-2em cal-padding-15px-32px cal-border-radius-10px cal-font-family-quot cal-font-weight-700 cal-height-auto">
                        Enviar Alerta de Liberación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formEnviarAlertaLiberacion");
    if(!form) return;
    const btn = document.getElementById("btn-submit-alerta-liberacion");
    
    // Marcar los campos que originalmente son requeridos
    const allRequired = form.querySelectorAll("input[required], select[required], textarea[required]");
    allRequired.forEach(input => input.setAttribute("data-was-required", "true"));
    
    function checkAlertaValidity() {
        if(!btn) return;
        
        let isValid = true;
        const checkInputs = form.querySelectorAll("[data-was-required='true']");
        
        checkInputs.forEach(input => {
            if (input.offsetParent === null) {
                input.removeAttribute("required");
            } else {
                input.setAttribute("required", "required");
                if (!input.value.trim()) isValid = false;
            }
        });
        
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

    form.addEventListener("input", checkAlertaValidity);
    form.addEventListener("change", checkAlertaValidity);
    
    setInterval(checkAlertaValidity, 500);
});
</script>
