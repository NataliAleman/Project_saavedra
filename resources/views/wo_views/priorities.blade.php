@extends('layouts.appMenu')

@section('head')
    <title>Dashboard de Prioridades</title>
    <link rel="icon" href="{{ url('images/lg_saavedra.png') }}?v=1">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @vite(['resources/css/wo_views/priorities.css', 'resources/js/wo_views/priorities_pdf.js'])
@endsection

@php
    function safeDateParse($value)
    {
        if (empty($value))
            return '';
        try {
            // Handle common formats before parsing
            $clean = str_replace('/', '-', $value);
            return \Carbon\Carbon::parse($clean)->format('Y-m-d');
        } catch (\Exception $e) {
            return ''; // If it's text like "FGF", return empty for the datepicker
        }
    }

    function safeDateParseDisplay($value)
    {
        if (empty($value))
            return '';
        try {
            $clean = str_replace('/', '-', $value);
            return \Carbon\Carbon::parse($clean)->format('d/m/Y');
        } catch (\Exception $e) {
            return $value; // If it's text, just display the text
        }
    }

    function getPastelColorForWeek($weekString)
    {
        // Una paleta curada de 20 colores pasteles profesionales y armoniosos
        $premiumPastels = [
            '#d4f0f0', // Cyan claro
            '#ffdfd3', // Peach
            '#e2f0cb', // Menta suave
            '#f3e5f5', // Lila
            '#ffebd2', // Naranja suave
            '#d6e4ff', // Azul bebe
            '#ffe5e5', // Rosa claro
            '#e8f4d9', // Verde te
            '#f0e6ff', // Lavanda
            '#fff2cc', // Amarillo pastel
            '#d9f0f9', // Celeste
            '#fbe4e4', // Rosa palido
            '#e0f7fa', // Cyan
            '#f5f5dc', // Beige
            '#e6e6fa', // Lavanda gris
            '#ffefd5', // Papaya
            '#e0e8f5', // Azul lavanda
            '#f0fff0', // Honeydew
            '#fff0f5', // Lavender blush
            '#fdf5e6'  // Old lace
        ];

        $weekNum = (int) preg_replace('/[^0-9]/', '', $weekString);
        if ($weekNum === 0) {
            $weekNum = crc32($weekString) % count($premiumPastels);
        }

        // Usar el número de semana como índice (haciendo un wrap-around si es mayor a 20)
        $index = $weekNum % count($premiumPastels);

        return $premiumPastels[$index];
    }
@endphp

