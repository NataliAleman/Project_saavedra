// ── MODAL LIBERACION DE MODELOS (Calidad) — F-CCL-LDM ────────────────────────
/**
 * Mapa de visibilidad de tablas por tipo de modelo.
 * lib-tabla-1    => Macho/Hembra A-G2 (Molde, Bombillo)
 * lib-tabla-2    => Matriz V,W,X,Y,Z  (Molde, Bombillo, Obturador)
 * lib-tabla-fondo=> Fondo
 */
const LIB_TABLA_MAP = {
    Fondo: ["lib-tabla-fondo"],
    Obturador: ["lib-tabla-obturador"],
    Molde: ["lib-tabla-1", "lib-tabla-2"],
    Bombillo: ["lib-tabla-1", "lib-tabla-2"],
    Corona: ["lib-tabla-fondo"],
    Plato: ["lib-tabla-fondo"],
    Embudo: ["lib-tabla-fondo"],
    "Cabeza de Soplo": ["lib-tabla-fondo"],
    "Candado Obturador": ["lib-tabla-fondo"],
};
const LIB_TODAS_TABLAS = [
    "lib-tabla-1",
    "lib-tabla-2",
    "lib-tabla-fondo",
    "lib-tabla-obturador",
];
let _libTipo = "aprobar";
let _libOt = "";
// ── Apertura del modal ────────────────────────────────────────────────────────
/**
 * Abre el modal de Liberacion de Modelos configurado para aprobar o rechazar.
 *
 * @param {string} ot    - Nombre completo de la OT
 * @param {string} tipo  - 'aprobar' | 'rechazar'
 */
window.abrirModalLiberacion = function (ot, tipo) {
    _libTipo = tipo;
    _libOt = ot;
    const modal = document.getElementById("modalLiberacionModelo");
    const header = document.getElementById("lib-modal-header");
    const title =
        document.getElementById("lib-modal-title-text") ||
        document.getElementById("lib-modal-title");
    const subtitle = document.getElementById("lib-modal-subtitle");
    const rechazoBlock = document.getElementById("lib-rechazo-block");
    const actionsEl = document.getElementById("lib-actions");
    const hiddenOt = document.getElementById("lib-ot");
    const hiddenAccion = document.getElementById("lib-accion");
    const otDisplay = document.getElementById("lib-ot-display");
    if (!modal) return;
    // Resetear formulario para no conservar datos previos
    const formEl = document.getElementById("formLiberacion");
    if (formEl) formEl.reset();
    // Mostrar OT en la cabecera del formato
    if (otDisplay) otDisplay.textContent = ot.replace(/_\d{8}_\d{6}_.*/, "");
    // Configurar apariencia segun tipo de accion
    const esRechazo = tipo === "rechazar";
    if (esRechazo) {
        header.classList.add("lib-modal-header-rechazo");
        if (title)
            title.textContent = "Formato de Rechazo de Modelo — F-CCL-LDM";
        if (subtitle)
            subtitle.textContent = `OT: ${ot.replace(/_\d{8}_\d{6}_.*/, "")}  |  Modo: Rechazo`;
        if (rechazoBlock) rechazoBlock.classList.remove("alm-display-none");
    } else {
        header.classList.remove("lib-modal-header-rechazo");
        if (title)
            title.textContent = "Formato de Liberacion de Modelos — F-CCL-LDM";
        if (subtitle)
            subtitle.textContent = `OT: ${ot.replace(/_\d{8}_\d{6}_.*/, "")}  |  Modo: Aprobacion`;
        if (rechazoBlock) rechazoBlock.classList.add("alm-display-none");
    }
    if (actionsEl) {
        const imgDescarga =
            window.almacenAppAssets?.descarga ?? "/images/Descarga.png";
        const imgAprobado =
            window.almacenAppAssets?.aprobado ?? "/images/aprobado.png";
        const imgRechazado =
            window.almacenAppAssets?.rechazado ?? "/images/Rechazado.png";
        actionsEl.innerHTML = `
            <div style="display:flex; gap:12px; justify-content:center; align-items:center; flex-wrap:wrap; width:100%;">
                <button type="button" class="btn-lib-aprobar-send" id="lib-btn-accion"
                    style="flex:1; min-width:200px; max-width:380px; justify-content:center; display:flex; gap:8px; align-items:center; font-size:1.15em; padding:14px 28px; border-radius:10px; font-family:'Poppins',sans-serif; font-weight:700; height:auto;">
                    <img src="${imgDescarga}" alt="" style="width:20px;height:20px;">
                    Aprobar y Descargar PDF
                </button>
            </div>
        `;
        // Asignar eventos a los botones recien creados
        document
            .getElementById("lib-btn-accion")
            ?.addEventListener("click", () => _libSubmit("accion"));
    }
    if (hiddenOt) hiddenOt.value = ot;
    if (hiddenAccion) hiddenAccion.value = esRechazo ? "rechazar" : "aprobar";
    // Abrir modal
    modal.classList.add("open");
    document.body.classList.add("modal-open");
    // Inicializar la lupa para las imagenes
    _libInicializarZoom();
    // Pre-cargar datos existentes del backend
    _libCargarDatos(ot);
};
// ── Cierre del modal ──────────────────────────────────────────────────────────
window.cerrarModalLiberacion = function () {
    const modal = document.getElementById("modalLiberacionModelo");
    if (modal) modal.classList.remove("open");
    document.body.classList.remove("modal-open");
    // Ocultar zoom si quedara visible
    const zoomEl = document.getElementById("lib-zoom-result");
    if (zoomEl) zoomEl.classList.add("alm-display-none");
};
/**
 * Actualiza dinamicamente el badge de estado en la columna "Modelo" de la tabla
 * sin necesidad de recargar la pagina. Se ejecuta tras un guardado parcial (borrador).
 *
 * @param {string} ot          - Identificador exacto de la OT
 * @param {string} nuevoEstado - 'pendiente' | 'aprobado' | 'rechazado'
 */
