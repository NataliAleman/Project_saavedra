@foreach (['activa' => 'Documentos Activos (Dibujos y Ayudas)', 'inactiva' => 'Documentos Inactivos (Histórico)'] as $estado => $titulo)
    @php
        $registrosEstado = $registros->where('status', $estado);
    @endphp

    <div class="alm-table-card alm-margin-bottom-2em">
        <div class="alm-table-header"
            style="{{ $estado === 'inactiva' ? 'background: #6c757d; border-bottom: 2px solid #5a6268;' : '' }}">
            <h2>{{ $titulo }}</h2>
            <span class="alm-results-count">{{ $registrosEstado->count() }}
                resultado{{ $registrosEstado->count() !== 1 ? 's' : '' }}</span>
        </div>

        @if ($estado === 'activa')
            {{-- ── BARRA DE SINCRONIZACIÓN MANUAL (solo tabla Activa) ── --}}
            <div id="sync-bar-activa"
                class="alm-display-flex alm-align-items-center alm-justify-content-space-between alm-flex-wrap-wrap alm-gap-10px alm-padding-10px-20px alm-background-linear-gradient-135deg-f0f9ff-0-e0f2fe-100pct alm-border-bottom-1px-solid-bae6fd alm-font-size-0-85rem alm-color-0369a1 alm-font-family-Poppins-sans-serif">
                <span id="sync-status-almacen" class="alm-display-flex alm-align-items-center alm-gap-6px alm-font-weight-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="#0369a1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                        class="alm-flex-shrink-0">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <span id="sync-last-time-almacen">Sincronización automática activa</span>
                </span>
                <button id="btn-sync-manual-almacen" onclick="sincronizarDibujos(true)" title="Sincronizar archivos ahora"
                    class="alm-display-inline-flex alm-align-items-center alm-gap-7px alm-padding-7px-18px alm-background-linear-gradient-135deg-0369a1-0-0284c7-100pct alm-color-fff alm-border-none alm-border-radius-8px alm-font-weight-700 alm-font-size-0-82rem alm-font-family-Poppins-sans-serif alm-cursor-pointer alm-box-shadow-0-3px-10px-rgba-3-105-161-0-25 alm-transition-all-0-2s-ease alm-white-space-nowrap"
                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 5px 15px rgba(3,105,161,0.35)';"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 3px 10px rgba(3,105,161,0.25)';">
                    <svg id="sync-icon-almacen" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="23 4 23 10 17 10"></polyline>
                        <polyline points="1 20 1 14 7 14"></polyline>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    Sincronizar ahora
                </button>
            </div>
        @endif

        @if ($registrosEstado->isEmpty())
            <div class="alm-empty">
                <div class="alm-empty-icon">
                    <img src="{{ asset('images/noPieces.png') }}" alt="Sin resultados" class="alm-width-64px alm-opacity-0-5">
                </div>
                <p>
                    @if ($busquedaOt || $desde || $hasta)
                        No se encontraron registros de {{ strtolower($titulo) }} con los filtros aplicados.
                    @else
                        Aún no hay registros en la bandeja de {{ strtolower($titulo) }}.
                    @endif
                </p>
            </div>
        @else
            <div class="alm-table-scroll">
                <table class="alm-table">
                    <thead>
                        <tr>
                            <th
                                style="width:30%; {{ $estado === 'inactiva' ? 'background: #6c757d; border-color: #5a6268;' : '' }}">
                                Orden de Trabajo</th>
                            <th style="width:12%; {{ $estado === 'inactiva' ? 'background: #6c757d; border-color: #5a6268;' : '' }}"
                                class="d-text-center">Estado</th>
                            <th style="width:12%; {{ $estado === 'inactiva' ? 'background: #6c757d; border-color: #5a6268;' : '' }}"
                                class="d-text-center">Modelo</th>
                            <th style="width:18%; {{ $estado === 'inactiva' ? 'background: #6c757d; border-color: #5a6268;' : '' }}"
                                class="d-text-center">Último envío</th>
                            <th style="width:10%; {{ $estado === 'inactiva' ? 'background: #6c757d; border-color: #5a6268;' : '' }}"
                                class="d-text-center">Archivos</th>
                            <th style="width:16%; {{ $estado === 'inactiva' ? 'background: #6c757d; border-color: #5a6268;' : '' }}"
                                class="d-text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="alm-tbody-{{ $estado }}">
                        @foreach ($registrosEstado as $reg)
                            @php
                                /** @var \App\Models\FundicionHistory $reg */
                                $liberacionesReg = \App\Models\LiberacionModeloFundicion::where('ot', $reg->ot)
                                    ->where('estado', '!=', 'pendiente')
                                    ->get();
                                /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\LiberacionModeloFundicion> $liberacionesReg */
                                $hasAprobados = $liberacionesReg->where('decision', 'aprobar')->isNotEmpty();

                                $latestReproceso = null;
                                if ($reg->rechazos_procesados) {
                                    $latestReproceso = \App\Models\FundicionHistory::where(
                                        function ($q) use ($reg) {
                                            $q->where('ot', 'LIKE', $reg->ot . '_R%', 'and')
                                              ->where('ot', 'LIKE', $reg->ot . '_%_R%', 'or');
                                        },
                                        null,
                                        null,
                                        'and'
                                    )
                                        ->orderBy('id', 'desc')
                                        ->first();
                                }
                                // Corregimos la asignación para que la fila original NO actúe como si fuera el reproceso.
                                // Así mantenemos las clases independientes (Ej: original para Bombillo, R1 para Fondo).
                                $targetReg = $reg;

                                // ── RESOLVER TODOS LOS REGISTROS RELACIONADOS ──
                                $baseOtName = preg_replace('/_(?:(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias)(?:_(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias))*_)?R\d+$/iu', '', $reg->ot);
                                $relatedRecords = \App\Models\FundicionHistory::where('ot', '=', $baseOtName, 'or')
                                    ->where('ot', 'LIKE', $baseOtName . '_R%', 'or')
                                    ->where('ot', 'LIKE', $baseOtName . '_%_R%', 'or')
                                    ->get();
                                $allRelatedOtNames = $relatedRecords->pluck('ot')->toArray();
                                $allOtNames = $allRelatedOtNames;

                                $isReprocesoOT = preg_match('/_R\d+$/i', $reg->ot);
                                $baseOtOfReg = preg_replace('/_(?:(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias)(?:_(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias))*_)?R\d+$/iu', '', $reg->ot);
                                preg_match('/_R(\d+)$/i', $reg->ot, $mReg);
                                $sReg = isset($mReg[1]) ? (int) $mReg[1] : 0;

                                $allowFileCrossOt = function ($fileOt) use ($reg, $isReprocesoOT, $baseOtOfReg, $sReg) {
                                    if ($fileOt === $reg->ot)
                                        return true;
                                    if (!$isReprocesoOT)
                                        return false;
                                    $baseOtOfFile = preg_replace('/_(?:(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias)(?:_(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias))*_)?R\d+$/iu', '', $fileOt);
                                    if ($baseOtOfFile !== $baseOtOfReg)
                                        return false;
                                    preg_match('/_R(\d+)$/i', $fileOt, $mFile);
                                    $sFile = isset($mFile[1]) ? (int) $mFile[1] : 0;
                                    return $sFile < $sReg;
                                };

                                // Obtener clases activas para filtrar archivos del historial
                                $activeClassesForOt = [];
                                $confSource = $targetReg->ayudas_config ?? ($reg->ayudas_config ?? null);
                                if (!empty($confSource)) {
                                    $configs = is_string($confSource) ? json_decode($confSource, true) : $confSource;
                                    if (is_array($configs)) {
                                        foreach ($configs as $val) {
                                            $val = strtolower($val);
                                            if (str_contains($val, 'opcional'))
                                                continue;
                                            $parts = explode(',', $val);
                                            foreach (['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'] as $kc) {
                                                foreach ($parts as $p) {
                                                    if (trim($p) === $kc) {
                                                        $activeClassesForOt[] = $kc;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                if (empty($activeClassesForOt)) {
                                    $po = \App\Models\PreOrdenFundicion::where('ot', $reg->ot)->first();
                                    if ($po) {
                                        $filas = $po->filas;
                                        if (is_string($filas)) {
                                            $filas = json_decode($filas, true);
                                        }
                                        if (is_array($filas)) {
                                            foreach ($filas as $f) {
                                                $val = null;
                                                if (isset($f['clase'])) {
                                                    $val = strtolower($f['clase']);
                                                } elseif (isset($f['clase_nombre'])) {
                                                    $val = strtolower($f['clase_nombre']);
                                                } elseif (isset($f['tipo_modelo'])) {
                                                    $val = strtolower($f['tipo_modelo']);
                                                }
                                                if ($val) {
                                                    $parts = explode(',', $val);
                                                    foreach (['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'] as $kc) {
                                                        foreach ($parts as $p) {
                                                            if (trim($p) === $kc) {
                                                                $activeClassesForOt[] = $kc;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                // Filtrar clases activas basándose en las decisiones de Calidad o liberaciones registradas
                                /** @var \App\Models\FundicionHistory $reg */
                                $isReproceso = preg_match('/_R\d+$/i', $reg->ot);
                                if (empty($activeClassesForOt)) {
                                    if ($isReproceso) {
                                        $classesInCurrentOtRaw = \App\Models\LiberacionModeloFundicion::where('ot', '=', $reg->ot)
                                            ->whereNotNull('tipo_modelo')
                                            ->pluck('tipo_modelo')
                                            ->toArray();

                                        $parsedCurrent = [];
                                        foreach ($classesInCurrentOtRaw as $dc) {
                                            $parts = explode(',', strtolower($dc));
                                            foreach ($parts as $p) {
                                                $p = trim($p);
                                                if ($p !== '') {
                                                    foreach (['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo', 'pistones', 'guías', 'guias'] as $kc) {
                                                        if (strpos($p, $kc) !== false) {
                                                            $parsedCurrent[] = $kc;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (!empty($parsedCurrent)) {
                                            $activeClassesForOt = array_unique($parsedCurrent);
                                        } else {
                                            $baseOt = preg_replace('/_(?:(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias)(?:_(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias))*_)?R\d+$/iu', '', $reg->ot);
                                            $latestRechazo = \App\Models\LiberacionModeloFundicion::where('ot', 'LIKE', $baseOt . '%', 'and')
                                                ->where('ot', '!=', $reg->ot, 'and')
                                                ->where('decision', '=', 'rechazar', 'and')
                                                ->orderBy('id', 'desc')
                                                ->first();
                                            $prevOt = $latestRechazo ? $latestRechazo->ot : $baseOt;
                                            $rejectedPrevRaw = \App\Models\LiberacionModeloFundicion::where('ot', '=', $prevOt)
                                                ->where('decision', '=', 'rechazar')
                                                ->pluck('tipo_modelo')
                                                ->toArray();

                                            $parsedPrev = [];
                                            foreach ($rejectedPrevRaw as $dc) {
                                                $parts = explode(',', strtolower($dc));
                                                foreach ($parts as $p) {
                                                    $p = trim($p);
                                                    if ($p !== '') {
                                                        foreach (['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo', 'pistones', 'guías', 'guias'] as $kc) {
                                                            if (strpos($p, $kc) !== false) {
                                                                $parsedPrev[] = $kc;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                            if (!empty($parsedPrev)) {
                                                $activeClassesForOt = array_unique($parsedPrev);
                                            }
                                        }
                                    } else {
                                        $classesRaw = \App\Models\LiberacionModeloFundicion::where('ot', '=', $reg->ot)
                                            ->whereNotNull('tipo_modelo')
                                            ->pluck('tipo_modelo')
                                            ->toArray();

                                        $parsedClasses = [];
                                        foreach ($classesRaw as $dc) {
                                            $parts = explode(',', strtolower($dc));
                                            foreach ($parts as $p) {
                                                $p = trim($p);
                                                if ($p !== '') {
                                                    foreach (['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo', 'pistones', 'guías', 'guias'] as $kc) {
                                                        if (strpos($p, $kc) !== false) {
                                                            $parsedClasses[] = $kc;
                                                            break;
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        if (!empty($parsedClasses)) {
                                            $activeClassesForOt = array_unique($parsedClasses);
                                        }
                                    }
                                } else {
                                    $classesRaw = \App\Models\LiberacionModeloFundicion::where('ot', '=', $reg->ot)
                                        ->whereNotNull('tipo_modelo')
                                        ->pluck('tipo_modelo')
                                        ->toArray();
                                    foreach ($classesRaw as $dc) {
                                        $parts = explode(',', strtolower($dc));
                                        foreach ($parts as $p) {
                                            $p = trim($p);
                                            if ($p !== '') {
                                                foreach (['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo', 'pistones', 'guías', 'guias'] as $kc) {
                                                    if (strpos($p, $kc) !== false && !in_array($kc, $activeClassesForOt)) {
                                                        $activeClassesForOt[] = $kc;
                                                        break;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if (empty($activeClassesForOt)) {
                                    $activeClassesForOt = ['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'];
                                }
                                $activeClassesForOt = array_values(array_unique($activeClassesForOt));

                                // Para reprocesos, las decisiones de rechazo están en la OT anterior (_R0, _R1, ...)
                                // Para OTs base, están en la misma OT.
                                $baseOt = preg_replace('/_(?:(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias)(?:_(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias))*_)?R\d+$/iu', '', $reg->ot);
                                $latestRechazo = \App\Models\LiberacionModeloFundicion::where('ot', 'LIKE', $baseOt . '%', 'and')
                                    ->where('ot', '!=', $reg->ot, 'and')
                                    ->where('decision', '=', 'rechazar', 'and')
                                    ->orderBy('id', 'desc')
                                    ->first();
                                $otParaRechazados = $latestRechazo ? $latestRechazo->ot : $reg->ot;
                                $clasesRechazadas = \App\Models\LiberacionModeloFundicion::where('ot', $otParaRechazados)
                                    ->where('decision', 'rechazar')
                                    ->pluck('tipo_modelo')
                                    ->map(function ($modelo) {
                                        return strtolower(trim($modelo));
                                    })
                                    ->toArray();


                                // Cuando esta OT ES un reproceso (_R1, _R2...) y tiene
                                // pre-orden generada, los dibujos/ayudas de las clases
                                // rechazadas ya estan siendo trabajadas nuevamente:
                                // mostrarlas como aprobadas (limpiar clasesRechazadas).
                                $reprocesoTienePreOrden = false;
                                if (preg_match('/_R\d+$/i', $reg->ot) && !empty($clasesRechazadas)) {
                                    $reprocesoTienePreOrden = (
                                        $reg->pre_orden_sent
                                        || $reg->pre_orden_email_sent
                                        || \App\Models\PreOrdenFundicion::where('ot', $reg->ot)->exists()
                                    );
                                    if ($reprocesoTienePreOrden) {
                                        $clasesRechazadas = [];
                                    }
                                }
                                $rechazadosDibujos = [];
                                $rechazadosAyudas = [];
                                $rechazadosOtros = [];
                                $archivos = [];
                                $dibujoBaseNames = [];
                                foreach ($relatedRecords as $relRec) {
                                    // No mezclar dibujos de OTs de reproceso (_R1, _R2...) en la OT base u otras OTs
                                    if ($relRec->ot !== $reg->ot && preg_match('/_R\d+$/i', $relRec->ot)) {
                                        continue;
                                    }
                                    if (preg_match('/_R\d+$/i', $reg->ot) && $relRec->ot !== $reg->ot && preg_match('/_R\d+$/i', $relRec->ot)) {
                                        continue;
                                    }

                                    $relArchivos = is_array($relRec->almacen_archivos) ? $relRec->almacen_archivos : [];
                                    foreach ($relArchivos as $archivo) {
                                        $base = basename($archivo);
                                        $fileLower = strtolower($archivo);
                                        $baseLower = strtolower($base);

                                        $isNonDrawing = (
                                            strpos($fileLower, 'ayudas_visuales') !== false ||
                                            strpos($fileLower, 'ayudas-visuales') !== false ||
                                            strpos($fileLower, 'preordenes') !== false ||
                                            strpos($fileLower, 'preorden') !== false ||
                                            strpos($fileLower, 'escaneados') !== false ||
                                            strpos($fileLower, 'documentos_aprobados') !== false ||
                                            strpos($fileLower, 'documentos_rechazados') !== false ||
                                            strpos($baseLower, 'f_alm_') !== false ||
                                            strpos($baseLower, 'f_ccl_') !== false ||
                                            strpos($baseLower, 'cfm') !== false ||
                                            strpos($baseLower, 'efm') !== false ||
                                            strpos($baseLower, 'pfm') !== false ||
                                            strpos($baseLower, 'pfc') !== false ||
                                            strpos($baseLower, 'efc') !== false ||
                                            strpos($baseLower, 'ldm') !== false ||
                                            strpos($baseLower, 'rdm') !== false ||
                                            strpos($baseLower, 'scar') !== false
                                        );

                                        if ($isNonDrawing) {
                                            continue;
                                        }

                                        $knownClasses = ['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'];
                                        $hasKnownClass = false;
                                        $foundClass = null;
                                        foreach ($knownClasses as $kc) {
                                            if (strpos($fileLower, $kc) !== false) {
                                                $hasKnownClass = true;
                                                $foundClass = $kc;
                                                break;
                                            }
                                        }
                                        if ($hasKnownClass) {
                                            // Los dibujos SIEMPRE se muestran, aunque la clase esté rechazada.
                                            // Son documentos de referencia permanentes.
                                            $matchesActive = in_array($foundClass, $activeClassesForOt);
                                            $matchesRejected = in_array($foundClass, $clasesRechazadas);
                                            if (!$matchesActive && !$matchesRejected)
                                                continue;
                                        } else {
                                            if (!$allowFileCrossOt($relRec->ot)) {
                                                continue;
                                            }
                                        }
                                        if (!in_array($base, $dibujoBaseNames)) {
                                            $archivos[] = [
                                                'nombre' => $archivo,
                                                'ot' => $relRec->ot,
                                                'tipo' => 'dibujo',
                                                'origin' => 'dibujo',
                                                'owner' => 'almacen',
                                            ];
                                            $dibujoBaseNames[] = $base;
                                        }
                                    }
                                }
                                $countDibujos = count($archivos);

                                $ayudasArchivos = [];
                                $otrosArchivos = [];
                                $ayudasBaseNames = [];
                                $normAyudasBaseNames = [];
                                $baseNames = $dibujoBaseNames;
                                $normBaseNames = array_map(function ($b) {
                                    return strtolower(preg_replace('/[\s_]+/', '', $b));
                                }, $baseNames);

                                // --- NUEVO: Escanear ayudas visuales globales desde AYUDAS_FUNDICION ---
                                $ayudasGlobalesBase = 'DOCUMENTACION_GIS/AYUDAS_FUNDICION';
                                foreach ($activeClassesForOt as $activeClass) {
                                    $classNameProper = ucfirst(strtolower($activeClass));

                                    $candidateDirs = [
                                        $ayudasGlobalesBase . '/' . $classNameProper,
                                        $ayudasGlobalesBase . '/' . $classNameProper . '/Fundicion'
                                    ];

                                    foreach ($candidateDirs as $globalClassDir) {
                                        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($globalClassDir)) {
                                            $files = \Illuminate\Support\Facades\Storage::disk('local')->files($globalClassDir);
                                            foreach ($files as $f) {
                                                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                                                if ($ext === 'pdf') {
                                                    $base = basename($f);
                                                    $fileLower = strtolower($f);
                                                    $baseLower = strtolower($base);
                                                    $isNonDrawingFile = (
                                                        strpos($fileLower, 'ayudas_visuales') !== false ||
                                                        strpos($fileLower, 'ayudas-visuales') !== false ||
                                                        strpos($fileLower, 'preordenes') !== false ||
                                                        strpos($fileLower, 'preorden') !== false ||
                                                        strpos($fileLower, 'escaneados') !== false ||
                                                        strpos($fileLower, 'documentos_aprobados') !== false ||
                                                        strpos($fileLower, 'documentos_rechazados') !== false ||
                                                        strpos($baseLower, 'f_alm_') !== false ||
                                                        strpos($baseLower, 'f_ccl_') !== false ||
                                                        strpos($baseLower, 'cfm') !== false ||
                                                        strpos($baseLower, 'efm') !== false ||
                                                        strpos($baseLower, 'pfm') !== false ||
                                                        strpos($baseLower, 'pfc') !== false ||
                                                        strpos($baseLower, 'efc') !== false ||
                                                        strpos($baseLower, 'ldm') !== false ||
                                                        strpos($baseLower, 'rdm') !== false ||
                                                        strpos($baseLower, 'scar') !== false
                                                    );
                                                    if ($isNonDrawingFile) {
                                                        continue;
                                                    }
                                                    $normBase = strtolower(preg_replace('/[\s_]+/', '', $base));
                                                    if (!in_array($normBase, $normAyudasBaseNames)) {
                                                        $ayudaData = [
                                                            'nombre' => $classNameProper . '/' . $base,
                                                            'url' => route('ayudas_fundicion.serve', ['clase' => $classNameProper, 'archivo' => $base]),
                                                            'tipo' => 'ayuda',
                                                            'ot' => $reg->ot,
                                                        ];

                                                        // Las ayudas globales SIEMPRE se muestran, aunque la clase esté rechazada.
                                                        // Son documentos de referencia permanentes.
                                                        $ayudasArchivos[] = $ayudaData;

                                                        $ayudasBaseNames[] = $base;
                                                        $normAyudasBaseNames[] = $normBase;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                $liberacionesPath = storage_path('app/public/liberaciones_pdf');

                                foreach ($allOtNames as $otName) {
                                    if ($otName !== $reg->ot && preg_match('/_R\d+$/i', $otName)) {
                                        continue;
                                    }
                                    if (preg_match('/_R\d+$/i', $reg->ot) && $otName !== $reg->ot && preg_match('/_R\d+$/i', $otName)) {
                                        continue;
                                    }

                                    $otNameSanitized = trim(
                                        preg_replace('/[\/\\\\]/', '', preg_replace('/\.\.+/', '', $otName)),
                                    );

                                    // 1. Escanear ayudas visuales de Almacen (Legacy y Nueva Estructura)
                                    $ayudasDir = 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/ayudas_visuales';
                                    $almacenRootScan = 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized;

                                    $scanDirs = [];
                                    if (\Illuminate\Support\Facades\Storage::disk('local')->exists($ayudasDir)) {
                                        $scanDirs[] = [
                                            'path' => $ayudasDir,
                                            'base_dir' => $ayudasDir,
                                        ];
                                    }
                                    foreach (['Candado obturador', 'Cabeza de soplo', 'Obturador', 'Bombillo', 'Embudo', 'Corona', 'Plato', 'Molde', 'Fondo'] as $claseDir) {
                                        $subDirs = [
                                            $claseDir . '/Ayudas_Visuales',
                                            strtoupper($claseDir) . '/AYUDAS_VISUALES_FUNDICION',
                                            $claseDir . '/AYUDAS_VISUALES_FUNDICION',
                                        ];
                                        foreach ($subDirs as $subDir) {
                                            $newAyDir = $almacenRootScan . '/' . $subDir;
                                            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($newAyDir)) {
                                                $scanDirs[] = [
                                                    'path' => $newAyDir,
                                                    'base_dir' => $almacenRootScan,
                                                ];
                                            }
                                        }
                                        $legacyClaseAyDir = $ayudasDir . '/' . $claseDir;
                                        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($legacyClaseAyDir)) {
                                            $scanDirs[] = [
                                                'path' => $legacyClaseAyDir,
                                                'base_dir' => $ayudasDir,
                                            ];
                                        }
                                    }

                                    foreach ($scanDirs as $sInfo) {
                                        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($sInfo['path'])) {
                                            $files = \Illuminate\Support\Facades\Storage::disk('local')->allFiles($sInfo['path']);
                                            foreach ($files as $f) {
                                                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                                                $isPdf = $ext === 'pdf';
                                                $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                                                if (!$isPdf && !$isImage)
                                                    continue;

                                                $fNorm = str_replace('\\', '/', $f);
                                                $dirNorm = str_replace('\\', '/', $sInfo['base_dir']);
                                                $relativePath = ltrim(str_replace($dirNorm, '', $fNorm), '/');
                                                $base = basename($relativePath);

                                                $fileLower = strtolower($relativePath);
                                                $knownClasses = ['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'];
                                                $hasKnownClass = false;
                                                foreach ($knownClasses as $kc) {
                                                    if (strpos($fileLower, $kc) !== false) {
                                                        $hasKnownClass = true;
                                                        break;
                                                    }
                                                }
                                                if ($hasKnownClass) {
                                                    // Las ayudas SIEMPRE se muestran aunque la clase esté rechazada.
                                                    $matchesActive = false;
                                                    foreach ($activeClassesForOt as $ac) {
                                                        if (strpos($fileLower, $ac) !== false) {
                                                            $matchesActive = true;
                                                            break;
                                                        }
                                                    }
                                                    // Incluir también archivos de clases rechazadas (siguen siendo referencia)
                                                    if (!$matchesActive) {
                                                        foreach ($clasesRechazadas as $rc) {
                                                            if (strpos($fileLower, $rc) !== false) {
                                                                $matchesActive = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if (!$matchesActive)
                                                        continue;
                                                } else {
                                                    if (!$allowFileCrossOt($otName)) {
                                                        continue;
                                                    }
                                                }

                                                $normBase = strtolower(preg_replace('/[\s_]+/', '', $base));
                                                if (str_starts_with($relativePath, 'preordenes/')) {
                                                    if (!in_array($normBase, $normBaseNames)) {
                                                        $otrosArchivos[] = [
                                                            'nombre' => $relativePath,
                                                            'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $relativePath, 'tipo' => 'otro']),
                                                            'tipo' => $isImage ? 'imagen' : 'otro',
                                                            'ot' => $otName,
                                                            'origin' => 'otro',
                                                            'owner' => 'almacen',
                                                        ];
                                                        $baseNames[] = $base;
                                                        $normBaseNames[] = $normBase;
                                                    }
                                                } elseif ($isPdf) {
                                                    if (!in_array($normBase, $normAyudasBaseNames)) {
                                                        $ayudasArchivos[] = [
                                                            'nombre' => $relativePath,
                                                            'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $relativePath, 'tipo' => 'ayuda']),
                                                            'tipo' => 'ayuda',
                                                            'ot' => $otName,
                                                        ];
                                                        $ayudasBaseNames[] = $base;
                                                        $normAyudasBaseNames[] = $normBase;
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // 2. Escanear ayudas visuales de Calidad
                                    $calidadDir = 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/ayudas_visuales/preordenes';
                                    if (\Illuminate\Support\Facades\Storage::disk('local')->exists($calidadDir)) {
                                        $files = \Illuminate\Support\Facades\Storage::disk('local')->allFiles($calidadDir);
                                        foreach ($files as $f) {
                                            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                                            $isPdf = $ext === 'pdf';
                                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                                            if (!$isPdf && !$isImage)
                                                continue;

                                            $fNorm = str_replace('\\', '/', $f);
                                            $dirNorm = str_replace('\\', '/', $calidadDir);
                                            $relativePath = ltrim(str_replace($dirNorm, '', $fNorm), '/');
                                            $base = basename($relativePath);

                                            $fileLower = strtolower($relativePath);
                                            $knownClasses = ['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'];
                                            $hasKnownClass = false;
                                            foreach ($knownClasses as $kc) {
                                                if (strpos($fileLower, $kc) !== false) {
                                                    $hasKnownClass = true;
                                                    break;
                                                }
                                            }
                                            if ($hasKnownClass) {
                                                // Ayudas de preordenes de Calidad SIEMPRE visibles (documentos de referencia).
                                                $matchesActive = false;
                                                foreach ($activeClassesForOt as $ac) {
                                                    if (strpos($fileLower, $ac) !== false) {
                                                        $matchesActive = true;
                                                        break;
                                                    }
                                                }
                                                // Incluir clases rechazadas también
                                                if (!$matchesActive) {
                                                    foreach ($clasesRechazadas as $rc) {
                                                        if (strpos($fileLower, $rc) !== false) {
                                                            $matchesActive = true;
                                                            break;
                                                        }
                                                    }
                                                }
                                                if (!$matchesActive)
                                                    continue;
                                            } else {
                                                if (!$allowFileCrossOt($otName)) {
                                                    continue;
                                                }
                                            }

                                            $normBase = strtolower(preg_replace('/[\s_]+/', '', $base));
                                            if (!in_array($normBase, $normBaseNames)) {
                                                $origin = 'otro';
                                                if (strpos($relativePath, 'documentos_aprobados') !== false) {
                                                    $origin = 'aprobado';
                                                } elseif (strpos($relativePath, 'documentos_rechazados') !== false) {
                                                    $origin = 'rechazado';
                                                }
                                                $relativePathWithPrefix = 'preordenes/' . $relativePath;

                                                $otrosArchivos[] = [
                                                    'nombre' => $relativePathWithPrefix,
                                                    'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $relativePathWithPrefix, 'tipo' => 'otro', 'origin' => $origin]),
                                                    'tipo' => $isImage ? 'imagen' : 'otro',
                                                    'ot' => $otName,
                                                    'origin' => $origin,
                                                    'owner' => 'calidad',
                                                ];
                                                $baseNames[] = $base;
                                                $normBaseNames[] = $normBase;
                                            }
                                        }
                                    }

                                    // 2b. Escanear Documentos_Aprobados, Documentos_Rechazados, Preordenes y Escaneados (SOLO PARA LA OT ACTUAL DEL REGISTRO)
                                    $newDirs = [];
                                    if ($otName === $reg->ot) {
                                        $newDirs = [
                                            ['dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/Documentos_Aprobados', 'origin' => 'aprobado', 'prefix' => 'Documentos_Aprobados/', 'owner' => 'almacen'],
                                            ['dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/DOCUMENTOS_APROBADOS', 'origin' => 'aprobado', 'prefix' => 'DOCUMENTOS_APROBADOS/', 'owner' => 'almacen'],
                                            ['dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/Documentos_Rechazados', 'origin' => 'rechazado', 'prefix' => 'Documentos_Rechazados/', 'owner' => 'almacen'],
                                            ['dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/DOCUMENTOS_RECHAZADOS', 'origin' => 'rechazado', 'prefix' => 'DOCUMENTOS_RECHAZADOS/', 'owner' => 'almacen'],
                                            ['dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/Documentos_Aprobados', 'origin' => 'aprobado', 'prefix' => 'Documentos_Aprobados/', 'owner' => 'calidad'],
                                            ['dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/DOCUMENTOS_APROBADOS', 'origin' => 'aprobado', 'prefix' => 'DOCUMENTOS_APROBADOS/', 'owner' => 'calidad'],
                                            ['dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/Documentos_Rechazados', 'origin' => 'rechazado', 'prefix' => 'Documentos_Rechazados/', 'owner' => 'calidad'],
                                            ['dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/DOCUMENTOS_RECHAZADOS', 'origin' => 'rechazado', 'prefix' => 'DOCUMENTOS_RECHAZADOS/', 'owner' => 'calidad'],
                                        ];

                                        // --- NUEVO: ESCANEAR RUTAS DE PREORDENES FALTANTES ---
                                        if ($otName === $reg->ot) {
                                            $preOrdenesCandidates = [
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/preordenes',
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/PREORDENES',
                                                'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/preordenes',
                                                'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/PREORDENES',
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/Documentos_Aprobados/preordenes',
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/DOCUMENTOS_APROBADOS/PREORDENES',
                                                'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/Documentos_Aprobados/preordenes',
                                                'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/DOCUMENTOS_APROBADOS/PREORDENES',
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/ayudas_visuales/preordenes',
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/ayudas_visuales/preordenes/documentos_aprobados',
                                                'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/ayudas_visuales/preordenes/documentos_aprobados',
                                                'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/preordenes/documentos_aprobados',
                                                'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/preordenes/documentos_aprobados',
                                            ];

                                            foreach ($preOrdenesCandidates as $poDir) {
                                                $owner = strpos($poDir, 'ALMACEN_FUNDICION') !== false ? 'almacen' : 'calidad';
                                                $newDirs[] = ['dir' => $poDir, 'origin' => 'aprobado', 'prefix' => 'preordenes/', 'owner' => $owner];
                                            }
                                        }

                                        // --- NUEVO: ESCANEAR PREORDENES Y DOCUMENTOS POR CLASE (MAYÚSCULAS Y MINÚSCULAS) ---
                                        $clasesBaseList = ['Candado obturador', 'Cabeza de soplo', 'Obturador', 'Bombillo', 'Embudo', 'Corona', 'Plato', 'Molde', 'Fondo', 'Pistones', 'Guías', 'Guias', 'General'];
                                        foreach ($clasesBaseList as $claseDir) {
                                            $claseVariants = array_values(array_unique([
                                                $claseDir,
                                                strtoupper($claseDir),
                                                ucfirst(strtolower($claseDir))
                                            ]));

                                            foreach ($claseVariants as $cVariant) {
                                                if ($otName === $reg->ot) {
                                                    // Preordenes
                                                    foreach (['PREORDENES', 'Preordenes', 'Preordenes_Fundicion', 'preordenes'] as $pSub) {
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $pSub,
                                                            'origin' => 'aprobado',
                                                            'prefix' => $cVariant . '/' . $pSub . '/',
                                                            'owner' => 'almacen'
                                                        ];
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $pSub,
                                                            'origin' => 'aprobado',
                                                            'prefix' => $cVariant . '/' . $pSub . '/',
                                                            'owner' => 'calidad'
                                                        ];
                                                    }

                                                    // Documentos Aprobados
                                                    foreach (['DOCUMENTOS_APROBADOS', 'Documentos_Aprobados', 'documentos_aprobados'] as $dSub) {
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $dSub,
                                                            'origin' => 'aprobado',
                                                            'prefix' => $cVariant . '/' . $dSub . '/',
                                                            'owner' => 'almacen'
                                                        ];
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $dSub,
                                                            'origin' => 'aprobado',
                                                            'prefix' => $cVariant . '/' . $dSub . '/',
                                                            'owner' => 'calidad'
                                                        ];
                                                        foreach (['Almacen', 'Calidad', 'ALMACEN', 'CALIDAD'] as $dept) {
                                                            $newDirs[] = [
                                                                'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $dSub . '/' . $dept,
                                                                'origin' => 'aprobado',
                                                                'prefix' => $cVariant . '/' . $dSub . '/' . $dept . '/',
                                                                'owner' => 'almacen'
                                                            ];
                                                            $newDirs[] = [
                                                                'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $dSub . '/' . $dept,
                                                                'origin' => 'aprobado',
                                                                'prefix' => $cVariant . '/' . $dSub . '/' . $dept . '/',
                                                                'owner' => 'calidad'
                                                            ];
                                                        }
                                                    }

                                                    // Documentos Rechazados
                                                    foreach (['DOCUMENTOS_RECHAZADOS', 'Documentos_Rechazados', 'documentos_rechazados'] as $rSub) {
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $rSub,
                                                            'origin' => 'rechazado',
                                                            'prefix' => $cVariant . '/' . $rSub . '/',
                                                            'owner' => 'almacen'
                                                        ];
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $rSub,
                                                            'origin' => 'rechazado',
                                                            'prefix' => $cVariant . '/' . $rSub . '/',
                                                            'owner' => 'calidad'
                                                        ];
                                                        foreach (['Almacen', 'Calidad', 'ALMACEN', 'CALIDAD'] as $dept) {
                                                            $newDirs[] = [
                                                                'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $rSub . '/' . $dept,
                                                                'origin' => 'rechazado',
                                                                'prefix' => $cVariant . '/' . $rSub . '/' . $dept . '/',
                                                                'owner' => 'almacen'
                                                            ];
                                                            $newDirs[] = [
                                                                'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $rSub . '/' . $dept,
                                                                'origin' => 'rechazado',
                                                                'prefix' => $cVariant . '/' . $rSub . '/' . $dept . '/',
                                                                'owner' => 'calidad'
                                                            ];
                                                        }
                                                    }

                                                    // Documentos Escaneados
                                                    foreach (['ESCANEADOS', 'Escaneados', 'escaneados', 'DOCUMENTOS_ESCANEADOS', 'Documentos_Escaneados', 'documentos_escaneados'] as $eSub) {
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $eSub,
                                                            'origin' => 'aprobado',
                                                            'prefix' => $cVariant . '/' . $eSub . '/',
                                                            'owner' => 'almacen'
                                                        ];
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $eSub,
                                                            'origin' => 'aprobado',
                                                            'prefix' => $cVariant . '/' . $eSub . '/',
                                                            'owner' => 'calidad'
                                                        ];
                                                        foreach (['Almacen', 'Calidad'] as $dept) {
                                                            $newDirs[] = [
                                                                'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $eSub . '/' . $dept,
                                                                'origin' => 'aprobado',
                                                                'prefix' => $cVariant . '/' . $eSub . '/' . $dept . '/',
                                                                'owner' => 'almacen'
                                                            ];
                                                            $newDirs[] = [
                                                                'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $otNameSanitized . '/' . $cVariant . '/' . $cVariant . '/' . $eSub . '/' . $dept,
                                                                'origin' => 'aprobado',
                                                                'prefix' => $cVariant . '/' . $eSub . '/' . $dept . '/',
                                                                'owner' => 'calidad'
                                                            ];
                                                        }
                                                    }
                                                }

                                                // --- FALLBACK PARA OTs DE REPROCESO: Buscar dibujos en carpeta de OT Padre Heredada ---
                                                $parentOtSanitized = preg_replace('/_.*_R\d+$|_R\d+$/i', '', $otNameSanitized);
                                                if ($parentOtSanitized && $parentOtSanitized !== $otNameSanitized) {
                                                    foreach (['DIBUJOS', 'Dibujos', 'dibujos', 'DIBUJOS_FUNDICION', 'Dibujos_Fundicion'] as $dibSub) {
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/ALMACEN_FUNDICION/' . $parentOtSanitized . '/' . $cVariant . '/' . $dibSub,
                                                            'origin' => 'dibujo',
                                                            'prefix' => $cVariant . '/' . $dibSub . '/',
                                                            'owner' => 'almacen'
                                                        ];
                                                        $newDirs[] = [
                                                            'dir' => 'DOCUMENTACION_GIS/CALIDAD_FUNDICION/' . $parentOtSanitized . '/' . $cVariant . '/' . $dibSub,
                                                            'origin' => 'dibujo',
                                                            'prefix' => $cVariant . '/' . $dibSub . '/',
                                                            'owner' => 'calidad'
                                                        ];
                                                    }
                                                }
                                            }
                                        }

                                        foreach ($newDirs as $dirInfo) {
                                            $targetDir = $dirInfo['dir'];
                                            $origin = $dirInfo['origin'];
                                            $prefix = $dirInfo['prefix'];

                                            if (\Illuminate\Support\Facades\Storage::disk('local')->exists($targetDir)) {
                                                $files = \Illuminate\Support\Facades\Storage::disk('local')->allFiles($targetDir);
                                                foreach ($files as $f) {
                                                    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                                                    $isPdf = $ext === 'pdf';
                                                    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                    $isDwg = $ext === 'dwg';

                                                    if (!$isPdf && !$isImage && !$isDwg)
                                                        continue;

                                                    $fNorm = str_replace('\\', '/', $f);
                                                    $dirNorm = str_replace('\\', '/', $targetDir);
                                                    $relativePath = ltrim(str_replace($dirNorm, '', $fNorm), '/');
                                                    $base = basename($relativePath);

                                                    $fileLower = strtolower($relativePath);
                                                    $fileClasses = [];
                                                    foreach ($knownClasses as $kc) {
                                                        if (strpos($fileLower, $kc) !== false) {
                                                            $fileClasses[] = $kc;
                                                        }
                                                    }
                                                    if (!empty($fileClasses)) {
                                                        // Verificar que la clase del archivo pertenece a esta OT (activas o rechazadas)
                                                        $hasInactiveClass = false;
                                                        foreach ($fileClasses as $fc) {
                                                            if (!in_array($fc, $activeClassesForOt) && !in_array($fc, $clasesRechazadas)) {
                                                                $hasInactiveClass = true;
                                                                break;
                                                            }
                                                        }
                                                        if ($hasInactiveClass) {
                                                            continue;
                                                        }
                                                        // El origin viene del directorio ($dirInfo['origin']), NO de la clase del archivo.
                                                        // Un ConfirmacionModelo en Documentos_Aprobados/ es siempre aprobado.
                                                    } else {
                                                        if ($otName !== $reg->ot) {
                                                            continue;
                                                        }
                                                    }

                                                    $normBase = strtolower(preg_replace('/[\s_]+/', '', $base));
                                                    if ($origin === 'dibujo' || $isDwg || strpos(strtolower($targetDir), 'dibujo') !== false) {
                                                        if (!in_array($base, $dibujoBaseNames)) {
                                                            $relativePathWithPrefix = $prefix . $relativePath;
                                                            $archivos[] = [
                                                                'nombre' => $relativePathWithPrefix,
                                                                'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $relativePathWithPrefix, 'tipo' => 'dibujo', 'origin' => 'dibujo']),
                                                                'tipo' => 'dibujo',
                                                                'ot' => $otName,
                                                                'origin' => 'dibujo',
                                                                'owner' => $dirInfo['owner'],
                                                            ];
                                                            $dibujoBaseNames[] = $base;
                                                            $baseNames[] = $base;
                                                            $normBaseNames[] = $normBase;
                                                        }
                                                    } elseif (!in_array($normBase, $normBaseNames)) {
                                                        $relativePathWithPrefix = $prefix . $relativePath;
                                                        $otrosArchivos[] = [
                                                            'nombre' => $relativePathWithPrefix,
                                                            'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $relativePathWithPrefix, 'tipo' => 'otro', 'origin' => $origin]),
                                                            'tipo' => $isImage ? 'imagen' : 'otro',
                                                            'ot' => $otName,
                                                            'origin' => $origin,
                                                            'owner' => $dirInfo['owner'],
                                                        ];
                                                        $baseNames[] = $base;
                                                        $normBaseNames[] = $normBase;
                                                    }
                                                }
                                            }
                                        }

                                        $otSanitizada = preg_replace('/[^\w\s\-]/', '', $otName);
                                        $otSanitizada = preg_replace('/[\s]+/', '_', trim($otSanitizada));

                                        if (file_exists($liberacionesPath) && $otName === $reg->ot) {
                                            // Buscar LDM y RDM PDFs generados para ESTA OT en public/liberaciones_pdf
                                            $otLow = mb_strtolower($otSanitizada, 'UTF-8');
                                            $otNameLow = mb_strtolower($otName, 'UTF-8');
                                            $ldmFiles = array_merge(
                                                glob("{$liberacionesPath}/*{$otSanitizada}*.pdf") ?: [],
                                                glob("{$liberacionesPath}/F*CCL*{$otSanitizada}*.pdf") ?: []
                                            );
                                            foreach (array_unique($ldmFiles) as $f) {
                                                $base = basename($f);
                                                $fileLower = mb_strtolower($base, 'UTF-8');
                                                if (!str_contains($fileLower, $otLow) && !str_contains($fileLower, $otNameLow)) {
                                                    continue;
                                                }

                                                // FIX: Evitar que documentos de OTs de Reproceso (ej. _R1) se muestren en la OT original
                                                $isReprocesoOT = (bool) preg_match('/_R\d+$/i', $otName);
                                                if (!$isReprocesoOT && preg_match('/_R\d+\.pdf$/i', $base)) {
                                                    continue; // Es un documento de un reproceso, omitir en la OT original
                                                }

                                                $knownClasses = ['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo', 'pistones', 'guías', 'guias'];
                                                $hasKnownClass = false;
                                                foreach ($knownClasses as $kc) {
                                                    if (strpos($fileLower, $kc) !== false) {
                                                        $hasKnownClass = true;
                                                        break;
                                                    }
                                                }
                                                if ($hasKnownClass) {
                                                    $matchesActive = false;
                                                    foreach ($activeClassesForOt as $ac) {
                                                        if (strpos($fileLower, $ac) !== false) {
                                                            $matchesActive = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$matchesActive)
                                                        continue;
                                                } else {
                                                    if (!$allowFileCrossOt($otName)) {
                                                        continue;
                                                    }
                                                }
                                                $normBase = strtolower(preg_replace('/[\s_]+/', '', $base));
                                                if (!in_array($normBase, $normBaseNames)) {
                                                    $isRechazado = strpos($fileLower, 'rdm') !== false || strpos($fileLower, 'rechazado') !== false;
                                                    $origin = $isRechazado ? 'rechazado' : 'aprobado';
                                                    $otrosArchivos[] = [
                                                        'nombre' => $base,
                                                        'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $base, 'tipo' => 'liberacion', 'origin' => $origin]),
                                                        'tipo' => 'liberacion',
                                                        'ot' => $otName,
                                                        'origin' => $origin,
                                                        'owner' => 'calidad',
                                                    ];
                                                    $baseNames[] = $base;
                                                    $normBaseNames[] = $normBase;
                                                }
                                            }

                                            // Buscar SCAR PDFs (digital y firmado)
                                            $scarPattern = "{$liberacionesPath}/F-CCL-SCAR_*_{$otSanitizada}*.pdf";
                                            $scarPattern2 = "{$liberacionesPath}/F-CCL-SCAR_{$otSanitizada}.pdf";
                                            $scarFiles = array_merge(glob($scarPattern) ?: [], glob($scarPattern2) ?: []);
                                            foreach (array_unique($scarFiles) as $f) {
                                                $base = basename($f);
                                                $fileLower = strtolower($base);
                                                
                                                // FIX: Evitar que documentos de OTs de Reproceso (ej. _R1) se muestren en la OT original
                                                $isReprocesoOT = (bool) preg_match('/_R\d+$/i', $otName);
                                                if (!$isReprocesoOT && preg_match('/_R\d+\.pdf$/i', $base)) {
                                                    continue; // Es un documento de un reproceso, omitir en la OT original
                                                }

                                                $knownClasses = ['candado obturador', 'cabeza de soplo', 'obturador', 'bombillo', 'embudo', 'corona', 'plato', 'molde', 'fondo'];
                                                $hasKnownClass = false;
                                                foreach ($knownClasses as $kc) {
                                                    if (strpos($fileLower, $kc) !== false) {
                                                        $hasKnownClass = true;
                                                        break;
                                                    }
                                                }
                                                if ($hasKnownClass) {
                                                    $matchesActive = false;
                                                    foreach ($activeClassesForOt as $ac) {
                                                        if (strpos($fileLower, $ac) !== false) {
                                                            $matchesActive = true;
                                                            break;
                                                        }
                                                    }
                                                    if (!$matchesActive)
                                                        continue;
                                                } else {
                                                    if (!$allowFileCrossOt($otName)) {
                                                        continue;
                                                    }
                                                }
                                                $normBase = strtolower(preg_replace('/[\s_]+/', '', $base));
                                                if (!in_array($normBase, $normBaseNames)) {
                                                    $rechazadosOtros[] = [
                                                        'nombre' => $base,
                                                        'url' => route('almacen.fundicion.serve', ['ot' => $otName, 'archivo' => $base, 'tipo' => 'liberacion', 'origin' => 'rechazado']),
                                                        'tipo' => 'liberacion',
                                                        'ot' => $otName,
                                                        'origin' => 'rechazado',
                                                        'owner' => 'calidad',
                                                    ];
                                                    $baseNames[] = $base;
                                                    $normBaseNames[] = $normBase;
                                                }
                                            }
                                        }
                                    }

                                 }
                                // Aplicar filtros de visibilidad
                                $userPerfil = Auth::user()->perfil;
                                $filteredOtros = [];
                                foreach ($otrosArchivos as $archivo) {
                                    $nameLow = strtolower($archivo['nombre']);
                                    $isPreorden = (
                                        ((in_array($archivo['tipo'], ['otro', 'imagen']) || str_starts_with($archivo['nombre'], 'preordenes/')) &&
                                            strpos($nameLow, 'ldm') === false &&
                                            strpos($nameLow, 'rdm') === false &&
                                            strpos($nameLow, 'scar') === false &&
                                            strpos($nameLow, 'confirmacion') === false &&
                                            strpos($nameLow, 'liberacion') === false) ||
                                        strpos($nameLow, 'escaneado') !== false
                                    );

                                    // Si el archivo es de Calidad y no es preorden ni confirmación, verificar que Calidad haya enviado efectivamente la alerta por correo
                                    if ($archivo['owner'] === 'calidad' && !$isPreorden && strpos($nameLow, 'confirmacion') === false) {
                                        /** @var \App\Models\FundicionHistory|null $fileHistory */
                                        $fileHistory = $relatedRecords->firstWhere('ot', $archivo['ot']);
                                        $status = $targetReg->calidad_revision_status ?? ($fileHistory ? $fileHistory->calidad_revision_status : null);
                                        $calidadAlertaEnviada = (
                                            in_array($status, ['calidad_aprobado', 'calidad_rechazado', 'calidad_mixto', 'calidad_parcial', 'casting_aprobado']) ||
                                            \App\Models\LiberacionModeloFundicion::where(function ($q) use ($archivo, $targetReg) {
                                                $q->where('ot', '=', $archivo['ot'])->orWhere('ot', '=', $targetReg->ot);
                                            })->where('alerta_enviada', true)->exists() ||
                                            \App\Models\ScarModelo::where(function ($q) use ($archivo, $targetReg) {
                                                $q->where('ot', '=', $archivo['ot'])->orWhere('ot', '=', $targetReg->ot);
                                            })->whereIn('estatus', ['alertado', 'cerrado'])->exists()
                                        );
                                        if (!$calidadAlertaEnviada) {
                                            continue; // Ocultar formatos F-CCL-LDM / SCAR en Almacén hasta que Calidad envíe la alerta
                                        }
                                    }

                                    if ($userPerfil != 1 && $userPerfil != 2) {
                                        if ($userPerfil == 4 || $userPerfil == 3) { // Calidad o Master
                                            // Calidad/Master solo ve preordenes si pre_orden_email_sent es true
                                            if ($isPreorden) {
                                                /** @var \App\Models\FundicionHistory|null $fileHistory */
                                                $fileHistory = $relatedRecords->firstWhere('ot', $archivo['ot']);
                                                $hasPreorden = ($fileHistory && $fileHistory->pre_orden_email_sent)
                                                    || !empty($targetReg->pre_orden_sent)
                                                    || !empty($targetReg->pre_orden_email_sent)
                                                    || \App\Models\PreOrdenFundicion::where('ot', $archivo['ot'])->orWhere('ot', $targetReg->ot)->exists();
                                                if (!$hasPreorden && strpos($nameLow, 'documentos_aprobados') === false) {
                                                    continue;
                                                }
                                            }
                                        } elseif ($userPerfil == 5) { // Almacén
                                            // Almacén ve preordenes y confirmaciones (calidad ya se filtró arriba)
                                        }
                                    }
                                    $filteredOtros[] = $archivo;
                                }
                                $otrosArchivos = $filteredOtros;

                                $almacenPreordenes = [];
                                $calidadAprobadosLdm = [];
                                $archivosRechazados = [];
                                foreach ($otrosArchivos as $archivo) {
                                    $nameLow = strtolower($archivo['nombre']);
                                    $baseLow = strtolower(basename($archivo['nombre']));
                                    if (
                                        strpos($nameLow, 'documentos_rechazados') !== false ||
                                        strpos($baseLow, 'rechazado') !== false ||
                                        strpos($baseLow, 'scar') !== false ||
                                        strpos($baseLow, 'rdm') !== false ||
                                        strpos($baseLow, 'fdrdm') !== false ||
                                        strpos($nameLow, 'f_ccl_rdm') !== false ||
                                        strpos($nameLow, 'f_ccl_scar') !== false
                                    ) {
                                        $archivosRechazados[] = $archivo;
                                    } elseif (
                                        strpos($nameLow, 'fdldm') !== false ||
                                        strpos($nameLow, 'f_ccl_ldm') !== false
                                    ) {
                                        $calidadAprobadosLdm[] = $archivo;
                                    } elseif (
                                        strpos($nameLow, 'preorden_casting') !== false ||
                                        strpos($nameLow, 'preorden_modelo') !== false ||
                                        strpos($nameLow, 'confirmacion_modelo') !== false ||
                                        strpos($baseLow, 'pre-orden') !== false ||
                                        strpos($baseLow, 'preorden') !== false ||
                                        strpos($baseLow, 'confirmacion') !== false ||
                                        strpos($baseLow, 'escaneado') !== false ||
                                        strpos($baseLow, 'pfm') !== false ||
                                        strpos($baseLow, 'cfm') !== false ||
                                        strpos($baseLow, 'efm') !== false ||
                                        strpos($baseLow, 'pfc') !== false ||
                                        strpos($baseLow, 'efc') !== false ||
                                        strpos($nameLow, 'f_alm_efc') !== false ||
                                        strpos($nameLow, 'f_alm_cfm') !== false ||
                                        strpos($nameLow, 'preordenes/') !== false ||
                                        strpos($nameLow, 'escaneados/') !== false
                                    ) {
                                        $almacenPreordenes[] = $archivo;
                                    } else {
                                        $calidadAprobadosLdm[] = $archivo;
                                    }
                                }
                                $archivosAprobados = $almacenPreordenes;
                                $countAprobados = count($calidadAprobadosLdm);
                                $countRechazados = count($archivosRechazados);

                                $countAyudas = count($ayudasArchivos);
                                $countOtros = count($otrosArchivos);

                                // ── CALCULAR APROBADOS Y RECHAZADOS DE CADA CLASE ──
                                // (Calculado ANTES de showControlCard para poder usarlos en la lógica de visibilidad)
                                $liberacionesAll = \App\Models\LiberacionModeloFundicion::where('ot', $targetReg->ot)->get();
                                $latestLiberacionesByClass = [];
                                foreach ($liberacionesAll as $lib) {
                                    $tipo = $lib->tipo_modelo;
                                    $libOt = $lib->ot;

                                    preg_match('/_R(\d+)$/', $libOt, $matches);
                                    $suffixNum = isset($matches[1]) ? (int) $matches[1] : 0;

                                    $shouldReplace = !isset($latestLiberacionesByClass[$tipo]);
                                    if (!$shouldReplace) {
                                        $existSuffix = $latestLiberacionesByClass[$tipo]['suffix'];
                                        $existId = $latestLiberacionesByClass[$tipo]['lib']->id;
                                        if ($suffixNum > $existSuffix || ($suffixNum === $existSuffix && $lib->id > $existId)) {
                                            $shouldReplace = true;
                                        }
                                    }
                                    if ($shouldReplace) {
                                        $latestLiberacionesByClass[$tipo] = [
                                            'lib' => $lib,
                                            'suffix' => $suffixNum
                                        ];
                                    }
                                }

                                $aprobadosRaw = [];
                                $rechazadosRaw = [];
                                foreach ($latestLiberacionesByClass as $tipo => $data) {
                                    $lib = $data['lib'];
                                    if ($lib->alerta_enviada) {
                                        if ($lib->decision === 'aprobar' || $lib->estado === 'aprobado' || $lib->estado === 'aprobada') {
                                            $aprobadosRaw[] = $tipo;
                                        } elseif ($lib->decision === 'rechazar' || $lib->estado === 'rechazado' || $lib->estado === 'rechazada') {
                                            $rechazadosRaw[] = $tipo;
                                        }
                                    }
                                }

                                // Extraer clases de archivos aprobados en $calidadAprobadosLdm
                                foreach ($calidadAprobadosLdm as $docAprob) {
                                    $baseName = basename($docAprob['nombre']);
                                    if (preg_match('/F_CCL_LDM_([^\.]+)/i', $baseName, $mMatches)) {
                                        $extractedClass = trim(str_replace(['_', '-'], ' ', $mMatches[1]));
                                        if (!empty($extractedClass)) {
                                            $aprobadosRaw[] = $extractedClass;
                                        }
                                    }
                                    foreach ($activeClassesForOt as $ac) {
                                        if (!empty($ac) && strpos(strtolower($baseName), strtolower($ac)) !== false) {
                                            $aprobadosRaw[] = $ac;
                                        }
                                    }
                                }

                                // Normalizar y deduplicar aprobados y rechazados con respecto a activeClassesForOt
                                $aprobados = [];
                                foreach ($aprobadosRaw as $cRaw) {
                                    $cLow = strtolower(trim($cRaw));
                                    if (empty($cLow))
                                        continue;
                                    $matched = false;
                                    foreach ($activeClassesForOt as $ac) {
                                        $acLow = strtolower(trim($ac));
                                        if ($cLow === $acLow || strpos($cLow, $acLow) !== false || strpos($acLow, $cLow) !== false) {
                                            $aprobados[] = $ac;
                                            $matched = true;
                                            break;
                                        }
                                    }
                                    if (!$matched) {
                                        $aprobados[] = trim($cRaw);
                                    }
                                }
                                $aprobados = array_values(array_unique($aprobados));

                                $rechazados = array_values(array_unique(array_filter($rechazadosRaw, function ($clase) use ($activeClassesForOt) {
                                    return in_array(strtolower($clase), array_map('strtolower', $activeClassesForOt));
                                })));

                                // Fallback: si $tieneAprobados o count($calidadAprobadosLdm) > 0 y $aprobados está vacío
                                $isCalidadAlertedLocal = in_array($reg->calidad_revision_status, ['calidad_aprobado', 'calidad_rechazado', 'calidad_mixto', 'calidad_parcial', 'casting_aprobado'])
                                    || \App\Models\LiberacionModeloFundicion::where('ot', $reg->ot)->where('alerta_enviada', true)->exists();
                                if (!$isReprocesoOT && empty($aprobados) && ($isCalidadAlertedLocal || count($calidadAprobadosLdm) > 0 || in_array($targetReg->calidad_revision_status, ['calidad_aprobado', 'casting_aprobado']))) {
                                    $rechazadosNormLocal = array_map('strtolower', $rechazados);
                                    $aprobados = array_values(array_filter($activeClassesForOt, function ($ac) use ($rechazadosNormLocal) {
                                        return !in_array(strtolower($ac), $rechazadosNormLocal);
                                    }));
                                    if (empty($aprobados) && !empty($activeClassesForOt)) {
                                        $aprobados = $activeClassesForOt;
                                    }
                                }
                                // Clasificar dibujos y ayudas por etapa (Fabricación de Modelo vs Casting)
                                $aprobadosNorm = array_map('strtolower', $aprobados);
                                $rechazadosNorm = array_map('strtolower', $rechazados);
                                $clasesFabricacion = array_values(array_diff(array_map('strtolower', $activeClassesForOt), array_merge($aprobadosNorm, $rechazadosNorm)));
                                $isMixedProcess = (count($aprobadosNorm) > 0 && count($clasesFabricacion) > 0);

                                $dibujosCasting = array_values(array_filter($archivos, function ($d) use ($aprobadosNorm) {
                                    $nameLow = strtolower($d['nombre']);
                                    foreach ($aprobadosNorm as $ap) {
                                        if ($ap !== '' && strpos($nameLow, $ap) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                $dibujosModelo = array_values(array_filter($archivos, function ($d) use ($clasesFabricacion) {
                                    $nameLow = strtolower($d['nombre']);
                                    foreach ($clasesFabricacion as $cf) {
                                        if ($cf !== '' && strpos($nameLow, $cf) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                $ayudasCasting = array_values(array_filter($ayudasArchivos, function ($a) use ($aprobadosNorm) {
                                    $nameLow = strtolower($a['nombre']);
                                    foreach ($aprobadosNorm as $ap) {
                                        if ($ap !== '' && strpos($nameLow, $ap) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                $ayudasModelo = array_values(array_filter($ayudasArchivos, function ($a) use ($clasesFabricacion) {
                                    $nameLow = strtolower($a['nombre']);
                                    foreach ($clasesFabricacion as $cf) {
                                        if ($cf !== '' && strpos($nameLow, $cf) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                $dibujosRechazadosOrig = array_values(array_filter($archivos, function ($d) use ($rechazadosNorm) {
                                    $nameLow = strtolower($d['nombre']);
                                    foreach ($rechazadosNorm as $r) {
                                        if ($r !== '' && strpos($nameLow, $r) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                $ayudasRechazadosOrig = array_values(array_filter($ayudasArchivos, function ($a) use ($rechazadosNorm) {
                                    $nameLow = strtolower($a['nombre']);
                                    foreach ($rechazadosNorm as $r) {
                                        if ($r !== '' && strpos($nameLow, $r) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                // Clasificar rechazados:
                                // Guardar primero los que vienen del escaneo de filesystem (acumulados arriba)
                                // y limpiar los arrays para que la reclasificación empiece de cero.
                                $rechazadosOtrosFilesystem = $rechazadosOtros ?? [];
                                $rechazadosAyudasFilesystem = $rechazadosAyudas ?? [];
                                $rechazadosDibujos = $rechazadosDibujos ?? [];
                                // Reclasificar $archivosRechazados (los de la lógica de otrosArchivos)
                                $rechazadosAyudas = [];
                                $rechazadosOtros = [];
                                foreach ($archivosRechazados as $rArchivo) {
                                    $nameLow = strtolower($rArchivo['nombre']);
                                    $ext = pathinfo($nameLow, PATHINFO_EXTENSION);
                                    $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                                    $rArchivo['ot'] = $rArchivo['ot'] ?? $reg->ot;
                                    $rArchivo['tipo'] = $rArchivo['tipo'] ?? ($isImg ? 'imagen' : 'otro');

                                    if (strpos($nameLow, 'scar') !== false || strpos($nameLow, 'f_ccl_scar') !== false || strpos($nameLow, 'f_ccl_rdm') !== false || strpos($nameLow, 'foto') !== false) {
                                        $rechazadosOtros[] = $rArchivo;
                                    } elseif (strpos($nameLow, 'ayudas_visuales') !== false || strpos($nameLow, 'ayudas-visuales') !== false || $isImg) {
                                        if ($rArchivo['tipo'] === 'otro')
                                            $rArchivo['tipo'] = 'ayuda';
                                        $rechazadosAyudas[] = $rArchivo;
                                    } elseif (strpos($nameLow, 'dibujos') !== false || strpos($nameLow, 'dibujo') !== false) {
                                        $rechazadosDibujos[] = $rArchivo;
                                    } else {
                                        $rechazadosOtros[] = $rArchivo;
                                    }
                                }
                                // Combinar los del filesystem con los reclasificados, deduplicando por nombre base
                                $baseNamesRechOtros = array_map(fn($a) => basename($a['nombre']), $rechazadosOtros);
                                foreach ($rechazadosOtrosFilesystem as $rFs) {
                                    if (!in_array(basename($rFs['nombre']), $baseNamesRechOtros)) {
                                        $rechazadosOtros[] = $rFs;
                                        $baseNamesRechOtros[] = basename($rFs['nombre']);
                                    }
                                }
                                $baseNamesRechAyudas = array_map(fn($a) => basename($a['nombre']), $rechazadosAyudas);
                                foreach ($rechazadosAyudasFilesystem as $rFs) {
                                    if (!in_array(basename($rFs['nombre']), $baseNamesRechAyudas)) {
                                        $rechazadosAyudas[] = $rFs;
                                        $baseNamesRechAyudas[] = basename($rFs['nombre']);
                                    }
                                }
                                // ── CONTROL DE VISIBILIDAD DE LA CARD DE ALMACÉN Y CONTEO EXACTO DE TARJETAS ──
                                $hasVerdictosPendientes = count($aprobados) > 0 || count($rechazados) > 0;
                                $isFinalized = ($targetReg->calidad_revision_status === 'casting_aprobado') && !$hasVerdictosPendientes;
                                if ($hasVerdictosPendientes) {
                                    $isFinalized = false;
                                }
                                $isCalidadAlerted = in_array($reg->calidad_revision_status, ['calidad_aprobado', 'calidad_rechazado', 'calidad_mixto', 'calidad_parcial', 'casting_aprobado'])
                                    || \App\Models\LiberacionModeloFundicion::where('ot', $reg->ot)->where('alerta_enviada', true)->exists();

                                $castingEmailSent = ($reg->calidad_revision_status === 'casting_aprobado');

                                $rechazadosSinPreorden = [];
                                if ($isCalidadAlerted && count($rechazados) > 0 && !$reg->rechazos_procesados) {
                                    $rechazadosNormFab = array_map('strtolower', $rechazados);
                                    $preordenesSentClassesFab = [];
                                    $preOrdenesEnviadasFab = \App\Models\PreOrdenFundicion::where('ot', $targetReg->ot)->where('is_sent', 1)->get();
                                    foreach ($preOrdenesEnviadasFab as $poFab) {
                                        $filasFab = is_string($poFab->filas) ? json_decode($poFab->filas, true) : $poFab->filas;
                                        if (is_array($filasFab)) {
                                            foreach ($filasFab as $fFab) {
                                                if (!empty($fFab['clase'] ?? $fFab['clase_nombre'])) {
                                                    $preordenesSentClassesFab[] = strtolower($fFab['clase'] ?? $fFab['clase_nombre']);
                                                }
                                            }
                                        }
                                    }
                                    foreach ($rechazadosNormFab as $rClase) {
                                        $cubiertaFab = false;
                                        foreach ($preordenesSentClassesFab as $psc) {
                                            if (strpos($psc, $rClase) !== false || strpos($rClase, $psc) !== false) {
                                                $cubiertaFab = true;
                                                break;
                                            }
                                        }
                                        if (!$cubiertaFab) {
                                            $rechazadosSinPreorden[] = $rClase;
                                        }
                                    }
                                }
                                $hayRechazadosSinPreorden = count($rechazadosSinPreorden) > 0;

                                $aprobadosNorm = array_map('strtolower', $aprobados);
                                $rechazadosNorm = array_map('strtolower', $rechazados);

                                $almacenPreordenesFab = array_values(array_filter($almacenPreordenes, function ($doc) use ($clasesFabricacion) {
                                    $pathLow = strtolower($doc['nombre']);
                                    $nameLow = strtolower(basename($doc['nombre']));
                                    if (
                                        str_contains($pathLow, 'preorden_casting') ||
                                        str_contains($pathLow, 'casting') ||
                                        str_contains($pathLow, 'fdldm') ||
                                        str_contains($nameLow, 'pfc') ||
                                        str_contains($nameLow, 'f_alm_pfc') ||
                                        str_contains($nameLow, 'efc') ||
                                        str_contains($nameLow, 'f_alm_efc') ||
                                        str_contains($nameLow, 'f_ccl_ldm') ||
                                        str_contains($nameLow, 'fdldm')
                                    ) {
                                        return false;
                                    }
                                    if (empty($clasesFabricacion))
                                        return true;
                                    if (
                                        str_contains($nameLow, 'preorden') ||
                                        str_contains($nameLow, 'pre-orden') ||
                                        str_contains($nameLow, 'escaneado') ||
                                        str_contains($pathLow, 'escaneados') ||
                                        str_contains($nameLow, 'cfm') ||
                                        str_contains($nameLow, 'f_alm_cfm') ||
                                        str_contains($nameLow, 'pfm') ||
                                        str_contains($nameLow, 'efm') ||
                                        str_contains($pathLow, 'preorden_modelo') ||
                                        str_contains($pathLow, 'confirmacion_modelo')
                                    )
                                        return true;
                                    foreach ($clasesFabricacion as $cf) {
                                        if ($cf !== '' && strpos($nameLow, $cf) !== false)
                                            return true;
                                    }
                                    return false;
                                }));

                                $tieneArchivosFabricacion = count($dibujosModelo) > 0 || count($ayudasModelo) > 0 || count($almacenPreordenesFab) > 0;
                                $tieneFabricacion = (!$isCalidadAlerted && !$castingEmailSent) || $hayRechazadosSinPreorden || $tieneArchivosFabricacion;

                                $clasesFabricacionHeader = $clasesFabricacion;
                                if (empty($clasesFabricacionHeader)) {
                                    $archivosFabAll = array_merge($almacenPreordenesFab, $dibujosModelo, $ayudasModelo);
                                    $extractedFab = [];
                                    foreach ($archivosFabAll as $af) {
                                        $bName = basename($af['nombre']);
                                        foreach ($activeClassesForOt as $ac) {
                                            if (!empty($ac) && strpos(strtolower($bName), strtolower($ac)) !== false) {
                                                $extractedFab[] = $ac;
                                            }
                                        }
                                    }
                                    $clasesFabricacionHeader = array_values(array_unique($extractedFab));
                                    if (empty($clasesFabricacionHeader)) {
                                        $clasesFabricacionHeader = $activeClassesForOt;
                                    }
                                }
                                $aprobadosNorm = array_map('strtolower', $aprobados ?? []);
                                $rechazadosNorm = array_map('strtolower', $rechazados ?? []);

                                $calidadAprobadosLdmCasting = array_values(array_filter($calidadAprobadosLdm ?? [], function ($doc) use ($aprobadosNorm, $rechazadosNorm) {
                                    $nameLow = strtolower(basename($doc['nombre']));
                                    if (!empty($rechazadosNorm)) {
                                        $mencionaRechazada = false;
                                        foreach ($rechazadosNorm as $rCl) {
                                            if ($rCl !== '' && strpos($nameLow, $rCl) !== false) {
                                                $mencionaRechazada = true;
                                                break;
                                            }
                                        }
                                        if ($mencionaRechazada) {
                                            $mencionaAprobada = false;
                                            foreach ($aprobadosNorm as $ap) {
                                                if ($ap !== '' && strpos($nameLow, $ap) !== false) {
                                                    $mencionaAprobada = true;
                                                    break;
                                                }
                                            }
                                            if (!$mencionaAprobada) {
                                                return false;
                                            }
                                        }
                                    }
                                    return true;
                                }));

                                $almacenPreordenesCasting = array_values(array_filter($almacenPreordenes ?? [], function ($doc) use ($aprobadosNorm) {
                                    $pathLow = strtolower($doc['nombre']);
                                    $nameLow = strtolower(basename($doc['nombre']));
                                    $isCastingDoc = (
                                        str_contains($pathLow, 'preorden_casting') ||
                                        str_contains($pathLow, 'casting') ||
                                        str_contains($nameLow, 'pfc') ||
                                        str_contains($nameLow, 'f_alm_pfc') ||
                                        str_contains($nameLow, 'efc') ||
                                        str_contains($nameLow, 'f_alm_efc')
                                    );
                                    if (!$isCastingDoc) {
                                        return false;
                                    }
                                    if (empty($aprobadosNorm)) {
                                        return true;
                                    }
                                    foreach ($aprobadosNorm as $ap) {
                                        if ($ap !== '' && strpos($nameLow, $ap) !== false) {
                                            return true;
                                        }
                                    }
                                    return true;
                                }));

                                $tieneAprobados = count($aprobados ?? []) > 0 || count($calidadAprobadosLdmCasting ?? []) > 0 || count($dibujosCasting ?? []) > 0 || count($ayudasCasting ?? []) > 0 || count($almacenPreordenesCasting ?? []) > 0;
                                $tieneRechazados = count($rechazados ?? []) > 0 || count($rechazadosOtros ?? []) > 0 || count($rechazadosDibujos ?? []) > 0 || count($rechazadosAyudas ?? []) > 0 || count($dibujosRechazadosOrig ?? []) > 0 || count($ayudasRechazadosOrig ?? []) > 0;

                                $countVisibleFabricacion = $tieneFabricacion ? (count($dibujosModelo ?? []) + count($ayudasModelo ?? []) + count($almacenPreordenesFab ?? [])) : 0;
                                $countVisibleAprobados = $tieneAprobados ? (count($dibujosCasting ?? []) + count($ayudasCasting ?? []) + count($calidadAprobadosLdmCasting ?? []) + count($almacenPreordenesCasting ?? [])) : 0;
                                $countVisibleRechazados = $tieneRechazados ? (count($dibujosRechazadosOrig ?? []) + count($ayudasRechazadosOrig ?? []) + count($rechazadosDibujos ?? []) + count($rechazadosAyudas ?? []) + count($rechazadosOtros ?? [])) : 0;

                                $count = $countVisibleFabricacion + $countVisibleAprobados + $countVisibleRechazados;

                                $hasRechazosRealLocal = (count($rechazados) > 0 || $tieneRechazados);
                                $esReproceso = (bool) preg_match('/_R\d+$/i', $targetReg->ot);
                                $showControlCard = ($estado === 'activa' && !$isFinalized && (!$isCalidadAlerted || $hasRechazosRealLocal || ($esReproceso && count($clasesFabricacion) > 0)));
                                $hasFilesOrControl = ($count > 0 || $showControlCard);

                                // DEBUG MARKER
                                echo "<!-- DEBUG OT: {$reg->ot}, estado: {$estado}, isFinalized: " . ($isFinalized ? 'true' : 'false') . ", isCalidadAlerted: " . ($isCalidadAlerted ? 'true' : 'false') . ", showControlCard: " . ($showControlCard ? 'true' : 'false') . " -->";

                                $estadoConfig = \App\Services\FundicionStateService::resolverEstadoOT($reg, $targetReg, $aprobados, $esReproceso);
                                
                                $fsmState = $estadoConfig['fsmState'];
                                $icon = $estadoConfig['icon'];
                                $label = $estadoConfig['label'];
                                $tooltip = $estadoConfig['tooltip'];
                                $borderColor = $estadoConfig['borderColor'];
                                $bgColor = $estadoConfig['bgColor'];
                                $textColor = $estadoConfig['textColor'];
                            @endphp
                            @php
                                $pendingChanges = is_string($reg->pending_almacen_changes) ? json_decode($reg->pending_almacen_changes, true) : ($reg->pending_almacen_changes ?? []);
                                $hasPendingChanges = !empty($pendingChanges);
                            @endphp

                            {{-- Fila principal --}}
                            <tr data-ot="{{ $reg->ot }}" data-estado-real="{{ $fsmState }}"
                                data-is-fully-processed="{{ $targetReg->isAlmacenFullyProcessed() ? 'true' : 'false' }}">
                                <td>
                                    <div class="alm-ot-label">{{ preg_replace('/_\d{8}_\d{6}_.*/', '', $reg->ot) }}
                                    </div>
                                    @if ($reg->status === 'inactiva')
                                        <div class="alm-inactiva-note">
                                            La carpeta fue eliminada por el administrador. Los PDFs de {{ $deptName }} se
                                            conservan.
                                        </div>
                                    @endif
                                </td>
                                <td class="d-text-center">
                                    <span class="badge-status badge-{{ $reg->status }}">
                                        {{ $reg->status }}
                                    </span>
                                </td>
                                <td class="d-text-center">
                                    <div id="status-modelo-{{ $reg->ot }}">
                                        <div
                                            class="status-modelo-container alm-display-inline-flex alm-flex-direction-column alm-align-items-center alm-gap-2px alm-padding-6px alm-border-radius-8px">
                                            <span class="badge-modelo-icon" title="{{ $tooltip }}"
                                                style="display: flex; align-items: center; justify-content: center; width: 52px; height: 52px; border-radius: 50%; background: {{ $bgColor }}; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); border: 2px solid {{ $borderColor }}; transition: all 0.2s ease;">
                                                <img src="{{ asset('images/' . $icon) }}" alt="{{ $label }}"
                                                    class="alm-width-34px alm-height-34px alm-object-fit-contain">
                                            </span>
                                            <span class="status-modelo-label"
                                                style="font-size: 11px; font-weight: 700; color: {{ $textColor }}; margin-top: 4px; text-transform: uppercase; white-space: nowrap;">
                                                {{ $label }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="alm-date d-text-center">
                                    {{ $reg->alert_sent_at ? $reg->alert_sent_at->format('d/m/Y H:i') : '—' }}
                                </td>
                                <td class="d-text-center">
                                    <span class="badge-pdf-count">{{ $count }}</span>
                                </td>
                                <td class="d-text-center">
                                    @if($hasPendingChanges)
                                        <button class="btn-toggle-files"
                                            style="background: linear-gradient(135deg, #f97316, #ea580c); color: white; border: 1px solid #c2410c;"
                                            onclick="almacenRevisarCambios('{{ $reg->ot }}')">
                                            Revisar Cambios
                                        </button>
                                    @elseif ($hasFilesOrControl)
                                        <button class="btn-toggle-files" data-target="files-{{ $estado }}-{{ $loop->index }}"
                                            data-ot="{{ $reg->ot }}" id="toggle-btn-{{ $estado }}-{{ $loop->index }}"
                                            aria-expanded="false">
                                            Ver Archivos
                                        </button>
                                    @else
                                        <span class="d-text-subtle alm-font-size-0-85em">Sin archivos</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Fila desplegable de archivos --}}
                            @if ($hasFilesOrControl)
                                <tr class="alm-files-row" id="files-{{ $estado }}-{{ $loop->index }}">
                                    <td colspan="6">
                                        {{-- CONTENEDOR PRINCIPAL PROCESOS (CONTENEDOR 0) --}}
                                        <div class="alm-contenedor-principal-procesos"
                                            style="display: flex; flex-direction: column; gap: 25px; width: 100%; margin-top: 15px;">

                                            {{-- CONTENEDOR 1: FABRICACIÓN / RE-PROCESO DE MODELO --}}
                                            @include('almacen.partials.containers.fabrication_container')

                                            {{-- CONTENEDOR 2: PROCESO DE CASTING / MODELOS APROBADOS --}}
                                            @include('almacen.partials.containers.casting_container')

                                            {{-- CONTENEDOR 3: MODELOS RECHAZADOS --}}
                                            @include('almacen.partials.containers.rejected_container')
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endforeach