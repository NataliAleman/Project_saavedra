// ── MODAL PRE-ORDEN ───────────────────────────────────────────────────────────
let availableClasses = []; // Caché de clases para las filas nuevas
let optionsHtmlCache = ""; // Caché del HTML de las opciones para evitar reconstruir en cada fila
const normalizeStr = (str) => {
    if (!str) return "";
    return str
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();
};

// ── Clases que usan N/A en impresiones ─────────────────────────────────────────
const CLASES_SIN_IMPRESIONES = ["molde", "bombillo", "embudo", "corona", "plato"];

/**
 * Si la clase seleccionada no usa impresiones, pone "N/A" y bloquea el campo.
 * Si sí usa impresiones, lo desbloquea.
 */
window.actualizarInputImpresiones = function (claseSelect) {
    const row = claseSelect.closest("tr");
    if (!row) return;
    const inputImp = row.querySelector(".po-impresiones");
    if (!inputImp) return;
    const nombreClase = (
        claseSelect.options[claseSelect.selectedIndex]?.dataset?.nombre ||
        claseSelect.options[claseSelect.selectedIndex]?.text ||
        ""
    ).toLowerCase();
    const sinImpresiones = CLASES_SIN_IMPRESIONES.some((c) => nombreClase.includes(c));
    if (sinImpresiones) {
        inputImp.value = "N/A";
        inputImp.readOnly = true;
        inputImp.style.backgroundColor = "#f1f5f9";
        inputImp.style.color = "#94a3b8";
    } else {
        if (inputImp.value === "N/A") inputImp.value = "";
        inputImp.readOnly = false;
        inputImp.style.backgroundColor = "";
        inputImp.style.color = "";
    }
};

/**
 * Aplica actualizarInputImpresiones a todas las filas de un tbody.
 */
function aplicarBloqueoImpresionesEnTodas(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    tbody.querySelectorAll("select.po-clase-select").forEach((sel) => {
        window.actualizarInputImpresiones(sel);
    });
}
window.aplicarBloqueoImpresionesEnTodas = aplicarBloqueoImpresionesEnTodas;


