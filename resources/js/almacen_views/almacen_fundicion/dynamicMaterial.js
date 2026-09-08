// ── VERDICT MODAL ORCHESTRATION & ALERTS ──

window.switchMgvTab = function (tabName) {
    document.querySelectorAll(".mgv-view").forEach((v) => {
        v.style.display = "none";
        v.classList.add("alm-display-none");
    });
    document
        .querySelectorAll(".mgv-tab")
        .forEach((t) => t.classList.remove("active"));
    
    const targetView = document.getElementById("mgv-view-" + tabName);
    if (targetView) {
        targetView.style.display = "flex";
        targetView.classList.remove("alm-display-none");
    }
    const activeTab = document.getElementById("tab-" + tabName);
    if (activeTab) activeTab.classList.add("active");
    
    const header = document.getElementById("mgv-header");
    if (header) {
        if (tabName === "aprobados") {
            header.style.background = "linear-gradient(135deg, #16a34a, #15803d)";
        } else {
            header.style.background = "linear-gradient(135deg, #dc2626, #b91c1c)";
        }
    }
};

window.abrirModalGestionVeredicto = function (ot, aprobados, rechazados) {
    const modal = document.getElementById("modalGestionVeredicto");
    if (!modal) return;
    document.getElementById("mgv-ot").value = ot;
    document.querySelectorAll(".mgv-form-ot").forEach((i) => (i.value = ot));
    const otClean = ot.replace(/_\d{8}_\d{6}_.*/, "");
    document.getElementById("mgv-subtitle").textContent = `OT: ${otClean}`;
    
    const today = new Date();
    const formattedToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
    document.getElementById("mgv-fecha").value = formattedToday;
    document
        .querySelectorAll(".mgv-form-fecha")
        .forEach((i) => (i.value = formattedToday));
    
    const requiredClasses = [
        "candado obturador",
        "cabeza de soplo",
        "obturador",
        "bombillo",
        "embudo",
        "corona",
        "plato",
        "molde",
        "fondo",
    ];
    const filteredAprobados = (aprobados || []).filter((c) =>
        requiredClasses.includes(c.toLowerCase()),
    );
    const filteredRechazados = (rechazados || []).filter((c) =>
        requiredClasses.includes(c.toLowerCase()),
    );
    const hiddenClasesRech = document.getElementById("mgv-clases-rechazadas");
    if (hiddenClasesRech) {
        hiddenClasesRech.value = JSON.stringify(filteredRechazados);
    }
    window.micRequiredClasses = filteredAprobados.map((c) => c.toLowerCase());
    window.cargarInputsCasting(ot, []);
    
    const dynamicRechInputs = document.getElementById("mgv-rechazados-inputs");
    if (dynamicRechInputs) {
        dynamicRechInputs.innerHTML =
            '<div style="text-align:center;padding:15px;color:#64748b;">Cargando formatos requeridos...</div>';
    }
    const hasAprobados = filteredAprobados.length > 0;
    const hasRechazados = filteredRechazados.length > 0;
    const tabAprobados = document.getElementById("tab-aprobados");
    const tabRechazados = document.getElementById("tab-rechazados");
    if (tabAprobados)
        tabAprobados.classList.toggle("alm-display-none", !hasAprobados);
    if (tabRechazados)
        tabRechazados.classList.toggle("alm-display-none", !hasRechazados);
    
    const filesContainerA = document.getElementById("mgv-aprobados-files");
    const filesContainerR = document.getElementById("mgv-rechazados-files");
    if (filesContainerA)
        filesContainerA.innerHTML = `<div style="text-align:center;padding:14px;"><div class="alm-spinner" style="border-top-color:#16a34a;display:inline-block;"></div><span style="color:#64748b;margin-left:10px;font-family:'Poppins',sans-serif;">Cargando archivos...</span></div>`;
    if (filesContainerR)
        filesContainerR.innerHTML = `<div style="text-align:center;padding:14px;"><div class="alm-spinner" style="border-top-color:#dc2626;display:inline-block;"></div><span style="color:#64748b;margin-left:10px;font-family:'Poppins',sans-serif;">Cargando archivos...</span></div>`;
    
    modal.classList.add("open");
    document.body.classList.add("modal-open");
    
    if (hasAprobados) window.switchMgvTab("aprobados");
    else if (hasRechazados) window.switchMgvTab("rechazados");
    
    fetch(
        `${window.almacenRoutes.archivos}?ot=${encodeURIComponent(ot)}&todo=1`,
    )
        .then((res) => res.json())
        .then((data) => {
            window.cargarInputsCasting(ot, data.archivos);
            window.cargarInputsRechazados(
                ot,
                data.archivos,
                filteredRechazados,
            );
            const btnIr = document.getElementById("btn-ir-preorden-casting");
            if (btnIr) {
                const hasPreordenCasting = (data.archivos || []).some((f) => {
                    const n = (f.nombre || "").toLowerCase();
                    return (
                        n.includes("pre-orden") &&
                        n.includes("fundicion") &&
                        !n.includes("modelo")
                    );
                });
                if (hasPreordenCasting && hasRechazados) {
                    if (tabAprobados)
                        tabAprobados.classList.add("alm-display-none");
                    window.switchMgvTab("rechazados");
                }
            }
            if (data.existe && data.archivos && data.archivos.length > 0) {
                const filtrarPorClasesActivas = (
                    archivosList,
                    clasesActivas,
                ) => {
                    if (!clasesActivas || clasesActivas.length === 0) return archivosList;
                    const clasesMonitoreadas = [
                        "candado obturador",
                        "cabeza de soplo",
                        "obturador",
                        "bombillo",
                        "embudo",
                        "corona",
                        "plato",
                        "molde",
                        "fondo",
                    ];
                    const clasesActivasLower = clasesActivas.map((c) =>
                        c.toLowerCase(),
                    );
                    return archivosList.filter((f) => {
                        const nombre = (f.nombre || "").toLowerCase();
                        if (nombre.includes("ayuda_visual") || nombre.includes("ayudas_visuales") || nombre.includes("ayudas visuales") || (f.tipo || "").toLowerCase() === "ayuda") {
                            return true;
                        }
                        const clasesEnNombre = clasesMonitoreadas.filter((c) =>
                            window.compararClasesSurgico(nombre, c),
                        );
                        if (clasesEnNombre.length === 0) {
                            return true;
                        }
                        return clasesActivasLower.some((c) =>
                            window.compararClasesSurgico(nombre, c),
                        );
                    });
                };
                
                const baseAprob = (data.archivos || []).filter((f) => {
                    const nombre = (f.nombre || "").toLowerCase().replace(/\\/g, "/");
                    if (
                        nombre.includes("documentos_rechazados") ||
                        nombre.includes("scar")
                    )
                        return false;
                    return true;
                });
                const archivosAprob =
                    filteredAprobados.length > 0
                        ? filtrarPorClasesActivas(baseAprob, filteredAprobados)
                        : baseAprob;
                if (filesContainerA) {
                    filesContainerA.innerHTML =
                        window.generarHtmlCategorizadoCastingAprobados(
                            archivosAprob,
                            otClean,
                            false,
                        ) ||
                        `<div style="text-align:center;color:#64748b;padding:15px;font-style:italic;">No hay archivos disponibles para esta clase.</div>`;
                }
                
                const baseRech = (data.archivos || []).filter((f) => {
                    const nombre = (f.nombre || "").toLowerCase().replace(/\\/g, "/");
                    if (
                        nombre.includes("pre-orden") &&
                        nombre.includes("fundicion") &&
                        !nombre.includes("modelo")
                    )
                        return false;
                    if (
                        nombre.includes("documentos_aprobados") &&
                        !nombre.includes("rechazado")
                    )
                        return false;
                    return true;
                });
                const archivosRech =
                    filteredRechazados.length > 0
                        ? filtrarPorClasesActivas(baseRech, filteredRechazados)
                        : baseRech;
                if (filesContainerR) {
                    filesContainerR.innerHTML =
                        window.generarHtmlCategorizadoCastingAprobados(
                            archivosRech,
                            otClean,
                            true,
                        ) ||
                        `<div style="text-align:center;color:#64748b;padding:15px;font-style:italic;">No hay archivos disponibles para esta clase.</div>`;
                }
            } else {
                if (filesContainerA)
                    filesContainerA.innerHTML = `<div style="text-align:center;color:#64748b;padding:15px;font-style:italic;font-family:'Poppins',sans-serif;">No hay archivos en el servidor.</div>`;
                if (filesContainerR)
                    filesContainerR.innerHTML = `<div style="text-align:center;color:#64748b;padding:15px;font-style:italic;font-family:'Poppins',sans-serif;">No hay archivos en el servidor.</div>`;
            }
        })
        .catch((err) => {
            console.error(err);
            if (filesContainerA)
                filesContainerA.innerHTML = `<div style="text-align:center;color:#ef4444;padding:15px;">Error al cargar.</div>`;
            if (filesContainerR)
                filesContainerR.innerHTML = `<div style="text-align:center;color:#ef4444;padding:15px;">Error al cargar.</div>`;
        });
};

