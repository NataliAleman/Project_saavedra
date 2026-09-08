<div id="modalConfirmarModelo" class="alm-modal" role="dialog" aria-modal="true">
    <div class="alm-modal-content alm-border-radius-20px alm-border-2-5px-solid-0a8504 alm-overflow-hidden"
        style="max-width: 1720px; width: 97vw; max-height: 96vh; height: 95vh; display: flex; flex-direction: column; margin: auto;">
        <div
            class="alm-modal-header alm-background-linear-gradient-135deg-0a8504-064e03 alm-border-bottom-2px-solid-064e03 alm-padding-0-9em-2-2em alm-position-relative">
            <div class="div-cerrar">
                <button type="button" class="btn-cerrar" onclick="cerrarModalConfirmarModelo()">
                    <img class="img-cerrar" src="{{ asset('images/cerrar.png') }}" alt="Cerrar"
                        style="width: 36px !important; height: 36px !important;">
                </button>
            </div>
            <div class="alm-display-flex alm-align-items-center alm-gap-16px">
                <img src="{{ asset('images/aprobado.png') }}"
                    style="width: 34px !important; height: 34px !important; max-width: 34px !important; max-height: 34px !important; object-fit: contain; flex-shrink: 0;"
                    alt="">
                <div>
                    <h3
                        class="alm-color-fff alm-margin-0 alm-font-size-1-3em alm-font-weight-800 alm-font-family-Poppins-sans-serif">
                        Confirmar Disponibilidad del Modelo</h3>
                    <div id="confirmar-modelo-subtitle"
                        class="alm-color-rgba-255-255-255-0-9 alm-font-size-0-88em alm-margin-top-2px alm-font-weight-500 alm-font-family-Poppins-sans-serif">
                        OT: -</div>
                </div>
            </div>
        </div>
        <div class="alm-modal-body alm-padding-1em-1-6em-1-2em-1-6em alm-background-fafafa alm-font-family-Poppins-sans-serif"
            style="flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0;">
            <form id="formConfirmarModelo" enctype="multipart/form-data"
                style="display: flex; flex-direction: column; flex: 1; min-height: 0;"
                data-email-modelo="{{ env('EMAIL_PROVEEDOR_MODELOS', 'produccion@ssmetalf.mx,asistenteprod@ssmetalf.mx') }}"
                data-email-calidad="{{ env('EMAIL_CALIDAD', 'inspecciontec@grupoindsaavedra.com') }}">
                <input type="hidden" id="cm-ot" name="ot">
                <input type="hidden" id="cm-id-hash" name="id_hash">

                <div
                    class="alm-background-fef9c3 alm-border-1px-solid-fde047 alm-border-radius-12px alm-padding-7px-14px alm-color-713f12 alm-font-size-0-86em alm-line-height-1-3 alm-margin-bottom-12px">
                    <strong>Documentos requeridos:</strong> Adjunta las evidencias o remisión que acrediten la recepción
                    del modelo para esta OT.
                </div>

                <div
                    style="display: grid; grid-template-columns: minmax(360px, 1fr) minmax(600px, 1.55fr); gap: 18px; align-items: stretch; flex: 1; min-height: 0;">

                    <!-- Columna Izquierda: Datos del Formulario + Botón Verde de Selección -->
                    <div style="display: flex; flex-direction: column; gap: 12px; min-height: 0;">

                        <!-- Bloque 1: Formulario Principal -->
                        <div
                            style="background: #fff; padding: 14px 16px; border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                            <h4
                                style="margin-top: 0; margin-bottom: 8px; color: #0a8504; font-size: 1em; border-bottom: 2px solid #0a8504; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px;">
                                <img src="{{ asset('images/copia-de-datos.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Datos de Confirmación
                            </h4>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <div class="form-group" id="div-cm-destinatario">
                                    <label for="cm-destinatario"
                                        style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Notificar
                                        a Proveedor:</label>
                                    <input type="text" id="cm-destinatario" name="destinatario" class="form-control"
                                        style="font-size: 0.84em; padding: 6px 10px; height: auto;" required>
                                </div>

                                <div class="form-group" id="div-cm-destinatario-calidad">
                                    <label for="cm-destinatario-calidad"
                                        style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Notificar
                                        a Calidad:</label>
                                    <input type="text" id="cm-destinatario-calidad" name="destinatario_calidad"
                                        class="form-control" style="font-size: 0.84em; padding: 6px 10px; height: auto;"
                                        required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-top: 6px;">
                                <label for="cm-fecha"
                                    style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Fecha
                                    de Envío <span class="alm-text-dark-red">*</span>:</label>
                                <input type="date" id="cm-fecha" name="fecha" class="form-control"
                                    style="font-size: 0.84em; padding: 6px 10px; height: auto;" required>
                            </div>

                            <div class="form-group" style="margin-top: 6px; margin-bottom: 0;">
                                <label
                                    style="font-weight: 700; color: #334155; display: block; margin-bottom: 2px; font-size: 0.84em;">Clases
                                    Disponibles <span class="alm-text-dark-red">*</span>:</label>
                                <div id="cm-clases-container"
                                    style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; display: flex; flex-wrap: wrap; gap: 6px; max-height: 100px; overflow-y: auto;">
                                    <div
                                        class="alm-spinner alm-border-top-color-0284c7 alm-display-block alm-margin-5px-auto">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bloque 2 (VERDE): SOLO el Botón Dropzone para Seleccionar/Cargar -->
                        <div
                            style="background: #f0fdf4; border: 2px solid #16a34a; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.08);">
                            <h4
                                style="margin-top: 0; margin-bottom: 6px; color: #15803d; font-size: 0.96em; border-bottom: 1.5px solid #16a34a; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px;">
                                <img src="{{ asset('images/anadir.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Subir Nuevos Archivos <span
                                    class="alm-text-dark-red">*</span>
                            </h4>

                            <div class="custom-file-dropzone"
                                style="border: 2px dashed #16a34a; background: #ffffff; padding: 12px 14px; border-radius: 10px; text-align: center; cursor: pointer; position: relative;">
                                <input type="file" id="cm-archivos" name="archivos[]" class="custom-file-input"
                                    style="position: absolute; top:0; left:0; width:100%; height:100%; opacity:0; cursor:pointer;"
                                    multiple>
                                <div class="dropzone-content"
                                    style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                                    <img src="{{ asset('images/anadir.png') }}"
                                        style="width: 22px; height: 22px; object-fit: contain;">
                                    <span style="font-weight: 700; color: #15803d; font-size: 0.86em;">Haz clic o
                                        arrastra PDFs e imágenes aquí</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Columna Derecha: 2 Sub-contenedores bien diferenciados -->
                    <div
                        style="display: flex; flex-direction: column; gap: 14px; height: 100%; min-height: 0; box-sizing: border-box;">

                        <!-- Sub-contenedor 1 (AZUL ICE): Archivos y Dibujos de la OT Disponibles -->
                        <div
                            style="background: #f0f7ff; border: 2px solid #0284c7; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.08); flex: 1.45; display: flex; flex-direction: column; min-height: 0;">
                            <h4
                                style="margin-top: 0; margin-bottom: 6px; color: #0369a1; font-size: 1.02em; border-bottom: 2px solid #0284c7; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                <img src="{{ asset('images/galeria.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Archivos y Dibujos de la OT
                                Disponibles
                            </h4>

                            <div id="cm-server-files-container"
                                style="background: #f0f7ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px; flex: 1; min-height: 0; overflow-y: auto; display: flex; flex-direction: column; gap: 10px;">
                                <div
                                    class="alm-spinner alm-border-top-color-0284c7 alm-display-block alm-margin-10px-auto">
                                </div>
                            </div>
                        </div>

                        <!-- Sub-contenedor 2 (VERDE ESMERALDA): Nuevos Archivos Adjuntados (Coincide en color con el botón de la izquierda) -->
                        <div
                            style="background: #f0fdf4; border: 2px solid #16a34a; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.08); flex: 1; display: flex; flex-direction: column; min-height: 0;">
                            <h4
                                style="margin-top: 0; margin-bottom: 6px; color: #15803d; font-size: 0.98em; border-bottom: 1.5px solid #16a34a; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                <img src="{{ asset('images/anadir.png') }}"
                                    style="width: 16px; height: 16px; object-fit: contain;"> Nuevos Archivos Adjuntados
                            </h4>

                            <div id="cm-archivos-list"
                                style="background: #f0fdf4; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px; flex: 1; min-height: 0; overflow-y: auto; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-start;">
                            </div>
                        </div>

                    </div>

                </div>

                <div class="form-actions"
                    style="text-align: center; margin-top: 10px; padding-top: 8px; flex-shrink: 0;">
                    <button type="submit" id="btn-submit-confirmar-modelo" class="btn-save-preorden" disabled
                        style="background: linear-gradient(135deg, #0a8504, #064e03); box-shadow: 0 4px 15px rgba(10, 133, 4, 0.35); padding: 11px 44px; border: none; border-radius: 10px; color: #fff; font-weight: 700; cursor: pointer; font-size: 1.05em; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        Confirmar y Registrar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const form = document.getElementById("formConfirmarModelo");
        if (!form) return;
        const btn = document.getElementById("btn-submit-confirmar-modelo");

        function checkFormValidity() {
            if (!btn) return;
            const selectedClasses = form.querySelectorAll(".cm-clase-checkbox:checked").length;
            const hasDate = document.getElementById("cm-fecha")?.value;
            const filesCount = window.cmConfirmarSelectedFiles ? window.cmConfirmarSelectedFiles.length : 0;

            if (selectedClasses > 0 && filesCount > 0 && hasDate) {
                btn.disabled = false;
                btn.style.opacity = "1";
                btn.style.cursor = "pointer";
            } else {
                btn.disabled = true;
                btn.style.opacity = "0.6";
                btn.style.cursor = "not-allowed";
            }
        }

        form.addEventListener("input", checkFormValidity);
        form.addEventListener("change", checkFormValidity);

        // Patch the global function to trigger validation check
        const originalRender = window.renderCmConfirmarBadges;
        window.renderCmConfirmarBadges = function () {
            if (originalRender) originalRender();
            checkFormValidity();
        };

        // Initial check
        setInterval(checkFormValidity, 500);
    });
</script>
