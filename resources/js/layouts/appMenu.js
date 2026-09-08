import "../layouts/partials/messages.js";

function initAppMenu() {
    let btn_open = document.querySelector(".open-menu");
    let nav = document.querySelector(".filter-opacity");
    let profileEl = document.getElementById("profile");

    if (!profileEl) return;

    let profile = profileEl.value;
    createMenu(profile);

    if (btn_open && nav) {
        btn_open.addEventListener("click", function () {
            if (nav.classList.contains("is-open")) {
                nav.classList.remove("is-open");
                nav.style.opacity = "0";
                setTimeout(() => {
                    if (!nav.classList.contains("is-open")) {
                        nav.style.visibility = "hidden";
                    }
                }, 250);
            } else {
                nav.classList.add("is-open");
                nav.style.visibility = "visible";
                nav.style.opacity = "1";
            }
        });

        nav.addEventListener("click", function (e) {
            if (e.target === nav) {
                nav.classList.remove("is-open");
                nav.style.opacity = "0";
                setTimeout(() => {
                    if (!nav.classList.contains("is-open")) {
                        nav.style.visibility = "hidden";
                    }
                }, 250);
            }
        });
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAppMenu);
} else {
    initAppMenu();
}

function createMenu(profile) {
    let btn_open = document.querySelector(".open-menu");
    if (profile == 2) {
        if (btn_open) {
            btn_open.style.opacity = "0";
            btn_open.style.pointerEvents = "none";
        }
    } else {
        if (btn_open) {
            btn_open.style.opacity = "1";
            btn_open.style.pointerEvents = "auto";
        }
        let routes = getRoutes(profile);
        let ul = document.querySelector(".nav-list");
        if (!ul) return;
        ul.innerHTML = "";
        ul.appendChild(createList(routes, false));

        // Determinar la sección principal preferida (Administración, Calidad, Almacén)
        let preferredTopSection = null;
        if (window.location.search.includes('almacen_only=1') || window.location.search.includes('sec=almacen')) {
            preferredTopSection = "Almacén";
        } else if (window.location.search.includes('sec=calidad')) {
            preferredTopSection = "Calidad";
        } else if (window.location.search.includes('admin_only=1') || window.location.search.includes('sec=admin')) {
            preferredTopSection = "Administración";
        } else {
            preferredTopSection = sessionStorage.getItem("active_top_section");
        }

        const currentPath = window.location.pathname;
        const allLinks = Array.from(ul.querySelectorAll("a.nav-link"));
        let targetLink = null;

        // 1. Si hay una sección preferida, buscar un enlace coincidente dentro de esa sección primero
        if (preferredTopSection) {
            const topSections = Array.from(ul.querySelectorAll(":scope > li.menu-section"));
            const prefLi = topSections.find(li => {
                const toggle = li.querySelector(":scope > .submenu-toggle");
                return toggle && toggle.textContent.trim() === preferredTopSection;
            });
            if (prefLi) {
                const prefLinks = Array.from(prefLi.querySelectorAll("a.nav-link"));
                targetLink = prefLinks.find(a => isLinkMatching(a, currentPath));
            }
        }

        // 2. Si no hay sección preferida o no hubo coincidencia en ella, buscar la primera coincidencia en todo el menú
        if (!targetLink) {
            targetLink = allLinks.find(a => isLinkMatching(a, currentPath));
        }

        // Expandir solo la rama del enlace activo seleccionado
        if (targetLink) {
            targetLink.classList.add("active");
            let parentEl = targetLink.parentElement;
            while (parentEl && parentEl.tagName !== "NAV" && parentEl !== document.body) {
                if (parentEl.classList.contains("menu-section")) {
                    parentEl.classList.add("active");
                    const directSubmenu = Array.from(parentEl.children).find(c => c.classList && c.classList.contains("submenu"));
                    if (directSubmenu) {
                        directSubmenu.hidden = false;
                    }
                    if (parentEl.parentElement && parentEl.parentElement.classList.contains("nav-list")) {
                        const toggleBtn = parentEl.querySelector(":scope > .submenu-toggle");
                        if (toggleBtn) {
                            sessionStorage.setItem("active_top_section", toggleBtn.textContent.trim());
                        }
                    }
                }
                parentEl = parentEl.parentElement;
            }
        }
    }
}