// ── Apertura / Cierre del modal ──
// Lista de clases ya procesadas (enviadas) en esta sesión del modal
window._clasesYaProcesadasEnModelo = [];
window.abrirModalPreOrden = function (ot, clasesYaProcesadas = []) {
    const modal = document.getElementById("modalPreOrden");
    const inputOt = document.getElementById("po-ot");
    const inputMoldura = document.getElementById("po-moldura");
    const tbody = document.getElementById("alm-tbody-preorden");
    window.currentFechaEntrega = "";
    window.predefinedCycleObs = "";
    const prefixDiv = document.getElementById("po-observaciones-cycle-prefix");
    if (prefixDiv) {
        prefixDiv.classList.add("alm-display-none");
        prefixDiv.textContent = "";
    }
    // Guardar clases ya procesadas para filtrar en el modal
    window._clasesYaProcesadasEnModelo = Array.isArray(clasesYaProcesadas)
        ? clasesYaProcesadas.map((c) => c.toLowerCase())
        : [];
    // Mostrar/ocultar badge de ciclo de re-fabricación
    const cycleMatch = ot.match(/_R(\d+)$/i);
    const cycleBadge = document.getElementById("po-modal-cycle-badge");
    if (cycleBadge) {
        if (cycleMatch) {
            cycleBadge.textContent = `Ciclo: R${cycleMatch[1]}`;
            cycleBadge.classList.remove("alm-display-none");
        } else {
            cycleBadge.classList.add("alm-display-none");
        }
    }
    // Resetear estado multi-orden (botón añadir siempre visible)
    resetMultiOrderState();
    // Separar OT y Moldura si vienen juntas (ej. "6473 - VINERA...")
    let otNum = ot;
    let molduraName = "";
    if (ot.includes(" - ")) {
        const parts = ot.split(" - ");
        otNum = parts[0].trim();
        molduraName = parts
            .slice(1)
            .join(" - ")
            .trim()
            .replace(/_\d{8}_\d{6}_.*/, "");
    }
    // Dejar solo los números de la OT (por ejemplo, "OT 6748" pasa a "6748")
    otNum = otNum.split("_")[0].replace(/[^0-9]/g, "");
    inputOt.value = otNum;
    document.getElementById("po-ot-raw").value = ot;
    if (molduraName) inputMoldura.value = molduraName;
    tbody.innerHTML =
        '<tr><td colspan="6" style="text-align:center; padding:20px;"><div class="alm-spinner"></div> Cargando datos de la OT...</td></tr>';
    modal.classList.add("open");
    document.body.classList.add("modal-open");
    // Cargar datos de la OT (Moldura y Clases activas)
    fetch(`${window.almacenRoutes.getOtData}?ot=${ot}`)
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                inputMoldura.value = data.moldura || "N/A";
                availableClasses = data.clases || [];
                availableClasses.forEach((c) => {
                    c._norm = normalizeStr(c.nombre);
                });
                if (data.clases_vinculadas) {
                    data.clases_vinculadas.forEach((cv) => {
                        const cvNorm = normalizeStr(cv);
                        const found = availableClasses.find(
                            (ac) =>
                                ac._norm === cvNorm ||
                                ac._norm.includes(cvNorm) ||
                                cvNorm.includes(ac._norm),
                        );
                        if (!found) {
                            availableClasses.push({
                                id: `manual_${cv}`,
                                nombre: cv,
                                _norm: cvNorm,
                            });
                        }
                    });
                }
                // Construir opciones de select, excluyendo las clases ya procesadas (reactivo)
                optionsHtmlCache = buildClaseOptionsForSelect(
                    availableClasses,
                    null,
                    null,
                );
                if (data.folio)
                    document.getElementById("po-folio").value = data.folio;
                tbody.innerHTML = "";
                if (data.pre_orden_data) {
                    const pod = data.pre_orden_data;
                    window.currentFechaEntrega = pod.fecha_entrega
                        ? pod.fecha_entrega.split(" ")[0]
                        : "";
                    if (pod.fecha_creacion) {
                        const dateOnly = pod.fecha_creacion.split(" ")[0];
                        document.getElementById("po-fecha").value = dateOnly;
                    }
                    // Para OTs de re-proceso (_R1, _R2...), generar una observación descriptiva
                    const obsField =
                        document.getElementById("po-observaciones");
                    if (obsField) {
                        if (cycleMatch) {
                            const cycle = parseInt(cycleMatch[1], 10);
                            const ordinal =
                                cycle === 1
                                    ? "2.ª"
                                    : cycle === 2
                                        ? "3.ª"
                                        : `${cycle + 1}.ª`;
                            window.predefinedCycleObs = `[${ordinal} vuelta — Ciclo R${cycle} de liberación de modelo y fabricación de casting]`;
                            const prefixDiv = document.getElementById(
                                "po-observaciones-cycle-prefix",
                            );
                            if (prefixDiv) {
                                prefixDiv.textContent =
                                    window.predefinedCycleObs;
                                prefixDiv.classList.remove("alm-display-none");
                            }
                            // Si la observación existente tiene el prefijo de ciclo o de RECHAZO, limpiarlo
                            let rawObs = (pod.observaciones || "")
                                .replace(/^RECHAZO:\s*/i, "")
                                .trim();
                            rawObs = rawObs
                                .replace(
                                    /^\[.*? vuelta — Ciclo R\d+ de liberación de modelo y fabricación de casting\]\s*/i,
                                    "",
                                )
                                .trim();
                            obsField.value = rawObs;
                        } else if (pod.observaciones) {
                            obsField.value = pod.observaciones;
                        }
                    }
                    if (pod.proveedor)
                        document.getElementById("po-proveedor").value =
                            pod.proveedor;
                    if (pod.filas && pod.filas.length > 0) {
                        const fragment = document.createDocumentFragment();
                        pod.filas.forEach((rowObj) => {
                            const claseId = rowObj.id_clase || rowObj.clase_id;
                            fragment.appendChild(
                                createRowElement("", false, {
                                    tipo_modelo: rowObj.tipo_modelo,
                                    impresiones: rowObj.impresiones,
                                    cantidad: rowObj.cantidad,
                                    clase_id: claseId,
                                    codigo_modelo: rowObj.codigo_modelo,
                                }),
                            );
                        });
                        tbody.appendChild(fragment);
                    } else {
                        tbody.appendChild(createRowElement());
                    }
                } else {
                    // Sin pre-orden existente — si es re-proceso, poner observación predeterminada
                    if (cycleMatch) {
                        const cycle = parseInt(cycleMatch[1], 10);
                        const ordinal =
                            cycle === 1
                                ? "2.ª"
                                : cycle === 2
                                    ? "3.ª"
                                    : `${cycle + 1}.ª`;
                        window.predefinedCycleObs = `[${ordinal} vuelta — Ciclo R${cycle} de liberación de modelo y fabricación de casting]`;
                        const prefixDiv = document.getElementById(
                            "po-observaciones-cycle-prefix",
                        );
                        if (prefixDiv) {
                            prefixDiv.textContent = window.predefinedCycleObs;
                            prefixDiv.classList.remove("alm-display-none");
                        }
                        const obsField =
                            document.getElementById("po-observaciones");
                        if (obsField) {
                            obsField.value = "";
                        }
                    }
                    if (
                        data.clases_vinculadas &&
                        data.clases_vinculadas.length > 0
                    ) {
                        const yaUsadas = (
                            window._clasesYaProcesadasEnModelo || []
                        ).filter((yp) => yp && yp.trim() !== "");
                        const clasesPendientes = data.clases_vinculadas.filter(
                            (claseNombre) => {
                                const nombreNorm = claseNombre.toLowerCase();
                                return !yaUsadas.some(
                                    (yp) =>
                                        nombreNorm.includes(yp) ||
                                        yp.includes(nombreNorm),
                                );
                            },
                        );
                        if (clasesPendientes.length > 0) {
                            const fragment = document.createDocumentFragment();
                            clasesPendientes.forEach((claseNombre) => {
                                fragment.appendChild(
                                    createRowElement(claseNombre),
                                );
                            });
                            tbody.appendChild(fragment);
                        } else {
                            tbody.appendChild(createRowElement());
                        }
                        syncClassOptions("alm-tbody-preorden");
                    } else {
                        tbody.appendChild(createRowElement());
                    }
                }
                // Bloque 3a: Aplicar bloqueo de Impresiones a todas las filas ya cargadas
                // (Molde y Bombillo siempre N/A sin importar el origen de los datos)
                setTimeout(
                    () =>
                        aplicarBloqueoImpresionesEnTodas("alm-tbody-preorden"),
                    0,
                );
                // Datos cargados con éxito
            } else {
                mostrarToast(
                    data.message || "Error al cargar datos de la OT",
                    true,
                );
                cerrarModalPreOrden();
            }
        })
        .catch((err) => {
            console.error(err);
            mostrarToast("Error al obtener datos", true);
        });
};
window.cerrarModalPreOrden = function () {
    const modal = document.getElementById("modalPreOrden");
    modal.classList.remove("open");
    document.body.classList.remove("modal-open");
    document.getElementById("formPreOrden").reset();
    document.getElementById("alm-tbody-preorden").innerHTML = "";
    optionsHtmlCache = "";
    window.currentFechaEntrega = "";
    resetMultiOrderState();
};
function resetMultiOrderState() {
    // No-op para mantener compatibilidad
}
// ── Creación de filas de la tabla ──
/**
 * Crea un elemento TR para la tabla de pre-orden.
 * @param {string} claseNombrePredefinida - Nombre de clase a preseleccionar.
 * @param {boolean} isSecondOrder - Si es para la tabla 2, usa el tbody2.
 */
