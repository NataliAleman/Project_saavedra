// ────────────────────────────────────────────────────────────
// show_wo_programacion.js — Lógica exclusiva para la vista
//  de Programación de O.T. (Admin/Programación)
//  Maneja: tamaño, tipo soldadura, fechas, procesos, máquinas.
// ────────────────────────────────────────────────────────────

const CLASS_OPTIONS = [
    "1 - MOLDES",
    "2 - FONDOS",
    "3 - BOMBILLOS",
    "4 - OBTURADORES",
    "5 - EMBUDOS",
    "6 - CORONA",
    "7 - GUIA VIAJERA",
    "8 - GUIA LIMITADORA",
    "9 - CABEZA DE SOPLO",
    "10 - PISTONES",
    "11 - ENFRIADORES",
    "12 - BASES P/OBTURAD",
    "13 - INSERTOS",
    "14 - PIPETAS",
    "15 - PLACAS",
    "16 - CENTRALIZADOR",
    "17 - GAUGE",
    "18 - MOUL",
    "23 - NECKRING",
    "28 - TIP P/ OBTURADOR",
    "29 - CARCAZA",
    "30 - CASQUILLO",
    "31 - RONDANA",
    "32 - LOTE",
    "33 - PLATO MOLDE",
    "34 - PLATO BOMBILLO",
    "35 - 1/2 CAÑA",
    "36 - CAMISA DE",
    "37 - INSERTO DE CARBURO",
    "39 - BIAS UNIT",
    "40 - CUERPO",
    "41 - BLOCK",
    "42 - SEMI",
    "43 - TOP PLATE",
    "44 - POSTIZO",
    "45 - TUBO",
    "46 - FLETE",
    "47 - DOMMI",
    "48 - SERVICIO",
    "61 - ANILLO DE CEDASO 3\"",
    "65 - FUNDICION DE",
    "66 - FUNDICION DE",
    "67 - 1/2 CAÑA LADO MACHO",
    "68 - 1/2 CAÑA LADO",
    "71 - CENTRALIZADOR 3.4",
    "72 - CENTRALIZADOR 3.875",
    "74 - DEDOS",
    "76 - KINKER",
    "79 - LAINA",
    "80 - REPARACION",
    "83 - VARIOS",
    "86 - PERNOS DE",
    "87 - ARRASTRADORES",
    "89 - CADENA INDUSTRIAL",
    "91 - RESORTE",
    "92 - CANDADO",
    "94 - ANILLO",
    "95 - PORTA CORONA",
    "97 - TEJO",
    "99 - BASE PARA PISTON",
    "100 - CANASTILLA PORTA",
    "101 - FABRICACION",
    "102 - BASE PARADORA",
    "103 - BASE PORTA MOLDE",
    "104 - CALIBRADOR",
    "106 - MOLDE SEMI",
    "107 - PLATO MOLDE SEMI",
    "108 - BOMBILLO SEMI",
    "109 - PLATO BOMBILLO",
    "110 - CORONA SEMI",
    "111 - PASTILLAS CORONA",
    "112 - PISTON SEMI",
    "113 - FONDO SEMI",
    "114 - GUIA VIAJERA SEMI",
    "115 - CASQUILLO ALTURA",
    "116 - RONDANA ALUMINIO",
    "117 - SEGURO OMEGA 1",
    "118 - SEGURO OMEGA 2",
    "119 - VALVULA HEXAGONAL",
    "120 - SELLO",
    "121 - ROLL PIN OBTURADOR"
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

const TIPOS_SOLDADURA_OPTIONS = [
    { value: "", label: "-- Sin Soldadura / Opcional --" },
    { value: "1", label: "P1 - 3" },
    { value: "2", label: "P2 - 2.5" },
    { value: "3", label: "P3 - 2" },
    { value: "4", label: "P4 - 1.5" },
];

// ── Estado global: siempre modo Programación (no Master) ──
window.isMasterMode = false;

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
}

createForm();

if (typeof window.validateWOForm === "function") {
    window.validateWOForm();
}