function isLinkMatching(a, currentPath) {
    const linkUrl = new URL(a.href, window.location.origin);
    const linkSearch = linkUrl.search;

    const pathMatch = isPathMatching(currentPath, a.href);
    let searchMatch = false;
    if (linkSearch) {
        searchMatch = window.location.search.includes(linkSearch.substring(1));
    } else {
        searchMatch = window.location.search === '' || (
            !window.location.search.includes('admin_only=1') &&
            !window.location.search.includes('almacen_only=1') &&
            !window.location.search.includes('sec=almacen') &&
            !window.location.search.includes('sec=calidad')
        );
    }

    return pathMatch && searchMatch;
}

function isPathMatching(currentPath, aHref) {
    if (!aHref) return false;
    try {
        const linkUrl = new URL(aHref, window.location.origin);
        const linkPath = linkUrl.pathname;

        if (currentPath === linkPath) return true;

        // Sub-rutas derivadas o parametrizadas de Orden de Trabajo (ej: /showWO/5468 -> /master/createWO o /manageWO)
        if (currentPath.startsWith('/showWO') || currentPath.startsWith('/show_wo_almacen')) {
            if (window.location.search.includes('from_master=1')) {
                if (window.routes && window.routes.createMasterWO) {
                    const masterPath = new URL(window.routes.createMasterWO, window.location.origin).pathname;
                    if (linkPath === masterPath) return true;
                }
            } else if (window.routes && window.routes.manageWO) {
                const managePath = new URL(window.routes.manageWO, window.location.origin).pathname;
                if (linkPath === managePath) return true;
            }
        }

        // Sub-rutas de usuarios (ej: /users/1/edit -> /users)
        if (currentPath.startsWith('/users/') && !currentPath.includes('/create') && !currentPath.includes('/recoverPassword')) {
            if (window.routes && window.routes.users) {
                const usersPath = new URL(window.routes.users, window.location.origin).pathname;
                if (linkPath === usersPath) return true;
            }
        }

        return false;
    } catch (e) {
        return false;
    }
}

function createList(sections, isNested = false) {
    const fragment = document.createDocumentFragment();

    sections.forEach((section) => {
        // Sección con título (submenú)
        if (section.title) {
            const liSection = document.createElement("li");
            liSection.classList.add("menu-section");
            if (isNested) {
                liSection.classList.add("nested-menu-section");
            }

            const toggle = document.createElement("a");
            toggle.href = "#";
            toggle.classList.add("submenu-toggle");
            toggle.textContent = section.title;

            const ulSubmenu = document.createElement("ul");
            ulSubmenu.classList.add("submenu");

            // Si routes tiene sub-secciones (objetos) en lugar de arrays
            const firstRoute = section.routes[0];
            if (firstRoute && typeof firstRoute === "object" && !Array.isArray(firstRoute)) {
                ulSubmenu.appendChild(createList(section.routes, true));
            } else {
                section.routes.forEach((route) => {
                    const li = document.createElement("li");
                    const a = document.createElement("a");
                    a.classList.add("nav-link");
                    a.href = window.routes[route[0]];
                    if (route[2]) {
                        if (a.href.includes('?')) {
                            a.href += '&' + route[2];
                        } else {
                            a.href += '?' + route[2];
                        }
                    }
                    a.textContent = route[1];

                    a.addEventListener("click", (e) => {
                        e.preventDefault();

                        // Guardar en sessionStorage el menú principal donde hizo clic el usuario
                        const topLi = li.closest(".nav-list > .menu-section");
                        if (topLi) {
                            const toggleBtn = topLi.querySelector(":scope > .submenu-toggle");
                            if (toggleBtn) {
                                sessionStorage.setItem("active_top_section", toggleBtn.textContent.trim());
                            }
                        }

                        let div_opacity = document.createElement("div");
                        div_opacity.classList.add("div-opacity");

                        let div_loading = document.createElement("div");
                        div_loading.classList.add("loading");

                        let img_loading = document.createElement("img");
                        img_loading.classList.add("img-loading");
                        img_loading.src = window.loading;
                        img_loading.alt = "Cargando...";
                        div_loading.appendChild(img_loading);

                        div_opacity.appendChild(div_loading);
                        document.body.appendChild(div_opacity);

                        window.location.href = a.href;
                    });

                    li.appendChild(a);
                    ulSubmenu.appendChild(li);
                });
            }

            // Manejador del click para abrir/cerrar este submenú
            toggle.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                const parent = liSection;
                const isAlreadyActive = parent.classList.contains("active");

                // Cerrar hermanos del mismo nivel (para evitar desorden)
                const siblings = parent.parentElement.children;
                for (let sibling of siblings) {
                    if (sibling !== parent && sibling.classList.contains("menu-section")) {
                        sibling.classList.remove("active");
                        const submenu = sibling.querySelector(".submenu");
                        if (submenu) submenu.hidden = true;
                    }
                }

                // Alternar el actual
                if (isAlreadyActive) {
                    parent.classList.remove("active");
                    ulSubmenu.hidden = true;
                } else {
                    parent.classList.add("active");
                    ulSubmenu.hidden = false;
                }
            });

            liSection.appendChild(toggle);
            liSection.appendChild(ulSubmenu);
            fragment.appendChild(liSection);
        }
        // Sección sin título (rutas individuales)
        else {
            section.routes.forEach((route) => {
                const li = document.createElement("li");
                const a = document.createElement("a");
                a.classList.add("nav-link");
                a.href = window.routes[route[0]];
                if (route[2]) {
                    if (a.href.includes('?')) {
                        a.href += '&' + route[2];
                    } else {
                        a.href += '?' + route[2];
                    }
                }
                a.textContent = route[1];

                a.addEventListener("click", (e) => {
                    e.preventDefault();
                    sessionStorage.removeItem("active_top_section");
                    let div_opacity = document.createElement("div");
                    div_opacity.classList.add("div-opacity");

                    let div_loading = document.createElement("div");
                    div_loading.classList.add("loading");

                    let img_loading = document.createElement("img");
                    img_loading.classList.add("img-loading");
                    img_loading.src = window.loading;
                    img_loading.alt = "Cargando...";
                    div_loading.appendChild(img_loading);

                    div_opacity.appendChild(div_loading);
                    document.body.appendChild(div_opacity);

                    window.location.href = a.href;
                });

                li.appendChild(a);
                fragment.appendChild(li);
            });
        }
    });

    return fragment;
}

