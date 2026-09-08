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
    "HG - SS10",
    "HG - SS10CR",
    "HG - SS20",
    "HG - 50V",
    "HG - DUCTIL 654512",
    "SSMF - MINOX",
    "DAMERON",
    "DAMERON - SSMF",
    "1018",
    "4140",
    "INOX 304",
    "INOX 316",
    "INOX 416",
    "ALUMINIO"
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

function createForm() {
    let div_rows = document.querySelector(".div-rows"); //Obtención del div en donde se insertará el formulario
    if (!div_rows) return;
    div_rows.appendChild(createRowsForm(get_inputAttributes(window.workOrder.id, window.molding.nombre)[0])); //Creación del formulario de la clase
    let div_rowsHidden = document.createElement("div");
    div_rowsHidden.className = "div-rows-hidden hidden";
    div_rows.appendChild(div_rowsHidden);

    const isMaster = (window.profile == 3 || window.location.search.includes('from_master=1'));
    const isAdmin = (window.profile == 1 || (!isMaster && window.profile != 5));

    // Si no hay clases registradas aún, activar directamente el modo para agregar la primera clase (perfil Master)
    if (!window.classes || window.classes.length === 0) {
        let checkbox = document.querySelector(".checkbox-add-class");
        if (checkbox) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event("change"));
        } else {
            setOrDelete_ClassButtons(null, true);
            div_rowsHidden.innerHTML = "";
            div_rowsHidden.appendChild(
                createRowsForm(get_inputAttributes(window.workOrder.id, window.molding.nombre)[1])
            );
            showformHidden(true);
        }
    }
}

// Ejecutar la creación del formulario
createForm();

// Funcion para obtener los atributos que se deben de implementar en los inputs del formulario
function get_inputAttributes(workOrder, molding, value = null) {
    let formInputs, formInputsHidden;

    formInputs = {
        workOrder: {
            label: "Orden de trabajo",
            input: {
                type: "text",
                value: workOrder,
                disabled: true,
            },
        },
        molding: {
            label: "Moldura",
            input: {
                type: "text",
                value: molding,
                disabled: true,
            },
        },
        table: {},
    };

    let tamanios = [];
    if (value != null && value.tamanio) {
        tamanios.push(value.tamanio);
        ["Chico", "Mediano", "Grande"].forEach((size) => {
            if (!tamanios.includes(size)) {
                tamanios.push(size);
            }
        });
    } else {
        tamanios = ["Chico", "Mediano", "Grande"];
    }

    if (value == null) {
        // Modo agregar clase nueva
        formInputsHidden = {
            classType: {
                label: "1. Tipo de Clase",
                required: true,
                select: {
                    name: "class",
                    class: "classes",
                    required: true,
                },
                options: CLASS_OPTIONS,
            },
            size: {
                label: "2. Tamaño",
                select: {
                    name: "size",
                    class: "selects",
                },
                options: tamanios,
                currentValue: null,
            },
            tipo_soldadura: {
                label: "3. Tipo de Soldadura",
                select: {
                    name: "tipo_soldadura",
                    class: "selects",
                },
                optionsMap: TIPOS_SOLDADURA_OPTIONS,
                currentValue: "",
            },
            order: {
                label: "4. Cantidad / Pedido",
                required: true,
                input: {
                    type: "number",
                    name: "order",
                    required: true,
                    min: 1,
                    placeholder: "Ingrese su cantidad",
                    value: "",
                },
            },
            pieces: {
                label: "5. Piezas con consignación",
                input: {
                    type: "number",
                    name: "pieces",
                    value: 0,
                },
            },
            startDate: {
                label: "6. Fecha de inicio",
                input: {
                    type: "date",
                    name: "start_date",
                    value: new Date().toISOString().split('T')[0],
                },
            },
            startTime: {
                label: "7. Hora de inicio",
                input: {
                    type: "time",
                    name: "start_time",
                    value: new Date().toTimeString().slice(0, 5),
                },
            },
            finishDate: {
                label: "8. Fecha de termino",
                input: {
                    type: "text",
                    name: "finish_date",
                    disabled: true,
                    value: "-",
                },
            },
            finishTime: {
                label: "9. Hora de termino",
                input: {
                    type: "text",
                    name: "finish_time",
                    disabled: true,
                    value: "-",
                },
            },
            material: {
                label: "10. Material",
                select: {
                    name: "material",
                    class: "selects",
                },
                options: MATERIAL_OPTIONS,
                currentValue: "HG - SS10",
            },
            proveedor_fundicion: {
                label: "11. Proveedor de Fundición",
                select: {
                    name: "proveedor_fundicion",
                    class: "selects",
                },
                optionsMap: [
                    { value: "", label: "-- Sin proveedor / Opcional --" },
                    ...FOUNDRY_PROVIDERS.map((p) => ({ value: p, label: p }))
                ],
                currentValue: "",
            },
        };
    } else {
        // Modo editar clase: SOLO editables Tamaño, Tipo de Soldadura, Fecha de inicio, Hora de inicio
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
                input: {
                    type: "text",
                    name: "class",
                    value: value.nombre,
                    disabled: true,
                },
            },
            size: {
                label: "Tamaño",
                select: {
                    name: "size",
                    class: "selects",
                },
                options: tamanios,
                currentValue: value.tamanio ?? "Chico",
            },
            tipo_soldadura: {
                label: "Tipo de Soldadura",
                select: {
                    name: "tipo_soldadura",
                    class: "selects",
                },
                optionsMap: TIPOS_SOLDADURA_OPTIONS,
                currentValue: value.tipo_soldadura ? String(value.tipo_soldadura) : "",
            },
            order: {
                label: "Cantidad / Pedido",
                input: {
                    type: "number",
                    name: "order",
                    value: value.pedido,
                    disabled: true,
                },
            },
            pieces: {
                label: "Piezas con consignación",
                input: {
                    type: "number",
                    name: "pieces",
                    value: value.piezas ?? 0,
                    disabled: true,
                },
            },
            startDate: {
                label: "Fecha de inicio",
                input: {
                    type: "date",
                    name: "start_date",
                    value: value.fecha_inicio ?? new Date().toISOString().split('T')[0],
                },
            },
            startTime: {
                label: "Hora de inicio",
                input: {
                    type: "time",
                    name: "start_time",
                    value: value.hora_inicio ? value.hora_inicio.slice(0, 5) : new Date().toTimeString().slice(0, 5),
                },
            },
            finishDate: {
                label: "Fecha de termino",
                input: {
                    type: "text",
                    name: "finish_date",
                    disabled: true,
                    value: formatDisplayDate(value.fecha_termino),
                },
            },
            finishTime: {
                label: "Hora de termino",
                input: {
                    type: "text",
                    name: "finish_time",
                    disabled: true,
                    value: formatDisplayTime(value.hora_termino),
                },
            },
            material: {
                label: "Material",
                input: {
                    type: "text",
                    name: "material",
                    value: value.material ?? "-",
                    disabled: true,
                },
            },
            proveedor_fundicion: {
                label: "Proveedor de Fundición",
                input: {
                    type: "text",
                    name: "proveedor_fundicion",
                    value: (value.proveedor && String(value.proveedor).trim() !== "") ? value.proveedor : "-",
                    disabled: true,
                },
            },
        };
    }

    //Eliminacion de valor del id de la clase en el input de tipo hidden
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

