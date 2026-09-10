let wOrderArray = window.wOInProgress;

// ══════════════════════════════════════════════════════════
// PTACardComponent — componente reactivo de la card PTA
//
// Estrategia de snapshot:
//   El total inicial se congela con Object.freeze() en el
//   constructor, garantizando que nunca pueda aumentar aunque
//   el servidor devuelva un número mayor en futuras consultas.
//
// Anti-loops:
//   El setInterval solo actualiza el DOM si el elemento raíz
//   sigue montado (via `this.root.isConnected`). Al detectar
//   count === 0 se cancela el intervalo y se elimina el nodo.
// ══════════════════════════════════════════════════════════
class PTACardComponent {
    /**
     * @param {string}  otId       - Identificador de la OT (ej. "OT-001")
     * @param {object}  initialData - { totalPTA, terminadas, liberadas } del servidor
     * @param {Element} container  - Elemento contenedor donde se monta la card
     * @param {object}  classArray - classArray de la OT (para calcular piezas malas)
     */
    constructor(otId, classId, initialData, container, classArray) {
        // — Raw data del montaje inicial
        const rawTotal = initialData.totalPTA || 0;
        const totalJuegos = rawTotal > 0 ? Math.round(rawTotal / 2) : 0;

        this.snapshot = totalJuegos > 0 ? Object.freeze({ total: totalJuegos }) : null;
        this.otId = otId;
        this.classId = classId;
        this.uniqueId = `${otId}-${classId}`;
        this.container = container;
        this.classArray = classArray;
        this._pollTimer = null;
        this._busy = false;
        this._mounted = false;   // true cuando el DOM ya está creado
        this.root = null;

        // Estado mutable
        this.current = initialData.terminadas || 0;
        this.liberadas = initialData.liberadas || 0;
        this.rechazadas = initialData.rechazadas || 0;
        this.sinLiberar = initialData.sinLiberar || 0;

        if (totalJuegos > 0) {
            // Regla 1: hay piezas → montar inmediatamente y arrancar polling
            this._mount();
        }
        // Siempre arrancar el polling (modo dormido si no hay piezas todavía)
        this._startPolling();
    }


    // ── Montaje del DOM (puede llamarse en diferido) ──────────────
    _mount() {
        if (this._mounted) return;
        this.root = this._render();
        this.container.appendChild(this.root);
        this._mounted = true;
    }

    // ── Construye el DOM inicial ──────────────────────────────
    _render() {
        const section = document.createElement("div");
        section.className = "process-section";
        section.style.cursor = "pointer";
        section.id = `pta-card-${this.uniqueId}`;
        section.title = "Ver resultados de Soldadura PTA";

        const title = document.createElement("h3");
        title.className = "process-title";
        title.innerHTML = "Resultados Soldadura PTA";
        section.appendChild(title);

        // Etiqueta juegos terminados correctamente (solo texto, sin barra)
        const label1 = this._buildLabel("label-term");
        section.appendChild(label1);

        // Label + Barra — Sin liberar / mixto (AZUL)
        const bar2 = this._buildBar("bar-sin", "#dbeafe", "linear-gradient(to right, #90bff3, #043885)");
        section.appendChild(bar2);

        // Label + Barra — Liberados por admin (VERDE)
        const bar4 = this._buildBar("bar-lib", "#e1fcc6", "linear-gradient(to right, #9ff390, #0a8504)");
        section.appendChild(bar4);

        // Label + Barra apilada — Juegos Totales (azul = sin liberar | verde = liberados)
        const labelTot = this._buildLabel("label-tot");
        section.appendChild(labelTot);
        const barTot = this._buildStackedBar("bar-tot");
        section.appendChild(barTot);

        // Click: navegar a la vista de resultados
        // (se registra ANTES de _updateBars para que un posible error
        //  en la actualización de barras no bloquee la navegación)
        section.addEventListener("click", () => {
            const base = window.ptaResultsBaseUrl || (window.baseUrl + "/admin/pta/results");
            window.location.href = `${base}/${this.otId}?clase_id=${this.classId}`;
        });

        // Actualizar valores iniciales (skip connection check, aún no está en DOM)
        this._updateBars(section, true);

        return section;
    }

    // ── Construye un label encima de la barra ─────────────────
    _buildLabel(labelId) {
        const label = document.createElement("div");
        label.className = "pta-bar-label";
        label.id = `${labelId}-${this.uniqueId}`;
        label.classList.add("pip-label");
        return label;
    }

    // ── Construye una barra vacía con IDs predecibles ────────
    // bgColor: color de fondo del contenedor; fillGradient: gradiente del fill
    _buildBar(barId, bgColor = "rgba(3, 56, 97, 0.15)", fillGradient = "linear-gradient(to right, #416d90, #033861)") {
        const wrap = document.createElement("div");
        wrap.className = "progress-bar";
        wrap.style.backgroundColor = bgColor;
        wrap.id = `${barId}-${this.uniqueId}`;

        const fill = document.createElement("div");
        fill.className = `pta-bar-fill ${barId}-fill progress`;
        fill.style.background = fillGradient;
        fill.style.width = "0%";
        wrap.appendChild(fill);

        const info = document.createElement("div");
        info.className = "progress-percentage";
        wrap.appendChild(info);

        return wrap;
    }

    // ── Construye la barra apilada de Juegos Totales ──────────
    // Siempre al 100%. Segmento izquierdo (violeta) = liberados,
    // segmento derecho (naranja) = sin liberar. Conforme avanzan liberados
    // el violeta crece y el naranja se reduce.
    _buildStackedBar(barId) {
        const wrap = document.createElement("div");
        wrap.className = "progress-bar";
        wrap.style.backgroundColor = "#e4d9f7"; // fondo suave si nada liberado
        wrap.style.position = "relative";
        wrap.style.overflow = "hidden";
        wrap.id = `${barId}-${this.uniqueId}`;

        // Segmento VIOLETA: juegos liberados (crece desde la izquierda)
        const fillLib = document.createElement("div");
        fillLib.className = "pta-bar-fill progress";
        fillLib.id = `${barId}-fill-lib-${this.uniqueId}`;
        fillLib.style.cssText = "background: linear-gradient(to right, #c084fc, #7c3aed); width:0%; position:absolute; left:0; top:0; height:100%; transition: width .5s ease;";
        wrap.appendChild(fillLib);

        // Segmento NARANJA: juegos sin liberar (lo que queda a la derecha)
        const fillSin = document.createElement("div");
        fillSin.className = "pta-bar-fill progress";
        fillSin.id = `${barId}-fill-sin-${this.uniqueId}`;
        fillSin.style.cssText = "background: linear-gradient(to right, #ffb347, #e65c00); width:100%; position:absolute; right:0; top:0; height:100%; transition: width .5s ease;";
        wrap.appendChild(fillSin);

        const info = document.createElement("div");
        info.className = "progress-percentage";
        info.style.position = "relative";
        info.style.zIndex = "1";
        wrap.appendChild(info);

        return wrap;
    }

    // ── Actualiza solo los elementos internos (sin re-render) ─
    _updateBars(root, skipConnectionCheck) {
        root = root || this.root;
        if (!root) return;
        if (!skipConnectionCheck && !root.isConnected) return;

        const terminadas = this.current;

        // sinLiberar se calcula localmente: juegos terminados que aún no están liberados.
        // Esto evita el bug donde el servidor devuelve 0 porque auto-libera al guardar.
        const sinLiberar = Math.max(0, terminadas - this.liberadas - this.rechazadas);

        // Si no hay juegos terminados ni rechazados, marcar la tarjeta como inactiva
        if (terminadas === 0 && this.rechazadas === 0) {
            root.classList.add("inactive-process");
        } else {
            root.classList.remove("inactive-process");
        }

        // Borde verde si el proceso está concluido (100%)
        if (this.snapshot && this.snapshot.total > 0) {
            let ptaPercentage = (terminadas * 100) / this.snapshot.total;
            let hue = 30 + (Math.min(100, ptaPercentage) * 0.9);
            root.style.setProperty('--glow-hue', hue);

            // Tooltip descriptivo
            if (ptaPercentage === 0) {
                root.title = "Proceso aún no iniciado. Esperando primeras piezas.";
            } else if (ptaPercentage < 50) {
                root.title = `Progreso bajo (${ptaPercentage.toFixed(1)}%). Se requiere atención.`;
            } else if (ptaPercentage < 100) {
                root.title = `Progreso estable (${ptaPercentage.toFixed(1)}%). Buen ritmo de trabajo.`;
            } else {
                root.title = "¡Proceso completado exitosamente al 100%!";
            }

            if (ptaPercentage >= 100) {
                root.style.borderColor = '#4ade80';
                root.style.boxShadow = 'none';
            } else if (ptaPercentage > 0) {
                root.style.borderColor = `hsl(${hue}, 100%, 50%)`;
                root.style.boxShadow = 'none';
            } else {
                root.style.borderColor = '';
                root.style.boxShadow = '';
            }
        }

        // Etiqueta terminadas (texto informativo, sin barra)
        const labelT = root.querySelector(`#label-term-${this.uniqueId}`);
        if (labelT) labelT.textContent =
            `Terminadas: ${terminadas} juego${terminadas !== 1 ? 's' : ''} correctos`;

        // —— helper interno —————————————————————————————————
        const setBar = (barId, labelId, count, labelText, countText) => {
            const pct = terminadas > 0 ? Math.min(100, Math.round((count / terminadas) * 100)) : 0;
            const wrap = root.querySelector(`#${barId}-${this.uniqueId}`);
            if (wrap) {
                wrap.querySelector(".pta-bar-fill")?.style.setProperty("width", `${pct}%`);
                const info = wrap.querySelector(".progress-percentage");
                if (info) info.textContent = `${pct}% ${count}/${terminadas} ${countText}`;
            }
            const lbl = root.querySelector(`#${labelId}-${this.uniqueId}`);
            if (lbl) lbl.textContent = labelText;
        };

        // Barra sin liberar (AZUL) — calculado localmente
        setBar("bar-sin", "label-sin", sinLiberar,
            `Sin liberar: ${sinLiberar}/${terminadas}`,
            "juegos sin liberar"
        );

        // Barra liberados (VERDE)
        setBar("bar-lib", "label-lib", this.liberadas,
            `Liberados: ${this.liberadas}/${terminadas}`,
            "juegos liberados"
        );

        // ── Barra apilada: Juegos Totales (siempre 100% llena) ──────────
        // Violeta (liberados) crece desde la izquierda.
        // Naranja (sin liberar) ocupa el resto hacia la derecha.
        const pctLib = terminadas > 0 ? Math.min(100, Math.round((this.liberadas / terminadas) * 100)) : 0;
        const pctSin = 100 - pctLib; // naranja = lo que queda

        const barTot = root.querySelector(`#bar-tot-${this.uniqueId}`);
        const lblTot = root.querySelector(`#label-tot-${this.uniqueId}`);
        if (barTot) {
            const fLib = barTot.querySelector(`#bar-tot-fill-lib-${this.uniqueId}`);
            const fSin = barTot.querySelector(`#bar-tot-fill-sin-${this.uniqueId}`);
            if (fLib) fLib.style.width = `${pctLib}%`;
            if (fSin) fSin.style.width = `${pctSin}%`;
            const info = barTot.querySelector(".progress-percentage");
            if (info) info.textContent =
                `${this.liberadas} liberados + ${sinLiberar} pendientes = ${terminadas} total`;
        }
        if (lblTot) lblTot.textContent =
            `Juegos Totales: ${this.liberadas}/${terminadas} liberados`;
    }