function createRowElement(
    claseNombrePredefinida = "",
    isSecondOrder = false,
    rowObj = null,
) {
    const tr = document.createElement("tr");
    const fechaEntregaVal =
        rowObj && rowObj.fecha_entrega
            ? rowObj.fecha_entrega
            : window.currentFechaEntrega || "";
    let selectedId = "";
    if (rowObj && rowObj.clase_id) {
        selectedId = rowObj.clase_id;
    } else if (claseNombrePredefinida) {
        const search = normalizeStr(claseNombrePredefinida);
        const found = availableClasses.find(
            (c) =>
                c._norm === search ||
                c._norm.includes(search) ||
                search.includes(c._norm),
        );
        if (found) selectedId = found.id;
    }
    let options = optionsHtmlCache;
    if (selectedId) {
        options =
            '<option value="">Selecciona clase</option>' +
            availableClasses
                .map((c) => {
                    const selectedAttr = selectedId == c.id ? "selected" : "";
                    return `<option value="${c.id}" data-nombre="${c.nombre}" ${selectedAttr}>${c.nombre}</option>`;
                })
                .join("");
    }
    const deleteHandler = isSecondOrder
        ? "eliminarFilaPreOrden2(this)"
        : "eliminarFilaPreOrden(this)";
    const tipoVal = rowObj && rowObj.tipo_modelo ? rowObj.tipo_modelo : "";
    const impresionesVal =
        rowObj && rowObj.impresiones ? rowObj.impresiones : "";
    const cantidadVal = rowObj && rowObj.cantidad ? rowObj.cantidad : "";
    const codigoVal =
        rowObj && rowObj.codigo_modelo ? rowObj.codigo_modelo : "";
    tr.innerHTML = `
        <td>
            <select name="tipo_modelo[]" class="form-control po-tipo-select" required onchange="generarCodigoFila(this.closest('tr').querySelector('.po-clase-select'))">
                <option value="" disabled ${!tipoVal ? "selected" : ""}>Selecciona uno</option>
                <option value="Suelto" ${tipoVal === "Suelto" ? "selected" : ""}>Suelto</option>
                <option value="Placa" ${tipoVal === "Placa" ? "selected" : ""}>Placa</option>
                <option value="Templadera" ${tipoVal === "Templadera" ? "selected" : ""}>Templadera</option>
            </select>
        </td>
        <td>
            <input type="text" name="impresiones[]" class="form-control po-impresiones" style="text-align:center;" placeholder="Ej. 1" required value="${impresionesVal}">
        </td>
        <td>
            <input type="number" name="cantidad[]" class="form-control" style="text-align:center;" min="1" placeholder="0" required value="${cantidadVal}">
        </td>
        <td>
            <select name="id_clase[]" class="form-control po-clase-select" required onchange="generarCodigoFila(this); actualizarInputImpresiones(this);">
                ${options}
            </select>
        </td>
        <td>
            <input type="text" name="codigo_modelo[]" class="form-control po-codigo-input" value="${codigoVal}">
        </td>
        <td>
            <input type="date" name="fecha_entrega_rows[]" class="form-control po-fecha-entrega-row" readonly value="${fechaEntregaVal}" style="text-align:center; background-color: #f1f5f9; color: #64748b;">
        </td>
        <td style="text-align:center;">
            <button type="button" class="btn-img-action" onclick="${deleteHandler}" title="Quitar esta clase de la lista">
                <img src="${window.baseUrl || ""}${(window.baseUrl || "").endsWith("/") ? "" : "/"}images/quitar.png" alt="Quitar" style="width: 30px;">
            </button>
        </td>
    `;
    const select = tr.querySelector(".po-clase-select");
    if (!rowObj && select.value) {
        const ot = document.getElementById("po-ot").value;
        const nombreClase =
            select.options[select.selectedIndex].dataset.nombre ||
            select.options[select.selectedIndex].text;
        tr.querySelector(".po-codigo-input").value = calculateModelCode(
            ot,
            nombreClase,
        );
    }
    // Bloque 3a: Si la fila ya tiene clase seleccionada (carga de datos existentes),
    // aplicar el bloqueo de impresiones si aplica
    if (select.value) {
        setTimeout(() => window.actualizarInputImpresiones(select), 0);
    }
    return tr;
}
window.agregarFilaPreOrden = function () {
    const newRow = createRowElement();
    document.getElementById("alm-tbody-preorden").appendChild(newRow);
    // Aplicar filtro de clases ya procesadas al nuevo select
    const newSelect = newRow.querySelector("select.po-clase-select");
    if (newSelect) {
        newSelect.innerHTML = buildClaseOptionsForSelect(
            availableClasses,
            null,
            null,
        );
    }
    syncClassOptions("alm-tbody-preorden");
};
window.eliminarFilaPreOrden = function (btn) {
    const row = btn.closest("tr");
    const tbody = document.getElementById("alm-tbody-preorden");
    if (tbody.rows.length > 1) {
        row.remove();
        // No llamamos a syncClassOptions: las opciones ya no se filtran
    } else {
        mostrarToast("Debe haber al menos una clase en la pre-orden", true);
    }
};
// ── Cálculo de códigos ──
/**
 * Función centralizada para calcular el código de modelo
 */