function createRowsForm(formInputs) {
    let fragment = document.createDocumentFragment(); //Creación de un fragmento para insertar los elementos del formulario

    const keys = Object.keys(formInputs);
    let inputsCounter = 0; //Contador para los inputs que se van insertando en el formulario

    while (inputsCounter < keys.length) {
        let nameInput = keys[inputsCounter]; //Obtención del nombre del input

        if (nameInput === "table") {
            fragment.appendChild(createScrollableTable(window.classes));
            //Inserción de los elementos correspondientes al checkbox de agregar más clases
            insertWOButtons(fragment);
            fragment.appendChild(createCheckboxAddClass());
            inputsCounter++;
            continue;
        }

        let inputConfig = formInputs[nameInput];

        // Si este campo es fullWidth, crear una fila dedicada de ancho completo
        if (inputConfig.fullWidth) {
            let row = document.createElement("div");
            row.className = "row";

            let col = document.createElement("div");
            col.className = "column full-width-column";


            if (inputConfig.label != undefined) {
                let label = document.createElement("label");
                label.className = "label-form";
                let isRequired = inputConfig.required || (inputConfig.input && inputConfig.input.required);
                if (isRequired) {
                    label.innerHTML = inputConfig.label + ' <span style="color: #dc3545;">*</span>';
                } else {
                    label.textContent = inputConfig.label;
                }
                col.appendChild(label);
            }

            let htmlTag;
            if (nameInput === "composicionQuimica" && inputConfig.hasOwnProperty("options")) {
                htmlTag = createChemicalCompositionChips(inputConfig);
            } else if (nameInput === "composicionQuimica" && inputConfig.isTags) {
                htmlTag = createChemicalCompositionTags(inputConfig.value, inputConfig.tipoSoldadura ?? null);
            } else {
                let element = inputConfig.hasOwnProperty("select") ? "select" : "input";
                htmlTag = createSelectOrInput(element, inputConfig, nameInput);
            }

            col.appendChild(htmlTag);
            row.appendChild(col);
            fragment.appendChild(row);

            inputsCounter++;
            continue;
        }

        let row = document.createElement("div");
        row.className = "row";

        // Crear hasta 2 columnas por fila
        for (let j = 0; j < 2; j++) {
            nameInput = keys[inputsCounter];
            // Guard: salir si no hay más campos o si es tabla
            if (nameInput === undefined || formInputs[nameInput] === undefined) break;
            if (nameInput === "table") break;
            if (formInputs[nameInput].fullWidth) break; // Terminar fila actual si el siguiente es fullWidth

            let col = document.createElement("div");
            col.className = "column";

            //Creación del label correspondiente
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

            //Creación del input correspondiente
            let element = formInputs[nameInput].hasOwnProperty("select") ? "select" : "input";
            let attributesArray = formInputs[nameInput]; //Obtención de los atributos del input correspondiente
            let htmlTag = createSelectOrInput(element, attributesArray, nameInput);

            //Inserción de elementos
            col.appendChild(htmlTag);
            row.appendChild(col);

            inputsCounter++; //Incremento del contador de inputs
        }
        fragment.appendChild(row); //Inserción del div "row" en el fragmento
    }
    return fragment;
}

function insertWOButtons(fragment) {
    let div = document.createElement("div");
    div.className = "container-WOButtons";

    //Creación del botón de generar PDF de la orden de trabajo
    let buttonPDF = document.createElement("a");
    buttonPDF.className = "btn-pdfWO action-btns";
    buttonPDF.textContent = "Generar PDF";
    buttonPDF.href = `../generatePDFWO/${window.workOrder.id}`;

    div.appendChild(buttonPDF);
    fragment.appendChild(div);
}