    // ── Polling AJAX cada 10 s ───────────────────────────────
    _startPolling() {
        this._pollTimer = setInterval(() => this._poll(), 10_000);
    }

    async _poll() {
        // Guard: petición en vuelo
        if (this._busy) return;

        // Guard extra: si el contenedor original ya no está en el DOM, destruir el timer.
        if (this.container && !this.container.isConnected) {
            this._destroy();
            return;
        }

        // Guard extra: si el nodo ya fue montado, verificar que sigue conectado
        if (this._mounted && this.root && !this.root.isConnected) {
            this._destroy();
            return;
        }

        this._busy = true;

        try {
            const res = await fetch(`${window.baseUrl}/piecesInProgress/ptaCard/${this.otId}/${this.classId}`);
            if (!res.ok) return;

            const data = await res.json();
            if (data.error) return;
            const rawTotal = data.totalPTA || 0;
            const totalJuegos = rawTotal; // Trust the backend game count

            // ── Primera activación (modo dormido → con piezas) ────────────
            if (!this._mounted && totalJuegos > 0) {
                // Congelar el total en el primer tick con datos reales
                this.snapshot = Object.freeze({ total: totalJuegos });
                this.current = data.terminadas || 0;
                this.liberadas = data.liberadas || 0;
                this.rechazadas = data.rechazadas || 0;
                this.sinLiberar = data.sinLiberar || 0;
                this._mount();
                return;
            }


            if (!this._mounted) return; // aún sin piezas, seguir esperando

            // Regla 2: Actualizar el total si ha cambiado (ej. rechazos o nuevas piezas)
            const newTotalJuegos = data.totalPTA || 0;
            if (newTotalJuegos !== this.snapshot.total) {
                this.snapshot = Object.freeze({ total: newTotalJuegos });
            }

            const newTerminadas = data.terminadas || 0;
            const newLiberadas = data.liberadas || 0;
            const newRechazadas = data.rechazadas || 0;
            const newSinLiberar = data.sinLiberar || 0;

            // Detectar cambio real antes de tocar el DOM (evita re-renders innecesarios)
            if (newTerminadas === this.current && newLiberadas === this.liberadas && newRechazadas === this.rechazadas && newSinLiberar === this.sinLiberar) return;

            this.current = newTerminadas;
            this.liberadas = newLiberadas;
            this.rechazadas = newRechazadas;
            this.sinLiberar = newSinLiberar;

            // Regla 4: auto-desmontaje cuando el contador llega a 0
            if (this.current === 0) {
                this._destroy();
                return;
            }

            // Regla 3: actualizar barras de forma quirúrgica
            this._updateBars();
        } catch (_) {
            // Error de red — silencioso, el próximo tick reintenta
        } finally {
            this._busy = false;
        }
    }


    // ── Destrucción limpia ───────────────────────────────────
    _destroy() {
        clearInterval(this._pollTimer);
        this._pollTimer = null;
        if (this.root && this.root.isConnected) {
            this.root.remove();
        }
    }
}

// ══════════════════════════════════════════════════════════
// FundicionChecklistCard — Checklist reactivo del flujo de
// fundición (Modelo → Liberación → SCAR → Casting/Reproceso)
//
// Patrón idéntico a PTACardComponent:
//   - Carga inicial desde window.fundicionChecklist
//   - Polling AJAX cada 30s para refrescar sin reload
//   - Solo se instancia para perfiles 1 y 2 (guard en Dashboard)
//   - Auto-actualiza borde y colores según estado global
// ══════════════════════════════════════════════════════════
class FundicionChecklistCard {
    /**
     * @param {string}  otId        - Clave de la OT (ej. "6748" o "6748_R1")
     * @param {object}  initialData - { esReproceso: bool, pasos: { key: { label, estado } } }
     * @param {Element} container   - Div wrapper donde se monta la card
     * @param {string}  [instanceId] - Sufijo único para IDs del DOM (por defecto = otId).
     *                                  Usar el nombre de la clase cuando la OT tiene varias secciones.
     */
    constructor(otId, initialData, container, instanceId) {
        this.otId = otId;
        this.className = instanceId || '';
        this._instanceId = instanceId ? `${otId}-${instanceId}`.replace(/[^a-zA-Z0-9_-]/g, '_') : otId;
        this._data = initialData;
        this.container = container;
        this._pollTimer = null;
        this._mounted = false;
        this.root = null;

        this._mount();
        this._startPolling();
    }

    // ── Montar el DOM ─────────────────────────────────────
    _mount() {
        if (this._mounted) return;
        this.root = this._render();
        this.container.appendChild(this.root);
        this._mounted = true;
    }

    // ── Icono según estado ────────────────────────────────
    _getIconFor(estado) {
        const baseUrl = window.baseUrl || (window.location.origin + '/');
        const slash = baseUrl.endsWith('/') ? '' : '/';
        let imgName = '';
        switch (estado) {
            case 'Completado':
                imgName = 'Aprobado.png';
                break;
            case 'En Espera':
                imgName = 'Espera.png';
                break;
            case 'Revisando':
                imgName = 'Revisando.png';
                break;
            case 'Incompleto':
            default:
                imgName = 'Recibido.png';
                break;
        }
        return `${baseUrl}${slash}images/${imgName}`;
    }

    // ── Color del borde izquierdo según estado global ─────
    // Verde si todos completados, rojo si alguno rechazado, naranja si en progreso
    _getBorderColor(data) {
        const pasos = Object.values(data.pasos || {});
        if (pasos.some(p => (p.estado || '').toLowerCase() === 'rechazado')) return '#9D0402'; // corporate red
        if (pasos.length > 0 && pasos.every(p => {
            const st = (p.estado || '').toLowerCase();
            return st === 'completado' || st === 'proveedor' || st === 'aprobado' || st === 'aprobado_final' || st === 'no_aplica';
        })) return '#0C8201'; // corporate green
        return '#424141'; // pending - corporate gray
    }