// ────────────────────────────────────────
// Generador de atributos (modo Programación)
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

    let tamanios = [];
    if (value != null && value.tamanio) {
        tamanios.push(value.tamanio);
        ["Chico", "Mediano", "Grande"].forEach((size) => {
            if (!tamanios.includes(size)) tamanios.push(size);
        });
    } else {
        tamanios = ["Chico", "Mediano", "Grande"];
    }

    let formInputsHidden;

    if (value == null) {
        // ── Modo solo lectura general (sin clase seleccionada) ──
        formInputsHidden = {
            classType: {
                label: "1. Tipo de Clase",
                required: true,
                select: { name: "class", class: "classes", required: true },
                options: CLASS_OPTIONS,
            },
            size: {
                label: "2. Tamaño",
                select: { name: "size", class: "selects" },
                options: tamanios,
                currentValue: null,
            },
            tipo_soldadura: {
                label: "3. Tipo de Soldadura",
                select: { name: "tipo_soldadura", class: "selects" },
                optionsMap: TIPOS_SOLDADURA_OPTIONS,
                currentValue: "",
            },
            order: {
                label: "4. Cantidad / Pedido",
                required: true,
                input: { type: "number", name: "order", required: true, min: 1, placeholder: "Ingrese su cantidad", value: "" },
            },
            pieces: {
                label: "5. Piezas con consignación",
                input: { type: "number", name: "pieces", value: 0 },
            },
            startDate: {
                label: "6. Fecha de inicio",
                input: { type: "date", name: "start_date", value: new Date().toISOString().split('T')[0] },
            },
            startTime: {
                label: "7. Hora de inicio",
                input: { type: "time", name: "start_time", value: new Date().toTimeString().slice(0, 5) },
            },
            finishDate: {
                label: "8. Fecha de termino",
                input: { type: "text", name: "finish_date", disabled: true, value: "-" },
            },
            finishTime: {
                label: "9. Hora de termino",
                input: { type: "text", name: "finish_time", disabled: true, value: "-" },
            },
            material: {
                label: "10. Material",
                select: { name: "material", class: "selects" },
                options: MATERIAL_OPTIONS,
                currentValue: "HG - SS10",
            },
            proveedor_fundicion: {
                label: "11. Proveedor de Fundición",
                select: { name: "proveedor_fundicion", class: "selects" },
                optionsMap: [
                    { value: "", label: "-- Sin proveedor / Opcional --" },
                    ...FOUNDRY_PROVIDERS.map((p) => ({ value: p, label: p }))
                ],
                currentValue: "",
            },
        };
    } else {
        // ── Modo editar clase: fechas/horas, tamaño y tipo soldadura editables ──
        let formatDisplayDate = (d) => {
            if (!d || d === "null" || d === "-") return "-";
            let parts = d.split("-");
            if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
            return d;
        };

        let formatDisplayTime = (t) => {
            if (!t || t === "null" || t === "-") return "-";
            try {
                let parts = t.split(":");
                if (parts.length >= 2) {
                    let h = parseInt(parts[0]);
                    let m = parts[1];
                    let ampm = h >= 12 ? "p. m." : "a. m.";
                    let h12 = h % 12 || 12;
                    return `${h12 < 10 ? '0' + h12 : h12}:${m} ${ampm}`;
                }
            } catch (e) {}
            return t;
        };

        formInputsHidden = {
            classType: {
                label: "Clase",
                input: { type: "text", name: "class", value: value.nombre, disabled: true },
            },
            size: {
                label: "Tamaño",
                select: { name: "size", class: "selects" },
                options: tamanios,
                currentValue: value.tamanio ?? "Chico",
            },
            tipo_soldadura: {
                label: "Tipo de Soldadura",
                select: { name: "tipo_soldadura", class: "selects" },
                optionsMap: TIPOS_SOLDADURA_OPTIONS,
                currentValue: value.tipo_soldadura ? String(value.tipo_soldadura) : "",
            },
            order: {
                label: "Cantidad / Pedido",
                input: { type: "number", name: "order", value: value.pedido, disabled: true },
            },
            pieces: {
                label: "Piezas con consignación",
                input: { type: "number", name: "pieces", value: value.piezas ?? 0, disabled: true },
            },
            startDate: {
                label: "Fecha de inicio",
                input: { type: "date", name: "start_date", value: value.fecha_inicio ?? new Date().toISOString().split('T')[0] },
            },
            startTime: {
                label: "Hora de inicio",
                input: { type: "time", name: "start_time", value: value.hora_inicio ? value.hora_inicio.slice(0, 5) : new Date().toTimeString().slice(0, 5) },
            },
            finishDate: {
                label: "Fecha de termino",
                input: { type: "text", name: "finish_date", disabled: true, value: formatDisplayDate(value.fecha_termino) },
            },
            finishTime: {
                label: "Hora de termino",
                input: { type: "text", name: "finish_time", disabled: true, value: formatDisplayTime(value.hora_termino) },
            },
            material: {
                label: "Material",
                input: { type: "text", name: "material", value: value.material ?? "-", disabled: true },
            },
            proveedor_fundicion: {
                label: "Proveedor de Fundición",
                input: {
                    type: "text", name: "proveedor_fundicion",
                    value: (value.proveedor && String(value.proveedor).trim() !== "") ? value.proveedor : "-",
                    disabled: true,
                },
            },
        };
    }

    // Asignar ID al hidden
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
// Construcción del DOM
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