function _libActualizarBadgeEstado(ot, nuevoEstado) {
    const container = document.getElementById(`status-modelo-${ot}`);
    if (!container) return;
    const assets = window.almacenAppAssets ?? {};
    const imgMap = {
        pendiente: {
            src: assets.guardado ?? "/images/Guardado.png",
            alt: "Guardado (Borrador)",
            cls: "badge-modelo-guardado",
            title: "Datos capturados por Calidad (borrador)",
        },
        aprobado: {
            src: assets.aprobado ?? "/images/aprobado.png",
            alt: "Aprobado",
            cls: "badge-modelo-ok",
            title: "Modelo liberado y aprobado por Calidad",
        },
        rechazado: {
            src: assets.rechazado ?? "/images/Rechazado.png",
            alt: "Rechazado",
            cls: "badge-modelo-rechazado",
            title: "Modelo rechazado por Calidad",
        },
    };
    const cfg = imgMap[nuevoEstado];
    if (!cfg) return;
    container.innerHTML = `
        <span class="${cfg.cls}" title="${cfg.title}">
            <img src="${cfg.src}" alt="${cfg.alt}" style="width:38px;height:38px;">
        </span>
    `;
}
// Cerrar al hacer clic en el backdrop
document.addEventListener("click", (e) => {
    if (e.target.id === "modalLiberacionModelo") cerrarModalLiberacion();
    if (e.target.id === "modalScar") cerrarModalScar();
    if (e.target.id === "modalEnviarScar") cerrarModalEnviarScar();
});
// Cerrar lightbox con Escape, cerrar modal con Escape si lightbox cerrado
document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    const lb = document.getElementById("lib-lightbox");
    if (lb && lb.classList.contains("open")) {
        libCerrarLightbox();
    } else {
        cerrarModalLiberacion();
        cerrarModalScar();
        cerrarModalEnviarScar();
    }
});
// ── Cambio dinamico de tabla segun tipo seleccionado ─────────────────────────
/**
 * Muestra u oculta las tablas segun el tipo de modelo elegido en el select.
 * Los campos de las tablas ocultas se marcan con data-lib-hidden="1" para
 * ser llenados con 0.000 antes del submit.
 *
 * @param {string} tipo - Valor seleccionado en #lib-tipo
 */