    // ── Construir el DOM inicial ──────────────────────────
    _render() {
        const card = document.createElement('div');
        card.className = 'fundicion-checklist-card';
        card.id = `fundicion-checklist-${this._instanceId}`;

        // — Header —
        const header = document.createElement('div');
        header.className = 'checklist-header';

        const title = document.createElement('span');
        title.className = 'checklist-title';
        title.textContent = 'Levantamiento de OT';
        header.appendChild(title);

        const badge = document.createElement('span');
        badge.className = 'checklist-reproceso-badge';
        badge.id = `checklist-badge-${this._instanceId}`;
        badge.textContent = this._data.badgeText || 'Reproceso';
        badge.classList.toggle("hidden", !(this._data.isBadgeVisible));
        header.appendChild(badge);

        card.appendChild(header);

        // — Contenedor de pasos —
        const itemsContainer = document.createElement('div');
        itemsContainer.className = 'checklist-items';
        itemsContainer.id = `checklist-items-${this._instanceId}`;
        itemsContainer.hidden = true;
        card.appendChild(itemsContainer);

        card.classList.add('is-closed');

        // Toggle logic: make whole card clickable
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
            if (itemsContainer.hidden) {
                itemsContainer.hidden = false;
                card.classList.remove('is-closed');
            } else {
                itemsContainer.hidden = true;
                card.classList.add('is-closed');
            }
        });

        // Render inicial de estados
        this._updateCard(card, this._data);

        return card;
    }

    // ── Actualiza borde + badge + pasos (sin re-render completo) ─
    _updateCard(card, data) {
        if (!card) return;

        // Contorno verde si completado, rojo si rechazado, sin glow
        let colorHex = this._getBorderColor(data);
        card.classList.remove('card-state-completado', 'card-state-incompleto', 'card-state-rechazado');
        if (colorHex === '#9D0402') { // Red
            card.classList.add('card-state-rechazado');
        } else if (colorHex === '#0C8201') { // Green
            card.classList.add('card-state-completado');
        } else {
            card.classList.add('card-state-incompleto');
        }

        const titleSpan = card.querySelector('.checklist-title');
        if (titleSpan) {
            if (colorHex === '#0C8201') {
                titleSpan.style.color = '#4ade80';
                titleSpan.style.textShadow = '0 0 10px rgba(74, 222, 128, 0.5)';
            } else if (colorHex === '#9D0402') {
                titleSpan.style.color = '#f87171';
                titleSpan.style.textShadow = '0 0 10px rgba(248, 113, 113, 0.5)';
            } else {
                titleSpan.style.color = '';
                titleSpan.style.textShadow = '';
            }
        }

        // Badge de reproceso
        const safeId = (typeof CSS !== 'undefined' && CSS.escape) ? CSS.escape(this._instanceId) : this._instanceId;
        const badge = card.querySelector(`#checklist-badge-${safeId}`);
        if (badge) {
            badge.classList.toggle("hidden", !(data.isBadgeVisible));
            if (data.badgeText) {
                badge.textContent = data.badgeText;
            }
        }

        // Reconstruir lista de pasos
        const container = card.querySelector(`#checklist-items-${safeId}`);
        if (!container) return;
        container.innerHTML = '';

        // Solo mostrar los pasos que no estén inactivos (para que vayan apareciendo uno a uno)
        const pasosEntries = Object.entries(data.pasos || {}).filter(([key, paso]) => paso.estado !== 'Incompleto');

        pasosEntries.forEach(([key, paso], idx) => {
            if (!paso) return;

            const safeEstado = paso.estado.toLowerCase().replace(/\s+/g, '_');
            const item = document.createElement('div');
            item.className = `checklist-item checklist-item--${safeEstado}`;
            if (paso.tooltip) {
                item.title = paso.tooltip;
                item.style.cursor = 'help';
            }

            // 1. Área del Ícono (Izquierda)
            const iconCol = document.createElement('div');
            iconCol.className = 'checklist-icon-col';

            const iconSpan = document.createElement('span');
            iconSpan.className = 'checklist-icon';

            const img = document.createElement('img');
            img.src = this._getIconFor(paso.estado);
            img.alt = safeEstado;
            img.className = 'checklist-state-icon';
            iconSpan.appendChild(img);

            iconCol.appendChild(iconSpan);

            // Conector vertical
            if (idx < pasosEntries.length - 1) {
                const line = document.createElement('div');
                line.className = 'checklist-connector-line';
                iconCol.appendChild(line);
            }

            // 2. Área de Descripción (Centro)
            const contentCol = document.createElement('div');
            contentCol.className = 'checklist-content-col';

            const labelParts = paso.label.split(': ');

            const labelDesc = document.createElement('span');
            labelDesc.className = 'checklist-label-desc';
            labelDesc.textContent = labelParts.length > 1 ? labelParts[1] : paso.label;

            contentCol.appendChild(labelDesc);

            if (labelParts.length > 1) {
                const labelDept = document.createElement('span');
                labelDept.className = 'checklist-label-dept';
                labelDept.textContent = labelParts[0];
                contentCol.appendChild(labelDept);
            }

            // 3. Área de Acción / Status (Derecha)
            const actionCol = document.createElement('div');
            actionCol.className = 'checklist-action-col';

            const statusBadge = document.createElement('span');
            statusBadge.className = `checklist-status-badge badge--${safeEstado.replace(/_/g, '-')}`;
            statusBadge.textContent = paso.estado.toUpperCase();

            actionCol.appendChild(statusBadge);

            item.appendChild(iconCol);
            item.appendChild(contentCol);
            item.appendChild(actionCol);

            // Add sub-pasos rendering if they exist
            const activeSubPasos = (paso.subPasos || []).filter(sp => sp.estado !== 'Incompleto');
            if (activeSubPasos.length > 0) {
                const subPasosContainer = document.createElement('div');
                subPasosContainer.className = 'checklist-subpasos-container';
                subPasosContainer.hidden = true;

                activeSubPasos.forEach((subPaso, spIdx) => {
                    const spEstado = subPaso.estado.toLowerCase().replace(/\s+/g, '_');
                    const spItem = document.createElement('div');
                    spItem.className = `checklist-subpaso-item checklist-subpaso-item--${spEstado}`;

                    const spIconCol = document.createElement('div');
                    spIconCol.className = 'checklist-subpaso-icon-col';

                    const spIcon = document.createElement('img');
                    spIcon.src = this._getIconFor(subPaso.estado);
                    spIcon.className = 'checklist-subpaso-icon';
                    spIconCol.appendChild(spIcon);

                    if (spIdx < activeSubPasos.length - 1) {
                        const spLine = document.createElement('div');
                        spLine.className = 'checklist-subpaso-connector';
                        spIconCol.appendChild(spLine);
                    }

                    const spContentCol = document.createElement('div');
                    spContentCol.className = 'checklist-subpaso-content-col';

                    const spLabel = document.createElement('span');
                    spLabel.className = 'checklist-subpaso-label';
                    spLabel.textContent = subPaso.label;
                    spContentCol.appendChild(spLabel);

                    if (subPaso.detalle) {
                        const spDetalle = document.createElement('span');
                        spDetalle.className = 'checklist-subpaso-detalle';
                        spDetalle.textContent = subPaso.detalle;
                        spContentCol.appendChild(spDetalle);
                    }

                    const spBadgeCol = document.createElement('div');
                    spBadgeCol.className = 'checklist-subpaso-badge-col';

                    const spStatusBadge = document.createElement('span');
                    spStatusBadge.className = `checklist-subpaso-badge badge--${spEstado.replace(/_/g, '-')}`;
                    spStatusBadge.textContent = subPaso.estado.toUpperCase();
                    spBadgeCol.appendChild(spStatusBadge);

                    spItem.appendChild(spIconCol);
                    spItem.appendChild(spContentCol);
                    spItem.appendChild(spBadgeCol);

                    subPasosContainer.appendChild(spItem);
                });

                // Add expand indicator icon to main item
                const expandIcon = document.createElement('span');
                expandIcon.className = 'checklist-expand-icon';
                expandIcon.innerHTML = '▼';
                actionCol.appendChild(expandIcon);

                item.style.cursor = 'pointer';
                item.addEventListener('click', (e) => {
                    e.stopPropagation(); // Avoid triggering the main card toggle

                    // Close other expanded items
                    const allExpandedItems = container.querySelectorAll('.checklist-item.is-expanded');
                    allExpandedItems.forEach(expandedItem => {
                        if (expandedItem !== item) {
                            expandedItem.classList.remove('is-expanded');
                            const relatedContainer = expandedItem.nextElementSibling;
                            if (relatedContainer && relatedContainer.classList.contains('checklist-subpasos-container')) {
                                relatedContainer.hidden = true;
                            }
                            const relatedIcon = expandedItem.querySelector('.checklist-expand-icon');
                            if (relatedIcon) {
                                relatedIcon.innerHTML = '▼';
                            }
                        }
                    });

                    if (subPasosContainer.classList.contains("hidden")) {
                        subPasosContainer.hidden = false;
                        item.classList.add('is-expanded');
                        expandIcon.innerHTML = '▲';
                    } else {
                        subPasosContainer.hidden = true;
                        item.classList.remove('is-expanded');
                        expandIcon.innerHTML = '▼';
                    }
                });

                container.appendChild(item);
                container.appendChild(subPasosContainer);
            } else {
                container.appendChild(item);
            }
        });
    }

    // ── Polling AJAX cada 30s ──────────────────────────────
    _startPolling() {
        // 30_000ms = 30 segundos; suficiente para un checklist de bajo cambio
        this._pollTimer = setInterval(() => this._poll(), 30_000);
    }

    async _poll() {
        // Guard: si el nodo fue desmontado, cancelar polling
        if (!this.root || !this.root.isConnected) {
            this._destroy();
            return;
        }
        try {
            const url = this.className ? `${window.fundicionChecklistUrl}/${this.otId}/${this.className}` : `${window.fundicionChecklistUrl}/${this.otId}`;
            const res = await fetch(url);
            if (!res.ok) return; // 404 si la OT dejó de tener flujo — se deja morir en siguiente tick

            const data = await res.json();
            if (data && !data.error) {
                this._data = data;
                this._updateCard(this.root, data);
            }
        } catch (_) {
            // Error de red — silencioso, el próximo tick reintenta
        }
    }

    // ── Destrucción limpia ─────────────────────────────────
    _destroy() {
        clearInterval(this._pollTimer);
        this._pollTimer = null;
        if (this.root && this.root.isConnected) {
            this.root.remove();
        }
    }
}

// ══════════════════════════════════════════════════════════
// PlaneacionChecklistCard — Checklist reactivo de Planeación
// por clase (Dibujos Maquinado, Cotas, Proceso).
// ══════════════════════════════════════════════════════════
class PlaneacionChecklistCard {
    constructor(otId, claseId, container) {
        this.otId = otId;
        this.claseId = claseId;
        this._safeClaseId = String(claseId).replace(/[^a-zA-Z0-9_-]/g, '_');
        this.container = container;
        this._pollTimer = null;
        this._mounted = false;
        this.root = null;

        this._mount();
        this._startPolling();
    }

    _mount() {
        if (this._mounted) return;
        this.root = this._render();
        this.container.appendChild(this.root);
        this._mounted = true;
    }

    _getIconFor(estado, label = '') {
        const baseUrl = window.baseUrl || (window.location.origin + '/');
        const slash = baseUrl.endsWith('/') ? '' : '/';
        let imgName = '';

        if (estado === 'completado') {
            imgName = 'Aprobado.png';
        } else if (estado === 'no_aplica') {
            imgName = 'icono_sin_cotas.png';
        } else {
            if (label.includes('Cotas de OT/Clase subidas')) {
                imgName = 'icono_cotas.png';
            } else if (label.includes('Dibujos de maquinados subidos')) {
                imgName = 'DrawsProduction.png';
            } else {
                switch (estado) {
                    case 'pendiente': imgName = 'Espera.png'; break;
                    case 'rechazado': imgName = 'Rechazado.png'; break;
                    case 'inactivo':
                    default: imgName = 'Recibido.png'; break;
                }
            }
        }
        return `${baseUrl}${slash}images/${imgName}`;
    }

