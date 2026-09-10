// ────────────────────────────────────────────────────────────
// show_wo_master.js — Lógica exclusiva para la vista Master
//  (Gestión de Clases: nombre, cantidad, material, proveedor)
// Este archivo NO maneja procesos, fechas ni máquinas.
// ────────────────────────────────────────────────────────────

const CLASS_OPTIONS = [
    "1 - MOLDES", "2 - FONDOS", "3 - BOMBILLOS", "4 - OBTURADORES",
    "5 - EMBUDOS", "6 - CORONA", "7 - GUIA VIAJERA", "8 - GUIA LIMITADORA",
    "9 - CABEZA DE SOPLO", "10 - PISTONES", "11 - ENFRIADORES",
    "12 - BASES P/OBTURAD", "13 - INSERTOS", "14 - PIPETAS", "15 - PLACAS",
    "16 - CENTRALIZADOR", "17 - GAUGE", "18 - MOUL", "23 - NECKRING",
    "28 - TIP P/ OBTURADOR", "29 - CARCAZA", "30 - CASQUILLO", "31 - RONDANA",
    "32 - LOTE", "33 - PLATO MOLDE", "34 - PLATO BOMBILLO", "35 - 1/2 CAÑA",
    "36 - CAMISA DE", "37 - INSERTO DE CARBURO", "39 - BIAS UNIT",
    "40 - CUERPO", "41 - BLOCK", "42 - SEMI", "43 - TOP PLATE", "44 - POSTIZO",
    "45 - TUBO", "46 - FLETE", "47 - DOMMI", "48 - SERVICIO",
    "61 - ANILLO DE CEDASO 3\"", "65 - FUNDICION DE", "66 - FUNDICION DE",
    "67 - 1/2 CAÑA LADO MACHO", "68 - 1/2 CAÑA LADO",
    "71 - CENTRALIZADOR 3.4", "72 - CENTRALIZADOR 3.875", "74 - DEDOS",
    "76 - KINKER", "79 - LAINA", "80 - REPARACION", "83 - VARIOS",
    "86 - PERNOS DE", "87 - ARRASTRADORES", "89 - CADENA INDUSTRIAL",
    "91 - RESORTE", "92 - CANDADO", "94 - ANILLO", "95 - PORTA CORONA",
    "97 - TEJO", "99 - BASE PARA PISTON", "100 - CANASTILLA PORTA",
    "101 - FABRICACION", "102 - BASE PARADORA", "103 - BASE PORTA MOLDE",
    "104 - CALIBRADOR", "106 - MOLDE SEMI", "107 - PLATO MOLDE SEMI",
    "108 - BOMBILLO SEMI", "109 - PLATO BOMBILLO", "110 - CORONA SEMI",
    "111 - PASTILLAS CORONA", "112 - PISTON SEMI", "113 - FONDO SEMI",
    "114 - GUIA VIAJERA SEMI", "115 - CASQUILLO ALTURA", "116 - RONDANA ALUMINIO",
    "117 - SEGURO OMEGA 1", "118 - SEGURO OMEGA 2", "119 - VALVULA HEXAGONAL",
    "120 - SELLO", "121 - ROLL PIN OBTURADOR"
];

const MATERIAL_OPTIONS = [
    "HG - SS10", "HG - SS10CR", "HG - SS20", "HG - 50V",
    "HG - DUCTIL 654512", "SSMF - MINOX", "DAMERON", "DAMERON - SSMF",
    "1018", "4140", "INOX 304", "INOX 316", "INOX 416", "ALUMINIO"
];

const FOUNDRY_PROVIDERS = [
    "SS Metal Foundry, S. de R. L. de C. V.",
    "SOCIEDAD COOPERATIVA DE PRODUCCIÓN JACARANDAS",
    "EXTERNO"
];

// ── Estado global: siempre modo Master ──
window.isMasterMode = true;