window.libCambiarTipo = function (tipo) {
    const aviso = document.getElementById("lib-tabla-aviso");
    const visibles = LIB_TABLA_MAP[tipo] ?? [];
    // Resetear formulario para evitar cruce de datos entre "Molde" y "Bombillo"
    const form = document.getElementById("formLiberacion");
    const currOt = document.getElementById("lib-ot")?.value;
    const currAcc = document.getElementById("lib-accion")?.value;
    if (form) form.reset();
    // Restaurar meta datos despues de limpiar
    if (document.getElementById("lib-ot"))
        document.getElementById("lib-ot").value = currOt;
    if (document.getElementById("lib-accion"))
        document.getElementById("lib-accion").value = currAcc;
    if (document.getElementById("lib-tipo"))
        document.getElementById("lib-tipo").value = tipo;
    LIB_TODAS_TABLAS.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) return;
        const activo = visibles.includes(id);
        el.classList.toggle("alm-display-none", !activo);
        if (activo) {
            el.removeAttribute("hidden");
        } else {
            el.setAttribute("hidden", "");
        }
        // Marcar inputs ocultos para el zero-fill en submit
        el.querySelectorAll('input[type="number"]').forEach((inp) => {
            inp.dataset.libHidden = activo ? "0" : "1";
        });
    });
    if (aviso) aviso.classList.toggle("alm-display-none", visibles.length > 0);
    const tituloFondo = document.getElementById("lib-tabla-fondo-title");
    if (tituloFondo) {
        if (tipo === "Fondo") tituloFondo.textContent = "Dimensiones de Fondo";
        else tituloFondo.textContent = "Dimensiones de " + tipo;
    }
    const decisionSelector = document.getElementById("lib-decision-selector");
    if (decisionSelector) {
        decisionSelector.classList.toggle("alm-display-none", !tipo);
        decisionSelector.style.display = tipo ? "flex" : "none";
    }
    if (typeof _libActualizarColorSelectPropio === "function") {
        _libActualizarColorSelectPropio();
    }
    // Si tenemos registros cacheados especificos para este tipo, poblamos la UI
    if (
        tipo &&
        window.cacheLiberacionGlobal &&
        window.cacheLiberacionGlobal[tipo]
    ) {
        const cached = window.cacheLiberacionGlobal[tipo];
        _libRellenarInputs(cached);
        if (cached.decision) {
            _libSetDecisionUI(cached.decision);
        } else {
            _libSetDecisionUI("aprobar");
        }
    } else {
        _libSetDecisionUI("aprobar");
    }
    // CARGAR BORRADOR AUTOMÁTICAMENTE ANTES DE CAPTURAR EL ESTADO INICIAL
    window.loadLiberacionDraft();
    // Capturar el estado despues de llenar la UI
    setTimeout(() => {
        window._libLastSavedState = _libGetSerializedForm();
    }, 150);
};
function _libGetSerializedForm() {
    const form = document.getElementById("formLiberacion");
    if (!form) return "";
    _libZeroFillOcultos();
    document
        .querySelectorAll(".lib-num-input, .lib-num-input-sm")
        .forEach((inp) => formatInputTruncated(inp));
    return new URLSearchParams(new FormData(form)).toString();
}
// ── Lightbox de imagenes ──────────────────────────────────────────────────────
/**
 * Abre el lightbox con la imagen del wrapper clicado.
 * @param {HTMLElement} wrapper - div.lib-img-zoom-wrapper
 */
window.libAbrirLightbox = function (wrapper) {
    const lb = document.getElementById("lib-lightbox");
    const lbImg = document.getElementById("lib-lightbox-img");
    const lbCap = document.getElementById("lib-lightbox-caption");
    const img = wrapper.querySelector(".lib-ref-img");
    if (!lb || !lbImg || !img) return;
    lbImg.src = wrapper.dataset.src || img.src;
    lbImg.alt = wrapper.dataset.label || img.alt;
    if (lbCap) lbCap.textContent = wrapper.dataset.label || "";
    lb.classList.add("open");
    document.body.classList.add("modal-open");
};
/**
 * Cierra el lightbox de imagen ampliada.
 */