    _getBorderColor(pasos) {
        if (!pasos || Object.keys(pasos).length === 0) return '#424141';
        const vals = Object.values(pasos);
        if (vals.some(p => (p.estado || '').toLowerCase() === 'rechazado')) return '#9D0402';
        if (vals.every(p => {
            const st = (p.estado || '').toLowerCase();
            return st === 'completado' || st === 'no_aplica';
        })) return '#0C8201';
        return '#424141';
    }

    _render() {
        const card = document.createElement('div');
        card.className = 'fundicion-checklist-card';
        card.id = `planeacion-checklist-${this.otId}-${this._safeClaseId}`;

        // Contenedores base
        card.innerHTML = `
            <div class="checklist-header">
                <span class="checklist-title">Planeación</span>
            </div>
            <div class="checklist-items" id="planeacion-items-${this.otId}-${this._safeClaseId}" style="padding-top: 5px;">
                <div class="checklist-item checklist-item--pendiente" title="Pendiente">
                    <div class="checklist-icon-col">
                        <span class="checklist-icon"><img src="${this._getIconFor('pendiente')}" alt="pendiente" class="checklist-state-icon"></span>
                        <div class="checklist-connector-line"></div>
                    </div>
                    <div class="checklist-content-col">
                        <span class="checklist-label">Procesos de Producción</span>
                    </div>
                    <div class="checklist-action-col">
                        <span class="checklist-status-badge badge--pendiente">PENDIENTE</span>
                    </div>
                </div>
                <div class="checklist-item checklist-item--inactivo" title="Inactivo">
                    <div class="checklist-icon-col">
                        <span class="checklist-icon"><img src="${this._getIconFor('inactivo', 'Dibujos de maquinados subidos')}" alt="inactivo" class="checklist-state-icon"></span>
                        <div class="checklist-connector-line"></div>
                    </div>
                    <div class="checklist-content-col">
                        <span class="checklist-label">Dibujos de maquinados subidos</span>
                    </div>
                    <div class="checklist-action-col">
                        <span class="checklist-status-badge badge--inactivo">INACTIVO</span>
                    </div>
                </div>
                <div class="checklist-item checklist-item--inactivo" title="Inactivo">
                    <div class="checklist-icon-col">
                        <span class="checklist-icon"><img src="${this._getIconFor('inactivo', 'Cotas de OT/Clase subidas')}" alt="inactivo" class="checklist-state-icon"></span>
                    </div>
                    <div class="checklist-content-col">
                        <span class="checklist-label">Cotas de OT/Clase subidas (Admin)</span>
                    </div>
                    <div class="checklist-action-col">
                        <span class="checklist-status-badge badge--inactivo">INACTIVO</span>
                    </div>
                </div>
            </div>
        `;

        const itemsContainer = card.querySelector('.checklist-items');
        // Ocultar mediante atributo HTML (no clase CSS) para que el toggle sea consistente
        if (itemsContainer) itemsContainer.hidden = true;

        card.classList.add('is-closed');
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
            if (itemsContainer.hidden) {
                itemsContainer.hidden = false;
                card.classList.remove('is-closed');
            } else {
                itemsContainer.hidden = true;
                card.classList.add('is-closed');
            }
        });

        return card;
    }

    _updateCard(data) {
        if (!this.root) return;
        const container = this.root.querySelector(`#planeacion-items-${this.otId}-${this._safeClaseId}`);
        if (!container) return;

        const pasosData = data[this.claseId];

        if (!pasosData) {
            container.innerHTML = `<div style="font-size: 13px; color: #94a3b8; text-align: center; padding: 10px;">Sin datos para esta clase</div>`;
            return;
        }

        let colorHex = this._getBorderColor(pasosData);
        this.root.classList.remove('card-state-completado', 'card-state-incompleto', 'card-state-rechazado');
        if (colorHex === '#9D0402') {
            this.root.style.borderColor = '';
            this.root.classList.add('card-state-rechazado');
        } else if (colorHex === '#0C8201') {
            this.root.style.borderColor = '';
            this.root.classList.add('card-state-completado');
        } else {
            this.root.style.borderColor = '';
            this.root.classList.add('card-state-incompleto');
        }

        const titleSpan = this.root.querySelector('.checklist-title');
        if (titleSpan) {
            if (colorHex === '#0C8201') {
                titleSpan.style.color = '#4ade80';
                titleSpan.style.textShadow = '0 0 10px rgba(74, 222, 128, 0.5)';
            } else if (colorHex === '#9D0402') {
                titleSpan.style.color = '#f87171';
                titleSpan.style.textShadow = '0 0 10px rgba(248, 113, 113, 0.5)';
            } else {
                titleSpan.style.color = '';
                titleSpan.style.textShadow = '';
            }
        }

        container.innerHTML = '';
        // Solo mostrar los pasos que no estén inactivos
        const pasosEntries = Object.entries(pasosData).filter(([key, paso]) => paso.estado !== 'Incompleto');

        pasosEntries.forEach(([key, paso], idx) => {
            if (!paso) return;

            const safeEstado = paso.estado.toLowerCase().replace(/\s+/g, '_');
            const item = document.createElement('div');
            item.className = `checklist-item checklist-item--${safeEstado}`;

            // 1. Área del Ícono (Izquierda)
            const iconCol = document.createElement('div');
            iconCol.className = 'checklist-icon-col';

            const iconSpan = document.createElement('span');
            iconSpan.className = 'checklist-icon';

            const img = document.createElement('img');
            img.src = this._getIconFor(paso.estado, paso.label);
            img.alt = safeEstado;
            img.className = 'checklist-state-icon';
            iconSpan.appendChild(img);

            iconCol.appendChild(iconSpan);

            // Conector vertical
            if (idx < pasosEntries.length - 1) {
                const line = document.createElement('div');
                line.className = 'checklist-connector-line';
                iconCol.appendChild(line);
            }

            // 2. Área de Descripción (Centro)
            const contentCol = document.createElement('div');
            contentCol.className = 'checklist-content-col';

            const label = document.createElement('span');
            label.className = 'checklist-label';
            label.textContent = paso.label;

            contentCol.appendChild(label);

            // 3. Área de Acción / Status (Derecha)
            const actionCol = document.createElement('div');
            actionCol.className = 'checklist-action-col';

            const actionBadge = document.createElement('span');
            actionBadge.className = `checklist-status-badge badge--${safeEstado.replace(/_/g, '-')}`;
            actionBadge.textContent = paso.estado.toUpperCase();

            actionCol.appendChild(actionBadge);

            item.appendChild(iconCol);
            item.appendChild(contentCol);
            item.appendChild(actionCol);
            container.appendChild(item);
        });
    }

    _startPolling() {
        this._poll();
        this._pollTimer = setInterval(() => this._poll(), 5_000);
    }

    async _poll() {
        if (!this.root || !this.root.isConnected) {
            this._destroy();
            return;
        }
        try {
            const res = await fetch(`${window.planeacionChecklistUrl}/${this.otId}`);
            if (!res.ok) return;
            const data = await res.json();
            if (data && !data.error) {
                this._updateCard(data);
            }
        } catch (_) { }
    }

    _destroy() {
        clearInterval(this._pollTimer);
        this._pollTimer = null;
        if (this.root && this.root.isConnected) {
            this.root.remove();
        }
    }
}

class TermicoChecklistCard {
    constructor(otId, className, initialData, container) {
        this.otId = otId;
        this.className = className;
        this.container = container;
        this.tPieces = initialData.pieces || 0;
        this.tTratadas = initialData.tratadas || 0;
        this._build();
        this._poll();
        this._pollTimer = setInterval(() => this._poll(), 30_000);
    }

    _build() {
        this.root = document.createElement('div');
        this.root.style.flex = '0.7 1 0%';
        this.root.style.minWidth = '250px';
        this.root.style.cursor = 'pointer';

        this._renderContent();

        this.container.appendChild(this.root);

        this.root.addEventListener('click', () => {
            const tItems = this.root.querySelector('.checklist-items');
            if (tItems) {
                if (tItems.hidden) {
                    tItems.hidden = false;
                    this.root.classList.remove('is-closed');
                } else {
                    tItems.hidden = true;
                    this.root.classList.add('is-closed');
                }
            }
        });
    }

    _renderContent() {
        const isTermicoComplete = (this.tPieces > 0 && this.tTratadas >= this.tPieces);

        const tItems = this.root.querySelector('.checklist-items');
        const isHidden = tItems ? tItems.hidden : true;
        const hiddenClass = isHidden ? 'hidden' : '';
        const isClosedClass = tItems ? (this.root.classList.contains('is-closed') ? 'is-closed' : '') : 'is-closed';

        this.root.className = isTermicoComplete
            ? `fundicion-checklist-card card-state-completado ${isClosedClass}`
            : `fundicion-checklist-card card-state-incompleto ${isClosedClass}`;

        const baseUrl = window.baseUrl || (window.location.origin + '/');
        const slash = baseUrl.endsWith('/') ? '' : '/';
        const iconUrl = isTermicoComplete ? `${baseUrl}${slash}images/Aprobado.png` : `${baseUrl}${slash}images/tratamiento_termico.png`;
        const badgeClass = isTermicoComplete ? 'badge--completado' : 'badge--pendiente';
        const badgeText = isTermicoComplete ? 'COMPLETADO' : 'PENDIENTE';
        const iconStateClass = isTermicoComplete ? 'checklist-item--completado' : 'checklist-item--pendiente';

        this.root.innerHTML = `
            <div class="checklist-header">
                <span class="checklist-title">Tratamiento Térmico</span>
            </div>
            <div class="checklist-items" style="padding-top: 15px;">
                <div class="checklist-item ${iconStateClass}" title="Piezas en tratamiento térmico" style="cursor: help;">
                    <div class="checklist-icon-col">
                        <span class="checklist-icon">
                            <img src="${iconUrl}" alt="${badgeText.toLowerCase()}" class="checklist-state-icon">
                        </span>
                    </div>
                    <div class="checklist-content-col">
                        <span class="checklist-label">Piezas en tratamiento: ${this.tTratadas} / ${this.tPieces}</span>
                    </div>
                    <div class="checklist-action-col">
                        <span class="checklist-status-badge ${badgeClass}">${badgeText}</span>
                    </div>
                </div>
            </div>
        `;

        // Restaurar el estado de visibilidad usando el atributo HTML (no la clase CSS)
        // para que el listener de click (que usa tItems.hidden) funcione correctamente.
        const newItems = this.root.querySelector('.checklist-items');
        if (newItems) newItems.hidden = isHidden;
    }