// ────────────────────────────────────────
// Inicialización del formulario
// ────────────────────────────────────────
function createForm() {
    let div_rows = document.querySelector(".div-rows");
    if (!div_rows) return;

    div_rows.appendChild(createRowsForm(get_inputAttributes(window.workOrder.id, window.molding.nombre)[0]));

    let div_rowsHidden = document.createElement("div");
    div_rowsHidden.className = "div-rows-hidden hidden";
    div_rows.appendChild(div_rowsHidden);

    // Ya no se abre el formulario automáticamente. El usuario debe pulsar "Agregar Nueva Clase"
}

createForm();

// ────────────────────────────────────────
// Abrir formulario de nueva clase
// ────────────────────────────────────────
function openNewClassForm() {
    // Deseleccionar cualquier clase activa
    document.querySelectorAll(".btnClass").forEach(b => {
        b.classList.remove("swo-btn-selected");
        b.classList.add("swo-btn-unselected");
    });

    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    div_rowsHidden.innerHTML = "";

    let inputClassId = document.getElementById("idClass");
    if (inputClassId) { inputClassId.value = ""; inputClassId.removeAttribute("value"); }

    div_rowsHidden.appendChild(
        createRowsForm(get_inputAttributes(window.workOrder.id, window.molding.nombre)[1])
    );
    showformHidden(true);

    // Ocultar Editar/Eliminar; mostrar Guardar Clase
    document.querySelectorAll(".btn-deleteClass, .btn-editClass, #btn-saveClass, #btn-saveProcess").forEach(b => b.remove());

    let btn_addClass = document.querySelector(".btn-addClass:not(#btn-saveProcess):not(#btn-saveClass)");
    if (btn_addClass) {
        btn_addClass.textContent = "Guardar Clase";
        btn_addClass.hidden = false;
        btn_addClass.classList.remove("hidden");
        btn_addClass.disabled = true;
        btn_addClass.style.opacity = "0.5";
        btn_addClass.style.cursor = "not-allowed";
    }

    // Ocultar botón de Agregar Nueva Clase al abrir su formulario
    let btn_openNewClass = document.getElementById("btn-openNewClass");
    if (btn_openNewClass) {
        btn_openNewClass.style.display = "none";
    }

    // Cancelar
    let div_btns = document.querySelector(".div-btns");
    let existingCancel = document.getElementById("btn-cancelNew");
    if (!existingCancel) {
        let btn_cancel = document.createElement("button");
        btn_cancel.type = "button";
        btn_cancel.className = "btn-deleteClass action-btns";
        btn_cancel.id = "btn-cancelNew";
        btn_cancel.textContent = "Cancelar";
        btn_cancel.addEventListener("click", function (e) {
            e.preventDefault();
            showformHidden(false);
            div_rowsHidden.innerHTML = "";
            let inputClassId2 = document.getElementById("idClass");
            if (inputClassId2) { inputClassId2.value = ""; inputClassId2.removeAttribute("value"); }
            resetBtnsToDefault();

            // Volver a mostrar el botón de Agregar Nueva Clase
            let btn_openNewClass2 = document.getElementById("btn-openNewClass");
            if (btn_openNewClass2) {
                btn_openNewClass2.style.display = ""; // restablece el estilo original
            }
        });
        div_btns.appendChild(btn_cancel);
    }

    if (typeof window.validateWOForm === "function") window.validateWOForm();

    document.addEventListener("input", window.validateWOForm, { once: false });
    document.addEventListener("change", window.validateWOForm, { once: false });
}

// Restablecer botones al estado inicial (sin clase seleccionada)
function resetBtnsToDefault() {
    document.querySelectorAll(".btn-deleteClass, .btn-editClass, #btn-saveClass, #btn-saveProcess, #btn-cancelNew").forEach(b => b.remove());
    let btn_addClass = document.querySelector(".btn-addClass:not(#btn-saveProcess):not(#btn-saveClass)");
    if (btn_addClass) {
        btn_addClass.hidden = true;
        btn_addClass.classList.add("hidden");
    }

    // Asegurar que el botón de Agregar Nueva Clase vuelva a aparecer siempre que se restablece la vista
    let btn_openNewClass = document.getElementById("btn-openNewClass");
    if (btn_openNewClass) {
        btn_openNewClass.style.display = ""; 
    }
}

