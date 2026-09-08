<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Orden de Trabajo #{{ $workOrder->id }} - Ficha Técnica</title>
    <style>
        @page {
            size: letter landscape;
            margin: 8mm 10mm;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 1.35;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #033966;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .header-title {
            font-size: 18px;
            color: #033966;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin: 2px 0 0 0;
            font-weight: 500;
        }

        .header-date {
            text-align: right;
            font-size: 9px;
            color: #64748b;
            vertical-align: bottom;
        }

        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
            background-color: #ffffff;
        }

        .info-table td {
            padding: 5px 8px;
            vertical-align: middle;
            border: 1px solid #cbd5e1;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #033966;
            width: 14%;
            font-size: 9px;
            text-transform: uppercase;
            background-color: #f1f5f9;
        }

        .info-value {
            width: 36%;
            color: #0f172a;
            font-weight: 600;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        table.data-table th {
            background-color: #033966;
            color: #ffffff;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
            padding: 6px 4px;
            border: 1px solid #033966;
            letter-spacing: 0.3px;
            text-align: center;
        }

        table.data-table td {
            padding: 6px 4px;
            border: 1px solid #cbd5e1;
            font-size: 9px;
            color: #334155;
            text-align: center;
            vertical-align: middle;
        }

        table.data-table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .class-name {
            font-weight: bold;
            color: #033966;
            text-align: left !important;
            padding-left: 6px !important;
            white-space: nowrap;
        }

        .material-val {
            font-weight: bold;
            color: #0f172a;
            text-align: center;
        }

        .badge-tag {
            display: inline-block;
            background-color: #e0f2fe;
            color: #0369a1;
            padding: 1px 4px;
            border-radius: 3px;
            font-weight: bold;
            font-size: 8.5px;
        }

        .nowrap-cell {
            white-space: nowrap;
            font-size: 8.5px;
        }

        .process-list {
            font-size: 8.5px;
            color: #1e293b;
            text-align: left !important;
            line-height: 1.3;
            padding: 4px 6px !important;
        }

        .no-records {
            padding: 20px;
            color: #94a3b8;
            font-style: italic;
            text-align: center;
        }

        .footer-note {
            margin-top: 10px;
            font-size: 8px;
            color: #94a3b8;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $tiposSoldaduraMap = [
            '1' => 'P1 - 3',
            '2' => 'P2 - 2.5',
            '3' => 'P3 - 2',
            '4' => 'P4 - 1.5',
        ];
        $usuarioActual = auth()->user();
        $usuarioTexto = $usuarioActual 
            ? trim(($usuarioActual->matricula ? $usuarioActual->matricula . ' - ' : '') . $usuarioActual->nombre . ($usuarioActual->a_paterno ? ' ' . $usuarioActual->a_paterno : ''))
            : 'Sistema';
        $nombresClases = $classes ? $classes->pluck('nombre')->implode(', ') : '-';
    @endphp

    <table class="header-table">
        <tr>
            <td style="vertical-align: top;">
                <h1 class="header-title">GRUPO INDUSTRIAL SAAVEDRA</h1>
                <p class="header-subtitle">Ficha Técnica de Producción — Orden de Trabajo #{{ $workOrder->id }}</p>
            </td>
            <td class="header-date">
                <strong>Fecha de Emisión:</strong> {{ now()->format('d/m/Y H:i') }}<br>
                <strong>Usuario:</strong> {{ $usuarioTexto }}
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td class="info-label">Orden de Trabajo</td>
            <td class="info-value">#{{ $workOrder->id }}</td>
            <td class="info-label">Moldura</td>
            <td class="info-value" style="color: #033966; font-size: 11px;">{{ $molding->nombre ?? '-' }}</td>
        </tr>
        <tr>
            <td class="info-label">Fecha de Registro</td>
            <td class="info-value">{{ $workOrder->created_at ? \Carbon\Carbon::parse($workOrder->created_at)->format('d/m/Y H:i') : '-' }}</td>
            <td class="info-label">Clases</td>
            <td class="info-value">{{ $nombresClases }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="text-align: left; width: 14%;">Clase</th>
                <th style="width: 7%;">Tamaño</th>
                <th style="width: 9%;">Tipo Soldadura</th>
                <th style="width: 9%;">Material</th>
                <th style="width: 14%;">Proveedor Fundición</th>
                <th style="width: 5%;">Pedido</th>
                <th style="width: 5%;">Piezas Consig.</th>
                <th style="width: 9%;">Fecha / Hora Inicio</th>
                <th style="width: 9%;">Fecha / Hora Término</th>
                <th style="text-align: left; width: 19%;">Procesos y Máquinas</th>
            </tr>
        </thead>
        @if ($classes != null && count($classes) > 0)
            <tbody>
                @foreach($classes as $class)
                @php
                    $fechaInicioFormatted = $class->fecha_inicio ? \Carbon\Carbon::parse($class->fecha_inicio)->format('d/m/Y') : '-';
                    $horaInicioFormatted = $class->hora_inicio ? substr($class->hora_inicio, 0, 5) : '';
                    $fechaTerminoFormatted = $class->fecha_termino ? \Carbon\Carbon::parse($class->fecha_termino)->format('d/m/Y') : '-';
                    $horaTerminoFormatted = $class->hora_termino ? substr($class->hora_termino, 0, 5) : '';
                    $tipoSold = isset($tiposSoldaduraMap[strval($class->tipo_soldadura)]) ? $tiposSoldaduraMap[strval($class->tipo_soldadura)] : ($class->tipo_soldadura ? 'Tipo ' . $class->tipo_soldadura : '-');
                @endphp
                <tr>
                    <td class="class-name">{{ $class->nombre }}</td>
                    <td>{{ $class->tamanio ?? '-' }}</td>
                    <td>
                        @if($tipoSold !== '-')
                            <span class="badge-tag">{{ $tipoSold }}</span>
                        @else
                            <span style="color: #94a3b8;">-</span>
                        @endif
                    </td>
                    <td class="material-val">{{ $class->material ?? '-' }}</td>
                    <td style="font-size: 8.5px;">{{ (!empty($class->proveedor) && trim($class->proveedor) !== '') ? $class->proveedor : '-' }}</td>
                    <td><strong>{{ $class->pedido }}</strong></td>
                    <td>{{ $class->piezas ?? 0 }}</td>
                    <td class="nowrap-cell">
                        {{ $fechaInicioFormatted }}
                        @if($horaInicioFormatted)
                            <br><span style="color: #64748b; font-size: 8px;">{{ $horaInicioFormatted }}</span>
                        @endif
                    </td>
                    <td class="nowrap-cell">
                        {{ $fechaTerminoFormatted }}
                        @if($horaTerminoFormatted && $fechaTerminoFormatted !== '-')
                            <br><span style="color: #64748b; font-size: 8px;">{{ $horaTerminoFormatted }}</span>
                        @endif
                    </td>
                    <td class="process-list">
                        @if ($processes != null && isset($processes[$class->id]) && !empty($processes[$class->id]))
                            {{ $processes[$class->id] }}
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Sin procesos asignados</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        @else
            <tbody>
                <tr>
                    <td colspan="10" class="no-records">No hay clases registradas en esta orden de trabajo.</td>
                </tr>
            </tbody>
        @endif
    </table>

    <div class="footer-note">
        Documento oficial de producción — Grupo Industrial Saavedra © {{ date('Y') }}
    </div>
</body>

</html>