    async _poll() {
        if (!this.root || !this.root.isConnected) {
            this._destroy();
            return;
        }
        try {
            const res = await fetch(`${window.termicoChecklistUrl}/${this.otId}`);
            if (!res.ok) return;
            const data = await res.json();
            if (data && !data.error && data[this.className]) {
                const newData = data[this.className];
                if (this.tTratadas !== newData.tratadas || this.tPieces !== newData.pieces) {
                    this.tTratadas = newData.tratadas;
                    this.tPieces = newData.pieces;
                    this._renderContent();
                }
            }
        } catch (_) { }
    }

    _destroy() {
        clearInterval(this._pollTimer);
        this._pollTimer = null;
        if (this.root && this.root.isConnected) {
            this.root.remove();
        }
    }
}

class Dashboard {
    constructor(wOrderArray) {
        this.wOrderArray = wOrderArray;
    }
    //Función para general carrusel
    generateSection($workOrder, $class, $processes) {
        let divHeader = document.createElement("div");
        divHeader.className = "header";
        let workOrderDiv = document.createElement("div");
        workOrderDiv.className = "work-order";
        let h2 = document.createElement("h2");
        h2.className = "work-order-title text-header";
        h2.innerHTML = "Orden de trabajo: " + $workOrder;
        let moldingLabel = document.createElement("label");
        moldingLabel.className = "molding-label text-header";
        label.innerHTML = "Moldura: " + $workOrder["moldura"];
        let classLabel = document.createElement("label");
        classLabel.className = "class-label text-header";
        classLabel.innerHTML = "Clase: " + $class;

        //Insertar los elementos en el div
        workOrderDiv.appendChild(h2);
        workOrderDiv.appendChild(moldingLabel);
        workOrderDiv.appendChild(classLabel);
        divHeader.appendChild(workOrderDiv);
        section.appendChild(divHeader);
        return section;
    }
    createSections() {
        let body = document.querySelector("body");
        let otIds = window.orderedOtIds && window.orderedOtIds.length > 0
            ? window.orderedOtIds
            : Object.keys(this.wOrderArray);

        otIds.forEach((wOrderName) => {
            let workOrder = this.wOrderArray[wOrderName];
            if (!workOrder) return;

            Object.values(workOrder["classes"]).forEach((classArray, indexClass) => {
                let section = document.createElement("section");
                section.className = "section";
                let className = Object.keys(workOrder["classes"])[indexClass];
                let headerSection = this.generateHeaderofWorkOrder(wOrderName, workOrder["molding"], className, classArray);
                let processesSection = document.createElement("div");
                processesSection.className = "processes-section";

                Object.values(classArray["processes"]).forEach((processesArray, indexProcess) => {
                    let processName = Object.keys(classArray["processes"])[indexProcess]

                    // ── Cálculo de piezas disponibles (limitPieces) ──────────────────────────
                    // Se toma la consignación original y se le descuentan TODAS las bajas
                    // acumuladas de los procesos ANTERIORES a este.
                    // Esto garantiza que si en cepillado hay 1 baja de 128,
                    // el siguiente proceso tiene 127 disponibles (no las que ya se procesaron).
                    let accumulatedBad = 0;
                    for (let p = 0; p < indexProcess; p++) {
                        let prevProcKey = Object.keys(classArray["processes"])[p];
                        accumulatedBad += (classArray["processes"][prevProcKey]["pieces"]["bad"] || 0);
                    }
                    let limitPieces = classArray["pieces"] - accumulatedBad;

                    processesSection.appendChild(this.generateProcessSection(processesArray, processName, limitPieces, classArray["pieces"], indexProcess + 1));
                });

                // ── Card PTA: instanciar SIEMPRE si la clase tiene soldaduraPTA activo ─
                // Se usa el flag `hasPTA` enviado por el servidor (campo soldaduraPTA != 0
                // en la tabla de procesos), que es la fuente de verdad más confiable.
                // Si aún no hay piezas (ptaData=null), el componente arrancará en modo
                // dormido y se montará solo en cuanto el polling detecte la primera pieza.
                if (classArray["hasPTA"] === true) {
                    let userProfile = document.getElementById("profile");
                    // Perfiles permitidos: 1=Master, 2=Operador, 3=Admin, 4=Calidad, 5=Almacén
                    if (userProfile && (userProfile.value == "1" || userProfile.value == "2" || userProfile.value == "3" || userProfile.value == "4" || userProfile.value == "5")) {
                        const classId = classArray["id"];
                        const ptaData = (window.ptaCardsData && window.ptaCardsData[wOrderName] && window.ptaCardsData[wOrderName][classId])
                            ? window.ptaCardsData[wOrderName][classId]
                            : { totalPTA: 0, liberadas: 0 };
                        this.generatePTASection(ptaData, wOrderName, classId, classArray, processesSection);
                    }
                }

                section.appendChild(headerSection);

                // ── Checklist de Fundición (entre header y procesos) ──────────
                // Guard de perfil: 1=Master, 2=Admin, 4=Calidad, 5=Almacén.
                let userProfileChecklist = document.getElementById("profile");
                // Perfiles permitidos: 1=Master, 2=Admin, 4=Calidad, 5=Almacén
                if (
                    userProfileChecklist &&
                    (userProfileChecklist.value === "1" || userProfileChecklist.value === "2" || userProfileChecklist.value === "3" ||
                        userProfileChecklist.value === "4" || userProfileChecklist.value === "5")
                ) {
                    section.classList.add('section--has-checklist');
                    const checklistWrapper = document.createElement('div');
                    checklistWrapper.className = 'fundicion-checklist-wrapper';
                    checklistWrapper.hidden = false;
                    checklistWrapper.style.gap = '20px';
                    checklistWrapper.style.flexWrap = 'nowrap';
                    checklistWrapper.style.alignItems = 'flex-start';

                    if (window.fundicionChecklist && window.fundicionChecklist[wOrderName] && window.fundicionChecklist[wOrderName][className]) {
                        const levantamientoCard = new FundicionChecklistCard(
                            wOrderName,
                            window.fundicionChecklist[wOrderName][className],
                            checklistWrapper,
                            className   // instanceId único por sección de clase
                        );
                        if (levantamientoCard.root) {
                            levantamientoCard.root.style.flex = '1';
                            levantamientoCard.root.style.minWidth = '300px';
                        }
                    } else {
                        // Render empty state card
                        const emptyCard = document.createElement('div');
                        emptyCard.className = 'fundicion-checklist-card empty-checklist-card inactive-process';
                        emptyCard.style.borderLeftColor = '#424141';
                        emptyCard.style.flex = '1';
                        emptyCard.style.minWidth = '300px';
                        emptyCard.style.pointerEvents = 'none';
                        emptyCard.innerHTML = `
                                <div class="checklist-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0; justify-content: center;">
                                    <span class="checklist-title" style="color: #ffffff;">
                                        Levantamiento de OT No Disponible
                                    </span>
                                </div>
                                <div class="empty-checklist-text" style="font-size: 0.95rem; color: rgba(255, 255, 255, 0.7); margin-top: 0.8rem; text-align: center;">
                                    Esta orden de trabajo es antigua o no requiere el levantamiento de OT.
                                </div>
                            `;
                        checklistWrapper.appendChild(emptyCard);
                    }

                    // Card 2: Tratamiento Térmico (Reactiva)
                    const termicoCard = new TermicoChecklistCard(
                        wOrderName,
                        className,
                        { pieces: classArray["pieces"] || 0, tratadas: classArray["tratadas"] || 0 },
                        checklistWrapper
                    );

                    // Card 3: Planeación (Reactiva)
                    const planeacionCard = new PlaneacionChecklistCard(
                        wOrderName,
                        classArray["id"],
                        checklistWrapper
                    );
                    if (planeacionCard.root) {
                        planeacionCard.root.style.flex = '1';
                        planeacionCard.root.style.minWidth = '300px';
                    }

                    // ── [FIN] DUMMY CARDS Y CARDS REACTIVAS ──

                    section.appendChild(checklistWrapper);
                }
                // ─────────────────────────────────────────────────────────────

                section.appendChild(processesSection);
                body.appendChild(section);
            });
        });
    }
    generateHeaderofWorkOrder(wOrderName, moldingName, className, classArray) {
        let valueText = [
            [
                `${wOrderName} ${moldingName}`,
                `${className}`,
                `<span class="header-label">Fecha de inicio:</span> <span class="header-value highlight-date">${classArray["startDate"]}</span>`,
                `<span class="header-label">Fecha de término:</span> <span class="header-value highlight-date">${classArray["endDate"]}</span>`,
            ],
            [
                `<span class="header-label" style="display: block; font-size: 0.85em; color: #94a3b8; margin-bottom: 5px; letter-spacing: 0.5px;">Pedido:</span> <span class="header-value highlight-value" style="display: block; font-size: 1.4em; font-weight: bold; text-align: center;">${classArray["order"]}</span>`,
                `<span class="header-label" style="display: block; font-size: 0.85em; color: #94a3b8; margin-bottom: 5px; letter-spacing: 0.5px;">Pedido + consignación:</span> <span class="header-value highlight-value" style="display: block; font-size: 1.4em; font-weight: bold; text-align: center;">${classArray["pieces"]}</span>`,
                `<span class="header-label" style="display: block; font-size: 0.85em; color: #94a3b8; margin-bottom: 5px; letter-spacing: 0.5px;">Piezas entregadas:</span> <span class="header-value highlight-value" style="display: block; font-size: 1.4em; font-weight: bold; color: #10b981; text-align: center;">${classArray["entregadas"] || 0}</span>`,
                `<span class="header-label" style="display: block; font-size: 0.85em; color: #94a3b8; margin-bottom: 5px; letter-spacing: 0.5px;">Piezas completadas:</span> <span class="header-value completed-value" style="display: block; font-size: 1.4em; font-weight: bold; text-align: center;">${this.getCompletedPieces(classArray)}</span>`,
            ],
        ];
        let classText = [
            ["workOrder-text", "class-text", "start-date-text", "end-date-text"],
            ["order-text", "pieces-text", "delivered-pieces-text", "completed-pieces-text"],
        ];

        let header_section = document.createElement("div");
        header_section.className = "header-section";

        let a = document.createElement("a");
        let baseUrl = window.baseUrl ? window.baseUrl : '';
        a.href = `${baseUrl}/finishOrder/${wOrderName}/${className}`;
        a.className = "finish-order";
        a.innerHTML = "Finalizar pedido";

        a.addEventListener("click", async (e) => {
            e.preventDefault();
            if (a.dataset.loading === "true") return;

            a.dataset.loading = "true";
            let originalText = a.innerHTML;
            a.innerHTML = "Procesando...";
            a.style.opacity = "0.7";
            a.classList.add("pointer-events-none");

            try {
                let response = await fetch(a.href, {
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest"
                    }
                });

                let data = await response.json();

                // Muestra modal dinámico
                mostrarModalFinalizarPedido(data[0], data[1], data[0] === "success");

            } catch (err) {
                console.error("Error al finalizar pedido, intentando redirigir...", err);
                window.location.href = a.href;
            } finally {
                a.innerHTML = originalText;
                a.style.opacity = "1";
                a.classList.remove("pointer-events-none");
                delete a.dataset.loading;
            }
        });