// ────────────────────────────────────────
// Generador de atributos de formulario (modo Master)
// ────────────────────────────────────────
function get_inputAttributes(workOrder, molding, value = null) {
    let formInputs = {
        workOrder: {
            label: "Orden de trabajo",
            input: { type: "text", value: workOrder, disabled: true },
        },
        molding: {
            label: "Moldura",
            input: { type: "text", value: molding, disabled: true },
        },
        table: {},
    };

    let formInputsHidden;

    if (value == null) {
        // ── Modo agregar nueva clase ──
        formInputsHidden = {
            classType: {
                label: "Tipo de Clase",
                required: true,
                select: { name: "class", class: "classes", required: true },
                optionsMap: [
                    { value: "", label: "-- Seleccione Clase --" },
                    ...CLASS_OPTIONS.map((c) => ({ value: c, label: c }))
                ],
                currentValue: "",
            },
            order: {
                label: "Cantidad",
                required: true,
                input: { type: "number", name: "order", required: true, min: 1, placeholder: "Ingrese su cantidad", value: "" },
            },
            material: {
                label: "Material",
                required: true,
                select: { name: "material", class: "selects", required: true },
                optionsMap: [
                    { value: "", label: "-- Seleccione Material --" },
                    ...MATERIAL_OPTIONS.map((m) => ({ value: m, label: m }))
                ],
                currentValue: "",
            },
            proveedor_fundicion: {
                label: "Proveedor de Fundición",
                required: true,
                select: { name: "proveedor_fundicion", class: "selects", required: true },
                optionsMap: [
                    { value: "", label: "-- Seleccione Proveedor --" },
                    ...FOUNDRY_PROVIDERS.map((p) => ({ value: p, label: p }))
                ],
                currentValue: "",
            },
        };
    } else {
        // ── Modo editar clase ──
        formInputsHidden = {
            classType: {
                label: "Tipo de Clase",
                required: true,
                select: { name: "class", class: "classes", required: true },
                optionsMap: [
                    { value: "", label: "-- Seleccione Clase --" },
                    ...CLASS_OPTIONS.map((c) => ({ value: c, label: c }))
                ],
                currentValue: value.nombre,
            },
            order: {
                label: "Cantidad",
                required: true,
                input: { type: "number", name: "order", required: true, min: 1, value: value.pedido },
            },
            material: {
                label: "Material",
                required: true,
                select: { name: "material", class: "selects", required: true },
                optionsMap: [
                    { value: "", label: "-- Seleccione Material --" },
                    ...MATERIAL_OPTIONS.map((m) => ({ value: m, label: m }))
                ],
                currentValue: value.material ?? "",
            },
            proveedor_fundicion: {
                label: "Proveedor de Fundición",
                required: true,
                select: { name: "proveedor_fundicion", class: "selects", required: true },
                optionsMap: [
                    { value: "", label: "-- Seleccione Proveedor --" },
                    ...FOUNDRY_PROVIDERS.map((p) => ({ value: p, label: p }))
                ],
                currentValue: value.proveedor ?? "",
            },
        };
    }

    // Asignar el ID de clase al input hidden
    let inputClassId = document.getElementById("idClass");
    if (inputClassId) {
        if (value != null) {
            inputClassId.setAttribute("value", value.id);
            inputClassId.value = value.id;
        } else {
            inputClassId.value = "";
            inputClassId.removeAttribute("value");
        }
    }

    return [formInputs, formInputsHidden];
}