window.libCerrarLightbox = function () {
    const lb = document.getElementById("lib-lightbox");
    if (lb) lb.classList.remove("open");
};
// ── Zoom tipo lupa (magnifying glass) ────────────────────────────────────────
/**
 * Inicializa el efecto de lupa para todas las imagenes de referencia del modal.
 * Se llama una sola vez al abrir el modal; usa delegacion para evitar
 * registros duplicados si el modal se abre varias veces.
 */
let _libZoomInit = false;
function _libInicializarZoom() {
    if (_libZoomInit) return;
    _libZoomInit = true;
    const zoomResult = document.getElementById("lib-zoom-result");
    if (!zoomResult) return;
    // Tamano del recuadro de zoom y factor de ampliacion
    const ZOOM_SIZE = 450;
    const ZOOM_RATIO = 3.2;
    document.addEventListener("mousemove", (e) => {
        const wrapper = e.target.closest(".lib-img-zoom-wrapper");
        if (!wrapper) {
            zoomResult.classList.add("alm-display-none");
            return;
        }
        // Solo activar si el modal de liberacion esta abierto
        const modal = document.getElementById("modalLiberacionModelo");
        if (!modal || !modal.classList.contains("open")) {
            zoomResult.classList.add("alm-display-none");
            return;
        }
        const img = wrapper.querySelector(".lib-ref-img");
        if (!img || !img.complete) return;
        const rect = img.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        // Ignorar si el cursor esta fuera de los limites de la imagen
        if (x < 0 || y < 0 || x > rect.width || y > rect.height) {
            zoomResult.classList.add("alm-display-none");
            return;
        }
        // Calcular posicion del background para el recuadro de zoom
        const bgX = -(x * ZOOM_RATIO - ZOOM_SIZE / 2);
        const bgY = -(y * ZOOM_RATIO - ZOOM_SIZE / 2);
        zoomResult.classList.remove("alm-display-none");
        zoomResult.style.backgroundImage = `url(${img.src})`;
        zoomResult.style.backgroundSize = `${rect.width * ZOOM_RATIO}px ${rect.height * ZOOM_RATIO}px`;
        zoomResult.style.backgroundPosition = `${bgX}px ${bgY}px`;
        zoomResult.style.width = `${ZOOM_SIZE}px`;
        zoomResult.style.height = `${ZOOM_SIZE}px`;
        // Posicionar el recuadro cerca del cursor, evitando que salga de pantalla
        const offsetX = 24;
        const offsetY = -ZOOM_SIZE / 2;
        let posX = e.clientX + offsetX;
        let posY = e.clientY + offsetY;
        if (posX + ZOOM_SIZE > window.innerWidth - 10)
            posX = e.clientX - ZOOM_SIZE - offsetX;
        if (posY < 10) posY = 10;
        if (posY + ZOOM_SIZE > window.innerHeight - 10)
            posY = window.innerHeight - ZOOM_SIZE - 10;
        zoomResult.style.left = `${posX}px`;
        zoomResult.style.top = `${posY}px`;
    });
    document.addEventListener(
        "mouseleave",
        () => {
            zoomResult.classList.add("alm-display-none");
        },
        true,
    );
}
// ── Carga de datos existentes desde el backend ────────────────────────────────
window.cacheLiberacionGlobal = {};
/**
 * Consulta la API y pre-llena el formulario con datos guardados previamente.
 * Estructura medidas_plantilla: claves en formato "{row}_{col}" (ej: "plantilla_V", "templadera_x1").
 */
