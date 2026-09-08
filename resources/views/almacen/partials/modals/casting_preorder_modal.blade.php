<div id="modalPreOrdenCasting" class="alm-modal" role="dialog" aria-modal="true">
    <div class="alm-modal-content alm-max-width-1800px alm-width-95vw alm-border-radius-20px alm-overflow-hidden alm-border-1-5px-solid-0284c7" style="box-shadow: 0 25px 60px rgba(2, 132, 199, 0.25);">
        {{-- HEADER DEL MODAL --}}
        <div class="alm-modal-header alm-background-linear-gradient-135deg-0369a1-0284c7 alm-padding-2-2em-2-5em-1-5em alm-position-relative" style="background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%);">
            <div class="div-cerrar">
                <button type="button" class="btn-cerrar" onclick="cerrarModalPreOrdenCasting()">
                    <img class="img-cerrar" src="{{ asset('images/cerrar.png') }}" alt="Cerrar" style="width: 36px !important; height: 36px !important;">
                </button>
            </div>
            <h3 class="alm-font-size-2em alm-margin-0 alm-font-family-Poppins-sans-serif alm-font-weight-700 alm-color-fff" style="letter-spacing: 0.5px; text-shadow: 0 2px 4px rgba(0,0,0,0.15);">
                Pre-Orden de Fabricación de Casting (4ALM-17)
            </h3>
            <p id="poc-modal-subtitle" class="lib-modal-subtitle alm-color-bae6fd alm-font-size-1-15em alm-margin-top-8px alm-margin-bottom-0 alm-font-family-Poppins-sans-serif alm-font-weight-500" style="color: #e0f2fe; text-shadow: 0 1px 2px rgba(0,0,0,0.1);">
            </p>

            {{-- NAVEGACIÓN DE PROVEEDORES / PESTAÑAS --}}
            <div class="alm-display-flex alm-gap-10px alm-margin-top-25px alm-border-bottom-2px-solid-rgba-255-255-255-0-2 alm-padding-bottom-0 alm-align-items-center" style="border-bottom: 2px solid rgba(255,255,255,0.25);">
                <button type="button" id="tab-poc-page-1" onclick="switchPocPage(1)" class="btn-po-tab active" style="background: #ffffff; color: #0369a1; border: none; padding: 12px 28px; border-top-left-radius: 12px; border-top-right-radius: 12px; font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.05em; cursor: pointer; transition: all 0.2s ease; box-shadow: 0 -4px 12px rgba(0,0,0,0.15);">
                    Proveedor 1
                </button>
                <button type="button" id="tab-poc-page-2" onclick="switchPocPage(2)" class="btn-po-tab alm-display-none cal-display-none" style="background: rgba(255,255,255,0.2); color: #ffffff; border: none; padding: 12px 28px; border-top-left-radius: 12px; border-top-right-radius: 12px; font-family: 'Poppins', sans-serif; font-weight: 600; font-size: 1.05em; cursor: pointer; transition: all 0.2s ease;">
                    Proveedor 2
                </button>

                <button type="button" id="btn-add-poc-page-2" onclick="agregarPocPagina2()" class="btns btn-add-tab" style="align-items: center; gap: 8px; padding: 10px 20px; background: rgba(255, 255, 255, 0.2); border: 2px dashed rgba(255, 255, 255, 0.6); border-radius: 30px; color: #ffffff; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 0.95em; font-weight: 600; transition: all 0.2s ease; margin-left: 15px;">
                    <img src="{{ asset('images/anadir.png') }}" style="width: 16px !important; height: 16px !important;" alt=""> Agregar Proveedor 2
                </button>
                <button type="button" id="btn-remove-poc-page-2" onclick="removerPocPagina2()" class="btns btn-remove-tab alm-display-none cal-display-none" style="align-items: center; gap: 8px; padding: 10px 20px; background: #dc2626; border: 1.5px solid #b91c1c; border-radius: 30px; color: #ffffff; cursor: pointer; font-family: 'Poppins', sans-serif; font-size: 0.95em; font-weight: 600; transition: all 0.2s ease; margin-left: 15px;">
                    Remover Proveedor 2
                </button>
            </div>
        </div>

        {{-- CUERPO DEL MODAL --}}
        <div class="alm-modal-body alm-padding-2-5em alm-background-fafafa alm-font-family-Poppins-sans-serif" style="background: #f8fafc; padding: 2em 2.5em;">
            <form id="formPreOrdenCasting" autocomplete="off">
                @csrf
                <input type="hidden" id="poc-has-page2" name="has_page2" value="0">

                {{-- PÁGINA 1 (PROVEEDOR 1) --}}
                <div id="poc-page-1" class="poc-page">
                    <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 18px; margin-bottom: 25px; background: #ffffff; padding: 20px 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div class="form-group">
                            <label for="poc-p1-proveedor" style="font-weight: 700; color: #0f172a; font-size: 0.95em; margin-bottom: 8px; display: block;">Proveedor <span style="color: #dc2626;">*</span>:</label>
                            <select id="poc-p1-proveedor" name="page1_proveedor" onchange="handlePocProveedorChange(1)" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #0284c7; font-family: 'Poppins', sans-serif; font-size: 0.95em; color: #0f172a; background: #ffffff; box-shadow: 0 2px 4px rgba(2,132,199,0.05);" required>
                                <option value="" disabled selected>-- Selecciona un proveedor --</option>
                                <option value="SS Metal Foundry, S. de R. L. de C. V.">SS Metal Foundry, S. de R. L. de C. V.</option>
                                <option value="SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS">SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="poc-p1-fecha" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Fecha Creación de Documento:</label>
                            <input type="date" id="poc-p1-fecha" name="page1_fecha" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #64748b; font-weight: 600;" readonly disabled title="Fecha del día de creación del documento (bloqueada)">
                        </div>
                        <div class="form-group">
                            <label for="poc-p1-folio" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Folio:</label>
                            <input type="text" id="poc-p1-folio" name="page1_folio" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #0369a1; font-weight: 800;" readonly>
                        </div>
                        <div class="form-group">
                            <label for="poc-p1-moldura" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Moldura:</label>
                            <input type="text" id="poc-p1-moldura" name="page1_moldura" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #334155;" readonly>
                        </div>
                        <div class="form-group">
                            <label for="poc-p1-ot" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Orden de Trabajo:</label>
                            <input type="text" id="poc-p1-ot" name="page1_ot" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #334155;" readonly>
                        </div>
{{-- El campo fecha_entrega ya no tiene un default global; cada fila de la tabla tiene su propia fecha de entrega --}}
                    </div>

                    {{-- TABLA PÁGINA 1 --}}
                    <div class="modal-table-container" style="overflow-x: auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 0; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
                        <table class="modal-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; font-weight: 700; font-size: 0.88em; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <th style="padding: 14px 12px; min-width: 120px;">Tipo de Modelo <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 95px;">Cant. Fabricar <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 95px;">Cant. Consign. <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 140px;">Descripción / Clase <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 160px;">Material <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 130px;">Código Modelo <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 90px;">Peso Juego</th>
                                    <th style="padding: 14px 12px; min-width: 90px;">Peso Total</th>
                                    <th style="padding: 14px 12px; min-width: 130px;">Fecha Entrega <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; text-align: center; min-width: 70px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="alm-tbody-poc-p1">
                            </tbody>
                        </table>
                        <div style="margin: 16px 0; text-align: center;">
                            <button type="button" id="btn-add-row-poc-p1" onclick="agregarFilaPoc(1)" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: #f0f9ff; border: 2px dashed #0284c7; border-radius: 30px; color: #0284c7; font-weight: 700; font-family: 'Poppins', sans-serif; font-size: 0.92em; cursor: pointer; transition: all 0.2s ease;">
                                <img src="{{ asset('images/anadir.png') }}" alt="Añadir" style="width: 18px; height: 18px;">
                                <span>+ Añadir otra clase / modelo</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <label for="poc-p1-observaciones" style="font-weight: 700; color: #0f172a; font-size: 0.95em; margin-bottom: 8px; display: block;">Observaciones (Proveedor 1):</label>
                        <textarea id="poc-p1-observaciones" name="page1_observaciones" style="width: 100%; min-height: 80px; border-radius: 12px; padding: 14px; font-family: 'Poppins', sans-serif; font-size: 0.95em; border: 1.5px solid #cbd5e1; box-sizing: border-box;" placeholder="Escribe observaciones adicionales para el proveedor 1..."></textarea>
                    </div>
                </div>

                {{-- PÁGINA 2 (PROVEEDOR 2) --}}
                <div id="poc-page-2" class="poc-page alm-display-none cal-display-none">
                    <div class="form-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 18px; margin-bottom: 25px; background: #ffffff; padding: 20px 24px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.03);">
                        <div class="form-group">
                            <label for="poc-p2-proveedor" style="font-weight: 700; color: #0f172a; font-size: 0.95em; margin-bottom: 8px; display: block;">Proveedor 2 <span style="color: #dc2626;">*</span>:</label>
                            <select id="poc-p2-proveedor" name="page2_proveedor" onchange="handlePocProveedorChange(2)" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #0284c7; font-family: 'Poppins', sans-serif; font-size: 0.95em; color: #0f172a; background: #ffffff;" required>
                                <option value="" disabled selected>-- Selecciona un proveedor --</option>
                                <option value="SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS">SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS</option>
                                <option value="SS Metal Foundry, S. de R. L. de C. V.">SS Metal Foundry, S. de R. L. de C. V.</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="poc-p2-fecha" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Fecha Creación de Documento:</label>
                            <input type="date" id="poc-p2-fecha" name="page2_fecha" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #64748b; font-weight: 600;" readonly disabled title="Fecha del día de creación del documento (bloqueada)">
                        </div>
                        <div class="form-group">
                            <label for="poc-p2-folio" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Folio:</label>
                            <input type="text" id="poc-p2-folio" name="page2_folio" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #0369a1; font-weight: 800;" readonly>
                        </div>
                        <div class="form-group">
                            <label for="poc-p2-moldura" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Moldura:</label>
                            <input type="text" id="poc-p2-moldura" name="page2_moldura" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #334155;" readonly>
                        </div>
                        <div class="form-group">
                            <label for="poc-p2-ot" style="font-weight: 700; color: #475569; font-size: 0.95em; margin-bottom: 8px; display: block;">Orden de Trabajo:</label>
                            <input type="text" id="poc-p2-ot" name="page2_ot" class="form-control" style="width: 100%; height: 44px; padding: 8px 14px; border-radius: 10px; border: 1.5px solid #cbd5e1; font-family: 'Poppins', sans-serif; font-size: 0.95em; background: #f1f5f9; color: #334155;" readonly>
                        </div>
                        {{-- Sin fecha de entrega default — cada fila de la tabla tiene su propia fecha --}}
                    </div>

                    {{-- TABLA PÁGINA 2 --}}
                    <div class="modal-table-container" style="overflow-x: auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 0; box-shadow: 0 4px 16px rgba(0,0,0,0.04);">
                        <table class="modal-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                            <thead>
                                <tr style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; font-weight: 700; font-size: 0.88em; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <th style="padding: 14px 12px; min-width: 120px;">Tipo de Modelo <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 95px;">Cant. Fabricar <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 95px;">Cant. Consign. <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 140px;">Descripción / Clase <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 160px;">Material <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 130px;">Código Modelo <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; min-width: 90px;">Peso Juego</th>
                                    <th style="padding: 14px 12px; min-width: 90px;">Peso Total</th>
                                    <th style="padding: 14px 12px; min-width: 130px;">Fecha Entrega <span style="color: #f87171;">*</span></th>
                                    <th style="padding: 14px 12px; text-align: center; min-width: 70px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="alm-tbody-poc-p2">
                            </tbody>
                        </table>
                        <div style="margin: 16px 0; text-align: center;">
                            <button type="button" id="btn-add-row-poc-p2" onclick="agregarFilaPoc(2)" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: #f0f9ff; border: 2px dashed #0284c7; border-radius: 30px; color: #0284c7; font-weight: 700; font-family: 'Poppins', sans-serif; font-size: 0.92em; cursor: pointer; transition: all 0.2s ease;">
                                <img src="{{ asset('images/anadir.png') }}" alt="Añadir" style="width: 18px; height: 18px;">
                                <span>+ Añadir otra clase / modelo</span>
                            </button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 25px;">
                        <label for="poc-p2-observaciones" style="font-weight: 700; color: #0f172a; font-size: 0.95em; margin-bottom: 8px; display: block;">Observaciones (Proveedor 2):</label>
                        <textarea id="poc-p2-observaciones" name="page2_observaciones" style="width: 100%; min-height: 80px; border-radius: 12px; padding: 14px; font-family: 'Poppins', sans-serif; font-size: 0.95em; border: 1.5px solid #cbd5e1; box-sizing: border-box;" placeholder="Escribe observaciones adicionales para el proveedor 2..."></textarea>
                    </div>
                </div>

                {{-- BOTÓN DE GUARDAR GLOBAL --}}
                <div class="form-actions" style="margin-top: 35px; text-align: center;">
                    <button type="submit" id="btn-submit-poc" class="btn-save-preorden" disabled style="font-size: 1.15em; padding: 16px 42px; border-radius: 14px; font-family: 'Poppins', sans-serif; font-weight: 700; background: linear-gradient(135deg, #0369a1 0%, #0284c7 100%); border: none; color: #ffffff; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 8px 24px rgba(3, 105, 161, 0.35); height: auto; letter-spacing: 0.5px;">
                        <i class="fas fa-file-pdf" style="margin-right: 8px;"></i> Guardar y Descargar Pre-Orden de Casting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formPreOrdenCasting");
    if(!form) return;
    const btn = document.getElementById("btn-submit-poc");
    const hasPage2Input = document.getElementById("poc-has-page2");
    
    function checkCastingFormValidity() {
        if(!btn) return;
        
        // Manejar dinámicamente los campos requeridos para evitar errores de HTML5
        const checkInputs = form.querySelectorAll("input[required], select[required], textarea[required], [data-was-required='true']");
        let isValid = true;
        
        checkInputs.forEach(input => {
            if (!input.hasAttribute("data-was-required")) {
                input.setAttribute("data-was-required", "true");
            }
            if (input.offsetParent === null) {
                // Oculto (ej. página 2 no activa): quitamos el required
                input.removeAttribute("required");
            } else {
                // Visible: restauramos el required y validamos
                input.setAttribute("required", "required");
                if (!input.value || !String(input.value).trim()) isValid = false;
            }
        });
        
        const p1HasRows = document.getElementById("alm-tbody-poc-p1") && document.getElementById("alm-tbody-poc-p1").querySelectorAll("tr").length > 0;
        if (!p1HasRows) isValid = false;
        
        if (hasPage2Input && hasPage2Input.value === "1") {
            const p2HasRows = document.getElementById("alm-tbody-poc-p2") && document.getElementById("alm-tbody-poc-p2").querySelectorAll("tr").length > 0;
            if (!p2HasRows) isValid = false;
        }
        
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

    form.addEventListener("input", checkCastingFormValidity);
    form.addEventListener("change", checkCastingFormValidity);
    
    // Check periodically in case rows are added/removed dynamically
    setInterval(checkCastingFormValidity, 500);
});
</script>
