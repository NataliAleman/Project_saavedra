<div id="modalGestionVeredicto" class="alm-modal" role="dialog" aria-modal="true">
    <div class="alm-modal-content lib-modal-content"
        style="max-width: 1720px; width: 97vw; max-height: 96vh; height: 95vh; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column; margin: auto;">
        <div id="mgv-header" class="alm-modal-header lib-modal-header"
            style="background: linear-gradient(135deg, #16a34a, #15803d); padding: 0.9em 2.2em; transition: background 0.3s ease; flex-shrink: 0;">
            @include('layouts.partials.close_button', ['onclick' => 'cerrarModalGestionVeredicto()'])
            <h3 style="font-size: 1.45em; margin: 0; font-family:'Poppins', sans-serif; font-weight: 700; color: #fff;"
                id="mgv-title">
                Procesamiento de Modelos (Liberación)</h3>
            <p id="mgv-subtitle" class="lib-modal-subtitle"
                style="color: #e0e7ff; font-size: 0.88em; margin-top: 4px; margin-bottom: 0; font-family:'Poppins', sans-serif; font-weight: 500;">
            </p>

            {{-- Pestañas dinámicas --}}
            <div id="mgv-tabs-container"
                style="display: flex; gap: 10px; margin-top: 14px; border-bottom: 2px solid rgba(255,255,255,0.2); padding-bottom: 0;">
                <button type="button" id="tab-aprobados" class="mgv-tab active" onclick="switchMgvTab('aprobados')"
                    style="background: rgba(255,255,255,0.2); border: none; padding: 10px 22px; border-top-left-radius: 10px; border-top-right-radius: 10px; color: white; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 0.95em; cursor: pointer; transition: all 0.2s ease;">
                    <i class="fas fa-check-circle" style="margin-right: 6px;"></i> Modelos Aprobados (Pre-Orden Casting)
                </button>
                <button type="button" id="tab-rechazados" class="mgv-tab" onclick="switchMgvTab('rechazados')"
                    style="background: rgba(255,255,255,0.05); border: none; padding: 10px 22px; border-top-left-radius: 10px; border-top-right-radius: 10px; color: rgba(255,255,255,0.7); font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 0.95em; cursor: pointer; transition: all 0.2s ease;">
                    <i class="fas fa-times-circle" style="margin-right: 6px;"></i> Modelos Rechazados (Nueva Pre-Orden
                    Modelo)
                </button>
            </div>
        </div>

        <div class="alm-modal-body lib-modal-body"
            style="padding: 1.1em 1.6em; background: #fafafa; flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0;">
            <input type="hidden" id="mgv-ot" name="ot">
            <input type="hidden" id="mgv-fecha" name="fecha_recepcion">

            {{-- ─────────────────────────────────────────────── --}}
            {{-- VISTA: APROBADOS (CASTING) --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div id="mgv-view-aprobados" class="mgv-view"
                style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                <form id="formMgvAprobados" enctype="multipart/form-data"  autocomplete="off"
                    style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                    @csrf
                    <input type="hidden" name="ot" class="mgv-form-ot">
                    <input type="hidden" name="fecha_recepcion" class="mgv-form-fecha">

                    <p
                        style="margin-bottom: 12px; font-family:'Poppins', sans-serif; font-weight:500; line-height:1.5; color:#334155; font-size: 0.92em; flex-shrink: 0;">
                        Revisa los archivos disponibles de la OT, sube los formatos <strong
                            style="color:#15803d;">F-CCL-LDM</strong> firmados por cada modelo aprobado y genera la
                        Pre-Orden de Casting.
                    </p>

                    <div
                        style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.8fr); gap: 18px; align-items: stretch; flex: 1; min-height: 0; width: 100%; box-sizing: border-box;">

                        <!-- Columna Izquierda: Formatos F-CCL-LDM Requeridos -->
                        <div
                            style="background: #f0fdf4; border: 2px solid #16a34a; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(22, 163, 74, 0.08); display: flex; flex-direction: column; height: 100%; min-height: 0; box-sizing: border-box;">
                            <h4
                                style="margin-top: 0; margin-bottom: 8px; color: #15803d; font-size: 1.02em; border-bottom: 1.5px solid #16a34a; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                <img src="{{ asset('images/anadir.png') }}"
                                    style="width: 16px; height: 16px; object-fit: contain;"> Formatos F-CCL-LDM Firmados
                                (Requeridos)
                            </h4>
                            <div id="mgv-aprobados-inputs"
                                style="display:flex; flex-direction:column; gap:10px; flex: 1; overflow-y: auto; padding-right: 4px;">
                            </div>
                        </div>

                        <!-- Columna Derecha (AZUL ICE): Archivos de la OT disponibles -->
                        <div
                            style="background: #f0f7ff; border: 2px solid #0284c7; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(2, 132, 199, 0.08); display: flex; flex-direction: column; height: 100%; min-height: 0; box-sizing: border-box; width: 100%;">
                            <h4
                                style="margin-top: 0; margin-bottom: 6px; color: #0369a1; font-size: 1.02em; border-bottom: 2px solid #0284c7; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                <img src="{{ asset('images/galeria.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Archivos de la OT
                                Disponibles (Aprobados)
                            </h4>
                            <div id="mgv-aprobados-files"
                                style="background:#f0f7ff; border:1px solid #bae6fd; border-radius:10px; padding:12px; flex:1; min-height:0; overflow-y:auto; display:flex; flex-direction: column; gap: 10px; width: 100%; box-sizing: border-box; min-width: 0;">
                                <div
                                    style="text-align:center; color:#64748b; padding:12px; font-style:italic; font-size:0.9em; font-family:'Poppins',sans-serif;">
                                    Cargando archivos...
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="form-actions"
                        style="text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; gap: 15px;">
                        <button type="submit" class="btn-save-preorden" id="btn-submit-aprobados"
                            style="font-size:0.95em; padding:10px 28px; border-radius:10px; font-family:'Poppins',sans-serif; font-weight: 700; height: auto; background: linear-gradient(135deg, #16a34a, #15803d); box-shadow: 0 4px 15px rgba(22,163,74,0.35); display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; color: white; cursor: pointer;">
                            <span>Procesar Aceptados</span>
                        </button>
                    </div>
                </form>
            </div>

            {{-- ─────────────────────────────────────────────── --}}
            {{-- VISTA: RECHAZADOS (PROCESAR) --}}
            {{-- ─────────────────────────────────────────────── --}}
            <div id="mgv-view-rechazados" class="mgv-view alm-display-none cal-display-none"
                style="display: none; flex-direction: column; flex: 1; min-height: 0;">
                <form id="formMgvRechazados" enctype="multipart/form-data"  autocomplete="off"
                    style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                    @csrf
                    <input type="hidden" name="ot" class="mgv-form-ot">
                    <input type="hidden" name="fecha_recepcion" class="mgv-form-fecha">
                    <input type="hidden" name="clases_rechazadas" id="mgv-clases-rechazadas">

                    <p
                        style="margin-bottom: 12px; font-family:'Poppins', sans-serif; font-weight:500; line-height:1.5; color:#334155; font-size: 0.92em; flex-shrink: 0;">
                        Revisa los archivos disponibles, sube el <strong style="color:#b91c1c;">Formato de
                            Rechazo</strong> y el <strong style="color:#b91c1c;">SCAR</strong> por cada modelo
                        rechazado. Al finalizar, podrás generar la nueva Pre-Orden de Fabricación de Modelo.
                    </p>

                    <div
                        style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.8fr); gap: 18px; align-items: stretch; flex: 1; min-height: 0; width: 100%; box-sizing: border-box;">

                        <!-- Columna Izquierda: Formatos de Rechazo y SCAR -->
                        <div
                            style="background: #fef2f2; border: 2px solid #dc2626; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.08); display: flex; flex-direction: column; height: 100%; min-height: 0; box-sizing: border-box;">
                            <h4
                                style="margin-top: 0; margin-bottom: 8px; color: #b91c1c; font-size: 1.02em; border-bottom: 2px solid #dc2626; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                                <img src="{{ asset('images/anadir.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Formatos de Rechazo y SCAR
                                (Requeridos)
                            </h4>
                            <div id="mgv-rechazados-inputs"
                                style="display:flex; flex-direction:column; gap:10px; flex: 1; overflow-y: auto; padding-right: 4px;">
                            </div>
                        </div>

                        <!-- Columna Derecha (AZUL ICE): Archivos de la OT disponibles -->
                        <div
                            style="background: #f0f7ff; border: 2px solid #0284c7; padding: 14px 16px; border-radius: 14px; box-shadow: 0 4px 10px rgba(220, 38, 38, 0.08); display: flex; flex-direction: column; height: 100%; min-height: 0; box-sizing: border-box;">
                            <h4
                                style="margin-top: 0; margin-bottom: 8px; color: #0369a1; font-size: 1.02em; border-bottom: 2px solid #0284c7; padding-bottom: 4px; font-weight: 700; font-family: 'Poppins', sans-serif; display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                                <img src="{{ asset('images/galeria.png') }}"
                                    style="width: 18px; height: 18px; object-fit: contain;"> Archivos de la OT
                                Disponibles (Rechazados)
                            </h4>
                            <div id="mgv-rechazados-files"
                                style="background:#f0f7ff; border:1px solid #bae6fd; border-radius:10px; padding:12px; flex:1; min-height:0; overflow-y:auto; display:flex; flex-direction: column; gap: 10px; width: 100%; box-sizing: border-box; min-width: 0;">
                                <div
                                    style="text-align:center; color:#64748b; padding:12px; font-style:italic; font-size:0.9em; font-family:'Poppins',sans-serif;">
                                    Cargando archivos...
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="form-actions"
                        style="text-align: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid #e2e8f0; flex-shrink: 0; display: flex; align-items: center; justify-content: center; gap: 15px;">
                        <button type="submit" class="btn-save-preorden" id="btn-submit-rechazados"
                            style="font-size:0.95em; padding:10px 28px; border-radius:10px; font-family:'Poppins',sans-serif; font-weight: 700; height: auto; background: linear-gradient(135deg, #dc2626, #b91c1c); box-shadow: 0 4px 15px rgba(220,38,38,0.35); display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: none; color: white; cursor: pointer;">
                            <span>Subir Formatos y Generar Pre-Orden de Modelo</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>