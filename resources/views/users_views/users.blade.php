@extends('layouts.appMenu')

@section('background-body', 'background-image:url("' . asset("images/fondoLogin.jpg") . '")')

@section('content')
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/viewUsers.css', 'resources/js/viewUsers.js'])
</head>

<div class="container1">

    {{-- ── Encabezado ─────────────────────────────────────────── --}}
    <div class="page-header">
        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
            <h1>Gestión de Usuarios</h1>
            <span class="record-count" id="record-count">{{ $users->count() }} usuarios</span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="{{ route('users.organigrama') }}" class="btn-clear-filters" style="background: var(--gis-blue); color: #ffffff; border-color: var(--gis-blue); text-decoration: none; display: inline-flex; align-items: center; gap: 6px;" title="Ver organigrama jerárquico">
                🏢 Organigrama
            </a>
            <a href="{{ route('createUser') }}" class="btn-clear-filters" style="background: #28a745; color: #ffffff; border-color: #28a745; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;" title="Registrar nuevo usuario">
                ➕ Registrar Usuario
            </a>
        </div>
    </div>

    {{-- ── Alertas ─────────────────────────────────────────────── --}}
    @if(session('success'))
        <div class="alert-success">
            ✔ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-error">
            ✖ {{ session('error') }}
        </div>
    @endif

    {{-- ── Barra de herramientas ───────────────────────────────── --}}
    <div class="toolbar">
        <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input
                type="text"
                id="search-input"
                class="search-input"
                placeholder="Buscar por matrícula o nombre…"
                autocomplete="off"
            >
        </div>

        <select id="role-filter" class="role-select-toolbar">
            <option value="todos">Todos los roles</option>
            <option value="Administrador">Administrador</option>
            <option value="Operador">Operador</option>
            <option value="Master">Master</option>
            <option value="Calidad">Calidad</option>
            <option value="Almacén">Almacén</option>
        </select>

        <select id="status-filter" class="role-select-toolbar">
            <option value="todos">Todos los estatus</option>
            <option value="Activo">Activo</option>
            <option value="Inactivo">Inactivo</option>
        </select>

        <button id="btn-clear-filters" class="btn-clear-filters" title="Limpiar filtros">
            ✕ Limpiar filtros
        </button>
    </div>

    {{-- ── Tabla ───────────────────────────────────────────────── --}}
    <div class="table-wrapper">
        <table id="users-table">
            <thead>
                <tr>
                    <th>Rol</th>
                    <th>Matrícula</th>
                    <th>Usuario</th>
                    <th>Planta</th>
                    <th>Turno</th>
                    <th>Área</th>
                    <th>Puesto</th>
                    <th>Fecha de registro</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="users-tbody">
                @forelse($users as $user)
                @php
                    $rolLabel = match((string)$user->perfil) {
                        '1' => 'Administrador',
                        '2' => 'Operador',
                        '3' => 'Master',
                        '4' => 'Calidad',
                        '5' => 'Almacén',
                        default => $user->perfil,
                    };
                    $rolClass = match((string)$user->perfil) {
                        '1' => 'badge-admin',
                        '2' => 'badge-operador',
                        '3' => 'badge-master',
                        '4' => 'badge-calidad',
                        '5' => 'badge-almacen',
                        default => 'badge-default',
                    };
                @endphp
                <tr data-rol="{{ $rolLabel }}" data-status="{{ $user->estatus ? 'Activo' : 'Inactivo' }}">
                    {{-- Rol --}}
                    <td><span class="badge-rol {{ $rolClass }}">{{ $rolLabel }}</span></td>

                    {{-- Matrícula --}}
                    <td>
                        <span class="user-matricula use-font-size-9rem use-font-weight-600 use-color-1e293b">{{ $user->matricula }}</span>
                    </td>

                    {{-- Usuario --}}
                    <td>
                        <span class="user-name">{{ $user->name }}</span>
                    </td>

                    {{-- Planta --}}
                    <td>
                        <span class="badge-planta">{{ $user->planta ?: '—' }}</span>
                    </td>

                    {{-- Turno --}}
                    <td>
                        <span class="badge-turno">{{ $user->turno ?: '—' }}</span>
                    </td>

                    {{-- Área --}}
                    <td>
                        <span>{{ $user->area ?: '—' }}</span>
                    </td>

                    {{-- Puesto --}}
                    <td>
                        <span>{{ $user->puesto ?: '—' }}</span>
                    </td>

                    {{-- Fecha --}}
                    <td class="date-cell">
                        {{ $user->created_at ? $user->created_at->format('d/m/Y') : '—' }}
                    </td>

                    {{-- Estatus --}}
                    <td>
                        <span class="badge-status {{ $user->estatus ? 'badge-activo' : 'badge-inactivo' }}">
                            {{ $user->estatus ? 'Activo' : 'Inactivo' }}
                        </span>
                    </td>

                    {{-- Acciones --}}
                    <td>
                        <div class="actions-cell">
                            {{-- Editar --}}
                            <button
                                type="button"
                                class="btn-action-icon"
                                onclick="openEditModal({{ json_encode([
                                    'id' => $user->id,
                                    'matricula' => $user->matricula,
                                    'nombre' => $user->nombre,
                                    'a_paterno' => $user->a_paterno,
                                    'a_materno' => $user->a_materno,
                                    'perfil' => (string)$user->perfil,
                                    'planta' => $user->planta ?? '',
                                    'turno' => $user->turno ?? '',
                                    'area' => $user->area ?? '',
                                    'puesto' => $user->puesto ?? '',
                                ]) }})"
                                title="Editar usuario"
                            >
                                <img src="{{ asset('images/editaruser.png') }}" alt="Editar" class="action-img-icon" width="20" height="20" style="width:20px !important; height:20px !important; max-width:20px !important; max-height:20px !important; object-fit:contain; display:block;">
                            </button>

                            {{-- Activar / Inactivar --}}
                            <form action="{{ route($user->estatus ? 'baja_usuario' : 'alta_usuario', $user->id) }}" method="POST" class="use-display-inline">
                                @csrf
                                <button type="submit" class="btn-action-icon" title="{{ $user->estatus ? 'Inactivar usuario' : 'Activar usuario' }}">
                                    <img src="{{ asset($user->estatus ? 'images/inactivar.png' : 'images/activar.png') }}" alt="{{ $user->estatus ? 'Inactivar' : 'Activar' }}" class="action-img-icon" width="20" height="20" style="width:20px !important; height:20px !important; max-width:20px !important; max-height:20px !important; object-fit:contain; display:block;">
                                </button>
                            </form>

                            {{-- Eliminar --}}
                            <form
                                action="{{ route('eliminar_usuario', $user->id) }}"
                                method="POST"
                                class="use-display-inline"
                                onsubmit="return confirm('¿Seguro que deseas eliminar al usuario {{ addslashes($user->name) }}? Esta acción no se puede deshacer.');"
                            >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-icon" title="Eliminar usuario">
                                    <img src="{{ asset('images/delete.png') }}" alt="Eliminar" class="action-img-icon" width="20" height="20" style="width:20px !important; height:20px !important; max-width:20px !important; max-height:20px !important; object-fit:contain; display:block;">
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr class="empty-row">
                    <td colspan="10">No hay usuarios registrados.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