function getSizeLabel(size) {
    const sizeLabels = {
        "Chico": "CHICO -  Altra 4´´ a 7´´ y Diametro de 4´´ a 5´´",
        "Mediano": "MEDIANO -  Altra 7´´ a 10´ y Diametro de 5´´ a 6´´",
        "Grande": "GRANDE -  Altra 10´´ a 13´´ y Diametro de 6´´ a 8´´"
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
    htmlTag.classList.add("form-control"); //Se añade la clase "form-control al input correspondiente"

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

    //Si el elemento es un select, se añaden las opciones correspondientes
    if (element == "select") {
        if (attributesArray["optionsMap"]) {
            let optionsMap = attributesArray["optionsMap"];
            let currentValue = attributesArray["currentValue"] ?? null;
            optionsMap.forEach((opt) => {
                let option = document.createElement("option");
                option.value = opt.value;
                option.text = opt.label;
                if (currentValue !== null && String(opt.value) === String(currentValue)) {
                    option.selected = true;
                }
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
                if (currentValue && options[i] === currentValue) {
                    option.selected = true;
                }
                htmlTag.add(option);
            }
        }
        if (nameInput == "classType") {
            htmlTag.addEventListener("change", () => {
                showformHidden(true);
            });
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

function modifySelect(className) {
    //Obtención del los elementos del select correspondiente
    let sizeSelect = document.querySelector(".selects");
    if (!sizeSelect || !sizeSelect.previousElementSibling) return;
    let label = sizeSelect.previousElementSibling.textContent;

    //If para verificart si es necesario modificar el select de tamaño dependendiendo del tipo de clase
    let divSelect = removeSelect(sizeSelect); //Recibe como parametro el select que se eliminara
    divSelect.appendChild(createSelect(className)); //Agrega el nuevo select al div padre
}

function removeSelect(select) {
    let parentDiv = select.parentElement; //Obtiene el div padre del select
    select.previousElementSibling.remove(); //Elimina el label del select
    select.remove(); //Elimina el select
    return parentDiv; //Retorna el div padre del select
}

function createSelect(className) {
    let fragment = document.createDocumentFragment(); //Creación de un fragmento para insertar los elementos del formulario

    //Creacion de los elementos
    let label = document.createElement("label");
    let select = document.createElement("select");
    select.className = "selects form-control";

    //Se crea un select con las opciones de tamaño
    label.textContent = "Seleccione el tamaño";
    select.name = "size";

    let options = ["Chico", "Mediano", "Grande"];
    for (let i = 0; i < 3; i++) {
        let option = document.createElement("option");
        option.value = options[i];
        option.text = getSizeLabel(options[i]);
        select.add(option);
    }
    fragment.appendChild(label);
    fragment.appendChild(select);
    return fragment;
}

function createScrollableTable(classes = null) {
    let scrollableTable = document.createElement("div"); //Obtención de la tabla
    scrollableTable.className = "scrollabe-table"; //Clase de la tabla
    if (classes != null) {
        //Si no se reciben las clases, se muestra un mensaje de alerta
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
    let fragment = document.createDocumentFragment(); //Creación de un fragmento para insertar los elementos del formulario

    let table = document.createElement("table"); //Creación de la tabla
    table.className = "table"; //Clase de la tabla

    //Creación de la fila de títulos
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

    //Creación de las filas de clases
    classes.forEach((classArray) => {
        //Se recorren las clases
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

        //Agregar evento al boton
        button.addEventListener("click", function (event) {
            event.preventDefault();

            //Estilos de los botones de la tabla
            let buttons = document.querySelectorAll(".btnClass");
            buttons.forEach((buttonOne) => {
                buttonOne.classList.remove("swo-btn-selected"); buttonOne.classList.add("swo-btn-unselected");
            });
            button.classList.remove("swo-btn-unselected"); button.classList.add("swo-btn-selected");

            //Obtener el valor del boton y mostrar la información de la clase seleccionada
            setClassInfo(classes, button.value);

            // Asignar el ID de la clase seleccionada al input hidden
            let inputClassId = document.getElementById("idClass");
            if (inputClassId) {
                inputClassId.value = button.value;
                inputClassId.setAttribute("value", button.value);
            }

            let checkbox = document.querySelector(".checkbox-add-class");
            if (checkbox && checkbox.checked) {
                checkbox.checked = false;
            }
            showformHidden(true);

            // Buscar la clase seleccionada
            let selectedClass = classes.find((c) => String(c.id) === String(button.value));
            let selectedClassName = selectedClass ? selectedClass.nombre : "";

            // Para Programación de OT (Admin y Master): mostrar casillas de procesos en modo solo lectura (edit = false)
            createOperationsCheckBox(selectedClassName, window.processes ? window.processes[button.value] : null, false);
            setOrDelete_ClassButtons(button.value, false);
        });

        fragment.appendChild(button);
    });
    return fragment;
}

function setOrDelete_ClassButtons(idClass, action) {
    let btn_addClass = document.querySelector(".btn-addClass"); //Obtener el boton de agregar clase
    let containerCheckbox = document.querySelector(".container-checkbox");

    //Eliminar botones de acción anteriores si ya existen
    document.querySelectorAll(".btn-deleteClass, .btn-editClass, #btn-saveClass, #btn-saveProcess").forEach((btn) => btn.remove());

    let div_btns = document.querySelector(".div-btns");

    // En Programación de OT (showWO) no se permite agregar clases
    if (containerCheckbox) {
        containerCheckbox.hidden = true;
        containerCheckbox.classList.add("hidden");
        containerCheckbox.style.display = "none";
    }

    if (action === "edit") {
        if (btn_addClass) {
            btn_addClass.hidden = true;
            btn_addClass.classList.add("hidden");
        }

        let btn_saveProcess = document.createElement("button");
        btn_saveProcess.type = "submit";
        btn_saveProcess.className = "btn-addClass btn";
        btn_saveProcess.id = "btn-saveProcess";
        btn_saveProcess.innerHTML = "Guardar Procesos";
        btn_saveProcess.setAttribute("form", "form");
        btn_saveProcess.disabled = false;
        div_btns.appendChild(btn_saveProcess);
        return;
    }

    if (btn_addClass) {
        btn_addClass.hidden = true;
        btn_addClass.classList.add("hidden");
    }

    if (idClass !== null) {
        // 1. Botón Editar Clase y 2. Botón Eliminar Clase
        createButtons(idClass).forEach((button) => {
            div_btns.appendChild(button);
        });
    }
}

function createButtons(idClass) {
    //Creacion del boton editar
    let btn_editClass = document.createElement("button");
    btn_editClass.className = "btn-editClass action-btns";
    btn_editClass.innerHTML = "Editar Clase";
    btn_editClass.addEventListener("click", function (event) {
        event.preventDefault();
        enableEditClass(idClass);
    });

    //Creacion del boton eliminar clase
    let btn_deleteClass = document.createElement("a");
    btn_deleteClass.className = "btn-deleteClass action-btns";
    btn_deleteClass.innerHTML = "Eliminar Clase";
    btn_deleteClass.href = `../destroyClass/${idClass}`;
    btn_deleteClass.addEventListener("click", function (e) {
        if (!confirm("¿Estás seguro de que deseas eliminar esta clase?")) {
            e.preventDefault();
        }
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
                createRowsForm(
                    get_inputAttributes(window.workOrder.id, window.molding.nombre, selectedClass)[1]
                )
            );
            break;
        }
    }

    if (selectedClass && window.profile != 5) {
        // Habilitar casillas de procesos y máquinas para edición (tanto Admin como Master en Programación de OT)
        createOperationsCheckBox(selectedClass.nombre, window.processes ? window.processes[idClass] : null, true);
    }

    // Asignar el ID de la clase para el envío del formulario
    let inputClassId = document.getElementById("idClass");
    if (inputClassId) {
        inputClassId.value = idClass;
        inputClassId.setAttribute("value", idClass);
    }

    showformHidden(true);
}

function setClassInfo(classesObject = null, classSelected) {
    // Asegurar que el id de clase del formulario oculto está limpio en vista de solo lectura
    let inputClassId = document.getElementById("idClass");
    if (inputClassId) {
        inputClassId.value = "";
        inputClassId.removeAttribute("value");
    }

    //Obtener el div en donde se insertaran los inputs con el valor de la clase seleccionada y eliminar los inputs anteriores
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
                classType: {
                    label: "Clase",
                    input: {
                        type: "text",
                        value: cls.nombre,
                        disabled: true,
                    },
                },
                size: {
                    label: "Tamaño",
                    input: {
                        type: "text",
                        value: cls.tamanio ?? "-",
                        disabled: true,
                    },
                },
                tipo_soldadura: {
                    label: "Tipo de Soldadura",
                    input: {
                        type: "text",
                        value: getTipoSoldaduraLabel(cls.tipo_soldadura),
                        disabled: true,
                    },
                },
                order: {
                    label: "Pedido Total",
                    input: {
                        type: "number",
                        value: cls.pedido,
                        disabled: true,
                    },
                },
                pieces: {
                    label: "Piezas con consignación",
                    input: {
                        type: "number",
                        value: cls.piezas ?? 0,
                        disabled: true,
                    },
                },
                startDate: {
                    label: "Fecha de inicio",
                    input: {
                        type: "text",
                        value: formatDisplayDate(cls.fecha_inicio),
                        disabled: true,
                    },
                },
                startTime: {
                    label: "Hora de inicio",
                    input: {
                        type: "text",
                        value: formatDisplayTime(cls.hora_inicio),
                        disabled: true,
                    },
                },
                finishDate: {
                    label: "Fecha de termino",
                    input: {
                        type: "text",
                        value: formatDisplayDate(cls.fecha_termino),
                        disabled: true,
                    },
                },
                finishTime: {
                    label: "Hora de termino",
                    input: {
                        type: "text",
                        value: formatDisplayTime(cls.hora_termino),
                        disabled: true,
                    },
                },
                material: {
                    label: "Material",
                    input: {
                        type: "text",
                        value: cls.material ?? "-",
                        disabled: true,
                    },
                },
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

function createCheckboxAddClass() {
    let div = document.createElement("div");
    div.className = "container-checkbox";
    // En Programación de OT (showWO) no se permite agregar clases
    div.hidden = true;
    div.classList.add("hidden");
    div.style.display = "none";
    return div;
}

function showformHidden(value) {
    let div_rowsHidden = document.querySelector(".div-rows-hidden");
    if (div_rowsHidden) {
        div_rowsHidden.hidden = !value;
        div_rowsHidden.classList.toggle("hidden", !value);
    }
}

function isClassWithProcesses(className) {
    if (!className) return false;
    let clLower = className.toLowerCase();
    let isExcluded = clLower.includes('base') || clLower.includes('tip') || clLower.includes('roll pin') || clLower.includes('porta') || clLower.includes('pastilla') || clLower.includes('canastilla');
    if (isExcluded) return false;
    return (
        clLower.includes('bombillo') ||
        clLower.includes('molde') ||
        clLower.includes('fondo') ||
        clLower.includes('obturador') ||
        clLower.includes('corona') ||
        clLower.includes('plato') ||
        clLower.includes('embudo') ||
        clLower.includes('cabeza de soplo') ||
        clLower.includes('candado')
    );
}

function createOperationsCheckBox(className, markedProcesses, edit) {
    let div_boxes = document.querySelector(".div-boxes");
    let sections = document.querySelector(".sections");
    if (sections) sections.innerHTML = "";

    if (!isClassWithProcesses(className)) {
        if (div_boxes) {
            div_boxes.hidden = true;
            div_boxes.classList.add("hidden");
            div_boxes.style.display = "none";
        }
        return;
    }

    if (div_boxes) {
        div_boxes.hidden = false;
        div_boxes.classList.remove("hidden");
        div_boxes.style.display = "";
    }

    //Obtener los titulos de los checkbox y su name atraves de arrays
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
    if (!isClassWithProcesses(className)) {
        return [[], []];
    }
    let clLower = (className || "").toLowerCase();

    if (clLower.includes("bombillo") || clLower.includes("molde")) {
        let labelName = clLower.includes("bombillo") ? "Bombillo" : "Molde";
        operations = [
            "Cepillado",
            "Desbaste exterior",
            "Revision Laterales",
            "1ra Operación",
            "Barreno maniobra",
            "2da Operación",
            "Soldadura",
            "Soldadura PTA",
            "Rectificado",
            "Asentado",
            "Calificado",
            "Acabado " + labelName,
            "Barreno profundidad",
            "Cavidades",
            "Copiado",
            "Offset",
            "Palomas",
            "Rebajes",
            "Grabado",
        ];
        operationsArray = [
            "cepillado",
            "desbaste_exterior",
            "revision_laterales",
            "pOperacion",
            "barreno_maniobra",
            "sOperacion",
            "soldadura",
            "soldaduraPTA",
            "rectificado",
            "asentado",
            "calificado",
            "acabado" + labelName,
            "barreno_profundidad",
            "cavidades",
            "copiado",
            "offSet",
            "palomas",
            "rebajes",
            "grabado",
        ];
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
    } else {
        operations = [];
        operationsArray = [];
    }
    return [operations, operationsArray];
}

function crearCasillas(operations, operationsArray, markedProcesses, edit) {
    let sections = document.querySelector(".sections"); //Obtener el div de las secciones

    //Secciones de las casillas
    let section1 = document.createElement("div");
    section1.className = "section1";
    let section2 = document.createElement("div");
    section2.className = "section2";

    for (let i = 0; i < operations.length; i++) {
        //For para la creación de cada una de las casillas
        let div = createProcessBox(operations[i], i + 1, operationsArray[i], markedProcesses, edit);
        //Agregar a las secciones correspondientes
        if (i < parseInt(operations.length / 2)) {
            section1.appendChild(div);
        } else {
            section2.appendChild(div);
        }
    }
    //Inserción de las secciones en el div de las casillas
    sections.appendChild(section1);
    sections.appendChild(section2);
    //Si no se esta editando la clase, se deshabilita el checkbox de seleccionar todo
    if (window.profile != 5) {
        createCheckboxAll(edit);
    }

    // changeStatusSoldaduras(); //Agregar eventos a los checkbox de soldaduras
}

function createProcessBox(operation, processIndex, operationName, markedProcesses, edit) {
    //Creación de un div que sera el contenedor de los elementos del proceso correspondiente
    let div = document.createElement("div");
    div.className = "checkbox-container";

    //Creación de un label para cada checkbox
    let label = document.createElement("label");
    label.className = "checkbox-label";
    label.innerHTML = operation;

    //Creación de un input en donde se insertara el numero de maquinas a utilizar en el proceso correspondiente
    let labelMachine = document.createElement("label");
    labelMachine.textContent = "Máquinas: ";
    labelMachine.classList.add("class", "label-machine");

    let machineInput = document.createElement("input");
    machineInput.type = "number";
    machineInput.name = "machines[]";
    machineInput.className = "input-machine";
    machineInput.id = `process-${processIndex}`;

    //Creación de un checkbox del proceso correspondiente
    let checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.name = "operations[]";
    checkbox.value = operationName;
    checkbox.className = "checkbox";

    //Algoritmo para el desmarcado y deshabilitado de los checkbox y los inputs de las maquinas
    let elements = automateCheckbox(checkbox, machineInput, operationName, markedProcesses, edit);
    checkbox = elements[0];
    machineInput = elements[1];

    //Inserción de los elementos en el div contenedor
    div.appendChild(labelMachine);
    div.appendChild(machineInput);
    div.appendChild(checkbox);
    div.appendChild(label);

    return div;
}

function createCheckboxAll(edit) {
    //Eliminar el checkbox de seleccionar todo si ya existe uno
    let existingDiv = document.querySelector(".div-checkboxAll");
    if (existingDiv != null) {
        existingDiv.remove();
    }

    if (window.profile == 5) return;

    let div_boxes = document.querySelector(".div-boxes");
    if (!div_boxes) return;

    let div = document.createElement("div");
    div.className = "div-checkboxAll";

    let checkbox = document.createElement("input");
    checkbox.type = "checkbox";
    checkbox.className = "checkboxAll";

    let label = document.createElement("label");
    label.className = "checkbox-label";
    label.id = "all-label";
    label.innerHTML = "Seleccionar todo";

    let checkboxes = document.querySelectorAll(".checkbox");
    let allChecked = checkboxes.length > 0 && Array.from(checkboxes).every((cb) => cb.checked);
    checkbox.checked = allChecked;

    if (!edit) {
        checkbox.disabled = true;
    } else {
        checkbox.disabled = false;
        checkbox.addEventListener("change", function () {
            let allCheckboxes = document.querySelectorAll(".checkbox");
            let machineInputs = document.querySelectorAll(".input-machine");
            if (this.checked) {
                machineInputs.forEach((input) => {
                    input.disabled = false;
                    input.classList.remove("swo-input-disabled");
                    input.classList.add("swo-input-enabled");
                    if (!input.value || input.value === "0") input.value = "1";
                });
            } else {
                machineInputs.forEach((input) => {
                    input.disabled = true;
                    input.classList.remove("swo-input-enabled");
                    input.classList.add("swo-input-disabled");
                    input.value = "";
                });
            }
            allCheckboxes.forEach((cb) => {
                cb.checked = this.checked;
            });
        });
    }

    div.appendChild(checkbox);
    div.appendChild(label);
    div_boxes.appendChild(div);
}

function automateCheckbox(checkbox, machineInput, operationName, markedProcesses, edit) {
    checkbox.checked = true;
    machineInput.required = true;
    // //Si el proceso es de soldadura, se muestra desmarcado el checkbox y el input se deshabilita
    // if (operationName == "soldadura" || operationName == "soldaduraPTA") {
    //     checkbox.className = "checkbox-soldaduras";
    //     machineInput.className = "input-machine-soldaduras";
    //     checkbox.checked = false;
    //     machineInput.disabled = true;
    // }

    if (markedProcesses !== null) {
        //Si el proceso ya ha sido seleccionado anteriormente en la clase, se muestra marcado el checkbox y se muestran las maquinas en el input
        checkbox.checked = false;
        machineInput.disabled = true;
        if (markedProcesses !== undefined) {
            if (markedProcesses[operationName] != undefined) {
                checkbox.checked = true;
                machineInput.value = markedProcesses[operationName];
                if (edit) {
                    machineInput.disabled = false;
                }
            }
        }
    }
    if (!edit) {
        //Si no se esta editando la clase, se deshabilita todo (Unicamente se muestran)
        checkbox.disabled = true;
        machineInput.disabled = true;
    }

    //Si se ingresa a la interfaz con el perfil de almacen deshabilitar las casillas de los procesos
    if (window.profile == 5) {
        machineInput.disabled = true;
        checkbox.disabled = true;
        if (markedProcesses == null) {
            // Si el proceso no ha sido seleccionado anteriormente en la clase
            checkbox.checked = false;
        }
    }
    //Agregar eventos a los checkbox
    checkbox.addEventListener("change", function () {
        changeStatusCheckbox(checkbox, machineInput);
    });

    //Agregar los estilos correspondientes a los inputs de las maquinas
    if (machineInput.disabled) {
        machineInput.classList.remove("swo-input-enabled"); machineInput.classList.add("swo-input-disabled");
    }
    return [checkbox, machineInput];
}

function changeStatusCheckbox(checkbox, machineInput) {
    if (checkbox.checked) {
        //Si el checkbox se marca
        machineInput.disabled = false;
        machineInput.classList.remove("swo-input-disabled"); machineInput.classList.add("swo-input-enabled");
    } else {
        //Si el checkbox se desmarca
        machineInput.disabled = true;
        machineInput.classList.remove("swo-input-enabled"); machineInput.classList.add("swo-input-disabled");
        machineInput.value = "";
    }
}

function changeStatusSoldaduras() {
    let checkboxes = document.querySelectorAll(".checkbox-soldaduras");
    let machineInput = document.querySelectorAll(".input-machine-soldaduras");
    // Agregar un evento de cambio a cada checkbox
    checkboxes.forEach((checkbox, index) => {
        checkbox.addEventListener("change", function () {
            // Deshabilitar el input-maq correspondiente según el estado de la checkbox
            machineInput[index].disabled = !checkbox.checked;
            // Desmarcar el otro checkbox cuando uno se selecciona
            checkboxes.forEach((otherCheckbox, otherIndex) => {
                if (otherCheckbox !== checkbox) {
                    otherCheckbox.checked = false;
                    // Deshabilitar el input-maq correspondiente si la checkbox no está marcada
                    machineInput[otherIndex].disabled = !otherCheckbox.checked;
                }
            });
            machineInput.forEach((input) => {
                if (input.disabled) {
                    input.classList.remove("swo-input-enabled"); input.classList.add("swo-input-disabled");
                    input.value = "";
                } else {
                    input.classList.remove("swo-input-disabled"); input.classList.add("swo-input-enabled");
                }
            });
        });
    });
}

function mostrarDiv(route) {
    let div_padre = document.createElement("div");
    div_padre.className = "div-opacity";
    div_padre.id = "div-opacity";

    let div = document.createElement("div");
    div.className = "div-delete";

    let label = document.createElement("label");
    label.className = "label-delete";
    label.innerHTML = route.includes("Class")
        ? "¿Estás seguro de eliminar la clase?"
        : "¿Estás seguro de eliminar la orden de trabajo?";

    let image = document.createElement("img");
    image.className = "img-delete";
    image.src = window.deleteImgUrl;

    let div_cerrar = document.createElement("div");
    div_cerrar.className = "div-cerrar";
    let btn_cerrar = document.createElement("button");
    btn_cerrar.className = "btn-cerrar";
    btn_cerrar.addEventListener("click", function () {
        cerrarDiv();
    });
    let imageCerrar = document.createElement("img");
    imageCerrar.className = "img-cerrar";
    imageCerrar.src = window.cerrarImgUrl;
    btn_cerrar.appendChild(imageCerrar);
    div_cerrar.appendChild(btn_cerrar);

    let a = document.createElement("a");
    a.className = "btn-deleteClass action-btns";
    a.href = route;
    a.innerHTML = "Eliminar";

    div.appendChild(div_cerrar);
    div.appendChild(image);
    div.appendChild(label);
    div.appendChild(a);
    div_padre.appendChild(div);
    return div_padre;
}

function cerrarDiv() {
    let div_padre = document.getElementById("div-opacity");
    div_padre.remove();
}

function modificarSelect() {
    let secciones = document.getElementById("secciones"); //Obtener el div de las casillas
    secciones.innerHTML = ""; //Eliminar las casillas
    crearCheckbox(clase.value, 0, 0, false); //Crear los checkbox de acuerdo a la clase
}

function createChemicalCompositionChips(attributesArray) {
    let wrapper = document.createElement("div");
    wrapper.className = "chemical-composition-wrapper";

    let grid = document.createElement("div");
    grid.className = "chemical-composition-grid";

    let options = attributesArray.options;
    let currentValue = attributesArray.currentValue ?? null;

    // Parsear currentValue respetando grupos con '/':
    // Si un grupo A/B tiene algún elemento que NO es opción predefinida,
    // el grupo completo se trata como composición personalizada (ej: BRONCE/ZINC).
    // Si todos los elementos del grupo son opciones predefinidas, se marcan como chips individuales.
    let activeCompositions = [];  // elementos predefinidos (chips a marcar)
    let customGroups = [];        // grupos personalizados (campo "otro")

    if (currentValue) {
        if (Array.isArray(currentValue)) {
            activeCompositions = currentValue;
        } else {
            // Separar primero por coma (distintas composiciones)
            let commaGroups = currentValue.split(/\s*,\s*/).map(s => s.trim()).filter(Boolean);
            commaGroups.forEach((group) => {
                // Cada grupo puede tener '/' (mezcla)
                let parts = group.split(/\s*\/\s*/).map(s => s.trim()).filter(Boolean);
                let allPredefined = parts.every(p => options.includes(p));
                if (allPredefined) {
                    // Todos son chips predefinidos → marcarlos individualmente
                    parts.forEach(p => activeCompositions.push(p));
                } else {
                    // Hay al menos un elemento no predefinido → mantener el grupo unido
                    customGroups.push(group);
                }
            });
        }
    }

    options.forEach((optionValue) => {
        let label = document.createElement("label");
        label.className = "composition-option";

        let checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.name = "composicion_quimica[]";
        checkbox.value = optionValue;
        checkbox.className = "chemical-composition-input";

        if (window.profile == 5) {
            checkbox.disabled = true;
        }

        let isChecked = activeCompositions.includes(optionValue);
        if (isChecked) {
            checkbox.checked = true;
        }

        let chip = document.createElement("span");
        chip.className = "chemical-composition-chip";
        chip.textContent = optionValue;

        label.appendChild(checkbox);
        label.appendChild(chip);
        grid.appendChild(label);
    });

    wrapper.appendChild(grid);

    // Construir el valor del campo "otro" con los grupos personalizados
    let customValue = customGroups.join(", ");

    // Crear el campo "otro" + selector de Tipo de Soldadura en la misma fila
    let otroContainer = document.createElement("div");
    otroContainer.className = "otro-composition-container";
    otroContainer.classList.add("swo-otro-container");

    let otroLabel = document.createElement("label");
    otroLabel.textContent = "Otro (Especificar composición):";
    otroLabel.classList.add("swo-otro-label");
    otroLabel.hidden = false;

    // Fila que contiene el input "otro" y el selector de tipo de soldadura lado a lado
    let otroInputRow = document.createElement("div");
    otroInputRow.hidden = false;
    otroInputRow.classList.add("swo-otro-row");

    let otroInput = document.createElement("input");
    otroInput.type = "text";
    otroInput.name = "composicion_quimica_otro";
    otroInput.className = "form-control";
    otroInput.classList.add("swo-otro-input");
    otroInput.placeholder = "Separar por comas (ej: COBRE, BRONCE) o con / para mezclas (ej: HG/MINOX)";
    otroInput.value = customValue ? normalizeChemicalInput(customValue) : customValue;

    if (window.profile == 5) {
        otroInput.disabled = true;
    }

    // Normalización en tiempo real: mayúsculas + reglas de / y , (solo admin=1 y master=3)
    if (window.profile == 1 || window.profile == 3) {
        otroInput.addEventListener("input", function () {
            let cursorPos = this.selectionStart;
            let original = this.value;
            let normalized = normalizeChemicalInput(original);
            if (normalized !== original) {
                this.value = normalized;
                let diff = normalized.length - original.length;
                this.setSelectionRange(cursorPos + diff, cursorPos + diff);
            }
        });
    }

    // Selector de Tipo de Soldadura (al lado del input "otro")
    let soldaduraWrapper = document.createElement("div");
    soldaduraWrapper.id = "welding-type-wrapper";
    soldaduraWrapper.hidden = false;
    soldaduraWrapper.classList.add("swo-sol-wrapper");
    soldaduraWrapper.style.marginTop = "15px";
    soldaduraWrapper.style.display = "flex";

    let soldaduraLabel = document.createElement("label");
    soldaduraLabel.textContent = "Tipo de Soldadura:";
    soldaduraLabel.classList.add("swo-sol-label");

    let soldaduraSelect = document.createElement("select");
    soldaduraSelect.name = "tipo_soldadura";
    soldaduraSelect.className = "form-control";
    soldaduraSelect.classList.add("swo-sol-select");

    let defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.text = "-- Seleccionar --";
    soldaduraSelect.add(defaultOption);

    const tiposSoldadura = [
        { value: "1", label: "P1 - 3" },
        { value: "2", label: "P2 - 2.5" },
        { value: "3", label: "P3 - 2" },
        { value: "4", label: "P4 - 1.5" },
    ];
    tiposSoldadura.forEach(({ value, label }) => {
        let opt = document.createElement("option");
        opt.value = value;
        opt.text = label;
        if (attributesArray.tipoSoldadura && String(attributesArray.tipoSoldadura) === value) {
            opt.selected = true;
        }
        soldaduraSelect.add(opt);
    });

    if (window.profile == 5) {
        soldaduraSelect.disabled = true;
    }

    soldaduraWrapper.appendChild(soldaduraLabel);
    soldaduraWrapper.appendChild(soldaduraSelect);

    otroInputRow.appendChild(otroInput);

    otroContainer.appendChild(otroLabel);
    otroContainer.appendChild(otroInputRow);
    
    wrapper.appendChild(otroContainer);
    wrapper.appendChild(soldaduraWrapper);

    return wrapper;
}

function createChemicalCompositionTags(valueString, tipoSoldadura) {
    let container = document.createElement("div");
    container.className = "chemical-composition-tags-container";

    if (!valueString || valueString === "-") {
        let noData = document.createElement("span");
        noData.textContent = "-";
        noData.classList.add("swo-no-data");
        container.appendChild(noData);
    } else {
        let activeCompositions = valueString.split(/\s*\/\s*/);
        activeCompositions.forEach((tagText) => {
            if (!tagText.trim()) return;
            let tag = document.createElement("span");
            tag.className = "chemical-composition-tag";
            tag.textContent = tagText.trim();
            container.appendChild(tag);
        });
    }

    // Separador siempre visible
    let sep = document.createElement("span");
    sep.classList.add("swo-sep");
    sep.textContent = "│";
    container.appendChild(sep);

    let soldaduraWrapper = document.createElement("span");
    soldaduraWrapper.hidden = false;
    soldaduraWrapper.classList.add("swo-sol-wrapper-sm");

    let soldaduraLabelText = document.createElement("span");
    soldaduraLabelText.classList.add("swo-sol-label-sm");
    soldaduraLabelText.textContent = "Tipo de Soldadura:";

    let soldaduraBadge = document.createElement("span");
    soldaduraBadge.className = "chemical-composition-tag";

    const tiposSoldaduraMap = { "1": "P1 - 3", "2": "P2 - 2.5", "3": "P3 - 2", "4": "P4 - 1.5" };
    if (tipoSoldadura) {
        // Badge azul oscuro con el tipo registrado
        soldaduraBadge.classList.add("swo-badge-active");
        soldaduraBadge.textContent = tiposSoldaduraMap[String(tipoSoldadura)] ?? ("Tipo " + tipoSoldadura);
    } else {
        // Badge gris indicando que no hay información
        soldaduraBadge.classList.add("swo-badge-inactive");
        soldaduraBadge.textContent = "Sin información";
    }

    soldaduraWrapper.appendChild(soldaduraLabelText);
    soldaduraWrapper.appendChild(soldaduraBadge);
    container.appendChild(soldaduraWrapper);

    return container;
}

/**
 * Normaliza el texto de composición química:
 * - Convierte todo a mayúsculas
 * - Si hay '/', elimina espacios alrededor (juntar): "HG / MINOX" → "HG/MINOX"
 * - Si hay ',', separa con ", " (separar): "hg,minox" → "HG, MINOX"
 */
function normalizeChemicalInput(value) {
    if (!value) return value;
    // Convertir a mayúsculas
    let result = value.toUpperCase();
    // Normalizar '/': quitar espacios alrededor (juntar elementos)
    result = result.replace(/\s*\/\s*/g, "/");
    // Normalizar ',': asegurar un espacio después de la coma (separar)
    result = result.replace(/\s*,\s*/g, ", ");
    return result;
}

/**
 * Muestra u oculta el selector de tipo de soldadura según la clase seleccionada.
 * Las clases que aplican son: Molde, Fondo, Bombillo, Obturador, Corona.
 */
function toggleWeldingTypeVisibility(className) {
    if (!className) return;
    let clLower = className.toLowerCase();
    const weldingClasses = ["molde", "fondo", "bombillo", "obturador", "corona"];
    let wrapper = document.getElementById("welding-type-wrapper");
    if (!wrapper) return;

    let shouldShow = weldingClasses.some(wc => clLower.includes(wc));
    if (shouldShow) {
        wrapper.hidden = false;
    } else {
        wrapper.hidden = true;
        // Limpiar el selector para no enviar datos residuales
        let select = wrapper.querySelector('select[name="tipo_soldadura"]');
        if (select) {
            select.value = "";
        }
    }
}

// ── Lógica de Polling (Sincronización en tiempo real) ──
if (window.classesDataUrl && window.workOrder && window.workOrder.id) {
    setInterval(async () => {
        try {
            const res = await fetch(`${window.classesDataUrl}/${window.workOrder.id}`);
            if (!res.ok) return;
            const data = await res.json();

            if (data && Array.isArray(data) && window.classes) {
                data.forEach(updatedClass => {
                    // Actualizar el array en memoria
                    const classInMem = window.classes.find(c => c.id == updatedClass.id);
                    if (classInMem) {
                        const currentPedido = parseInt(classInMem.pedido);
                        const currentPiezas = parseInt(classInMem.piezas);

                        if (currentPedido !== updatedClass.pedido || currentPiezas !== updatedClass.piezas) {
                            classInMem.pedido = updatedClass.pedido;
                            classInMem.piezas = updatedClass.piezas;

                            // Actualizar visualmente la tabla
                            const btn = document.querySelector(`.btnClass[value="${updatedClass.id}"]`);
                            if (btn) {
                                const tdPedido = btn.querySelector('.td-pedido');
                                const tdPiezas = btn.querySelector('.td-piezas');
                                if (tdPedido) tdPedido.textContent = updatedClass.pedido;
                                if (tdPiezas) tdPiezas.textContent = updatedClass.piezas;
                            }

                            // Actualizar los inputs si esta clase es la que está abierta actualmente y NO estamos editando
                            const idClassInput = document.getElementById('idClass');
                            if (idClassInput && idClassInput.value == updatedClass.id) {
                                const isEditing = document.getElementById('btn-saveClass') != null;
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
        } catch (e) {
            // Error silencioso de red
        }
    }, 15000);
}