        for (let i = 0; i < valueText.length; i++) {
            let div = document.createElement("div");

            if (i === 1) {
                // Fila 2: Renderizarla como un div completo con clase "title-div"
                div.className = "title-div";
                div.classList.add("pip-card-div-1");

                // Título
                let titleDiv = document.createElement("div");
                titleDiv.classList.add("pip-card-title-1");
                titleDiv.style.width = "100%";

                let titleSpan = document.createElement("span");
                titleSpan.style.color = "#fff";
                titleSpan.style.fontSize = "1.25rem";
                titleSpan.style.fontWeight = "800";
                titleSpan.hidden = false;
                titleSpan.style.textTransform = "uppercase";
                titleSpan.style.letterSpacing = "0.03em";
                titleSpan.textContent = "Recepción de Material";
                titleDiv.appendChild(titleSpan);
                div.appendChild(titleDiv);

                // Contenedor interno para los valores (abajo, distribuidos horizontalmente)
                let itemsContainer = document.createElement("div");
                itemsContainer.hidden = false;
                itemsContainer.classList.add("pip-items-container");
                itemsContainer.style.width = "100%";

                for (let j = 0; j < valueText[i].length; j++) {
                    let h3 = document.createElement("h3");
                    h3.className = classText[i][j];
                    h3.style.margin = "0";
                    h3.hidden = false;
                    h3.style.flexDirection = "column";
                    h3.style.alignItems = "center";
                    h3.style.justifyContent = "center";
                    h3.innerHTML = valueText[i][j];
                    itemsContainer.appendChild(h3);
                }
                div.appendChild(itemsContainer);
            } else {
                // Fila 1 (Izquierda): Datos de la OT y Clase alineados en una sola fila con cabecera principal
                div.className = "title-div";
                div.classList.add("pip-card-div-2"); // Permite posicionamiento absoluto del icono de prioridad

                // Inyectar icono de prioridad según orden de OTs
                let rawOtIdForPriority = parseInt(wOrderName, 10);
                let otPriority = -1;
                if (window.orderedOtIds) {
                    otPriority = window.orderedOtIds.map(x => parseInt(x, 10)).indexOf(rawOtIdForPriority) + 1;
                }
                if (otPriority > 0) {
                    let pBadge = document.createElement("div");
                    pBadge.style.position = "absolute";
                    pBadge.style.top = "6px";
                    pBadge.style.left = "8px";
                    pBadge.hidden = false;
                    pBadge.style.alignItems = "center";
                    pBadge.style.gap = "8px";
                    pBadge.style.zIndex = "5";

                    // Gradual visual decay: starts after priority 5
                    let badgeOpacity = 1.0;
                    let badgeScale = 1.0;
                    if (otPriority > 5) {
                        badgeOpacity = Math.max(0.45, 1.0 - (otPriority - 5) * 0.05);
                        badgeScale = Math.max(0.75, 1.0 - (otPriority - 5) * 0.025);
                    }
                    pBadge.style.opacity = badgeOpacity;
                    pBadge.style.transform = `scale(${badgeScale})`;
                    pBadge.style.transformOrigin = "left center";

                    let priorityImgPath = "";
                    let isPlataPriority = false;
                    if (otPriority === 1) priorityImgPath = `${window.baseUrl}/images/uno.png`;
                    else if (otPriority === 2) priorityImgPath = `${window.baseUrl}/images/dos.png`;
                    else if (otPriority === 3) priorityImgPath = `${window.baseUrl}/images/tres.png`;
                    else if (otPriority === 4) priorityImgPath = `${window.baseUrl}/images/cuatro.png`;
                    else if (otPriority === 5) priorityImgPath = `${window.baseUrl}/images/cinco.png`;
                    else {
                        priorityImgPath = `${window.baseUrl}/images/plata.png`;
                        isPlataPriority = true;
                    }

                    // Neon glow effect (gold for 1-5, silver for 6+)
                    let glowFilter = isPlataPriority
                        ? "drop-shadow(0 0 10px rgba(203, 213, 225, 0.85)) drop-shadow(0 1px 3px rgba(0,0,0,0.35))"
                        : "drop-shadow(0 0 10px rgba(251, 191, 36, 0.85)) drop-shadow(0 1px 3px rgba(0,0,0,0.35))";

                    pBadge.innerHTML = `
                        <span style="color: #ffffff; font-weight: 900; font-size: 1.7rem; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.9);">${otPriority}</span>
                        <div style="position: relative; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center;">
                            <img src="${priorityImgPath}" alt="${otPriority}" style="width: 100%; height: 100%; object-fit: contain; filter: ${glowFilter};">
                        </div>
                    `;
                    div.appendChild(pBadge);
                }

                // Cabecera principal: Información de la OT
                let titleDiv = document.createElement("div");
                titleDiv.style.borderBottom = "none";
                titleDiv.style.paddingBottom = "0.6rem";
                titleDiv.style.textAlign = "center";
                titleDiv.style.width = "100%";
                titleDiv.style.marginBottom = "0.2rem";

                let titleSpan = document.createElement("span");
                titleSpan.style.color = "#fff";
                titleSpan.style.fontSize = "1.25rem";
                titleSpan.style.fontWeight = "800";
                titleSpan.hidden = false;
                titleSpan.style.textTransform = "uppercase";
                titleSpan.style.letterSpacing = "0.03em";
                titleSpan.textContent = "Información de la OT";
                titleDiv.appendChild(titleSpan);
                div.appendChild(titleDiv);

                // Fila de datos: OT (izquierda) y Clase (derecha) - sin líneas de separación
                let otClassRow = document.createElement("div");
                otClassRow.hidden = false;
                otClassRow.style.flexDirection = "row";
                otClassRow.style.justifyContent = "space-between";
                otClassRow.style.alignItems = "center";
                otClassRow.style.width = "100%";
                otClassRow.style.marginBottom = "0.4rem";

                // OT text
                let otH3 = document.createElement("h3");
                otH3.className = "workOrder-text";
                otH3.classList.add("pip-ot-h3");
                otH3.innerHTML = `<span style="font-size: 0.75em; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-right: 5px; letter-spacing: 0.5px;">OT:</span>${wOrderName} ${moldingName}`;

                // Clase text
                let classH3 = document.createElement("h3");
                classH3.className = "class-text";
                classH3.classList.add("pip-class-h3");
                classH3.innerHTML = `<span style="font-size: 0.75em; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-right: 5px; letter-spacing: 0.5px;">Clase:</span>${className}`;

                otClassRow.appendChild(otH3);
                otClassRow.appendChild(classH3);
                div.appendChild(otClassRow);

                // Fechas (Start Date and End Date) - las fechas sí llevan línea de separación (Start Date tiene línea, End Date no en el CSS)
                for (let j = 2; j < valueText[i].length; j++) {
                    let h3 = document.createElement("h3");
                    h3.className = classText[i][j];
                    h3.style.margin = "0";
                    h3.innerHTML = valueText[i][j];
                    div.appendChild(h3);
                }
            }

            header_section.appendChild(div);

            if (i === 0) {
                header_section.appendChild(a);
            }
        }