@section('content')
@section('background-body')
    background-image: url('{{ asset('images/fondoLogin.jpg') }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
@endsection

    <div class="priorities-wrapper">
        <!-- Barra de Filtros y Herramientas (Oculta al imprimir) -->
        <div class="toolbar no-print">
            <form method="GET" action="{{ route('master.priorities') }}" class="filters-form">
                <div class="filter-group">
                    <label for="start_week">De Semana:</label>
                    <select id="start_week" name="start_week" onchange="this.form.submit()">
                        <option value="">Cualquiera</option>
                        @for($i = 1; $i <= 52; $i++)
                            <option value="{{ $i }}" {{ ($startWeek == $i) ? 'selected' : '' }}>Semana {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="filter-group">
                    <label for="end_week">A Semana:</label>
                    <select id="end_week" name="end_week" onchange="this.form.submit()">
                        <option value="">Cualquiera</option>
                        @for($i = 1; $i <= 52; $i++)
                            <option value="{{ $i }}" {{ ($endWeek == $i) ? 'selected' : '' }}>Semana {{ $i }}</option>
                        @endfor
                    </select>
                </div>

                <div class="filter-group">
                    <label for="ot_id">Por OT:</label>
                    <select id="ot_id" name="ot_id" onchange="this.form.submit()">
                        <option value="">Todas las OTs</option>
                        @foreach($allOts as $ot)
                            <option value="{{ $ot->id }}" {{ ($otId == $ot->id) ? 'selected' : '' }}>
                                OT {{ $ot->id }} - {{ $ot->cliente ?? 'Sin Cliente' }}
                                ({{ $ot->moldura->nombre ?? 'Sin Moldura' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-actions">
                    <a href="{{ route('master.priorities') }}" class="btn-clear">General / Limpiar</a>
                </div>
            </form>

            <div class="export-actions">
                <div id="pm-status-badge" class="pm-table-status-badge no-print" title="Estado de sincronización">
                    <span class="pm-table-spinner" hidden></span>
                    <span class="pm-table-status-text">Autoguardado Activo</span>
                </div>
                <button type="button" class="btn-export-excel"
                    onclick="exportTableToExcel('prioritiesTable', 'Prioridades_GIS')">Descargar Excel</button>
                <a href="{{ route('master.priorities.pdf', request()->all()) }}" class="btn-export-pdf"
                    style="text-decoration:none; display:inline-block;">Descargar PDF</a>
                <button type="button" class="btn-print" onclick="window.print()">Imprimir</button>
            </div>
        </div>

        <!-- Tabla y Encabezado -->
        <div class="excel-table-container">
            <!-- Encabezado Estilo Excel -->
            <div class="excel-header">
                <div class="header-logo">
                    <img src="{{ asset('images/lg_saavedra.png') }}" alt="Grupo Industrial Saavedra" />
                </div>
                <div class="header-title">
                    <h1>PRIORIDADES</h1>
                </div>
                <div class="header-week">
                    @if(!empty($startWeek) && !empty($endWeek) && $startWeek == $endWeek)
                        <h2>SEMANA {{ $startWeek }}</h2>
                    @elseif(!empty($startWeek) || !empty($endWeek))
                        <h2>SEMANAS {{ $startWeek ?? 1 }}-{{ $endWeek ?? 52 }}</h2>
                    @else
                        <h2>SEMANA {{ \Carbon\Carbon::now()->isoWeek() }}</h2>
                    @endif
                    <p>REPORTE GENERAL</p>
                </div>
                <div class="header-date">
                    <h3>{{ \Carbon\Carbon::now()->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</h3>
                </div>
            </div>

            <table class="excel-table" id="prioritiesTable">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-drag no-print">MOVER</th>
                        <th rowspan="2" class="col-num">Nº</th>
                        <th rowspan="2" class="col-ot">ORDEN DE TRABAJO</th>
                        <th rowspan="2" class="col-compra">FECHA Y ORDEN DE COMPRA CLIENTE</th>
                        <th rowspan="2" class="col-cliente">CLIENTE</th>
                        <th rowspan="2" class="col-producto">NOMBRE DEL PRODUCTO</th>
                        <th rowspan="2" class="col-cantidades">CANTIDADES</th>
                        <th rowspan="2" class="col-piezas">PIEZAS</th>
                        <th rowspan="2" class="col-forma">FORMA<br>GRABADOS<br>1,2,3,4</th>
                        <th rowspan="2" class="col-prov">PROVEEDOR DE MATERIAL</th>
                        <th rowspan="2" class="col-material">MATERIAL</th>
                        <th rowspan="2" class="col-fecha-fund">FECHA DE ENTREGA PROVEEDOR FUNDICION</th>
                        <th rowspan="2" class="col-fecha-tec">FECHA ENTREGA TECAMAC</th>
                        <th rowspan="2" class="col-semana">N° DE SEMANA</th>
                        <th rowspan="2" class="col-fecha-mex">FECHA ENTREGA MEXICO</th>
                        <th rowspan="2" class="col-fecha-prom" style="background-color: yellow;">FECHA PROMETIDA ENTREGA
                        </th>
                        <th colspan="2" class="col-obs text-center">OBSERVACIONES</th>
                    </tr>
                    <tr>
                        <th class="col-pzas">PZAS</th>
                        <th class="col-cav">CAV</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $rowCount = 1;
                        $wosJsData = [];
                    @endphp
                    @foreach($groupedWOs as $semana => $wos)
                        @php
                            $rowColor = getPastelColorForWeek($semana);
                        @endphp
                        @foreach($wos as $wo)
                            @php
                                $isInactive = ($wo->clases->where('finalizada', 0)->count() === 0);
                                $rowColor = $isInactive ? '#d6d6d6' : getPastelColorForWeek($semana);

                                $rawClases = $wo->clases ?? collect();
                                if ($rawClases->count() > 0) {
                                    $sortedClases = $rawClases->sortBy(function($cl) use ($wo) {
                                        return !empty($cl->fecha_entrega_fundicion) ? trim($cl->fecha_entrega_fundicion) : (!empty($wo->fecha_entrega_fundicion) ? trim($wo->fecha_entrega_fundicion) : '');
                                    })->values();
                                } else {
                                    $sortedClases = collect();
                                }

                                $clasesJsArr = [];
                                foreach($sortedClases as $cl) {
                                    $clasesJsArr[] = [
                                        'id' => $cl->id,
                                        'nombre' => $cl->nombre,
                                        'pedido' => $cl->pedido,
                                        'fecha_entrega_fundicion' => safeDateParse(!empty($cl->fecha_entrega_fundicion) ? $cl->fecha_entrega_fundicion : $wo->fecha_entrega_fundicion)
                                    ];
                                }

                                $wosJsData[$wo->id] = [
                                    'id' => $wo->id,
                                    'cliente' => $wo->cliente ?? 'Sin Cliente',
                                    'moldura' => $wo->moldura ? $wo->moldura->nombre : ($wo->nombre_producto ?? 'Sin Moldura'),
                                    'fecha_entrega_fundicion' => safeDateParse($wo->fecha_entrega_fundicion),
                                    'clases' => $clasesJsArr
                                ];
                            @endphp
                            <tr class="pm-table-row {{ $isInactive ? 'is-inactive-ot' : '' }}" data-ot-id="{{ $wo->id }}" data-is-inactive="{{ $isInactive ? '1' : '0' }}" style="background-color: {{ $rowColor }};">
                                <td class="text-center no-print cell-drag">
                                    <div class="table-drag-handle" title="Sujeta y arrastra para mover de posición la OT">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                                            stroke-linejoin="round">
                                            <circle cx="9" cy="5" r="1.5" fill="currentColor" />
                                            <circle cx="9" cy="12" r="1.5" fill="currentColor" />
                                            <circle cx="9" cy="19" r="1.5" fill="currentColor" />
                                            <circle cx="15" cy="5" r="1.5" fill="currentColor" />
                                            <circle cx="15" cy="12" r="1.5" fill="currentColor" />
                                            <circle cx="15" cy="19" r="1.5" fill="currentColor" />
                                        </svg>
                                    </div>
                                </td>
                                <td class="text-center font-bold font-large cell-row-num">{{ $rowCount++ }}</td>
                                <td class="text-center font-bold cell-ot-val">{{ $wo->id }}</td>
                                <td class="text-center font-bold">
                                    @if($wo->fecha_compra)
                                        {{ \Carbon\Carbon::parse($wo->fecha_compra)->format('d/m/Y') }}<br>
                                    @endif
                                    {{ $wo->orden_compra }}
                                </td>
                                <td class="text-center font-bold cell-cliente-val">{{ $wo->cliente }}</td>
                                <td class="text-center font-bold cell-producto-val">
                                    {{ $wo->moldura ? $wo->moldura->nombre : $wo->nombre_producto }}
                                </td>
                                <td class="text-center p-0 font-bold">
                                    @if($sortedClases->count() > 0)
                                        @foreach($sortedClases as $cl)
                                            <div class="subcell-row font-bold">{{ $cl->pedido }}</div>
                                        @endforeach
                                    @else
                                        <div class="subcell-row font-bold">{{ $wo->cantidad }}</div>
                                    @endif
                                </td>
                                <td class="text-center p-0 text-uppercase font-bold">
                                    @if($sortedClases->count() > 0)
                                        @foreach($sortedClases as $cl)
                                            <div class="subcell-row font-bold">{{ $cl->nombre }}</div>
                                        @endforeach
                                    @else
                                        <div class="subcell-row font-bold">-</div>
                                    @endif
                                </td>
                                <td class="text-center" contenteditable="true"
                                    onblur="autosaveField({{ $wo->id }}, 'forma_grabados', this)">{{ $wo->forma_grabados }}</td>
                                @php
                                    $classSuppliers = $sortedClases->map(function($cl) use ($wo) {
                                        return !empty($cl->proveedor) ? trim($cl->proveedor) : (!empty($wo->proveedor_material) ? trim($wo->proveedor_material) : '');
                                    })->filter();
                                    $uniqueSuppliers = $classSuppliers->unique();
                                @endphp
                                @if($sortedClases->count() > 0 && $uniqueSuppliers->count() > 1)
                                    <td class="text-center p-0 cell-suppliers font-bold">
                                        @foreach($sortedClases as $cl)
                                            <div class="subcell-row font-bold">{{ !empty($cl->proveedor) ? $cl->proveedor : (!empty($wo->proveedor_material) ? $wo->proveedor_material : '-') }}</div>
                                        @endforeach
                                    </td>
                                @else
                                    <td class="text-center font-bold">{{ $uniqueSuppliers->first() ?? $wo->proveedor_material ?? '-' }}</td>
                                @endif
                                <td class="text-center p-0 cell-materials font-bold">
                                    @if($sortedClases->count() > 0)
                                        @foreach($sortedClases as $cl)
                                            <div class="subcell-row font-bold">{{ $cl->material ?? '-' }}</div>
                                        @endforeach
                                    @else
                                        <div class="subcell-row font-bold">{{ $wo->material }}</div>
                                    @endif
                                </td>
                                @php
                                    $fundDateBlocks = [];
                                    if ($sortedClases->count() > 0) {
                                        $currentBlock = null;
                                        foreach ($sortedClases as $cl) {
                                            $rawDate = !empty($cl->fecha_entrega_fundicion) ? $cl->fecha_entrega_fundicion : $wo->fecha_entrega_fundicion;
                                            $clFundDate = safeDateParse($rawDate);
                                            $rawName = $cl->nombre ?? 'Clase';
                                            $cleanName = trim(preg_replace('/^[0-9]+\s*-\s*/', '', $rawName));
                                            if (empty($cleanName)) { $cleanName = $rawName; }

                                            if ($currentBlock === null) {
                                                $currentBlock = ['date' => $clFundDate, 'count' => 1, 'clases' => [$cleanName]];
                                            } elseif ($currentBlock['date'] === $clFundDate) {
                                                $currentBlock['count']++;
                                                $currentBlock['clases'][] = $cleanName;
                                            } else {
                                                $fundDateBlocks[] = $currentBlock;
                                                $currentBlock = ['date' => $clFundDate, 'count' => 1, 'clases' => [$cleanName]];
                                            }
                                        }
                                        if ($currentBlock !== null) {
                                            $fundDateBlocks[] = $currentBlock;
                                        }
                                    } else {
                                        $fundDateBlocks[] = ['date' => safeDateParse($wo->fecha_entrega_fundicion ?? ''), 'count' => 1, 'clases' => []];
                                    }
                                    $singleClass = $sortedClases->count() === 1 ? $sortedClases->first() : null;
                                    $singleFundDate = $singleClass ? ($singleClass->fecha_entrega_fundicion ?: $wo->fecha_entrega_fundicion) : $wo->fecha_entrega_fundicion;
                                @endphp
                                @if($sortedClases->count() <= 1)
                                    <td class="text-center p-0 date-cell" style="cursor: pointer; vertical-align: middle;" onclick="openDatePicker(this)" title="Hacer clic para seleccionar fecha de fundición">
                                        <div class="date-single-box" style="padding: 6px; width: 100%; box-sizing: border-box;">
                                            <span class="date-display" style="font-size: 11px; color: #000000; font-weight: 500;">{{ safeDateParseDisplay($singleFundDate) }}</span>
                                            <input type="date" style="opacity:0; position:absolute; z-index:-1; width:1px; height:1px;"
                                                value="{{ safeDateParse($singleFundDate) }}"
                                                onchange="handleDateChange(this, {{ $wo->id }}, 'fecha_entrega_fundicion'{{ $singleClass ? ', '.$singleClass->id : '' }})">
                                        </div>
                                    </td>
                                @else
                                    <td class="text-center p-0 date-cell-group" style="cursor: pointer; vertical-align: stretch; height: 1px;" onclick="openFundicionModal({{ $wo->id }})" title="Hacer clic para abrir gestor de fechas de fundición">
                                        <div style="display: flex; flex-direction: column; height: 100%; width: 100%; box-sizing: border-box;">
                                            @foreach($fundDateBlocks as $bIdx => $block)
                                                <div class="subcell-row date-cell" style="flex: 1 1 0px; min-height: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 5px 3px; box-sizing: border-box; {{ $bIdx < count($fundDateBlocks) - 1 ? 'border-bottom: 1px solid #000;' : '' }}">
                                                    @if(!empty($block['clases']) && count($fundDateBlocks) > 1 && count($fundDateBlocks) < $sortedClases->count())
                                                        <span class="subcell-class-label font-bold" style="font-size: 10px; color: #033966; text-transform: uppercase; font-weight: 800; line-height: 1.1; margin-bottom: 1px; display: block; text-align: center; word-break: break-word;">
                                                            {{ implode(', ', $block['clases']) }}
                                                        </span>
                                                    @endif
                                                    <span class="date-display" style="font-size: 11px; color: #000000; font-weight: 500; line-height: 1.1;">{{ safeDateParseDisplay($block['date']) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                                @php
                                    $classTecDates = $sortedClases->map(function($cl) use ($wo) {
                                        return !empty($cl->entrega_tecamac) ? trim($cl->entrega_tecamac) : (!empty($wo->entrega_tecamac) ? trim($wo->entrega_tecamac) : '');
                                    })->filter();
                                    $uniqueTecDates = $classTecDates->unique();
                                @endphp
                                @if($sortedClases->count() > 0 && $uniqueTecDates->count() > 1)
                                    <td class="text-center p-0">
                                        @foreach($sortedClases as $cl)
                                            @php
                                                $clTecDate = !empty($cl->entrega_tecamac) ? $cl->entrega_tecamac : $wo->entrega_tecamac;
                                            @endphp
                                            <div class="subcell-row date-cell" style="cursor: pointer;" onclick="openDatePicker(this)">
                                                <span class="date-display" style="font-weight: 500;">{{ safeDateParseDisplay($clTecDate) }}</span>
                                                <input type="date" style="opacity:0; position:absolute; z-index:-1; width:1px; height:1px;"
                                                    value="{{ safeDateParse($clTecDate) }}"
                                                    onchange="handleDateChange(this, {{ $wo->id }}, 'entrega_tecamac', {{ $cl->id }})">
                                            </div>
                                        @endforeach
                                    </td>
                                @else
                                    @php
                                        $singleTecDate = $uniqueTecDates->first() ?? $wo->entrega_tecamac;
                                    @endphp
                                    <td class="text-center p-0 date-cell" style="cursor: pointer;" onclick="openDatePicker(this)">
                                        <span class="date-display" style="font-weight: 500;">{{ safeDateParseDisplay($singleTecDate) }}</span>
                                        <input type="date" style="opacity:0; position:absolute; z-index:-1; width:1px; height:1px;"
                                            value="{{ safeDateParse($singleTecDate) }}"
                                            onchange="handleDateChange(this, {{ $wo->id }}, 'entrega_tecamac')">
                                    </td>
                                @endif
                                <td class="text-center font-large font-bold cell-semana">{{ $wo->semana_entrega_cliente }}</td>
                                @php
                                    $classMexDates = $sortedClases->map(function($cl) use ($wo) {
                                        return !empty($cl->fecha_real) ? trim($cl->fecha_real) : (!empty($wo->fecha_real) ? trim($wo->fecha_real) : '');
                                    })->filter();
                                    $uniqueMexDates = $classMexDates->unique();
                                @endphp
                                @if($sortedClases->count() > 0 && $uniqueMexDates->count() > 1)
                                    <td class="text-center p-0">
                                        @foreach($sortedClases as $cl)
                                            @php
                                                $clMexDate = !empty($cl->fecha_real) ? $cl->fecha_real : $wo->fecha_real;
                                            @endphp
                                            <div class="subcell-row date-cell" style="cursor: pointer;" onclick="openDatePicker(this)">
                                                <span class="date-display" style="font-weight: 500;">{{ safeDateParseDisplay($clMexDate) }}</span>
                                                <input type="date" style="opacity:0; position:absolute; z-index:-1; width:1px; height:1px;"
                                                    value="{{ safeDateParse($clMexDate) }}"
                                                    onchange="handleDateChange(this, {{ $wo->id }}, 'fecha_real', {{ $cl->id }})">
                                            </div>
                                        @endforeach
                                    </td>
                                @else
                                    @php
                                        $singleMexDate = $uniqueMexDates->first() ?? $wo->fecha_real;
                                    @endphp
                                    <td class="text-center p-0 date-cell" style="cursor: pointer;" onclick="openDatePicker(this)">
                                        <span class="date-display" style="font-weight: 500;">{{ safeDateParseDisplay($singleMexDate) }}</span>
                                        <input type="date" style="opacity:0; position:absolute; z-index:-1; width:1px; height:1px;"
                                            value="{{ safeDateParse($singleMexDate) }}"
                                            onchange="handleDateChange(this, {{ $wo->id }}, 'fecha_real')">
                                    </td>
                                @endif
                                <td class="text-center font-bold">
                                    {{ safeDateParseDisplay($wo->fecha_entrega_cliente) }}
                                </td>
                                <td class="text-center" contenteditable="true"
                                    onblur="autosaveField({{ $wo->id }}, 'observaciones_prioridad', this)" colspan="2">
                                    {{ $wo->observaciones_prioridad }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        window.prioritiesWOsData = @json($wosJsData);

        window.openFundicionModal = function(otId) {
            var woData = window.prioritiesWOsData ? window.prioritiesWOsData[otId] : null;
            if (!woData) return;

            var clases = woData.clases || [];
            if (clases.length === 0) {
                var currentVal = woData.fecha_entrega_fundicion || '';
                Swal.fire({
                    title: 'Fecha Entrega Proveedor Fundición',
                    html: `<strong>OT ${otId}</strong> - ${woData.moldura}<br><br>Ingresa la fecha de entrega:`,
                    input: 'date',
                    inputValue: currentVal,
                    showCancelButton: true,
                    confirmButtonText: 'Guardar Fecha',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        popup: 'gis-date-modal-popup',
                        title: 'gis-date-modal-title',
                        confirmButton: 'gis-swal-confirm',
                        cancelButton: 'gis-swal-cancel'
                    }
                }).then(function(result) {
                    if (result.isConfirmed && result.value !== undefined) {
                        _doAutosave(otId, 'fecha_entrega_fundicion', result.value, document.body);
                        setTimeout(function() { window.location.reload(); }, 600);
                    }
                });
                return;
            }

            var firstDate = clases[0].fecha_entrega_fundicion || '';
            var allSame = clases.every(function(c) { return (c.fecha_entrega_fundicion || '') === firstDate; });
            var masterDate = allSame ? firstDate : (firstDate || woData.fecha_entrega_fundicion || '');

            var rowsHtml = '';
            clases.forEach(function(c, index) {
                var cDate = c.fecha_entrega_fundicion || '';
                rowsHtml += `
                    <tr>
                        <td style="font-weight: 700;">${index + 1}</td>
                        <td style="font-weight: 700; text-align: left;">${c.nombre}</td>
                        <td style="font-weight: 700;">${c.pedido}</td>
                        <td>
                            <input type="date" class="swal-class-date-input gis-date-input-clean" data-clase-id="${c.id}" value="${cDate}" style="width: 100%; box-sizing: border-box; ${allSame ? 'opacity: 0.5; cursor: not-allowed;' : ''}" ${allSame ? 'disabled' : ''}>
                        </td>
                    </tr>
                `;
            });

            var modalHtml = `
                <div class="gis-date-modal-info-card">
                    <div class="gis-date-modal-info-item">
                        <span class="gis-date-modal-info-label">OT</span>
                        <span class="gis-date-modal-info-val" style="color: #033966;">#${woData.id}</span>
                    </div>
                    <div class="gis-date-modal-info-item">
                        <span class="gis-date-modal-info-label">Cliente</span>
                        <span class="gis-date-modal-info-val">${woData.cliente}</span>
                    </div>
                    <div class="gis-date-modal-info-item">
                        <span class="gis-date-modal-info-label">Producto / Moldura</span>
                        <span class="gis-date-modal-info-val">${woData.moldura}</span>
                    </div>
                </div>

                <div class="gis-date-modal-mode-box">
                    <div style="font-size: 0.72rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 8px;">Asignación de Fechas</div>
                    <div style="display: flex; gap: 24px; align-items: center;">
                        <label>
                            <input type="radio" name="swal_date_mode" value="same" ${allSame ? 'checked' : ''} onchange="window.toggleSwalDateMode('same')">
                            Misma fecha para todas las clases
                        </label>
                        <label>
                            <input type="radio" name="swal_date_mode" value="diff" ${!allSame ? 'checked' : ''} onchange="window.toggleSwalDateMode('diff')">
                            Fechas diferentes por clase
                        </label>
                    </div>
                </div>

                <div id="swal-master-date-container" style="margin-bottom: 16px; text-align: left; ${allSame ? '' : 'display: none;'}">
                    <label style="font-size: 0.72rem; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; display: block; margin-bottom: 6px;">Fecha General (Aplica a todas las clases)</label>
                    <input type="date" id="swal-master-date" value="${masterDate}" class="gis-date-input-clean" style="width: 100%; box-sizing: border-box;" onchange="window.applyMasterDateToAllClasses(this.value)">
                </div>

                <div class="gis-date-modal-table-wrap">
                    <table class="gis-date-modal-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">Nº</th>
                                <th style="text-align: left;">Clase</th>
                                <th style="width: 60px;">Cant.</th>
                                <th style="width: 170px;">Fecha Entrega Fundición</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rowsHtml}
                        </tbody>
                    </table>
                </div>
            `;

            Swal.fire({
                title: 'Fecha Entrega Proveedor Fundición',
                html: modalHtml,
                width: '740px',
                showCancelButton: true,
                confirmButtonText: 'Guardar Fechas',
                cancelButtonText: 'Cancelar',
                customClass: {
                    popup: 'gis-date-modal-popup',
                    title: 'gis-date-modal-title',
                    confirmButton: 'gis-swal-confirm',
                    cancelButton: 'gis-swal-cancel'
                },
                allowOutsideClick: false
            }).then(function(result) {
                if (result.isConfirmed) {
                    var mode = document.querySelector('input[name="swal_date_mode"]:checked')?.value || 'same';
                    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    if (mode === 'same') {
                        var val = document.getElementById('swal-master-date')?.value || '';
                        fetch("{{ route('master.priorities.autosave') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ ot_id: otId, field: 'fecha_entrega_fundicion', value: val, apply_to_all: true })
                        }).then(function(r) { return r.json(); }).then(function(d) {
                            if (d.success) {
                                window.location.reload();
                            }
                        });
                    } else {
                        var batch = [];
                        document.querySelectorAll('.swal-class-date-input').forEach(function(inp) {
                            var cId = inp.dataset.claseId;
                            batch.push({ clase_id: cId, fecha: inp.value });
                        });
                        fetch("{{ route('master.priorities.autosave') }}", {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                            body: JSON.stringify({ ot_id: otId, field: 'fecha_entrega_fundicion', batch_dates: batch })
                        }).then(function(r) { return r.json(); }).then(function(d) {
                            if (d.success) {
                                window.location.reload();
                            }
                        });
                    }
                }
            });
        };

        window.toggleSwalDateMode = function(mode) {
            var masterContainer = document.getElementById('swal-master-date-container');
            if (masterContainer) {
                masterContainer.style.display = mode === 'same' ? 'block' : 'none';
            }
            var isSame = mode === 'same';
            document.querySelectorAll('.swal-class-date-input').forEach(function(inp) {
                inp.disabled = isSame;
                if (isSame) {
                    inp.style.opacity = '0.5';
                    inp.style.cursor = 'not-allowed';
                } else {
                    inp.style.opacity = '1';
                    inp.style.cursor = 'pointer';
                }
            });
            if (isSame) {
                var masterVal = document.getElementById('swal-master-date')?.value || '';
                window.applyMasterDateToAllClasses(masterVal);
            }
        };

        window.applyMasterDateToAllClasses = function(val) {
            document.querySelectorAll('.swal-class-date-input').forEach(function(inp) {
                inp.value = val;
            });
        };
    </script>

    <!-- Menú Contextual para Liberar Piezas -->

    <script>
        function autosaveField(otId, fieldName, element) {
            var value = element.innerText.trim();
            _doAutosave(otId, fieldName, value, element);
        }

        function openDatePicker(td) {
            var input = td.querySelector('input[type="date"]');
            if (input) {
                try {
                    input.showPicker();
                } catch (e) {
                    input.focus();
                }
            }
        }

        function handleDateChange(input, otId, fieldName, claseId) {
            var value = input.value;
            var td = input.parentElement;
            var span = td.querySelector('.date-display');

            // Update display text immediately
            if (value) {
                var parts = value.split('-');
                span.innerText = parts[2] + '/' + parts[1] + '/' + parts[0];
            } else {
                span.innerText = '';
            }

            _doAutosave(otId, fieldName, value, td, claseId);
        }

        function _doAutosave(otId, fieldName, value, feedbackElement, claseId) {
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                console.error("CSRF token not found.");
                return;
            }

            // Add a visual indicator of saving (border outline)
            feedbackElement.style.outline = "2px solid #0d6efd";
            feedbackElement.style.outlineOffset = "-2px";

            var payload = {
                ot_id: otId,
                field: fieldName,
                value: value
            };
            if (claseId) {
                payload.clase_id = claseId;
            }

            fetch("{{ route('master.priorities.autosave') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Success indicator (green)
                        feedbackElement.style.outline = "2px solid #198754";
                        setTimeout(() => {
                            feedbackElement.style.outline = "none";
                        }, 1000);
                    } else {
                        // Error indicator (red)
                        feedbackElement.style.outline = "2px solid #dc3545";
                        console.error(data.message);
                    }
                })
                .catch(error => {
                    feedbackElement.style.outline = "2px solid #dc3545";
                    console.error("Error al guardar:", error);
                });
        }

        // ══════════════════════════════════════════════════════════════
        // DRAG AND DROP EN LA TABLA DE PRIORIDADES CON SELECCIÓN DE SEMANA
        // ══════════════════════════════════════════════════════════════
        document.addEventListener('DOMContentLoaded', function () {
            var tableBody = document.querySelector('#prioritiesTable tbody');
            if (!tableBody) return;

            var draggedRow = null;
            var originalNextSibling = null;
            var originalParent = null;
            var isSavingPriorities = false;
            var savePrioritiesPending = false;

            // Auto-scroll durante el arrastre
            var autoScrollSpeed = 0;
            var autoScrollInterval = null;

            document.addEventListener('dragover', function(e) {
                if (!draggedRow) return;
                
                var container = document.querySelector('.excel-table-container');
                if (!container) return;

                var rect = container.getBoundingClientRect();
                var edgeSize = 100; // Píxeles desde el borde del contenedor
                
                if (e.clientY < rect.top + edgeSize) {
                    autoScrollSpeed = -15; // Hacia arriba
                } else if (e.clientY > rect.bottom - edgeSize) {
                    autoScrollSpeed = 15; // Hacia abajo
                } else {
                    autoScrollSpeed = 0;
                }
                
                if (autoScrollSpeed !== 0 && !autoScrollInterval) {
                    autoScrollInterval = setInterval(function() {
                        container.scrollTop += autoScrollSpeed;
                    }, 20);
                } else if (autoScrollSpeed === 0 && autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            });

            document.addEventListener('dragend', function() {
                if (autoScrollInterval) {
                    clearInterval(autoScrollInterval);
                    autoScrollInterval = null;
                }
            });

            var premiumPastels = [
                '#d4f0f0', '#ffdfd3', '#e2f0cb', '#f3e5f5', '#ffebd2',
                '#d6e4ff', '#ffe5e5', '#e8f4d9', '#f0e6ff', '#fff2cc',
                '#d9f0f9', '#fbe4e4', '#e0f7fa', '#f5f5dc', '#e6e6fa',
                '#ffefd5', '#e0e8f5', '#f0fff0', '#fff0f5', '#fdf5e6'
            ];

            function getJsPastelColor(weekStr) {
                var weekNum = parseInt(String(weekStr).replace(/[^0-9]/g, ''), 10) || 0;
                var index = weekNum % premiumPastels.length;
                return premiumPastels[index];
            }

            function initRowDrag(row) {
                var handle = row.querySelector('.table-drag-handle');
                if (!handle) return;

                handle.addEventListener('mousedown', function () {
                    row.draggable = true;
                });
                handle.addEventListener('touchstart', function () {
                    row.draggable = true;
                }, { passive: true });

                row.addEventListener('dragstart', function (e) {
                    draggedRow = row;
                    originalNextSibling = row.nextSibling;
                    originalParent = row.parentNode;
                    row.classList.add('is-row-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', row.dataset.otId || '');
                });

                row.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';

                    if (!draggedRow || draggedRow === row) return;

                    var rect = row.getBoundingClientRect();
                    var isBottom = e.clientY > (rect.top + rect.height / 2);

                    if (isBottom) {
                        row.classList.remove('drag-over-top');
                        row.classList.add('drag-over-bottom');
                    } else {
                        row.classList.remove('drag-over-bottom');
                        row.classList.add('drag-over-top');
                    }
                });

                row.addEventListener('dragleave', function (e) {
                    var rect = row.getBoundingClientRect();
                    if (
                        e.clientX < rect.left ||
                        e.clientX >= rect.right ||
                        e.clientY < rect.top ||
                        e.clientY >= rect.bottom
                    ) {
                        row.classList.remove('drag-over-top', 'drag-over-bottom');
                    }
                });

                row.addEventListener('drop', function (e) {
                    e.preventDefault();
                    e.stopPropagation();

                    row.classList.remove('drag-over-top', 'drag-over-bottom');
                    if (!draggedRow || draggedRow === row) return;

                    var activeDragRow = draggedRow;
                    var otId = activeDragRow.dataset.otId;
                    var semanaCell = activeDragRow.querySelector('.cell-semana');
                    var currentWeekRaw = semanaCell ? semanaCell.textContent.trim() : '';

                    var rect = row.getBoundingClientRect();
                    var isBottom = e.clientY > (rect.top + rect.height / 2);

                    if (isBottom) {
                        row.parentNode.insertBefore(activeDragRow, row.nextSibling);
                    } else {
                        row.parentNode.insertBefore(activeDragRow, row);
                    }

                    renumberRows();

                    var prevRow = activeDragRow.previousElementSibling;
                    var nextRow = activeDragRow.nextElementSibling;

                    function extractWeek(r) {
                        if (!r) return null;
                        var c = r.querySelector('.cell-semana');
                        if (!c) return null;
                        var txt = c.textContent.trim();
                        var w = parseInt(txt.replace(/[^0-9]/g, ''), 10);
                        return isNaN(w) || w <= 0 ? null : w;
                    }

                    var wPrev = extractWeek(prevRow);
                    var wNext = extractWeek(nextRow);

                        function applyRowBg(r, week) {
                            if (r.dataset.isInactive === '1') {
                                r.style.backgroundColor = '#d6d6d6';
                            } else {
                                r.style.backgroundColor = getJsPastelColor(week);
                            }
                        }

                        if (wPrev !== null && wNext !== null && wPrev === wNext) {
                            // Auto-asignar misma semana sin preguntar
                            if (semanaCell) {
                                semanaCell.textContent = wPrev;
                            }
                            applyRowBg(activeDragRow, wPrev);
                            autoSaveTablePriorities(otId, wPrev);
                            return;
                        }

                    var weekOptions = {};
                    var initialWeekVal = null;
                    var currentW = parseInt(String(currentWeekRaw).replace(/[^0-9]/g, ''), 10);

                    if (wPrev !== null && wNext !== null && wPrev !== wNext) {
                        // Colocado entre dos semanas distintas: incluir todas las semanas en el rango entre ambas (inclusive)
                        var minW = Math.min(wPrev, wNext);
                        var maxW = Math.max(wPrev, wNext);
                        for (var w = minW; w <= maxW; w++) {
                            weekOptions[w] = 'Semana ' + w;
                        }
                        if (!isNaN(currentW) && currentW >= minW && currentW <= maxW) {
                            initialWeekVal = currentW;
                        } else {
                            initialWeekVal = wPrev;
                        }
                    } else if (wPrev === null && wNext === null) {
                        // Colocado entre semanas sin número de semana: preguntar a qué semana pertenecerá
                        for (var w = 1; w <= 52; w++) {
                            weekOptions[w] = 'Semana ' + w;
                        }
                        if (!isNaN(currentW) && currentW >= 1 && currentW <= 52) {
                            initialWeekVal = currentW;
                        } else {
                            initialWeekVal = 1;
                        }
                    } else {
                        // Una celda tiene semana y la otra no
                        for (var w = 1; w <= 52; w++) {
                            weekOptions[w] = 'Semana ' + w;
                        }
                        var nonNullW = wPrev !== null ? wPrev : wNext;
                        if (!isNaN(currentW) && currentW >= 1 && currentW <= 52) {
                            initialWeekVal = currentW;
                        } else {
                            initialWeekVal = nonNullW;
                        }
                    }

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Actualizar Número de Semana',
                            html: `Has cambiado de posición la <strong>OT ${otId}</strong>.<br><br>Ingresa o confirma el <strong>Número de Semana</strong> de entrega:`,
                            input: 'select',
                            inputOptions: weekOptions,
                            inputValue: initialWeekVal,
                            showCancelButton: true,
                            confirmButtonText: 'Guardar Orden y Semana',
                            cancelButtonText: 'Cancelar Cambio',
                            customClass: {
                                popup: 'gis-swal-popup',
                                title: 'gis-swal-title',
                                htmlContainer: 'gis-swal-html',
                                input: 'gis-swal-select',
                                confirmButton: 'gis-swal-confirm',
                                cancelButton: 'gis-swal-cancel'
                            },
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            inputValidator: function (value) {
                                if (!value) {
                                    return 'Debes seleccionar un número de semana obligatorio';
                                }
                            }
                        }).then(function (result) {
                            if (result.isConfirmed && result.value) {
                                var newWeek = result.value;
                                if (semanaCell) {
                                    semanaCell.textContent = newWeek;
                                }
                                applyRowBg(activeDragRow, newWeek);
                                autoSaveTablePriorities(otId, newWeek);
                            } else {
                                // Revertir cambio si se cancela
                                if (originalNextSibling) {
                                    originalParent.insertBefore(activeDragRow, originalNextSibling);
                                } else {
                                    originalParent.appendChild(activeDragRow);
                                }
                                renumberRows();
                            }
                        });
                    } else {
                        var promptWeek = prompt("Has movido la OT " + otId + ". Ingresa el número de semana (1-52):", initialWeekVal);
                        if (promptWeek && promptWeek.trim() !== '') {
                            var parsedW = parseInt(promptWeek.replace(/[^0-9]/g, ''), 10);
                            if (parsedW > 0 && parsedW <= 52) {
                                if (semanaCell) semanaCell.textContent = parsedW;
                                applyRowBg(activeDragRow, parsedW);
                                autoSaveTablePriorities(otId, parsedW);
                            } else {
                                alert("Semana inválida. Se cancela el movimiento.");
                                if (originalNextSibling) {
                                    originalParent.insertBefore(activeDragRow, originalNextSibling);
                                } else {
                                    originalParent.appendChild(activeDragRow);
                                }
                                renumberRows();
                            }
                        } else {
                            if (originalNextSibling) {
                                originalParent.insertBefore(activeDragRow, originalNextSibling);
                            } else {
                                originalParent.appendChild(activeDragRow);
                            }
                            renumberRows();
                        }
                    }
                });

                row.addEventListener('dragend', function () {
                    row.draggable = false;
                    row.classList.remove('is-row-dragging');
                    document.querySelectorAll('#prioritiesTable tbody tr').forEach(function (r) {
                        r.classList.remove('drag-over-top', 'drag-over-bottom', 'is-row-dragging');
                    });
                    draggedRow = null;
                });
            }

            document.querySelectorAll('#prioritiesTable tbody tr').forEach(initRowDrag);

            function renumberRows() {
                var rows = document.querySelectorAll('#prioritiesTable tbody tr');
                rows.forEach(function (r, index) {
                    var numCell = r.querySelector('.cell-row-num');
                    if (numCell) {
                        numCell.textContent = index + 1;
                    }
                });
            }

            function autoSaveTablePriorities(updatedOtId, updatedWeek) {
                if (isSavingPriorities) {
                    savePrioritiesPending = true;
                    return;
                }
                isSavingPriorities = true;
                savePrioritiesPending = false;

                updateTableStatus('saving');

                var rows = document.querySelectorAll('#prioritiesTable tbody tr');
                var priorities = [];
                rows.forEach(function (r, index) {
                    var otId = r.dataset.otId;
                    if (otId) {
                        var item = {
                            ot_id: otId,
                            prioridad: index + 1
                        };
                        if (updatedOtId && String(otId) === String(updatedOtId) && updatedWeek) {
                            item.semana_entrega_cliente = updatedWeek;
                        }
                        priorities.push(item);
                    }
                });

                var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch("{{ route('savePriorities') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ priorities: priorities })
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.success) {
                            updateTableStatus('saved');
                        } else {
                            updateTableStatus('error');
                        }
                    })
                    .catch(function (err) {
                        console.error("Error al autoguardar prioridades:", err);
                        updateTableStatus('error');
                    })
                    .finally(function () {
                        isSavingPriorities = false;
                        if (savePrioritiesPending) {
                            autoSaveTablePriorities();
                        }
                    });
            }

            function updateTableStatus(state) {
                var badge = document.getElementById('pm-status-badge');
                if (!badge) return;
                var spinner = badge.querySelector('.pm-table-spinner');
                var text = badge.querySelector('.pm-table-status-text');

                if (state === 'saving') {
                    badge.className = 'pm-table-status-badge pm-status-saving no-print';
                    if (spinner) spinner.hidden = false;
                    if (text) text.textContent = 'Guardando orden...';
                } else if (state === 'saved') {
                    badge.className = 'pm-table-status-badge pm-status-saved no-print';
                    if (spinner) spinner.hidden = true;
                    if (text) text.textContent = '✓ Orden Guardado';
                } else if (state === 'error') {
                    badge.className = 'pm-table-status-badge pm-status-error no-print';
                    if (spinner) spinner.hidden = true;
                    if (text) text.textContent = '⚠ Error al guardar';
                } else {
                    badge.className = 'pm-table-status-badge no-print';
                    if (spinner) spinner.hidden = true;
                    if (text) text.textContent = 'Autoguardado Activo';
                }
            }
        });
    </script>
@endsection
