@extends('layouts.appMenu')

@section('head')
<title>Programación de O.T. #{{ $workOrder->id }}</title>
<link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
<script>
    // Rutas e imágenes globales
    window.deleteImgUrl   = "{{ asset('images/delete.png') }}";
    window.cerrarImgUrl   = "{{ asset('images/cerrar.png') }}";
    window.errorImgUrl    = "{{ asset('images/error.png') }}";
    window.classesDataUrl = "{{ url('/piecesInProgress/classesData') }}";
</script>
@vite(['resources/css/wo_views/show_wo.css', 'resources/js/wo_views/show_wo_programacion.js'])
@endsection

@section('content')
@section('background-body', 'background-image:url("' . asset("images/fondoLogin.jpg") . '")')

<form action="{{ route('saveClass') }}" method="POST" id="form" class="container-form pt-3">
    @csrf

    {{-- Datos de identificación de la OT --}}
    <input type="hidden" name="workOrder" value="{{ $workOrder->id }}">
    <input type="hidden" name="molding"   value="{{ $molding->id }}">
    <input type="hidden" name="idClass"   id="idClass">

    <div class="main-layout">
        {{-- Panel izquierdo: información y listado de clases --}}
        <div class="wrapper">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <img src="{{ asset('images/lg_saavedra.png') }}" alt="Grupo Industrial Saavedra"
                     style="width: 180px; filter: drop-shadow(0px 8px 12px rgba(0,0,0,0.08));">
                <h2 style="font-size: 1.4em; color: rgb(3, 57, 102); font-weight: 700; margin-top: 10px; margin-bottom: 0;">
                    Programación de O.T.
                </h2>
            </div>

            <h3>Información de la orden de trabajo</h3>

            {{-- Selector rápido de O.T. en su propio contenedor de ancho completo entre el título y los inputs de OT/Moldura --}}
            @if(isset($allWorkOrders) && $allWorkOrders->count() > 0)
                <div class="quick-wo-full-container" style="width: 100%; margin: 6px 0 14px 0; box-sizing: border-box;">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 14px; background: transparent; padding: 4px 0; width: 100%; box-sizing: border-box;">
                        <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#033966" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 3 4 7l4 4"/>
                                <path d="M4 7h16"/>
                                <path d="m16 21 4-4-4-4"/>
                                <path d="M20 17H4"/>
                            </svg>
                            <label for="quickWoSelect" style="color: var(--gis-blue, #033966); font-weight: 700; font-size: 0.88em; margin: 0; white-space: nowrap;">
                                Seleccionar otra Orden de Trabajo:
                            </label>
                        </div>
                        <select id="quickWoSelect" class="form-control" style="flex-grow: 1; max-width: 65%; background-color: #ffffff; color: #1e293b; font-weight: 600; font-size: 0.88em; border-radius: 5px; border: 1px solid rgba(3, 57, 102, 0.28); height: 36px; padding: 4px 10px; cursor: pointer; outline: none;" onchange="handleQuickWoChange(this, '{{ url('/showWO') }}');">
                            @foreach($allWorkOrders as $woItem)
                                @php
                                    $isCurrent = (string)$woItem->id === (string)$workOrder->id;
                                    $woClases = $woItem->clases;
                                    $isComplete = true;
                                    if (!$woClases || $woClases->count() === 0) {
                                        $isComplete = false;
                                    } else {
                                        foreach ($woClases as $cl) {
                                            if (empty($cl->tamanio) || empty($cl->fecha_inicio) || empty($cl->hora_inicio)) {
                                                $isComplete = false;
                                                break;
                                            }
                                            $clLower = strtolower($cl->nombre ?? '');
                                            $isWeldingClass = false;
                                            foreach (['molde', 'fondo', 'bombillo', 'obturador', 'corona'] as $wCl) {
                                                if (str_contains($clLower, $wCl)) {
                                                    $isWeldingClass = true;
                                                    break;
                                                }
                                            }
                                            if ($isWeldingClass && (is_null($cl->tipo_soldadura) || $cl->tipo_soldadura === '')) {
                                                $isComplete = false;
                                                break;
                                            }
                                            if (\App\Models\Clase::normalizeClassName($cl->nombre) !== '') {
                                                if (!$cl->procesos) {
                                                    $isComplete = false;
                                                    break;
                                                }
                                                $p = $cl->procesos;
                                                $procCols = ['cepillado', 'desbaste_exterior', 'revision_laterales', 'pOperacion', 'barreno_maniobra', 'sOperacion', 'soldadura', 'soldaduraPTA', 'rectificado', 'asentado', 'calificado', 'acabadoBombillo', 'acabadoMolde', 'barreno_profundidad', 'cavidades', 'copiado', 'offSet', 'palomas', 'rebajes', 'grabado', 'operacionEquipo', 'embudoCM'];
                                                $hasAnyProc = false;
                                                foreach ($procCols as $col) {
                                                    if (isset($p->$col) && (int)$p->$col > 0) {
                                                        $hasAnyProc = true;
                                                        break;
                                                    }
                                                }
                                                if (!$hasAnyProc) {
                                                    $isComplete = false;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    $bgColor = $isComplete ? '#d1e7dd' : '#fff9c4';
                                    $textColor = $isComplete ? '#0f5132' : '#664d03';
                                @endphp
                                <option value="{{ $woItem->id }}" {{ $isCurrent ? 'selected' : '' }} style="background-color: {{ $bgColor }}; color: {{ $textColor }}; font-weight: 500;">
                                    OT {{ $woItem->id }} - {{ $woItem->moldura->nombre ?? 'Sin Moldura' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endif

            @include('layouts.partials.messages')

            {{-- El JS inserta aquí los inputs del formulario principal --}}
            <div class="div-rows"></div>

            <div class="div-btns">
                <button type="submit" class="btn-addClass btn hidden" hidden form="form">Guardar Clase</button>
            </div>
        </div>

        {{-- Panel derecho: procesos y máquinas --}}
        <div class="div-boxes hidden" hidden id="casillas">
            <h3>Procesos y número de máquinas disponibles</h3>
            <div class="sections">
                {{-- El JS inserta aquí las casillas de procesos --}}
            </div>
        </div>
    </div>
</form>

<script>
    window.workOrder = @json($workOrder);
    window.molding   = @json($molding);
    window.classes   = @json($classes);
    window.profile   = @json(auth()->user()->perfil);
    window.processes = @json($processes);
</script>
@endsection