        return header_section;
    }

    getCompletedPieces(classArray) {
        //Obtener las piezas del ultimo proceso de la clase
        if (!classArray["processes"] || Object.keys(classArray["processes"]).length === 0) {
            return 0;
        }
        let completedPieces;
        let lastProcess = Object.keys(classArray["processes"])[Object.keys(classArray["processes"]).length - 1];
        if (lastProcess == "Soldadura PTA" || lastProcess == "Soldadura") {
            // Si ultimo proceso es Soldadura o Soldadura PTA
            let otherProcess = lastProcess == "Soldadura PTA" ? "Soldadura" : "Soldadura PTA";
            // Si se incluyen los dos procesos en la clase, sumar las piezas buenas
            if (Object.keys(classArray["processes"]).includes(otherProcess)) {
                completedPieces =
                    classArray["processes"][lastProcess]["pieces"]["good"] +
                    classArray["processes"][otherProcess]["pieces"]["good"];
            } else {
                completedPieces = classArray["processes"][lastProcess]["pieces"]["good"];
            }
        } else {
            completedPieces = Object.values(classArray["processes"])[Object.keys(classArray["processes"]).length - 1][
                "pieces"
            ]["good"];
        }
        return completedPieces;
    }
    generateProcessSection(processesArray, processName, limitPieces, pedido, processNumber) {
        let processSection = document.createElement("div");
        processSection.className = "process-section";
        processSection.style.position = "relative"; // Permitir que el badge de cotas sea absoluto

        let cotasBadge = document.createElement("img");
        cotasBadge.className = "cotas-badge";
        const baseUrl = window.baseUrl || (window.location.origin + '/');
        const slash = baseUrl.endsWith('/') ? '' : '/';
        
        cotasBadge.style.position = "absolute";
        cotasBadge.style.top = "12px";
        cotasBadge.style.right = "12px";
        cotasBadge.style.width = "40px";
        cotasBadge.style.height = "40px";
        cotasBadge.style.zIndex = "5";
        
        if (processesArray["requiresCotas"] === false) {
            cotasBadge.src = `${baseUrl}${slash}images/Aprobado.png`;
            cotasBadge.title = "No requiere cotas";
            cotasBadge.classList.add("cotas-badge-glow");
        } else if (processesArray["hasCotas"]) {
            cotasBadge.src = `${baseUrl}${slash}images/icono_cotas.png`;
            cotasBadge.title = "Cotas registradas correctamente";
            cotasBadge.classList.add("cotas-badge-glow");
        } else {
            cotasBadge.src = `${baseUrl}${slash}images/icono_sin_cotas.png`;
            cotasBadge.title = "Falta registrar cotas";
            cotasBadge.style.opacity = "0.7";
        }
        processSection.appendChild(cotasBadge);

        // Badge con número de proceso en la esquina superior izquierda
        let numberBadge = document.createElement("span");
        numberBadge.className = "process-number-badge";
        numberBadge.textContent = String(processNumber).padStart(2, '0');
        processSection.appendChild(numberBadge);

        let processTitle = document.createElement("h3");
        processTitle.className = "process-title";
        processTitle.innerHTML = processName;
        processSection.appendChild(processTitle);

        // "Total disponible" = las piezas que entran a este proceso (buenas del proceso anterior,
        // o la consignación si es el primero). Las bajas de ESTE proceso se ven en la barra roja.
        let limitLabel = document.createElement("label");
        limitLabel.className = "limit-label";
        limitLabel.style.fontSize = "12px";
        limitLabel.style.color = "#fff";
        limitLabel.innerHTML = `Total disponible: ${limitPieces}`;
        processSection.appendChild(limitLabel);

        let pieces = [processesArray["pieces"]["good"], processesArray["pieces"]["bad"]];

        // Si no hay piezas procesadas (buenas ni malas), oscurecer los textos
        if (pieces[0] === 0 && pieces[1] === 0) {
            processSection.classList.add("inactive-process");
        }

        // El 100% de la barra verde es sobre limitPieces (piezas disponibles de entrada).
        // Esto garantiza que si todos los procesos previos están bien y este también,
        // la barra llega exactamente a 100%.
        let goodPercentage = limitPieces == 0 ? (pieces[0] > 0 ? 100 : 0) : (pieces[0] * 100) / limitPieces;
        goodPercentage = Math.min(100, goodPercentage); // Clamp a 100%

        let hue = 30 + (goodPercentage * 0.9);
        processSection.style.setProperty('--glow-hue', hue);

        // Tooltip descriptivo
        if (goodPercentage === 0) {
            processSection.title = "Proceso aún no iniciado. Esperando primeras piezas.";
        } else if (goodPercentage < 50) {
            processSection.title = `Progreso bajo (${goodPercentage.toFixed(1)}%). Se requiere atención.`;
        } else if (goodPercentage < 100) {
            processSection.title = `Progreso estable (${goodPercentage.toFixed(1)}%). Buen ritmo de trabajo.`;
        } else {
            processSection.title = "¡Proceso completado exitosamente al 100%!";
        }

        // Borde verde si el proceso está concluido (100%)
        if (goodPercentage >= 100) {
            processSection.style.borderColor = '#4ade80';
            processSection.style.boxShadow = 'none';
            numberBadge.style.color = '#4ade80';
            numberBadge.style.borderColor = 'rgba(74, 222, 128, 0.3)';
            numberBadge.style.background = 'rgba(74, 222, 128, 0.1)';
        } else if (goodPercentage > 0) {
            processSection.style.borderColor = `hsl(${hue}, 100%, 50%)`;
            processSection.style.boxShadow = 'none';
            numberBadge.style.color = `hsl(${hue}, 100%, 50%)`;
            numberBadge.style.borderColor = `hsla(${hue}, 100%, 50%, 0.3)`;
            numberBadge.style.background = `hsla(${hue}, 100%, 50%, 0.1)`;
        } else {
            processSection.style.borderColor = '';
            processSection.style.boxShadow = '';
            numberBadge.style.color = '';
            numberBadge.style.borderColor = '';
            numberBadge.style.background = '';
        }

        for (let i = 0; i < pieces.length; i++) {
            //Crear barra de progreso
            let progressBar = document.createElement("div");
            progressBar.className = "progress-bar";
            progressBar.style.backgroundColor = i == 0 ? "rgba(12, 130, 1, 0.15)" : "rgba(157, 4, 2, 0.15)";

            let progress = document.createElement("div");
            progress.className = i == 0 ? "good-progress" : "bad-progress";
            progress.classList.add("progress");

            // Ambas barras (buenas y malas) se calculan sobre el total disponible de ENTRADA (limitPieces)
            // Así, buenas + malas <= 100% y el usuario ve de forma inmediata cuántas piezas se perdieron.
            let percentage = limitPieces == 0 ? (pieces[i] > 0 ? 100 : 0) : (pieces[i] * 100) / limitPieces;
            let displayPercentage = percentage;
            percentage = Math.min(100, percentage); // Clamp para el width visual y el color

            progress.style.width = `${percentage}%`;

            // Cambiar color de barra según progreso (Naranja -> Verde)
            if (i == 0 && percentage > 0) {
                let hue = 30 + (percentage * 0.9);
                progress.style.backgroundColor = `hsl(${hue}, 100%, 32%)`;
            }

            displayPercentage = displayPercentage != 0 ? displayPercentage.toFixed(1) : 0;
            let div = document.createElement("div");
            div.className = "progress-percentage";
            div.innerHTML = pieces[i] == 1 ? `${displayPercentage}% ${pieces[i]} pieza` : `${displayPercentage}% ${pieces[i]} piezas`;

            progressBar.appendChild(progress);
            progressBar.appendChild(div);
            processSection.appendChild(progressBar);
        }

        //Agregar evento al div de progreso
        processSection.addEventListener("click", () => {
            this.generateDivBadPieces(processName, processesArray["piecesBadData"]);

        });
        return processSection;
    }

    // ── Card especial para Resultados de Soldadura PTA ──────────────────────
    generatePTASection(ptaData, otId, classId, classArray, container) {
        // Monta el componente reactivo directamente en el container dado.
        // El componente gestiona su propio ciclo de vida.
        new PTACardComponent(otId, classId, ptaData, container, classArray);
    }
    generateDivBadPieces(processName, badPieces) {
        //Creacion del div de opacidad de fondo
        let div = document.createElement("div");
        div.className = "opacity-div";

        //Creacion del div en donde se mostrara la tabla de las piezas malas
        let modal = document.createElement("div");
        modal.className = "modal";

        //Creacion del titulo del proceso al que se da click
        let modalTitle = document.createElement("h2");
        modalTitle.className = "modal-title";
        modalTitle.innerHTML = `Proceso: ${processName}`;
        modal.appendChild(modalTitle);

        //Creacion del boton de cerrar el modal
        let modalClose = document.createElement("button");
        modalClose.className = "modal-close";

        let imageClose = document.createElement("img");
        imageClose.className = "img-close";
        imageClose.src = window.cerrarImgUrl;
        modalClose.appendChild(imageClose);

        modalClose.addEventListener("click", function () {
            document.body.removeChild(div);
            document.body.classList.remove("overflow-hidden"); document.body.classList.add("overflow-auto");
        });
        modal.appendChild(modalClose);

        //Creacion de la tabla de las piezas malas
        let tableWrapper = document.createElement("div");
        tableWrapper.className = "bad-pieces-table-wrapper";
        let table = this.createTableBadPieces(badPieces, processName);
        tableWrapper.appendChild(table);
        modal.appendChild(tableWrapper);

        div.addEventListener("click", function (e) {
            if (e.target === div) {
                document.body.removeChild(div);
                document.body.classList.remove("overflow-hidden"); document.body.classList.add("overflow-auto");
            }
        });
        div.appendChild(modal);
        document.body.appendChild(div);
        document.body.classList.remove("overflow-auto"); document.body.classList.add("overflow-hidden");
    }
    createTableBadPieces(badPieces, processName) {
        let table = document.createElement("table");
        table.className = "bad-pieces-table";
        let thead = document.createElement("thead");
        let headerRow = document.createElement("tr");
        let headers =
            processName == "Operacion Equipo"
                ? ["Pieza", "Numero de juego", "Operador", "Proceso", "Operacion", "Error"]
                : ["Pieza", "Numero de juego", "Operador", "Proceso", "Error"];

        //Insertar encabezados de la tabla
        headers.forEach((header) => {
            let th = document.createElement("th");
            th.innerHTML = header;
            th.style.width = (100 / headers.length) + "%"; // Ajustar el ancho de las columnas
            headerRow.appendChild(th);
        });

        //Insertar los datos de cada una de las piezas malas
        //prettier-ignore
        let tbody = document.createElement("tbody");
        if (Object.keys(badPieces).length > 0) {
            // Para Soldadura PTA, agrupar por número de juego (setNumber)
            if (processName.includes("Soldadura PTA")) {
                let setGroups = {};
                Object.values(badPieces).forEach((piece) => {
                    let key = piece["setNumber"];
                    if (!setGroups[key]) {
                        setGroups[key] = {
                            pieces: [],
                            setNumber: piece["setNumber"],
                            operator: piece["operator"],
                            process: piece["process"],
                            error: piece["error"],
                        };
                    }
                    setGroups[key].pieces.push(piece["piece"]);
                });
                Object.values(setGroups).forEach((group) => {
                    let row = document.createElement("tr");
                    let pieceData = [
                        group.pieces.join(", "),
                        group.setNumber,
                        group.operator,
                        group.process,
                        group.error,
                    ];
                    pieceData.forEach((data) => {
                        let td = document.createElement("td");
                        td.innerHTML = data;
                        row.appendChild(td);
                    });
                    tbody.appendChild(row);
                });
            } else {
                Object.values(badPieces).forEach((piece) => {
                    let row = document.createElement("tr");
                    let pieceData =
                        processName == "Operacion Equipo"
                            ? [
                                piece["piece"],
                                piece["setNumber"],
                                piece["operator"],
                                piece["process"],
                                piece["operation"],
                                piece["error"],
                            ]
                            : [piece["piece"], piece["setNumber"], piece["operator"], piece["process"], piece["error"]];
                    pieceData.forEach((data) => {
                        let td = document.createElement("td");
                        td.innerHTML = data;
                        row.appendChild(td);
                    });
                    tbody.appendChild(row);
                });
            }
        } else {
            let row = document.createElement("tr");
            let td = document.createElement("td");
            td.colSpan = headers.length;
            td.classList.add("no-bad-pieces");
            td.innerHTML = "No hay piezas malas registradas para este proceso.";
            row.appendChild(td);
            tbody.appendChild(row);
        }
        thead.appendChild(headerRow);
        table.appendChild(thead);
        table.appendChild(tbody);
        return table;
    }
}



