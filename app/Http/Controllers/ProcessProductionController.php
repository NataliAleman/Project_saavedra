<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHeaderProcessRequest;
use App\Models\Clase;
use App\Models\Maquinas;
use App\Models\Metas;
use App\Models\Moldura;
use App\Models\Orden_trabajo;
use App\Models\Pieza;
use App\Models\Procesos;
use App\Models\tiempoproduccion;
use App\Models\User;
use App\Models\SystemLog;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProcessProductionController extends Controller
{
    /** @var \App\Http\Controllers\ClassController */
    protected $classController;
    /** @var \App\Http\Controllers\ProcessesController */
    protected $processesController;
    /** @var \App\Http\Controllers\CepilladoController */
    protected $cepilladoController;

    public function __construct()
    {
        $this->middleware('auth');
        $this->classController = new ClassController();
        $this->processesController = new ProcessesController();
    }

    /**
     * Resuelve el problema de max_input_vars truncando datos en formularios grandes.
     * Si el front-end envía 'piece_data_json', se decodifica y se fusiona en el request.
     */
    protected function mergeJsonIntoRequest(Request $request)
    {
        if ($request->has('piece_data_json')) {
            $jsonData = json_decode($request->input('piece_data_json'), true);
            if (is_array($jsonData)) {
                $request->merge($jsonData);
            }
        }
    }
        /**
     * @param mixed $returnArray
     */
    public function show($returnArray = null)
    {
        // ── OPTIMIZACIÓN: eager loading de moldura evita N+1 ──
        $wOrdersFounded = Orden_trabajo::query()->with('moldura')->get();
        $workOrders = array();
        if (count($wOrdersFounded) > 0) {
            foreach ($wOrdersFounded as $workOrder) {
                $classes = $this->classController->getClasses($workOrder);
                if (count($classes) > 0) {
                    foreach ($classes as $key => $class) {
                        if ($class->finalizada == 0) {
                            if (!array_key_exists($workOrder->id, $workOrders)) {
                                $workOrders[$workOrder->id] = array();
                                // Moldura ya cargada (0 queries)
                                $workOrders[$workOrder->id]['moldura'] = $workOrder->moldura ? $workOrder->moldura->nombre : 'Moldura no encontrada';
                            }
                            $processes = Procesos::query()->where('id_clase', $class->id)->first();
                            if ($processes) {
                                $workOrders[$workOrder->id][$class->nombre] = array();
                                $workOrders[$workOrder->id][$class->nombre] = $this->setOrderedProcess($class);
                            }
                        }
                    }
                }
            }
        }
        $workOrders = count($workOrders) > 0 ? $workOrders : null;
        if ($returnArray != null) {
            return $workOrders; // Si se solicita un array, se retorna el array de órdenes de trabajo
        }
        return view('processes_views.processProduction_view', compact('workOrders'));
    }

        /**
     * @param mixed $class
     */
    public function setOrderedProcess($class)
    {
        //Establecer el orden de los procesos
        $baseType = $class->getBaseType();
        switch ($baseType) {
            case "Bombillo":
            case "Molde":
                $processesInOrder = ["cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoBombillo", "acabadoMolde", "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado"];
                break;
            case "Obturador":
                $processesInOrder = ["operacionEquipo", "soldadura", "soldaduraPTA"];
                break;
            case "Fondo":
                $processesInOrder = ["operacionEquipo", "soldadura", "soldaduraPTA"];
                break;
            case "Corona":
                $processesInOrder = ["cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado"];
                break;
            case "Plato":
                $processesInOrder = ["barreno_maniobra", "operacionEquipo"];
                break;
            case "Embudo":
                $processesInOrder = ["operacionEquipo", "embudoCM"];
                break;
            case "Cabeza de Soplo":
                $processesInOrder = ["primeraOperacionCabezaSoplo", "segundaOperacionCabezaSoplo"];
                break;
            case "Candado Obturador":
                $processesInOrder = ["operacionEquipo"];
                break;
            default:
                $processesInOrder = [];
                break;
        }


        //Verificar los procesos por los que pasa la clase
        $processesNotEmpty = Procesos::query()->where("id_clase", $class->id)->first();
        if ($processesNotEmpty) {
            foreach ($processesInOrder as $key => $proc) {
                if (isset($processesNotEmpty->$proc) && $processesNotEmpty->$proc == 0) {
                    unset($processesInOrder[$key]);
                }
            }
        }
        // Reindexar el array para mantener los índices consecutivos
        $processesInOrder = array_values($processesInOrder);

        // Fallback: if all processes are 0 in DB (legacy data), show all expected processes
        if (empty($processesInOrder)) {
            switch ($baseType) {
                case "Bombillo":
                case "Molde":
                    $processesInOrder = ["cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoBombillo", "acabadoMolde"];
                    break;
                case "Corona":
                    $processesInOrder = ["cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado"];
                    break;
                case "Plato":
                    $processesInOrder = ["barreno_maniobra", "operacionEquipo"];
                    break;
                case "Embudo":
                    $processesInOrder = ["operacionEquipo", "embudoCM"];
                    break;
                case "Cabeza de Soplo":
                    $processesInOrder = ["primeraOperacionCabezaSoplo", "segundaOperacionCabezaSoplo"];
                    break;
                default:
                    $processesInOrder = [];
                    break;
            }
        }

        // Convertir los procesos a su formato de nombre completo
        foreach ($processesInOrder as $key => $proc) {
            $processesInOrder[$key] = $this->processesController->convertProcessToString($proc);
        }

        return $processesInOrder;
    }

        /**
     * @param mixed $meta
     * @param mixed $process
     * @param mixed $edit
     */
    public function showReportFormat($meta, $process, $edit)
    {
        $this->updateMeta($meta);
        $workOrders = $this->show(true);

        $meta = Metas::query()->find($meta);

        $machine = Maquinas::query()->where('id_meta', $meta->id)->first();
        if (!$machine) {
            return redirect()->route('processProduction')->with('error', 'La máquina ha sido liberada. Por favor, crea una nueva meta para continuar registrando piezas.');
        }

        $class = Clase::query()->find($meta->id_clase);
        $edit = $edit != 0 ? $edit : false;

        $arrayData = $this->prepareReportData($meta, $class, $edit);
        $piecesData = $this->get_ArrayPieces($meta->proceso, $class, $meta);
        $pieceToBeUsed = $this->get_pieceToBeUsed($meta->proceso, $piecesData['availableAssemblies'], $meta, $class);

        return view('processes_views.processProduction_view', compact('arrayData', 'workOrders', 'pieceToBeUsed'));
    }

    /**
     * Prepare all data needed for the report view
     *
     * @param Metas $meta
     * @param Clase $class
     * @param bool|int $edit
     * @return array
     */
    private function prepareReportData($meta, $class, $edit)
    {
        $workOrder = Orden_trabajo::query()->find($meta->id_ot);
        $molding = Moldura::query()->find($workOrder->id_moldura);

        $process = $this->getSub_Process($meta->proceso, 0);
        $subprocess = $this->getSub_Process($meta->proceso, 1);
        $piecesData = $this->get_ArrayPieces($meta->proceso, $class, $meta);

        // Para Soldadura PTA pre-renderizar la tabla de rowspans en el servidor
        $ptaTableHtml = null;
        if ($process === 'Soldadura PTA') {
            // Historial: piezas ya completadas (estado=2) — modo reporte
            $rawHistoryPieces = collect($piecesData['machinedPiecesInMeta'] ?? [])
                ->map(fn($item) => $item['piece'])
                ->sortBy(fn($p) => [(int) filter_var($p->n_pieza, FILTER_SANITIZE_NUMBER_INT), $p->n_pieza]);

            $piezasGroupHistory = $rawHistoryPieces->groupBy('n_pieza');

            // Construir mapa de liberación por n_pieza (para colorear filas históricas)
            $ptaLiberacion = [];
            foreach ($piezasGroupHistory->keys() as $nPieza) {
                $piezaDB = Pieza::query()->where('n_pieza', $nPieza)
                    ->where('proceso', 'Soldadura PTA')
                    ->where('id_clase', $class->id)
                    ->first();
                $ptaLiberacion[$nPieza] = $piezaDB ? (int) $piezaDB->liberacion : null;
            }

            // Piezas activas (estado=1, meta actual) — modo captura
            $processIdString = str_replace(' ', '_', 'Soldadura PTA') . '_' . $class->nombre . '_' . $class->id_ot;
            $ptaProcessDB = \App\Models\SoldaduraPTA::query()->where('id_proceso', $processIdString)->first();

            // Obtener solo el primer nombre de pieza que tenga estado 1 para forzar flujo 1 a 1
            $firstActivePieceName = $ptaProcessDB
                ? \App\Models\SoldaduraPTA_pza::query()->where('id_proceso', $ptaProcessDB->id)
                    ->where('id_meta', $meta->id)
                    ->where('estado', 1)
                    ->orderBy('n_pieza')
                    ->value('n_pieza')
                : null;

            $piezasGroupActivas = ($ptaProcessDB && $firstActivePieceName)
                ? \App\Models\SoldaduraPTA_pza::query()->where('id_proceso', $ptaProcessDB->id)
                    ->where('id_meta', $meta->id)
                    ->where('estado', 1)
                    ->where('n_pieza', $firstActivePieceName)
                    ->orderByRaw("FIELD(tipo_medida, 'D_Conexion_pico', 'D_Conexion_obt', 'Perfilado')")
                    ->get()
                    ->groupBy('n_pieza')
                : collect();

            $ptaTableHtml = view('processes_views.soldaduraPTA_table_partial', [
                'piezasGroup' => $piezasGroupHistory,
                'piezasGroupActivas' => $piezasGroupActivas,
                'modo' => ($edit == 2) ? 'captura' : 'reporte',
                'ptaLiberacion' => $ptaLiberacion,
                'esJuegoCompleto' => in_array(strtoupper($class->nombre), ['OBTURADOR', 'FONDO']),
                'claseNombre' => $class->nombre,
            ])->render();
        }

        return [
            'operator' => $this->getOperatorName(),
            'workOrder' => $workOrder->id . ' - ' . $molding->nombre,
            'class'     => $class->nombre,
            'process' => $process,
            'subprocess' => $subprocess,
            'startTime' => $meta->h_inicio,
            'endTime' => $meta->h_termino,
            'machine' => $meta->maquina,
            'date' => $meta->fecha,
            'edit' => $edit,
            'meta' => $meta,
            'numberPieces' => $this->verifyNumbersOfPieces($meta),
            'consignmentPieces' => $piecesData['consignmentPieces'],
            'remainingPieces' => $piecesData['remainingPieces'],
            'machinedPiecesInMeta' => $piecesData['machinedPiecesInMeta'],
            'availableAssemblies' => $piecesData['availableAssemblies'],
            'cNominals' => $this->saveCNominals($class, $process, $subprocess),
            'history' => $this->getProcessHistory($class),
            'ptaTableHtml' => $ptaTableHtml,
            'tipo_soldadura' => $class->tipo_soldadura,
        ];
    }


    /**
     * Get formatted operator name
     */
    private function getOperatorName()
    {
        $user = auth()->user();
        return $user->matricula . ' - ' . $user->a_paterno . ' ' . $user->a_materno . ' ' . $user->nombre;
    }
        /**
     * @param mixed $processName
     * @param mixed $availableAssemblies
     * @param mixed $meta
     * @param mixed $class
     */
    public function get_pieceToBeUsed($processName, $availableAssemblies, $meta, $class)
    {
        // Obtener el proceso
        $processString = str_contains($processName, "Operacion Equipo") ? $this->getSub_Process($processName, 0) : $processName;
        $modelProcess = $this->get_ModelProcess($processString, $class);
        $id_process = str_replace(" ", "_", $processName) . "_" . $class->nombre . "_" . $class->id_ot;
        $process = $modelProcess::query()->where('id_proceso', $id_process)->first();
        // Obtener el modelo de las piezas del proceso
        $modelPieces = $this->get_ModelProcessPieces($processString, $class);
        if ($process) { // Si no existe el proceso, no se puede obtener una pieza
            //Verificar si hay piezas vacias asociadas a la meta del usuario
            $pieceMeta = $modelPieces::query()->where("id_meta", $meta->id)->whereNot('estado', 2)->get();
            if (count($pieceMeta) > 0) { // Si hay una pieza vacía asociada a la meta, se puede usar
                foreach ($pieceMeta as $pMeta) {
                    if ($this->verifiedRejectedPiece($pMeta, $class, $processName)) {
                        $pMeta->estado = 1;
                        $pMeta->save();
                        return $pMeta;
                    }
                }
            }
            //Verificar si hay alguna pieza que este en la misma maquina en la que se esta trabajando
            $unoccupiedPiece = $modelPieces::query()->where("id_proceso", $process->id)->where('estado', 0)->get();
            if (count($unoccupiedPiece) > 0) {
                //Verificar que la pieza aun no este rechazada
                foreach ($unoccupiedPiece as $uPiece) {
                    if ($this->verifiedRejectedPiece($uPiece, $class, $processName)) {
                        $metaPiece = $uPiece->id_meta;
                        $metaPiece = Metas::query()->find($metaPiece);
                        if ($metaPiece->maquina == $meta->maquina) {
                            // Marcar la pieza como ocupada
                            $uPiece->estado = 1;
                            $uPiece->id_meta = $meta->id;
                            $uPiece->save();
                            return $uPiece;
                        }
                    }
                }
            }

            //Si no hay piezas vacias asociadas a la meta, se crea una nueva pieza solamente si el proceso es "Cepillado"
            $isCandadoOpeEquipo = $processString === "Operacion Equipo" && $class && $class->nombre === "Candado Obturador";
            if ($processString == "Cepillado" || $isCandadoOpeEquipo) {
                if (count($availableAssemblies) > 0) {
                    $assembly = $availableAssemblies[0];
                    $noAssembly = substr($assembly, 0, -1); // Extraer el numero de juego
                    for ($i = 1; $i <= 2; $i++) {
                        $pieceLetter = $i > 1 ? "H" : "M"; // Asociar la letra de la mitad de la pieza

                        //Verificar que no exista la pieza que se quiere crear
                        $existingPiece = $modelPieces::query()->where("id_proceso", $process->id)
                            ->where("n_pieza", $noAssembly . $pieceLetter)
                            ->first();

                        //Creación de piezas
                        $newPiece = null;
                        if (!$existingPiece) {
                            $newPiece = new $modelPieces();
                            $newPiece->id_pza = $noAssembly . $pieceLetter . $process->id;
                            $newPiece->id_meta = $meta->id;
                            $newPiece->id_proceso = $process->id;
                            $newPiece->estado = 1;
                            $newPiece->n_pieza = $noAssembly . $pieceLetter;
                            $newPiece->n_juego = $assembly;
                            try {
                                $newPiece->save();
                            } catch (\Illuminate\Database\QueryException $ex) {
                                if ($ex->errorInfo[1] == 1062) {
                                    return back()->with('error', 'Este juego ya fue asignado.');
                                }
                                throw $ex;
                            }
                        }
                        if ($i == 1) {
                            $pieceToBeUsed = !$existingPiece ? $newPiece : $existingPiece;
                        }
                    }
                    return $pieceToBeUsed;
                } else {
                    return null;
                }
            } else {
                //Verificar que haya piezas registradas en el proceso anterior
                $previousProcess = $this->convertProcessToString($this->get_previousProcess($class, $processName));
                $previousProcess = $previousProcess == "operacionEquipo" ? "Operacion Equipo_2 operacion" : $previousProcess;

                // Modificación para el Gateway de Copiado (Requiere Barreno Profundidad y Cavidades)
                if ($processString == "Copiado") {
                    $requiredProcesses = ["Barreno Profundidad", "Cavidades"];
                    foreach ($requiredProcesses as $reqProc) {
                        $modelReqPieces = $this->get_ModelProcessPieces($reqProc, $class);
                        $reqProcessId = str_replace(" ", "_", $reqProc) . "_" . $class->nombre . "_" . $class->id_ot;
                        $reqProcessDB = $this->get_ModelProcess($reqProc, $class)::query()->where('id_proceso', $reqProcessId)->first();

                        if ($reqProcessDB) {
                            $finishedPieces = $modelReqPieces::query()->where('id_proceso', $reqProcessDB->id)->where('estado', 2)->exists();
                            if (!$finishedPieces) {
                                return "NoPreviousPieces";
                            }
                        } else {
                            return "NoPreviousPieces";
                        }
                    }
                    return null; // Si ambos tienen piezas, permitimos continuar (el usuario seleccionará pieza manualmente)
                }

                if ($previousProcess) {
                    $specialProcess = ["Soldadura", "Soldadura PTA", "Desbaste Exterior", "Revision Laterales"];
                    if (in_array($previousProcess, $specialProcess)) {
                        $specialProcesses = $previousProcess == "Soldadura" || $previousProcess == "Soldadura PTA" ? ["Soldadura", "Soldadura PTA"] : ["Desbaste Exterior", "Revision Laterales"];
                        $hasPieces = false;
                        foreach ($specialProcesses as $specProcess) {
                            $modelPreviousProcessPieces = $this->get_ModelProcessPieces($specProcess, $class);
                            $previousProcessId = str_replace(" ", "_", $specProcess) . "_" . $class->nombre . "_" . $class->id_ot;
                            $previousProcessDB = $this->get_ModelProcess($specProcess, $class)::query()->where('id_proceso', $previousProcessId)->first();
                            if ($previousProcessDB) {
                                $previousPieces = $modelPreviousProcessPieces::query()->where('id_proceso', $previousProcessDB->id)->where('estado', 2)->get();
                                if ($previousPieces->isNotEmpty()) {
                                    $hasPieces = true;
                                    break;
                                }
                            }
                        }
                        if (!$hasPieces) {
                            return "NoPreviousPieces";
                        }
                    } else {
                        $preProcessString = str_contains($previousProcess, "Operacion Equipo") ? $this->getSub_Process($previousProcess, 0) : $previousProcess;
                        $modelPreviousProcessPieces = $this->get_ModelProcessPieces($preProcessString, $class);
                        $previousProcessId = str_replace(" ", "_", $previousProcess) . "_" . $class->nombre . "_" . $class->id_ot;
                        $previousProcessDB = $this->get_ModelProcess($preProcessString, $class)::query()->where('id_proceso', $previousProcessId)->first();
                        if ($previousProcessDB) {
                            $previousPieces = $modelPreviousProcessPieces::query()->where('id_proceso', $previousProcessDB->id)->where('estado', 2)->get();
                            if (!$previousPieces->isNotEmpty()) {
                                return "NoPreviousPieces";
                            }
                        } else {
                            return "NoPreviousPieces";
                        }
                    }
                }
                return null;
            }
        } else {
            return "NoPreviousPieces";
        }
    }
        /**
     * @param mixed $piece
     * @param mixed $class
     * @param mixed $processName
     */
    public function verifiedRejectedPiece($piece, $class, $processName)
    {
        //Verificar que la pieza aun no este rechazada
        $n_juego = $piece->n_pieza ? $piece->n_pieza : $piece->n_juego;
        $n_juego = substr($n_juego, 0, -1);

        $unoccupiedPieceV = Pieza::query()->where('id_clase', $class->id)
            ->where('proceso', $processName)
            ->whereRaw("n_pieza REGEXP '^{$n_juego}[A-Z]$'")
            ->first();

        if ($unoccupiedPieceV) {
            if ($unoccupiedPieceV->liberacion == 0) {
                return true;
            }
        } else {
            return true;
        }
        return false;
    }
        /**
     * @param mixed $class
     * @param mixed $process
     * @param mixed $subprocess
     */
    public function saveCNominals($class, $process, $subprocess)
    {
        if ($process == "Copiado") {
            $data = array();
            $data["Cilindrado"] = $this->processesController->searchCNominals($class, $process, "Cilindrado");
            $data["Cavidades"] = $this->processesController->searchCNominals($class, $process, "Cavidades");
            return $data;
        } else {
            return $this->processesController->searchCNominals($class, $process, $subprocess);
        }
    }

        /**
     * @param mixed $process
     * @param mixed $param
     */
    public function getSub_Process($process, $param)
    {
        $subprocess = explode('_', $process);
        return isset($subprocess[$param]) ? $subprocess[$param] : null;
    }

        /**
     * @param mixed $passwordEntered
     */
    public function validatePasswordAdmin($passwordEntered)
    {
        if ($passwordEntered) {
            // ── OPTIMIZACIÓN: solo cargar admins y calidad, no User::all() ──
            $users = User::query()->whereIn('perfil', [1, 4, 5], 'and', false)->get();
            foreach ($users as $user) {
                if (Hash::check($passwordEntered, $user->contrasena)) {
                    return true;
                }
            }
        }
        return false;
    }
        /**
     * @param Request $request
     */
    public function verifiedPasswordAdmin(Request $request)
    {
        $password = $request->input('passwordAdmin');
        if ($this->validatePasswordAdmin($password)) {
            $meta = Metas::query()->find($request->input('meta'));
            $process = $meta->proceso;

            // REGLA DE ORO: REGISTRAR AUTORIZACIÓN (OPCIÓN 2)
            if ($request->has('h_inicio_solicitud')) {
                SystemLog::create([
                    'user_matricula' => auth()->user()->matricula,
                    'action' => 'Autorización de Edición',
                    'details' => 'El supervisor/administrador autorizó el acceso a edición tras validar su identidad.',
                    'ot' => $meta->id_ot,
                    'clase' => $meta->id_clase,
                    'proceso' => $process,
                    'maquina' => $meta->maquina,
                    'h_inicio' => $request->input('h_inicio_solicitud'),
                    'h_termino' => now()->format('H:i:s'),
                ]);
            }

            if (!$request->input('editPieces')) {
                if ($meta) {
                    return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $process, "edit" => 1])->with('success', 'Contraseña correcta. Ahora puedes editar tu meta');
                }
            }
            return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $process, "edit" => 2])->with('success', 'Contraseña correcta. Ahora puedes editar las piezas que has registrado');
        }
        return redirect()->back()->with('error', 'Contraseña incorrecta, intenta de nuevo');
    }
        /**
     * @param mixed $meta
     */
    public function verifyNumbersOfPieces($meta)
    {
        $class = Clase::query()->find($meta->id_clase);
        $processString = str_contains($meta->proceso, "Operacion Equipo") ? $this->getSub_Process($meta->proceso, 0) : $meta->proceso;
        $model = $this->get_ModelProcessPieces($processString, $class);

        if ($processString === 'Soldadura PTA') {
            $class = Clase::query()->find($meta->id_clase);
            $esJuegoCompleto = $class ? in_array(strtoupper($class->nombre), ['OBTURADOR', 'FONDO']) : false;
            // Dividimos entre 2 porque las metas y procesos paralelos originales evalúan PTA por Juego, no por mitad.
            // 2 mitades (M y H) = 1 juego. Para Juego Completo el divisor es 1.
            $divisor = $esJuegoCompleto ? 1 : 2;
            return $model::query()->where('id_meta', $meta->id)->distinct('n_pieza')->count('n_pieza') / $divisor;
        }

        $piecesCount = $model::query()->where('id_meta', $meta->id)->count();
        return $piecesCount;
    }
        /**
     * @param Request $request
     */
    public function storePiece(Request $request)
    {
        $this->mergeJsonIntoRequest($request);
        $meta = Metas::query()->find($request->input('meta'));
        $machine = Maquinas::query()->where('id_meta', $meta->id)->first();
        if ($machine) {
            $user = auth()->user();
            if ($user && $user->perfil == 2) {
                // REINICIAR CRONÓMETRO: Se registra actividad de producción (con 00 segs)
                $user->update(['prod_start_at' => now()->setSeconds(0), 'prod_locked_type' => null]);
            }

            $class = Clase::query()->find($meta->id_clase);
            $this->savePiece($class, $meta->proceso, $request, $meta);

            //Retornar pieza siguiente
            return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with('success', 'Pieza registrada correctamente.');
        } else {
            return redirect()->route('processProduction')->with('error', 'La máquina ha sido liberada. Por favor, crea una nueva meta para continuar registrando piezas.');
        }
    }
        /**
     * @param mixed $class
     * @param mixed $processName
     * @param mixed $request
     * @param mixed $meta
     * @param int $index
     * @param mixed $arrayPieces
     */
    public function savePiece($class, $processName, $request, $meta, $index = null, $arrayPieces = null)
    {
        $processString = str_contains($processName, "Operacion Equipo") ? $this->getSub_Process($processName, 0) : $processName;
        // Obtener los datos de CNominal y Tolerancia del proceso
        if ($processString != "Soldadura" && $processString != "Asentado" && $processString != "Rectificado" && $processString != "Soldadura PTA") {
            $id_process = str_replace(" ", "_", $processName) . "_" . $class->nombre . "_" . $class->id_ot;
            [$cNominalModel, $toleranceModel] = $this->getModelProcessCNominal_Tolerance($processString, $class);
            $cNominal = $cNominalModel::query()->where("id_proceso", $id_process)->first();
            $tolerance = $toleranceModel::query()->where("id_proceso", $id_process)->first();

            //Guardar los datos de la pieza en su respectiva tabla del proceso
            $controllerProcess = $this->get_ControllerProcess($processString, $class);
            if ($processName == "Copiado") {
                $controllerProcess->storePiece($request, $cNominal, $tolerance, $index !== null ? $index : null, $arrayPieces);
            } else {
                $controllerProcess->storePiece($request, $cNominal, $tolerance, $index !== null ? $index : null);
            }
        } else {
            //Guardar los datos de la pieza en su respectiva tabla del proceso
            $controllerProcess = $this->get_ControllerProcess($processString, $class);
            $controllerProcess->storePiece($request, $index !== null ? $index : null);
        }

        //Guardar la pieza en la tabla Piezas
        $modelProcessPiece = $this->get_ModelProcessPieces($processString, $class);

        // ── Hotfix para Soldadura PTA (arrays de pieza iterativos) ──
        if ($request->input('process') == "Soldadura PTA") {
            $pieceIds = $request->input('piece_id') ?? [];
            if (empty($pieceIds)) {
                return;
            }

            $piecesToProcess = [];
            $piecesToProcess = [];
            foreach ($pieceIds as $key => $pid) {
                if (!$pid)
                    continue;
                $pieceRow = $modelProcessPiece::query()->find($pid);
                if (!$pieceRow)
                    continue;

                $n_piece = $pieceRow->n_pieza ? $pieceRow->n_pieza : $pieceRow->n_juego;
                if (!isset($piecesToProcess[$n_piece])) {
                    $piecesToProcess[$n_piece] = [
                        'hasFundicion' => false,
                        'hasMal' => false,
                        'obs' => [],
                        'p2_obs' => []
                    ];
                }

                $defecto = $request->input('defecto_pta')[$key] ?? 'Ninguno';
                $resultado = $request->input('resultado')[$key] ?? 'Bien';

                if ($defecto === 'Fundición' || $defecto === 'Fundicion')
                    $piecesToProcess[$n_piece]['hasFundicion'] = true;
                if ($resultado === 'Mal')
                    $piecesToProcess[$n_piece]['hasMal'] = true;

                // Colectar observaciones de esta sub-fila
                if (!empty($pieceRow->observaciones) && $pieceRow->observaciones !== '—') {
                    $piecesToProcess[$n_piece]['obs'][] = $pieceRow->observaciones;
                }
                if (!empty($pieceRow->p2_observaciones) && $pieceRow->p2_observaciones !== '—') {
                    $piecesToProcess[$n_piece]['p2_obs'][] = $pieceRow->p2_observaciones;
                }
            }

            foreach ($piecesToProcess as $n_piece => $data) {
                $pieceInPiezas = Pieza::query()->where("id_clase", $class->id)
                    ->where("proceso", "Soldadura PTA")
                    ->where("n_pieza", $n_piece)
                    ->first();

                if (!$pieceInPiezas) {
                    $pieceInPiezas = new Pieza();
                    $pieceInPiezas->id_ot = $class->id_ot;
                    $pieceInPiezas->id_clase = $class->id;
                    $pieceInPiezas->n_pieza = $n_piece;
                    $pieceInPiezas->id_operador = $meta->id_usuario;
                    $pieceInPiezas->maquina = $meta->maquina;
                    $pieceInPiezas->proceso = "Soldadura PTA";
                }

                $error = $data['hasFundicion'] ? "Fundicion" : ($data['hasMal'] ? "Maquinado" : "Ninguno");
                $pieceInPiezas->error = $error;

                // Combinar observaciones de sub-filas y pasadas
                $allObs = array_unique(array_merge($data['obs'], $data['p2_obs']));
                $pieceInPiezas->observacion_operador = empty($allObs) ? '—' : implode(' | ', $allObs);

                $pieceInPiezas->save();
            }
            return; // Termina la función ya que PTA fue procesado completamente
        } else {
            $pieceId = $index !== null ? $request->input('piece')[$index] : $request->input('piece');
            $piece = $modelProcessPiece::query()->find($pieceId);
        }

        if (!$piece)
            return;

        $n_piece = $piece->n_pieza ? $piece->n_pieza : $piece->n_juego;
        $pieceInPiezas = Pieza::query()->where("id_clase", $class->id)->where("proceso", $request->input('process'))->where("n_pieza", $n_piece)->first();
        if (!$pieceInPiezas) {
            $pieceInPiezas = new Pieza();
            $pieceInPiezas->id_ot = $class->id_ot;
            $pieceInPiezas->id_clase = $class->id;
            $pieceInPiezas->n_pieza = $n_piece;
            $pieceInPiezas->id_operador = $meta->id_usuario;
            $pieceInPiezas->maquina = $meta->maquina;
            $pieceInPiezas->proceso = $request->input('process');
        }

        if ($request->input('process') == "Copiado") {
            $hasMaquinado = $piece->error_cilindrado === "Maquinado" || $piece->error_cavidades === "Maquinado";
            $hasFundicion = $piece->error_cilindrado === "Fundicion" || $piece->error_cavidades === "Fundicion";

            if ($hasFundicion) {
                $error = "Fundicion";
            } elseif ($hasMaquinado) {
                $error = "Maquinado";
            } else {
                $error = "Ninguno";
            }
        } else {
            $error = $piece->error;
        }
        $pieceInPiezas->error = $error ?? 'Ninguno';
        // Copiar observaciones del operador si existen
        if (isset($piece->observaciones)) {
            $pieceInPiezas->observacion_operador = $piece->observaciones;
        }
        $pieceInPiezas->save();
    }
        /**
     * @param Request $request
     */
    public function selectAssembly(Request $request)
    {
        $this->mergeJsonIntoRequest($request);
        // Obtener las variables principales
        $meta = Metas::query()->find($request->input('meta'));
        $machine = Maquinas::query()->where('id_meta', $meta->id)->first();
        if (!$machine) {
            return redirect()->route('processProduction')->with('error', 'La máquina ha sido liberada. Por favor, crea una nueva meta para continuar registrando piezas.');
        }

        $user = auth()->user();
        if ($user && $user->perfil == 2) {
            // REINICIAR CRONÓMETRO: Selección de juego cuenta como inicio de actividad (con 00 segs)
            $user->update(['prod_start_at' => now()->setSeconds(0), 'prod_locked_type' => null]);
        }

        $class = Clase::query()->find($meta->id_clase);

        // Obtener los modelos de la tabla de las piezas del proceso
        $processString = str_contains($request->input('process'), "Operacion Equipo") ? $this->getSub_Process($request->input('process'), 0) : $request->input('process');
        $processIdString = str_replace(" ", "_", $request->input('process')) . "_" . $class->nombre . "_" . $class->id_ot;
        $modelProcess = $this->get_ModelProcess($processString, $class);
        $process = $modelProcess::query()->where("id_proceso", $processIdString)->first();
        $modelPieces = $this->get_ModelProcessPieces($processString, $class);

        $param = null;
        $message = null;

        // Crear las piezas la tabla de piezas del proceso
        if ($request->input('selectedAssembly')) {
            $processAssembly = ["Barreno Maniobra", "Soldadura", "Rectificado", "Asentado", "Barreno Profundidad", "Palomas", "Rebajes", "Grabado", "Embudo CM"];
            // Operacion Equipo is set-based ONLY when class is NOT Candado Obturador
            if (!($processString === "Operacion Equipo" && $class->nombre === "Candado Obturador")) {
                $processAssembly[] = "Operacion Equipo";
            }
            $noAssembly = substr($request->input('selectedAssembly'), 0, -1); // Extraer el numero de juego
            if (in_array($processString, $processAssembly)) {
                //Verificar que no exista la pieza que se quiere crear
                $existingPiece = $modelPieces::query()->where("id_proceso", $process->id)
                    ->where("n_juego", $request->input('selectedAssembly'))
                    ->first();

                if (!$existingPiece || ($existingPiece && $existingPiece->meta == $meta->id)) {
                    if ($processString == "Soldadura" || $processString == "Soldadura PTA") {
                        $reverseProcess = $processString == "Soldadura" ? "Soldadura PTA" : "Soldadura";
                        $processReverseIdString = str_replace(" ", "_", $reverseProcess) . "_" . $class->nombre . "_" . $class->id_ot;
                        $modelProcessReverse = $this->get_ModelProcess($reverseProcess, $class);
                        $reverseProcessDB = $modelProcessReverse::query()->where("id_proceso", $processReverseIdString)->first();
                        $modelReversePieces = $this->get_ModelProcessPieces($reverseProcess, $class);

                        $existingPiece = $modelReversePieces::query()->where("id_proceso", $reverseProcessDB->id)
                            ->where("n_juego", $request->input('selectedAssembly'))
                            ->first();
                        if ($existingPiece && $existingPiece->meta != $meta->id) {
                            $param = "error";
                            $message = 'El juego ' . $noAssembly . ' ya está en uso en ' . $reverseProcess . '. Por favor, elija otro juego.';
                            return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with($param, $message);
                        }
                    }
                    // Obtener el proceso anterior
                    $previousProcess = $this->convertProcessToString($this->get_previousProcess($class, $request->input('process')));
                    if ($previousProcess == "Desbaste Exterior" || $previousProcess == "Revision Laterales") {
                        [$availableAssemblies, $remainingPieces, $totalGood] = $this->getRemainingPieces_LateralesOrDesbaste($processString, $previousProcess, $class);
                    } else if ($previousProcess == "Soldadura PTA" || $previousProcess == "Soldadura") {
                        [$availableAssemblies, $remainingPieces, $totalGood] = $this->getRemainingPieces_Soldaduras($processString, $class);
                    } else {
                        $previousProcess = $previousProcess == "operacionEquipo" ? "Operacion Equipo_2 operacion" : $previousProcess;
                        [$availableAssemblies, $remainingPieces, $totalGood] = $this->get_RemainingPieces($processString, $previousProcess, $class);
                    }

                    if (!in_array($request->input('selectedAssembly'), $availableAssemblies)) {
                        $param = "error";
                        $message = 'El juego ' . $noAssembly . ' no está disponible. Por favor, elija otro juego de la lista.';
                        return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with($param, $message);
                    }

                    //Creación de piezas
                    $newPiece = new $modelPieces();
                    $newPiece->id_pza = $request->input('selectedAssembly') . $process->id;
                    $newPiece->id_meta = $meta->id;
                    $newPiece->id_proceso = $process->id;
                    $newPiece->estado = 1;
                    $newPiece->n_juego = $request->input('selectedAssembly');
                    try {
                        $newPiece->save();
                    } catch (\Illuminate\Database\QueryException $ex) {
                        if ($ex->errorInfo[1] == 1062) {
                            return back()->with('error', 'Este juego ya fue asignado.');
                        }
                        throw $ex;
                    }

                    $param = "success";
                    $message = 'Juego ' . $noAssembly . ' seleccionado correctamente';
                } else {
                    $param = "error";
                    $message = 'El juego ' . $noAssembly . ' ya está en uso. Por favor, elija otro juego.';
                }
            } else {
                $esJuegoCompleto = false;
                if ($processString === 'Soldadura PTA') {
                    $esJuegoCompleto = in_array(strtoupper($class->nombre), ['OBTURADOR', 'FONDO']);
                }
                $maxMitades = $esJuegoCompleto ? 1 : 2;

                for ($i = 1; $i <= $maxMitades; $i++) {
                    $pieceLetter = $esJuegoCompleto ? "J" : ($i > 1 ? "H" : "M"); // Asociar la letra de la mitad de la pieza

                    //Verificar que no exista la pieza que se quiere crear en el proceso actual
                    $existingPiece = $modelPieces::query()->where("id_proceso", $process->id)
                        ->where("n_pieza", $noAssembly . $pieceLetter)
                        ->first();

                    if (!$existingPiece) {
                        // Verificar si el proceso actual es Revision Laterales o Desbaste Exterior
                        if ($processString == "Revision Laterales" || $processString == "Desbaste Exterior") {
                            // Verificar si el juego ya esta maquinado o ocupado en el proceso intermedio
                            $intermediateProcess = $processString == "Revision Laterales" ? "Desbaste Exterior" : "Revision Laterales";
                            $intermediateProcessId = str_replace(" ", "_", $intermediateProcess) . "_" . $class->nombre . "_" . $class->id_ot;
                            $intermediateProcessDB = $this->get_ModelProcess($intermediateProcess, $class)::query()->where('id_proceso', $intermediateProcessId)->first();
                            if ($intermediateProcessDB) {
                                $assembly = $this->get_ModelProcessPieces($intermediateProcess, $class)::query()->where('n_juego', $request->input('selectedAssembly'))->where('id_proceso', $intermediateProcessDB->id)->get();
                                if ($assembly->isNotEmpty()) {
                                    $status = 0;
                                    $correct = 0;
                                    $error = false;
                                    foreach ($assembly as $piece) {
                                        if ($piece->estado == 2) { // Si la pieza esta registrada y maquinada en el proceso intermedio
                                            $releasedPiece = $this->verifyPiece(Pieza::query()->where('n_pieza', $piece->n_pieza)->where('proceso', $intermediateProcess)->where('id_clase', $class->id)->first());
                                            if ($releasedPiece) {
                                                $status += 1;
                                                $correct += 1;
                                            } else {
                                                $error = true;
                                            }
                                        } else if ($piece->estado == 0) {
                                            // Si la pieza esta registrada pero no ocupada ni maquinada en el proceso intermedio
                                            $status += 1;
                                        }
                                    }
                                    // Verificar que esten bien las dos o que ninugna este ocupada
                                    if (!($status > 1 && ($correct == 0 || $correct > 1))) {
                                        $param = "error";
                                        if ($error) {
                                            $message = 'El juego ' . $noAssembly . ' esta incorrecto en el proceso ' . $intermediateProcess . '. Por favor, elija otro juego.';
                                        } else {
                                            $message = 'El juego ' . $noAssembly . ' ya está en uso en el proceso ' . $intermediateProcess . '. Por favor, elija otro juego.';
                                        }
                                        return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with($param, $message);
                                    }
                                }
                            }
                        }
                        // ── Creación de piezas ──
                        if ($processString === 'Soldadura PTA') {
                            // Defaults de pre-llenado para Soldadura PTA (Molde y Bombillo)
                            $claseNormPTA = strtolower(trim($class->nombre ?? ''));
                            $ptaDefaults = in_array($claseNormPTA, ['molde', 'bombillo', 'obturador', 'fondo', 'plato']) ? [
                                'D_Conexion_pico' => [
                                    'vl' => 2.000, 'sold_inicial' => 3.000, 'sold_aplicada' => 8.000,
                                    'sold_final' => 6.000, 'corr_inicial' => 45.000, 'corr_aplicada' => 60.000,
                                    'corr_final' => 60.000, 'gas_argon' => 2.200, 'precalentamiento' => 415,
                                    'd_conexion_pico' => 0,
                                ],
                                'D_Conexion_obt' => [
                                    'vl' => 2.000, 'sold_inicial' => 3.000, 'sold_aplicada' => 8.000,
                                    'sold_final' => 6.000, 'corr_inicial' => 45.000, 'corr_aplicada' => 60.000,
                                    'corr_final' => 70.000, 'gas_argon' => 2.200,
                                    'd_conexion_obt' => 0,
                                ],
                                'Perfilado' => [
                                    'vl' => "0.000", 'sold_inicial' => "0.000", 'sold_aplicada' => "0.000",
                                    'sold_final' => "0.000", 'corr_inicial' => "0.000", 'corr_aplicada' => "0.000",
                                    'corr_final' => "0.000", 'gas_argon' => "0.000", 'velocidad_calculada' => "0.000",
                                    'perfilado' => "0.000",
                                ],
                            ] : [];

                            // PTA: 3 sub-filas por mitad (D_Conexion_pico, D_Conexion_obt, Perfilado)
                            foreach (\App\Http\Controllers\SoldaduraPTAController::TIPOS_MEDIDA as $tidx => $tipo) {
                                $newPiece = new $modelPieces();
                                $newPiece->id_pza = $noAssembly . $pieceLetter . '_' . $tidx . '_' . $process->id;
                                $newPiece->id_meta = $meta->id;
                                $newPiece->id_proceso = $process->id;
                                $newPiece->estado = 1;
                                $newPiece->n_pieza = $noAssembly . $pieceLetter;
                                $newPiece->n_juego = $request->input('selectedAssembly');
                                $newPiece->tipo_medida = $tipo;

                                // Aplicar defaults de pre-llenado
                                if (!empty($ptaDefaults[$tipo])) {
                                    foreach ($ptaDefaults[$tipo] as $campo => $valor) {
                                        $newPiece->$campo = $valor;
                                    }
                                }

                                try {
                                    $newPiece->save();
                                } catch (\Illuminate\Database\QueryException $ex) {
                                    if ($ex->errorInfo[1] == 1062) {
                                        return back()->with('error', 'Este juego ya fue asignado.');
                                    }
                                    throw $ex;
                                }
                            }
                        } else {
                            // Proceso normal: 1 registro por mitad
                            $newPiece = new $modelPieces();
                            $newPiece->id_pza = $noAssembly . $pieceLetter . $process->id;
                            $newPiece->id_meta = $meta->id;
                            $newPiece->id_proceso = $process->id;
                            $newPiece->estado = 1;
                            $newPiece->n_pieza = $noAssembly . $pieceLetter;
                            $newPiece->n_juego = $request->input('selectedAssembly');
                            try {
                                $newPiece->save();
                            } catch (\Illuminate\Database\QueryException $ex) {
                                if ($ex->errorInfo[1] == 1062) {
                                    return back()->with('error', 'Este juego ya fue asignado.');
                                }
                                throw $ex;
                            }
                        }

                        $param = "success";
                        $message = 'Juego ' . $noAssembly . ' seleccionado correctamente';
                    } else {
                        $param = "error";
                        $message = 'El juego ' . $noAssembly . ' ya está en uso. Por favor, elija otro juego.';
                    }
                }
            }
        } else {
            $param = "error";
            $message = 'No fue posible seleccionar el juego. Intenta de nuevo.';
        }
        return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with($param, $message);
    }
        /**
     * @param Request $request
     */
    public function editPieces(Request $request)
    {
        $this->mergeJsonIntoRequest($request);
        $meta = Metas::query()->find($request->input('meta'));
        $machine = Maquinas::query()->where('id_meta', $meta->id)->first();
        if (!$machine) {
            return redirect()->route('processProduction')->with('error', 'La máquina ha sido liberada. Por favor, crea una nueva meta para continuar registrando piezas.');
        }

        $user = auth()->user();
        if ($user && $user->perfil == 2) {
            // REINICIAR CRONÓMETRO: La edición manual también cuenta como actividad activa (con 00 segs)
            $user->update(['prod_start_at' => now()->setSeconds(0), 'prod_locked_type' => null]);
        }

        $class = Clase::query()->find($meta->id_clase);

        if ($meta->proceso === 'Soldadura PTA') {
            $ptaController = new \App\Http\Controllers\SoldaduraPTAController();
            $ptaController->storePiece($request);

            // Sincronizar los errores de PTA en la tabla general de Piezas
            $this->savePiece($class, $meta->proceso, $request, $meta);
        } else {
            $arrayPieces = $request->input('piece') ? array_unique($request->input('piece')) : [];
            foreach ($arrayPieces as $index => $piece) {
                if ($meta->proceso == "Copiado") {
                    $this->savePiece($class, $meta->proceso, $request, $meta, $index, $arrayPieces);
                } else {
                    $this->savePiece($class, $meta->proceso, $request, $meta, $index);
                }
            }
        }
        //Retornar pieza siguiente
        return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with('success', 'Piezas editadas correctamente.');
    }
        /**
     * @param Request $request
     */
    public function editMeta(Request $request)
    {
        // Verificar que la clase ingresada exista
        $workOrder = strtok($request->input('workOrder'), ' ');
        $class = Clase::query()->where('id_ot', $workOrder)->where('nombre', $request->input('class'))->first(); //Obtener el id de la clase
        if ($class) {
            $foundedMeta = Metas::query()->find($request->input('meta'));
            if ($foundedMeta) {
                //Cambiar el formato de las horas ingresadas 00:00 a 00:00:00
                $startTime = DateTime::createFromFormat('H:i', $request->input('startTime'));
                $startTime = $startTime->format('H:i:s');
                $endTime = DateTime::createFromFormat('H:i', $request->input('endTime'));
                $endTime = $endTime->format('H:i:s');
                $date = Carbon::createFromFormat('Y-m-d', $request->input('date'))->format('Y-m-d');

                //Verificar si ya hay piezas registradas de esa meta
                if ($this->verifyNumbersOfPieces($foundedMeta) == 0) {
                    // Verificar si la maquina no esta siendo ocupada
                    $machineOccupied = Maquinas::query()->where('maquina', $request->input('machine'))->where('proceso', $request->input('process'))->first();
                    if (!$machineOccupied || $machineOccupied->id_meta === $foundedMeta->id) {
                        // Si la máquina ocupada es la misma que habia creado, se elimina
                        if ($machineOccupied) {
                            $machineOccupied->delete();
                        } else {
                            $oldMachine = Maquinas::query()->where('maquina', $foundedMeta->maquina)->where('proceso', $foundedMeta->proceso)->first();
                            if ($oldMachine) {
                                $oldMachine->delete(); // Eliminar la máquina ocupada anterior
                            }
                        }

                        //Verificar si existe una meta creada con los datos ingresados
                        $existingMeta = Metas::query()->where('id_ot', $workOrder)
                            ->where('id_clase', $class->id)
                            ->where('fecha', $date)
                            ->where('h_inicio', $startTime)
                            ->where('h_termino', $endTime)
                            ->where('maquina', $request->input('machine'))
                            ->where('proceso', $request->input('process'))
                            ->where('id_usuario', auth()->user()->matricula)
                            ->first();


                        if ($existingMeta && $existingMeta->id != $foundedMeta->id) { // Si existe la meta y es diferente a la anterior
                            // Si existe, borrar la meta
                            $foundedMeta->delete();
                            $this->storeMachine($request, $existingMeta); // Si la máquina no existe, se crea una nueva máquina ocupada asociada a la meta
                            $successMessage = 'Se ha ingresado correctamente a la meta de ' . auth()->user()->a_paterno . ' ' . auth()->user()->a_materno . ' ' . auth()->user()->nombre;
                            $meta = $existingMeta;
                        } else {
                            //Si no existe se edita la meta que habia ingresado
                            $this->storeMeta($request, $class, $startTime, $endTime, $date, $foundedMeta);
                            $this->storeMachine($request, $foundedMeta); // Se crea una nueva máquina ocupada asociada a la meta
                            $successMessage = 'Tu meta se ha editado correctamente';
                            $meta = $foundedMeta;
                        }
                        // Se retorna al reporte con el mensaje de exito
                        return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $request->input('process'), "edit" => 0])->with('success', $successMessage);
                    }
                    return redirect()->route('showReportFormat', ["meta" => $foundedMeta, "process" => $foundedMeta->proceso, "edit" => 0])->with('error', 'La máquina esta ocupada. Por favor, elija otra maquina o pida a un supervisor desbloquearla'); // Si la mquina esta ocupada retornar error con la meta antes creada
                } else {
                    $foundedMeta->fecha = $date;
                    $foundedMeta->h_inicio = $startTime;
                    $foundedMeta->h_termino = $endTime;
                    $this->calculateMeta($foundedMeta, $startTime, $endTime, $class, $foundedMeta->maquina);
                    $foundedMeta->save();
                    return redirect()->route('showReportFormat', ["meta" => $foundedMeta, "process" => $request->input('process'), "edit" => 0])->with('success', 'Tu meta se ha editado correctamente');
                }
            }
            return redirect()->route('processProduction')->with('error', 'La meta a editar no se ha encontrado.'); // Si la meta a editar no existe, retornar error
        }
        return redirect()->route('processProduction')->with('error', 'La clase ingresada no existe.'); // Si la clase no existe, retornar error
    }
        /**
     * @param StoreHeaderProcessRequest $request
     */
    public function storeHeaderdata(StoreHeaderProcessRequest $request)
    {
        $validatedData = $request->validated(); //Validación de los datos ingresados.
        // Verificar que la clase ingresada exista
        $class = Clase::query()->where('id_ot', $request->input('workOrder'))->where('nombre', $request->input('class'))->first();
        if ($class) {
            // Verificar si la maquina no esta siendo ocupada
            $machineOccupied = Maquinas::query()->where('maquina', $request->input('machine'))->where('proceso', $request->input('process'))->first();
            if (!$machineOccupied) {
                //Cambiar el formato de las horas ingresadas 00:00 a 00:00:00
                $startTime = DateTime::createFromFormat('H:i', $request->input('startTime'));
                $startTime = $startTime->format('H:i:s');
                $endTime = DateTime::createFromFormat('H:i', $request->input('endTime'));
                $endTime = $endTime->format('H:i:s');
                $date = Carbon::createFromFormat('Y-m-d', $request->input('date'))->format('Y-m-d');

                echo $processString = $request->input('subprocess') ? $request->input('process') . '_' . $request->input('subprocess') : $request->input('process');
                $foundedMeta = Metas::query()->where('id_ot', $request->input('workOrder'))
                    ->where('id_clase', $class->id)
                    ->where('fecha', $date)
                    ->where('h_inicio', $startTime)
                    ->where('h_termino', $endTime)
                    ->where('maquina', $request->input('machine'))
                    ->where('proceso', $processString)
                    ->where('id_usuario', auth()->user()->matricula)
                    ->first();
                if ($foundedMeta) { // Si la máquina no existe, pero ya existe una meta con los mismos datos
                    $this->storeMachine($request, $foundedMeta); // Si la máquina no existe, se crea una nueva máquina ocupada asociada a la meta
                    $meta = $foundedMeta;
                    $successMessage = 'Se ha ingresado correctamente a la meta de ' . auth()->user()->a_paterno . ' ' . auth()->user()->a_materno . ' ' . auth()->user()->nombre;

                    SystemLog::create([
                        'user_matricula' => auth()->user()->matricula,
                        'action' => 'Ingreso a Meta Existente',
                        'details' => $successMessage . " (OT: {$request->workOrder}, Clase: {$class->nombre}, Maquina: {$request->machine})",
                        'ot' => $request->workOrder,
                        'clase' => $class->nombre,
                        'maquina' => $request->machine,
                        'proceso' => $processString,
                        'h_inicio' => now()->format('H:i:s'),
                        'h_termino' => now()->format('H:i:s'),
                        'id_ot' => $request->workOrder
                    ]);
                } else { // Si la máquina no existe y tampoco una meta con esos datos, se crea una nueva meta y maquina
                    $meta = $this->storeMeta($request, $class, $startTime, $endTime, $date);
                    $meta = Metas::query()->find($meta->id);
                    $this->storeMachine($request, $meta); // Se crea una nueva máquina ocupada asociada a la meta
                    $successMessage = 'Se ha creado correctamente la meta';

                    SystemLog::create([
                        'user_matricula' => auth()->user()->matricula,
                        'action' => 'Nueva Meta Creada',
                        'details' => $successMessage . " (OT: {$request->workOrder}, Clase: {$class->nombre}, Maquina: {$request->machine})",
                        'ot' => $request->workOrder,
                        'clase' => $class->nombre,
                        'maquina' => $request->machine,
                        'proceso' => $processString,
                        'h_inicio' => now()->format('H:i:s'),
                        'h_termino' => now()->format('H:i:s'),
                        'id_ot' => $request->workOrder
                    ]);
                }
                return redirect()->route('showReportFormat', ["meta" => $meta, "process" => $processString, "edit" => 0])->with('success', $successMessage);
            }
            return redirect()->route('processProduction')->with('warning', 'La máquina esta ocupada. Por favor, elija otra maquina o pida a un supervisor desbloquearla');
        }
        return redirect()->route('processProduction')->with('error', 'La clase ingresada no existe.'); // Si la clase no existe, retornar error
    }

        /**
     * @param mixed $request
     * @param mixed $class
     * @param mixed $startTime
     * @param mixed $endTime
     * @param mixed $date
     * @param mixed $meta
     */
    public function storeMeta($request, $class, $startTime, $endTime, $date, $meta = null)
    {
        // Si no se encontró la meta, se puede crear una nueva
        if (!$meta) {
            $meta = new Metas();
        }
        $meta->id_ot = strtok($request->input('workOrder'), ' ');
        $meta->id_usuario = auth()->user()->matricula;
        $meta->fecha = $date;
        $meta->h_inicio = $startTime;
        $meta->h_termino = $endTime;
        $meta->maquina = $request->input('machine');
        $meta->id_clase = $class->id;
        $meta->proceso = $request->input('subprocess') ? $request->input('process') . '_' . $request->input('subprocess') : $request->input('process');
        $this->calculateMeta($meta, $startTime, $endTime, $class, $request->input('machine'));
        $meta->save();
        return $meta;
    }

        /**
     * @param mixed $request
     * @param mixed $newMeta
     * @param mixed $machineOccupied
     */
    public function storeMachine($request, $newMeta, $machineOccupied = null)
    {
        // Crear una nueva máquina ocupada asociada a la meta
        if (!$machineOccupied) {
            $machineOccupied = new Maquinas();
            $machineOccupied->maquina = $request->input('machine');
            $machineOccupied->proceso = $request->input('subprocess') ? $request->input('process') . '_' . $request->input('subprocess') : $request->input('process');
        }
        $machineOccupied->id_meta = $newMeta->id;
        $machineOccupied->save();
    }

        /**
     * @param mixed $meta
     */
    public function finishReport($meta)
    {
        $meta = Metas::query()->find($meta);
        if ($meta) {
            $user = auth()->user();
            if ($user && $user->perfil == 2) {
                // Registrar finalización en log técnico de productividad
                \Illuminate\Support\Facades\Log::channel('productivity')->info("[FINALIZACIÓN] El operador {$user->matricula} ha finalizado su meta de producción (OT: {$meta->id_ot}). El sistema entra en fase de espera.");

                // Reiniciar estado de productividad al finalizar el reporte para evitar bloqueos fantasmales
                $user->update([
                    'prod_status' => 'inicio',
                    'prod_start_at' => now(),
                    'prod_locked_type' => null
                ]);
            }

            $class = Clase::query()->find($meta->id_clase);
            // Desocupar la maquina
            $machineOccupied = Maquinas::query()->where('id_meta', $meta->id)->first();
            if ($machineOccupied) {
                $machineOccupied->delete();
            }
            // Desocupar piezas en la meta si es que estaban ocupadas
            $processString = str_contains($meta->proceso, "Operacion Equipo") ? $this->getSub_Process($meta->proceso, 0) : $meta->proceso;
            $modelProcessPieces = $this->get_ModelProcessPieces($processString, $class);
            $occupiedPieces = $modelProcessPieces::query()->where('id_meta', $meta->id)->where('estado', 1)->get();
            if (count($occupiedPieces) > 0) {
                $isAssemblyOpeEquipo = $processString === "Operacion Equipo" && $class && $class->nombre !== "Candado Obturador";
                $processesAssemblies = ["Barreno Maniobra", "Soldadura", "Soldadura PTA", "Rectificado", "Asentado", "Barreno Profundidad", "Palomas", "Rebajes", "Grabado", "Embudo CM"];
                if ($isAssemblyOpeEquipo) {
                    $processesAssemblies[] = "Operacion Equipo";
                }
                if (in_array($processString, $processesAssemblies)) {
                    foreach ($occupiedPieces as $piece) { // Si es un juego marcar como desocupado
                        $piece->delete();
                    }
                } else {
                    if (count($occupiedPieces) < 2) { // Si es una mitad marcar como desocupada
                        foreach ($occupiedPieces as $piece) {
                            $piece->estado = 0;
                            $piece->save();
                        }
                    } else {
                        foreach ($occupiedPieces as $piece) { // Si son dos mitades eliminarlas
                            $piece->delete();
                        }
                    }
                }
            }
            return redirect()->route('home');
        }
        return redirect()->route('home')->with('error', 'Meta no encontrada.');
    }

        /**
     * @param mixed $process
     * @param mixed $class
     * @param mixed $meta
     */
    public function get_ArrayPieces($process, $class, $meta)
    {
        $arrayData = array();
        // Obtener pedido con piezas de consignacion
        $consignmentPieces = Clase::query()->find($class->id)->piezas;

        //Obtener las piezas maquinadas en la meta
        $machinedPiecesInMeta = $this->get_machinedPiecesInMeta($meta);

        //Calcular piezas restantes por maquinar y devolver las que estan disponibles
        $previousProcess = $this->convertProcessToString($this->get_previousProcess($class, $process));

        if ($previousProcess == "Desbaste Exterior" || $previousProcess == "Revision Laterales") {
            [$availableAssemblies, $remainingPieces, $totalGood] = $this->getRemainingPieces_LateralesOrDesbaste($process, $previousProcess, $class);
        } else if ($previousProcess == "Soldadura PTA" || $previousProcess == "Soldadura") {
            [$availableAssemblies, $remainingPieces, $totalGood] = $this->getRemainingPieces_Soldaduras($process, $class);
        } else if ($process == "Copiado") {
            [$availableAssemblies, $remainingPieces, $totalGood] = $this->getRemainingPieces_BarrenoCavidades($process, $class);
        } else {
            $previousProcess = $previousProcess == "operacionEquipo" ? "Operacion Equipo_2 operacion" : $previousProcess;
            [$availableAssemblies, $remainingPieces, $totalGood] = $this->get_RemainingPieces($process, $previousProcess, $class);
        }

        if ($process == "Soldadura" || $process == "Soldadura PTA") {
            $reverseProcess = $process == "Soldadura" ? "Soldadura PTA" : "Soldadura";
            $this->get_FilteredPiecesSoldadura_SoldaduraPTA($reverseProcess, $class, $availableAssemblies, $remainingPieces);
        }

        // Asignar los valores en el array
        $arrayData = [
            'consignmentPieces' => $consignmentPieces,
            'machinedPiecesInMeta' => $machinedPiecesInMeta,
            'availableAssemblies' => $availableAssemblies,
            'remainingPieces' => $remainingPieces,
        ];
        return $arrayData;
    }
        /**
     * @param mixed $process
     * @param mixed $class
     */
    public function getRemainingPieces_BarrenoCavidades($process, $class)
    {
        $remainingPieces = 0;
        $totalGood = 0;
        $availableAssemblies = array();

        // Obtener las piezas maquinadas de Barreno Profundidad y Cavidades
        $processes = ["Barreno Profundidad", "Cavidades"];
        $goodPieces = [];

        foreach ($processes as $procName) {
            $id_process = str_replace(' ', '_', $procName) . "_" . $class->nombre . "_" . $class->id_ot;
            $modelProcess = $this->get_ModelProcess($procName, $class);
            $processDB = $modelProcess::query()->where('id_proceso', $id_process)->first();

            if ($processDB) {
                // Obtener piezas maquinadas (estado 2)
                $modelPiecesProcess = $this->get_ModelProcessPieces($procName, $class);
                $pieces = $modelPiecesProcess::query()->where('id_proceso', $processDB->id)->where('estado', 2)->get();

                foreach ($pieces as $piece) {
                    $pieceName = $piece->n_pieza ?: $piece->n_juego; // Puede ser por pieza o juego
                    // Verificar si está correcta/liberada
                    $fullPiece = Pieza::query()->where('n_pieza', $pieceName)
                        ->where('proceso', $procName)
                        ->where('id_clase', $class->id)
                        ->first();

                    if ($this->verifyPiece($fullPiece)) {
                        // Almacenar como buena para este proceso
                        $goodPieces[$procName][] = $pieceName;
                    }
                }
            }
        }

        // Intersección: Piezas que están buenas en AMBOS procesos
        $commonPieces = [];
        if (isset($goodPieces["Barreno Profundidad"]) && isset($goodPieces["Cavidades"])) {
            $commonPieces = array_intersect($goodPieces["Barreno Profundidad"], $goodPieces["Cavidades"]);
        }

        $totalGood = count($commonPieces);

        // Ahora filtrar las que ya están ocupadas o terminadas en Copiado
        [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($process, $class); // Copiado

        foreach ($commonPieces as $pieceName) {
            // Unicamente pasan las que no han sido maquinadas. Las ocupadas aún cuentan como restantes.
            if (!in_array($pieceName, $machinedPieces)) {
                $remainingPieces++;
                if (!in_array($pieceName, $occupiedAssemblies)) {
                    $availableAssemblies[] = $pieceName;
                }
            }
        }

        return [$availableAssemblies, $remainingPieces, $totalGood];
    }
        /**
     * @param mixed $process
     * @param mixed $class
     */
    public function getRemainingPieces_Soldaduras($process, $class)
    {
        //Obtener los juegos buenos maquinados en Soldadura y Soldadura PTA
        $processes = ["Soldadura", "Soldadura PTA"];
        $goodAvailableAssemblies = [];
        $halvesTracker = []; // Array auxiliar para llevar el registro de mitades de Soldadura PTA

        foreach ($processes as $processArray) {
            [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($processArray, $class);

            // Para Soldadura PTA eliminamos duplicados porque tiene 3 filas por mitad
            if ($processArray == "Soldadura PTA") {
                $machinedPieces = array_unique($machinedPieces);
            }

            foreach ($machinedPieces as $piece) {
                // Verificar si está aprobada en su respectiva tabla
                $liberatedPiece = $this->verifyPiece(Pieza::query()->where('n_pieza', $piece)->where('proceso', $processArray)->where('id_clase', $class->id)->first());
                if ($liberatedPiece) {
                    $pieceLetter = substr($piece, -1);
                    $noAssembly = substr($piece, 0, -1);

                    if ($pieceLetter == 'J') {
                        // Es de Soldadura directa, se guarda como juego entero
                        if (!in_array($piece, $goodAvailableAssemblies)) {
                            $goodAvailableAssemblies[] = $piece;
                        }
                    } else if ($pieceLetter == 'M' || $pieceLetter == 'H') {
                        // Es de Soldadura PTA, se rige por mitades
                        $halvesTracker[$noAssembly][] = $pieceLetter;

                        // Si ya tiene ambas mitades válidas, conformamos el juego
                        if (count(array_unique($halvesTracker[$noAssembly])) == 2) {
                            $juegoFormado = $noAssembly . "J";
                            if (!in_array($juegoFormado, $goodAvailableAssemblies)) {
                                $goodAvailableAssemblies[] = $juegoFormado;
                            }
                        }
                    }
                }
            }
        }

        $totalGood = count($goodAvailableAssemblies);
        $availableAssemblies = [];
        $remainingPieces = 0;
        //Filtrar los juegos que pasaron y los que se encuentran registrados en el proceso actual
        [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($process, $class); //Obtener las piezas maquinadas en el proceso actual
        foreach ($goodAvailableAssemblies as $assembly) {
            if (!in_array($assembly, $occupiedAssemblies)) {
                $remainingPieces++;
                $availableAssemblies[] = $assembly;
            } else { // Si el juego ya esta ocupado en el proceso actual pero no ha sido maquinado
                $processAssembly = ["Barreno Maniobra", "Soldadura", "Rectificado", "Asentado", "Barreno Profundidad", "Palomas", "Rebajes", "Grabado", "Operacion Equipo", "Embudo CM"];
                if (in_array($process, $processAssembly)) {
                    if (!in_array($assembly, $machinedPieces)) {
                        $remainingPieces += 1;
                    }
                } else {
                    for ($i = 1; $i <= 2; $i++) {
                        $halfPiece = substr($assembly, 0, -1) . ($i == 1 ? "H" : "M");
                        if (!in_array($halfPiece, $machinedPieces)) {
                            $remainingPieces += 0.5;
                        }
                    }
                }
            }
        }
        return [$availableAssemblies, $remainingPieces, $totalGood];
    }

        /**
     * @param mixed $reverseProcess
     * @param mixed $class
     * @param mixed &$availableAssemblies
     * @param mixed &$remainingPieces
     */
    public function get_FilteredPiecesSoldadura_SoldaduraPTA($reverseProcess, $class, &$availableAssemblies, &$remainingPieces)
    {
        // Filtrar los juegos que aun no han sido maquinados en el proceso inverso
        [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($reverseProcess, $class);
        foreach ($occupiedAssemblies as $assembly) {
            if (in_array($assembly, $availableAssemblies)) {
                $key = array_search($assembly, $availableAssemblies);
                if ($key !== false) {
                    unset($availableAssemblies[$key]);
                    // Reindexar el array para evitar huecos en las claves
                    $availableAssemblies = array_values($availableAssemblies);
                    $modelProcess = $this->get_ModelProcess($reverseProcess, $class);
                    $id_processId = str_replace(" ", "_", $reverseProcess) . "_" . $class->nombre . "_" . $class->id_ot;
                    $processDB = $modelProcess::query()->where('id_proceso', $id_processId)->first();
                    $modelProcessPieces = $this->get_ModelProcessPieces($reverseProcess, $class);
                    $assembly = $modelProcessPieces::query()->where('id_proceso', $processDB->id)->where('n_juego', $assembly)->first();
                    if ($assembly && $assembly->estado == 2) {
                        $remainingPieces -= 1;
                    }
                }
            }
        }
    }
        /**
     * @param mixed $metaId
     */
    public function updateMeta($metaId)
    {
        $meta = Metas::query()->find($metaId);
        $class = Clase::query()->find($meta->id_clase);
        $process = $meta->proceso;

        // Obtener cadena de proceso y subproceso (Si existe)
        $process = $this->getSub_Process($meta->proceso, 0);
        $subprocess = $this->getSub_Process($meta->proceso, 1);
        //Actualizar las piezas de la meta (correctas o incorrectas) en base a las Cotas nominales y Tolerancias
        $this->updatePieces($process, $subprocess, $meta, $class);

        //Obtener array de las piezas maquinadas en la meta
        $machinedPiecesInMeta = $this->get_machinedPiecesInMeta($meta);

        //Calcular la meta
        $this->calculateMeta($meta, $meta->h_inicio, $meta->h_termino, $class, $meta->maquina);
        $meta->resultado = $this->calculate_metaResult($machinedPiecesInMeta, $class, $process);
        $meta->save();
    }
    // //Se actualiza las piezas de cada proceso para verificar que este correcta
        /**
     * @param mixed $process
     * @param mixed $subprocess
     * @param mixed $meta
     * @param mixed $class
     */
    public function updatePieces($process, $subprocess, $meta, $class)
    {
        $processString = str_replace(" ", "_", $process); // Reemplazar espacios por guiones bajos
        $subprocessString = $subprocess ? str_replace(" ", "_", $subprocess) : null; // Reemplazar espacios por guiones bajos
        $completeString = $subprocessString ? $processString . '_' . $subprocessString : $processString;

        $processId = $completeString . '_' . $class->nombre . '_' . $meta->id_ot; // Obtener la cadena "id" del proceso

        //Obtener registro del proceso
        $modelProcess = $this->get_ModelProcess($process, $class);
        $processDB = $modelProcess::query()->where('id_proceso', $processId)->first();
        if ($processDB && ($process != "Soldadura PTA" && $process != "Soldadura" && $process != "Asentado" && $process != "Rectificado")) { // Si ya hay piezas creadas de  esa clase y proceso
            //Obtener las piezas registradas del proceso
            $modelProcessPieces = $this->get_ModelProcessPieces($process, $class);
            $piecesInMeta = $modelProcessPieces::query()->where('id_proceso', $processDB->id)->where('estado', 2)->where('id_meta', $meta->id)->get();

            if ($piecesInMeta->count() > 0) { // Si hay piezas registradas
                //Actualizar las piezas del proceso
                $controllerProcess = $this->get_ControllerProcess($process, $class); // Obtener el controlador del proceso

                [$cNominalModel, $toleranceModel] = $this->getModelProcessCNominal_Tolerance($process, $class); // Obtener los modelos de las Cotas nominales y Tolerancias del proceso
                $cNominal = $cNominalModel::query()->where('id_proceso', $processId)->first();
                $tolerance = $toleranceModel::query()->where('id_proceso', $processId)->first();

                //Verificar si las medidas de la pieza estan correctas
                foreach ($piecesInMeta as $piece) {
                    if ($meta->proceso == "Copiado") {
                        $correctSubprocess = $controllerProcess->comparePieceData($piece, $cNominal, $tolerance);
                        foreach ($correctSubprocess as $key => $value) {
                            if ($value == 0 && ($piece->$key == "Ninguno" || $piece->$key == "Maquinado")) {
                                $piece->$key = 'Maquinado';
                                $piece->correcto = 0;
                            } else if (($value == 0 && $piece->$key == 'Fundicion') || ($value == 1 && $piece->$key == 'Fundicion')) {
                                $piece->$key = $piece->$key;
                                $piece->correcto = 0;
                            } else {
                                $piece->$key = 'Ninguno';
                                $piece->correcto = 1;
                            }
                        }
                    } else {
                        $correct = $controllerProcess->comparePieceData($piece, $cNominal, $tolerance);
                        if ($correct == 0 && ($piece->error == "Ninguno" || $piece->error == "Maquinado")) {
                            $piece->error = 'Maquinado';
                            $piece->correcto = 0;
                        } else if (($correct == 0 && $piece->error == 'Fundicion') || ($correct == 1 && $piece->error == 'Fundicion')) {
                            $piece->correcto = 0;
                        } else {
                            $piece->error = 'Ninguno';
                            $piece->correcto = 1;
                        }
                    }
                    $piece->save(); // Actualizar la pieza en su tabla

                    //Actualizar la pieza en la tabla de Piezas (En donde se almacenan todas las piezas)
                    $n_piece = $piece->n_pieza ? $piece->n_pieza : $piece->n_juego; // Obtener el nombre de la pieza o del juego
                    $pieceDB = Pieza::query()->where('n_pieza', $n_piece)->where('id_ot', $meta->id_ot)->where('id_clase', $class->id)->where('proceso', $meta->proceso)->first();
                    if (!isset($pieceDB)) {
                        $pieceDB = new Pieza();
                        $pieceDB->id_ot = $class->id_ot;
                        $pieceDB->id_clase = $class->id;
                        $pieceDB->n_pieza = $n_piece;
                        $pieceDB->id_operador = $meta->id_usuario;
                        $pieceDB->maquina = $meta->maquina;
                        $pieceDB->proceso = $meta->proceso;
                    }
                    if ($meta->proceso == "Copiado") {
                        $hasMaquinado = $piece->error_cilindrado === "Maquinado" || $piece->error_cavidades === "Maquinado";
                        $hasFundicion = $piece->error_cilindrado === "Fundicion" || $piece->error_cavidades === "Fundicion";

                        if ($hasFundicion) {
                            $error = "Fundicion";
                        } elseif ($hasMaquinado) {
                            $error = "Maquinado";
                        } else {
                            $error = "Ninguno";
                        }
                    } else {
                        $error = $piece->error;
                    }
                    $pieceDB->error = $error;
                    $pieceDB->save();
                }
            }
        }
    }
        /**
     * @param mixed $process
     * @param mixed $class
     */
    public function get_ControllerProcess($process, $class = null)
    {
        if ($class && $class->nombre == 'Cabeza de Soplo') {
            return match ($process) {
                'Primera Operacion Cabeza Soplo' => new PrimeraOperacionCabezaSoploController(),
                'Segunda Operacion Cabeza Soplo' => new SegundaOperacionCabezaSoploController(),
                default => null, // Should not happen for this class if configured correctly
            };
        }

        return match ($process) {
            'Cepillado' => new CepilladoController(),
            'Desbaste Exterior' => new DesbasteExteriorController(),
            'Revision Laterales' => new RevLateralesController(),
            'Primera Operacion' => new PrimeraOpeSoldaduraController(),
            'Barreno Maniobra' => new BarrenoManiobraController(),
            'Segunda Operacion' => new SegundaOpeSoldaduraController(),
            'Soldadura' => new SoldaduraController(),
            'Soldadura PTA' => new SoldaduraPTAController(),
            'Rectificado' => new RectificadoController(),
            'Asentado' => new AsentadoController(),
            'Calificado' => new revCalificadoController(),
            'Acabado Bombillo' => new AcabadoBombilloController(),
            'Acabado Molde' => new AcabadoMoldeController(),
            'Barreno Profundidad' => new BarrenoProfundidadController(),
            'Cavidades' => new CavidadesController(),
            'Copiado' => new CopiadoController(),
            'Off Set' => new OffSetController(),
            'Palomas' => new PalomasController(),
            'Rebajes' => new RebajesController(),
            'Embudo CM' => new EmbudoCMController(),

            'Operacion Equipo' => $class && $class->nombre == 'Candado Obturador' ? new CandadoObturadorController() : new PySOpeSoldaduraController(),
            'Primera Operacion Cabeza Soplo' => new PrimeraOperacionCabezaSoploController(),
            'Segunda Operacion Cabeza Soplo' => new SegundaOperacionCabezaSoploController(),
        };
    }

        /**
     * @param mixed $arrayPiecesInMeta
     * @param mixed $class
     * @param mixed $process
     */
    public function calculate_metaResult($arrayPiecesInMeta, $class, $process)
    {
        $total = 0;
        if ($arrayPiecesInMeta) {

            $usedAssemblies = array();
            foreach ($arrayPiecesInMeta as $piece) {
                // Obtener el número de pieza o juego (Soldadura normal solo tiene n_juego)
                $nPiece = $piece["piece"]->n_pieza ? $piece["piece"]->n_pieza : $piece["piece"]->n_juego;

                //Verificar si la pieza se registra por mitad o por juego
                $char = substr($nPiece, -1) ? substr($nPiece, -1) : "J";
                if ($char == "J") {
                    $total += $piece["color"] == "#ACF980A8" || $piece["color"] == "#79BFED" ? 1 : 0;
                } else {
                    $badPieces = 0;
                    // Extraer el numero de pieza
                    $noAssembly = substr($nPiece, 0, -1);
                    // Encontrar la primera mitad del juego en la tabla Piezas
                    $halfPiece = Pieza::query()->where('id_clase', $class->id)->where('proceso', $process)->where("n_pieza", $nPiece)->first();

                    //Verificar si ese juego aun no ha sido contado
                    if (!in_array($noAssembly, $usedAssemblies)) {
                        array_push($usedAssemblies, $noAssembly);

                        // Si la pieza no existe en la tabla piezas, omitir este juego
                        if ($halfPiece === null) {
                            continue;
                        }

                        // Buscar la segunda mitad del juego
                        $halfLetter = substr($halfPiece->n_pieza, -1) == "M" ? "H" : "M";
                        $halfPiece2 = Pieza::query()->where('id_clase', $class->id)->where('proceso', $process)->where("n_pieza", $noAssembly . $halfLetter)->first();
                        if ($halfPiece2 == null) { // Si aun no existe la otra mitad
                            $total += $this->verifyPiece($halfPiece) ? 0.5 : 0;
                        } else {
                            //Verificar si las dos piezas estan bien para contarlo en la meta
                            $correct = 0;
                            if ($halfPiece->id_operador == $halfPiece2->id_operador) {
                                $correct += $this->verifyPiece($halfPiece) ? 0.5 : 0;
                                $correct += $this->verifyPiece($halfPiece2) ? 0.5 : 0;
                                $total += $correct < 1 ? 0 : 1;

                                // Verificar si las mitades no pertenecen a la misma meta
                                $id_process = str_replace(" ", "_", $process) . '_' . $class->nombre . '_' . $class->id_ot;
                                $processDB = $this->get_ModelProcess($process, $class)::query()->where('id_proceso', $id_process)->first();

                                $modelProcessPieces = $this->get_ModelProcessPieces($process, $class);
                                $piece2 = $modelProcessPieces::query()->where('id_proceso', $processDB->id)
                                    ->where(function ($query) use ($halfPiece2) {
                                        $query->where('n_pieza', $halfPiece2->n_pieza)
                                            ->orWhere('n_juego', substr($halfPiece2->n_pieza, 0, -1));
                                    })
                                    ->first();
                                if ($piece2->id_meta != $piece["piece"]->id_meta) {
                                    // Si las mitades pertenecen a diferentes metas, restar la mitad correspondiente
                                    $total -= 0.5;
                                }
                            } else {
                                // Seleccionar la pieza que corresponda al operador de la meta y verificarla
                                if ($halfPiece->id_operador == auth()->user()->matricula) {
                                    $total += $this->verifyPiece($halfPiece) ? 0.5 : 0;
                                } else {
                                    $total += $this->verifyPiece($halfPiece2) ? 0.5 : 0;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $total;
    }
        /**
     * @param mixed $halfPiece
     */
    public function verifyPiece($halfPiece)
    {
        if ($halfPiece) {
            // Special logic for Soldadura PTA:
            // - Rechazada (2) o Incompleta (5) → siempre bloqueada
            // - Error de Fundicion → bloqueada hasta que sea liberada
            // - Cualquier otro defecto → pasa (no bloquea)
            if ($halfPiece->proceso === "Soldadura PTA") {
                if (in_array($halfPiece->liberacion, [2, 5])) {
                    return false;
                }
                // Si tiene defecto de Fundicion y NO ha sido liberada formalmente, bloquear
                if (in_array($halfPiece->error, ['Fundicion', 'Fundición']) && !in_array($halfPiece->liberacion, [1, 3])) {
                    return false;
                }
                return true;
            }

            // Standard logic for other processes
            // Estados válidos:
            // - liberacion = 1 (Liberado): Pieza aprobada por calidad
            // - liberacion = 3 (Buena sin liberación): Pieza correcta sin liberación formal
            // - liberacion = 0 con error = "Ninguno": Lógica legacy para piezas sin inspección

            if ($halfPiece->liberacion == 1 || $halfPiece->liberacion == 3) {
                // Pieza liberada o buena sin liberación
                return true;
            } elseif ($halfPiece->liberacion == 0 && $halfPiece->error == "Ninguno") {
                // Lógica legacy: pieza sin liberación pero sin errores
                return true;
            }
        }
        return false;
    }
        /**
     * @param mixed $meta
     */
    public function get_machinedPiecesInMeta($meta)
    {
        //Obtener las piezas desde la tabla del proceso y despues compararla para ver si esta liberada o no
        $class = Clase::query()->find($meta->id_clase);
        $modelPiecesProcess = $this->get_ModelProcessPieces($this->getSub_Process($meta->proceso, 0), $class);
        $piecesProcess = $modelPiecesProcess::query()->where("id_meta", $meta->id)->where('estado', 2)->get();
        if (count($piecesProcess) > 0) {
            $machinedPieces = [];
            foreach ($piecesProcess as $key => $piece) {
                if ($meta->proceso == "Copiado") {
                    $color = $piece->error_cilindrado === "Ninguno" && $piece->error_cavidades === "Ninguno" ? "#ACF980A8" : "#EC7063";
                } else {
                    $color = $piece->error == "Ninguno" ? "#ACF980A8" : "#EC7063";
                }
                $nPiece = $piece->n_pieza ? $piece->n_pieza : $piece->n_juego;
                $releasedPiece = Pieza::query()->where("id_clase", $meta->id_clase)->where('proceso', $meta->proceso)->where("n_pieza", $nPiece)->first();
                if ($releasedPiece) { //Verificar si la pieza esta inspeccionada (sin liberación, liberada, rechazada)
                    $color = match ($releasedPiece->liberacion) {
                        0 => $color, //Sin liberación (mantiene color basado en error)
                        1 => '#79BFED', // Liberada - Azul
                        2 => '#FF6B6B', // Rechazada - Rojo
                        3 => '#90EE90', // Buena sin liberación - Verde
                        4 => '#DDA0DD', // Mala sin liberación - Morado
                        5 => '#FFD700', // Incompleto - Amarillo
                        default => $color
                    };
                }
                $machinedPieces[$key] = [
                    'piece' => $piece,
                    'color' => $color
                ];
            }
            return $machinedPieces;
        }
        return null;
    }
        /**
     * @param mixed $class
     * @param mixed $processName
     */
    public function get_previousProcess($class, $processName)
    {
        $processString = str_contains($processName, "Operacion Equipo") ? $this->getSub_Process($processName, 0) : $processName;
        $process = $this->get_processNameDB($processString);

        //Establecer el orden de los procesos
        $baseType = $class->getBaseType();
        switch ($baseType) {
            case "Bombillo":
            case "Molde":
                $processesInOrder = ["cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoBombillo", "acabadoMolde", "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado"];
                break;
            case "Obturador":
                $processesInOrder = ["operacionEquipo", "soldadura", "soldaduraPTA"];
                break;
            case "Fondo":
                $processesInOrder = ["operacionEquipo", "soldadura", "soldaduraPTA"];
                break;
            case "Corona":
                $processesInOrder = ["cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado"];
                break;
            case "Plato":
                $processesInOrder = ["barreno_maniobra", "operacionEquipo"];
                break;
            case "Embudo":
                $processesInOrder = ["operacionEquipo", "embudoCM"];
                break;
            case "Cabeza de Soplo":
                $processesInOrder = ["primeraOperacionCabezaSoplo", "segundaOperacionCabezaSoplo"];
                break;
            case "Candado Obturador":
                $processesInOrder = ["operacionEquipo"];
                break;
            default:
                $processesInOrder = [];
                break;
        }

        //Verificar los procesos por los que pasa la clase
        $processesNotEmpty = Procesos::query()->where("id_clase", $class->id)->first();
        if ($processesNotEmpty) {
            // Solo filtrar si hay al menos un proceso configurado (valor > 0) para esta clase
            $hasConfiguration = false;
            foreach ($processesInOrder as $proc) {
                if (isset($processesNotEmpty->$proc) && $processesNotEmpty->$proc > 0) {
                    $hasConfiguration = true;
                    break;
                }
            }
            if ($hasConfiguration) {
                foreach ($processesInOrder as $key => $proc) {
                    if (isset($processesNotEmpty->$proc) && $processesNotEmpty->$proc == 0) {
                        unset($processesInOrder[$key]);
                    }
                }
                // Reindexar el array para mantener los índices consecutivos
                $processesInOrder = array_values($processesInOrder);
            }
        }

        //Obtener el proceso anterior al actual
        $positionActualProcess = array_search($process, $processesInOrder);

        if ($positionActualProcess === false) {
            return null;
        }

        $previousProcess = $positionActualProcess !== 0 ? $processesInOrder[$positionActualProcess - 1] : null;

        // Modificación para procesos paralelos (Barreno Profundidad y Cavidades)
        if ($process == "cavidades") {
            // Forzar a que Cavidades mire hacia atrás al mismo proceso que Barreno Profundidad
            // En la lista: ... acabadoMolde, barreno_profundidad, cavidades ...
            // barreno_profundidad es el índice X. cavidades es X+1.
            // previous de cavidades por defecto es barreno. Queremos el anterior a ese.

            $currentIndex = array_search($process, $processesInOrder);
            if ($currentIndex >= 2) {
                $previousProcess = $processesInOrder[$currentIndex - 2];
            } else {
                $previousProcess = null;
            }
        }

        if ($process == "operacionEquipo") {
            $subOperation = $this->getSub_Process($processName, 1);
            if ($subOperation[0] == "2") {
                return "Operacion Equipo_1 operacion";
            }
            return null;
        } else {
            if ($process == "soldaduraPTA" && $previousProcess == "soldadura" || $process == "revision_laterales" && $previousProcess == "desbaste_exterior") {
                if (array_search($previousProcess, $processesInOrder) != 0) {
                    $previousProcess = $processesInOrder[array_search($process, $processesInOrder) - 2];
                } else {
                    return null;
                }
            }
        }
        return $previousProcess;
    }

        /**
     * @param mixed $processName
     */
    public function get_processNameDB($processName)
    {
        $process = match ($processName) {
            'Cepillado' => 'cepillado',
            'Desbaste Exterior' => 'desbaste_exterior',
            'Revision Laterales' => 'revision_laterales',
            'Primera Operacion' => 'pOperacion',
            'Barreno Maniobra' => 'barreno_maniobra',
            'Segunda Operacion' => 'sOperacion',
            'Rectificado' => 'rectificado',
            'Asentado' => 'asentado',
            'Calificado' => 'calificado',
            'Acabado Bombillo' => 'acabadoBombillo',
            'Acabado Molde' => 'acabadoMolde',
            'Barreno Profundidad' => 'barreno_profundidad',
            'Cavidades' => 'cavidades',
            'Copiado' => 'copiado',
            'Off Set' => 'offSet',
            'Palomas' => 'palomas',
            'Rebajes' => 'rebajes',
            'Grabado' => 'grabado',
            'Operacion Equipo' => 'operacionEquipo',
            'Embudo CM' => 'embudoCM',
            'Soldadura' => 'soldadura',
            'Soldadura PTA' => 'soldaduraPTA',
            'Primera Operacion Cabeza Soplo' => 'primeraOperacionCabezaSoplo',
            'Segunda Operacion Cabeza Soplo' => 'segundaOperacionCabezaSoplo',
        };
        return $process;
    }

        /**
     * @param mixed $process
     * @param mixed $previousProcess
     * @param mixed $class
     */
    public function getRemainingPieces_LateralesOrDesbaste($process, $previousProcess, $class)
    {
        $remainingPieces = 0;
        $totalGood = 0;
        $availableAssemblies = array();

        // Precargar piezas generales para evitar consultas N+1 en el bucle
        $generalPieces = Pieza::query()
            ->select(['id', 'id_ot', 'id_clase', 'n_pieza', 'proceso', 'liberacion', 'error', 'id_operador'])
            ->where('id_clase', '=', $class->id, 'and')
            ->get()
            ->groupBy('proceso');

        // Obtener las piezas maquinadas de Desbaste y Revision Laterales y oragizarlas en arrays como buenas y malas
        $assembliesProcesses = [
            "Desbaste Exterior" => array("good" => array(), "bad" => array(), "incomplete" => array()),
            "Revision Laterales" => array("good" => array(), "bad" => array(), "incomplete" => array())
        ];
        foreach ($assembliesProcesses as $processName => $assemblies) {
            // Obtener el id del proceso anterior
            $id_process = str_replace(' ', '_', $processName) . "_" . $class->nombre . "_" . $class->id_ot;
            $modelProcess = $this->get_ModelProcess($processName, $class);
            $processDB = $modelProcess::query()->where('id_proceso', '=', $id_process, 'and')->first();

            $countedAssemblies = array(); // Array para almacenar los juegos que ya han pasado
            if ($processDB) {
                // Obtener las piezas maquinadas en el proceso
                $modelPiecesProcess = $this->get_ModelProcessPieces($processName, $class);
                $pieces = $modelPiecesProcess::query()->where('id_proceso', '=', $processDB->id, 'and')->where('estado', '=', 2, 'and')->get();
                if (count($pieces) > 0) {
                    foreach ($pieces as $piece) {
                        if (!in_array($piece->n_juego, $countedAssemblies)) { // Si el juego aun no ha sido contado
                            array_push($countedAssemblies, $piece->n_juego); // Contar el juego
                            // Obtener las mitades de ese juego
                            $halfPieces = $modelPiecesProcess::query()->where('n_juego', '=', $piece->n_juego, 'and')->where('id_proceso', '=', $processDB->id, 'and')->get();
                            // Verificar si el juego esta completo
                            if ($halfPieces->count() > 1) { // Si el juego esta completo
                                $correct = false;
                                foreach ($halfPieces as $halfPiece) {
                                    // Verificar si la pieza ya esta maquinada
                                    $half = $generalPieces->get($processName)?->firstWhere('n_pieza', $halfPiece->n_pieza);
                                    if ($half) { // Si esta maquinada
                                        $releasedPiece = $this->verifyPiece($half);
                                        if ($releasedPiece) { // Verificar si la mitad esta correcta
                                            $correct = true;
                                        } else {
                                            $correct = false;
                                            array_push($assembliesProcesses[$processName]["bad"], $piece->n_juego);
                                            break;
                                        }
                                    } else {
                                        $correct = false;
                                        array_push($assembliesProcesses[$processName]["incomplete"], $piece->n_juego);
                                        break;
                                    }
                                }
                                if ($correct) { // Si las dos piezas estan correctas y maquinadas
                                    array_push($assembliesProcesses[$processName]["good"], $piece->n_juego);
                                }
                            } else {
                                array_push($assembliesProcesses[$processName]["incomplete"], $piece->n_juego);
                            }
                        }
                    }
                }
            }
        }


        // Filtrar los juegos que pasan al siguiente proceso, unicamente pasan los que estan buenos en ambos procesos o los que en un proceso estan bien y en el otro aun no se han registrado
        $goodAssemblies = array();
        foreach ($assembliesProcesses as $processName => $assemblies) {
            $reverseProcess = $processName == "Desbaste Exterior" ? "Revision Laterales" : "Desbaste Exterior";
            foreach ($assembliesProcesses[$processName]["good"] as $goodAssembly) {
                if (!in_array($goodAssembly, $goodAssemblies)) {
                    if (!in_array($goodAssembly, $assembliesProcesses[$reverseProcess]["bad"]) && !in_array($goodAssembly, $assembliesProcesses[$reverseProcess]["incomplete"])) {
                        array_push($goodAssemblies, $goodAssembly);
                    }
                }
            }
        }

        $totalGood = count($goodAssemblies);

        //Filtrar los juegos que pasaron y los que se encuentran registrados en el proceso actual
        [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($process, $class); //Obtener las piezas maquinadas en el proceso actual
        foreach ($goodAssemblies as $assembly) {
            if (!in_array($assembly, $occupiedAssemblies)) {
                $remainingPieces++;
                array_push($availableAssemblies, $assembly);
            } else { // Si el juego ya esta ocupado en el proceso actual pero no ha sido maquinado
                $processForAssembly = str_contains($process, "Operacion Equipo") ? "Operacion Equipo" : $process;
                $processAssembly = ["Barreno Maniobra", "Soldadura", "Rectificado", "Asentado", "Barreno Profundidad", "Palomas", "Rebajes", "Grabado", "Embudo CM"];
                if (!($processForAssembly === "Operacion Equipo" && $class && $class->nombre === "Candado Obturador")) {
                    $processAssembly[] = "Operacion Equipo";
                }
                if (in_array($processForAssembly, $processAssembly)) {
                    if (!in_array($assembly, $machinedPieces)) {
                        $remainingPieces += 1;
                    }
                } else {
                    for ($i = 1; $i <= 2; $i++) {
                        $halfPiece = substr($assembly, 0, -1) . ($i == 1 ? "H" : "M");
                        if (!in_array($halfPiece, $machinedPieces)) {
                            $remainingPieces += 0.5;
                        }
                    }
                }
            }
        }
        return [$availableAssemblies, $remainingPieces, $totalGood];
    }
        /**
     * @param mixed $process
     * @param mixed $previousProcess
     * @param mixed $class
     */
    public function get_RemainingPieces($process, $previousProcess, $class)
    {
        // Normalize sub-process names for assembly-type checks
        // "Operacion Equipo_1 operacion" / "Operacion Equipo_2 operacion" → "Operacion Equipo"
        $processForCheck = str_contains($process, "Operacion Equipo") ? "Operacion Equipo" : $process;
        $preProcessString = str_contains($previousProcess, "Operacion Equipo") ? "Operacion Equipo" : $previousProcess;
        $remainingPieces = 0;
        $totalGood = 0;
        $availableAssemblies = array();
        if ($previousProcess != null) {
            //Obtener las piezas maquinadas que esten correctas o liberadas del proceso anterior
            //Obtener el id del proceso anterior

            $modelPreProcess = $this->get_ModelProcess($preProcessString, $class);
            $stringPreProcess = str_replace(' ', '_', $previousProcess);
            $stringPreProcess = $stringPreProcess . "_" . $class->nombre . "_" . $class->id_ot; // Obtener el registro de la tabla del proceso anterior
            $preProcessDB = $modelPreProcess::query()->where('id_proceso', '=', $stringPreProcess, 'and')->first();
            if ($preProcessDB) {
                //Obtener las piezas maquinadas en el proceso anterior
                $modelPiecesPreProcess = $this->get_ModelProcessPieces($preProcessString, $class);
                $prePieces = $modelPiecesPreProcess::query()->where('id_proceso', '=', $preProcessDB->id, 'and')->where('estado', '=', 2, 'and')->get();
                if ($prePieces->isNotEmpty()) {
                    [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($process, $class); //Obtener las piezas maquinadas en el proceso actual

                    // Pre-fetch all general pieces for this OT and class to avoid N+1 in the loop
                    $generalPieces = Pieza::query()
                        ->select(['id', 'id_ot', 'id_clase', 'n_pieza', 'proceso', 'liberacion', 'error', 'id_operador'])
                        ->where('id_clase', '=', $class->id, 'and')
                        ->get()
                        ->groupBy('proceso');

                    $countedAssemblies = array();
                    // Piece assembly cache
                    $assembliesByGame = $prePieces->groupBy('n_juego');

                    //Guardar en un array los juegos restantes de la piezas del proceso anterior
                    foreach ($prePieces as $prePiece) {
                        // Skip if already occupied or machined in current process or already counted
                        if (in_array($prePiece->n_juego, $occupiedAssemblies) || in_array($prePiece->n_juego, $countedAssemblies)) {
                            continue;
                        }

                        $assembly = $assembliesByGame->get($prePiece->n_juego);
                        array_push($countedAssemblies, $prePiece->n_juego);
                        $status = 0;
                        $correct = 0;

                        if ($assembly->count() > 1) { //Si el juego tiene dos mitades
                            foreach ($assembly as $piece) {
                                // Optimized lookup via pre-fetched collection
                                $generalPiece = $generalPieces->get($previousProcess)?->where('n_pieza', $piece->n_pieza)->first();
                                $releasedPiece = $this->verifyPiece($generalPiece);

                                if ($releasedPiece) { // Si la pieza esta correcta o liberada en el proceso anterior
                                    if ($process == "Revision Laterales" || $process == "Desbaste Exterior") {
                                        // Verificar si la pieza ha sido registrada en el proceso intermedio
                                        $intermediateProcess = $process == "Revision Laterales" ? "Desbaste Exterior" : "Revision Laterales";
                                        $id_process_int = str_replace(" ", "_", $intermediateProcess) . "_" . $class->nombre . "_" . $class->id_ot;
                                        $processIntermediateDB = $this->get_ModelProcess($intermediateProcess, $class)::query()->where('id_proceso', $id_process_int)->first();

                                        if ($processIntermediateDB) {
                                            $pieceIntermedio = $this->get_ModelProcessPieces($intermediateProcess, $class)::query()->where('n_pieza', $piece->n_pieza)->where('id_proceso', $processIntermediateDB->id)->first();
                                            if ($pieceIntermedio) {
                                                if ($pieceIntermedio->estado == 2) { // Si la pieza esta registrada y maquinada en el proceso intermedio
                                                    $generalPieceInt = $generalPieces->get($intermediateProcess)?->where('n_pieza', $pieceIntermedio->n_pieza)->first();
                                                    if ($this->verifyPiece($generalPieceInt)) {
                                                        $status += 1;
                                                        $correct += 1;
                                                    }
                                                } else if ($pieceIntermedio->estado == 0) {
                                                    $status += 1;
                                                }
                                            } else {
                                                $status += 1;
                                                $correct += 1;
                                            }
                                        } else {
                                            $status += 1;
                                            $correct += 1;
                                        }
                                    } else {
                                        $status += 1;
                                        $correct += 1;
                                    }
                                }
                            }
                            if ($status > 1 && ($correct == 0 || $correct > 1)) { //Si las dos piezas estan correctas o liberadas contar el juego
                                $remainingPieces++;
                                $totalGood++;
                                array_push($availableAssemblies, $prePiece->n_juego);
                            }
                        } else { // Si el juego es completo
                            // Verificar si la pieza esta correcta
                            $generalPiece = $generalPieces->get($previousProcess)?->where('n_pieza', $assembly[0]->n_juego)->first();
                            if ($this->verifyPiece($generalPiece)) {
                                $remainingPieces++;
                                $totalGood++;
                                array_push($availableAssemblies, $prePiece->n_juego);
                            }
                        }
                    }
                }
            }
        } else {
            $consignamentPieces = $class->piezas; //Obtener las piezas con consignación
            [$occupiedAssemblies, $machinedPieces] = $this->get_machinedPieces($process, $class); //Obtener las piezas maquinadas

            //Calcular las piezas restantes
            $remainingPieces = $consignamentPieces;
            $totalGood = $consignamentPieces;
            if (count($machinedPieces) > 0) {
                $letterPiece = substr($machinedPieces[0], -1);
                foreach ($machinedPieces as $piece) {
                    if ($letterPiece != "J") {
                        $remainingPieces = $remainingPieces - 0.5; //Si las piezas se registran por mitad restar .5
                    } else {
                        $remainingPieces = $remainingPieces - 1; //Si las piezas se registran completas restar 1
                    }
                }
            }

            //Filtrar las piezas disponibles en los procesos quitando las piezas maquinadas
            for ($i = 1; $i <= $consignamentPieces; $i++) {
                $n_juego_string = $i . "J";
                if (!in_array($n_juego_string, $occupiedAssemblies)) {
                    array_push($availableAssemblies, $n_juego_string);
                }
            }
        }
        return [$availableAssemblies, $remainingPieces, $totalGood];
    }

        /**
     * @param mixed $processName
     * @param mixed $class
     */
    public function get_machinedPieces($processName, $class)
    {
        $processString = str_contains($processName, "Operacion Equipo") ? $this->getSub_Process($processName, 0) : $processName;
        // Obtener el modelo del proceso
        $modelProcess = $this->get_ModelProcess($processString, $class);
        $id_process_string = str_replace(' ', '_', $processName) . "_" . $class->nombre . "_" . $class->id_ot;
        $processDB = $modelProcess::query()->where('id_proceso', '=', $id_process_string, 'and')->first();

        //Si el proceso no existe crearlo para retornar las piezas
        if (!$processDB) {
            // Guard: "Operacion Equipo" without a sub-process number cannot be inserted
            // (operacion column would be empty). Return empty arrays instead.
            if ($processString == "Operacion Equipo" && !str_contains($processName, "operacion")) {
                return [[], []];
            }
            $processDB = new $modelProcess();
            $processDB->id_proceso = $id_process_string;
            $processDB->id_ot = $class->id_ot;
            if ($processString == "Operacion Equipo") {
                $processDB->id_clase = $class->id;
                $noOperation = explode(" ", $this->getSub_Process($processName, 1));
                $processDB->operacion = $noOperation[0];
            }
            $processDB->save();
        }

        // Obtener las piezas maquinadas en el proceso correspondiente
        $modelPiecesProcess = $this->get_ModelProcessPieces($processString, $class);
        $machinedPiecesInProcess = $modelPiecesProcess::query()->where('id_proceso', '=', $processDB->id, 'and')->where('estado', '=', 2, 'and')->get();

        //Insertar los juegos maquinados en un array
        $machinedPieces = [];
        foreach ($machinedPiecesInProcess as $piece) {
            if ($piece->n_pieza) {
                array_push($machinedPieces, $piece->n_pieza);
            } else {
                array_push($machinedPieces, $piece->n_juego);
            }
        }
        //Obtener las piezas ocupadas en el proceso correspondiente
        $occupiedPieces = $modelPiecesProcess::query()->where('id_proceso', '=', $processDB->id, 'and')->whereIn('estado', [1, 2], 'and', false)->get();

        //Insertar los juegos ocupados en un array
        $occupiedAssemblies = [];
        foreach ($occupiedPieces as $piece) {
            if (!in_array($piece->n_juego, $occupiedAssemblies)) {
                $occupiedAssemblies[] = $piece->n_juego;
            }
        }
        return [$occupiedAssemblies, $machinedPieces];
    }
        /**
     * @param mixed $process
     * @param mixed $class
     */
    public function get_ModelProcess($process, $class = null)
    {
        $modelProcess = match ($process) {
            'Cepillado' => "Cepillado",
            'Desbaste Exterior' => "DesbasteExterior",
            'Revision Laterales' => "RevLaterales",
            'Primera Operacion' => "PrimeraOpeSoldadura",
            'Barreno Maniobra' => "BarrenoManiobra",
            'Segunda Operacion' => "SegundaOpeSoldadura",
            'Rectificado' => "Rectificado",
            'Asentado' => "Asentado",
            'Calificado' => "revCalificado",
            'Acabado Bombillo' => "AcabadoBombilo",
            'Acabado Molde' => "AcabadoMolde",
            'Barreno Profundidad' => "BarrenoProfundidad",
            'Cavidades' => "Cavidades",
            'Copiado' => "Copiado",
            'Off Set' => "OffSet",
            'Palomas' => "Palomas",
            'Rebajes' => "Rebajes",
            'Grabado' => "Grabado", // No existe, crearlo
            'Operacion Equipo' => ($class && $class->nombre == 'Candado Obturador') ? "CandadoObturador" : "PySOpeSoldadura",
            'Embudo CM' => "EmbudoCM",
            'Soldadura' => "Soldadura",
            'Soldadura PTA' => "SoldaduraPTA",
            'Primera Operacion Cabeza Soplo' => "PrimeraOperacionCabezaSoplo",
            'Segunda Operacion Cabeza Soplo' => "SegundaOperacionCabezaSoplo",
            'Candado Obturador' => "CandadoObturador",
        };
        return "App\Models\\" . $modelProcess;
    }
        /**
     * @param mixed $process
     * @param mixed $class
     */
    public function getModelProcessCNominal_Tolerance($process, $class = null)
    {
        $cNominal = match ($process) {
            'Cepillado' => "Cepillado_cnominal",
            'Desbaste Exterior' => "Desbaste_cnominal",
            'Revision Laterales' => "RevLaterales_cnominal",
            'Primera Operacion' => "PrimeraOpeSoldadura_cnominal",
            'Barreno Maniobra' => "BarrenoManiobra_cnominal",
            'Segunda Operacion' => "SegundaOpeSoldadura_cnominal",
            'Calificado' => "revCalificado_cnominal",
            'Acabado Bombillo' => "AcabadoBombilo_cnominal",
            'Acabado Molde' => "AcabadoMolde_cnominal",
            'Barreno Profundidad' => "BarrenoProfundidad_cnominal",
            'Cavidades' => "Cavidades_cnominal",
            'Copiado' => "Copiado_cnominal",
            'Off Set' => "OffSet_cnominal",
            'Palomas' => "Palomas_cnominal",
            'Rebajes' => "Rebajes_cnominal",
            'Grabado' => "Grabado_cnominal", // No existe, crearlo
            'Operacion Equipo' => ($class && $class->nombre == 'Candado Obturador') ? "CandadoObturador_cnominal" : "PySOpeSoldadura_cnominal",
            'Embudo CM' => "EmbudoCM_cnominal",
            'Primera Operacion Cabeza Soplo' => "PrimeraOperacionCabezaSoplo_cnominal",
            'Segunda Operacion Cabeza Soplo' => "SegundaOperacionCabezaSoplo_cnominal",
            'Candado Obturador' => "CandadoObturador_cnominal",
        };
        $cNominal = "App\Models\\" . $cNominal;

        $tolerance = match ($process) {
            'Cepillado' => "Cepillado_tolerancia",
            'Desbaste Exterior' => "Desbaste_tolerancia",
            'Revision Laterales' => "RevLaterales_tolerancia",
            'Primera Operacion' => "PrimeraOpeSoldadura_tolerancia",
            'Barreno Maniobra' => "BarrenoManiobra_tolerancia",
            'Segunda Operacion' => "SegundaOpeSoldadura_tolerancia",
            'Calificado' => "revCalificado_tolerancia",
            'Acabado Bombillo' => "AcabadoBombilo_tolerancia",
            'Acabado Molde' => "AcabadoMolde_tolerancia",
            'Barreno Profundidad' => "BarrenoProfundidad_tolerancia",
            'Cavidades' => "Cavidades_tolerancia",
            'Copiado' => "Copiado_tolerancia",
            'Off Set' => "OffSet_tolerancia",
            'Palomas' => "Palomas_tolerancia",
            'Rebajes' => "Rebajes_tolerancia",
            'Grabado' => "Grabado_tolerancia", // No existe, crearlo
            'Operacion Equipo' => ($class && $class->nombre == 'Candado Obturador') ? "CandadoObturador_tolerancia" : "PySOpeSoldadura_tolerancia",
            'Embudo CM' => "EmbudoCM_tolerancias",
            'Primera Operacion Cabeza Soplo' => "PrimeraOperacionCabezaSoplo_tolerancia",
            'Segunda Operacion Cabeza Soplo' => "SegundaOperacionCabezaSoplo_tolerancia",
            'Candado Obturador' => "CandadoObturador_tolerancia",
        };
        $tolerance = "App\Models\\" . $tolerance;

        return [$cNominal, $tolerance];
    }
        /**
     * @param mixed $process
     * @param mixed $class
     */
    public function get_ModelProcessPieces($process, $class = null)
    {
        $modelProcess = match ($process) {
            'Cepillado' => "Pza_cepillado",
            'Desbaste Exterior' => "Desbaste_pza",
            'Revision Laterales' => "RevLaterales_pza",
            'Primera Operacion' => "PrimeraOpeSoldadura_pza",
            'Barreno Maniobra' => "BarrenoManiobra_pza",
            'Segunda Operacion' => "SegundaOpeSoldadura_pza",
            'Rectificado' => "Rectificado_pza",
            'Asentado' => "Asentado_pza",
            'Calificado' => "revCalificado_pza",
            'Acabado Bombillo' => "AcabadoBombilo_pza",
            'Acabado Molde' => "AcabadoMolde_pza",
            'Barreno Profundidad' => "BarrenoProfundidad_pza",
            'Cavidades' => "Cavidades_pza",
            'Copiado' => "Copiado_pza",
            'Off Set' => "OffSet_pza",
            'Palomas' => "Palomas_pza",
            'Rebajes' => "Rebajes_pza",
            // 'Grabado' => "Grabado_pza", // No existe, crearlo
            'Operacion Equipo' => ($class && $class->nombre == 'Candado Obturador') ? "CandadoObturador_pza" : "PySOpeSoldadura_pza",
            'Embudo CM' => "EmbudoCM_pza",
            'Soldadura' => "Soldadura_pza",
            'Soldadura PTA' => "SoldaduraPTA_pza",
            'Primera Operacion Cabeza Soplo' => "PrimeraOperacionCabezaSoplo_pza",
            'Segunda Operacion Cabeza Soplo' => "SegundaOperacionCabezaSoplo_pza",
            'Candado Obturador' => "CandadoObturador_pza",
        };
        return "App\Models\\" . $modelProcess;
    }

        /**
     * @param mixed $h_inicio
     * @param mixed $h_termino
     */
    public function calculateHrs($h_inicio, $h_termino) //Función para calcular las horas trabajadas.
    {
        $carbon1 = Carbon::parse($h_inicio);
        $carbon2 = Carbon::parse($h_termino);

        // Si la hora de termino es menor o igual a la hora de inicio, se le suma un día a la hora de termino
        if ($carbon2 <= $carbon1) {
            $carbon2->addDay();
        }

        //Calcular la diferencia entre las horas en minutos
        $diferencia = $carbon1->diffInMinutes($carbon2);
        if ($diferencia > 480) {
            $diferencia = $diferencia - 90; //Si la diferencia es mayor a 8 horas, se le resta media hora de limpieza y una hora de comida
        } else {
            $diferencia = $diferencia - 60; //Si la diferencia es menor o igual a 8 horas, se le resta media hora de limpieza y media hora de comida
        }
        return $diferencia; //Retorno las horas trabajadas.
    }
        /**
     * @param mixed &$meta
     * @param mixed $h_inicio
     * @param mixed $h_termino
     * @param mixed $class
     * @param mixed $machine
     */
    public function calculateMeta(&$meta, $h_inicio, $h_termino, $class, $machine) //Función para calcular la meta.
    {
        //Asignar tiempo estándar
        $tiempo = tiempoproduccion::query()->where('id_clase', $class->id)->where('proceso', $this->nameProcess($meta->proceso))->first();
        $meta->t_estandar = $tiempo->tiempo ?? 0;

        //Calcular las horas de trabajo de cada operador
        if ($tiempo) {
            $workHrs = $this->calculateHrs($h_inicio, $h_termino);
            $tiempo = $tiempo->tiempo != 0 ? round(($workHrs / $tiempo->tiempo)) : 0;
            $meta->meta = str_contains($machine, '_') ? $tiempo * 2 : $tiempo; //Asignar la meta calculada
        } else {
            $meta->meta = 0; //Si no se encuentra el tiempo, se asigna 0 a la meta
        }
        $meta->save();
    }

        /**
     * @param mixed $process
     */
    public function nameProcess($process)
    {
        $nameProcess = match ($process) {
            'Cepillado' => 'cepillado',
            'Desbaste Exterior' => 'desbaste',
            'Revision Laterales' => 'revLaterales',
            'Primera Operacion' => 'primeraOpeSoldadura',
            'Barreno Maniobra' => 'barrenoManiobra',
            'Segunda Operacion' => 'segundaOpeSoldadura',
            'Primera Operacion Cabeza Soplo' => 'primeraOperacionCabezaSoplo',
            'Segunda Operacion Cabeza Soplo' => 'segundaOperacionCabezaSoplo',
            'Rectificado' => 'rectificado',
            'Asentado' => 'asentado',
            'Calificado' => 'revCalificado',
            'Acabado Bombillo' => 'acabadoBombillo',
            'Acabado Molde' => 'acabadoMolde',
            'Barreno Profundidad' => 'barrenoProfundidad',
            'Cavidades' => 'cavidades',
            'Copiado' => 'copiado',
            'Off Set' => 'offset',
            'Palomas' => 'palomas',
            'Rebajes' => 'rebajes',
            'Grabado' => 'grabado',
            'Operacion Equipo_1 operacion' => 'operacionEquipo',
            'Operacion Equipo_2 operacion' => 'operacionEquipo',
            'Embudo CM' => 'embudoCM',
            'Soldadura' => 'soldadura',
            'Soldadura PTA' => 'soldaduraPTA',
        };
        return $nameProcess;
    }
        /**
     * @param mixed $process
     */
    public function convertProcessToString($process)
    {
        switch ($process) {
            case "cepillado":
                return "Cepillado";
            case "desbaste_exterior":
                return "Desbaste Exterior";
            case "revision_laterales":
                return "Revision Laterales";
            case "pOperacion":
                return "Primera Operacion";
            case "barreno_maniobra":
                return "Barreno Maniobra";
            case "sOperacion":
                return "Segunda Operacion";
            case "rectificado":
                return "Rectificado";
            case "asentado":
                return "Asentado";
            case "calificado":
                return "Calificado";
            case "acabadoBombillo":
                return "Acabado Bombillo";
            case "acabadoMolde":
                return "Acabado Molde";
            case "barreno_profundidad":
                return "Barreno Profundidad";
            case "primeraOperacionCabezaSoplo":
                return "Primera Operacion Cabeza Soplo";
            case "segundaOperacionCabezaSoplo":
                return "Segunda Operacion Cabeza Soplo";
            case "cavidades":
                return "Cavidades";
            case "copiado":
                return "Copiado";
            case "offSet":
                return "Off Set";
            case "palomas":
                return "Palomas";
            case "rebajes":
                return "Rebajes";
            case "grabado":
                return "Grabado";
            case "embudoCM":
                return "Embudo CM";
            case "soldadura":
                return "Soldadura";
            case "soldaduraPTA":
                return "Soldadura PTA";
            default:
                return $process;
        }
    }

    /**
     * @param string|null $passwordEntered
     * @return User|false
     */
    public function validatePasswordQuality($passwordEntered)
    {
        if ($passwordEntered) {
            $users = User::query()->where('perfil', 4)->get();
            foreach ($users as $user) {
                if (Hash::check($passwordEntered, $user->contrasena)) {
                    return $user; // Retornar el usuario de calidad
                }
            }
        }
        return false;
    }

    /**
     * Verificar contraseña de calidad y retornar datos de piezas para liberación
     */
    public function verifyQualityPassword(Request $request)
    {
        $password = $request->input('passwordQuality');
        $qualityUser = $this->validatePasswordQuality($password);

        if ($qualityUser) {
            $meta = Metas::query()->find($request->meta);
            if (!$meta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta no encontrada'
                ], 404);
            }


            // Obtener el modelo de piezas específico del proceso
            $processString = str_contains($meta->proceso, "Operacion Equipo")
                ? $this->getSub_Process($meta->proceso, 0)
                : $meta->proceso;

            try {
                $class = Clase::query()->find($meta->id_clase);
                $modelPiecesProcess = $this->get_ModelProcessPieces($processString, $class);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proceso no soportado para liberación de calidad'
                ], 400);
            }

            // Obtener piezas del proceso específico filtradas por id_meta
            $processPieces = $modelPiecesProcess::query()->where('id_meta', $meta->id)
                ->where('estado', 2) // Solo piezas maquinadas
                ->orderBy('created_at', 'asc') // Ordenar por fecha de creación
                ->get();

            // Evitar múltiples registros por una misma pieza (ej. Soldadura PTA tiene 3 sub-records por pieza M/H)
            $processPieces = $processPieces->unique(function ($item) {
                return $item->n_pieza ?? $item->n_juego;
            })->values();

            // Obtener información de liberación de la tabla Pieza
            $piecesWithReleaseInfo = [];
            foreach ($processPieces as $processPiece) {
                $nPiece = $processPiece->n_pieza ?? $processPiece->n_juego;
                $releasedPiece = Pieza::query()->where('id_clase', $meta->id_clase)
                    ->where('proceso', $meta->proceso)
                    ->where('n_pieza', $nPiece)
                    ->first();

                // Determinar el error
                $error = 'Ninguno';
                if ($meta->proceso == "Copiado") {
                    if ($processPiece->error_cilindrado !== "Ninguno" || $processPiece->error_cavidades !== "Ninguno") {
                        $error = trim($processPiece->error_cilindrado . ' ' . $processPiece->error_cavidades);
                    }
                } else {
                    $error = $releasedPiece ? $releasedPiece->error : ($processPiece->error ?? 'Ninguno');
                }

                $piecesWithReleaseInfo[] = [
                    'id' => $releasedPiece ? $releasedPiece->id : null,
                    'n_pieza' => $nPiece,
                    'proceso' => $meta->proceso,
                    'error' => $error,
                    'liberacion' => $releasedPiece ? $releasedPiece->liberacion : 0,
                    'operator' => $meta->id_usuario,
                    'operator_id' => $meta->id_usuario,
                    'created_at' => $processPiece->created_at,
                ];
            }

            // Agrupar piezas en juegos completos
            $completeSets = $this->groupPiecesIntoCompleteSets($piecesWithReleaseInfo);

            // Guardar el usuario de calidad en sesión para usarlo después
            session(['quality_user' => $qualityUser->matricula]);

            // Retornar JSON con los datos de los juegos completos
            return response()->json([
                'success' => true,
                'message' => 'Contraseña correcta. Acceso a liberación de piezas.',
                'pieces' => $completeSets,
                'qualityUser' => $qualityUser->nombre . ' ' . $qualityUser->a_paterno . ' ' . $qualityUser->a_materno
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Contraseña incorrecta. Solo personal de calidad puede acceder.'
        ], 200);
    }

    /**
     * Agrupar piezas en juegos completos (M + H = J)
     * Solo retorna juegos completos, ignora piezas sueltas
     *
     * @param array $pieces
     * @return array
     */
    private function groupPiecesIntoCompleteSets($pieces)
    {
        $completeSets = [];
        $processedIndices = [];

        for ($i = 0; $i < count($pieces); $i++) {
            // Saltar si ya fue procesada
            if (in_array($i, $processedIndices)) {
                continue;
            }

            $piece = $pieces[$i];
            $pieceNumber = $piece['n_pieza'];

            // Verificar si la pieza termina en M o H
            if (preg_match('/^(\d+)M$/', $pieceNumber, $matchM)) {
                // Buscar la pieza H correspondiente
                $baseNumber = $matchM[1];
                $hPieceIndex = null;

                for ($j = $i + 1; $j < count($pieces); $j++) {
                    if (in_array($j, $processedIndices)) {
                        continue;
                    }
                    if ($pieces[$j]['n_pieza'] === "{$baseNumber}H") {
                        $hPieceIndex = $j;
                        break;
                    }
                }

                if ($hPieceIndex !== null) {
                    // Encontramos ambas mitades, crear un juego
                    $completeSets[] = [
                        'displayName' => "{$baseNumber}J",
                        'isSet' => true,
                        'pieces' => [$piece, $pieces[$hPieceIndex]]
                    ];
                    $processedIndices[] = $i;
                    $processedIndices[] = $hPieceIndex;
                }
                // Si no hay H correspondiente, ignorar la pieza M (no se agrega)
            } elseif (preg_match('/^(\d+)H$/', $pieceNumber, $matchH)) {
                // Buscar la pieza M correspondiente
                $baseNumber = $matchH[1];
                $mPieceIndex = null;

                for ($j = $i + 1; $j < count($pieces); $j++) {
                    if (in_array($j, $processedIndices)) {
                        continue;
                    }
                    if ($pieces[$j]['n_pieza'] === "{$baseNumber}M") {
                        $mPieceIndex = $j;
                        break;
                    }
                }

                if ($mPieceIndex !== null) {
                    // Encontramos ambas mitades, crear un juego (M primero, H segundo)
                    $completeSets[] = [
                        'displayName' => "{$baseNumber}J",
                        'isSet' => true,
                        'pieces' => [$pieces[$mPieceIndex], $piece]
                    ];
                    $processedIndices[] = $i;
                    $processedIndices[] = $mPieceIndex;
                }
                // Si no hay M correspondiente, ignorar la pieza H (no se agrega)
            } else {
                // No es una pieza M o H, podría ser un juego completo (NJ)
                // En este caso, agregarlo como está
                if ($piece['id']) {
                    $completeSets[] = [
                        'displayName' => $pieceNumber,
                        'isSet' => false,
                        'pieces' => [$piece]
                    ];
                    $processedIndices[] = $i;
                }
            }
        }

        return $completeSets;
    }


    /**
     * Procesar la liberación de piezas
     */
    public function releasePieces(Request $request)
    {
        $meta = Metas::query()->find($request->input('meta'));
        if (!$meta) {
            return redirect()->back()->with('error', 'Meta no encontrada');
        }

        $qualityUserMatricula = session('quality_user');
        if (!$qualityUserMatricula) {
            return redirect()->back()->with('error', 'Sesión de calidad expirada. Por favor, vuelva a autenticarse.');
        }

        $pieces = $request->input('pieces', []);

        // Contadores para cada tipo de estado
        $statusCounts = [
            1 => 0, // Liberado
            2 => 0, // Rechazado
            3 => 0, // Buena sin liberación
            4 => 0, // Mala sin liberación
            5 => 0  // Incompleto
        ];

        $statusPieceNumbers = [
            1 => [],
            2 => [],
            5 => []
        ];

        foreach ($pieces as $pieceData) {
            // Verificar si es un juego completo (tiene múltiples IDs)
            $isSet = isset($pieceData['isSet']) && $pieceData['isSet'] == '1';
            $pieceIds = [];

            if ($isSet && isset($pieceData['ids'])) {
                // Es un juego completo, obtener todos los IDs
                $pieceIds = array_values($pieceData['ids']);
            } elseif (!empty($pieceData['id'])) {
                // Es una pieza individual (formato antiguo para compatibilidad)
                $pieceIds = [$pieceData['id']];
            } elseif (isset($pieceData['ids']) && is_array($pieceData['ids'])) {
                // Formato nuevo pero pieza individual
                $pieceIds = array_values($pieceData['ids']);
            }

            // Procesar cada pieza del grupo (o la pieza individual)
            if (!empty($pieceData['action']) && !empty($pieceIds)) {
                foreach ($pieceIds as $pieceId) {
                    $piece = Pieza::query()->find($pieceId);
                    if ($piece) {
                        $action = intval($pieceData['action']);
                        $comments = $pieceData['comments'] ?? '';

                        // Validar que el valor de acción esté en el rango permitido (1-5)
                        if ($action >= 1 && $action <= 5) {
                            // Actualizar el estado de liberación
                            $piece->liberacion = $action;
                            $piece->fecha_liberacion = now();
                            $piece->user_liberacion = $qualityUserMatricula;
                            $piece->observacion_liberacion = $comments;
                            $piece->save();

                            // Incrementar el contador correspondiente
                            $statusCounts[$action]++;

                            // Guardar el número de juego limpio (solo el número + J)
                            if (isset($statusPieceNumbers[$action])) {
                                $cleanNum = preg_replace('/[a-zA-Z]/', '', (string)$piece->n_pieza);
                                if ($cleanNum) {
                                    $statusPieceNumbers[$action][] = $cleanNum . "J";
                                }
                            }
                        }
                    }
                }
            }
        }

        // Obtener el nombre del inspector de calidad
        $qualityUser = User::query()->where('matricula', $qualityUserMatricula)->first();
        $qualityName = $qualityUser ? "{$qualityUser->nombre} {$qualityUser->a_paterno}" : "Inspector";

        // Limpiar la sesión de calidad
        session()->forget('quality_user');

        // Construir mensajes diferenciados
        $statusMessagesToast = [];
        $htmlLogPartsArr = [];

        $labelsToast = [1 => 'Liberadas', 2 => 'Rechazadas', 5 => 'Incompletas'];
        $labelsLog = [1 => 'registro de Liberación', 2 => 'registro de rechazos', 5 => 'registro de incompletas'];
        $colorsLog = [1 => '#2E86C1', 2 => '#C0392B', 5 => '#B7950B'];

        foreach ([1, 2, 5] as $status) {
            $count = $statusCounts[$status];
            if ($count > 0) {
                $unique = array_unique($statusPieceNumbers[$status]);
                sort($unique, SORT_NATURAL);
                $nums = implode(', ', $unique);

                // Mensaje para el Toast (Directo)
                $statusMessagesToast[] = "{$labelsToast[$status]}: {$count} [{$nums}]";

                // Mensaje para el Log (Narrativo + Colores)
                $labelLog = $labelsLog[$status];
                $colorLog = $colorsLog[$status];
                $htmlLogPartsArr[] = "{$labelLog} de los juegos <span style='color:{$colorLog}; font-weight:bold;'>[{$nums}]</span>";
            }
        }

        if (count($statusMessagesToast) > 0) {
            // --- CONSTRUCCIÓN DE MENSAJE SIMPLIFICADO PARA EL TOAST (OPERADOR) ---
            $actionsPerformed = [];
            if ($statusCounts[1] > 0) $actionsPerformed[] = "liberaciones";
            if ($statusCounts[2] > 0) $actionsPerformed[] = "rechazos";
            if ($statusCounts[5] > 0) $actionsPerformed[] = "registro de incompletos";

            $actionsText = "";
            if (count($actionsPerformed) === 1) {
                $actionsText = $actionsPerformed[0];
            } elseif (count($actionsPerformed) === 2) {
                $actionsText = $actionsPerformed[0] . " y " . $actionsPerformed[1];
            } else {
                $lastAct = array_pop($actionsPerformed);
                $actionsText = implode(', ', $actionsPerformed) . " y " . $lastAct;
            }

            $message = "El inspector de calidad {$qualityName} realizó {$actionsText} correctamente.";

            // --- CONSTRUCCIÓN DE NARRATIVA DINÁMICA... (se mantiene igual para el Log)
            $activeResults = [];

            // 1. Lógica para LIBERADOS (AZUL)
            if (!empty($statusPieceNumbers[1])) {
                $unique = array_unique($statusPieceNumbers[1]);
                sort($unique, SORT_NATURAL);
                $nums = implode(', ', $unique);
                $isPlural = count($unique) > 1;
                $verb = $isPlural ? "se liberaron los juegos" : "se liberó el juego";
                $activeResults[] = "<span style='color:#2E86C1; font-weight:bold;'>{$verb} [{$nums}]</span>";
            }

            // 2. Lógica para RECHAZADOS (ROJO)
            if (!empty($statusPieceNumbers[2])) {
                $unique = array_unique($statusPieceNumbers[2]);
                sort($unique, SORT_NATURAL);
                $nums = implode(', ', $unique);
                $isPlural = count($unique) > 1;
                $verb = $isPlural ? "los juegos [{$nums}] fueron rechazados" : "el juego [{$nums}] fue rechazado";
                $activeResults[] = "<span style='color:#C0392B; font-weight:bold;'>{$verb}</span>";
            }

            // 3. Lógica para INCOMPLETOS (AMARILLO)
            if (!empty($statusPieceNumbers[5])) {
                $unique = array_unique($statusPieceNumbers[5]);
                sort($unique, SORT_NATURAL);
                $nums = implode(', ', $unique);
                $isPlural = count($unique) > 1;
                $verb = $isPlural ? "los juegos [{$nums}] quedaron registrados como incompletos" : "el juego [{$nums}] quedó registrado como incompleto";
                $activeResults[] = "<span style='color:#B7950B; font-weight:bold;'>{$verb}</span>";
            }

            // Construir el cuerpo de la oración con conectores naturales
            $introText = count($activeResults) > 1 ? "los siguientes resultados" : "el siguiente resultado";
            $narrative = "";

            // Construir el cuerpo de la oración con conectores naturales
            $introText = count($activeResults) > 1 ? "los siguientes resultados" : "el siguiente resultado";
            $narrative = "";

            if (count($activeResults) === 1) {
                $narrative = $activeResults[0];
            } elseif (count($activeResults) === 2) {
                $narrative = $activeResults[0] . " mientras que " . $activeResults[1];
            } else {
                $last = array_pop($activeResults);
                $narrative = implode(', ', $activeResults) . " mientras que " . $last;
            }

            // Obtener nombres descriptivos para los logs (en lugar de IDs)
            $otObj = Orden_trabajo::query()->with('moldura')->find($meta->id_ot);
            $otLabel = $otObj ? ($otObj->id . ($otObj->moldura ? " - " . $otObj->moldura->nombre : "")) : $meta->id_ot;

            $claseObj = Clase::query()->find($meta->id_clase);
            $classLabel = $claseObj ? $claseObj->nombre : $meta->id_clase;

            // Determinar la acción principal para el log
            $mainAction = 'Liberación por Calidad';
            if ($statusCounts[2] > 0 && $statusCounts[1] == 0) {
                $mainAction = 'Rechazo por Calidad';
            }

            // Recopilar todos los números de juegos/piezas afectados
            $allAffectedNums = [];
            foreach ([1, 2, 5] as $status) {
                if (!empty($statusPieceNumbers[$status])) {
                    $allAffectedNums = array_merge($allAffectedNums, $statusPieceNumbers[$status]);
                }
            }
            $cleanAffected = implode(', ', array_unique($allAffectedNums));

            // Obtener h_inicio del usuario (prioridad: inicio de solicitud de calidad -> inicio producción)
            $user = auth()->user();
            $h_inicio = $request->h_inicio_solicitud ?: ($user->prod_start_at ? Carbon::parse($user->prod_start_at)->format('H:i:s') : 'N/A');
            $h_termino = now()->format('H:i:s');

            // --- LOG 1: CIERRE DE INTERFAZ (Abandono de Liberación) ---
            SystemLog::create([
                'user_matricula' => $qualityUserMatricula,
                'action' => 'Abandono de Liberación',
                'details' => "El inspector <b>{$qualityName}</b> finalizó el registro y cerró la interfaz de calidad.",
                'ot' => $otLabel,
                'clase' => $classLabel,
                'proceso' => $meta->proceso,
                'maquina' => $meta->maquina,
                'n_pieza' => $cleanAffected ?: 'N/A',
                'h_inicio' => $h_inicio,
                'h_termino' => $h_termino,
                'id_ot' => $meta->id_ot,
                'id_clase' => $meta->id_clase
            ]);

            // --- LOG 2: RESULTADOS DE PRODUCCIÓN (Liberación o Rechazo) ---
            SystemLog::create([
                'user_matricula' => $qualityUserMatricula,
                'action' => $mainAction,
                'details' => "El inspector <b>{$qualityName}</b> finalizó la revisión de los juegos con {$introText}: {$narrative}.",
                'ot' => $otLabel,
                'clase' => $classLabel,
                'proceso' => $meta->proceso,
                'maquina' => $meta->maquina,
                'n_pieza' => $cleanAffected ?: 'N/A',
                'h_inicio' => $h_inicio,
                'h_termino' => $h_termino,
                'id_ot' => $meta->id_ot,
                'id_clase' => $meta->id_clase
            ]);

        } else {
            $message = "El inspector de calidad {$qualityName} no realizó cambios en las piezas.";
        }

        return redirect()->route('showReportFormat', [
            'meta' => $meta->id,
            'process' => $meta->proceso,
            'edit' => 0
        ])->with('success', $message);
    }

    /**
     * Obtener piezas para liberación (API endpoint)
     */
    public function getPiecesForRelease(Request $request)
    {
        $metaId = $request->input('meta');
        $meta = Metas::query()->find($metaId);

        if (!$meta) {
            return response()->json([
                'success' => false,
                'message' => 'Meta no encontrada'
            ], 404);
        }

        // Obtener las piezas del operador para esta meta
        $pieces = Pieza::query()->where('id_clase', $meta->id_clase)
            ->where('id_operador', $meta->id_usuario)
            ->where('proceso', $meta->proceso)
            ->get();

        return response()->json([
            'success' => true,
            'pieces' => $pieces
        ]);
    }

        /**
     * @param mixed $class
     */
    public function getProcessHistory($class)
    {
        $processes = array();
        $orderedProcesses = $this->setOrderedProcess($class);

        if ($orderedProcesses) {
            $soldaduraIncluded = false;
            foreach ($orderedProcesses as $processName) {
                // Modificación para agrupar Soldadura y Soldadura PTA
                if ($processName == "Soldadura" || $processName == "Soldadura PTA") {
                    if ($soldaduraIncluded) {
                        continue;
                    }
                    $processNameKey = "Soldadura y Soldadura PTA";
                    $soldaduraIncluded = true;
                } elseif ($processName == "Operacion Equipo") {
                    // Split Operacion Equipo into its subprocesses
                    $subprocesses = ["Operacion Equipo_1 operacion", "Operacion Equipo_2 operacion"];
                    foreach ($subprocesses as $subprocess) {
                        $processes[$subprocess] = array();
                        $piecesBadData = array();
                        $processes[$subprocess]['pieces'] = $this->getPieces($class, $subprocess, $piecesBadData);
                        $processes[$subprocess]['piecesBadData'] = $piecesBadData;
                    }
                    continue; // Skip the main "Operacion Equipo" key
                } else {
                    $processNameKey = $processName;
                }

                $processes[$processNameKey] = array();
                $piecesBadData = array();
                $processes[$processNameKey]['pieces'] = $this->getPieces($class, $processNameKey, $piecesBadData);
                $processes[$processNameKey]['piecesBadData'] = $piecesBadData;
            }
        }
        return $processes;
    }

        /**
     * @param mixed $class
     * @param mixed $processName
     * @param mixed &$piecesBadData
     */
    public function getPieces($class, $processName, &$piecesBadData)
    {
        $setStoredParts = array();
        $piecesArray = array();
        $piecesArray["good"] = array();
        $piecesArray["bad"] = array();
        $piecesArray["total"] = 0;

        $processNamesArray = $processName == "Soldadura y Soldadura PTA" ? ["Soldadura", "Soldadura PTA"] : [$processName];

        foreach ($processNamesArray as $pName) {
            $pieces = Pieza::query()->where("proceso", $pName)->where('id_clase', $class->id)->get();

            if (count($pieces) > 0) {
                foreach ($pieces as $piece) {
                    if (substr($piece->n_pieza, -1, 1) != "J") {
                        $pares = true;
                        preg_match('/^\d+/', $piece->n_pieza, $noSet);
                        if (isset($noSet[0])) {
                            $noSet = $noSet[0];
                            if (!in_array($noSet, $setStoredParts)) {
                                array_push($setStoredParts, $noSet);

                                $pFemale = Pieza::query()->where("n_pieza", $noSet . "H")->where('id_clase', $class->id)->where('proceso', $pName)->first();
                                $pMale = Pieza::query()->where("n_pieza", $noSet . "M")->where('id_clase', $class->id)->where('proceso', $pName)->first();

                                if ($pFemale && $pMale) {
                                    if ($pFemale->liberacion == 0) {
                                        if ($pFemale->error == "Ninguno" && $pMale->error == "Ninguno") {
                                            array_push($piecesArray["good"], $pFemale, $pMale);
                                        } else {
                                            // Modificación para PTA: solo Fundicion bloquea
                                            if ($processName === "Soldadura PTA") {
                                                if (in_array($pFemale->error, ['Fundicion', 'Fundición']) || in_array($pMale->error, ['Fundicion', 'Fundición'])) {
                                                    array_push($piecesArray["bad"], $pFemale, $pMale);
                                                    if (in_array($pFemale->error, ['Fundicion', 'Fundición']))
                                                        array_push($piecesBadData, $this->getBadPiecesData($pFemale));
                                                    if (in_array($pMale->error, ['Fundicion', 'Fundición']))
                                                        array_push($piecesBadData, $this->getBadPiecesData($pMale));
                                                } else {
                                                    array_push($piecesArray["good"], $pFemale, $pMale);
                                                }
                                            } else {
                                                array_push($piecesArray["bad"], $pFemale, $pMale);
                                                if ($pFemale->error != "Ninguno")
                                                    array_push($piecesBadData, $this->getBadPiecesData($pFemale));
                                                if ($pMale->error != "Ninguno")
                                                    array_push($piecesBadData, $this->getBadPiecesData($pMale));
                                            }
                                        }
                                    } else if ($pFemale->liberacion == 1) {
                                        array_push($piecesArray["good"], $pFemale, $pMale);
                                    } else {
                                        array_push($piecesArray["bad"], $pFemale, $pMale);
                                        if ($pFemale->error != "Ninguno") {
                                            array_push($piecesBadData, $this->getBadPiecesData($pFemale));
                                        } else {
                                            array_push($piecesBadData, $this->getBadPiecesData($pFemale, "Rechazada"));
                                        }
                                        if ($pMale->error != "Ninguno") {
                                            array_push($piecesBadData, $this->getBadPiecesData($pMale));
                                        } else {
                                            array_push($piecesBadData, $this->getBadPiecesData($pMale, "Rechazada"));
                                        }
                                    }
                                } else {
                                    $imcompletePiece = $pFemale ? $pFemale : $pMale;
                                    if ($imcompletePiece && $imcompletePiece->liberacion == 2) {
                                        array_push($piecesArray["bad"], $imcompletePiece, $imcompletePiece);
                                        array_push($piecesBadData, $this->getBadPiecesData($imcompletePiece, "Rechazada"));
                                    }
                                }
                            }
                        }
                    } else {
                        $pares = false;
                        if ($piece->liberacion == 0) {
                            if ($piece->error == "Ninguno") {
                                array_push($piecesArray["good"], $piece);
                            } else {
                                if ($processName === "Soldadura PTA") {
                                    if (in_array($piece->error, ['Fundicion', 'Fundición'])) {
                                        array_push($piecesArray["bad"], $piece);
                                        array_push($piecesBadData, $this->getBadPiecesData($piece));
                                    } else {
                                        array_push($piecesArray["good"], $piece);
                                    }
                                } else {
                                    array_push($piecesArray["bad"], $piece);
                                    array_push($piecesBadData, $this->getBadPiecesData($piece));
                                }
                            }
                        } else if ($piece->liberacion == 1) {
                            array_push($piecesArray["good"], $piece);
                        } else {
                            array_push($piecesArray["bad"], $piece);
                            if ($piece->error != "Ninguno") {
                                array_push($piecesBadData, $this->getBadPiecesData($piece));
                            } else {
                                array_push($piecesBadData, $this->getBadPiecesData($piece, "Rechazada"));
                            }
                        }
                    }
                }
            }
        }

        if (isset($pares)) {
            // Contar las piezas en base a si son juegos completos o mitades
            $goodCount = 0;
            foreach ($piecesArray["good"] as $p) {
                if (substr($p->n_pieza, -1) == "J") {
                    $goodCount += 1;
                } else {
                    $goodCount += 0.5;
                }
            }

            $badCount = 0;
            foreach ($piecesArray["bad"] as $p) {
                if (substr($p->n_pieza, -1) == "J") {
                    $badCount += 1;
                } else {
                    $badCount += 0.5;
                }
            }

            $piecesArray["good"] = $goodCount;
            $piecesArray["bad"] = $badCount;
            $piecesArray["total"] = $goodCount + $badCount;
        } else {
            $piecesArray["good"] = 0;
            $piecesArray["bad"] = 0;
            $piecesArray["total"] = 0;
        }
        return $piecesArray;
    }

        /**
     * @param mixed $piece
     * @param mixed $rechazada
     * @param mixed $operation
     */
    public function getBadPiecesData($piece, $rechazada = null, $operation = "- - - ")
    {
        $array = array();
        $operador = User::query()->where('matricula', $piece->id_operador)->first();
        $array["piece"] = $piece->n_pieza;
        preg_match('/^\d+/', $piece->n_pieza, $n_juego);
        $array["setNumber"] = isset($n_juego[0]) ? $n_juego[0] . "J" : $piece->n_pieza;
        $array["operator"] = $operador ? ($operador->nombre . " " . $operador->a_paterno . " " . $operador->a_materno) : "Desconocido";
        $array["process"] = $piece->proceso;
        $array["operation"] = $operation;
        $array["error"] = $rechazada ? $rechazada : $piece->error;
        return $array;
    }
}