function calculateModelCode(ot, nombreClase, tipoModelo = "") {
    const nameUpper = (nombreClase || "").toUpperCase();
    let sigla = "X";

    const mappings = [
        { keys: ["MOLDE"], val: "M" },
        { keys: ["BOMBILLO"], val: "B" },
        { keys: ["FONDO"], val: "F" },
        { keys: ["CORONA"], val: "C" },
        { keys: ["CABEZA DE SOPLO", "CABEZA SOPLO"], val: "CS" },
        { keys: ["CANDADO OBTURADOR", "CANDADO"], val: "CO" },
        { keys: ["OBTURADOR"], val: "O" },
        { keys: ["GUIA", "GUÍA"], val: "G" },
        { keys: ["PISTON", "PISTÓN"], val: "P" },
        { keys: ["PLATO"], val: "PL" },
        { keys: ["EMBUDO"], val: "E" }
    ];

    for (const mapping of mappings) {
        if (mapping.keys.some(key => nameUpper.includes(key))) {
            sigla = mapping.val;
            break;
        }
    }

    const matches = ot.match(/\d+/);
    const otNum = matches ? matches[0] : ot;
    
    // Regla especial: Templadera → prefijo T en cualquier clase
    if (tipoModelo === "Templadera") {
        return `T${sigla}${otNum}`;
    }
    return `${sigla}${otNum}`;
}
window.generarCodigoFila = function (select) {
    const row = select.closest("tr");
    const inputCodigo = row.querySelector(".po-codigo-input");
    const tipoSelect = row.querySelector(".po-tipo-select");
    const ot = document.getElementById("po-ot").value;
    const tipoModelo = tipoSelect ? tipoSelect.value : "";
    if (!select.value) {
        inputCodigo.value = "";
    } else {
        const nombreClase =
            select.options[select.selectedIndex].dataset.nombre ||
            select.options[select.selectedIndex].text;
        inputCodigo.value = calculateModelCode(ot, nombreClase, tipoModelo);
    }
    // No se llama a syncClassOptions: las opciones no se filtran
};
/**
 * Construye el HTML de opciones para un select específico,
 * asegurando que su valor actual no desaparezca.
 */