// ────────────────────────────────────────
// Construcción del formulario en el DOM
// ────────────────────────────────────────
function createRowsForm(formInputs) {
    let fragment = document.createDocumentFragment();
    const keys = Object.keys(formInputs);
    let inputsCounter = 0;

    while (inputsCounter < keys.length) {
        let nameInput = keys[inputsCounter];

        if (nameInput === "table") {
            fragment.appendChild(createScrollableTable(window.classes));
            insertWOButtons(fragment);
            fragment.appendChild(createCheckboxAddClass());
            inputsCounter++;
            continue;
        }

        let row = document.createElement("div");
        row.className = "row";

        for (let j = 0; j < 2; j++) {
            nameInput = keys[inputsCounter];
            if (nameInput === undefined || formInputs[nameInput] === undefined) break;
            if (nameInput === "table") break;

            let col = document.createElement("div");
            col.className = "column";

            if (formInputs[nameInput].label != undefined) {
                let label = document.createElement("label");
                label.className = "label-form";
                let isRequired = formInputs[nameInput].required || (formInputs[nameInput].input && formInputs[nameInput].input.required);
                if (isRequired) {
                    label.innerHTML = formInputs[nameInput].label + ' <span style="color: #dc3545;">*</span>';
                } else {
                    label.textContent = formInputs[nameInput].label;
                }
                col.appendChild(label);
            }

            let element = formInputs[nameInput].hasOwnProperty("select") ? "select" : "input";
            let htmlTag = createSelectOrInput(element, formInputs[nameInput], nameInput);

            col.appendChild(htmlTag);
            row.appendChild(col);
            inputsCounter++;
        }
        fragment.appendChild(row);
    }
    return fragment;
}

// ────────────────────────────────────────
// Botones: Generar PDF + Agregar Nueva Clase
// ────────────────────────────────────────
function insertWOButtons(fragment) {
    let div = document.createElement("div");
    div.className = "container-WOButtons";

    // ── Botón PDF ──
    let buttonPDF = document.createElement("a");
    buttonPDF.className = "btn-pdfWO action-btns";
    buttonPDF.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
             style="vertical-align: middle; margin-right: 4px;">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
            <polyline points="10 9 9 9 8 9"></polyline>
        </svg>Generar PDF`;
    buttonPDF.href = `../generatePDFWO/${window.workOrder.id}?type=master`;
    buttonPDF.style.display = "inline-flex";
    buttonPDF.style.alignItems = "center";

    // ── Botón Agregar Nueva Clase ──
    let buttonAdd = document.createElement("button");
    buttonAdd.type = "button";
    buttonAdd.className = "btn-addNewClass";
    buttonAdd.id = "btn-openNewClass";
    buttonAdd.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>Agregar Nueva Clase`;
    buttonAdd.addEventListener("click", function (e) {
        e.preventDefault();
        openNewClassForm();
    });

    if (!window.classes || window.classes.length === 0) {
        buttonPDF.style.display = "none";
    }

    div.appendChild(buttonPDF);
    div.appendChild(buttonAdd);
    fragment.appendChild(div);
}