let div_opacity = document.querySelector(".div-opacity");
if (div_opacity) {
    let btn_cerrar = document.querySelector(".btn-cerrar");
    if (btn_cerrar) {
        btn_cerrar.addEventListener("click", (e) => {
            e.stopPropagation();
            let div_padre = document.querySelector(".div-opacity");
            if (div_padre) div_padre.remove();
        });
    }
    div_opacity.addEventListener("click", (e) => {
        if (e.target === div_opacity) {
            let div_padre = document.querySelector(".div-opacity");
            if (div_padre) div_padre.remove();
        }
    });
}

// Referencias y caché para el scroll snap manual
let cachedSections = [];
function updateCachedSections() {
    const elements = document.querySelectorAll("section");
    cachedSections = Array.from(elements).map(sec => ({
        element: sec,
        offsetTop: sec.offsetTop
    }));
}

if (Object.keys(wOrderArray).length > 0) {
    let dashboard = new Dashboard(wOrderArray);
    dashboard.createSections();
    updateCachedSections();

    window.getClosestSection = function () {
        let closest = null;
        let minDist = Infinity;
        const scrollY = window.scrollY || document.documentElement.scrollTop;

        for (let i = 0; i < cachedSections.length; i++) {
            const sec = cachedSections[i];
            const dist = Math.abs(sec.offsetTop - scrollY);
            if (dist < minDist) {
                minDist = dist;
                closest = sec.element;
            }
        }

        return closest;
    }
} else {
    let body = document.querySelector("body");
    let noDataMessage = document.createElement("h2");
    noDataMessage.className = "no-data-message";
    noDataMessage.innerHTML = "No hay órdenes de trabajo en progreso.";
    body.appendChild(noDataMessage);
}

// Actualizar caché al cambiar el tamaño de ventana o al cargar
window.addEventListener("resize", updateCachedSections);
window.addEventListener("load", updateCachedSections);

// Lógica de los botones de navegación rápida y scroll snap con velocidad controlada (no tan rápida ni tan lenta)
document.addEventListener("DOMContentLoaded", () => {
    // Forzar el fondo a la imagen requerida y eliminar colores de fondo
    const bUrl = window.baseUrl || (window.location.origin + '/');
    const bSlash = bUrl.endsWith('/') ? '' : '/';
    document.body.style.setProperty('background', `url('${bUrl}${bSlash}images/fondoLogin.jpg') no-repeat center center fixed`, 'important');
    document.body.style.setProperty('background-size', 'cover', 'important');
    document.body.style.setProperty('background-color', 'transparent', 'important');

    const btnTop = document.getElementById("btn-scroll-top");
    const btnBottom = document.getElementById("btn-scroll-bottom");

    window.isScrollingProgrammatically = false;

    // Función de scroll suave personalizado (easeInOutQuad) para controlar la duración exacta
    function smoothScrollTo(targetY, duration = 550, onComplete = null) {
        window.isScrollingProgrammatically = true;
        const startY = window.scrollY || document.documentElement.scrollTop;
        const difference = targetY - startY;
        const startTime = performance.now();

        function easeInOutQuad(t, b, c, d) {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t + b;
            t--;
            return -c / 2 * (t * (t - 2) - 1) + b;
        }

        function animate(currentTime) {
            const timeElapsed = currentTime - startTime;
            const nextY = easeInOutQuad(timeElapsed, startY, difference, duration);

            window.scrollTo(0, nextY);

            if (timeElapsed < duration) {
                requestAnimationFrame(animate);
            } else {
                window.scrollTo(0, targetY);
                setTimeout(() => {
                    window.isScrollingProgrammatically = false;
                    if (onComplete) onComplete();
                }, 50);
            }
        }

        requestAnimationFrame(animate);
    }

    let isUpdatingButtons = false;
    function updateScrollButtons() {
        if (isUpdatingButtons) return;
        isUpdatingButtons = true;
        requestAnimationFrame(() => {
            isUpdatingButtons = false;
            if (!btnTop || !btnBottom) return;
            const scrollTop = window.scrollY || document.documentElement.scrollTop;
            const scrollHeight = document.documentElement.scrollHeight;
            const clientHeight = document.documentElement.clientHeight;

            // Tolerancia de 5px para desactivar botón arriba
            const atTop = scrollTop <= 5;
            btnTop.disabled = atTop;
            btnTop.style.opacity = atTop ? "0.2" : "1";
            btnTop.style.pointerEvents = atTop ? "none" : "auto";
            btnTop.style.transform = atTop ? "scale(0.9)" : "none";

            // Tolerancia de 10px para desactivar botón abajo
            const atBottom = scrollTop + clientHeight >= scrollHeight - 10;
            btnBottom.disabled = atBottom;
            btnBottom.style.opacity = atBottom ? "0.2" : "1";
            btnBottom.style.pointerEvents = atBottom ? "none" : "auto";
            btnBottom.style.transform = atBottom ? "scale(0.9)" : "none";
        });
    }

    if (btnTop) {
        btnTop.addEventListener("click", () => {
            smoothScrollTo(0, 600); // 600ms es la velocidad ideal para toda la página
        });
    }
    if (btnBottom) {
        btnBottom.addEventListener("click", () => {
            smoothScrollTo(document.body.scrollHeight, 600);
        });
    }

    updateScrollButtons(); // Ejecutar inicialmente
    window.addEventListener('scroll', () => {
        updateScrollButtons();

        // Cerrar automáticamente las tarjetas de checklist al hacer scroll
        if (!window.isScrollingProgrammatically) {
            document.querySelectorAll('.fundicion-checklist-card:not(.is-closed)').forEach(card => {
                const itemsContainer = card.querySelector('.checklist-items');
                if (itemsContainer && !itemsContainer.hidden) {
                    itemsContainer.hidden = true;
                    card.classList.add('is-closed');
                }
            });
        }
    }, { passive: true });
    window.addEventListener('resize', updateScrollButtons, { passive: true });
    setTimeout(updateCachedSections, 200);
});

window.mostrarModalFinalizarPedido = function (tipo, mensaje, recargar = false) {
    let divOpacity = document.createElement("div");
    divOpacity.className = "div-opacity";

    let alertDiv = document.createElement("div");
    alertDiv.className = "alert-finishOrder";

    let divCerrar = document.createElement("div");
    divCerrar.className = "div-cerrar";

    let btnCerrar = document.createElement("button");
    btnCerrar.className = "btn-cerrar";
    btnCerrar.innerHTML = `<img class="img-cerrar" src="${window.cerrarImgUrl || window.baseUrl + '/images/cerrar.png'}">`;

    btnCerrar.addEventListener("click", (e) => {
        e.stopPropagation();
        divOpacity.remove();
        if (recargar) location.reload();
    });

    divCerrar.appendChild(btnCerrar);
    alertDiv.appendChild(divCerrar);

    let imgRoute = (tipo === "error") ? "/images/error.png" : "/images/ready.png";
    let img = document.createElement("img");
    img.className = "img-error";
    img.src = (window.baseUrl || "") + imgRoute;
    img.alt = "alert image";
    alertDiv.appendChild(img);

    let label = document.createElement("label");
    label.textContent = mensaje;
    alertDiv.appendChild(label);

    divOpacity.appendChild(alertDiv);

    divOpacity.addEventListener("click", (e) => {
        if (e.target === divOpacity) {
            divOpacity.remove();
            if (recargar) location.reload();
        }
    });

    document.body.appendChild(divOpacity);
};
