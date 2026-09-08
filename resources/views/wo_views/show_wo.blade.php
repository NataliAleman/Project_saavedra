@extends('layouts.appMenu')

@section('head')
<title>Orden de trabajo</title>
<link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
<script>
    //Rutas de imagenes
    window.deleteImgUrl = "{{ asset('images/delete.png') }}";
    window.cerrarImgUrl = "{{ asset('images/cerrar.png') }}";
    window.classesDataUrl = "{{ url('/piecesInProgress/classesData') }}";
</script>
@vite(['resources/css/wo_views/show_wo.css', 'resources/js/wo_views/show_wo.js'])
@endsection

@section('content')
@section('background-body', 'background-image:url("' . asset("images/fondoLogin.jpg") . '")')

<form action="{{ route('saveClass') }}" method="POST" id="form" class="container-form pt-3">
    @csrf
    <!--Primera parte del formulario-->
    <input type="hidden" name="workOrder" value="{{ $workOrder->id }}">
    <input type="hidden" name="molding" value="{{ $molding->id }}">
    <input type="hidden" name="idClass" id="idClass">
    @if(request('from_master') == 1 || auth()->user()->perfil == 3)
        <input type="hidden" name="from_master" value="1">
    @endif
    <div class="main-layout">
        <div class="wrapper">
            <h3>Información de la orden de trabajo</h3>
            @include('layouts.partials.messages')

            <!--Div en donde se muetran los inputs del formulario-->
            <div class="div-rows">
                <!--Los campos y lase insertan atraves del archivo JavaScript vinculado-->
            </div>

            <div class="div-btns">
                <button type="submit" class="btn-addClass btn hidden" hidden form="form">Guardar Clase</button>
            </div>
        </div>

        <!--Segunda parte del formulario: Procesos (Programación de OT)-->
        <div class="div-boxes hidden" hidden id="casillas">
            <h3>Procesos y número de máquinas disponibles</h3>
            <div class="sections">
                <!--Se inserta el algoritmo para generar las casillas a través de JavaScript-->
            </div>
        </div>
    </div>
</form>
<script>
    window.workOrder = @json($workOrder);
    window.molding = @json($molding);
    window.classes = @json($classes);
    window.profile = @json(auth()->user()->perfil);
    window.processes = @json($processes);
</script>
@endsection