function buildClaseOptionsForSelect(classes, currentVal, currentText) {
    const yaUsadas = (window._clasesYaProcesadasEnModelo || []).filter(
        (yp) => yp && yp.trim() !== "",
    );
    return (
        '<option value="">Selecciona clase</option>' +
        classes
            .map((c) => {
                const nombreNorm = c.nombre.toLowerCase();
                const yaUsada = yaUsadas.some(
                    (yp) => nombreNorm.includes(yp) || yp.includes(nombreNorm),
                );
                const isSelected =
                    currentVal &&
                    (currentVal == c.id || currentText === c.nombre);
                // Si la clase ya fue usada PERO es la que está seleccionada actualmente en este select, la mostramos.
                if (yaUsada && !isSelected) return "";
                const selectedAttr = isSelected ? "selected" : "";
                return `<option value="${c.id}" data-nombre="${c.nombre}" ${selectedAttr}>${c.nombre}</option>`;
            })
            .filter(Boolean)
            .join("")
    );
}
function syncClassOptions(tbodyId) {
    // Reaplica las opciones filtradas a todos los selects del tbody
    // para que las clases ya procesadas aparezcan tachadas/deshabilitadas
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const yaUsadas = window._clasesYaProcesadasEnModelo || [];
    if (yaUsadas.length === 0) return; // Sin restricciones: no hacer nada
    Array.from(tbody.querySelectorAll("select.po-clase-select")).forEach(
        (select) => {
            const currentVal = select.value;
            const currentText =
                select.selectedIndex > 0
                    ? select.options[select.selectedIndex].text
                    : "";
            select.innerHTML = buildClaseOptionsForSelect(
                availableClasses,
                currentVal,
                currentText,
            );
            if (currentVal) select.value = currentVal;
        },
    );
}
// ── Modales de confirmación (ELIMINADOS) ──
/**
 * Construye el payload de una forma a partir del tbody y los campos del form.
 */
