@extends('layouts.appMenu')

@section('background-body', 'background-image:url("' . asset("images/fondoLogin.jpg") . '")')

@section('content')
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organigrama — Grupo Industrial Saavedra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/users_views/organigrama.css', 'resources/js/users_views/organigrama.js'])
</head>

@php
    // Filtrar EXCLUSIVAMENTE personal que tiene asignado al menos área o puesto
    $assignedUsers = $users->filter(function($u) {
        $hasArea = !empty(trim($u->area ?? ''));
        $hasPuesto = !empty(trim($u->puesto ?? ''));
        return $hasArea || $hasPuesto;
    });

    // Identificar Director General / Jefe de Planta entre los asignados
    $director = $assignedUsers->first(function($u) {
        $area = strtoupper(trim($u->area ?? ''));
        $puesto = strtoupper(trim($u->puesto ?? ''));
        return in_array($area, ['JEFE PLANTA', 'JEFE DE PLANTA', 'GERENCIA', 'DIRECCIÓN', 'DIRECCION']) ||
               in_array($puesto, ['JEFE DE PLANTA', 'GERENTE', 'DIRECTOR', 'JEFE PLANTA']);
    });

    // Definición estándar de Áreas
    $areaDefinitions = [
        'PRODUCCIÓN' => [
            'aliases' => ['PRODUCCIÓN', 'PRODUCCION'],
            'label' => 'Producción',
        ],
        'CALIDAD' => [
            'aliases' => ['CALIDAD', 'METROLOGIA'],
            'label' => 'Calidad',
        ],
        'ALMACÉN' => [
            'aliases' => ['ALMACÉN', 'ALMACEN'],
            'label' => 'Almacén',
        ],
        'SOLDADURA' => [
            'aliases' => ['SOLDADURA', 'SOLDADOR'],
            'label' => 'Soldadura',
        ],
        'MANTENIMIENTO' => [
            'aliases' => ['MANTENIMIENTO'],
            'label' => 'Mantenimiento',
        ],
        'SOFTWARE' => [
            'aliases' => ['SOFTWARE', 'SISTEMAS'],
            'label' => 'Software / Sistemas',
        ],
        'PROGRAMACIÓN' => [
            'aliases' => ['PROGRAMACIÓN', 'PROGRAMACION'],
            'label' => 'Programación',
        ],
        'ADMINISTRACIÓN' => [
            'aliases' => ['ADMINISTRACIÓN', 'ADMINISTRACION', 'ADMIN'],
            'label' => 'Administración',
        ],
    ];

    // Clasificar usuarios por cada área activa
    $areaData = [];
    foreach ($areaDefinitions as $areaKey => $def) {
        $usersInArea = $assignedUsers->filter(function($u) use ($def, $director) {
            if ($director && $u->id === $director->id) return false;
            $uArea = strtoupper(trim($u->area ?? ''));
            foreach ($def['aliases'] as $alias) {
                if (str_contains($uArea, $alias)) return true;
            }
            return false;
        });

        // Solo incluir el área en el organigrama si cuenta con personal asignado
        if ($usersInArea->isNotEmpty()) {
            // Supervisor del área
            $supervisor = $usersInArea->first(function($u) {
                $puesto = strtoupper(trim($u->puesto ?? ''));
                return str_contains($puesto, 'SUPERVISOR') || str_contains($puesto, 'JEFE') || str_contains($puesto, 'ENCARGAD');
            });

            // Equipo operativo bajo el supervisor
            $team = $usersInArea->filter(function($u) use ($supervisor) {
                return !$supervisor || $u->id !== $supervisor->id;
            });

            $areaData[$areaKey] = [
                'label' => $def['label'],
                'supervisor' => $supervisor,
                'team' => $team,
            ];
        }
    }

    $totalCount = $assignedUsers->count();
    $activeCount = $assignedUsers->where('estatus', 1)->count();
    $inactiveCount = $assignedUsers->where('estatus', 0)->count();
@endphp