// ────────────────────────────────────────
// Tabla de clases con diseño premium
// ────────────────────────────────────────
function createScrollableTable(classes = null) {
    let scrollableTable = document.createElement("div");
    scrollableTable.className = "scrollabe-table";
    if (classes != null && classes.length > 0) {
        scrollableTable.appendChild(createTableClasses(classes));
    } else {
        // Estado vacío elegante
        let emptyDiv = document.createElement("div");
        emptyDiv.style.cssText = `
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 32px 16px; color: #94a3b8; text-align: center; gap: 10px;`;
        emptyDiv.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none"
                 stroke="#cbd5e1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                <line x1="8" y1="21" x2="16" y2="21"></line>
                <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
            <span style="font-size: 0.88em; font-weight: 600;">Aún no hay clases registradas</span>
            <span style="font-size: 0.78em;">Usa el botón <strong style="color:#057a35;">Agregar Nueva Clase</strong> para comenzar</span>`;
        scrollableTable.appendChild(emptyDiv);
    }
    return scrollableTable;
}

function createTableClasses(classes) {
    let fragment = document.createDocumentFragment();

    // ── Cabecera (table con th) ──
    let table = document.createElement("table");
    table.className = "table";

    let headerConfig = [
        { label: "Clase", key: "nombre" },
        { label: "Pedido", key: "pedido" },
        { label: "Material", key: "material" },
        { label: "Proveedor de Fundición", key: "proveedor" }
    ];
    let tr = document.createElement("tr");
    headerConfig.forEach((item) => {
        let th = document.createElement("th");
        th.textContent = item.label;
        th.className = "t-title th-" + item.key;
        tr.appendChild(th);
    });
    table.appendChild(tr);
    fragment.appendChild(table);

    // ── Filas de clase ──
    classes.forEach((classArray) => {
        let button = document.createElement("button");
        button.value = classArray["id"];
        button.className = "btnClass";
        button.type = "button";

        let fields = [
            { key: "nombre",   val: classArray["nombre"]   ?? "-" },
            { key: "pedido",   val: classArray["pedido"]   ?? "-" },
            { key: "material", val: classArray["material"] ?? "-" },
            {
                key: "proveedor",
                val: (classArray["proveedor"] && String(classArray["proveedor"]).trim() !== "")
                     ? classArray["proveedor"] : "-"
            }
        ];

        fields.forEach((field) => {
            let div_td = document.createElement("div");
            div_td.className = "div-td td-" + field.key;
            div_td.textContent = field.val;
            button.appendChild(div_td);
        });

        button.addEventListener("click", function (event) {
            event.preventDefault();

            document.querySelectorAll(".btnClass").forEach((b) => {
                b.classList.remove("swo-btn-selected");
                b.classList.add("swo-btn-unselected");
            });
            button.classList.remove("swo-btn-unselected");
            button.classList.add("swo-btn-selected");

            setClassInfo(classes, button.value);

            let inputClassId = document.getElementById("idClass");
            if (inputClassId) {
                inputClassId.value = button.value;
                inputClassId.setAttribute("value", button.value);
            }

            showformHidden(true);
            setOrDelete_ClassButtons(button.value, false);
        });

        fragment.appendChild(button);
    });
    return fragment;
}

// ────────────────────────────────────────
// Información de la clase seleccionada (lectura)
// ────────────────────────────────────────
function setClassInfo(classesObject = null, classSelected) {
    let inputClassId = document.getElementById("idClass");
    if (inputClassId) { inputClassId.value = ""; inputClassId.removeAttribute("value"); }

    let parentDiv = document.querySelector(".div-rows-hidden");
    parentDiv.innerHTML = "";

    for (let classObject in classesObject) {
        if (classesObject[classObject].id == classSelected) {
            let cls = classesObject[classObject];
            let formInputs = {
                classType: { label: "Clase",    input: { type: "text",   value: cls.nombre,  disabled: true } },
                order:     { label: "Cantidad",  input: { type: "number", value: cls.pedido,  disabled: true } },
                material:  { label: "Material",  input: { type: "text",   value: cls.material ?? "-", disabled: true } },
                proveedor_fundicion: {
                    label: "Proveedor de Fundición",
                    input: {
                        type: "text",
                        value: (cls.proveedor && String(cls.proveedor).trim() !== "") ? cls.proveedor : "-",
                        disabled: true,
                    },
                },
            };
            parentDiv.appendChild(createRowsForm(formInputs));
            break;
        }
    }
}

// ────────────────────────────────────────
// Botones de acción (editar / eliminar / guardar)
// ────────────────────────────────────────
function setOrDelete_ClassButtons(idClass, action) {
    let btn_addClass = document.querySelector(".btn-addClass:not(#btn-saveProcess):not(#btn-saveClass)");
    let containerCheckbox = document.querySelector(".container-checkbox");

    document.querySelectorAll(".btn-deleteClass, .btn-editClass, #btn-saveClass, #btn-saveProcess, #btn-cancelNew").forEach((btn) => btn.remove());

    let div_btns = document.querySelector(".div-btns");

    if (containerCheckbox) {
        containerCheckbox.hidden = true;
        containerCheckbox.classList.add("hidden");
        containerCheckbox.style.display = "none";
    }

    // ── Modo edición: muestra Guardar + Cancelar ──
    if (action === "edit") {
        if (btn_addClass) { btn_addClass.hidden = true; btn_addClass.classList.add("hidden"); }

        let btn_save = document.createElement("button");
        btn_save.type = "submit";
        btn_save.className = "btn-addClass btn swo-btn-saveClass";
        btn_save.id = "btn-saveClass";
        btn_save.setAttribute("form", "form");
        btn_save.textContent = "Guardar Modificaciones";
        btn_save.disabled = true;
        btn_save.style.opacity = "0.5";
        btn_save.style.cursor = "not-allowed";
        div_btns.appendChild(btn_save);

        let btn_cancel = document.createElement("button");
        btn_cancel.type = "button";
        btn_cancel.className = "btn-deleteClass action-btns";
        btn_cancel.id = "btn-cancelEdit";
        btn_cancel.textContent = "Cancelar";
        btn_cancel.addEventListener("click", function (e) {
            e.preventDefault();
            let activeRow = document.querySelector(".swo-btn-selected");
            if (activeRow) { activeRow.click(); } else { window.location.reload(); }
        });
        div_btns.appendChild(btn_cancel);
        return;
    }

    // ── Modo normal: oculta el submit, muestra Editar / Eliminar ──
    if (btn_addClass) { btn_addClass.hidden = true; btn_addClass.classList.add("hidden"); }

    if (idClass !== null) {
        createButtons(idClass).forEach((button) => div_btns.appendChild(button));
    }
}

function createButtons(idClass) {
    let btn_editClass = document.createElement("button");
    btn_editClass.className = "btn-editClass action-btns";
    btn_editClass.type = "button";
    btn_editClass.textContent = "Editar Clase";
    btn_editClass.addEventListener("click", function (event) {
        event.preventDefault();
        enableEditClass(idClass);
    });

    let btn_deleteClass = document.createElement("a");
    btn_deleteClass.className = "btn-deleteClass action-btns";
    btn_deleteClass.textContent = "Eliminar Clase";
    btn_deleteClass.href = `../destroyClass/${idClass}`;
    btn_deleteClass.addEventListener("click", function (e) {
        if (!confirm("¿Estás seguro de que deseas eliminar esta clase?")) e.preventDefault();
    });

    return [btn_editClass, btn_deleteClass];
}

function enableEditClass(idClass) {
    setOrDelete_ClassButtons(idClass, "edit");

    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    div_rowsHidden.innerHTML = "";

    // Marca edición parcial (solo campos Master)
    let partialInput = document.createElement("input");
    partialInput.type = "hidden";
    partialInput.name = "partial_edit";
    partialInput.value = "1";
    div_rowsHidden.appendChild(partialInput);

    for (let classObject in window.classes) {
        if (window.classes[classObject].id == idClass) {
            let selectedClass = window.classes[classObject];
            div_rowsHidden.appendChild(
                createRowsForm(get_inputAttributes(window.workOrder.id, window.molding.nombre, selectedClass)[1])
            );
            break;
        }
    }

    let inputClassId = document.getElementById("idClass");
    if (inputClassId) { inputClassId.value = idClass; inputClassId.setAttribute("value", idClass); }

    storeOriginalFormValues();
    showformHidden(true);
}

function showformHidden(value) {
    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    if (div_rowsHidden) {
        div_rowsHidden.hidden = !value;
        div_rowsHidden.classList.toggle("hidden", !value);
    }
}

// ── Placeholder legacy (oculto) ──
function createCheckboxAddClass() {
    let div = document.createElement("div");
    div.className = "container-checkbox";
    div.hidden = true;
    div.classList.add("hidden");
    div.style.display = "none";
    return div;
}

// ────────────────────────────────────────
// Input / Select builder
// ────────────────────────────────────────
function createSelectOrInput(element, attributesArray, nameInput) {
    let htmlTag = document.createElement(element);
    for (let attribute in attributesArray[element]) {
        if (attribute === "disabled") {
            htmlTag.disabled = Boolean(attributesArray[element][attribute]);
        } else {
            htmlTag.setAttribute(attribute, attributesArray[element][attribute]);
        }
    }
    htmlTag.classList.add("form-control");

    if (element === "input" && attributesArray.input && attributesArray.input.disabled && attributesArray.input.name) {
        let frag = document.createDocumentFragment();
        let hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = attributesArray.input.name;
        hidden.value = attributesArray.input.value ?? "";
        frag.appendChild(htmlTag);
        frag.appendChild(hidden);
        return frag;
    }

    if (element === "select") {
        if (attributesArray["optionsMap"]) {
            let currentValue = attributesArray["currentValue"] ?? null;
            attributesArray["optionsMap"].forEach((opt) => {
                let option = document.createElement("option");
                option.value = opt.value;
                option.text = opt.label;
                if (currentValue !== null && String(opt.value) === String(currentValue)) option.selected = true;
                htmlTag.add(option);
            });
        } else {
            let options = attributesArray["options"] || [];
            let currentValue = attributesArray["currentValue"] ?? null;
            for (let i = 0; i < options.length; i++) {
                let option = document.createElement("option");
                option.value = options[i];
                option.text = options[i];
                if (currentValue && options[i] === currentValue) option.selected = true;
                htmlTag.add(option);
            }
        }

        if (attributesArray.select && attributesArray.select.disabled && attributesArray.select.name) {
            let frag = document.createDocumentFragment();
            let hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = attributesArray.select.name;
            hidden.value = attributesArray.currentValue ?? htmlTag.value ?? "";
            frag.appendChild(htmlTag);
            frag.appendChild(hidden);
            return frag;
        }
    }
    return htmlTag;
}

// ────────────────────────────────────────
// Validación del formulario
// ────────────────────────────────────────
window.validateWOForm = function () {
    let btn_saveClass = document.getElementById("btn-saveClass");
    let btn_addClass = document.querySelector(".btn-addClass:not(#btn-saveProcess):not(#btn-saveClass)");
    let targetBtn = btn_saveClass || btn_addClass;

    if (!targetBtn || targetBtn.hidden) return;

    let allValid = true;
    let hasChanges = false;

    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    if (div_rowsHidden && !div_rowsHidden.classList.contains("hidden")) {
        let requiredElements = div_rowsHidden.querySelectorAll("[required]");
        requiredElements.forEach(el => {
            if (!el.value || el.value.trim() === "") allValid = false;
        });

        let allInputs = div_rowsHidden.querySelectorAll("input.form-control, select.form-control");
        allInputs.forEach(el => {
            if (el.dataset.originalValue !== undefined && el.value !== el.dataset.originalValue) hasChanges = true;
        });
    }

    let isEditing = btn_saveClass != null;

    if (allValid && (!isEditing || hasChanges)) {
        targetBtn.disabled = false;
        targetBtn.style.opacity = "1";
        targetBtn.style.cursor = "pointer";
    } else {
        targetBtn.disabled = true;
        targetBtn.style.opacity = "0.5";
        targetBtn.style.cursor = "not-allowed";
    }
};

document.addEventListener("DOMContentLoaded", function () {
    let form = document.getElementById("form");
    if (form) {
        form.addEventListener("input", window.validateWOForm);
        form.addEventListener("change", window.validateWOForm);
    }
    window.validateWOForm();
});

function storeOriginalFormValues() {
    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    if (div_rowsHidden) {
        let allInputs = div_rowsHidden.querySelectorAll("input.form-control, select.form-control");
        allInputs.forEach(el => {
            el.dataset.originalValue = el.value || "";
            el.addEventListener("input", window.validateWOForm);
            el.addEventListener("change", window.validateWOForm);
        });
    }
}

// ────────────────────────────────────────
// Polling: actualización en tiempo real
// ────────────────────────────────────────
if (window.classesDataUrl && window.workOrder && window.workOrder.id) {
    setInterval(async () => {
        try {
            const res = await fetch(`${window.classesDataUrl}/${window.workOrder.id}`);
            if (!res.ok) return;
            const data = await res.json();
            if (data && Array.isArray(data) && window.classes) {
                data.forEach(updatedClass => {
                    const classInMem = window.classes.find(c => c.id == updatedClass.id);
                    if (classInMem && parseInt(classInMem.pedido) !== updatedClass.pedido) {
                        classInMem.pedido = updatedClass.pedido;
                        const btn = document.querySelector(`.btnClass[value="${updatedClass.id}"]`);
                        if (btn) {
                            const tdPedido = btn.querySelector(".td-pedido");
                            if (tdPedido) tdPedido.textContent = updatedClass.pedido;
                        }
                    }
                });
            }
        } catch (e) {
            // Error silencioso de red
        }
    }, 15000);
}
