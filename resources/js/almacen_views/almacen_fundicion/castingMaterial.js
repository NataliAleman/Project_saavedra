// ── CASTING MATERIAL & APPROVED STAGE LOGIC ──

window.generarHtmlCategorizadoCastingAprobados = function (
    archivos,
    otClean,
    isRechazados = false,
) {
    const baseUrl = window.baseUrl || window.location.origin + "/";
    const secciones = [
        {
            label: "Ayudas Visuales",
            color: "#d97706",
            tipos: ["ayuda"],
            claseCard: "card-ayuda",
        },
        {
            label: "Dibujos de Fundición",
            color: "#0284c7",
            tipos: ["dibujo", "plano"],
            claseCard: "card-plano",
        },
        {
            label: isRechazados
                ? "Documentos Rechazados"
                : "Documentos Aprobados",
            color: isRechazados ? "#ef4444" : "#059669",
            tipos: ["aprobado", "preorden", "otro"],
            claseCard: "card-ayuda",
        },
    ];
    let html = "";
    secciones.forEach((sec) => {
        const archivosSeccion = archivos.filter((f) => {
            const tipo = (f.tipo || "").toLowerCase();
            const nombre = (f.nombre || "").toLowerCase();
            if (sec.tipos.includes("aprobado")) {
                const targetFolder = isRechazados
                    ? "documentos_rechazados"
                    : "documentos_aprobados";
                return (
                    tipo === "aprobado" ||
                    tipo === "preorden" ||
                    tipo === "otro" ||
                    nombre.includes(targetFolder) ||
                    nombre.includes("preorden") ||
                    nombre.includes("pre-orden") ||
                    nombre.includes("fdldm") ||
                    nombre.includes("fdrdm") ||
                    nombre.includes("f_ccl_ldm") ||
                    nombre.includes("f-ccl-ldm") ||
                    nombre.includes("f_ccl_rdm") ||
                    nombre.includes("f-ccl-rdm") ||
                    nombre.includes("f_ccl_scar") ||
                    nombre.includes("f-ccl-scar") ||
                    nombre.includes("scar") ||
                    nombre.includes("pfc") ||
                    nombre.includes("pfm") ||
                    nombre.includes("cfm") ||
                    nombre.includes("efm") ||
                    nombre.includes("efc") ||
                    nombre.includes("escaneado")
                );
            }
            if (sec.tipos.includes("ayuda")) {
                return tipo === "ayuda" || nombre.includes("ayuda_visual") || nombre.includes("ayudas_visuales") || nombre.includes("ayudas visuales");
            }
            return sec.tipos.includes(tipo);
        });
        if (archivosSeccion.length === 0) {
            html += `<div style="width:100%;">
                <h4 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;font-size:1.05em;margin-top:10px;margin-bottom:12px;border-left:4px solid ${sec.color};padding-left:8px;">${sec.label}</h4>
                <div style="text-align:center; color:#94a3b8; padding:8px; font-style:italic; font-size:0.85em; background:#fff; border: 1px dashed #cbd5e1; border-radius: 8px;">
                    No hay archivos en esta categoría.
                </div>
            </div>`;
            return;
        }
        html += `<div style="width:100%;">
            <h4 style="font-family:'Poppins',sans-serif;font-weight:700;color:#1e293b;font-size:1.05em;margin-top:10px;margin-bottom:12px;border-left:4px solid ${sec.color};padding-left:8px;">${sec.label}</h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:12px;width:100%;box-sizing:border-box;">`;
        archivosSeccion.forEach((f) => {
            const nombre = f.nombre || "";
            const baseName = nombre.split("/").pop();
            const ext = baseName.split(".").pop().toLowerCase();
            const isImg = ["png", "jpg", "jpeg", "gif", "webp"].includes(ext);
            const isDwg = ext === "dwg";
            const iconDefault = isDwg
                ? `${getBaseUrl()}images/dwg-shadow.png`
                : (isImg
                ? `${getBaseUrl()}images/galeria-shadow.png`
                : `${getBaseUrl()}images/pdf-view-shadow.png`);
            const iconHover = isDwg
                ? `${getBaseUrl()}images/dwg.png`
                : (isImg
                ? `${getBaseUrl()}images/galeria.png`
                : `${getBaseUrl()}images/pdf-view.png`);
            const titleAttr = isDwg ? "Descargar DWG" : "Ver archivo";
            const btnText = isDwg ? "Descargar" : "Ver";
            const safeName = nombre.replace(/'/g, "\\'");
            const safeOt = otClean.replace(/'/g, "\\'");
            const tipoParam = isRechazados ? "rechazados" : "aprobados";
            const safeTipo = tipoParam.replace(/'/g, "\\'");
            const fnViewer = typeof window.almacenVerPdf === 'function' ? 'almacenVerPdf' : 'calidadVerPdf';
            html += `<div class="dibujos-file-card ${sec.claseCard}" style="border-left-color: ${sec.color};">
                <div class="file-icon-wrapper alm-cursor-pointer" onclick="${fnViewer}('${safeOt}','${safeName}','${safeTipo}')" title="${titleAttr}">
                    <img src="${iconDefault}" class="file-icon icon-default">
                    <img src="${iconHover}" class="file-icon icon-hover">
                </div>
                <div class="file-name alm-cursor-pointer" onclick="${fnViewer}('${safeOt}','${safeName}','${safeTipo}')" title="${titleAttr}">
                    ${baseName}
                </div>
                <div class="file-actions alm-flex-gap-5">
                    <button type="button" class="btn-dibujos btn-dibujos-sm btn-ver" style="background-color: ${sec.color}; color: white; border-color: ${sec.color};" onclick="${fnViewer}('${safeOt}','${safeName}','${safeTipo}')">${btnText}</button>
                </div>
            </div>`;
        });
        html += `</div></div>`;
    });
    return html;
};

window.cargarInputsCasting = function (ot, files) {
    const dynamicInputs = document.getElementById("mgv-aprobados-inputs");
    if (!dynamicInputs) return;
    dynamicInputs.innerHTML = "";
    const otClean = ot.replace(/_\d{8}_\d{6}_.*/, "");
    let allLoaded = true;
    if (window.micRequiredClasses && window.micRequiredClasses.length > 0) {
        window.micRequiredClasses.forEach((c) => {
            const group = document.createElement("div");
            group.className = "custom-class-upload";
            group.style.marginBottom = "12px";
            let existingFile = null;
            if (files) {
                let sanitizedOt = ot.replace(/[^\w\s\-]/g, "");
                sanitizedOt = sanitizedOt
                    .trim()
                    .replace(/[\s]+/g, "_")
                    .toUpperCase();
                existingFile = files.find((f) => {
                    const nameUpper = (f.nombre || "").toUpperCase();
                    const cleanClass = c.toUpperCase();
                    const isLdmDoc = nameUpper.includes("FDLDM") || nameUpper.includes("F_CCL_LDM") || nameUpper.includes("F-CCL-LDM") || nameUpper.includes("LDM");
                    const isUserUploaded = nameUpper.includes("/FDLDM/") && !nameUpper.includes("_APROBADO.PDF") && !nameUpper.includes("_RECHAZADO.PDF");
                    return isLdmDoc && nameUpper.includes(cleanClass) && isUserUploaded;
                });
            }
            const label = c.charAt(0).toUpperCase() + c.slice(1);
            if (!existingFile) {
                allLoaded = false;
            }
            const cleanName = existingFile ? existingFile.nombre.split("/").pop() : "";
            const isLocked = !!existingFile;
            group.innerHTML = `
                <div class="ldm-upload-group" style="margin-bottom: 15px; width: 100%;">
                    <label style="font-weight:700;color:#334155;margin-bottom:8px;display:block;font-family:'Poppins',sans-serif;font-size:0.95em;">
                        Formato F-CCL-LDM — ${label} <span style="color:#ef4444;">*</span>
                        ${existingFile ? `<span style="background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 8px;font-size:0.82em;margin-left:4px;font-weight:600;">Cargado</span>` : ''}
                    </label>
                    <div class="custom-file-upload-btn-wrapper" style="margin-bottom: 8px; display:flex; justify-content:center;">
                        <div class="custom-file-dropzone btn-modelo btn-modelo-si" style="position:relative; ${isLocked ? 'pointer-events:none; opacity:0.6; filter:grayscale(0.3);' : ''}">
                            <input type="file" name="ldm_${c}" data-type="ldm" data-clase="${c}" accept=".pdf" ${isLocked ? 'disabled' : 'required'} style="position:absolute;top:0;left:0;width:100%;height:100%;opacity:0;cursor:${isLocked ? 'not-allowed' : 'pointer'};" onchange="window._onLdmFileSelected(this, '${ot}')">
                            <img src="${getBaseUrl()}images/upload_icon.png" alt="Subir LDM">
                            <span>${isLocked ? 'Formato Cargado' : 'Subir Formato'}</span>
                        </div>
                    </div>
                    <div class="file-card-preview-container" style="width: 100%;">
                        ${existingFile ? `
                            <div class="dibujos-file-card card-otro" style="display: flex; align-items: center; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); background: #fff; padding: 8px 10px; border: 1.5px solid #d1fae5; border-left: 4px solid #15803d; width: 100%; box-sizing: border-box; margin-top: 6px; gap: 8px;">
                                <div class="file-icon-wrapper alm-cursor-pointer" title="Abrir PDF" onclick="almacenVerPdf('${ot}','${existingFile.nombre}','aprobado')" style="position:relative;width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;cursor:pointer;">
                                    <img src="${getBaseUrl()}images/pdf-view-shadow.png" class="file-icon icon-default" style="width:32px;height:32px;object-fit:contain;">
                                    <img src="${getBaseUrl()}images/pdf-view.png" class="file-icon icon-hover" style="width:32px;height:32px;object-fit:contain;position:absolute;top:0;left:0;opacity:0;">
                                </div>
                                <div class="file-name alm-cursor-pointer" title="Abrir PDF" onclick="almacenVerPdf('${ot}','${existingFile.nombre}','aprobado')" style="cursor: pointer; font-size: 0.8em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; color: #334155; flex: 1; text-align: left;">
                                    ${cleanName}
                                </div>
                                <div class="file-actions" style="display:flex;gap:5px;flex-shrink:0;">
                                    <button type="button" class="btn-dibujos btn-dibujos-sm btn-ver alm-background-color-155724 alm-color-white" onclick="almacenVerPdf('${ot}','${existingFile.nombre}','aprobado')">Ver</button>
                                    <button type="button" class="btn-dibujos btn-dibujos-sm" style="background:#ef4444;border-color:#ef4444;color:white;" onclick="quitarArchivoAprobado('${ot}','${existingFile.nombre}',this)">Eliminar</button>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                </div>`;
            dynamicInputs.appendChild(group);

        });
    } else {
        dynamicInputs.innerHTML =
            "<p style=\"color:#10b981;font-weight:600;font-family:'Poppins',sans-serif;padding:12px;background:#f0fdf4;border-radius:8px;\">No se requieren formatos LDM adicionales.</p>";
    }

    const btnSubmit = document.getElementById("btn-submit-aprobados");
    const btnIr = document.getElementById("btn-ir-preorden-casting");
    if (btnSubmit) {
        const textSpan = btnSubmit.querySelector("span");
        if (allLoaded) {
            btnSubmit.classList.remove("alm-display-none");
            if (textSpan) textSpan.innerText = "Procesar Aceptados";
        } else {
            btnSubmit.classList.remove("alm-display-none");
            if (textSpan) textSpan.innerText = "Subir Formatos y Continuar";
        }
    }
    if (btnIr) {
        btnIr.classList.toggle("alm-display-none", !allLoaded);
    }
};

window.quitarArchivoAprobado = function (ot, archivo, buttonEl) {
    if (
        !confirm(
            "¿Estás seguro de que deseas eliminar permanentemente este formato LDM? Esta acción no se puede deshacer.",
        )
    ) {
        return;
    }
    if (buttonEl) buttonEl.disabled = true;
    fetch(window.almacenRoutes.deleteFile, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
            Accept: "application/json",
        },
        body: JSON.stringify({
            ot: ot,
            archivo: archivo,
            tipo: "aprobado",
        }),
    })
        .then((res) => res.json())
        .then((data) => {
            if (data.success) {
                almacenToast(
                    data.message || "Formato LDM eliminado correctamente.",
                    "success",
                );
                fetch(
                    `${window.almacenRoutes.archivos}?ot=${encodeURIComponent(ot)}`,
                )
                    .then((res) => res.json())
                    .then((data) => {
                        window.cargarInputsCasting(ot, data.archivos);
                        const filesContainer = document.getElementById(
                            "mgv-aprobados-files",
                        );
                        if (filesContainer) {
                            const otClean = ot.replace(/_\d{8}_\d{6}_.*/, "");
                            let baseUrl =
                                window.baseUrl || window.location.origin + "/";
                            if (!baseUrl.endsWith("/")) baseUrl += "/";
                            const sectionsHtml =
                                window.generarHtmlCategorizadoArchivos(
                                    data.archivos,
                                    otClean,
                                    baseUrl,
                                    "preorden",
                                );
                            filesContainer.innerHTML =
                                sectionsHtml ||
                                `
                            <div style="text-align: center; color: #64748b; padding: 15px; font-style: italic;">
                                No se encontraron archivos en el servidor para esta OT.
                            </div>
                        `;
                        }
                        const downloadBtn = document.getElementById(
                            "btn-download-casting-po",
                        );
                        if (downloadBtn) {
                            if (data.casting_pdf_generated) {
                                downloadBtn.classList.remove(
                                    "alm-display-none",
                                );
                            } else {
                                downloadBtn.classList.add("alm-display-none");
                            }
                        }
                    });
            } else {
                almacenToast(
                    data.error || "Error al eliminar archivo.",
                    "error",
                );
                if (buttonEl) buttonEl.disabled = false;
            }
        })
        .catch((err) => {
            console.error(err);
            almacenToast("Error de red al eliminar archivo.", "error");
            if (buttonEl) buttonEl.disabled = false;
        });
};

document.addEventListener("DOMContentLoaded", () => {
    document
        .getElementById("formMgvAprobados")
        ?.addEventListener("submit", async function (e) {
            e.preventDefault();
            let allValid = true;
            const fileInputs = this.querySelectorAll('input[type="file"]');
            fileInputs.forEach((input) => {
                if (
                    input.hasAttribute("required") &&
                    (!input.files || input.files.length === 0)
                ) {
                    allValid = false;
                }
            });
            if (!allValid) {
                almacenToast(
                    "Por favor, selecciona los formatos LDM requeridos.",
                    "error",
                );
                return;
            }
            const formData = new FormData(this);
            const submitBtn = document.getElementById("btn-submit-aprobados");
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.querySelector("span").innerText = "Procesando...";
            }
            try {
                const response = await fetch(window.almacenRoutes.iniciarCasting, {
                    method: "POST",
                    body: formData,
                    headers: {
                        "X-CSRF-TOKEN":
                            document
                                .querySelector('meta[name="csrf-token"]')
                                ?.getAttribute("content") || "",
                    },
                });
                const data = await response.json();
                if (data.success) {
                    almacenToast(data.message, "success");
                    const otRaw = document.getElementById("mgv-ot").value;
                    sessionStorage.setItem("openCastingOt", otRaw);
                    setTimeout(() => {
                        window.cerrarModalGestionVeredicto();
                        location.reload();
                    }, 1500);
                } else {
                    almacenToast(
                        data.message || "Error al procesar casting.",
                        "error",
                    );
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.querySelector("span").innerText =
                            "Procesar Aceptados";
                    }
                }
            } catch (err) {
                console.error(err);
                almacenToast("Error de red.", "error");
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.querySelector("span").innerText =
                        "Procesar Aceptados";
                }
            }
        });
});

// ── Handler: bloquear btn Subir Formato al seleccionar archivo (aprobados) ──
window._onLdmFileSelected = function (input, ot) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const dropzone = input.closest(".custom-file-dropzone");
    const previewContainer = dropzone?.closest(".ldm-upload-group")?.querySelector(".file-card-preview-container");

    // Ocultar el botón de subida
    if (dropzone) {
        dropzone.style.display = 'none';
    }

    // Crear URL temporal
    const objectUrl = URL.createObjectURL(file);
    if (input._objectUrl) {
        URL.revokeObjectURL(input._objectUrl);
    }
    input._objectUrl = objectUrl;

    // Asignar un ID al input si no lo tiene
    if (!input.id) input.id = 'ldm-input-' + Math.random().toString(36).substr(2, 9);

    // Mostrar mini-card preview con el nombre del archivo
    if (previewContainer) {
        let base = window.baseUrl || window.location.origin + "/";
        if (!base.endsWith("/")) base += "/";

        previewContainer.style.display = 'flex';
        previewContainer.style.justifyContent = 'center';
        previewContainer.style.width = '100%';

        previewContainer.innerHTML = `
            <div class="dibujos-file-card card-otro" style="border-left-color: #15803d; width: 100%; max-width: 280px; margin-top: 6px;">
                <div class="file-icon-wrapper alm-cursor-pointer" onclick="window.open('${objectUrl}', '_blank')" title="Ver archivo">
                    <img src="${base}images/pdf-view-shadow.png" class="file-icon icon-default">
                    <img src="${base}images/pdf-view.png" class="file-icon icon-hover">
                </div>
                <div class="file-name alm-cursor-pointer" onclick="window.open('${objectUrl}', '_blank')" title="Ver archivo">
                    ${file.name}
                    <div style="font-size:0.82em;background:#dcfce7;color:#15803d;border-radius:20px;padding:2px 8px;font-weight:700;display:inline-block;margin-top:4px;">Por subir</div>
                </div>
                <div class="file-actions alm-flex-gap-5">
                    <button type="button" class="btn-dibujos btn-dibujos-sm btn-ver" style="background-color: #15803d; color: white; border-color: #15803d;" onclick="window.open('${objectUrl}', '_blank')">Ver</button>
                    <button type="button" class="btn-dibujos btn-dibujos-sm btn-eliminar" style="background-color: #dc2626; color: white; border-color: #dc2626;" onclick="window._quitarLdmTemporal('${input.id}')">Quitar</button>
                </div>
            </div>`;
    }
};

window._quitarLdmTemporal = function (inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        input.value = "";
        if (input._objectUrl) {
            URL.revokeObjectURL(input._objectUrl);
            input._objectUrl = null;
        }
        const dropzone = input.closest(".custom-file-dropzone");
        if (dropzone) {
            dropzone.style.display = ''; // Restaurar visibilidad
        }
        const previewContainer = dropzone?.closest(".ldm-upload-group")?.querySelector(".file-card-preview-container");
        if (previewContainer) {
            previewContainer.innerHTML = "";
            previewContainer.style.display = '';
        }
    }
};
