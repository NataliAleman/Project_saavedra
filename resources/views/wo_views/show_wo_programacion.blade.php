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
