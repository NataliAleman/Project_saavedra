document.addEventListener("DOMContentLoaded", function () {
    const btnCreate = document.getElementById("btn-mode-create");
    const btnModify = document.getElementById("btn-mode-modify");
    const formCreate = document.getElementById("form-create-master-wo");
    const formUpdate = document.getElementById("form-update-master-wo");

    if (btnCreate && btnModify) {
        btnCreate.addEventListener("click", function () {
            btnCreate.classList.add("active");
            btnModify.classList.remove("active");
            formCreate.style.display = "block";
            formUpdate.style.display = "none";
        });

        btnModify.addEventListener("click", function () {
            btnModify.classList.add("active");
            btnCreate.classList.remove("active");
            formUpdate.style.display = "block";
            formCreate.style.display = "none";
        });
    }

    // Input sanitization & formatting for OT inputs
    const otInputs = [document.getElementById("workOrder")];
    otInputs.forEach(input => {
        if (input) {
            input.addEventListener("input", function () {
                this.value = this.value.replace(/[^0-9]/g, "").slice(0, 5);
            });
        }
    });

    const ocInputs = [document.getElementById("orden_compra"), document.getElementById("mod_orden_compra")];
    ocInputs.forEach(input => {
        if (input) {
            input.addEventListener("input", function () {
                let val = this.value.toUpperCase();
                val = val.replace(/\s+/g, "");
                val = val.replace(/[^A-Z0-9-]/g, "");
                this.value = val.slice(0, 25);
            });
        }
    });

    // Manejar selección de OT en modo Modificar
    const woSelect = document.getElementById("workOrderSelect");
    const btnSaveModify = document.getElementById("btn-save-modify");
    let initialValues = null;

    function checkChanges() {
        if (!btnSaveModify || !initialValues) return;
        let hasChanged = false;

        const fields = {
            fecha_compra: document.getElementById("mod_fecha_compra"),
            orden_compra: document.getElementById("mod_orden_compra"),
            cliente: document.getElementById("mod_cliente"),
            material: document.getElementById("mod_material"),
            fecha_entrega_cliente: document.getElementById("mod_fecha_entrega_cliente")
        };

        for (let key in fields) {
            if (fields[key]) {
                const currentVal = fields[key].value.trim();
                const initVal = (initialValues[key] ?? "").trim();
                if (currentVal !== initVal) {
                    hasChanged = true;
                    break;
                }
            }
        }

        const MATERIAL_OPTIONS = [
            "HG - SS10", "HG - SS10CR", "HG - SS20", "HG - 50V", "HG - DUCTIL 654512",
            "SSMF - MINOX", "DAMERON", "DAMERON - SSMF", "1018", "4140",
            "INOX 304", "INOX 316", "INOX 416", "ALUMINIO"
        ];

        const FOUNDRY_PROVIDERS = [
            "SS Metal Foundry, S. de R. L. de C. V.",
            "SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS",
            "EXTERNO"
        ];

        if (!hasChanged && initialValues.class_orders) {
            for (let clId in initialValues.class_orders) {
                const classInput = document.querySelector(`input[name="class_orders[${clId}]"]`);
                if (classInput && !classInput.disabled) { // Si está deshabilitado es porque se eliminó
                    const currentQty = String(classInput.value).trim();
                    const initQty = String(initialValues.class_orders[clId]).trim();
                    if (currentQty !== initQty) {
                        hasChanged = true;
                        break;
                    }
                }
            }
        }

        if (!hasChanged && initialValues.class_materials) {
            for (let clId in initialValues.class_materials) {
                const matSelect = document.querySelector(`select[name="class_materials[${clId}]"]`);
                if (matSelect && !matSelect.disabled) {
                    const currentMat = String(matSelect.value).trim();
                    const initMat = String(initialValues.class_materials[clId] ?? "").trim();
                    if (currentMat !== initMat) {
                        hasChanged = true;
                        break;
                    }
                }
            }
        }

        if (!hasChanged && initialValues.class_proveedores) {
            for (let clId in initialValues.class_proveedores) {
                const provSelect = document.querySelector(`select[name="class_proveedores[${clId}]"]`);
                if (provSelect && !provSelect.disabled) {
                    const currentProv = String(provSelect.value).trim();
                    const initProv = String(initialValues.class_proveedores[clId] ?? "").trim();
                    if (currentProv !== initProv) {
                        hasChanged = true;
                        break;
                    }
                }
            }
        }

        // Si hay filas nuevas de clases agregadas manualmente, también habilitamos el botón
        if (!hasChanged) {
            const newClassRows = document.querySelectorAll("select[name^='new_classes']");
            if (newClassRows.length > 0) {
                hasChanged = true;
            }
        }
        
        // Si hay clases que se van a eliminar, también habilitamos
        if (!hasChanged) {
            const deletedClasses = document.querySelectorAll("input[name='deleted_classes[]']");
            if (deletedClasses.length > 0) {
                hasChanged = true;
            }
        }

        btnSaveModify.disabled = !hasChanged;
        btnSaveModify.style.opacity = hasChanged ? "1" : "0.5";
        btnSaveModify.style.cursor = hasChanged ? "pointer" : "not-allowed";
    }

    const CLASS_OPTIONS = [
        "1 - MOLDES",
        "2 - FONDOS",
        "3 - BOMBILLOS",
        "4 - OBTURADORES",
        "5 - EMBUDOS",
        "6 - CORONA",
        "7 - GUIA VIAJERA",
        "8 - GUIA LIMITADORA",
        "9 - CABEZA DE SOPLO",
        "10 - PISTONES",
        "11 - ENFRIADORES",
        "12 - BASES P/OBTURAD",
        "13 - INSERTOS",
        "14 - PIPETAS",
        "15 - PLACAS",
        "16 - CENTRALIZADOR",
        "17 - GAUGE",
        "18 - MOUL",
        "23 - NECKRING",
        "28 - TIP P/ OBTURADOR",
        "29 - CARCAZA",
        "30 - CASQUILLO",
        "31 - RONDANA",
        "32 - LOTE",
        "33 - PLATO MOLDE",
        "34 - PLATO BOMBILLO",
        "35 - 1/2 CAÑA",
        "36 - CAMISA DE",
        "37 - INSERTO DE CARBURO",
        "39 - BIAS UNIT",
        "40 - CUERPO",
        "41 - BLOCK",
        "42 - SEMI",
        "43 - TOP PLATE",
        "44 - POSTIZO",
        "45 - TUBO",
        "46 - FLETE",
        "47 - DOMMI",
        "48 - SERVICIO",
        "61 - ANILLO DE CEDASO 3\"",
        "65 - FUNDICION DE",
        "66 - FUNDICION DE",
        "67 - 1/2 CAÑA LADO MACHO",
        "68 - 1/2 CAÑA LADO",
        "71 - CENTRALIZADOR 3.4",
        "72 - CENTRALIZADOR 3.875",
        "74 - DEDOS",
        "76 - KINKER",
        "79 - LAINA",
        "80 - REPARACION",
        "83 - VARIOS",
        "86 - PERNOS DE",
        "87 - ARRASTRADORES",
        "89 - CADENA INDUSTRIAL",
        "91 - RESORTE",
        "92 - CANDADO",
        "94 - ANILLO",
        "95 - PORTA CORONA",
        "97 - TEJO",
        "99 - BASE PARA PISTON",
        "100 - CANASTILLA PORTA",
        "101 - FABRICACION",
        "102 - BASE PARADORA",
        "103 - BASE PORTA MOLDE",
        "104 - CALIBRADOR",
        "106 - MOLDE SEMI",
        "107 - PLATO MOLDE SEMI",
        "108 - BOMBILLO SEMI",
        "109 - PLATO BOMBILLO",
        "110 - CORONA SEMI",
        "111 - PASTILLAS CORONA",
        "112 - PISTON SEMI",
        "113 - FONDO SEMI",
        "114 - GUIA VIAJERA SEMI",
        "115 - CASQUILLO ALTURA",
        "116 - RONDANA ALUMINIO",
        "117 - SEGURO OMEGA 1",
        "118 - SEGURO OMEGA 2",
        "119 - VALVULA HEXAGONAL",
        "120 - SELLO",
        "121 - ROLL PIN OBTURADOR"
    ];

    const MATERIAL_OPTIONS = [
        "HG - SS10", "HG - SS10CR", "HG - SS20", "HG - 50V", "HG - DUCTIL 654512",
        "SSMF - MINOX", "DAMERON", "DAMERON - SSMF", "1018", "4140",
        "INOX 304", "INOX 316", "INOX 416", "ALUMINIO"
    ];

    const FOUNDRY_PROVIDERS = [
        "SS Metal Foundry, S. de R. L. de C. V.",
        "SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS",
        "EXTERNO"
    ];

    if (woSelect && window.workOrdersData) {
        const populateFields = () => {
            const woId = woSelect.value;
            if (!woId) return;

            const wo = window.workOrdersData.find(w => w.id == woId);
            if (wo) {
                // Limpiar inputs ocultos de clases eliminadas de la OT anterior
                document.querySelectorAll("input[name='deleted_classes[]']").forEach(el => el.remove());
                
                formUpdate.querySelector("#mod_molding").value = wo.moldura?.nombre || "Sin Moldura";
                formUpdate.querySelector("#mod_fecha_compra").value = wo.fecha_compra || "";
                document.getElementById("mod_orden_compra").value = wo.orden_compra ?? "";
                
                const clienteSelect = document.getElementById("mod_cliente");
                if (clienteSelect) {
                    const clientVal = wo.cliente ?? "";
                    if (clientVal && !Array.from(clienteSelect.options).some(o => o.value === clientVal)) {
                        const opt = document.createElement("option");
                        opt.value = clientVal;
                        opt.textContent = clientVal;
                        clienteSelect.appendChild(opt);
                    }
                    clienteSelect.value = clientVal;
                }

                const matSelect = document.getElementById("mod_material");
                if (matSelect) {
                    matSelect.value = wo.material ?? "";
                }

                document.getElementById("mod_fecha_entrega_cliente").value = wo.fecha_entrega_cliente ?? "";

                // Guardar estado inicial para detectar cambios
                initialValues = {
                    fecha_compra: wo.fecha_compra ?? "",
                    orden_compra: wo.orden_compra ?? "",
                    cliente: wo.cliente ?? "",
                    material: wo.material ?? "",
                    fecha_entrega_cliente: wo.fecha_entrega_cliente ?? "",
                    class_orders: {},
                    class_materials: {},
                    class_proveedores: {}
                };

                // Llenar tabla de clases
                const container = document.getElementById("mod_classes_container");
                const tbody = document.getElementById("mod_classes_tbody");
                if (container && tbody) {
                    tbody.innerHTML = "";
                    if (wo.clases && wo.clases.length > 0) {
                        container.style.display = "block";
                        wo.clases.forEach(cl => {
                            initialValues.class_orders[cl.id] = String(cl.pedido ?? 0);
                            initialValues.class_materials[cl.id] = cl.material ?? "";
                            initialValues.class_proveedores[cl.id] = cl.proveedor ?? "";
                            
                            let matOptionsHtml = `<option value="" ${!cl.material ? 'selected' : ''}>-- Seleccione Material --</option>`;
                            MATERIAL_OPTIONS.forEach(mat => {
                                matOptionsHtml += `<option value="${mat}" ${cl.material === mat ? 'selected' : ''}>${mat}</option>`;
                            });
                            if (cl.material && !MATERIAL_OPTIONS.includes(cl.material)) {
                                matOptionsHtml += `<option value="${cl.material}" selected>${cl.material}</option>`;
                            }

                            let provOptionsHtml = `<option value="" ${(!cl.proveedor || cl.proveedor === '') ? 'selected' : ''}>-- Sin Proveedor / Opcional --</option>`;
                            FOUNDRY_PROVIDERS.forEach(prov => {
                                provOptionsHtml += `<option value="${prov}" ${cl.proveedor === prov ? 'selected' : ''}>${prov}</option>`;
                            });
                            if (cl.proveedor && !FOUNDRY_PROVIDERS.includes(cl.proveedor)) {
                                provOptionsHtml += `<option value="${cl.proveedor}" selected>${cl.proveedor}</option>`;
                            }

                            const tr = document.createElement("tr");
                            tr.innerHTML = `
                                <td><strong class="existing-class-name">${cl.nombre}</strong></td>
                                <td>
                                    <select name="class_materials[${cl.id}]" class="form-control class-mat-select" style="width: 100%;">
                                        ${matOptionsHtml}
                                    </select>
                                </td>
                                <td>
                                    <select name="class_proveedores[${cl.id}]" class="form-control class-prov-select" style="width: 100%;">
                                        ${provOptionsHtml}
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="class_orders[${cl.id}]" value="${cl.pedido ?? 0}" min="0" required class="form-control class-qty-input">
                                </td>
                                <td>
                                    <button type="button" class="btn-remove-existing-class" data-class-id="${cl.id}" style="background: #dc3545; color: white; border: none; padding: 3px 8px; border-radius: 4px; cursor: pointer;">Eliminar</button>
                                </td>
                            `;
                            tbody.appendChild(tr);
                            
                            const btnRemoveExisting = tr.querySelector(".btn-remove-existing-class");
                            if (btnRemoveExisting) {
                                btnRemoveExisting.addEventListener("click", function() {
                                    if(confirm("¿Estás seguro de que deseas quitar esta clase? Se eliminará definitivamente al guardar los cambios.")) {
                                        // Ocultar la fila
                                        tr.style.display = "none";
                                        // Deshabilitar inputs para que no se envíen
                                        const qtyInput = tr.querySelector(".class-qty-input");
                                        if (qtyInput) qtyInput.disabled = true;
                                        const matSelect = tr.querySelector(".class-mat-select");
                                        if (matSelect) matSelect.disabled = true;
                                        const provSelect = tr.querySelector(".class-prov-select");
                                        if (provSelect) provSelect.disabled = true;
                                        
                                        // Agregar input hidden para eliminar la clase
                                        const hiddenDelete = document.createElement("input");
                                        hiddenDelete.type = "hidden";
                                        hiddenDelete.name = "deleted_classes[]";
                                        hiddenDelete.value = cl.id;
                                        document.getElementById("form-update-master-wo").appendChild(hiddenDelete);
                                        
                                        checkChanges();
                                    }
                                });
                            }
                        });
                    } else {
                        container.style.display = "block";
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Aún no se han registrado clases para esta Orden de Trabajo.</td></tr>`;
                    }
                }

                // Escuchar eventos en los campos del formulario de modificación
                const modInputs = formUpdate.querySelectorAll("input, select");
                modInputs.forEach(el => {
                    el.removeEventListener("input", checkChanges);
                    el.removeEventListener("change", checkChanges);
                    el.addEventListener("input", checkChanges);
                    el.addEventListener("change", checkChanges);
                });

                // Resetear estado del botón a deshabilitado
                checkChanges();
            }
        };

        let newClassCounter = 0;
        const btnAddNewClass = document.getElementById("btn-add-new-class");
        if (btnAddNewClass) {
            btnAddNewClass.addEventListener("click", function() {
                const tbody = document.getElementById("mod_classes_tbody");
                let newMatOptionsHtml = `<option value="" disabled selected>Seleccione Material</option>`;
                MATERIAL_OPTIONS.forEach(mat => {
                    newMatOptionsHtml += `<option value="${mat}">${mat}</option>`;
                });

                let newProvOptionsHtml = `<option value="" selected>-- Sin Proveedor / Opcional --</option>`;
                FOUNDRY_PROVIDERS.forEach(prov => {
                    newProvOptionsHtml += `<option value="${prov}">${prov}</option>`;
                });

                let newClassOptionsHtml = `<option value="" disabled selected>Seleccione</option>`;
                CLASS_OPTIONS.forEach(cls => {
                    newClassOptionsHtml += `<option value="${cls}">${cls}</option>`;
                });

                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>
                        <select name="new_classes[${newClassCounter}][nombre]" required class="form-control" style="width: 100%;">
                            ${newClassOptionsHtml}
                        </select>
                    </td>
                    <td>
                        <select name="new_classes[${newClassCounter}][material]" class="form-control class-mat-select" style="width: 100%;">
                            ${newMatOptionsHtml}
                        </select>
                    </td>
                    <td>
                        <select name="new_classes[${newClassCounter}][proveedor]" class="form-control class-prov-select" style="width: 100%;">
                            ${newProvOptionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="number" name="new_classes[${newClassCounter}][cantidad]" value="" min="1" required class="form-control class-qty-input">
                    </td>
                    <td>
                        <button type="button" class="btn-remove-new-class" style="background: #dc3545; color: white; border: none; padding: 3px 8px; border-radius: 4px; cursor: pointer;">X</button>
                    </td>
                `;
                tbody.appendChild(tr);
                
                // Re-bind change listeners
                const selectsAndInputs = tr.querySelectorAll("select, input");
                selectsAndInputs.forEach(el => {
                    el.addEventListener("input", checkChanges);
                    el.addEventListener("change", checkChanges);
                });
                
                // Validación para no permitir clases repetidas
                const classSelect = tr.querySelector("select");
                if (classSelect) {
                    classSelect.addEventListener("change", function() {
                        let selectedVal = this.value;
                        if (!selectedVal) return;
                        
                        let duplicate = false;
                        
                        // 1. Revisar en clases existentes (incluso si están "eliminadas" visualmente, aunque no deberían contar si las vas a eliminar)
                        // Para prevenir problemas, revisamos las que están visibles
                        const existingNames = [];
                        document.querySelectorAll("#mod_classes_tbody tr:not([style*='display: none']) .existing-class-name").forEach(el => {
                            existingNames.push(el.textContent.trim());
                        });
                        
                        if (existingNames.includes(selectedVal)) {
                            duplicate = true;
                        } else {
                            // 2. Revisar en otros selects de clases nuevas
                            let count = 0;
                            document.querySelectorAll("select[name^='new_classes']").forEach(sel => {
                                if (sel.value === selectedVal) count++;
                            });
                            if (count > 1) duplicate = true; // Si hay más de 1 es porque este mismo select lo seleccionó y ya existía en otro
                        }
                        
                        if (duplicate) {
                            alert("La clase '" + selectedVal + "' ya se encuentra registrada o seleccionada en esta Orden de Trabajo.");
                            this.value = "";
                            checkChanges();
                        }
                    });
                }
                
                // Remove button logic
                tr.querySelector(".btn-remove-new-class").addEventListener("click", function() {
                    tr.remove();
                    checkChanges();
                });
                
                newClassCounter++;
                checkChanges(); // enable save button
            });
        }

        woSelect.addEventListener("change", populateFields);

        // Si ya hay una OT seleccionada por URL/query param o por defecto, cargar sus datos
        if (woSelect.value) {
            populateFields();
        }
    }
});
