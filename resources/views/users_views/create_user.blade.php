@extends('layouts.appMenu')
@section('head')
<title>Registrar usuario | Grupo Industrial Saavedra</title>
<link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
@vite(['resources/css/users_views/createUser.css'])
@endsection

@section('background-body', 'background-image:url("' . asset("images/fondoLogin.jpg") . '")')

@section('content')
<div class="create-user-container">

    {{-- Encabezado Principal --}}
    <div class="page-header">
        <div class="header-left">
            <div class="header-title-group">
                <h1>REGISTRO DE USUARIO</h1>
                <span class="subtitle-text">Alta de personal y asignación operativa en planta</span>
            </div>
        </div>
        <a href="{{ route('users') }}" class="btn-volver">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
            </svg>
            Gestión de Usuarios
        </a>
    </div>

    <form action="{{ route('storeUser') }}" method="post" class="form-container">
        @csrf
        @include('layouts.partials.messages')

        {{-- ── SECCIÓN 1: DATOS PERSONALES ── --}}
        <div class="form-section">
            <div class="section-header">
                <h3 class="section-title">1. INFORMACIÓN PERSONAL</h3>
                <span class="section-badge">Datos Generales</span>
            </div>
            <div class="grid-row grid-cols-3">
                <div class="form-outline">
                    <label class="form-label" for="nombreInput">
                        Nombre (s) <span class="required-indicator">*</span>
                    </label>
                    <input type="text" id="nombreInput" class="form-control" name="nombre" value="{{ old('nombre') }}" placeholder="Nombre(s) del trabajador" required />
                </div>
                <div class="form-outline">
                    <label class="form-label" for="paternoInput">
                        Apellido Paterno <span class="required-indicator">*</span>
                    </label>
                    <input type="text" id="paternoInput" class="form-control" name="a_paterno" value="{{ old('a_paterno') }}" placeholder="Primer apellido" required />
                </div>
                <div class="form-outline">
                    <label class="form-label" for="maternoInput">
                        Apellido Materno <span class="required-indicator">*</span>
                    </label>
                    <input type="text" id="maternoInput" class="form-control" name="a_materno" value="{{ old('a_materno') }}" placeholder="Segundo apellido" required />
                </div>
            </div>
        </div>

        {{-- ── SECCIÓN 2: ACCESO Y PERFIL ── --}}
        <div class="form-section">
            <div class="section-header">
                <h3 class="section-title">2. CREDENCIALES Y PERFIL DE ACCESO</h3>
                <span class="section-badge">Sistema</span>
            </div>
            <div class="grid-row grid-cols-3">
                <div class="form-outline">
                    <label class="form-label" for="matriculaInput">
                        Matrícula <span class="required-indicator">*</span>
                    </label>
                    <input type="text" id="matriculaInput" class="form-control" maxlength="7" minlength="4" name="matricula" value="{{ old('matricula') }}" placeholder="4 a 7 caracteres" required />
                </div>

                <div class="form-outline">
                    <label class="form-label" for="contrasenaInput">
                        Contraseña <span class="required-indicator">*</span>
                    </label>
                    <input type="password" id="contrasenaInput" class="form-control" maxlength="12" minlength="8" name="contrasena" placeholder="8 a 12 caracteres" required />
                </div>

                <div class="form-outline">
                    <label class="form-label" for="floatingSelect">
                        Tipo de Perfil (Rol) <span class="required-indicator">*</span>
                    </label>
                    <select class="form-select" id="floatingSelect" name="perfil" required>
                        <option value="1" {{ old('perfil') == '1' ? 'selected' : '' }}>Administrador</option>
                        <option value="2" {{ old('perfil', '2') == '2' ? 'selected' : '' }}>Operador</option>
                        <option value="3" {{ old('perfil') == '3' ? 'selected' : '' }}>Maestro</option>
                        <option value="4" {{ old('perfil') == '4' ? 'selected' : '' }}>Calidad</option>
                        <option value="5" {{ old('perfil') == '5' ? 'selected' : '' }}>Almacén</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── SECCIÓN 3: ASIGNACIÓN OPERATIVA ── --}}
        <div class="form-section">
            <div class="section-header">
                <h3 class="section-title">3. UBICACIÓN Y ASIGNACIÓN OPERATIVA</h3>
                <span class="section-badge">Operación</span>
            </div>
            <div class="grid-row grid-cols-4">
                <div class="form-outline">
                    <label class="form-label" for="selectPlanta">
                        Planta <span class="required-indicator">*</span>
                    </label>
                    <select class="form-select" id="selectPlanta" name="planta" required>
                        <option value="" disabled {{ old('planta') ? '' : 'selected' }}>Seleccione planta</option>
                        <option value="TECÁMAC" {{ old('planta') == 'TECÁMAC' ? 'selected' : '' }}>TECÁMAC</option>
                        <option value="CDMX" {{ old('planta') == 'CDMX' ? 'selected' : '' }}>CDMX</option>
                    </select>
                </div>

                <div class="form-outline">
                    <label class="form-label" for="selectTurno">
                        Turno <span class="required-indicator">*</span>
                    </label>
                    <select class="form-select" id="selectTurno" name="turno" required>
                        <option value="" disabled {{ old('turno') ? '' : 'selected' }}>Seleccione turno</option>
                        <option value="MIXTO" {{ old('turno') == 'MIXTO' ? 'selected' : '' }}>MIXTO</option>
                        <option value="MATUTINO" {{ old('turno') == 'MATUTINO' ? 'selected' : '' }}>MATUTINO</option>
                        <option value="VESPERTINO" {{ old('turno') == 'VESPERTINO' ? 'selected' : '' }}>VESPERTINO</option>
                        <option value="NOCTURNO" {{ old('turno') == 'NOCTURNO' ? 'selected' : '' }}>NOCTURNO</option>
                    </select>
                </div>

                <div class="form-outline">
                    <label class="form-label" for="selectArea">
                        Área <span class="required-indicator">*</span>
                    </label>
                    <select class="form-select" id="selectArea" name="area" required>
                        <option value="" disabled {{ old('area') ? '' : 'selected' }}>Seleccione área</option>
                        <option value="JEFE PLANTA" {{ old('area') == 'JEFE PLANTA' ? 'selected' : '' }}>JEFE PLANTA</option>
                        <option value="SUPERVISOR" {{ old('area') == 'SUPERVISOR' ? 'selected' : '' }}>SUPERVISOR</option>
                        <option value="PRODUCCIÓN" {{ old('area') == 'PRODUCCIÓN' ? 'selected' : '' }}>PRODUCCIÓN</option>
                        <option value="SOLDADURA" {{ old('area') == 'SOLDADURA' ? 'selected' : '' }}>SOLDADURA</option>
                        <option value="CALIDAD" {{ old('area') == 'CALIDAD' ? 'selected' : '' }}>CALIDAD</option>
                        <option value="MANTENIMIENTO" {{ old('area') == 'MANTENIMIENTO' ? 'selected' : '' }}>MANTENIMIENTO</option>
                        <option value="SOFTWARE" {{ old('area') == 'SOFTWARE' ? 'selected' : '' }}>SOFTWARE</option>
                        <option value="ALMACÉN" {{ old('area') == 'ALMACÉN' ? 'selected' : '' }}>ALMACÉN</option>
                        <option value="PROGRAMACIÓN" {{ old('area') == 'PROGRAMACIÓN' ? 'selected' : '' }}>PROGRAMACIÓN</option>
                        <option value="ADMINISTRACIÓN" {{ old('area') == 'ADMINISTRACIÓN' ? 'selected' : '' }}>ADMINISTRACIÓN</option>
                    </select>
                </div>

                <div class="form-outline">
                    <label class="form-label" for="selectPuesto">
                        Puesto <span class="required-indicator">*</span>
                    </label>
                    <select class="form-select" id="selectPuesto" name="puesto" required>
                        <option value="" disabled selected>Seleccione área primero</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- ── BARRA DE ACCIONES ── --}}
        <div class="form-actions-bar">
            <span class="actions-note">* Todos los campos con asterisco son obligatorios para el registro</span>
            <div class="actions-buttons">
                <a href="{{ route('users') }}" class="btn-cancelar">
                    Cancelar
                </a>
                <button type="submit" class="btn-registrar">
                    Guardar Usuario
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const puestosPorArea = {
            'JEFE PLANTA': [
                'SUPERVISOR'
            ],
            'SUPERVISOR': [
                'SUPERVISOR DE PRODUCCIÓN'
            ],
            'PRODUCCIÓN': [
                'HERRAMENTISTA',
                'AYUDANTE GENERAL',
                'CENTRO DE MAQUINADOS',
                'OPERADOR TORNO CNC',
                'BECARIO'
            ],
            'SOLDADURA': [
                'SOLDADOR',
                'SUPERVISOR'
            ],
            'CALIDAD': [
                'SUPERVISOR',
                'INSPECCIÓN'
            ],
            'MANTENIMIENTO': [
                'SUPERVISOR',
                'AUX MANTENIMIENTO'
            ],
            'SOFTWARE': [
                'SUPERVISOR',
                'BECARIO'
            ],
            'ALMACÉN': [
                'ALMACÉN',
                'SUPERVISOR'
            ],
            'PROGRAMACIÓN': [
                'SUPERVISOR',
                'BECARIO'
            ],
            'ADMINISTRACIÓN': [
                'SUPERVISOR',
                'BECARIO',
                'CHOFER',
                'INTENDENCIA'
            ]
        };

        const selectArea = document.getElementById('selectArea');
        const selectPuesto = document.getElementById('selectPuesto');
        const oldPuesto = @json(old('puesto', ''));

        function actualizarPuestos(selectedArea, defaultPuesto = '') {
            selectPuesto.innerHTML = '';

            if (!selectedArea || !puestosPorArea[selectedArea]) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'Seleccione área primero';
                opt.disabled = true;
                opt.selected = true;
                selectPuesto.appendChild(opt);
                return;
            }

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Seleccione puesto';
            placeholder.disabled = true;
            if (!defaultPuesto) {
                placeholder.selected = true;
            }
            selectPuesto.appendChild(placeholder);

            const puestos = puestosPorArea[selectedArea];
            puestos.forEach(puesto => {
                const opt = document.createElement('option');
                opt.value = puesto;
                opt.textContent = puesto;
                if (defaultPuesto && defaultPuesto === puesto) {
                    opt.selected = true;
                }
                selectPuesto.appendChild(opt);
            });
        }

        selectArea.addEventListener('change', function () {
            actualizarPuestos(this.value);
        });

        // Inicializar al cargar (para soporte de errores de validación con old())
        if (selectArea.value) {
            actualizarPuestos(selectArea.value, oldPuesto);
        } else {
            actualizarPuestos('');
        }
    });
</script>
@endsection