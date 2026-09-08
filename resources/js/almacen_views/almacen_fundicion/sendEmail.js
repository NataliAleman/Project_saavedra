// ── ENVÍO DE CORREO DE PRE-ORDEN ──

let adicionalesSelectedFiles = [];
window.adicionalesSelectedFiles = adicionalesSelectedFiles;

window.removeAdicionalAttachment = function (index) {
    adicionalesSelectedFiles.splice(index, 1);
    renderSelectedFilesBadges();
};

function renderSelectedFilesBadges() {
    window.renderFileCards(
        "env-archivos-adicionales-list",
        adicionalesSelectedFiles,
        "window.removeAdicionalAttachment",
        "#0369a1"
    );
}

window.renderSelectedFilesBadges = renderSelectedFilesBadges;

window.abrirModalEnviarPreOrden = function (ot, tipo, clasesFaltantes = null) {
    const modal = document.getElementById("modalEnviarPreOrden");
    const inputOt = document.getElementById("env-ot");
    const filesContainer = document.getElementById("env-server-files-container");
    inputOt.value = ot;
    
    const inputTipo = document.getElementById("env-tipo");
    if (inputTipo) {
        inputTipo.value = tipo || "modelo";
    }
    const subtitle = document.getElementById("env-po-modal-subtitle");
    if (subtitle) {
        subtitle.textContent = `OT: ${ot.replace(/_\d{8}_\d{6}_.*/, "")}`;
    }
    const inputDestinatario = document.getElementById("env-destinatario");
    const inputDestinatarioCalidad = document.getElementById("env-destinatario-calidad");
    const divDestinatarioCalidad = document.getElementById("div-env-destinatario-calidad");
    const form = document.getElementById("formEnviarPreOrden");
    if (inputDestinatario && form) {
        inputDestinatario.value = tipo === "casting"
            ? form.getAttribute("data-email-casting")
            : form.getAttribute("data-email-modelo");
    }
    if (inputDestinatarioCalidad && divDestinatarioCalidad && form) {
        if (tipo === "casting") {
            divDestinatarioCalidad.classList.add("alm-display-none");
        } else {
            divDestinatarioCalidad.classList.remove("alm-display-none");
            inputDestinatarioCalidad.value = form.getAttribute("data-email-calidad");
        }
    }
    
    adicionalesSelectedFiles = [];
    window.adicionalesSelectedFiles = adicionalesSelectedFiles;
    renderSelectedFilesBadges();
    
    filesContainer.innerHTML = `
        <div style="text-align: center; padding: 10px;">
            <div class="alm-spinner" style="border-top-color: #033966; display: inline-block;"></div>
            <span style="color: #64748b; margin-left: 10px;">Obteniendo archivos del servidor...</span>
        </div>
    `;
    modal.classList.add("open");
    document.body.classList.add("modal-open");
    
    fetch(`${window.almacenRoutes.archivos}?ot=${encodeURIComponent(ot)}&tipo=${encodeURIComponent(tipo || "modelo")}`)
        .then((res) => res.json())
        .then((data) => {
            const inputFecha = document.getElementById("env-fecha-entrega");
            if (inputFecha) {
                inputFecha.value = data.fecha_entrega || "";
            }
            if (data.existe && data.archivos && data.archivos.length > 0) {
                let baseUrl = window.baseUrl || window.location.origin + "/";
                if (!baseUrl.endsWith("/")) baseUrl += "/";
                let archivosAMostrar = data.archivos;
                if (tipo === "casting") {
                    archivosAMostrar = data.archivos.filter((f) => {
                        const n = (f.nombre || "").toLowerCase();
                        return (
                            n.includes("f_alm_pfc") ||
                            n.includes("pfc") ||
                            (n.includes("pre-orden") && n.includes("casting")) ||
                            n.includes("pre-orden_casting")
                        );
                    });
                } else {
                    if (clasesFaltantes && Array.isArray(clasesFaltantes) && clasesFaltantes.length > 0) {
                        archivosAMostrar = archivosAMostrar.filter((f) => {
                            const n = (f.nombre || "").toLowerCase();
                            const esAyudaOPreOrden = f.tipo === "ayuda" || n.includes("ayudas_visuales") || n.includes("pre-orden") || n.includes("preorden") || n.includes("f_ccl_ldm") || n.includes("confirmacionmodelo");
                            return esAyudaOPreOrden || clasesFaltantes.some((clase) => window.compararClasesSurgico(n, clase));
                        });
                    }
                }
                const sectionsHtml = window.generarHtmlCategorizadoArchivos(
                    archivosAMostrar,
                    ot,
                    baseUrl,
                    "preorden"
                );
                filesContainer.innerHTML = sectionsHtml || `<div style="text-align: center; color: #64748b; padding: 15px; font-style: italic;">No se encontraron archivos en el servidor para esta OT.</div>`;
                
                if ((clasesFaltantes && Array.isArray(clasesFaltantes) && clasesFaltantes.length > 0) || tipo === "casting") {
                    const fileCards = filesContainer.querySelectorAll(".select-file-card");
                    fileCards.forEach((card) => {
                        const fileInput = card.querySelector('input[type="checkbox"]');
                        if (!fileInput) return;
                        const fileName = fileInput.value.toLowerCase();
                        let shouldCheck = false;
                        if (tipo === "casting") {
                            if (
                                fileName.includes("f_alm_pfc") ||
                                fileName.includes("pfc") ||
                                (fileName.includes("pre-orden") && fileName.includes("casting")) ||
                                fileName.includes("pre-orden_casting")
                            ) {
                                shouldCheck = true;
                            }
                        } else {
                            if (fileName.includes("pre-orden") || fileName.includes("preorden")) {
                                shouldCheck = true;
                            } else if (clasesFaltantes && Array.isArray(clasesFaltantes)) {
                                clasesFaltantes.forEach((clase) => {
                                    if (window.compararClasesSurgico(fileName, clase)) shouldCheck = true;
                                });
                            }
                        }
                        if (shouldCheck) {
                            fileInput.checked = true;
                            card.classList.add("checked-card");
                        }
                    });
                }
                setTimeout(() => {
                    if (window.syncArchivosSeleccionadosPreOrden)
                        window.syncArchivosSeleccionadosPreOrden();
                }, 50);
            } else {
                filesContainer.innerHTML = `<div style="text-align: center; color: #64748b; padding: 15px; font-style: italic;">No se encontraron archivos en el servidor para esta OT.</div>`;
            }
        })
        .catch((err) => {
            console.error(err);
            filesContainer.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 15px; font-weight: 600;">Error al cargar la lista de archivos.</div>`;
        });
        
    const pendingContainer = document.getElementById("env-pending-preordenes-container");
    if (pendingContainer) {
        pendingContainer.innerHTML = `
            <div style="text-align: center; padding: 10px;">
                <div class="alm-spinner" style="border-top-color: #033966; display: inline-block;"></div>
                <span style="color: #64748b; margin-left: 10px;">Obteniendo pre-órdenes pendientes...</span>
            </div>
        `;
        fetch(`${window.almacenRoutes.pendingPreOrdenes}?ot=${encodeURIComponent(ot)}&tipo=${encodeURIComponent(tipo || "modelo")}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.success && data.pending && data.pending.length > 0) {
                    let html = "";
                    let hasJacarandas = false;
                    data.pending.forEach((po) => {
                        if (po.proveedor && po.proveedor.toLowerCase().includes("jacarandas")) {
                            hasJacarandas = true;
                        }
                        html += `
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; background: #fff; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px;">
                                <input type="checkbox" name="pre_orden_ids[]" value="${po.id}" checked data-clases="${po.clases_str || ""}" onchange="if(window.syncArchivosSeleccionadosPreOrden) window.syncArchivosSeleccionadosPreOrden()">
                                <div>
                                    <strong style="color: #0f172a;">${po.clases_str || "Sin clases"}</strong>
                                    <div style="font-size: 0.8em; color: #64748b;">PDF: ${po.pdf_filename} | Creada: ${po.fecha_creacion}</div>
                                </div>
                            </label>
                        `;
                    });
                    pendingContainer.innerHTML = html;
                    const form = document.getElementById("formEnviarPreOrden");
                    const inputDestinatario = document.getElementById("env-destinatario");
                    if (hasJacarandas && form && inputDestinatario) {
                        const jEmail = form.getAttribute("data-email-jacarandas");
                        if (jEmail) {
                            inputDestinatario.value = jEmail;
                        }
                    }
                    setTimeout(() => {
                        if (window.syncArchivosSeleccionadosPreOrden)
                            window.syncArchivosSeleccionadosPreOrden();
                    }, 50);
                } else {
                    pendingContainer.innerHTML = `<div style="text-align: center; color: #64748b; padding: 15px; font-style: italic;">No hay pre-órdenes pendientes de enviar.</div>`;
                }
            })
            .catch((err) => {
                console.error(err);
                pendingContainer.innerHTML = `<div style="text-align: center; color: #ef4444; padding: 15px; font-weight: 600;">Error al cargar las pre-órdenes pendientes.</div>`;
            });
    }
};

window.cerrarModalEnviarPreOrden = function () {
    const modal = document.getElementById("modalEnviarPreOrden");
    modal.classList.remove("open");
    document.body.classList.remove("modal-open");
    document.getElementById("formEnviarPreOrden").reset();
    adicionalesSelectedFiles = [];
    window.adicionalesSelectedFiles = adicionalesSelectedFiles;
    renderSelectedFilesBadges();
};

window.syncArchivosSeleccionadosPreOrden = function () {
    const pendingContainer = document.getElementById("env-pending-preordenes-container");
    const filesContainer = document.getElementById("env-server-files-container");
    if (!pendingContainer || !filesContainer) return;
    if (pendingContainer.querySelector(".alm-spinner") || filesContainer.querySelector(".alm-spinner")) return;
    
    const preOrdenesChecked = Array.from(pendingContainer.querySelectorAll('input[name="pre_orden_ids[]"]:checked'));
    let clasesActivas = [];
    preOrdenesChecked.forEach((chk) => {
        const clasesStr = chk.getAttribute("data-clases") || "";
        if (clasesStr) {
            clasesStr.split(",").forEach((c) => {
                let claseLimpia = c.trim().toLowerCase();
                claseLimpia = claseLimpia.replace(/^modelo\s+/i, "").replace(/^casting\s+/i, "");
                if (claseLimpia && !clasesActivas.includes(claseLimpia)) {
                    clasesActivas.push(claseLimpia);
                }
            });
        }
    });
    
    const archivoChecks = filesContainer.querySelectorAll('input[name="archivos_seleccionados[]"]');
    archivoChecks.forEach((chk) => {
        const val = chk.value.toLowerCase();
        let shouldBeChecked = true;
        const isCasting = document.getElementById("env-tipo") && document.getElementById("env-tipo").value === "casting";
        if (preOrdenesChecked.length > 0) {
            if (isCasting) {
                shouldBeChecked =
                    val.includes("f_alm_pfc") ||
                    val.includes("pfc") ||
                    (val.includes("pre-orden") && val.includes("casting")) ||
                    val.includes("pre-orden_casting");
            } else {
                shouldBeChecked = clasesActivas.some((c) => val.includes(c));
            }
        } else {
            shouldBeChecked = false;
        }
        if (chk.checked !== shouldBeChecked) {
            chk.checked = shouldBeChecked;
            const card = chk.closest(".select-file-card");
            if (card) {
                if (shouldBeChecked) card.classList.add("checked-card");
                else card.classList.remove("checked-card");
            }
        }
    });
};

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formEnviarPreOrden");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const fecha = document.getElementById("env-fecha-entrega").value;
            if (!fecha) {
                mostrarToast("Por favor, indica la fecha de entrega acordada.", true);
                return;
            }
            const btn = document.getElementById("btn-submit-envio");
            if (!btn) return;
            const originalText = btn.innerText;
            btn.disabled = true;
            btn.innerText = "Enviando correo...";
            const formData = new FormData(this);
            formData.delete("archivos_adicionales[]");
            adicionalesSelectedFiles.forEach((file) => {
                formData.append("archivos_adicionales[]", file);
            });
            
            fetch(window.almacenRoutes.sendEmailPreOrden, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                },
                body: formData,
            })
                .then((res) => res.json())
                .then((data) => {
                    if (data.success) {
                        mostrarToast(data.message);
                        window.cerrarModalEnviarPreOrden();
                        const ot = document.getElementById("env-ot").value;
                        if (window.ModeloStateMachine) {
                            if (formData.get("tipo") === "casting") {
                                window.ModeloStateMachine._forzarTerminal(ot, "casting_aprobado");
                            } else {
                                window.ModeloStateMachine.onCorreoEnviado(ot);
                            }
                        }
                        if (typeof window.bloquearModalPreOrden === 'function') {
                            window.bloquearModalPreOrden();
                        }
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        mostrarToast(data.message || "Error al enviar el correo.", true);
                    }
                })
                .catch((err) => {
                    console.error(err);
                    mostrarToast("Error de conexión al enviar el correo.", true);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerText = originalText;
                });
        });
    }
    
    // Custom file input listener
    const input = document.getElementById("env-archivos-adicionales");
    if (input) {
        input.addEventListener("change", function () {
            if (this.files && this.files.length > 0) {
                Array.from(this.files).forEach((file) => {
                    const alreadyExists = adicionalesSelectedFiles.some(
                        (f) => f.name === file.name && f.size === file.size
                    );
                    if (!alreadyExists) {
                        adicionalesSelectedFiles.push(file);
                    }
                });
            }
            renderSelectedFilesBadges();
            this.value = "";
        });
    }
});