// ── Botón PDF de Programación ──
function insertWOButtons(fragment) {
    let div = document.createElement("div");
    div.className = "container-WOButtons";

    let buttonPDF = document.createElement("a");
    buttonPDF.className = "btn-pdfWO action-btns";
    buttonPDF.textContent = "Generar PDF";
    buttonPDF.href = `../generatePDFWO/${window.workOrder.id}?type=admin`;

    if (!window.classes || window.classes.length === 0) {
        buttonPDF.style.display = "none";
    }

    div.appendChild(buttonPDF);
    fragment.appendChild(div);
}

// ── Tabla de clases ──
function createScrollableTable(classes = null) {
    let scrollableTable = document.createElement("div");
    scrollableTable.className = "scrollabe-table";
    if (classes != null) {
        scrollableTable.appendChild(createTableClasses(classes));
    } else {
        let div_alert = document.createElement("div");
        div_alert.className = "alert alert-danger text-center";
        div_alert.textContent = "Aún no se han registrado clases";
        scrollableTable.appendChild(div_alert);
    }
    return scrollableTable;
}

function createTableClasses(classes) {
    let fragment = document.createDocumentFragment();
    let table = document.createElement("table");
    table.className = "table";

    let headerConfig = [
        { label: "Clase", key: "nombre" },
        { label: "Pedido Total", key: "pedido" },
        { label: "Material", key: "material" },
        { label: "Proveedor Fundición", key: "proveedor" }
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

    classes.forEach((classArray) => {
        let button = document.createElement("button");
        button.value = classArray["id"];
        button.className = "btnClass";

        let fields = [
            { key: "nombre", val: classArray["nombre"] ?? "-" },
            { key: "pedido", val: classArray["pedido"] ?? "-" },
            { key: "material", val: classArray["material"] ?? "-" },
            { key: "proveedor", val: (classArray["proveedor"] && String(classArray["proveedor"]).trim() !== "") ? classArray["proveedor"] : "-" }
        ];

        fields.forEach((field) => {
            let div_td = document.createElement("div");
            div_td.className = "div-td td-" + field.key;
            div_td.textContent = field.val;
            button.appendChild(div_td);
        });

        button.addEventListener("click", function (event) {
            event.preventDefault();

            let buttons = document.querySelectorAll(".btnClass");
            buttons.forEach((b) => { b.classList.remove("swo-btn-selected"); b.classList.add("swo-btn-unselected"); });
            button.classList.remove("swo-btn-unselected");
            button.classList.add("swo-btn-selected");

            setClassInfo(classes, button.value);

            let inputClassId = document.getElementById("idClass");
            if (inputClassId) {
                inputClassId.value = button.value;
                inputClassId.setAttribute("value", button.value);
            }

            let checkbox = document.querySelector(".checkbox-add-class");
            if (checkbox && checkbox.checked) checkbox.checked = false;

            showformHidden(true);

            let selectedClass = classes.find((c) => String(c.id) === String(button.value));
            let selectedClassName = selectedClass ? selectedClass.nombre : "";
            createOperationsCheckBox(selectedClassName, window.processes ? window.processes[button.value] : null, false);
            setOrDelete_ClassButtons(button.value, false);
        });

        fragment.appendChild(button);
    });
    return fragment;
}

// ── Info de clase (solo lectura) ──
function setClassInfo(classesObject = null, classSelected) {
    let inputClassId = document.getElementById("idClass");
    if (inputClassId) { inputClassId.value = ""; inputClassId.removeAttribute("value"); }

    let parentDiv = document.querySelector(".div-rows-hidden");
    parentDiv.innerHTML = "";

    for (let classObject in classesObject) {
        if (classesObject[classObject].id == classSelected) {
            let cls = classesObject[classObject];

            let formatDisplayDate = (d) => {
                if (!d || d === "null" || d === "-") return "-";
                let parts = d.split("-");
                if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
                return d;
            };

            let formatDisplayTime = (t) => {
                if (!t || t === "null" || t === "-") return "-";
                try {
                    let parts = t.split(":");
                    if (parts.length >= 2) {
                        let h = parseInt(parts[0]);
                        let m = parts[1];
                        let ampm = h >= 12 ? "p. m." : "a. m.";
                        let h12 = h % 12 || 12;
                        return `${h12 < 10 ? '0' + h12 : h12}:${m} ${ampm}`;
                    }
                } catch (e) {}
                return t;
            };

            let formInputs = {
                classType: { label: "Clase", input: { type: "text", value: cls.nombre, disabled: true } },
                size: { label: "Tamaño", input: { type: "text", value: cls.tamanio ?? "-", disabled: true } },
                tipo_soldadura: { label: "Tipo de Soldadura", input: { type: "text", value: getTipoSoldaduraLabel(cls.tipo_soldadura), disabled: true } },
                order: { label: "Pedido Total", input: { type: "number", value: cls.pedido, disabled: true } },
                pieces: { label: "Piezas con consignación", input: { type: "number", value: cls.piezas ?? 0, disabled: true } },
                startDate: { label: "Fecha de inicio", input: { type: "text", value: formatDisplayDate(cls.fecha_inicio), disabled: true } },
                startTime: { label: "Hora de inicio", input: { type: "text", value: formatDisplayTime(cls.hora_inicio), disabled: true } },
                finishDate: { label: "Fecha de termino", input: { type: "text", value: formatDisplayDate(cls.fecha_termino), disabled: true } },
                finishTime: { label: "Hora de termino", input: { type: "text", value: formatDisplayTime(cls.hora_termino), disabled: true } },
                material: { label: "Material", input: { type: "text", value: cls.material ?? "-", disabled: true } },
                proveedor_fundicion: { label: "Proveedor de Fundición", input: { type: "text", value: (cls.proveedor && String(cls.proveedor).trim() !== "") ? cls.proveedor : "-", disabled: true } },
            };
            parentDiv.appendChild(createRowsForm(formInputs));
            break;
        }
    }
}

// ── Botones de acción ──
function setOrDelete_ClassButtons(idClass, action) {
    let btn_addClass = document.querySelector(".btn-addClass:not(#btn-saveProcess):not(#btn-saveClass)");
    let containerCheckbox = document.querySelector(".container-checkbox");

    document.querySelectorAll(".btn-deleteClass, .btn-editClass, #btn-saveClass, #btn-saveProcess").forEach((btn) => btn.remove());

    let div_btns = document.querySelector(".div-btns");

    if (containerCheckbox) {
        containerCheckbox.hidden = true;
        containerCheckbox.classList.add("hidden");
        containerCheckbox.style.display = "none";
    }

    if (action === "edit") {
        if (btn_addClass) { btn_addClass.hidden = true; btn_addClass.classList.add("hidden"); }

        let btn_save = document.createElement("button");
        btn_save.type = "submit";
        btn_save.className = "btn-addClass btn swo-btn-saveProcess";
        btn_save.id = "btn-saveProcess";
        btn_save.setAttribute("form", "form");
        btn_save.innerHTML = "Actualizar Clase";
        btn_save.disabled = true;
        btn_save.style.opacity = "0.5";
        btn_save.style.cursor = "not-allowed";
        div_btns.appendChild(btn_save);

        let btn_cancel = document.createElement("button");
        btn_cancel.type = "button";
        btn_cancel.className = "btn-deleteClass action-btns";
        btn_cancel.id = "btn-cancelEdit";
        btn_cancel.innerHTML = "Cancelar";
        btn_cancel.addEventListener("click", function (e) {
            e.preventDefault();
            let activeRow = document.querySelector(".swo-btn-selected");
            if (activeRow) { activeRow.click(); } else { window.location.reload(); }
        });
        div_btns.appendChild(btn_cancel);
        return;
    }

    if (btn_addClass) { btn_addClass.hidden = true; btn_addClass.classList.add("hidden"); }

    if (idClass !== null) {
        createButtons(idClass).forEach((button) => div_btns.appendChild(button));
    }
}

function createButtons(idClass) {
    let btn_editClass = document.createElement("button");
    btn_editClass.className = "btn-editClass action-btns";
    btn_editClass.innerHTML = "Editar Clase";
    btn_editClass.addEventListener("click", function (event) {
        event.preventDefault();
        enableEditClass(idClass);
    });

    let btn_deleteClass = document.createElement("a");
    btn_deleteClass.className = "btn-deleteClass action-btns";
    btn_deleteClass.innerHTML = "Eliminar Clase";
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

    let selectedClass = null;
    for (let classObject in window.classes) {
        if (window.classes[classObject].id == idClass) {
            selectedClass = window.classes[classObject];
            div_rowsHidden.appendChild(
                createRowsForm(get_inputAttributes(window.workOrder.id, window.molding.nombre, selectedClass)[1])
            );
            break;
        }
    }

    if (selectedClass && window.profile != 5) {
        let className = selectedClass.nombre;
        createOperationsCheckBox(className, window.processes ? window.processes[idClass] : null, true);
        toggleWeldingTypeVisibility(className);
    }

    let inputClassId = document.getElementById("idClass");
    if (inputClassId) {
        inputClassId.value = idClass;
        inputClassId.setAttribute("value", idClass);
    }

    storeOriginalFormValues();
    showformHidden(true);
}

function showformHidden(value) {
    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    let div_boxes = document.querySelector(".div-boxes");

    // En Programación el panel de procesos se oculta si cerramos todo (value=false)
    // El "mostrarlo" se delega a createOperationsCheckBox según si la clase tiene o no procesos.
    if (div_boxes && !value) {
        div_boxes.hidden = true;
        div_boxes.classList.add("hidden");
        div_boxes.style.display = "none";
    }

    if (div_rowsHidden) {
        div_rowsHidden.hidden = !value;
        div_rowsHidden.classList.toggle("hidden", !value);
    }
}

function createCheckboxAddClass() {
    let div = document.createElement("div");
    div.className = "container-checkbox";
    div.hidden = true;
    div.classList.add("hidden");
    div.style.display = "none";
    return div;
}

// ── Input / Select builder ──
function getSizeLabel(size) {
    const sizeLabels = {
        "Chico":   "CHICO -  Altra 4´´ a 7´´ y Diametro de 4´´ a 5´´",
        "Mediano": "MEDIANO -  Altra 7´´ a 10´ y Diametro de 5´´ a 6´´",
        "Grande":  "GRANDE -  Altra 10´´ a 13´´ y Diametro de 6´´ a 8´´"
    };
    return sizeLabels[size] || size;
}

function getTipoSoldaduraLabel(val) {
    const map = { "1": "P1 - 3", "2": "P2 - 2.5", "3": "P3 - 2", "4": "P4 - 1.5" };
    return map[String(val)] ?? (val ? "Tipo " + val : "Sin información");
}

function createSelectOrInput(element, attributesArray, nameInput) {
    let htmlTag = document.createElement(element);
    for (let attribute in attributesArray[element]) {
        if (attribute === "disabled") {
            htmlTag.disabled = Boolean(attributesArray[element][attribute]);
        } else {
            htmlTag.setAttribute(attribute, attributesArray[element][attribute]);
        }
    }
    if (window.profile == 5 && nameInput != "order" && nameInput != "pieces") {
        htmlTag.disabled = true;
    }
    if (window.profile != 5 && nameInput == "pieces") {
        htmlTag.disabled = true;
        if (htmlTag.value === "" || htmlTag.value === "null" || htmlTag.value == null) {
            htmlTag.value = 0;
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
            if (currentValue && !options.includes(currentValue)) {
                let customOpt = document.createElement("option");
                customOpt.value = currentValue;
                customOpt.text = currentValue;
                customOpt.selected = true;
                htmlTag.add(customOpt);
            }
            for (let i = 0; i < options.length; i++) {
                let option = document.createElement("option");
                option.value = options[i];
                option.text = nameInput === "size" ? getSizeLabel(options[i]) : options[i];
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
// Procesos / Casillas (Panel derecho)
// ────────────────────────────────────────
function isClassWithProcesses(className) {
    if (!className) return false;
    let clLower = className.toLowerCase();
    let isExcluded = clLower.includes('base') || clLower.includes('tip') || clLower.includes('roll pin') || clLower.includes('porta') || clLower.includes('pastilla') || clLower.includes('canastilla');
    if (isExcluded) return false;
    return (
        clLower.includes('bombillo') || clLower.includes('molde') || clLower.includes('fondo') ||
        clLower.includes('obturador') || clLower.includes('corona') || clLower.includes('plato') ||
        clLower.includes('embudo') || clLower.includes('cabeza de soplo') || clLower.includes('candado')
    );
}

function createOperationsCheckBox(className, markedProcesses, edit) {
    let div_boxes = document.querySelector(".div-boxes");
    let sections = document.querySelector(".sections");
    if (sections) sections.innerHTML = "";

    if (!isClassWithProcesses(className)) {
        if (div_boxes) { div_boxes.hidden = true; div_boxes.classList.add("hidden"); div_boxes.style.display = "none"; }
        return;
    }

    if (div_boxes) { div_boxes.hidden = false; div_boxes.classList.remove("hidden"); div_boxes.style.display = ""; }

    let operations = get_operationsArray(className);
    let operationsArray = operations[1];
    operations = operations[0];

    if (operations.length > 0) {
        crearCasillas(operations, operationsArray, markedProcesses, edit);
    }
}

function get_operationsArray(className) {
    let operations = [];
    let operationsArray = [];
    if (!isClassWithProcesses(className)) return [[], []];

    let clLower = (className || "").toLowerCase();

    if (clLower.includes("bombillo") || clLower.includes("molde")) {
        let labelName = clLower.includes("bombillo") ? "Bombillo" : "Molde";
        operations = ["Cepillado", "Desbaste exterior", "Revision Laterales", "1ra Operación", "Barreno maniobra", "2da Operación", "Soldadura", "Soldadura PTA", "Rectificado", "Asentado", "Calificado", "Acabado " + labelName, "Barreno profundidad", "Cavidades", "Copiado", "Offset", "Palomas", "Rebajes", "Grabado"];
        operationsArray = ["cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabado" + labelName, "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado"];
    } else if (clLower.includes("obturador") || clLower.includes("fondo")) {
        operations = ["1ra y 2da Operación Equipo", "Soldadura", "Soldadura PTA"];
        operationsArray = ["operacionEquipo", "soldadura", "soldaduraPTA"];
    } else if (clLower.includes("corona")) {
        operations = ["Cepillado", "Desbaste exterior", "1ra Operacion", "2da Operacion", "Soldadura", "Soldadura PTA", "Rectificado", "Asentado", "Calificado"];
        operationsArray = ["cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado"];
    } else if (clLower.includes("plato")) {
        operations = ["Barreno Maniobra", "1ra y 2da Operación Equipo"];
        operationsArray = ["barreno_maniobra", "operacionEquipo"];
    } else if (clLower.includes("embudo")) {
        operations = ["1ra y 2da Operación Equipo", "Embudo C.M."];
        operationsArray = ["operacionEquipo", "embudoCM"];
    } else if (clLower.includes("cabeza de soplo")) {
        operations = ["Primera Operacion", "Segunda Operacion"];
        operationsArray = ["primeraOperacionCabezaSoplo", "segundaOperacionCabezaSoplo"];
    } else if (clLower.includes("candado")) {
        operations = ["1ra y 2da Operación Equipo"];
        operationsArray = ["operacionEquipo"];
    }
    return [operations, operationsArray];
}

function crearCasillas(operations, operationsArray, markedProcesses, edit) {
    let sections = document.querySelector(".sections");
    let section1 = document.createElement("div"); section1.className = "section1";
    let section2 = document.createElement("div"); section2.className = "section2";

    for (let i = 0; i < operations.length; i++) {
        let div = createProcessBox(operations[i], i + 1, operationsArray[i], markedProcesses, edit);
        if (i < parseInt(operations.length / 2)) {
            section1.appendChild(div);
        } else {
            section2.appendChild(div);
        }
    }
    sections.appendChild(section1);
    sections.appendChild(section2);

    if (window.profile != 5) createCheckboxAll(edit);
}

function createProcessBox(operation, processIndex, operationName, markedProcesses, edit) {
    let div = document.createElement("div"); div.className = "checkbox-container";
    let label = document.createElement("label"); label.className = "checkbox-label"; label.innerHTML = operation;
    let labelMachine = document.createElement("label"); labelMachine.textContent = "Máquinas: "; labelMachine.classList.add("class", "label-machine");
    let machineInput = document.createElement("input"); machineInput.type = "number"; machineInput.name = "machines[]"; machineInput.className = "input-machine"; machineInput.id = `process-${processIndex}`;
    let checkbox = document.createElement("input"); checkbox.type = "checkbox"; checkbox.name = "operations[]"; checkbox.value = operationName; checkbox.className = "checkbox";

    let elements = automateCheckbox(checkbox, machineInput, operationName, markedProcesses, edit);
    checkbox = elements[0]; machineInput = elements[1];

    div.appendChild(labelMachine); div.appendChild(machineInput); div.appendChild(checkbox); div.appendChild(label);
    return div;
}

function createCheckboxAll(edit) {
    let existingDiv = document.querySelector(".div-checkboxAll");
    if (existingDiv != null) existingDiv.remove();
    if (window.profile == 5) return;

    let div_boxes = document.querySelector(".div-boxes");
    if (!div_boxes) return;

    let div = document.createElement("div"); div.className = "div-checkboxAll";
    let checkbox = document.createElement("input"); checkbox.type = "checkbox"; checkbox.className = "checkboxAll";
    let label = document.createElement("label"); label.className = "checkbox-label"; label.id = "all-label"; label.innerHTML = "Seleccionar todo"; label.style.pointerEvents = "none";

    div.addEventListener("click", function (e) {
        if (!edit) return;
        if (e.target !== checkbox) { checkbox.checked = !checkbox.checked; checkbox.dispatchEvent(new Event("change")); }
    });

    let checkboxes = document.querySelectorAll(".checkbox");
    checkbox.checked = checkboxes.length > 0 && Array.from(checkboxes).every((cb) => cb.checked);

    if (!edit) {
        checkbox.disabled = true;
    } else {
        checkbox.disabled = false;
        checkbox.addEventListener("change", function () {
            let allCheckboxes = document.querySelectorAll(".checkbox");
            let machineInputs = document.querySelectorAll(".input-machine");
            if (this.checked) {
                machineInputs.forEach((input) => { input.disabled = false; input.classList.remove("swo-input-disabled"); input.classList.add("swo-input-enabled"); if (!input.value || input.value === "0") input.value = "1"; });
            } else {
                machineInputs.forEach((input) => { input.disabled = true; input.classList.remove("swo-input-enabled"); input.classList.add("swo-input-disabled"); input.value = ""; });
            }
            allCheckboxes.forEach((cb) => { cb.checked = this.checked; });
            if (typeof window.validateWOForm === 'function') window.validateWOForm();
        });
    }

    div.appendChild(checkbox); div.appendChild(label);
    div_boxes.appendChild(div);
}

function automateCheckbox(checkbox, machineInput, operationName, markedProcesses, edit) {
    checkbox.checked = true; machineInput.required = true; machineInput.value = "1";

    if (markedProcesses !== null) {
        checkbox.checked = false; machineInput.disabled = true; machineInput.value = "";
        if (markedProcesses !== undefined) {
            if (markedProcesses[operationName] != undefined) {
                checkbox.checked = true;
                machineInput.value = markedProcesses[operationName];
                if (edit) machineInput.disabled = false;
            }
        }
    }
    if (!edit) { checkbox.disabled = true; machineInput.disabled = true; }
    if (window.profile == 5) {
        machineInput.disabled = true; checkbox.disabled = true;
        if (markedProcesses == null) checkbox.checked = false;
    }

    checkbox.addEventListener("change", function () { changeStatusCheckbox(checkbox, machineInput); });

    if (machineInput.disabled) { machineInput.classList.remove("swo-input-enabled"); machineInput.classList.add("swo-input-disabled"); }
    return [checkbox, machineInput];
}

function changeStatusCheckbox(checkbox, machineInput) {
    if (checkbox.checked) {
        machineInput.disabled = false;
        machineInput.classList.remove("swo-input-disabled"); machineInput.classList.add("swo-input-enabled");
        if (!machineInput.value || machineInput.value === "0") machineInput.value = "1";
    } else {
        machineInput.disabled = true;
        machineInput.classList.remove("swo-input-enabled"); machineInput.classList.add("swo-input-disabled");
        machineInput.value = "";
    }
    if (typeof window.validateWOForm === 'function') window.validateWOForm();
}

function toggleWeldingTypeVisibility(className) {
    if (!className) return;
    let clLower = className.toLowerCase();
    const weldingClasses = ["molde", "fondo", "bombillo", "obturador", "corona"];
    let wrapper = document.getElementById("welding-type-wrapper");
    if (!wrapper) return;
    let shouldShow = weldingClasses.some(wc => clLower.includes(wc));
    if (shouldShow) { wrapper.hidden = false; } else {
        wrapper.hidden = true;
        let select = wrapper.querySelector('select[name="tipo_soldadura"]');
        if (select) select.value = "";
    }
}

// ────────────────────────────────────────
// Validación del formulario
// ────────────────────────────────────────
window.validateWOForm = function () {
    let btn_saveProcess = document.getElementById("btn-saveProcess");
    let btn_addClass = document.querySelector(".btn-addClass:not(#btn-saveProcess):not(#btn-saveClass)");
    let targetBtn = btn_saveProcess || btn_addClass;

    if (!targetBtn) return;

    let allValid = true;
    let hasChanges = false;

    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    if (div_rowsHidden) {
        let requiredElements = div_rowsHidden.querySelectorAll("[required]");
        requiredElements.forEach(el => { if (!el.value || el.value.trim() === "") allValid = false; });

        let allInputs = div_rowsHidden.querySelectorAll("input.form-control, select.form-control");
        allInputs.forEach(el => {
            if (el.dataset.originalValue !== undefined && el.value !== el.dataset.originalValue) hasChanges = true;
        });
    }

    let div_boxes = document.querySelector(".div-boxes");
    if (div_boxes && !div_boxes.classList.contains("hidden")) {
        let checkboxes = div_boxes.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            if (cb.dataset.originalChecked !== undefined && (cb.checked ? "true" : "false") !== cb.dataset.originalChecked) hasChanges = true;
        });
        let machineInputs = div_boxes.querySelectorAll('.input-machine');
        machineInputs.forEach(input => {
            if (input.dataset.originalValue !== undefined && (input.value || "") !== input.dataset.originalValue) hasChanges = true;
        });
    }

    let isEditing = btn_saveProcess != null;

    if (allValid && (!isEditing || hasChanges)) {
        targetBtn.disabled = false; targetBtn.style.opacity = "1"; targetBtn.style.cursor = "pointer";
    } else {
        targetBtn.disabled = true; targetBtn.style.opacity = "0.5"; targetBtn.style.cursor = "not-allowed";
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
            el.addEventListener('input', window.validateWOForm);
            el.addEventListener('change', window.validateWOForm);
        });
    }

    let div_boxes = document.querySelector('.div-boxes');
    if (div_boxes) {
        let checkboxes = div_boxes.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(cb => {
            cb.dataset.originalChecked = cb.checked ? "true" : "false";
            cb.addEventListener('change', window.validateWOForm);
        });
        let inputs = div_boxes.querySelectorAll('.input-machine');
        inputs.forEach(input => {
            input.dataset.originalValue = input.value || "";
            input.addEventListener('input', window.validateWOForm);
            input.addEventListener('change', window.validateWOForm);
        });
    }
}

// ── Polling ──
if (window.classesDataUrl && window.workOrder && window.workOrder.id) {
    setInterval(async () => {
        try {
            const res = await fetch(`${window.classesDataUrl}/${window.workOrder.id}`);
            if (!res.ok) return;
            const data = await res.json();
            if (data && Array.isArray(data) && window.classes) {
                data.forEach(updatedClass => {
                    const classInMem = window.classes.find(c => c.id == updatedClass.id);
                    if (classInMem) {
                        const currentPedido = parseInt(classInMem.pedido);
                        const currentPiezas = parseInt(classInMem.piezas);
                        if (currentPedido !== updatedClass.pedido || currentPiezas !== updatedClass.piezas) {
                            classInMem.pedido = updatedClass.pedido;
                            classInMem.piezas = updatedClass.piezas;
                            const btn = document.querySelector(`.btnClass[value="${updatedClass.id}"]`);
                            if (btn) {
                                const tdPedido = btn.querySelector('.td-pedido');
                                if (tdPedido) tdPedido.textContent = updatedClass.pedido;
                            }
                            const idClassInput = document.getElementById('idClass');
                            if (idClassInput && idClassInput.value == updatedClass.id) {
                                const isEditing = document.getElementById('btn-saveProcess') != null;
                                if (!isEditing) {
                                    const inputOrder = document.querySelector('input[name="order"]');
                                    const inputPieces = document.querySelector('input[name="pieces"]');
                                    if (inputOrder) inputOrder.value = updatedClass.pedido;
                                    if (inputPieces) inputPieces.value = updatedClass.piezas;
                                }
                            }
                        }
                    }
                });
            }
        } catch (e) {}
    }, 15000);
}
