document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById("org-search-input");
    const plantaBtns = document.querySelectorAll(".planta-filter-btn");
    const turnoSelect = document.getElementById("org-turno-filter");
    const statusSelect = document.getElementById("org-status-filter");
    const userCards = document.querySelectorAll(".org-node");

    const canvas = document.getElementById("org-canvas");
    const treeRoot = document.getElementById("org-tree-root");
    const btnZoomIn = document.getElementById("btn-zoom-in");
    const btnZoomOut = document.getElementById("btn-zoom-out");
    const btnZoomReset = document.getElementById("btn-zoom-reset");

    let currentPlanta = "todas";
    let currentTurno = "todos";
    let currentStatus = "todos";
    let searchQuery = "";
    let currentZoom = 1;

    // ── Filtros ──────────────────────────────────────────────
    function filterUsers() {
        let visibleCount = 0;
        let activeVisible = 0;
        let inactiveVisible = 0;

        userCards.forEach((card) => {
            const cardPlanta = (card.dataset.planta || "").toUpperCase();
            const cardTurno = (card.dataset.turno || "").toUpperCase();
            const cardStatus = (card.dataset.status || "").toLowerCase();
            const cardText = (card.dataset.search || "").toLowerCase();

            // Planta Filter
            let matchPlanta = true;
            if (currentPlanta !== "todas") {
                matchPlanta = cardPlanta === currentPlanta.toUpperCase();
            }

            // Turno Filter
            let matchTurno = true;
            if (currentTurno !== "todos") {
                matchTurno = cardTurno === currentTurno.toUpperCase();
            }

            // Status Filter
            let matchStatus = true;
            if (currentStatus === "activo") {
                matchStatus = cardStatus === "activo";
            } else if (currentStatus === "inactivo") {
                matchStatus = cardStatus === "inactivo";
            }

            // Search Query
            let matchSearch = true;
            if (searchQuery.trim() !== "") {
                matchSearch = cardText.includes(searchQuery.toLowerCase());
            }

            if (matchPlanta && matchTurno && matchStatus && matchSearch) {
                card.style.opacity = "1";
                card.style.filter = "none";
                card.style.transform = "";
                visibleCount++;
                if (cardStatus === "activo") activeVisible++;
                else inactiveVisible++;
            } else {
                card.style.opacity = "0.2";
                card.style.filter = "grayscale(80%)";
            }
        });

        // Actualizar contadores
        const totalEl = document.getElementById("kpi-total-val");
        const activeEl = document.getElementById("kpi-active-val");
        const inactiveEl = document.getElementById("kpi-inactive-val");

        if (totalEl) totalEl.textContent = visibleCount;
        if (activeEl) activeEl.textContent = activeVisible;
        if (inactiveEl) inactiveEl.textContent = inactiveVisible;
    }

    // Planta Buttons
    plantaBtns.forEach((btn) => {
        btn.addEventListener("click", function () {
            plantaBtns.forEach((b) => b.classList.remove("active"));
            this.classList.add("active");
            currentPlanta = this.dataset.planta || "todas";
            filterUsers();
        });
    });

    // Turno Select
    if (turnoSelect) {
        turnoSelect.addEventListener("change", function () {
            currentTurno = this.value;
            filterUsers();
        });
    }

    // Status Select
    if (statusSelect) {
        statusSelect.addEventListener("change", function () {
            currentStatus = this.value;
            filterUsers();
        });
    }

    // Search Input
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            searchQuery = this.value;
            filterUsers();
        });
    }

    // ── Zoom y Pan en Canvas ─────────────────────────────────
    function setZoom(scale) {
        currentZoom = Math.min(Math.max(0.5, scale), 1.6);
        if (treeRoot) {
            treeRoot.style.transform = `scale(${currentZoom})`;
        }
        if (btnZoomReset) {
            btnZoomReset.textContent = `${Math.round(currentZoom * 100)}%`;
        }
    }

    if (btnZoomIn) {
        btnZoomIn.addEventListener("click", () => setZoom(currentZoom + 0.1));
    }
    if (btnZoomOut) {
        btnZoomOut.addEventListener("click", () => setZoom(currentZoom - 0.1));
    }
    if (btnZoomReset) {
        btnZoomReset.addEventListener("click", () => setZoom(1));
    }

    // Drag to Pan Canvas
    if (canvas) {
        let isDown = false;
        let startX, startY, scrollLeft, scrollTop;

        canvas.addEventListener("mousedown", (e) => {
            if (e.target.closest(".org-node")) return;
            isDown = true;
            canvas.style.cursor = "grabbing";
            startX = e.pageX - canvas.offsetLeft;
            startY = e.pageY - canvas.offsetTop;
            scrollLeft = canvas.scrollLeft;
            scrollTop = canvas.scrollTop;
        });

        canvas.addEventListener("mouseleave", () => {
            isDown = false;
            canvas.style.cursor = "grab";
        });

        canvas.addEventListener("mouseup", () => {
            isDown = false;
            canvas.style.cursor = "grab";
        });

        canvas.addEventListener("mousemove", (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - canvas.offsetLeft;
            const y = e.pageY - canvas.offsetTop;
            const walkX = (x - startX) * 1.5;
            const walkY = (y - startY) * 1.5;
            canvas.scrollLeft = scrollLeft - walkX;
            canvas.scrollTop = scrollTop - walkY;
        });

        // Centrar el scroll horizontalmente al cargar
        setTimeout(() => {
            canvas.scrollLeft = (canvas.scrollWidth - canvas.clientWidth) / 2;
        }, 100);
    }
});
