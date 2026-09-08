// ── MÁQUINA DE ESTADOS VISUAL — Estado del Modelo  (v4 — FSM Completa) ─────────
// ═══════════════════════════════════════════════════════════════════════════════
/**
 * ModeloStateMachine (v4)
 * ─────────────────────────────────────────────────────────────────────────────
 * FSM de 8 estados exactos en 3 niveles jerárquicos.
 *
 * REGLA DE ORO: Una vez alcanzado un nivel, los estados de nivel inferior
 * son ignorados. La transición solo avanza, nunca retrocede.
 *
 * ┌───────┬────────────┬──────────────┬──────────────────────────────────────┐
 * │ NIVEL │ Estado     │ Imagen       │ Disparador                           │
 * ├───────┼────────────┼──────────────┼──────────────────────────────────────┤
 * │   1   │ recibido   │ Recibido.png │ Alerta inicial del servidor          │
 * │   1   │ revisando  │ Revisando.png│ Clic en "Ver Archivos"               │
 * │   1   │ editando   │ Editando.png │ Clic en "Aprobar/Rechazar Lib."      │
 * ├───────┼────────────┼──────────────┼──────────────────────────────────────┤
 * │   2   │ guardado   │ Guardado.png │ Clic en "Guardar"                    │
 * │   2   │ descargado │ Descarga.png │ PDF generado y descargado            │
 * │   2   │ espera     │ documento.png│ Correo enviado / Dpto. confirmó      │
 * ├───────┼────────────┼──────────────┼──────────────────────────────────────┤
 * │   3   │ aprobado   │ Aprobado.png │ Liberación aprobada (servidor)       │
 * │   3   │ rechazado  │ Rechazado.png│ Liberación rechazada (servidor)      │
 * └───────┴────────────┴──────────────┴──────────────────────────────────────┘
 */