async function _libCargarDatos(ot) {
    if (!window.almacenRoutes?.getLiberacion) return;
    try {
        const url = `${window.almacenRoutes.getLiberacion}?ot=${encodeURIComponent(ot)}`;
        const resp = await fetch(url, {
            headers: {
                Accept: "application/json",
                "X-Requested-With": "XMLHttpRequest",
            },
        });
        const data = await resp.json();
        // Normalizar claves de cache desde la DB para evitar problemas case-sensitive
        const rawCache = data.registros_por_tipo || {};
        window.cacheLiberacionGlobal = {};
        const MAPA_TIPO = {
            "candado obturador": "Candado Obturador",
            "cabeza de soplo": "Cabeza de Soplo",
            embudo: "Embudo",
            corona: "Corona",
            plato: "Plato",
            fondo: "Fondo",
            obturador: "Obturador",
            molde: "Molde",
            bombillo: "Bombillo",
            pistones: "Pistones",
            guías: "Guías",
            guias: "Guías",
        };
        const knownKeys = Object.keys(MAPA_TIPO);
        for (let key in rawCache) {
            let normalizedKey = key;
            const keyLow = key.toLowerCase();
            for (let k of knownKeys) {
                if (keyLow.includes(k)) {
                    normalizedKey = MAPA_TIPO[k];
                    break;
                }
            }
            window.cacheLiberacionGlobal[normalizedKey] = rawCache[key];
        }
        // Colorear las opciones del select según su estado
        _libActualizarColoresSelect();
        if (!data.success) return;
        const lastLib = data.liberacion;
        // Pre-seleccionar tipo y actualizar visibilidad de tablas (priorizando el primer modelo en blanco/sin procesar)
        const selectTipo = document.getElementById("lib-tipo");
        if (selectTipo) {
            const autoSelectValue = _libFiltrarTiposModelo(
                window._currentClasesActivas,
                window._currentTodasClases,
            );
            if (autoSelectValue) {
                selectTipo.value = autoSelectValue;
                libCambiarTipo(autoSelectValue);
            } else if (lastLib && lastLib.tipo_modelo) {
                // Flujo normal: cargar el último modelo si todos están procesados
                let tipo = lastLib.tipo_modelo;
                const tipoLow = tipo.toLowerCase();
                for (let k of knownKeys) {
                    if (tipoLow.includes(k)) {
                        tipo = MAPA_TIPO[k];
                        break;
                    }
                }
                selectTipo.value = tipo;
                libCambiarTipo(tipo);
            } else {
                // Capturar el estado si no habia lastLib
                setTimeout(() => {
                    window._libLastSavedState = _libGetSerializedForm();
                }, 150);
            }
        }
    } catch (err) {
        console.error("Error al cargar datos de liberacion:", err);
    }
}
/**
 * Colorea las opciones del select #lib-tipo según la decisión guardada o seleccionada.
 */
function _libActualizarColoresSelect() {
    const select = document.getElementById("lib-tipo");
    if (!select) return;
    select.querySelectorAll("option").forEach((opt) => {
        const val = opt.value;
        if (!val) {
            opt.style.backgroundColor = "";
            opt.style.color = "";
            return;
        }
        const record =
            window.cacheLiberacionGlobal && window.cacheLiberacionGlobal[val];
        if (record) {
            if (record.decision === "aprobar") {
                opt.style.backgroundColor = "#d1fae5"; // Verde suave
                opt.style.color = "#065f46";
            } else if (record.decision === "rechazar") {
                opt.style.backgroundColor = "#fee2e2"; // Rojo suave
                opt.style.color = "#991b1b";
            } else {
                opt.style.backgroundColor = "";
                opt.style.color = "";
            }
        } else {
            opt.style.backgroundColor = "";
            opt.style.color = "";
        }
    });
    _libActualizarColorSelectPropio();
}
window._libActualizarColoresSelect = _libActualizarColoresSelect;
/**
 * Colorea el select en sí de acuerdo al valor y estado actual.
 */
function _libActualizarColorSelectPropio() {
    const select = document.getElementById("lib-tipo");
    if (!select) return;
    select.style.backgroundColor = "";
    select.style.color = "";
    select.style.borderColor = "#cbd5e1"; // neutral border
}
window._libActualizarColorSelectPropio = _libActualizarColorSelectPropio;
/**
 * Rellena los inputs con un objeto "lib" especifico de un tipo de modelo.
 */