function getRoutes(profile) {
    const routeHome = ["home", "Inicio"];
    let sections = [];

    switch (profile) {
        case "1":
            // Admin (perfil 1 en BD): secciones directas sin título "Administración"
            sections = [
                {
                    title: null,
                    routes: [routeHome],
                },
                {
                    title: "Prioridades",
                    routes: [
                        ["masterPriorities", "Prioridades GIS"],
                    ],
                },
                {
                    title: "Molduras",
                    routes: [
                        ["createMolding", "Crear nueva moldura"],
                        ["editMolding", "Editar moldura"],
                    ],
                },
                {
                    title: "Orden de Trabajo",
                    routes: [
                        ["manageWO", "Programación de O.T"],
                        ["piecesInProgress", "Orden de Trabajo en Progreso"],
                        ["priorityManager", "Prioridad de Órdenes de Trabajo"],
                        ["showPiecesReport_view", "Reporte de piezas"],
                        ["showReleasePieces_view", "Liberación de piezas"],
                    ],
                },
                {
                    title: "Documentación Técnica",
                    routes: [
                        ["ayudas_fundicion.manage", "Ayudas Visuales de Fundición"],
                        ["ayudas.manage", "Ayudas Visuales de Maquinados"],
                        ["fundicion.manage", "Dibujos de Fundición"],
                        ["dibujos.manage", "Dibujos de Maquinados"],
                        ["manuales.manage", "Manuales de Procesos"],
                    ],
                },
                {
                    title: "Usuarios",
                    routes: [
                        ['users', 'Ver usuarios'],
                        ["createUser", "Registrar usuario"],
                        ["recoverPassword", "Recuperar contraseña"],
                    ],
                },
                {
                    title: "Producción",
                    routes: [
                        ["productionData", "Datos de productividad"],
                        ["cNominals", "Editar C.Nominales y Tolerancias"],
                        ["machinesOccupied", "Máquinas ocupadas"],
                        ["show_panelWO", "Panel de progreso de O.T"],
                        ["systemLogsReport", "Auditoría de Producción"],
                        ["adminLogsReport", "Logs de Administradores"],
                    ],
                },
                {
                    title: "Soldadura PTA",
                    routes: [
                        ["pta.analysis", "Análisis de Resultados PTA"],
                        ["pta.segunda_pasada", "Segunda Pasada PTA"],
                    ],
                },
                {
                    title: "Reportes",
                    routes: [
                        ["reportes.reenvio", "Reenviar Reporte Diario"],
                        ["reportes.pta", "Envío de Reportes PTA"],
                    ],
                },
                {
                    title: "Herramientas",
                    routes: [
                        ["herramientas.tecamac.index", "Herramientas Tecamac"],
                    ],
                },
            ];
            break;
        case "2":
            sections = [
                {
                    title: null,
                    routes: [routeHome],
                },
                {
                    title: null,
                    routes: [["processProduction", "Proceso de Producción"]],
                },
                {
                    title: "Herramientas",
                    routes: [
                        ["herramientas.tecamac.index", "Herramientas Tecamac"],
                    ],
                },
            ];
            break;
        case "3":
            // Master (perfil 3 en BD): Administración + Calidad + Almacén
            sections = [
                {
                    title: null,
                    routes: [routeHome],
                },
                {
                    title: "Prioridades",
                    routes: [
                        ["masterPriorities", "Prioridades GIS"],
                    ],
                },
                {
                    title: "Administración",
                    routes: [
                        {
                            title: "Molduras",
                            routes: [
                                ["createMolding", "Crear nueva moldura"],
                                ["editMolding", "Editar moldura"],
                            ],
                        },
                        {
                            title: "Orden de Trabajo",
                            routes: [
                                ["createMasterWO", "Crear o Modificar Orden de Trabajo (Master)"],
                                ["manageWO", "Programación de O.T"],
                                ["piecesInProgress", "Orden de Trabajo en Progreso"],
                                ["priorityManager", "Prioridad de Órdenes de Trabajo"],
                                ["showPiecesReport_view", "Reporte de piezas"],
                                ["showReleasePieces_view", "Liberación de piezas"],
                            ],
                        },
                        {
                            title: "Documentación Técnica",
                            routes: [
                                ["ayudas_fundicion.manage", "Ayudas Visuales de Fundición"],
                                ["ayudas.manage", "Ayudas Visuales de Maquinados"],
                                ["fundicion.manage", "Dibujos de Fundición"],
                                ["dibujos.manage", "Dibujos de Maquinados"],
                                ["manuales.manage", "Manuales de Procesos"],
                            ],
                        },
                        {
                            title: "Usuarios",
                            routes: [
                                ['users', 'Ver usuarios'],
                                ["createUser", "Registrar usuario"],
                                ["recoverPassword", "Recuperar contraseña"],
                            ],
                        },
                        {
                            title: "Producción",
                            routes: [
                                ["productionData", "Datos de productividad"],
                                ["cNominals", "Editar C.Nominales y Tolerancias"],
                                ["machinesOccupied", "Máquinas ocupadas"],
                                ["show_panelWO", "Panel de progreso de O.T"],
                                ["systemLogsReport", "Auditoría de Producción"],
                                ["adminLogsReport", "Logs de Administradores"],
                            ],
                        },
                        {
                            title: "Soldadura PTA",
                            routes: [
                                ["pta.analysis", "Análisis de Resultados PTA"],
                                ["pta.segunda_pasada", "Segunda Pasada PTA"],
                            ],
                        },
                        {
                            title: "Reportes",
                            routes: [
                                ["reportes.reenvio", "Reenviar Reporte Diario"],
                                ["reportes.pta", "Envío de Reportes PTA"],
                            ],
                        },
                        {
                            title: "Herramientas",
                            routes: [
                                ["herramientas.tecamac.index", "Herramientas Tecamac"],
                            ],
                        },
                    ],
                },
                {
                    title: "Calidad",
                    routes: [
                        {
                            title: "Liberación de Piezas",
                            routes: [["showReleasePieces_view", "Liberación de piezas", "sec=calidad"]],
                        },
                        {
                            title: "Producción",
                            routes: [
                                ["piecesInProgress", "Orden de Trabajo en Progreso", "sec=calidad"],
                                ["cNominals", "Editar C.Nominales y Tolerancias", "sec=calidad"],
                            ],
                        },
                        {
                            title: "Documentación Técnica",
                            routes: [
                                ["calidad.fundicion.index", "Dibujos y Ayudas de Fundición"],
                                ["calidad.maquinados.index", "Dibujos y Ayudas de Maquinados"],
                            ],
                        },
                    ],
                },
                {
                    title: "Almacén",
                    routes: [
                        {
                            title: "Orden de Trabajo",
                            routes: [
                                ["manageWO", "Modificar O.T", "almacen_only=1"],
                                ["piecesInProgress", "Orden de Trabajo en Progreso", "sec=almacen"],
                            ],
                        },
                        {
                            title: "Soldadura",
                            routes: [
                                ["soldadura.generarQRLote", "Generar QR por Lote"],
                                ["soldadura.generarQRIndividual", "Generar QRs Botes"],
                                ["soldadura.recepcionPlanta", "Registrar entrada de Soldadura"],
                                ["soldadura.liberarQRPlanta", "Entrega de Soldadura a Planta"],
                                ["soldadura.regenerarQR", "Regenerar QRs"],
                            ],
                        },
                        {
                            title: "Documentación Técnica",
                            routes: [
                                ["almacen.fundicion.index", "Dibujos y Ayudas de Fundición"],
                            ],
                        },
                        {
                            title: "Herramientas",
                            routes: [
                                ["herramientas.tecamac.index", "Herramientas Tecamac", "sec=almacen"],
                            ],
                        },
                    ],
                },
            ];
            break;
        case "4":
            sections = [
                {
                    title: null,
                    routes: [routeHome],
                },
                {
                    title: "Liberación de Piezas",
                    routes: [["showReleasePieces_view", "Liberacion de piezas"]],
                },
                {
                    title: "Producción",
                    routes: [
                        ["piecesInProgress", "Orden de Trabajo en Progreso"],
                        ["cNominals", "Editar C.Nominales y Tolerancias"],
                        // ["showTimes", "Modificar tiempos de producción"],
                    ],
                },
                {
                    title: "Documentación Técnica",
                    routes: [
                        ["calidad.fundicion.index", "Dibujos y Ayudas de Fundición"],
                        ["calidad.maquinados.index", "Dibujos y Ayudas de Maquinados"],
                    ],
                },
            ];
            break;
        case "5":
            sections = [
                {
                    title: null,
                    routes: [routeHome],
                },
                {
                    title: "Orden de Trabajo",
                    routes: [
                        ["manageWO", "Modificar O.T"],
                        ["piecesInProgress", "Orden de Trabajo en Progreso"],
                    ],
                },
                {
                    title: "Soldadura",
                    routes: [
                        ["soldadura.generarQRLote", "Generar QR por Lote"],
                        ["soldadura.generarQRIndividual", "Generar QRs Botes"],
                        ["soldadura.recepcionPlanta", "Registrar entrada de Soldadura"],
                        ["soldadura.liberarQRPlanta", "Entrega de Soldadura a Planta"],
                        ["soldadura.regenerarQR", "Regenerar QRs"]
                    ],
                },
                {
                    title: "Documentación Técnica",
                    routes: [
                        ["almacen.fundicion.index", "Dibujos y Ayudas de Fundición"],
                    ],
                },
                {
                    title: "Herramientas",
                    routes: [
                        ["herramientas.tecamac.index", "Herramientas Tecamac"],
                    ],
                },
            ];
            break;
        case "pta_temp":
            sections = [
                {
                    title: "Opciones PTA",
                    routes: [
                        ["pta.results.current", "Resultados de Sold. PTA"],
                        ["pta.analysis", "Análisis de Resultados Sold. PTA"],
                        ["pta.segunda_pasada", "Segunda Pasada PTA"],
                    ],
                },
            ];
            break;
        default:
            sections = [
                {
                    title: null,
                    routes: [routeHome],
                },
            ];
            break;
    }
    return sections;
}