window.cerrarModalGestionVeredicto = function () {
    const modal = document.getElementById("modalGestionVeredicto");
    if (modal) modal.classList.remove("open");
    document.body.classList.remove("modal-open");
};

// ── ENVIO ALERTA DIRECTO A CALIDAD MODAL ──
let madcSelectedFiles = [];
window.removeMadcSelectedAttachment = function (index) {
    madcSelectedFiles.splice(index, 1);
    window.renderMadcSelectedFilesBadges();
};
window.renderMadcSelectedFilesBadges = function () {
    window.renderFileCards(
        "madc-archivos-adicionales-list",
        madcSelectedFiles,
        "window.removeMadcSelectedAttachment",
        "#ea580c"
    );
};
const handleMadcFileChange = function (e) {
    if (this.files) {
        for (let i = 0; i < this.files.length; i++) {
            madcSelectedFiles.push(this.files[i]);
        }
        window.renderMadcSelectedFilesBadges();
    }
};
window.bindMadcDropzone = function () {
    const inp = document.getElementById("madc-archivos-adicionales");
    if (inp) {
        inp.removeEventListener("change", handleMadcFileChange);
        inp.addEventListener("change", handleMadcFileChange);
    }
};
setTimeout(window.bindMadcDropzone, 500);

window.enviarAlertaDirectoCalidad = async function (ot, decision, tiposAprobados, tiposRechazados) {
    const modal = document.getElementById("modalEnviarAlertaDirectoCalidad");
    if (!modal) return;
    window.bindMadcDropzone();
    madcSelectedFiles = [];
    const dropzoneInput = document.getElementById("madc-archivos-adicionales");
    if (dropzoneInput) dropzoneInput.value = "";
    const badgeContainer = document.getElementById("madc-archivos-adicionales-list");
    if (badgeContainer) badgeContainer.innerHTML = "";
    
    document.getElementById("madc-ot").value = ot;
    document.getElementById("madc-decision").value = decision;
    document.getElementById("madc-tipos-aprobados").value = JSON.stringify(tiposAprobados || []);
    document.getElementById("madc-tipos-rechazados").value = JSON.stringify(tiposRechazados || []);
    
    const otClean = ot.replace(/_\d{8}_\d{6}_.*/, "");
    document.getElementById("madc-subtitle").textContent = `OT: ${otClean} (${decision.toUpperCase()})`;
    
    const header = document.getElementById("madc-header");
    const submitBtn = document.getElementById("btn-submit-direct-calidad");
    if (header) header.style.background = "#033966";
    if (submitBtn) {
        submitBtn.style.background = "#005194";
        submitBtn.style.boxShadow = "0 4px 15px rgba(0, 81, 148, 0.3)";
    }
    
    const today = new Date();
    const formattedToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
    document.getElementById("madc-fecha").value = formattedToday;
    
    const listContainer = document.getElementById("madc-server-files-container");
    listContainer.innerHTML = `<div style="text-align:center;color:#64748b;padding:10px;">Cargando archivos...</div>`;
    modal.classList.add("open");
    document.body.classList.add("modal-open");
    
    try {
        const response = await fetch(`${window.almacenRoutes.archivos}?ot=${encodeURIComponent(ot)}`);
        const data = await response.json();
        let baseUrl = window.baseUrl || window.location.origin + "/";
        if (!baseUrl.endsWith("/")) baseUrl += "/";
        if (data.existe && data.archivos && data.archivos.length > 0) {
            const sectionsHtml = window.generarHtmlCategorizadoArchivos(
                data.archivos,
                ot,
                baseUrl,
                "calidad"
            );
            listContainer.innerHTML = sectionsHtml || `<div style="text-align:center;color:#ef4444;font-family:'Poppins',sans-serif;padding:10px;font-weight:600;">No se encontraron PDFs en el servidor.</div>`;
        } else {
            listContainer.innerHTML = `<div style="text-align:center;color:#ef4444;font-family:'Poppins',sans-serif;padding:10px;font-weight:600;">No se encontraron PDFs en el servidor.</div>`;
        }
    } catch (err) {
        console.error(err);
        listContainer.innerHTML = `<div style="text-align:center;color:#ef4444;padding:10px;">Error al consultar archivos.</div>`;
    }
};