function _libRellenarInputs(lib) {
    if (!lib) return;
    try {
        const setValOrEmpty = (inp, val) => {
            if (!inp) return;
            if (val == null || val === "" || parseFloat(val) === 0) {
                inp.value = "";
            } else {
                inp.value = val;
            }
        };

        // Modelo (Macho/Hembra): id = lib-modelo-{ITEM}-{col}
        if (lib.medidas_modelo && typeof lib.medidas_modelo === "object") {
            Object.entries(lib.medidas_modelo).forEach(([item, cols]) => {
                if (!cols) return;
                ["dibujo", "macho", "hembra"].forEach((col) => {
                    const inp = document.getElementById(
                        `lib-modelo-${item}-${col}`,
                    );
                    setValOrEmpty(inp, cols[col]);
                });
            });
        }
        // Plantilla/Templadera (Matriz): clave = "{row}_{col}", id = lib-plt-{row}-{col}-{dim}
        if (
            lib.medidas_plantilla &&
            typeof lib.medidas_plantilla === "object"
        ) {
            Object.entries(lib.medidas_plantilla).forEach(([key, cols]) => {
                if (!cols) return;
                // key formato: "plantilla_V", "templadera_x1", etc.
                const m = key.match(/^(plantilla|templadera)_(.+)$/);
                if (!m) return;
                const [, row, col] = m;
                ["dibujo", "fisico"].forEach((dim) => {
                    const inp = document.getElementById(
                        `lib-plt-${row}-${col}-${dim}`,
                    );
                    setValOrEmpty(inp, cols[dim]);
                });
            });
        }
        // Fondo: id = lib-fondo-{key}-{dim}
        if (lib.medidas_fondo && typeof lib.medidas_fondo === "object") {
            Object.entries(lib.medidas_fondo).forEach(([item, cols]) => {
                if (!cols) return;
                ["dibujo", "fisico"].forEach((dim) => {
                    const inp = document.getElementById(
                        `lib-fondo-${item}-${dim}`,
                    );
                    setValOrEmpty(inp, cols[dim]);
                });
            });
        }
        // Obturador: id = lib-obturador-{key}-{dim}
        if (
            lib.medidas_obturador &&
            typeof lib.medidas_obturador === "object"
        ) {
            Object.entries(lib.medidas_obturador).forEach(([item, cols]) => {
                if (!cols) return;
                ["dibujo", "fisico"].forEach((dim) => {
                    const inp = document.getElementById(
                        `lib-obturador-${item}-${dim}`,
                    );
                    setValOrEmpty(inp, cols[dim]);
                });
            });
        }
        const obsModelo = document.getElementById("lib-obs-modelo");
        const obsPlantilla = document.getElementById("lib-obs-plantilla");
        const obsFondo = document.getElementById("lib-obs-fondo");
        const obsObturador = document.getElementById("lib-obs-obturador");
        const rechEl = document.getElementById("lib-motivo-rechazo");
        if (obsModelo) obsModelo.value = lib.observaciones_modelo || "";
        if (obsPlantilla)
            obsPlantilla.value = lib.observaciones_plantilla || "";
        if (obsFondo) obsFondo.value = lib.observaciones_fondo || "";
        if (obsObturador)
            obsObturador.value = lib.observaciones_obturador || "";
        if (rechEl) rechEl.value = lib.motivo_rechazo || "";
        // Truncar y formatear todos los campos numericos despues de cargar
        document
            .querySelectorAll(".lib-num-input, .lib-num-input-sm")
            .forEach((inp) => {
                formatInputTruncated(inp);
            });
    } catch (err) {
        console.error("Error al rellenar inputs de liberacion:", err);
    }
}
// ── Envio del formulario ──────────────────────────────────────────────────────
/**
 * Antes del submit, rellena con 0.000 todos los inputs de tablas ocultas
 * para garantizar consistencia en la base de datos.
 */
