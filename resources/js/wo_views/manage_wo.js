const urlParams = new URLSearchParams(window.location.search);
const profile = urlParams.get('almacen_only') === '1' ? 5 : document.getElementById("wo-profile").value;
createButtonsAdd_Select();

function createButtonsAdd_Select() {
    let div_bttns = document.querySelector(".div-bttns");
    let fragment = document.createDocumentFragment(); //Crear un fragmento de documento vacio
    let bttnText = {
        add: "Crear OT",
        select: "Seleccionar OT",
    };

    for (let name in bttnText) {
        // Si es Admin, Master o Almacén, ocultamos los botones ya que se auto-carga la selección y no pueden crear.
        if (profile == 1 || profile == 3 || profile == 5) {
            continue;
        }

        let bttn = document.createElement("button");
        bttn.id = `bttn-${name}`;
        bttn.className = "bttns-add-select";
        bttn.textContent = bttnText[name];

        //Agregar eventos a los botones
        bttn.addEventListener("click", function (event) {
            event.preventDefault();
            let form = document.querySelector(".form");
            if (form.childElementCount > 0) {
                form.removeChild(form.lastChild);
            }
            form.appendChild(
                createDiv(name, window.workOrders, window.moldings)
            );
        });
        fragment.appendChild(bttn);
    }
    div_bttns.appendChild(fragment);

    // Si es admin (1), almacén (5) o master (3), auto-cargar la vista de seleccionar OT
    if (profile == 1 || profile == 5 || profile == 3) {
        let form = document.querySelector(".form");
        if (form) {
            form.appendChild(createDiv("select", window.workOrders, window.moldings));
        }
    }
}

function createButtonAccept() {
    let div = document.createElement("div");
    div.className = "div-button text-center pt-3 text-muted";
    let button = document.createElement("button");
    button.className = "btn btn-block text-center my-3";
    button.type = "submit";
    button.textContent = "Aceptar";
    div.appendChild(button);
    return div;
}

function createDiv(value, workOrders, moldings = null) {
    //Crear un fragmento de documento vacio
    let fragment = document.createDocumentFragment();

    //Creacion de div
    let div = document.createElement("div");
    div.className = "container-form";

    //Creacion del div del formulario
    let divForm = document.createElement("div");
    divForm.className = "form-group py-2";
    //Creacion de row
    let row = document.createElement("div");
    row.className = "row";

    //Creacion de col
    let errorState = {
        error: false,
    };
    let form = designformOutline(value, workOrders, moldings, errorState);
    row.appendChild(form);
    divForm.appendChild(row);
    div.appendChild(divForm);

    //Insetar boton para enviar el formulario
    if (!errorState.error) {
        div.appendChild(createButtonAccept());
    }
    fragment.appendChild(div);

    return fragment;
}

function designformOutline(value, workOrders, moldings, errorState) {
    let fragment = document.createDocumentFragment();
    let formOutline;
    //Si se quiere seleccionar una OT
    if (value == "select") {
        formOutline = selectWorkOrder(workOrders, errorState);
    } else {
        //Si se quiere agregar una OT
        formOutline = addWorkOrder(moldings, errorState);
    }
    fragment.appendChild(formOutline);
    return fragment;
}

function selectWorkOrder(workOrders, errorState) {
    //Creacion de col
    let col = document.createElement("div");
    col.className = "col-md-12 mb-2";

    //Creacion de form-outline
    let formOutline = document.createElement("div");
    formOutline.className = "form-outline";

    if (workOrders != null && workOrders.length > 0) {
        let select = document.createElement("select");
        select.className = "form-control";
        select.id = "workOrders";
        select.name = "workOrderSelected";

        for (let workOrder in workOrders) {
            let option = document.createElement("option");
            option.value = workOrders[workOrder]["workOrder"];
            option.textContent = `${workOrders[workOrder]["workOrder"]} - ${workOrders[workOrder]["molding"]}`;
            select.appendChild(option);
        }
        formOutline.appendChild(select);
    } else {
        errorState.error = true;
        let div_alert = document.createElement("div");
        div_alert.className = "alert alert-danger";
        let i = document.createElement("i");
        i.className = "fas fa-check";
        i.textContent = "No hay ordenes de trabajo disponibles";
        div_alert.appendChild(i);
        formOutline.appendChild(div_alert);
    }
    return formOutline;
}

function addWorkOrder(moldings, errorState) {
    let fragment = document.createDocumentFragment();
    for (let i = 0; i < 2; i++) {
        //Creacion de col
        let col = document.createElement("div");
        col.className = "col-md-6 mb-3";
        let formOutline = document.createElement("div");
        formOutline.className = "form-outline";
        let label = document.createElement("label");
        label.className = "form-label";
        if (i == 0) {
            //Si es la primera iteracion se crea el select de las molduras
            label.textContent = "Selecciona una moldura";
            if (moldings.length > 0) {
                let select = document.createElement("select");
                select.id = "moldings";
                select.className = "form-control";
                select.name = "moldingSelected";
                moldings.forEach((molding) => {
                    let option = document.createElement("option");
                    option.value = molding["id"];
                    option.textContent = molding["nombre"];
                    select.appendChild(option);
                });

                let label = document.createElement("label");
                label.textContent = "Moldura";
                label.classList.add("form-label");
                formOutline.appendChild(select);
                formOutline.appendChild(label);
            } else {
                errorState.error = true;
                let div_alert = document.createElement("div");
                div_alert.className = "alert alert-danger";
                let i = document.createElement("i");
                i.className = "fas fa-check";
                i.textContent = "No hay molduras disponibles";
                div_alert.appendChild(i);
                formOutline.appendChild(div_alert);
            }
        } else {
            //Si es la segunda iteracion se crea el input para agregar la orden de trabajo
            if (moldings.length > 0) {
                label.textContent = "Orden de trabajo";
                let input = document.createElement("input");
                input.className = "form-control";
                input.type = "number";
                input.name = "workOrderAdded";
                input.required = true;
                formOutline.appendChild(input);
                formOutline.appendChild(label);
            }
        }
        col.appendChild(formOutline);
        fragment.appendChild(col);
    }
    return fragment;
}