const ModeloStateMachine = (() => {
    function _baseUrl() {
        let b = window.baseUrl || window.location.origin + "/";
        return b.endsWith("/") ? b : b + "/";
    }
    // ── Registro de estados ───────────────────────────────────────────────────
    const ESTADOS = {
        recibido: {
            img: "Recibido.png",
            label: "Nuevo",
            title: "Alerta inicial recibida, pendiente de procesar modelo por Almacén",
            borderColor: "#cbd5e1",
            bgColor: "#f1f5f9",
            textColor: "#64748b",
            nivel: 1,
            prio: 1,
        },
        pre_orden: {
            img: "pdf-view.png",
            label: "Pre-Orden",
            title: "Pre-orden de modelo generada y guardada, pendiente de enviar",
            borderColor: "#60a5fa",
            bgColor: "#eff6ff",
            textColor: "#2563eb",
            nivel: 3,
            prio: 2,
        },
        correo_enviado: {
            img: "enviando.png",
            label: "Correo Enviado",
            title: "Pre-orden enviada por correo electrónico, esperando revisión de Calidad",
            borderColor: "#818cf8",
            bgColor: "#e0e7ff",
            textColor: "#4f46e5",
            nivel: 2,
            prio: 3,
        },
        tiene_modelo: {
            img: "Espera.png",
            label: "Tengo Modelo",
            title: "Modelo físico disponible en Almacén, en espera de revisión por Calidad",
            borderColor: "#0ea5e9",
            bgColor: "#f0f9ff",
            textColor: "#0369a1",
            nivel: 3,
            prio: 4,
        },
        revisando: {
            img: "Revisando.png",
            label: "En Revisión",
            title: "Calidad está realizando la revisión del modelo",
            borderColor: "#f59e0b",
            bgColor: "#fffbeb",
            textColor: "#b45309",
            nivel: 2,
            prio: 5,
        },
        aprobado: {
            img: "Quality.png",
            label: "Aprobado",
            title: "Modelo aprobado y liberado por Calidad",
            borderColor: "#10b981",
            bgColor: "#ecfdf5",
            textColor: "#047857",
            nivel: 3,
            prio: 99,
        },
        aprobado_final: {
            img: "Aprobado.png",
            label: "Aprobado",
            title: "Proceso de modelo y casting finalizado y aprobado",
            borderColor: "#15803d",
            bgColor: "#f0fdf4",
            textColor: "#15803d",
            nivel: 3,
            prio: 100,
        },
        casting_aprobado: {
            img: "Proveedor.png",
            label: "Enviado a Proveedor",
            title: "Pre-orden de casting enviada al proveedor, proceso finalizado",
            borderColor: "#9333ea",
            bgColor: "#f3e8ff",
            textColor: "#9333ea",
            nivel: 3,
            prio: 100,
        },
        rechazado: {
            img: "Quality.png",
            label: "Rechazado",
            title: "Modelo rechazado por Calidad debido a desviaciones",
            borderColor: "#ef4444",
            bgColor: "#fef2f2",
            textColor: "#b91c1c",
            nivel: 3,
            prio: 99,
        },
        rechazado_final: {
            img: "Rechazado.png",
            label: "Rechazado",
            title: "Modelo rechazado y reproceso iniciado por Almacén",
            borderColor: "#dc2626",
            bgColor: "#fef2f2",
            textColor: "#b91c1c",
            nivel: 3,
            prio: 100,
        },
        mixto: {
            img: "Quality.png",
            label: "Mixto",
            title: "Liberación mixta por Calidad (clases aprobadas y rechazadas)",
            borderColor: "#eab308",
            bgColor: "#fef9c3",
            textColor: "#854d0e",
            nivel: 3,
            prio: 99,
        },
        casting: {
            img: "pdf-view.png",
            label: "Casting",
            title: "Pre-orden de casting generada y aprobada",
            borderColor: "#059669",
            bgColor: "#f0fdf4",
            textColor: "#15803d",
            nivel: 3,
            prio: 99,
        },
        reproceso: {
            img: "Reproceso.png",
            label: "Reproceso",
            title: "Retornado hacia un nuevo ciclo de modelo (Reproceso)",
            borderColor: "#ec4899",
            bgColor: "#fdf2f8",
            textColor: "#be185d",
            nivel: 1,
            prio: 1,
        },
    };
    /** Mapa alias → estado canónico para la caché interna */
    const _CANONICAL = {
        editando: "revisando",
        guardado: "revisando",
        descargado: "revisando",
        pendiente: "revisando",
        en_proceso: "revisando",
        espera: "tiene_modelo",
        enviando: "correo_enviado",
        documento: "tiene_modelo",
    };
    /** Caché: ot → estado canónico actual */
    const _cache = {};
    // ── Aplicar estado al DOM ─────────────────────────────────────────────────
    function _render(ot, estado, cfg) {
        const el = document.getElementById(`status-modelo-${ot}`);
        if (!el) {
            console.warn(
                `[FSM] Contenedor no encontrado: #status-modelo-${ot}`,
            );
            return;
        }
        const src = _baseUrl() + "images/" + cfg.img;
        el.innerHTML = `
            <div class="status-modelo-container" style="display: inline-flex; flex-direction: column; align-items: center; gap: 2px; padding: 6px; border-radius: 8px;">
                <span class="badge-modelo-icon" title="${cfg.title}" style="display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; background: ${cfg.bgColor}; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid ${cfg.borderColor}; transition: all 0.2s ease;">
                    <img src="${src}" alt="${cfg.label}" style="width: 34px; height: 34px; object-fit: contain;">
                </span>
                <span class="status-modelo-label" style="font-size: 11px; font-weight: 700; color: ${cfg.textColor}; margin-top: 4px; text-transform: uppercase; white-space: nowrap;">
                    ${cfg.label}
                </span>
            </div>
        `;
        console.info(`[FSM] "${ot}": → ${estado} (nivel ${cfg.nivel})`);
    }
    // ── Transición normal (respeta jerarquía) ─────────────────────────────────
    function transicion(ot, estado) {
        const canonical = _CANONICAL[estado] ?? estado;
        const cfg = ESTADOS[canonical];
        if (!cfg) {
            console.warn(
                `[FSM] Estado desconocido: "${estado}" (canonical: "${canonical}")`,
            );
            return false;
        }
        const actual = _cache[ot];
        const cfgActual = actual ? ESTADOS[actual] : null;
        // Regla 1 — Terminales son permanentes
        if (cfgActual?.nivel === 3) {
            console.info(
                `[FSM] "${ot}": BLOQUEADO — terminal "${actual}" (→ ${estado})`,
            );
            return false;
        }
        // Regla 2 — No retroceder prioridad
        if (cfg.prio <= (cfgActual?.prio ?? 0)) {
            console.info(
                `[FSM] "${ot}": BLOQUEADO — retroceso (${actual}[${cfgActual?.prio}] → ${estado}[${cfg.prio}])`,
            );
            return false;
        }
        _cache[ot] = canonical;
        _render(ot, canonical, cfg);
        return true;
    }
    // ── Forzar terminal (solo desde servidor) ─────────────────────────────────
    function _forzarTerminal(ot, estado) {
        const canonical = _CANONICAL[estado] ?? estado;
        const cfg = ESTADOS[canonical];
        if (!cfg || cfg.nivel !== 3) {
            console.warn(`[FSM] _forzarTerminal: "${estado}" no es terminal`);
            return false;
        }
        _cache[ot] = canonical;
        _render(ot, canonical, cfg);
        console.info(`[FSM] "${ot}": TERMINAL FORZADO → ${estado} ★`);
        return true;
    }
    // ── Sincronización desde DOM ──────────────────────────────────────────────
    function init() {
        document.querySelectorAll("tr[data-ot]").forEach((tr) => {
            const ot = tr.getAttribute("data-ot");
            if (ot && !_cache[ot]) {
                const fsmState =
                    tr.getAttribute("data-estado-real") || "recibido";
                let estado = _CANONICAL[fsmState] ?? fsmState;
                if (!ESTADOS[estado]) {
                    estado = "recibido";
                }
                _cache[ot] = estado;
                console.info(`[FSM] init: "${ot}" -> ${estado} (from backend)`);
            }
        });
    }
    function getEstado(ot) {
        return _cache[ot] ?? null;
    }
    function getNivel(ot) {
        return ESTADOS[_cache[ot]]?.nivel ?? 0;
    }
    function onAlertaEnviada(ot) {
        transicion(ot, "recibido");
    }
    function onVerArchivos(ot) {
        transicion(ot, "revisando");
    }
    function onAbrirDecision(ot) {
        transicion(ot, "editando");
    }
    function onGuardar(ot) {
        transicion(ot, "guardado");
    }
    function onDescargado(ot) {
        transicion(ot, "descargado");
    }
    function onCorreoEnviado(ot) {
        transicion(ot, "correo_enviado");
    }
    function onConfirmarModelo(ot) {
        transicion(ot, "tiene_modelo");
    }
    function onEnEspera(ot) {
        transicion(ot, "tiene_modelo");
    }
    function onAprobado(ot) {
        _forzarTerminal(ot, "aprobado");
    }
    function onRechazado(ot) {
        _forzarTerminal(ot, "rechazado");
    }
    return {
        transicion,
        _forzarTerminal,
        init,
        getEstado,
        getNivel,
        onAlertaEnviada,
        onVerArchivos,
        onAbrirDecision,
        onGuardar,
        onDescargado,
        onCorreoEnviado,
        onConfirmarModelo,
        onEnEspera,
        onAprobado,
        onRechazado,
        ESTADOS,
    };
})();
window.ModeloStateMachine = ModeloStateMachine;
// ═══════════════════════════════════════════════════════════════════════════════


// Expose to window for global access
window.ModeloStateMachine = ModeloStateMachine;