{{-- ── MODAL DE EDICIÓN DE USUARIO ── --}}
<div id="edit-user-modal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <h2 id="modal-title" class="modal-title">EDITAR USUARIO</h2>
            <button type="button" class="modal-close-btn" onclick="closeEditModal()" title="Cerrar">&times;</button>
        </div>

        <form id="edit-user-form" action="" method="POST">
            @csrf
            
            {{-- 1. Información Personal --}}
            <div class="modal-section">
                <div class="modal-section-title">1. INFORMACIÓN PERSONAL</div>
                <div class="modal-grid-3">
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_nombre">Nombre(s) *</label>
                        <input type="text" id="edit_nombre" name="nombre" class="modal-input" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_a_paterno">Apellido Paterno *</label>
                        <input type="text" id="edit_a_paterno" name="a_paterno" class="modal-input" required>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_a_materno">Apellido Materno *</label>
                        <input type="text" id="edit_a_materno" name="a_materno" class="modal-input" required>
                    </div>
                </div>
            </div>

            {{-- 2. Credenciales y Perfil --}}
            <div class="modal-section">
                <div class="modal-section-title">2. CREDENCIALES Y PERFIL</div>
                <div class="modal-grid-2">
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_matricula">Matrícula (Solo lectura)</label>
                        <input type="text" id="edit_matricula" class="modal-input" disabled style="background:#e2e8f0; cursor:not-allowed;">
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_perfil">Tipo de Perfil (Rol) *</label>
                        <select id="edit_perfil" name="perfil" class="modal-select" required>
                            <option value="1">Administrador</option>
                            <option value="2">Operador</option>
                            <option value="3">Master</option>
                            <option value="4">Calidad</option>
                            <option value="5">Almacén</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- 3. Ubicación y Asignación Operativa --}}
            <div class="modal-section">
                <div class="modal-section-title">3. UBICACIÓN Y ASIGNACIÓN OPERATIVA</div>
                <div class="modal-grid-4">
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_planta">Planta</label>
                        <select id="edit_planta" name="planta" class="modal-select">
                            <option value="">— Sin asignar —</option>
                            <option value="TECÁMAC">TECÁMAC</option>
                            <option value="CDMX">CDMX</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_turno">Turno</label>
                        <select id="edit_turno" name="turno" class="modal-select">
                            <option value="">— Sin asignar —</option>
                            <option value="MIXTO">MIXTO</option>
                            <option value="MATUTINO">MATUTINO</option>
                            <option value="VESPERTINO">VESPERTINO</option>
                            <option value="NOCTURNO">NOCTURNO</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_area">Área</label>
                        <select id="edit_area" name="area" class="modal-select">
                            <option value="">— Sin asignar —</option>
                            <option value="JEFE PLANTA">JEFE PLANTA</option>
                            <option value="SUPERVISOR">SUPERVISOR</option>
                            <option value="PRODUCCIÓN">PRODUCCIÓN</option>
                            <option value="SOLDADURA">SOLDADURA</option>
                            <option value="CALIDAD">CALIDAD</option>
                            <option value="MANTENIMIENTO">MANTENIMIENTO</option>
                            <option value="SOFTWARE">SOFTWARE</option>
                            <option value="ALMACÉN">ALMACÉN</option>
                            <option value="PROGRAMACIÓN">PROGRAMACIÓN</option>
                            <option value="ADMINISTRACIÓN">ADMINISTRACIÓN</option>
                        </select>
                    </div>
                    <div class="modal-form-group">
                        <label class="modal-label" for="edit_puesto">Puesto</label>
                        <select id="edit_puesto" name="puesto" class="modal-select">
                            <option value="">Seleccione área primero</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-modal-cancel" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="btn-modal-save">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    // ── Matriz de Puestos por Área ───────────────────────────────
    const puestosPorAreaModal = {
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

    const modalOverlay = document.getElementById('edit-user-modal');
    const editForm     = document.getElementById('edit-user-form');
    const modalTitle   = document.getElementById('modal-title');
    const editArea     = document.getElementById('edit_area');
    const editPuesto   = document.getElementById('edit_puesto');

    function actualizarPuestosModal(selectedArea, defaultPuesto = '') {
        editPuesto.innerHTML = '';

        if (!selectedArea || !puestosPorAreaModal[selectedArea]) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = 'Seleccione área primero';
            editPuesto.appendChild(opt);
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = '— Seleccionar puesto —';
        if (!defaultPuesto) {
            placeholder.selected = true;
        }
        editPuesto.appendChild(placeholder);

        const puestos = puestosPorAreaModal[selectedArea];
        puestos.forEach(puesto => {
            const opt = document.createElement('option');
            opt.value = puesto;
            opt.textContent = puesto;
            if (defaultPuesto && defaultPuesto === puesto) {
                opt.selected = true;
            }
            editPuesto.appendChild(opt);
        });
    }

    editArea.addEventListener('change', function () {
        actualizarPuestosModal(this.value);
    });

    function openEditModal(userData) {
        editForm.action = '{{ url("/users") }}/' + userData.id;
        modalTitle.textContent = 'EDITAR USUARIO: ' + userData.matricula + ' — ' + userData.nombre;

        document.getElementById('edit_matricula').value = userData.matricula || '';
        document.getElementById('edit_nombre').value    = userData.nombre || '';
        document.getElementById('edit_a_paterno').value = userData.a_paterno || '';
        document.getElementById('edit_a_materno').value = userData.a_materno || '';
        document.getElementById('edit_perfil').value    = userData.perfil || '2';
        document.getElementById('edit_planta').value    = userData.planta || '';
        document.getElementById('edit_turno').value     = userData.turno || '';

        editArea.value = userData.area || '';
        actualizarPuestosModal(userData.area, userData.puesto || '');

        modalOverlay.classList.add('active');
    }

    function closeEditModal() {
        modalOverlay.classList.remove('active');
    }

    modalOverlay.addEventListener('click', function (e) {
        if (e.target === modalOverlay) {
            closeEditModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modalOverlay.classList.contains('active')) {
            closeEditModal();
        }
    });

    // ── Filtrado en tiempo real ──────────────────────────────────
    const searchInput  = document.getElementById('search-input');
    const roleFilter   = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const tbody        = document.getElementById('users-tbody');
    const countBadge   = document.getElementById('record-count');

    function filterTable() {
        const query  = searchInput.value.toLowerCase().trim();
        const role   = roleFilter.value;
        const status = statusFilter.value;

        let visible = 0;

        tbody.querySelectorAll('tr[data-rol]').forEach(row => {
            const text   = row.innerText.toLowerCase();
            const rowRol = row.dataset.rol;
            const rowSt  = row.dataset.status;

            const matchText   = !query  || text.includes(query);
            const matchRole   = role   === 'todos' || rowRol === role;
            const matchStatus = status === 'todos' || rowSt  === status;

            const show = matchText && matchRole && matchStatus;
            row.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        countBadge.textContent = visible + ' usuario' + (visible !== 1 ? 's' : '');
    }

    searchInput.addEventListener('input',  filterTable);
    roleFilter.addEventListener('change',  filterTable);
    statusFilter.addEventListener('change', filterTable);

    // ── Limpiar filtros ──────────────────────────────────────────
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        searchInput.value   = '';
        roleFilter.value    = 'todos';
        statusFilter.value  = 'todos';
        filterTable();
    });
</script>

@endsection
