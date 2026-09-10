<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/css/layouts/appMenu.css', 'resources/js/layouts/appMenu.js', 'resources/js/layouts/productivity.js', 'resources/css/layouts/partials/messages.css', 'resources/js/layouts/partials/messages.js'])

    <link rel="icon" type="image/png" href="{{ asset('images/lg_saavedra.png') }}">

    @yield('head')
    <style>
        :root {
            --triangulo-abajo: url('{{ asset("images/triangulo_abajo.png") }}');
        }
    </style>
    <script>
        window.loading = "{{ asset('images/loading.gif') }}"
        window.liberar = "{{ asset('images/Liberar.png') }}"
        window.rechazar = "{{ asset('images/Rechazar.png') }}"
        window.ojito = "{{ asset('images/ojito.png') }}"
        window.baseUrl = "{{ url('/') }}";

        function adjustIconZoom() {
            const btn = document.querySelector('.open-menu');
            const icon = document.querySelector('.open-menu .icon');
            if (btn && icon) {
                const ratio = window.devicePixelRatio || 1;
                // Ajustar las dimensiones físicas reales para evitar desplazamiento
                const btnSize = 40 / ratio;
                const iconSize = 32 / ratio;
                
                btn.style.width = btnSize + 'px';
                btn.style.height = btnSize + 'px';
                icon.style.width = iconSize + 'px';
                icon.style.height = iconSize + 'px';
            }
        }
        window.addEventListener('resize', adjustIconZoom);
        window.addEventListener('zoom', adjustIconZoom);
        document.addEventListener('DOMContentLoaded', adjustIconZoom);
        // Ejecutar periódicamente por si cambia el zoom
        setInterval(adjustIconZoom, 1000);
    </script>
</head>

<body style="@yield('background-body')">
    <header>
        @auth
            <!--Menu-->
            <button class="open-menu">
                <img class="icon" src="{{ asset('images/icono.png') }}">
            </button>
            <div class="filter-opacity">
                <nav class="nav" id="nav">
                    <ul class="nav-list"></ul>
                    @php
                        // Si tiene sesión temporal Y NO ES perfil 1 (Admin/Sistemas) ni 6 (Gerencia) ni 8 (Calidad/Ingeniería), se recorta su menú
                        $isPtaTemp = session('pta_temp_auth') && !in_array(auth()->user()->perfil, ['1', '3', '6', '8']);
                    @endphp

                    @if($isPtaTemp)
                        <a class="btn-close-session" href="{{ route('pta.close_temp_session') }}">Regresar al Reporte</a>
                    @else
                        <a class="btn-close-session" href="{{ route('logout') }}">Cerrar sesión</a>
                    @endif
                    <input type="hidden" value="{{ $isPtaTemp ? 'pta_temp' : auth()->user()->perfil }}" id="profile">
                </nav>
            </div>

            <!--Texto central-->
            <span class="text-header">GRUPO INDUSTRIAL SAAVEDRA</span>
            <!--Logo Saavedra-->
            <img src="{!! asset('images/lg_saavedra.png') !!}" alt="logo" class="logo">
        @endauth
    </header>
    @yield('content')
</body>

<!--Creacion de rutas de laravel para pasarlas a JS-->
<script>
    window.routes = {
        ...(window.routes || {}),
        home: @json(route('home')),
        createMolding: @json(route('createMolding')),
        editMolding: @json(route('editMolding')),
        manageWO: @json(route('manageWO')),
        createMasterWO: @json(route('createMasterWO')),
        masterPriorities: @json(route('master.priorities')),
        show_panelWO: @json(route('show_panelWO')),
        users: @json(route('users')), // PENDING
        usersOrganigrama: @json(route('users.organigrama')),
        createUser: @json(route('createUser')),
        recoverPassword: @json(route('recoverPassword')),
        cNominals: @json(route('cNominals')),
        piecesInProgress: @json(route('showPiecesInProgress')),
        priorityManager: @json(route('showPriorityManager')),
        showPiecesReport_view: @json(route('showPiecesReport_view')),
        showReleasePieces_view: @json(route('showReleasePieces_view')),
        showTimes: @json(route('showTimes')),
        productionData: @json(route('productionData')),
        panelProgreso: @json(route('panelProgreso')),
        machinesOccupied: @json(route('machinesOccupied')),
        processProduction: @json(route('processProduction')),
        logout: @json(route('logout')),
        'soldadura.generarQRLote': @json(route('soldadura.generarQRLote')),
        'soldadura.generarQRIndividual': @json(route('soldadura.generarQRIndividual')),
        'soldadura.recepcionPlanta': @json(route('soldadura.recepcionPlanta')),
        'soldadura.liberarQRPlanta': @json(route('soldadura.liberarQRPlanta')),
        'soldadura.regenerarQR': @json(route('soldadura.regenerarQR')),
        'pta.analysis': @json(route('pta.analysis')),
        'pta.segunda_pasada': @json(route('pta.segunda_pasada')),
        'pta.results.current': @json(session('pta_temp_ot_id') ? route('pta.results', ['ot_id' => session('pta_temp_ot_id')]) : '#'),
        'reportes.reenvio': @json(route('reportes.reenvio')),
        'reportes.pta': @json(route('reportes.pta')),
        'dibujos.manage': @json(route('dibujos.manage')),
        'fundicion.manage': @json(route('fundicion.manage')),
        'manuales.manage': @json(route('manuales.manage')),
        'ayudas.manage': @json(route('ayudas.manage')),
        'ayudas_fundicion.manage': @json(route('ayudas_fundicion.manage')),
        'almacen.fundicion.index': @json(route('almacen.fundicion.index')),
        'calidad.fundicion.index': @json(route('calidad.fundicion.index')),
        'calidad.maquinados.index': @json(route('calidad.maquinados.index')),
        'herramientas.tecamac.index': @json(route('herramientas.tecamac.index')),
        systemLogsReport: @json(route('systemLogsReport')),
        adminLogsReport: @json(route('systemLogsReport', ['admin_only' => 1]))
    };
</script>
@isset($pieces_Released)
    <script>
        window.pieces_Released = ({{ auth()->user()->perfil }} == 4 || ({{ auth()->user()->perfil }} == 3 && "{{ request('sec') }}" === "calidad")) ? @json($pieces_Released) : [];
        window.info_Pieces = ({{ auth()->user()->perfil }} == 4 || ({{ auth()->user()->perfil }} == 3 && "{{ request('sec') }}" === "calidad")) ? @json($info_Pieces) : [];
    </script>
@endisset

</html>