window.cerrarModalEnviarAlertaDirectoCalidad = function () {
    const modal = document.getElementById("modalEnviarAlertaDirectoCalidad");
    if (modal) modal.classList.remove("open");
    document.body.classList.remove("modal-open");
};

document.getElementById("formEnviarAlertaDirectoCalidad")?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const ot = document.getElementById("madc-ot").value;
    const decision = document.getElementById("madc-decision").value;
    const tiposAprobados = JSON.parse(document.getElementById("madc-tipos-aprobados").value);
    const tiposRechazados = JSON.parse(document.getElementById("madc-tipos-rechazados").value);
    const fecha = document.getElementById("madc-fecha").value;
    const destinatario = document.getElementById("madc-destinatario").value.trim();
    
    if (!destinatario) {
        almacenToast("El destinatario es obligatorio.", "error");
        return;
    }
    if (!fecha) {
        almacenToast("La fecha de envío es obligatoria.", "error");
        return;
    }
    
    const submitBtn = document.getElementById("btn-submit-direct-calidad");
    if (!submitBtn) return;
    const origText = submitBtn.innerText;
    submitBtn.disabled = true;
    submitBtn.innerText = "Enviando...";
    
    const allTypes = [...tiposAprobados, ...tiposRechazados];
    const formData = new FormData(this);
    formData.set("tipo_modelo", allTypes.join(", "));
    
    madcSelectedFiles.forEach((file) => {
        if (decision === "rechazar") {
            formData.append("archivos_rechazados_extra[]", file);
        } else {
            formData.append("archivos_aprobados_extra[]", file);
        }
    });
    
    try {
        const response = await fetch(window.almacenRoutes.enviarAlertaLiberacion, {
            method: "POST",
            body: formData,
            headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
            },
        });
        const res = await response.json();
        if (res.success) {
            almacenToast(res.message || "Alerta enviada correctamente.", "success");
            setTimeout(() => {
                window.cerrarModalEnviarAlertaDirectoCalidad();
                location.reload();
            }, 1500);
        } else {
            almacenToast(res.message || "Error al enviar la alerta.", "error");
            submitBtn.disabled = false;
            submitBtn.innerText = origText;
        }
    } catch (error) {
        console.error(error);
        almacenToast("Error de conexión al enviar la alerta.", "error");
        submitBtn.disabled = false;
        submitBtn.innerText = origText;
    }
});