function buildPayload(tbodyId, formIds) {
    const rows = [];
    const tbody = document.getElementById(tbodyId);
    for (let i = 0; i < tbody.rows.length; i++) {
        const row = tbody.rows[i];
        const classSelect = row.querySelector('[name="id_clase[]"]');
        const tipoSelect = row.querySelector('[name="tipo_modelo[]"]');
        const rawClassName =
            classSelect.options[classSelect.selectedIndex].text;
        const tipoModelo = tipoSelect ? tipoSelect.value : "";
        // Si el tipo es Templadera, usar ese prefijo en la descripción; si no, usar "Modelo"
        const prefijo = tipoModelo === "Templadera" ? "Templadera" : "Modelo";
        const claseNombreFinal = rawClassName.startsWith(prefijo + " ")
            ? rawClassName
            : `${prefijo} ${rawClassName}`;
        rows.push({
            tipo_modelo: tipoModelo,
            impresiones: row.querySelector('[name="impresiones[]"]').value,
            cantidad: row.querySelector('[name="cantidad[]"]').value,
            id_clase: classSelect.value,
            clase_nombre: claseNombreFinal,
            codigo_modelo: row.querySelector('[name="codigo_modelo[]"]').value,
        });
    }
    // Limpiar OT: solo el número (quitar prefijos "OT ", espacios, etc.)
    const otRaw = document.getElementById(formIds.ot).value;
    const otClean = otRaw.replace(/[^0-9]/g, "") || otRaw;
    return {
        proveedor: document.getElementById(formIds.proveedor).value,
        fecha_creacion: document.getElementById(formIds.fecha_creacion).value,
        folio: document.getElementById(formIds.folio).value,
        ot: otClean,
        ot_raw: document.getElementById("po-ot-raw").value,
        moldura: document.getElementById(formIds.moldura).value,
        fecha_entrega: "", // Se deja vacío para llenado manual del proveedor
        observaciones: (() => {
            const userObs = document
                .getElementById(formIds.observaciones)
                .value.trim();
            if (window.predefinedCycleObs) {
                return (
                    window.predefinedCycleObs + (userObs ? "\n" : "") + userObs
                );
            }
            return userObs;
        })(),
        filas: rows,
    };
}
/**
 * Dispara el fetch de guardado de una pre-orden y maneja la respuesta.
 */