function _libZeroFillOcultos() {
    document.querySelectorAll('input[data-lib-hidden="1"]').forEach((inp) => {
        inp.value = "0.000";
    });
    // Limpiar observaciones de las tablas ocultas
    const t1 = document.getElementById("lib-tabla-1");
    if (t1 && t1.classList.contains("alm-display-none")) {
        const obs = document.getElementById("lib-obs-modelo");
        if (obs) obs.value = "";
    }
    const t2 = document.getElementById("lib-tabla-2");
    if (t2 && t2.classList.contains("alm-display-none")) {
        const obs = document.getElementById("lib-obs-plantilla");
        if (obs) obs.value = "";
    }
    const tf = document.getElementById("lib-tabla-fondo");
    if (tf && tf.classList.contains("alm-display-none")) {
        const obs = document.getElementById("lib-obs-fondo");
        if (obs) obs.value = "";
    }
    const to = document.getElementById("lib-tabla-obturador");
    if (to && to.classList.contains("alm-display-none")) {
        const obs = document.getElementById("lib-obs-obturador");
        if (obs) obs.value = "";
    }
}
/**
 * Envia el formulario de liberacion al backend.
 *
 * @param {'guardar'|'accion'} accion
 */
async function _libSubmit(accion) {
    const ot = document.getElementById("lib-ot")?.value;
    if (!ot) return;
    // Validacion del select tipo
    const tipoVal = document.getElementById("lib-tipo")?.value;
    if (!tipoVal) {
        almacenToast(
            "Selecciona el Tipo de Modelo antes de continuar.",
            "error",
        );
        return;
    }
    const activeDecisionEl = document.querySelector(
        ".lib-decision-card.active",
    );
    const decisionVal =
        activeDecisionEl && activeDecisionEl.id === "lib-dec-rechazar"
            ? "rechazar"
            : "aprobar";
    // Validacion obligatoria de motivo de rechazo
    if (decisionVal === "rechazar") {
        const motivo = document
            .getElementById("lib-motivo-rechazo")
            ?.value?.trim();
        if (!motivo) {
            almacenToast(
                'El campo "Motivo de Rechazo" es obligatorio para rechazar la liberacion.',
                "error",
            );
            document.getElementById("lib-motivo-rechazo")?.focus();
            return;
        }
    }
    // Rellenar con 0.000 los campos de tablas que no aplican al tipo seleccionado
    _libZeroFillOcultos();
    // Truncar y formatear todos los inputs a 3 decimales (rellenando los vacíos visibles con 0.000)
    document
        .querySelectorAll(".lib-num-input, .lib-num-input-sm")
        .forEach((inp) => {
            if (inp.value === "" && inp.dataset.libHidden !== "1") {
                inp.value = "0.000";
            } else {
                formatInputTruncated(inp);
            }
        });
    const form = document.getElementById("formLiberacion");
    const currentFormState = new URLSearchParams(new FormData(form)).toString();
    // Verificar si no hay cambios y es un rechazo ya guardado
    if (accion === "accion" && decisionVal === "rechazar") {
        const cached =
            window.cacheLiberacionGlobal &&
            window.cacheLiberacionGlobal[tipoVal];
        const isAlreadyRejected = cached && cached.decision === "rechazar";
        if (
            isAlreadyRejected &&
            window._libLastSavedState === currentFormState
        ) {
            // Abrir SCAR directamente sin descargar de nuevo el PDF
            const motivoRechazo =
                document.getElementById("lib-motivo-rechazo")?.value || "";
            cerrarModalLiberacion();
            if (typeof window.abrirModalScar === "function") {
                window.abrirModalScar(ot, tipoVal, motivoRechazo);
            }
            return;
        }
    }
    const fd = new FormData(form);
    fd.set("accion", accion === "accion" ? decisionVal : accion);
    fd.set("decision", decisionVal);
    fd.set("ot", ot);
    // Bloquear botones durante la peticion
    const btns = document.querySelectorAll("#lib-actions button");
    btns.forEach((b) => {
        b.disabled = true;
    });
    try {
        const resp = await fetch(window.almacenRoutes.submitLiberacion, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content ?? "",
                "X-Requested-With": "XMLHttpRequest",
            },
            body: fd,
        });
        const data = await resp.json();
        if (data.success) {
            almacenToast(data.message, "success");
            // LIMPIAR BORRADOR TRAS ÉXITO
            window.clearLiberacionDraft();
            // Descargar PDF automaticamente con nombre estetico
            if (data.pdf_url) {
                const enlace = document.createElement("a");
                enlace.href = data.pdf_url;
                enlace.download = data.pdf_filename ?? "Liberacion_Modelos.pdf";
                enlace.classList.add("alm-display-none");
                document.body.appendChild(enlace);
                enlace.click();
                document.body.removeChild(enlace);
            }
            if (accion === "guardar") {
                // Actualizar badge de estado en tabla sin recargar pagina
                if (data.ot && data.nuevo_estado) {
                    _libActualizarBadgeEstado(data.ot, data.nuevo_estado);
                }
                setTimeout(() => {
                    cerrarModalLiberacion();
                    window.location.reload();
                }, 1800);
            } else {
                // ── Máquina de estados: disparar evento de liberación final ──
                const otFinal = data.ot || ot;
                document.dispatchEvent(
                    new CustomEvent("modeloLiberado", {
                        detail: { ot: otFinal, accion },
                    }),
                );
                // ── Si fue un RECHAZO: abrir modal SCAR prellenado ──────────
                const activeDecisionEl = document.querySelector(
                    ".lib-decision-card.active",
                );
                const esRechazoPorDecision =
                    document.getElementById("lib-accion")?.value ===
                    "rechazar" ||
                    (activeDecisionEl &&
                        activeDecisionEl.id === "lib-dec-rechazar");
                // También detectar por la decisión enviada al servidor
                const decisionFD = fd.get("decision");
                const esRechazoFinal =
                    esRechazoPorDecision || decisionFD === "rechazar";
                if (
                    esRechazoFinal &&
                    typeof window.abrirModalScar === "function"
                ) {
                    const tipoModelo =
                        document.getElementById("lib-tipo")?.value || "";
                    const motivoRechazo =
                        document.getElementById("lib-motivo-rechazo")?.value ||
                        "";
                    // Pequeno delay para que el PDF se descargue primero
                    setTimeout(() => {
                        cerrarModalLiberacion();
                        window.abrirModalScar(
                            otFinal,
                            tipoModelo,
                            motivoRechazo,
                        );
                    }, 600);
                } else {
                    setTimeout(() => {
                        cerrarModalLiberacion();
                        window.location.reload();
                    }, 1800);
                }
            }
            // Actualizar ultimo estado guardado
            window._libLastSavedState = currentFormState;
        } else {
            almacenToast(
                data.message || "Ocurrio un error inesperado.",
                "error",
            );
        }
    } catch (err) {
        console.error("Error al enviar liberacion:", err);
        almacenToast("Error de red al enviar el formulario.", "error");
    } finally {
        btns.forEach((b) => {
            b.disabled = false;
        });
    }
}
// ═══════════════════════════════════════════════════════════════════════════════


// Expose to window for global access
window.LIB_TODAS_TABLAS = LIB_TODAS_TABLAS;
window._libRellenarInputs = _libRellenarInputs;
window._libTipo = _libTipo;
window._libActualizarColorSelectPropio = _libActualizarColorSelectPropio;
window._libGetSerializedForm = _libGetSerializedForm;
window._libInicializarZoom = _libInicializarZoom;
window._libCargarDatos = _libCargarDatos;
window._libZeroFillOcultos = _libZeroFillOcultos;
window._libActualizarColoresSelect = _libActualizarColoresSelect;
window._libSubmit = _libSubmit;
window._libActualizarBadgeEstado = _libActualizarBadgeEstado;
window._libOt = _libOt;
window._libZoomInit = _libZoomInit;
window.LIB_TABLA_MAP = LIB_TABLA_MAP;