// ── CONFIRMACION RECHAZO DE ALMACEN MODAL ──
window.abrirModalConfirmarRechazoAlmacen = function (ot) {
    const modal = document.getElementById("modalConfirmarRechazoAlmacen");
    if (!modal) return;
    document.getElementById("cr-ot").value = ot;
    const today = new Date();
    const formattedToday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;
    document.getElementById("cr-fecha").value = formattedToday;
    modal.classList.add("open");
    document.body.classList.add("modal-open");
};

window.cerrarModalConfirmarRechazoAlmacen = function () {
    const modal = document.getElementById("modalConfirmarRechazoAlmacen");
    if (modal) modal.classList.remove("open");
    document.body.classList.remove("modal-open");
};

document.getElementById("formConfirmarRechazoAlmacen")?.addEventListener("submit", async function (e) {
    e.preventDefault();
    const ot = document.getElementById("cr-ot").value;
    const fecha = document.getElementById("cr-fecha").value;
    if (!fecha) {
        almacenToast("La fecha de recepción es obligatoria.", "error");
        return;
    }
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerText = "Procesando...";
    }
    try {
        const response = await fetch(window.almacenRoutes.confirmarRecepcionRechazo, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
            },
            body: JSON.stringify({ ot, fecha }),
        });
        const data = await response.json();
        if (data.success) {
            almacenToast(data.message, "success");
            setTimeout(() => {
                window.cerrarModalConfirmarRechazoAlmacen();
                location.reload();
            }, 1500);
        } else {
            almacenToast(data.message || "Error al procesar el rechazo.", "error");
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerText = "Confirmar y Reiniciar";
            }
        }
    } catch (err) {
        console.error(err);
        almacenToast("Error de conexión al procesar el rechazo.", "error");
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerText = "Confirmar y Reiniciar";
        }
    }
});
