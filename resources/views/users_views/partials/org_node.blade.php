@php
    $isInactive = !$user->estatus;
    $fullName = trim($user->name ?: ($user->nombre . ' ' . $user->a_paterno . ' ' . $user->a_materno));
    $puesto = $user->puesto ?: ($isDirector ? 'Jefe de Planta / Dirección' : ($isSupervisor ? 'Supervisor de Área' : 'Puesto Operativo'));
    $nodeTypeClass = $isDirector ? 'org-node-director' : ($isSupervisor ? 'org-node-supervisor' : '');
    
    // Asignación de avatar estilizado según puesto / tipo
    $puestoUpper = strtoupper($puesto);
    $avatarType = 'male_1';
    if ($isDirector) {
        $avatarType = 'director';
    } elseif (str_contains($puestoUpper, 'SUPERVISOR')) {
        $avatarType = 'supervisor';
    } elseif (str_contains($puestoUpper, 'BECARI') || str_contains($puestoUpper, 'ADMIN') || str_contains(strtoupper($fullName), 'NATALI') || str_contains(strtoupper($fullName), 'ALE')) {
        $avatarType = 'female_1';
    } elseif (str_contains($puestoUpper, 'CALIDAD') || str_contains($puestoUpper, 'INSPECC')) {
        $avatarType = 'female_2';
    } elseif (str_contains($puestoUpper, 'HERRAMENTISTA')) {
        $avatarType = 'male_2';
    } else {
        $avatarType = 'male_1';
    }
@endphp

<div 
    class="org-node {{ $nodeTypeClass }} {{ $isInactive ? 'node-inactivo' : '' }}"
    data-planta="{{ $user->planta ?? '' }}"
    data-turno="{{ $user->turno ?? '' }}"
    data-status="{{ $user->estatus ? 'activo' : 'inactivo' }}"
    data-search="{{ strtolower($fullName . ' ' . $user->matricula . ' ' . $puesto . ' ' . ($user->area ?? '') . ' ' . ($user->planta ?? '')) }}"
    title="{{ $fullName }} - {{ $puesto }} (Matrícula: {{ $user->matricula }})"
>
    {{-- Avatar Ilustrado --}}
    <div class="org-avatar-wrapper">
        <div class="org-avatar-circle">
            @if($avatarType === 'director')
                {{-- Director Avatar (Beard / Brown Hair - Igual que la imagen de referencia) --}}
                <svg class="org-avatar-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#fed7aa" />
                    <!-- Hair -->
                    <path d="M22 24C22 16 26 12 32 12C38 12 42 16 42 24C42 26 40 24 38 24C36 24 35 22 32 22C29 22 28 24 26 24C24 24 22 26 22 24Z" fill="#9a3412" />
                    <!-- Head -->
                    <path d="M24 24C24 28.4 27.6 32 32 32C36.4 32 40 28.4 40 24V22H24V24Z" fill="#fed7aa" />
                    <!-- Beard -->
                    <path d="M24 28C24 36 28 40 32 40C36 40 40 36 40 28H24Z" fill="#9a3412" />
                    <path d="M28 32C29 33 31 33 32 33C33 33 35 33 36 32C36 34 34 36 32 36C30 36 28 34 28 32Z" fill="#fed7aa" />
                    <!-- Shirt -->
                    <path d="M16 54C16 44 23 42 32 42C41 42 48 44 48 54V60H16V54Z" fill="#f59e0b" />
                </svg>
            @elseif($avatarType === 'female_1')
                {{-- Female Avatar (Red/Orange Hair - Igual que imagen de referencia) --}}
                <svg class="org-avatar-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#fbcfe8" />
                    <!-- Long Hair Back -->
                    <path d="M20 24C20 14 25 10 32 10C39 10 44 14 44 24C44 34 42 42 42 42H22C22 42 20 34 20 24Z" fill="#ea580c" />
                    <!-- Head -->
                    <circle cx="32" cy="26" r="10" fill="#fde047" />
                    <!-- Shirt -->
                    <path d="M18 54C18 44 24 40 32 40C40 40 46 44 46 54V60H18V54Z" fill="#ec4899" />
                </svg>
            @elseif($avatarType === 'female_2')
                {{-- Female Avatar (Dark Hair / Glasses) --}}
                <svg class="org-avatar-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#ccfbf1" />
                    <!-- Hair -->
                    <path d="M20 22C20 12 25 10 32 10C39 10 44 12 44 22C44 32 40 38 40 38H24C24 38 20 32 20 22Z" fill="#0f172a" />
                    <!-- Head -->
                    <circle cx="32" cy="26" r="10" fill="#fcd34d" />
                    <!-- Shirt -->
                    <path d="M18 54C18 44 24 40 32 40C40 40 46 44 46 54V60H18V54Z" fill="#0d9488" />
                </svg>
            @elseif($avatarType === 'supervisor')
                {{-- Supervisor Avatar (Blue Shirt) --}}
                <svg class="org-avatar-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#e0f2fe" />
                    <!-- Hair -->
                    <path d="M22 22C22 14 26 12 32 12C38 12 42 14 42 22C42 24 40 22 36 22C32 22 28 22 22 22Z" fill="#1e293b" />
                    <!-- Head -->
                    <circle cx="32" cy="26" r="10" fill="#fed7aa" />
                    <!-- Shirt -->
                    <path d="M16 54C16 44 23 40 32 40C41 40 48 44 48 54V60H16V54Z" fill="#0284c7" />
                </svg>
            @elseif($avatarType === 'male_2')
                {{-- Herramentista / Technical (Orange Hair) --}}
                <svg class="org-avatar-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#ffedd5" />
                    <!-- Hair -->
                    <path d="M22 22C22 14 26 11 32 11C38 11 42 14 42 22C42 24 39 23 35 22C30 22 25 24 22 22Z" fill="#c2410c" />
                    <!-- Head -->
                    <circle cx="32" cy="26" r="10" fill="#fde68a" />
                    <!-- Shirt -->
                    <path d="M16 54C16 44 23 40 32 40C41 40 48 44 48 54V60H16V54Z" fill="#0f766e" />
                </svg>
            @else
                {{-- Standard Operative Avatar --}}
                <svg class="org-avatar-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="32" cy="32" r="30" fill="#f1f5f9" />
                    <!-- Hair -->
                    <path d="M22 22C22 14 26 12 32 12C38 12 42 14 42 22C42 24 38 23 35 22C30 22 25 24 22 22Z" fill="#334155" />
                    <!-- Head -->
                    <circle cx="32" cy="26" r="10" fill="#fcd34d" />
                    <!-- Shirt -->
                    <path d="M16 54C16 44 23 40 32 40C41 40 48 44 48 54V60H16V54Z" fill="#3b82f6" />
                </svg>
            @endif
        </div>
    </div>

    {{-- Nombre y Puesto --}}
    <div class="org-name">{{ $fullName }}</div>
    <div class="org-role">{{ $puesto }}</div>

    {{-- Tags / Meta --}}
    <div class="org-meta-tags">
        @if(!empty($user->planta))
            <span class="org-tag org-tag-planta">{{ $user->planta }}</span>
        @endif
        @if(!empty($user->turno))
            <span class="org-tag org-tag-turno">{{ $user->turno }}</span>
        @endif
        <span class="org-tag">#{{ $user->matricula }}</span>
    </div>

    {{-- Si está inactivo, badge de ALERTA EN ROJO --}}
    @if($isInactive)
        <div class="badge-falta-alert">
            🔴 FALTA / INACTIVO
        </div>
    @endif
</div>