function submitPreOrden(payload, btn, originalText, onSuccess) {
    fetch(window.almacenRoutes.storePreOrden, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify(payload),
    })
        .then(async (res) => {
            const contentType = res.headers.get("content-type");
            if (contentType && contentType.includes("application/pdf")) {
                const blob = await res.blob();
                // Intentar extraer el nombre del header Content-Disposition
                const disposition = res.headers.get("Content-Disposition");
                let filename = "";
                if (disposition && disposition.indexOf("attachment") !== -1) {
                    const filenameRegex =
                        /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                    const matches = filenameRegex.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, "");
                    }
                }
                return { blob, filename };
            }
            return res.json();
        })
        .then((data) => {
            if (data.blob) {
                const url = window.URL.createObjectURL(data.blob);
                const a = document.createElement("a");
                a.href = url;
                a.download =
                    data.filename ||
                    `PreOrden_${payload.ot.replace(/[^a-z0-9]/gi, "_")}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                mostrarToast("Pre-orden generada y descargada correctamente.");
                cerrarModalPreOrden();
                // Recargar para actualizar los estados y la lista de archivos
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                if (onSuccess) onSuccess(data);
            } else if (data.success) {
                mostrarToast(data.message + ". Actualizando...");
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                if (onSuccess) onSuccess();
            } else {
                mostrarToast(
                    data.message || "Error al procesar Pre-Orden",
                    true,
                );
            }
        })
        .catch((err) => {
            console.error(err);
            mostrarToast("Error al procesar la solicitud", true);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerText = originalText;
        });
}
// ── Envío Pre-Orden 1 ──
document
    .getElementById("formPreOrden")
    ?.addEventListener("submit", function (e) {
        e.preventDefault();
        const btn = document.getElementById("btn-submit-preorden");
        if (!btn) return;
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "Procesando...";
        const payload = buildPayload("alm-tbody-preorden", {
            proveedor: "po-proveedor",
            fecha_creacion: "po-fecha",
            folio: "po-folio",
            ot: "po-ot",
            moldura: "po-moldura",
            fecha_entrega: "po-fecha-entrega",
            observaciones: "po-observaciones",
        });
        submitPreOrden(payload, btn, originalText, () => {
            cerrarModalPreOrden();
        });
    });
// ── Envío Pre-Orden 2 (ELIMINADO) ──
function updateModelStatusUI(ot, status) {
    const container = document.getElementById(`status-modelo-${ot}`);
    if (!container) return;
    let icon = "Recibido.png";
    let label = "Recibido";
    let tooltip =
        "Alerta inicial recibida, pendiente de procesar modelo por Almacén";
    let borderColor = "#cbd5e1";
    let bgColor = "#f1f5f9";
    let textColor = "#64748b";
    if (status === "pendiente") {
        icon = "Espera.png";
        label = "Tengo Modelo";
        tooltip =
            "Modelo físico disponible en Almacén, en espera de revisión por Calidad";
        borderColor = "#0ea5e9";
        bgColor = "#f0f9ff";
        textColor = "#0369a1";
    } else if (status === "ok") {
        icon = "Quality.png";
        label = "Aprobado";
        tooltip = "Modelo aprobado y liberado por Calidad";
        borderColor = "#10b981";
        bgColor = "#ecfdf5";
        textColor = "#047857";
    }
    const baseUrl = window.baseUrl || window.location.origin + "/";
    const imgUrl =
        baseUrl + (baseUrl.endsWith("/") ? "" : "/") + "images/" + icon;
    container.innerHTML = `
        <div class="status-modelo-container" style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px; padding: 6px; border-radius: 8px;">
            <span class="badge-modelo-icon" title="${tooltip}" style="display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; background: ${bgColor}; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid ${borderColor}; transition: all 0.2s ease;">
                <img src="${imgUrl}" alt="${label}" style="width: 34px; height: 34px; object-fit: contain;">
            </span>
            <span class="status-modelo-label" style="font-size: 11px; font-weight: 700; color: ${textColor}; margin-top: 4px; text-transform: uppercase; white-space: nowrap;">
                ${label}
            </span>
        </div>
    `;
}


// Expose to window for global access
window.updateModelStatusUI = updateModelStatusUI;
window.buildClaseOptionsForSelect = buildClaseOptionsForSelect;
window.createRowElement = createRowElement;
window.syncClassOptions = syncClassOptions;
window.resetMultiOrderState = resetMultiOrderState;
window.normalizeStr = normalizeStr;
window.calculateModelCode = calculateModelCode;
window.submitPreOrden = submitPreOrden;
window.buildPayload = buildPayload;
window.availableClasses = availableClasses;
window.optionsHtmlCache = optionsHtmlCache;
