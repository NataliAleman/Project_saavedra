{{-- _modal_scar.blade.php — Formato SCAR de Modelos --}}

{{-- ── MODAL SCAR ── --}}
<div id="modalScar" class="alm-modal" role="dialog" aria-modal="true">
    <div class="alm-modal-content lib-modal-content" style="max-width: 780px;">

        {{-- CABECERA --}}
        <div class="alm-modal-header lib-modal-header lib-modal-header-rechazo" id="scar-modal-header">
            <div class="div-cerrar">
                <button type="button" class="btn-cerrar" onclick="cerrarModalScar()">
                    <img class="img-cerrar" src="{{ asset('images/cerrar.png') }}" alt="Cerrar" style="width: 36px !important; height: 36px !important;">
                </button>
            </div>
            <div class="lib-header-top">
                <div class="lib-format-meta">
                    <span class="lib-meta-item"><strong>Codigo:</strong> F-CCL-SCAR</span>
                    <span class="lib-meta-sep">|</span>
                    <span class="lib-meta-item"><strong>Version:</strong> A</span>
                    <span class="lib-meta-sep">|</span>
                    <span class="lib-meta-item"><strong>Revision:</strong> 26 de mayo de 2026</span>
                </div>
                <h3 class="lib-modal-title-text" style="color: #ffffff;">
                    Formato SCAR de Modelos — Solicitud de Acción Correctiva
                </h3>
                <p id="scar-modal-subtitle" class="lib-modal-subtitle"></p>
            </div>
        </div>

        {{-- CUERPO --}}
        <div class="alm-modal-body lib-modal-body">
            <form id="formScar" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" id="scar-ot" name="ot">
                <input type="hidden" id="scar-tipo" name="tipo_modelo">
                <input type="hidden" id="scar-motivo" name="motivo_rechazo_heredado">

                {{-- DATOS PRINCIPALES --}}
                <div class="lib-format-datos" style="margin-bottom: 18px;">
                    <div class="lib-datos-grid" style="grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="lib-dato-group">
                            <label class="lib-dato-label">Orden de Trabajo:</label>
                            <span id="scar-ot-display" class="lib-dato-value">—</span>
                        </div>
                        <div class="lib-dato-group">
                            <label class="lib-dato-label">Tipo de Modelo:</label>
                            <span id="scar-tipo-display" class="lib-dato-value">—</span>
                        </div>
                        <div class="lib-dato-group">
                            <label class="lib-dato-label">Fecha de Emisión:</label>
                            <span class="lib-dato-value">{{ now()->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- DATOS COMPLEMENTARIOS --}}
                <div class="lib-section-block"
                    style="margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
                    <h5 class="modal-subtitle-2">Datos del
                        Solicitante</h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <label class="lib-dato-label" for="scar-cliente-empresa">Cliente / Empresa:</label>
                            <input type="text" id="scar-cliente-empresa" name="cliente_empresa" class="form-control input-disabled-style" value="Industrial Saavedra">
                        </div>
                        <div>
                            <label class="lib-dato-label" for="scar-area-solicitante">Área Solicitante:</label>
                            <input type="text" id="scar-area-solicitante" name="area_solicitante" class="form-control input-disabled-style" value="Calidad">
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label class="lib-dato-label" for="scar-nombre-solicitante">Nombre del Solicitante:</label>
                            <input type="text" id="scar-nombre-solicitante" name="nombre_solicitante"
                                class="form-control input-disabled-style" value="{{ Auth::user() ? Auth::user()->name : '' }}"
                                placeholder="Inspector de Calidad" readonly>
                        </div>
                        <div>
                            <label class="lib-dato-label" for="scar-nombre-moldura">Nombre de la Moldura:</label>
                            <input type="text" id="scar-nombre-moldura" name="nombre_moldura" class="form-control input-disabled-style">
                        </div>
                    </div>
                </div>

                {{-- DESCRIPCIÓN DE LA NO CONFORMIDAD (pre-rellenado con motivo_rechazo) --}}
                <div class="lib-section-block mb-16">
                    <h5 style="font-weight: 700; color: #9c0300; font-size: 1.05em; margin-bottom: 6px;">
                        Descripción de la No Conformidad
                    </h5>
                    <p class="lib-section-hint" style="color: #9c0300; margin-bottom: 6px;">
                        Este campo se pre-rellena con el motivo de rechazo registrado en el formato F-CCL-LDM.
                    </p>
                    <textarea id="scar-descripcion" name="descripcion_no_conformidad"
                        class="form-control lib-textarea lib-textarea-danger" rows="4"
                        placeholder="Descripción del incumplimiento detectado..."></textarea>
                </div>

                {{-- PROVEEDOR --}}
                <div class="lib-section-block mb-16">
                    <h5 style="font-weight: 700; color: #334155; font-size: 1.05em; margin-bottom: 6px;">
                        Proveedor
                    </h5>
                    <input type="text" id="scar-proveedor" name="proveedor" class="form-control input-disabled-style" value="SS Metal Foundry, S. de R.L. de C.V.">
                </div>

                {{-- EVIDENCIA ADJUNTA --}}
                <div class="lib-section-block"
                    style="margin-bottom: 16px; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; padding-top: 16px; padding-bottom: 16px;">
                    <h5 class="modal-subtitle-2">Evidencia
                        Adjunta</h5>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <label
                                class="checkbox-label">
                                <input type="checkbox" id="scar-evidencia-reporte" name="evidencia_reporte" value="1"
                                    checked onclick="return false;" class="w-16 h-16">
                                <span>Reporte dimensional de Calidad (Obligatorio)</span>
                            </label>
                        </div>
                        <div>
                            <label
                                class="checkbox-label">
                                <input type="checkbox" id="scar-evidencia-dibujos" name="evidencia_dibujos" value="1"
                                    checked class="w-16 h-16">
                                <span>Dibujos autorizados</span>
                            </label>
                        </div>
                        <div>
                            <label
                                class="checkbox-label">
                                <input type="checkbox" id="scar-evidencia-ayudas" name="evidencia_ayudas" value="1"
                                    checked class="w-16 h-16">
                                <span>Ayudas visuales</span>
                            </label>
                        </div>
                        <div>
                            <label
                                class="checkbox-label">
                                <input type="checkbox" id="scar-evidencia-fotos" name="evidencia_fotos" value="1"
                                    class="w-16 h-16" onchange="const el = document.getElementById('scar-fotos-upload-group'); if(el) { el.style.display = this.checked ? 'block' : 'none'; el.classList.toggle('alm-display-none', !this.checked); el.classList.toggle('cal-display-none', !this.checked); }">
                                <span>Fotografías</span>
                            </label>
                        </div>
                        <div style="grid-column: span 2;">
                            <label
                                class="checkbox-label">
                                <input type="checkbox" id="scar-evidencia-otro" name="evidencia_otro" value="1"
                                    class="w-16 h-16" onchange="const el = document.getElementById('scar-otro-upload-group'); if(el) { el.style.display = this.checked ? 'block' : 'none'; el.classList.toggle('alm-display-none', !this.checked); el.classList.toggle('cal-display-none', !this.checked); }">
                                <span>Otro / PDFs adicionales</span>
                            </label>
                        </div>
                    </div>

                    {{-- UPLOAD DE FOTOS --}}
                    <div class="form-group alm-display-none cal-display-none" id="scar-fotos-upload-group" style="margin-top: 16px;">
                        <label for="scar-fotos" style="font-weight:700; color:#334155; display:block; margin-bottom:6px; font-size:0.9em;">
                            Subir Fotografías <span class="text-danger">*</span>
                        </label>
                        <div class="custom-file-dropzone" style="border: 2px dashed #d97706; background: #fffbeb; min-height: 80px; position: relative; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px; cursor: pointer;">
                            <input type="file" id="scar-fotos" name="fotos[]" class="custom-file-input" multiple accept="image/*" style="position: absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                            <div class="dropzone-content" style="display: flex; flex-direction: column; align-items: center; pointer-events: none;">
                                <img src="{{ asset('images/anadir.png') }}" style="width: 24px; height: 24px; margin-bottom: 4px;" alt="Añadir">
                                <span id="scar-fotos-text" style="font-weight: 700; color: #d97706; font-size: 0.85em; text-align: center;">Adjuntar fotos *</span>
                                <span style="font-size: 0.7em; color: #64748b; margin-top: 2px;">Solo archivos de imagen</span>
                            </div>
                        </div>
                        <div id="scar-fotos-list" style="margin-top: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; max-height: 420px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; justify-items: center; width: 100%; box-sizing: border-box;"></div>
                    </div>

                    {{-- UPLOAD DE OTROS ARCHIVOS --}}
                    <div class="form-group alm-display-none cal-display-none" id="scar-otro-upload-group" style="margin-top: 16px;">
                        <label for="scar-otro-archivos" style="font-weight:700; color:#334155; display:block; margin-bottom:6px; font-size:0.9em;">
                            Subir Otros Archivos / PDFs adicionales <span class="text-danger">*</span>
                        </label>
                        <div class="custom-file-dropzone" style="border: 2px dashed #0369a1; background: #f0f9ff; min-height: 80px; position: relative; border-radius: 12px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px; cursor: pointer;">
                            <input type="file" id="scar-otro-archivos" name="otros_archivos[]" class="custom-file-input" multiple accept="*/*" style="position: absolute; width:100%; height:100%; opacity:0; cursor:pointer;">
                            <div class="dropzone-content" style="display: flex; flex-direction: column; align-items: center; pointer-events: none;">
                                <img src="{{ asset('images/anadir.png') }}" style="width: 24px; height: 24px; margin-bottom: 4px;" alt="Añadir">
                                <span id="scar-otro-text" style="font-weight: 700; color: #0369a1; font-size: 0.85em; text-align: center;">Adjuntar otros archivos *</span>
                                <span style="font-size: 0.7em; color: #64748b; margin-top: 2px;">Cualquier tipo de archivo</span>
                            </div>
                        </div>
                        <div id="scar-otro-archivos-list" style="margin-top: 15px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; max-height: 420px; overflow-y: auto; display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; justify-items: center; width: 100%; box-sizing: border-box;"></div>
                    </div>

                    {{-- ARCHIVOS EVIDENCIA DEL SERVIDOR --}}
                    <div id="scar-server-files-container" class="alm-pdf-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; margin-top: 20px; width: 100%; box-sizing: border-box;">
                        <!-- Aquí se inyectarán las fotos y archivos previamente subidos al SCAR -->
                    </div>
                </div>

                {{-- ACCIÓN CORRECTIVA INMEDIATA REQUERIDA --}}
                <div class="lib-section-block mb-16">
                    <h5 class="modal-subtitle-2">Acción
                        Correctiva Inmediata Requerida</h5>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="scar-accion-regreso" name="accion_regreso" value="1"
                                class="w-16 h-16">
                            <span>Regreso del modelo al proveedor para su corrección</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="scar-accion-fabricacion" name="accion_fabricacion" value="1"
                                class="w-16 h-16">
                            <span>Fabricación de un modelo nuevo</span>
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" id="scar-accion-otro" name="accion_otro" value="1"
                                class="w-16 h-16"
                                onchange="const el = document.getElementById('scar-accion-otro-text-group'); if(el) { el.style.display = this.checked ? 'block' : 'none'; el.classList.toggle('alm-display-none', !this.checked); el.classList.toggle('cal-display-none', !this.checked); }">
                            <span>Otro</span>
                        </label>

                        <div id="scar-accion-otro-text-group" class="alm-display-none cal-display-none"
                            style="margin-top: 4px; padding-left: 24px;">
                            <input type="text" id="scar-accion-otro-texto" name="accion_otro_texto" class="form-control"
                                placeholder="Escriba la acción correctiva inmediata requerida..." required>
                        </div>
                    </div>
                </div>

                {{-- CAUSA RAÍZ --}}
                <div class="lib-section-block"
                    style="margin-bottom: 16px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                    <h5 style="font-weight: 700; color: #334155; font-size: 1.05em; margin-bottom: 6px;">
                        Causa Raíz del Defecto
                    </h5>
                    <textarea id="scar-causa-raiz" name="causa_raiz" class="form-control lib-textarea" rows="3"
                        placeholder="Indique la causa raíz identificada por el proveedor..."></textarea>
                </div>

                {{-- ACCIONES CORRECTIVAS --}}
                <div class="lib-section-block mb-16">
                    <h5 style="font-weight: 700; color: #334155; font-size: 1.05em; margin-bottom: 6px;">
                        Acciones Correctivas a Futuro
                    </h5>
                    <textarea id="scar-acciones" name="acciones_correctivas" class="form-control lib-textarea" rows="3"
                        placeholder="Indique las acciones correctivas planificadas para evitar recurrencia..."></textarea>
                </div>

                {{-- CÓDIGO DEL MODELO --}}
                <div class="lib-section-block mb-16">
                    <div style="display: grid; grid-template-columns: 1fr; gap: 16px;">
                        <div>
                            <label for="scar-codigo-modelo"
                                style="font-weight: 700; color: #334155; font-size: 0.95em; display: block; margin-bottom: 4px;">
                                Código del Modelo:
                            </label>
                            <input type="text" id="scar-codigo-modelo" name="codigo_modelo" class="form-control"
                                placeholder="Código de referencia del modelo" required>
                        </div>
                    </div>
                </div>

                {{-- BOTONES --}}
                <div class="form-actions" style="text-align: center; margin-top: 24px;">
                    <button type="button" class="btn-lib-rechazar-send" id="scar-btn-guardar" onclick="scarSubmit('guardar')" style="background: rgb(156, 3, 0); border: 2px solid rgb(122, 2, 0); box-shadow: rgba(156, 3, 0, 0.3) 0px 4px 15px; font-size:1.15em; padding:14px 28px; border-radius:10px; font-family:'Poppins',sans-serif; font-weight:700; height:auto; display:inline-flex; align-items:center; justify-content:center; gap:8px;">
                        <img src="{{ asset('images/Descarga.png') }}" alt="" style="width:20px;height:20px;">
                        Guardar y Generar Formato SCAR
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>