//Si window.pieces_released es de tipo Object convertirlo a Array
if (window.pieces_Released) {
    window.pieces_Released = !Array.isArray(window.pieces_Released)
        ? Object.values(window.pieces_Released)
        : window.pieces_Released;

    if (window.pieces_Released && window.pieces_Released.length > 0) {
        const divOpacity = document.createElement("div");
        divOpacity.classList.add("filter-opacity-home");

        // Crear elementos
        const divTable = document.createElement("div");
        divTable.className = "div-table-pieces-released";
        const table = document.createElement("table");
        table.id = "table";
        table.classList.add("table-pieces-released");

        const thead = document.createElement("thead");
        const tr = document.createElement("tr");

        // Agregar encabezados base
        const headers = ["Clase", "Orden de trabajo", "Juego", "Nombre del operador", "Máquina", "Proceso"];

        // Agregar columnas base
        headers.forEach((header, i) => {
            const th = document.createElement("th");
            th.textContent = header;
            if (header === "Nombre del operador") th.classList.add("cell-long-text");
            tr.appendChild(th);
        });

        // Agregar columnas finales
        const moreHeaders = [
            "Errores",
            "Observaciones",
            "Fecha de Maquinado",
            "Liberar",
            "Rechazar",
            "Ver",
        ];

        moreHeaders.forEach((header) => {
            const th = document.createElement("th");
            const headerText = typeof header === "object" ? header.name : header;
            th.textContent = headerText;
            if (headerText === "Errores" || headerText === "Observaciones") th.classList.add("cell-long-text");
            tr.appendChild(th);
        });

        // Estructurar tabla
        thead.appendChild(tr);
        table.appendChild(thead);
        divTable.appendChild(table);

        // Finalmente, agregar a tu documento
        divOpacity.appendChild(divTable); // O donde tú necesites insertarlo
        document.body.appendChild(divOpacity);
        crearTabla(window.pieces_Released, window.info_Pieces);
    }
}
function crearTabla(piezas, infoPiezas) {
    //Crea la tabla de piezas trabajadas en la O.T
    console.log(piezas);
    const table = document.getElementById("table");
    const tbody = document.createElement("tbody");
    //Convertir el objeto a un array
    piezas.forEach((pieza, counter) => {
        const tr = document.createElement("tr");
        pieza = orderedArray(pieza);
        //Insertar valores
        Object.keys(pieza).forEach((key) => {
            if (key != "colorPiece") {
                const td = document.createElement("td");
                switch (key) {
                    case "btn_release":
                        if (!pieza[key][1].includes("Incompleto") && pieza[key][0] != 1) {
                            td.appendChild(crearBotonLiberar(infoPiezas, counter, piezas));
                        }
                        break;
                    case "btn_decline":
                        if (pieza[key] != 2) {
                            td.appendChild(crearBotonRechazar(infoPiezas, counter));
                        }
                        break;
                    case "btn_seePiece":
                        td.appendChild(crearBotonVer(infoPiezas, counter, pieza[key]));
                        break;
                    default:
                        td.textContent = pieza[key];
                        if (key === "operator" || key === "errors" || key === "observations" || key === "observacion_liberacion") {
                            td.classList.add("cell-long-text");
                        }
                        break;
                }
                tr.appendChild(td);
            } else {
                tr.style.backgroundColor = pieza[key];
            }
        });
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
}
function asignColorTr(status, error, process) {
    switch (status) {
        case 1:
            return "#79BFED"; // Liberado - Azul
        case 2:
            return "#FF6B6B"; // Rechazado - Rojo
        case 3:
            return "#90EE90"; // Buena sin liberación - Verde
        case 4:
            // Para Soldadura PTA: solo Fundicion bloquea
            if (process === "Soldadura PTA" && !error.toLowerCase().includes("fundicion") && !error.toLowerCase().includes("fundición")) {
                return "#90EE90"; // Verde — defecto no bloqueante para PTA
            }
            return "#DDA0DD"; // Mala sin liberación - Morado
        case 5:
            return "#FFD700"; // Incompleto - Amarillo
        case 0:
        default:
            if (error.includes("Incompleto")) {
                return "#FFD700"; // Incompleto - Amarillo
            } else if (error == "Ninguno") {
                return "#90EE90"; // Buena sin liberación - Verde
            } else {
                // Para Soldadura PTA: solo Fundicion bloquea
                if (process === "Soldadura PTA" && !error.toLowerCase().includes("fundicion") && !error.toLowerCase().includes("fundición")) {
                    return "#90EE90"; // Verde — defecto no bloqueante para PTA
                }
                return "#DDA0DD"; // Mala sin liberación - Morado
            }
    }
}
function orderedArray(array) {
    return {
        class: array["className"],
        workOrder: array[0],
        noAssembly: array[1],
        operator: array[2],
        machine: array[3],
        process: array[4],
        errors: array[5],
        observations: array.observations,
        machinedDate: array[6],
        btn_release: [array[9], array[5]],
        btn_decline: array[9],
        btn_seePiece: array[2],
        colorPiece: asignColorTr(array[9], array[5] ?? "", array[4] ?? ""),
    };
}

function crearBotonLiberar(infoPiezas, i, piezas) {
    const a = document.createElement("a");
    a.className = "btn-liberar";

    let bool;
    if (infoPiezas[i][2] == "Ninguno" && piezas[i][9] != 2) {
        bool = true;
    } else {
        bool = false;
    }

    //Agregar imagen al boton de liberar
    const image = document.createElement("img");
    image.src = window.liberar;
    image.alt = "Liberar";
    image.className = "ver";
    a.appendChild(image);

    let keys = {
        pieza: infoPiezas[i][0],
        proceso: infoPiezas[i][1],
        liberar: true,
        buena: bool,
    };
    // Agregar evento al boton
    a.addEventListener("click", (e) => {
        e.preventDefault;
        create_ObservationsField(keys);
    });
    return a;
}
function crearBotonRechazar(infoPiezas, i) {
    const a = document.createElement("a");
    a.className = "btn-liberar";

    //Agregar imagen al boton de rechazar
    const image = document.createElement("img");
    image.src = window.rechazar;
    image.alt = "Rechazar";
    image.className = "ver";
    a.appendChild(image);

    let keys = {
        pieza: infoPiezas[i][0],
        proceso: infoPiezas[i][1],
        liberar: false,
        buena: false,
    };

    // Agregar evento al boton
    a.addEventListener("click", (e) => {
        e.preventDefault;
        create_ObservationsField(keys);
    });
    return a;
}
function create_ObservationsField(keys) {
    //Creacion del div con efcto blur
    let div_opacity = document.createElement("div");
    div_opacity.className = "div-opacity";
    div_opacity.addEventListener("click", () => {
        div_opacity.remove();
    });

    //Creacion del formulario
    let form = document.createElement("form");
    form.action = window.baseUrl + "/piezasLiberar";
    form.method = "POST";
    form.classList.add("form-liberation");
    form.addEventListener("click", (e) => {
        e.stopPropagation();
    });
    form.appendChild(generateToken());
    createInputsHidden(keys, form);


    //Creacion del textarea
    let textArea = document.createElement("textarea");
    textArea.setAttribute("cols", "50");
    textArea.setAttribute("row", "5");
    textArea.setAttribute(
        "placeholder",
        `Agrega una observación para el juego ${keys.pieza[0].toString().split(/[HMJ]/i)[0]}J de ${keys.proceso} (Opcional)`
    );
    textArea.classList.add("textArea-liberation");
    textArea.setAttribute("name", "observationPiece");

    //Creacion del submit
    let submit = document.createElement("input");
    submit.type = "submit";
    submit.value = keys.liberar ? "Liberar" : "Rechazar";
    submit.classList.add("btn-liberation");
    submit.classList.add(keys.liberar ? "btn-liberate" : "btn-reject");

    form.appendChild(textArea);
    form.appendChild(submit);
    div_opacity.appendChild(form);
    document.body.appendChild(div_opacity);
}
function generateToken() {
    let token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
    let input_token = document.createElement("input");
    input_token.type = "hidden";
    input_token.name = "_token";
    input_token.value = token;
    return input_token;
}
function createInputsHidden(array, form) {
    // Guardar claves del array
    let keysArray = [];
    Object.keys(array).forEach((item) => {
        keysArray.push(item);
    });

    // Crear inputs hidden e insertarlos en el form
    keysArray.forEach((key) => {
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = key;
        input.value = array[key];
        form.appendChild(input);
    });

    let input = document.createElement("input");
    input.type = "hidden";
    input.name = "requestLiberation";
    input.value = "yes";
    form.appendChild(input);
}
function crearBotonVer(infoPiezas, i, usuarios) {
    const a = document.createElement("a");
    a.className = "btn-pza";

    let nPiezas = [];
    for (let j = 0; j < infoPiezas[i][0].length; j++) {
        nPiezas.push(infoPiezas[i][0][j]);
    }
    //INFORMACIÓN DE LAS PIEZAS O PIEZA
    let url = `${window.baseUrl}/pieces/${nPiezas}/${infoPiezas[i][1]}/quality`;
    a.href = url;

    const image = document.createElement("img");
    image.src = window.ojito;
    image.alt = "Ver";
    image.className = "ver";
    a.appendChild(image);
    return a;
}