<div class="org-master-wrapper">

    {{-- ── Encabezado Principal ── --}}
    <div class="org-top-header">
        <div class="org-brand">
            <img src="{{ asset('images/lg_saavedra.png') }}" alt="GIS Logo" class="org-brand-logo">
            <div class="org-brand-titles">
                <h1>ORGANIGRAMA</h1>
                <span>Grupo Industrial Saavedra &bull; Estructura Organizacional y Jerárquica</span>
            </div>
        </div>

        <div class="org-header-controls">
            <a href="{{ route('users') }}" class="btn-header-action btn-action-table" title="Ver tabla de usuarios">
                📋 Tabla de Usuarios
            </a>
            <a href="{{ route('createUser') }}" class="btn-header-action btn-action-create" title="Registrar nuevo usuario">
                ➕ Registrar Personal
            </a>
        </div>
    </div>

    {{-- ── Barra de KPIs Resumen ── --}}
    <div class="org-kpi-bar">
        <div class="org-kpi-pill pill-total">
            <span class="kpi-title">👥 Personal Asignado</span>
            <span class="kpi-num" id="kpi-total-val">{{ $totalCount }}</span>
        </div>
        <div class="org-kpi-pill pill-active">
            <span class="kpi-title">🟢 Activos</span>
            <span class="kpi-num" id="kpi-active-val">{{ $activeCount }}</span>
        </div>
        <div class="org-kpi-pill pill-inactive">
            <span class="kpi-title">🔴 Inactivos / Faltantes</span>
            <span class="kpi-num" id="kpi-inactive-val">{{ $inactiveCount }}</span>
        </div>
    </div>

    {{-- ── Barra de Herramientas, Filtros y Zoom ── --}}
    <div class="org-toolbar-bar">
        <div class="filter-group">
            <span class="filter-lbl">Planta:</span>
            <div class="btn-toggle-group">
                <button type="button" class="btn-toggle planta-filter-btn active" data-planta="todas">Todas</button>
                <button type="button" class="btn-toggle planta-filter-btn" data-planta="TECÁMAC">Tecámac</button>
                <button type="button" class="btn-toggle planta-filter-btn" data-planta="CDMX">CDMX</button>
            </div>
        </div>

        <div class="filter-group">
            <span class="filter-lbl">Turno:</span>
            <select id="org-turno-filter" class="select-filter-org">
                <option value="todos">Todos los turnos</option>
                <option value="MATUTINO">Matutino</option>
                <option value="VESPERTINO">Vespertino</option>
                <option value="NOCTURNO">Nocturno</option>
                <option value="MIXTO">Mixto</option>
            </select>
        </div>

        <div class="filter-group">
            <span class="filter-lbl">Estatus:</span>
            <select id="org-status-filter" class="select-filter-org">
                <option value="todos">Todos los estatus</option>
                <option value="activo">Solo Activos</option>
                <option value="inactivo">Solo Faltantes (Inactivos)</option>
            </select>
        </div>

        <div class="search-box-org">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
            </svg>
            <input type="text" id="org-search-input" placeholder="Buscar por nombre, puesto o matrícula…">
        </div>

        <div class="zoom-controls">
            <button type="button" class="btn-zoom" id="btn-zoom-in" title="Acercar (Zoom In)">+</button>
            <button type="button" class="btn-zoom" id="btn-zoom-out" title="Alejar (Zoom Out)">−</button>
            <button type="button" class="btn-zoom" id="btn-zoom-reset" title="Restablecer vista" style="font-size: 0.75rem; width: auto; padding: 0 8px;">100%</button>
        </div>
    </div>

    {{-- ── LIENZO DEL ORGANIGRAMA EN ÁRBOL CONECTADO ── --}}
    <div class="org-canvas-container" id="org-canvas">
        <div class="org-tree-root" id="org-tree-root">

            @if($assignedUsers->isEmpty())
                <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                    <div style="font-size: 3rem; margin-bottom: 10px;">📋</div>
                    <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--gis-blue);">No hay personal con puesto o área asignada todavía</h3>
                    <p style="font-size: 0.9rem; margin-top: 6px;">Edita los usuarios en la <a href="{{ route('users') }}" style="color: #0284c7; font-weight: 700;">Tabla de Usuarios</a> asignándoles su Área y Puesto para que aparezcan en el Organigrama.</p>
                </div>
            @else
                <div class="tree-branch">
                    <ul>
                        {{-- ── NODO RAÍZ: JEFE DE PLANTA / DIRECCIÓN GENERAL ── --}}
                        <li>
                            @if($director)
                                @include('users_views.partials.org_node', ['user' => $director, 'isDirector' => true, 'isSupervisor' => false])
                            @else
                                <div class="org-node org-node-director">
                                    <div class="org-avatar-circle">
                                        <span style="font-size:1.8rem;">🏢</span>
                                    </div>
                                    <div class="org-name">DIRECCIÓN GENERAL</div>
                                    <div class="org-role">Jefatura de Planta</div>
                                </div>
                            @endif

                            {{-- ── RAMAS A CADA ÁREA ACTIVA ── --}}
                            @if(!empty($areaData))
                                <ul>
                                    @foreach($areaData as $areaKey => $area)
                                        <li>
                                            {{-- Titulo del Área --}}
                                            <div class="area-branch-title">{{ $area['label'] }}</div>

                                            {{-- NODO SUPERVISOR --}}
                                            @if($area['supervisor'])
                                                @include('users_views.partials.org_node', ['user' => $area['supervisor'], 'isDirector' => false, 'isSupervisor' => true])
                                            @else
                                                <div class="org-node org-node-supervisor node-inactivo" data-search="{{ strtolower($area['label']) }}">
                                                    <div class="org-avatar-wrapper">
                                                        <div class="org-avatar-circle" style="border-color:#dc2626; background:#fee2e2;">
                                                            <span style="font-size:1.6rem;">⚠️</span>
                                                        </div>
                                                    </div>
                                                    <div class="org-name" style="color:#b91c1c;">FALTA SUPERVISOR</div>
                                                    <div class="org-role">Encargado de {{ $area['label'] }}</div>
                                                    <div class="badge-falta-alert">🔴 VACANTE</div>
                                                </div>
                                            @endif

                                            {{-- SUB-RAMAS DE EQUIPO AGRUPADAS POR PUESTO (Torno CNC, Centro de Maquinados, Ayudante General, etc.) --}}
                                            @if($area['team']->isNotEmpty())
                                                @php
                                                    $teamByPuesto = $area['team']->groupBy(function($u) {
                                                        return trim($u->puesto ?: 'Operativo');
                                                    })->sortBy(function($group, $puestoName) {
                                                        $p = strtoupper($puestoName);
                                                        if (str_contains($p, 'TORNO')) return 1;
                                                        if (str_contains($p, 'CENTRO')) return 2;
                                                        if (str_contains($p, 'AYUDANTE')) return 3;
                                                        if (str_contains($p, 'HERRAMENTISTA')) return 4;
                                                        if (str_contains($p, 'SOLDADOR')) return 5;
                                                        if (str_contains($p, 'INSPECC')) return 6;
                                                        if (str_contains($p, 'ALMACEN')) return 7;
                                                        if (str_contains($p, 'MANTENIMIENTO')) return 8;
                                                        if (str_contains($p, 'BECARI')) return 9;
                                                        if (str_contains($p, 'CHOFER')) return 10;
                                                        if (str_contains($p, 'INTENDENCIA')) return 11;
                                                        return 20;
                                                    });
                                                @endphp
                                                <ul>
                                                    @foreach($teamByPuesto as $puestoName => $membersInPuesto)
                                                        <li>
                                                            <div class="sub-role-branch-title">{{ $puestoName }}</div>
                                                            <div class="puesto-nodes-column">
                                                                @foreach($membersInPuesto as $member)
                                                                    @include('users_views.partials.org_node', ['user' => $member, 'isDirector' => false, 'isSupervisor' => false])
                                                                @endforeach
                                                            </div>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    </ul>
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
