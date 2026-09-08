@extends('layouts.appMenu')

@section('head')
<title>Crear o Modificar Orden de Trabajo (Master)</title>
<link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
@vite(['resources/css/wo_views/create_master_wo.css', 'resources/js/wo_views/create_master_wo.js'])
@endsection

@section('content')
@section('background-body', 'background-image:url("' . asset("images/fondoLogin.jpg") . '")')

@php
    $mode = request('mode') === 'modify' ? 'modify' : 'create';
    $selectedOtId = request('ot_id');
@endphp

<div class="wrapper-master">
    <div class="header-master">
        <img src="{{ asset('images/lg_saavedra.png') }}" class="lg-saavedra rounded-4" alt="Grupo Industrial Saavedra" />
        <h2>Crear o Modificar Orden de Trabajo (Master)</h2>
        <p>Seleccione una opción para dar de alta una nueva Orden de Trabajo o modificar los datos y cantidades de una existente.</p>
    </div>

    @include('layouts.partials.messages')

    <div class="master-mode-selector">
        <button type="button" class="btn-mode {{ $mode === 'create' ? 'active' : '' }}" id="btn-mode-create">Crear Orden de Trabajo</button>
        <button type="button" class="btn-mode {{ $mode === 'modify' ? 'active' : '' }}" id="btn-mode-modify">Modificar Orden de Trabajo</button>
    </div>

    <!-- FORMULARIO 1: CREAR ORDEN DE TRABAJO -->
    <form action="{{ route('storeMasterWO') }}" method="POST" id="form-create-master-wo" style="{{ $mode === 'modify' ? 'display: none;' : '' }}">
        @csrf
        <div class="form-grid">
            <!-- 1. Orden de Trabajo (OT) -->
            <div class="form-field">
                <label for="workOrder">1. Orden de Trabajo (OT) <span class="text-danger">*</span></label>
                <input type="text" name="workOrder" id="workOrder" required placeholder="Ej. 4520" maxlength="5" pattern="[0-9]{1,5}" value="{{ old('workOrder') }}">
            </div>

            <!-- 2. Nombre del Producto / Moldura -->
            <div class="form-field">
                <label for="moldingSelected">2. Nombre del Producto / Moldura <span class="text-danger">*</span></label>
                <select name="moldingSelected" id="moldingSelected" required>
                    <option value="" disabled selected>Seleccione la moldura o producto</option>
                    @foreach($moldings as $molding)
                        <option value="{{ $molding->id }}" {{ old('moldingSelected') == $molding->id ? 'selected' : '' }}>
                            {{ $molding->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Fecha de Compra -->
            <div class="form-field">
                <label for="fecha_compra">3. Fecha de Compra <span class="text-danger">*</span></label>
                <input type="date" name="fecha_compra" id="fecha_compra" required value="{{ old('fecha_compra') }}">
            </div>

            <!-- 4. Orden de Compra -->
            <div class="form-field">
                <label for="orden_compra">4. Orden de Compra <span class="text-danger">*</span></label>
                <input type="text" name="orden_compra" id="orden_compra" required placeholder="Ej. OC-9912" maxlength="25" pattern="[A-Za-z0-9-]{1,25}" value="{{ old('orden_compra') }}">
            </div>

            <!-- 5. Cliente -->
            <div class="form-field">
                <label for="cliente">5. Cliente <span class="text-danger">*</span></label>
                <select name="cliente" id="cliente" required>
                    <option value="" disabled {{ old('cliente') ? '' : 'selected' }}>Seleccione el cliente</option>
                    @foreach(['SLP', 'FEVISA', 'SAVERGLASS', 'MEXICALI', 'GLASS & GLASS', 'VETRO', 'L. VERACRUZ'] as $clienteOption)
                        <option value="{{ $clienteOption }}" {{ old('cliente') == $clienteOption ? 'selected' : '' }}>
                            {{ $clienteOption }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- 6. F. Comprometida con el Cliente -->
            <div class="form-field">
                <label for="fecha_entrega_cliente">6. F. Comprometida con el Cliente <span class="text-danger">*</span></label>
                <input type="date" name="fecha_entrega_cliente" id="fecha_entrega_cliente" required value="{{ old('fecha_entrega_cliente') }}">
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-save-master">Crear Orden de Trabajo</button>
            </div>
        </div>
    </form>

    <!-- FORMULARIO 2: MODIFICAR ORDEN DE TRABAJO -->
    <form action="{{ route('updateMasterWO') }}" method="POST" id="form-update-master-wo" style="{{ $mode === 'create' ? 'display: none;' : '' }}">
        @csrf
        <div class="form-grid form-grid-4">
            <!-- Selector de OT a Modificar -->
            <div class="form-field" style="grid-column: 1 / -1;">
                <label for="workOrderSelect">Seleccionar Orden de Trabajo a Modificar <span class="text-danger">*</span></label>
                <select name="workOrderSelect" id="workOrderSelect" required>
                    <option value="" disabled {{ !$selectedOtId ? 'selected' : '' }}>-- Seleccione una Orden de Trabajo --</option>
                    @foreach($workOrdersAll as $wo)
                        <option value="{{ $wo->id }}" {{ $selectedOtId == $wo->id ? 'selected' : '' }}>
                            OT {{ $wo->id }} - {{ $wo->cliente ?? 'Sin Cliente' }} ({{ $wo->moldura->nombre ?? 'Sin Moldura' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Datos de la OT seleccionada (se llenan dinámicamente con JS) -->
            <div class="form-field">
                <label for="mod_molding">Nombre del Producto / Moldura</label>
                <input type="text" id="mod_molding" disabled placeholder="Moldura asociada">
            </div>

            <div class="form-field">
                <label for="mod_fecha_compra">Fecha de Compra <span class="text-danger">*</span></label>
                <input type="date" name="fecha_compra" id="mod_fecha_compra" required>
            </div>

            <div class="form-field">
                <label for="mod_orden_compra">Orden de Compra <span class="text-danger">*</span></label>
                <input type="text" name="orden_compra" id="mod_orden_compra" required maxlength="25" pattern="[A-Za-z0-9-]{1,25}">
            </div>

            <div class="form-field">
                <label for="mod_cliente">Cliente <span class="text-danger">*</span></label>
                <select name="cliente" id="mod_cliente" required>
                    <option value="" disabled selected>Seleccione el cliente</option>
                    @foreach(['SLP', 'FEVISA', 'SAVERGLASS', 'MEXICALI', 'GLASS & GLASS', 'VETRO', 'L. VERACRUZ'] as $clienteOption)
                        <option value="{{ $clienteOption }}">{{ $clienteOption }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-field">
                <label for="mod_fecha_entrega_cliente">F. Comprometida con el Cliente <span class="text-danger">*</span></label>
                <input type="date" name="fecha_entrega_cliente" id="mod_fecha_entrega_cliente" required>
            </div>

            <!-- Tabla de Clases y Cantidades de la OT -->
            <div class="classes-table-container" id="mod_classes_container" style="display: none;">
                <h4>Clases registradas para esta O.T</h4>
                <table class="classes-table">
                    <thead>
                        <tr>
                            <th>Clase</th>
                            <th>Material</th>
                            <th>Proveedor Fundición</th>
                            <th>Cantidad / Pedido (Piezas)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="mod_classes_tbody">
                        <!-- Llenado dinámico por JS -->
                    </tbody>
                </table>
                <div style="margin-top: 10px; text-align: right;">
                    <button type="button" id="btn-add-new-class" style="padding: 5px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        + Agregar Clase
                    </button>
                </div>
            </div>

            <div class="btn-container">
                <button type="submit" class="btn-save-master" id="btn-save-modify" disabled style="opacity: 0.5; cursor: not-allowed;">Guardar Modificaciones</button>
            </div>
        </div>
    </form>
</div>

<script>
    window.workOrdersData = @json($workOrdersAll);
</script>
@endsection
