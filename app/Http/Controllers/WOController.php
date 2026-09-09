<?php

namespace App\Http\Controllers;

use App\Http\Requests\OTRequest;
use App\Models\Clase;
use App\Models\Fecha_proceso;
use App\Models\Metas;
use App\Models\Moldura;
use App\Models\Orden_trabajo;
use App\Models\Pieza;
use App\Models\Procesos;
use App\Models\SystemLog;
use App\Models\User;
use App\Http\Controllers\PtaResultsController;
use Barryvdh\DomPDF\Facade\Pdf as FacadePdf;
use DateTime;
use Illuminate\Http\Request;
use App\Models\FundicionHistory;
use App\Models\LiberacionModeloFundicion;
use App\Models\ScarModelo;
use App\Models\PreOrdenFundicion;
use App\Models\RemisionOt;
use App\Models\ParcialidadOt;
use App\Models\TratamientoTermico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\DibujosFundicionPdfController;

/**
 * @noinspection PhpUndefinedFieldInspection
 * @noinspection PhpParamsInspection
 * @noinspection PhpMissingParamTypeInspection
 */
class WOController extends Controller
{
    /** @var \App\Http\Controllers\ClassController */
    protected $classController;
    /** @var \App\Http\Controllers\ProcessesController */
    protected $processesController;
    /** @var \App\Http\Controllers\PzasLiberadasController */
    protected $releasedPiecesController;

    public function __construct()
    {
        $this->middleware('auth');
        $this->classController = new ClassController();
        $this->processesController = new ProcessesController();
        $this->releasedPiecesController = new PzasLiberadasController();
    }

    //Mostrar la vista para seleccionar o crear una Orden de Trabajo
    public function manage()
    {
        // Obtener todas las molduras (incluyendo las nuevas), a excepción de aquellas cuyas clases tienen finalizada = 2
        $moldings = Moldura::whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('clases')
                ->join('orden_trabajo', 'clases.id_ot', '=', 'orden_trabajo.id')
                ->whereColumn('orden_trabajo.id_moldura', '=', 'molduras.id')
                ->where('clases.finalizada', '=', 2, 'and');
        }, 'and')->get();
        $workOrdersAll = Orden_trabajo::query()->with(['clases', 'moldura'])->get();
        $workOrders = null;

        $isAlmacen = auth()->user()->perfil == 5 || request('almacen_only') == 1;

        if ($workOrdersAll->isNotEmpty()) {
            $workOrders = [];
            $counter = 0;
            foreach ($workOrdersAll as $workOrder) {
                $clases = $workOrder->clases;
                if ($isAlmacen) {
                    if ($clases->count() == 0)
                        continue;
                } else {
                    $clases = $clases->where('finalizada', '=', 0, 'and');
                    if ($clases->count() == 0)
                        continue;
                }
                // Moldura ya cargada con eager loading (0 queries)
                $workOrders[$counter]['workOrder'] = $workOrder->id;
                $workOrders[$counter]['molding'] = $workOrder->moldura ? $workOrder->moldura->nombre : '?';
                $counter++;
            }
        }
        return view('wo_views.manage_wo', compact('moldings', 'workOrders'));
    }

    /**
     * @param OTRequest $request
     */
    public function store(OTRequest $request) //Funcion para registrar una OT.
    {
        if (isset($request->workOrderAdded)) {
            //Creacion de la orden de trabajo registrada
            $newWorkOrder = new Orden_trabajo();
            $newWorkOrder->id = $request->workOrderAdded;
            $newWorkOrder->id_moldura = $request->input('moldingSelected');
            $newWorkOrder->save();

            SystemLog::create([
                'user_matricula' => auth()->user()->matricula,
                'action' => 'Cargo de OT',
                'details' => "El administrador registró la OT {$request->input('workOrderAdded')} con id_moldura {$request->input('moldingSelected')}.",
                'ot' => $request->input('workOrderAdded'),
                'id_ot' => $request->input('workOrderAdded'),
            ]);
        }
        //Busqueda de la orden de trabajo ingresada o creada
        $workOrder = Orden_trabajo::query()->find(isset($request->workOrderAdded) ? $request->input('workOrderAdded') : $request->input('workOrderSelected'), ['*']);

        $redirectParams = ['workOrder' => $workOrder];
        // ÚNICAMENTE el perfil 5 (Almacén) debe usar el modo almacén
        // Admin (1) y Master (3) siempre necesitan la vista completa para editar todos los procesos
        if (auth()->user()->perfil == 5 || $request->filled('almacen_only')) {
            $redirectParams['almacen_only'] = 1;
        }

        return redirect()->route('showWO', $redirectParams);
    }

    /**
     * Vista exclusiva para el perfil Master para crear una Orden de Trabajo con datos generales.
     */
    public function createMasterWO()
    {
        $moldings = Moldura::all();
        $workOrdersAll = Orden_trabajo::with(['clases', 'moldura'])->orderByDesc('created_at')->get();
        return view('wo_views.create_master_wo', compact('moldings', 'workOrdersAll'));
    }

    /**
     * Registrar la Orden de Trabajo creada por el perfil Master.
     */
    public function storeMasterWO(Request $request)
    {
        $request->validate([
            'workOrder' => 'required|digits_between:1,5|unique:orden_trabajo,id',
            'moldingSelected' => 'required|exists:molduras,id',
            'fecha_compra' => 'required|date',
            'orden_compra' => 'required|string|max:25|regex:/^[A-Za-z0-9-]+$/',
            'cliente' => 'required|string',
            'proveedor_material' => 'nullable|string',
            'semana_entrega_cliente' => 'nullable|string',
            'fecha_entrega_cliente' => 'required|date',
        ], [
            'workOrder.unique' => 'La Orden de Trabajo ingresada ya existe.',
            'workOrder.required' => 'El número de Orden de Trabajo es obligatorio.',
            'workOrder.digits_between' => 'La Orden de Trabajo debe ser numérica y tener un máximo de 5 dígitos.',
            'moldingSelected.required' => 'Debe seleccionar una moldura.',
            'fecha_compra.required' => 'La Fecha de Compra es obligatoria.',
            'orden_compra.required' => 'La Orden de Compra es obligatoria.',
            'orden_compra.max' => 'La Orden de Compra no puede exceder los 25 caracteres.',
            'orden_compra.regex' => 'La Orden de Compra solo admite números, letras y guiones, sin espacios.',
            'cliente.required' => 'El nombre del cliente es obligatorio.',
            'fecha_entrega_cliente.required' => 'La Fecha Entrega Comprometida con Cliente es obligatoria.',
        ]);

        $molding = Moldura::find($request->input('moldingSelected'));

        $fixDate = function ($dateStr) {
            if (!$dateStr)
                return null;
            if (preg_match('/(\d{4}-\d{2}-\d{2})$/', $dateStr, $matches)) {
                return $matches[1];
            }
            try {
                return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return substr($dateStr, -10);
            }
        };

        $fechaCompra = $fixDate($request->input('fecha_compra'));
        $fechaEntrega = $fixDate($request->input('fecha_entrega_cliente'));

        $ot = new Orden_trabajo();
        $ot->id = trim($request->input('workOrder'));
        $ot->id_moldura = $request->input('moldingSelected');
        $ot->fecha_compra = $fechaCompra;
        $ot->orden_compra = strtoupper(str_replace(' ', '', trim($request->input('orden_compra'))));
        $ot->cliente = $request->input('cliente');
        $ot->nombre_producto = $molding ? $molding->nombre : $request->input('nombre_producto');
        $ot->cantidad = 0;
        $ot->proveedor_material = $request->input('proveedor_material');
        if ($fechaEntrega) {
            try {
                $ot->semana_entrega_cliente = (string) (int) \Carbon\Carbon::parse($fechaEntrega)->format('W');
            } catch (\Exception $e) {
                $ot->semana_entrega_cliente = $request->input('semana_entrega_cliente');
            }
        } else {
            $ot->semana_entrega_cliente = $request->input('semana_entrega_cliente');
        }
        $ot->fecha_entrega_cliente = $fechaEntrega;
        $ot->save();

        SystemLog::create([
            'user_matricula' => auth()->user()->matricula,
            'action' => 'Alta de OT Master',
            'details' => "El Master registró la OT {$ot->id} para el cliente {$ot->cliente} del producto {$ot->nombre_producto}.",
            'ot' => $ot->id,
            'id_ot' => $ot->id,
        ]);

        return redirect()->route('showWO', ['workOrder' => $ot->id, 'from_master' => 1])->with('success', "¡Orden de Trabajo {$ot->id} creada exitosamente! Ahora puede dar de alta sus clases.");
    }

    /**
     * Modificar datos generales y/o cantidades de clases de una OT por el perfil Master.
     */
    public function updateMasterWO(Request $request)
    {
        $request->validate([
            'workOrderSelect' => 'required|exists:orden_trabajo,id',
            'fecha_compra' => 'required|date',
            'orden_compra' => 'required|string|max:25|regex:/^[A-Za-z0-9-]+$/',
            'cliente' => 'required|string',
            'proveedor_material' => 'nullable|string',
            'semana_entrega_cliente' => 'nullable|string',
            'fecha_entrega_cliente' => 'required|date',
        ], [
            'workOrderSelect.required' => 'Debe seleccionar una Orden de Trabajo a modificar.',
            'fecha_compra.required' => 'La Fecha de Compra es obligatoria.',
            'orden_compra.required' => 'La Orden de Compra es obligatoria.',
            'orden_compra.max' => 'La Orden de Compra no puede exceder los 25 caracteres.',
            'orden_compra.regex' => 'La Orden de Compra solo admite números, letras y guiones, sin espacios.',
            'cliente.required' => 'El nombre del cliente es obligatorio.',
            'fecha_entrega_cliente.required' => 'La F. Comprometida con el Cliente es obligatoria.',
        ]);

        $fixDate = function ($dateStr) {
            if (!$dateStr)
                return null;
            if (preg_match('/(\d{4}-\d{2}-\d{2})$/', $dateStr, $matches)) {
                return $matches[1];
            }
            try {
                return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return substr($dateStr, -10);
            }
        };

        $fechaCompra = $fixDate($request->input('fecha_compra'));
        $fechaEntrega = $fixDate($request->input('fecha_entrega_cliente'));

        $ot = Orden_trabajo::findOrFail($request->input('workOrderSelect'));
        $ot->fecha_compra = $fechaCompra;
        $ot->orden_compra = strtoupper(str_replace(' ', '', trim($request->input('orden_compra'))));
        $ot->cliente = $request->input('cliente');
        if ($request->filled('proveedor_material')) {
            $ot->proveedor_material = $request->input('proveedor_material');
        }
        if ($fechaEntrega) {
            try {
                $ot->semana_entrega_cliente = (string) (int) \Carbon\Carbon::parse($fechaEntrega)->format('W');
            } catch (\Exception $e) {
                if ($request->filled('semana_entrega_cliente')) {
                    $ot->semana_entrega_cliente = $request->input('semana_entrega_cliente');
                }
            }
        }
        $ot->fecha_entrega_cliente = $fechaEntrega;
        $ot->save();

        $hasUpdates = $ot->wasChanged();
        $addedClassesCount = 0;
        $deletedClassesCount = 0;
        $failedDeletes = [];

        // Eliminar clases
        if ($request->has('deleted_classes') && is_array($request->input('deleted_classes'))) {
            foreach ($request->input('deleted_classes') as $delClassId) {
                $classToDel = Clase::find($delClassId);
                if ($classToDel && $classToDel->id_ot == $ot->id) {
                    $hasPieces = Pieza::where('id_clase', $classToDel->id)->exists();
                    $goals = Metas::where('id_clase', $classToDel->id)->exists();

                    if ($hasPieces || $goals) {
                        $failedDeletes[] = $classToDel->nombre;
                    } else {
                        $process = Procesos::where('id_clase', $classToDel->id)->first();
                        if ($process) {
                            $process->delete();
                            Fecha_proceso::where('clase', $classToDel->id)->delete();
                        }
                        $classToDel->delete();
                        $deletedClassesCount++;
                        $hasUpdates = true;
                    }
                }
            }
        }

        if ($request->has('class_orders') && is_array($request->input('class_orders'))) {
            $materials = $request->input('class_materials', []);
            $proveedores = $request->input('class_proveedores', []);
            foreach ($request->input('class_orders') as $classId => $qty) {
                $clase = Clase::find($classId);
                if ($clase && $clase->id_ot == $ot->id) {
                    $newQty = max(0, (int) $qty);
                    $newMat = isset($materials[$classId]) && trim($materials[$classId]) !== '' ? trim($materials[$classId]) : null;
                    $newProv = isset($proveedores[$classId]) && trim($proveedores[$classId]) !== '' ? trim($proveedores[$classId]) : null;
                    $classChanged = false;

                    if ($clase->pedido != $newQty || $clase->piezas != $newQty) {
                        $clase->pedido = $newQty;
                        $clase->piezas = $newQty;
                        $classChanged = true;
                    }

                    if ($clase->material != $newMat) {
                        $clase->material = $newMat;
                        $classChanged = true;
                    }

                    if ($clase->proveedor != $newProv) {
                        $clase->proveedor = $newProv;
                        $classChanged = true;
                    }

                    if ($classChanged) {
                        $clase->save();
                        $hasUpdates = true;
                    }
                }
            }
        }

        if ($request->has('new_classes') && is_array($request->input('new_classes'))) {
            $classController = new \App\Http\Controllers\ClassController();
            foreach ($request->input('new_classes') as $newClass) {
                $nombre = $newClass['nombre'] ?? null;
                $cantidad = (int) ($newClass['cantidad'] ?? 0);
                $material = isset($newClass['material']) && trim($newClass['material']) !== '' ? trim($newClass['material']) : null;
                $proveedor = isset($newClass['proveedor']) && trim($newClass['proveedor']) !== '' ? trim($newClass['proveedor']) : null;

                if ($nombre && $cantidad > 0) {
                    // Check if class already exists
                    $exists = Clase::where('id_ot', $ot->id)->where('nombre', $nombre)->exists();
                    if (!$exists) {
                        $class = new Clase();
                        $class->id_ot = $ot->id;
                        $class->nombre = $nombre;
                        $class->pedido = $cantidad;
                        $class->piezas = $cantidad;
                        $class->tamanio = 'Chico';
                        $class->material = $material;
                        $class->proveedor = $proveedor;
                        $class->save();

                        $controllerProductionTime = new \App\Http\Controllers\TiemposProduccionController();
                        $controllerProductionTime->setProductionTimes($class);

                        $process = new Procesos();
                        $classController->storeProcess($class, null, null, $process);

                        $addedClassesCount++;
                        $hasUpdates = true;
                    }
                }
            }
        }

        SystemLog::create([
            'user_matricula' => auth()->user()->matricula,
            'action' => 'Modificación de OT Master',
            'details' => "El Master modificó los datos generales, composiciones y/o cantidades de la OT {$ot->id}.",
            'ot' => $ot->id,
            'id_ot' => $ot->id,
        ]);

        $successMsg = "¡La Orden de Trabajo {$ot->id} fue procesada exitosamente!";
        $details = [];
        if ($hasUpdates)
            $details[] = "Se actualizaron los datos generales/composiciones/cantidades.";
        if ($addedClassesCount > 0)
            $details[] = "Se agregaron $addedClassesCount nuevas clases.";
        if ($deletedClassesCount > 0)
            $details[] = "Se eliminaron $deletedClassesCount clases.";

        $finalMsg = $successMsg . " " . implode(" ", $details);

        $redirect = redirect()->route('createMasterWO', ['mode' => 'modify', 'ot_id' => $ot->id])->with('success', $finalMsg);

        if (count($failedDeletes) > 0) {
            $redirect->with('error', "No se pudieron eliminar las siguientes clases porque ya tienen piezas o metas registradas: " . implode(", ", $failedDeletes));
        }

        return $redirect;
    }

    /**
     * Vista de Prioridades (Dashboard Master)
     */
    public function prioritiesView(Request $request)
    {
        $startWeek = $request->input('start_week');
        $endWeek = $request->input('end_week');
        $otId = $request->input('ot_id');

        $query = Orden_trabajo::with(['moldura', 'clases']);

        if (!empty($otId)) {
            $query->where('id', $otId);
        }

        $workOrders = $query
            ->orderByRaw('CASE WHEN prioridad IS NULL OR prioridad = 0 THEN 999999 ELSE prioridad END ASC')
            ->orderByRaw('CAST(semana_entrega_cliente AS UNSIGNED) ASC')
            ->orderBy('id', 'ASC')
            ->get();

        // Agrupar por semana y aplicar filtrado manual de rango (ya que la DB podría tener texto mezclado)
        $groupedWOs = [];
        foreach ($workOrders as $wo) {
            $semanaRaw = preg_replace('/[^0-9]/', '', $wo->semana_entrega_cliente ?? '');

            if (empty($semanaRaw) && (!empty($startWeek) || !empty($endWeek))) {
                continue;
            }

            if (!empty($startWeek) && !empty($semanaRaw)) {
                if ((int) $semanaRaw < (int) $startWeek)
                    continue;
            }
            if (!empty($endWeek) && !empty($semanaRaw)) {
                if ((int) $semanaRaw > (int) $endWeek)
                    continue;
            }

            $semana = empty($semanaRaw) ? 'Sin Semana' : $semanaRaw;
            $groupedWOs[$semana][] = $wo;
        }

        // Ordenar estrictamente las semanas de menor a mayor (ascendente)
        uksort($groupedWOs, function ($a, $b) {
            if ($a === 'Sin Semana') return 1;
            if ($b === 'Sin Semana') return -1;
            return (int)$a - (int)$b;
        });

        // Obtener lista de OTs para el select con sus molduras
        $allOts = Orden_trabajo::with('moldura')->orderBy('id', 'desc')->get();

        // Obtener lista de semanas únicas para los selects
        $allWeeksRaw = Orden_trabajo::select('semana_entrega_cliente')
            ->whereNotNull('semana_entrega_cliente')
            ->where('semana_entrega_cliente', '!=', '')
            ->distinct()
            ->get()
            ->map(function ($ot) {
                return (int) preg_replace('/[^0-9]/', '', $ot->semana_entrega_cliente);
            })
            ->filter(function ($week) {
                return $week > 0;
            })
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return view('wo_views.priorities', compact('groupedWOs', 'allOts', 'allWeeksRaw', 'startWeek', 'endWeek', 'otId'));
    }

    /**
     * Descarga de Prioridades en PDF (Dashboard Master)
     */
    public function downloadPrioritiesPdf(Request $request)
    {
        $startWeek = $request->input('start_week');
        $endWeek = $request->input('end_week');
        $otId = $request->input('ot_id');

        $query = Orden_trabajo::with(['moldura', 'clases']);

        if (!empty($otId)) {
            $query->where('id', $otId);
        }

        $workOrders = $query
            ->orderByRaw('CASE WHEN prioridad IS NULL OR prioridad = 0 THEN 999999 ELSE prioridad END ASC')
            ->orderByRaw('CAST(semana_entrega_cliente AS UNSIGNED) ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $groupedWOs = [];
        foreach ($workOrders as $wo) {
            $semanaRaw = preg_replace('/[^0-9]/', '', $wo->semana_entrega_cliente ?? '');

            if (empty($semanaRaw) && (!empty($startWeek) || !empty($endWeek))) {
                continue;
            }

            if (!empty($startWeek) && !empty($semanaRaw)) {
                if ((int) $semanaRaw < (int) $startWeek)
                    continue;
            }
            if (!empty($endWeek) && !empty($semanaRaw)) {
                if ((int) $semanaRaw > (int) $endWeek)
                    continue;
            }

            $semana = empty($semanaRaw) ? 'Sin Semana' : $semanaRaw;
            $groupedWOs[$semana][] = $wo;
        }

        // Ordenar estrictamente las semanas de menor a mayor (ascendente)
        uksort($groupedWOs, function ($a, $b) {
            if ($a === 'Sin Semana') return 1;
            if ($b === 'Sin Semana') return -1;
            return (int)$a - (int)$b;
        });

        $pdf = FacadePdf::loadView('wo_views.priorities_pdf_export', compact('groupedWOs', 'startWeek', 'endWeek'));

        $pdf->getDomPDF()->set_option('isPhpEnabled', true);
        $pdf->setPaper('letter', 'landscape');

        return $pdf->download('Prioridades_GIS.pdf');
    }

    /**
     * Autoguardado AJAX para campos vacíos en la vista de Prioridades
     */
    public function autosavePriority(Request $request)
    {
        $request->validate([
            'ot_id' => 'required|exists:orden_trabajo,id',
            'field' => 'required|string',
            'value' => 'nullable|string'
        ]);

        $allowedFields = [
            'fecha_real',
            'forma_grabados',
            'entrega_tecamac',
            'observaciones_prioridad',
            'fecha_entrega_fundicion',
            'semana_entrega_cliente'
        ];

        if (!in_array($request->field, $allowedFields)) {
            return response()->json(['success' => false, 'message' => 'Campo no permitido.'], 403);
        }

        $value = $request->value;

        if ($request->field === 'fecha_entrega_fundicion' && !empty($value)) {
            try {
                // Replace slashes with dashes to help Carbon parse DD/MM/YYYY better
                $cleanValue = str_replace('/', '-', $value);
                $value = \Carbon\Carbon::parse($cleanValue)->format('Y-m-d');
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Formato de fecha inválido.'], 400);
            }
        }

        $wo = Orden_trabajo::find($request->ot_id);
        if ($wo) {
            $wo->{$request->field} = $value;
            $wo->save();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'OT no encontrada.'], 404);
    }

    /**
     * @param mixed $workOrder
     */
    public function show($workOrder)
    {
        $workOrder = Orden_trabajo::query()->find($workOrder, ['*']);
        $molding = Moldura::query()->find($workOrder->id_moldura, ['*']);

        //Se obtienen las clases de la Orden de trabajo
        $classes = $this->classController->getClasses($workOrder);
        $classes = $classes->count() == 0 ? null : $classes;

        // Vista especial para Almacén (perfil 5 o si se solicita almacen_only=1).
        if (auth()->user()->perfil == 5 || request()->filled('almacen_only')) {
            // Cargar remisiones y parcialidades agrupadas por id_clase
            $claseIds = $classes ? $classes->pluck('id')->toArray() : [];
            $remisiones = RemisionOt::with('usuario')->whereIn('id_clase', $claseIds, 'and', false)->where('visible', '=', 1, 'and')->orderByDesc('created_at')->get()->groupBy('id_clase');
            $parcialidades = ParcialidadOt::with(['usuario', 'remision'])->whereIn('id_clase', $claseIds, 'and', false)->orderByDesc('fecha_recepcion')->get()->groupBy('id_clase');
            $tratamientos = TratamientoTermico::whereIn('id_clase', $claseIds, 'and', false)->orderByDesc('created_at')->get()->groupBy('id_clase');

            return view('wo_views.show_wo_almacen', compact('workOrder', 'molding', 'classes', 'remisiones', 'parcialidades', 'tratamientos'));
        }

        //Se obtienen las maquinas de los procesos guardados
        $processes = $this->classController->getClassProcesses($classes);
        return view('wo_views.show_wo', compact('workOrder', 'molding', 'classes', 'processes'));
    }

    public function destroy(string $idWOrder)
    {
        $hasPieces = Pieza::query()->where('id_ot', '=', $idWOrder, 'and')->exists(); //Busco si hay piezas de la OT
        $hasGoals = Metas::query()->where('id_ot', '=', $idWOrder, 'and')->exists();
        if (!$hasPieces && !$hasGoals) { //Si la OT no tiene piezas ni metas asociadas entonces
            $classes = Clase::query()->where('id_ot', '=', $idWOrder)->get(); //Busco todas las clases que pertenecen a la OT
            foreach ($classes as $class) { //Recorro las clases de la OT
                $this->classController->destroy($class->id, $idWOrder); //Elimino las clases
            }
            $workOrder = Orden_trabajo::query()->find($idWOrder, ['*']);
            if ($workOrder) {
                // Desactivar en FundicionHistory (bandeja de Almacén/Calidad) para mantener históricos
                $isOriginal = !preg_match('/_R\d+$/i', $idWOrder);
                if ($isOriginal) {
                    $histories = FundicionHistory::query()
                        ->where('ot', 'LIKE', "OT {$idWOrder}%", 'and')
                        ->where('ot', 'NOT LIKE', '%_del%', 'and')
                        ->where('status', '!=', 'inactiva')
                        ->get();
                    foreach ($histories as $history) {
                        $pattern = '/^OT\s*' . preg_quote($idWOrder, '/') . '(?:\b|_R\d+|$)/i';
                        if (preg_match($pattern, $history->ot)) {
                            // Sincronizar y copiar dibujos a la carpeta protegida de Almacén antes de inactivar
                            DibujosFundicionPdfController::copyToAlmacen($history->ot);
                            // Archivar físicamente e inactivar registros en BD
                            DibujosFundicionPdfController::deactivateOtAndArchive($history->ot);
                        }
                    }
                } else {
                    $baseId = preg_replace('/_(?:(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias)(?:_(?:candado\s+obturador|cabeza\s+de\s+soplo|obturador|bombillo|embudo|corona|plato|molde|fondo|pistones|guías|guias))*_)?R\d+$/iu', '', $idWOrder);
                    $histories = FundicionHistory::query()
                        ->where('ot', 'LIKE', "OT {$baseId}%", 'and')
                        ->where('ot', 'NOT LIKE', '%_del%', 'and')
                        ->where('status', '!=', 'inactiva')
                        ->get();
                    foreach ($histories as $history) {
                        preg_match('/_R\d+$/i', $idWOrder, $suffixMatch);
                        $suffix = $suffixMatch[0] ?? '';
                        if (!empty($suffix) && str_contains($history->ot, $suffix)) {
                            // Sincronizar y copiar dibujos a la carpeta protegida de Almacén antes de inactivar
                            DibujosFundicionPdfController::copyToAlmacen($history->ot);
                            // Archivar físicamente e inactivar registros en BD
                            DibujosFundicionPdfController::deactivateOtAndArchive($history->ot);
                        }
                    }
                }

                $workOrder->delete(); //Eliminar OT
            }
            return redirect()->route('manageWO')->with('success', '¡Orden de trabajo eliminada con éxito!'); //Redirecciono a la vista de registro de la OT
        }
        return redirect()->route('showWO', ['workOrder' => $idWOrder])->with('error', '¡La orden de trabajo no se puede eliminar porque tiene piezas o metas asociadas!');
    }
    public function generatePDF(string $idWOrder)
    {
        $workOrder = Orden_trabajo::query()->find($idWOrder, ['*']);
        if (!$workOrder) {
            return redirect()->back()->with('error', 'Orden de trabajo no encontrada');
        }
        $molding = Moldura::query()->find($workOrder->id_moldura, ['*']);

        $classes = $this->classController->getClasses($workOrder);
        $classes = ($classes && $classes->count() > 0) ? $classes : null;
        $processes = null;
        if ($classes) {
            $processesFounded = $this->classController->getClassProcesses($classes);
            if ($processesFounded != null) {
                $processes = [];
                // Obtener el nombre del campo del proceso y su número de máquinas
                foreach ($processesFounded as $idClass => $process) {
                    $processesList = [];
                    foreach ($process as $processName => $numMachines) {
                        $label = $this->nombreProceso($processName);
                        if ($numMachines && intval($numMachines) > 1) {
                            $processesList[] = "{$label} ({$numMachines} máq.)";
                        } else {
                            $processesList[] = $label;
                        }
                    }
                    $processes[$idClass] = implode(', ', $processesList);
                }
            }
        }
        $pdf = FacadePdf::loadView('wo_views.pdf_wo', compact('workOrder', 'molding', 'classes', 'processes'));
        $pdf->setPaper('letter', 'landscape');
        return $pdf->download('Orden_de_trabajo_' . $workOrder->id . '.pdf');
    }

    public function show_panelWO()
    {
        return view('wo_views.progressPanel_wo');
    }

    /**
     * @param mixed $moldingId
     */
    public function getMolding($moldingId)
    {
        $molding = Moldura::query()->find($moldingId, ['*']);
        return $molding ? $molding->nombre : null;
    }

    /**
     * @param array $array
     * @param mixed $class
     */
    public function insertClassesData(&$array, $class)
    {
        $array[$class->nombre] = array();
        $array[$class->nombre]["id"] = $class->id;
        $array[$class->nombre]["pieces"] = $class->piezas;
        $array[$class->nombre]["order"] = $class->pedido;
        $array[$class->nombre]["startDate"] = $this->getStringDate($class->fecha_inicio, $class->hora_inicio);
        $array[$class->nombre]["endDate"] = $class->fecha_termino ? $this->getStringDate($class->fecha_termino, $class->hora_termino) : "-";
        $array[$class->nombre]["entregadas"] = ParcialidadOt::query()->where('id_clase', '=', $class->id, 'and')->sum('cantidad');
        $array[$class->nombre]["tratadas"] = TratamientoTermico::query()->where('id_clase', '=', $class->id, 'and')->sum('cantidad');
        $array[$class->nombre]["processes"] = $this->insertProcessesData($class);

        // Flag para indicar si la clase lleva el proceso Soldadura PTA activo.
        // Se usa en el frontend para decidir si montar la PTACardComponent.
        $hasPTA = false;
        if ($class->procesos && $class->procesos->soldaduraPTA != 0) {
            $hasPTA = true;
        }
        $array[$class->nombre]["hasPTA"] = $hasPTA;
    }

    /**
     * @param mixed $class
     */
    public function insertProcessesData($class)
    {
        $processes = array();
        $processesFounded = $class->procesos;

        //Establecer el orden de los procesos
        $processesInOrder = array();
        $baseType = $class->getBaseType();
        switch ($baseType) {
            case "Bombillo":
            case "Molde":
                $processesInOrder = ["cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoBombillo", "acabadoMolde", "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado"];
                break;
            case 'Corona':
                $processesInOrder = ["cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado"];
                break;
            case "Obturador":
            case "Fondo":
                $processesInOrder = ["operacionEquipo", "soldadura", "soldaduraPTA"];
                break;
            case "Candado Obturador":
                $processesInOrder = ["operacionEquipo"];
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
        //Ordenar array
        $soldaduraBand = false;
        if ($processesFounded) {
            // Fallback: if all relevant processes are 0 (legacy data saved with wrong JS keys),
            // treat all processes in the expected list as active.
            $anyActive = false;
            foreach ($processesInOrder as $p) {
                if ($processesFounded[$p] != 0) {
                    $anyActive = true;
                    break;
                }
            }

            foreach ($processesInOrder as $process) {
                $field = $process == "operacionEquipo" ? ["1 operacion", "2 operacion"] : [$process];
                foreach ($field as $processField) {
                    //Asignar el nombre del proceso
                    if (count($field) > 1) {
                        $processName = "Operacion Equipo_" . $processField;
                    } else {
                        if (str_contains($processField, "soldadura")) {
                            $processName = "Soldadura y Soldadura PTA";
                        } else {
                            $processName = $this->nombreProceso($processField);
                        }
                    }

                    $piecesBadData = array();
                    $pieces = $this->getPieces($class, $processName, $piecesBadData);

                    // Decidir si está activo:
                    // 1. Si hay al menos un proceso configurado (>0 en la BD), usamos la configuración de la BD.
                    // 2. Si no hay configuración (todo en 0), solo lo mostramos si tiene piezas registradas (total > 0).
                    $dbActive = $processesFounded->$process != 0;
                    $isActive = $anyActive ? $dbActive : ($pieces['total'] > 0);

                    if ($isActive) {
                        if (str_contains($process, "soldadura") && $soldaduraBand) { // Verificar si soldadura o soldadura PTA ya fueron insertadas
                            continue;
                        }
                        $soldaduraBand = str_contains($process, "soldadura") ? true : false;

                        $processes[$processName] = array();
                        $processes[$processName]['pieces'] = $pieces;
                        $processes[$processName]['piecesBadData'] = $piecesBadData; //Informacion de las piezas malas
                        $processes[$processName]['endDate'] = $this->getDateEndFromProcess($field, $class); //Fecha de termino del proceso

                        // Check if cotas are uploaded for this process
                        $searchProcess = explode('_', $processName)[0];
                        if (in_array($processName, ['Soldadura y Soldadura PTA', 'Asentado', 'Rectificado'])) {
                            $searchProcess = 'none';
                        }
                        $hasCotas = false;
                        $requiresCotas = ($searchProcess !== 'none');
                        if ($requiresCotas) {
                            $hasCotas = SystemLog::query()->where('id_ot', '=', $class->id_ot, 'and')
                                ->where('clase', '=', $class->nombre, 'and')
                                ->where('action', '=', 'Cargo/Modificación Cotas Nominales', 'and')
                                ->where('proceso', '=', $searchProcess, 'and')
                                ->exists();
                        }
                        $processes[$processName]['requiresCotas'] = $requiresCotas;
                        $processes[$processName]['hasCotas'] = $hasCotas;
                    }
                }
            }
        }
        return $processes;

    }
    /**
     * @param mixed $process
     * @param mixed $class
     */
    public function getDateEndFromProcess($process, $class)
    {
        if ($class instanceof Clase) {
            $processesArray = (array) $process;
            $dateEnd = $class->fechasProcesos
                ->first(function ($fp) use ($processesArray) {
                    return in_array($fp->proceso, $processesArray);
                });
        } else {
            $dateEnd = Fecha_proceso::query()->where('clase', '=', $class, 'and')->where('proceso', '=', $process, 'and')->first();
        }
        if ($dateEnd) {
            $formattedDate = new DateTime($dateEnd->fecha_fin);
            $formattedDate = $formattedDate->format('d-m-Y');

            $formattedTime = new DateTime($dateEnd->fecha_fin);
            $formattedTime = $formattedTime->format('H:i:s');
            return $this->getStringDate($formattedDate, $formattedTime);
        }
    }

    /**
     * Construye el array de checklist del flujo de fundición para una OT activa.
     */
    private function buildFundicionChecklistForOt(string $otId, ?string $className = null): ?array
    {
        $histories = FundicionHistory::query()
            ->where(function ($q) use ($otId) {
                $q->where('ot', 'LIKE', "OT {$otId} - %")
                    ->orWhere('ot', 'LIKE', "OT {$otId}_R% - %")
                    ->orWhere('ot', '=', "OT {$otId}")
                    ->orWhere('ot', 'LIKE', "OT {$otId}_R%");
            })
            ->where('status', '!=', 'inactiva')
            ->orderBy('id', 'asc')
            ->get();

        if ($histories->isEmpty()) {
            return null;
        }

        $otKey = $histories->last()->ot;
        $history = $histories->last();

        if ($className) {
            foreach ($histories as $h) {
                $otKey = $h->ot;
                $history = $h;

                // Si la clase tiene un SCAR en esta iteración, significa que fue rechazada
                // y su flujo continúa en el siguiente Reproceso (_R).
                $hasScar = ScarModelo::query()
                    ->where('ot', $otKey)
                    ->where('tipo_modelo', $className)
                    ->exists();

                if (!$hasScar) {
                    // Si no tiene SCAR, su flujo de revisión se detuvo en este nivel (aprobada o pendiente).
                    break;
                }
            }
        }

        $libQ = LiberacionModeloFundicion::query()->where('ot', $otKey);
        if ($className) {
            $libQ->where('tipo_modelo', $className);
        }
        $liberacion = $libQ->orderByDesc('created_at')->first();

        $scarQ = ScarModelo::query()->where('ot', $otKey);
        if ($className) {
            $scarQ->where('tipo_modelo', $className);
        }
        $scar = $scarQ->orderByDesc('created_at')->first();

        $esReprocesoRegistro = (bool) preg_match('/_R\d+$/i', $otKey);
        $preOrdenReprocesoQ = PreOrdenFundicion::query()->orderByDesc('created_at');
        if ($esReprocesoRegistro) {
            $preOrdenReprocesoQ->where('ot', '=', $otKey);
        } else {
            $preOrdenReprocesoQ->where('ot', 'LIKE', "{$otKey}_R%");
        }

        $preOrdenList = $preOrdenReprocesoQ->get();
        $preOrdenReproceso = null;
        foreach ($preOrdenList as $po) {
            if (!$className) {
                $preOrdenReproceso = $po;
                break;
            }
            $filas = is_string($po->filas) ? json_decode($po->filas, true) : $po->filas;
            if (is_array($filas)) {
                foreach ($filas as $f) {
                    if (strcasecmp($f['clase'] ?? ($f['clase_nombre'] ?? ''), $className) === 0) {
                        $preOrdenReproceso = $po;
                        break 2;
                    }
                }
            }
        }

        $esReproceso = $liberacion && ($liberacion->decision === 'rechazar' || $liberacion->decision === 'rechazado' || $liberacion->decision === 'mixto');

        $isBadgeVisible = $esReprocesoRegistro || $esReproceso;
        $badgeText = 'Reproceso';
        if ($isBadgeVisible) {
            $iteration = 1;
            if ($esReprocesoRegistro && preg_match('/_R(\d+)$/i', $otKey, $matches)) {
                $iteration = (int) $matches[1];
                if ($esReproceso) {
                    $iteration++;
                }
            } else if ($esReproceso) {
                $iteration = 1;
            }
            $ordinales = [1 => '1er', 2 => '2do', 3 => '3er', 4 => '4to', 5 => '5to', 6 => '6to', 7 => '7mo', 8 => '8vo', 9 => '9no', 10 => '10mo'];
            $prefix = $ordinales[$iteration] ?? "{$iteration}vo";
            $badgeText = "{$prefix} Reproceso";
        }

        $pasos = [];

        // --- Banderas individuales ---
        $tieneArchivos = !empty($history->almacen_archivos) || $history->alert_sent_at !== null;
        $alertaAlmacen = $history->alert_sent_at !== null;
        $vistosAlmacen = $history->dibujos_vistos_almacen;

        $almacenProceso = $history->tiene_modelo || $history->pre_orden_sent;
        $preordenAutorizada = $history->pre_orden_email_sent;

        $alertaCalidad = $history->pre_orden_email_sent;
        $revisadosCalidad = $history->documentos_revisados_calidad;
        $tieneLiberacion = $liberacion !== null;
        $tieneScar = $esReproceso ? ($scar !== null) : true;
        $formatoCompletado = $tieneLiberacion && $tieneScar;

        $alertaAlmacen2 = ($history->calidad_revision_status !== null);
        $vistosAlmacen2 = $history->documentos_vistos_almacen_2;
        $firmadosCargados = $history->documentos_firmados_cargados;
        $castingPreordenExists = false;
        $castingList = PreOrdenFundicion::query()
            ->where('ot', '=', $otKey)
            ->where(function ($q) {
                $q->where('pdf_filename', 'LIKE', '%casting%')
                    ->orWhere('pdf_filename', 'LIKE', '%Casting%')
                    ->orWhere('pdf_filename', 'LIKE', '%F_ALM_PFC_%')
                    ->orWhere('pdf_filename', 'LIKE', '%PFC%');
            })
            ->get();

        foreach ($castingList as $po) {
            if (!$className) {
                $castingPreordenExists = true;
                break;
            }
            $filas = is_string($po->filas) ? json_decode($po->filas, true) : $po->filas;
            if (is_array($filas)) {
                foreach ($filas as $f) {
                    if (strcasecmp($f['clase'] ?? ($f['clase_nombre'] ?? ''), $className) === 0) {
                        $castingPreordenExists = true;
                        break 2;
                    }
                }
            }
        }
        $pasoFinalGenerado = $esReproceso ? ($preOrdenReproceso !== null) : $castingPreordenExists;

        // Determinar si la liberación de este ciclo fue aprobada o rechazada
        // (calculado ANTES de la cascada, con el valor real de $liberacion en BD)
        $decisionActual = $liberacion ? ($liberacion->decision ?? $liberacion->estado ?? null) : null;
        $cicloAprobado = in_array($decisionActual, ['aprobar', 'aprobado']);
        $cicloRechazado = in_array($decisionActual, ['rechazar', 'rechazado', 'mixto']);

        // --- AUTO-COMPLETADO ESCALONADO (de atrás hacia adelante) ---
        // Cada nivel sólo se auto-completa cuando hay evidencia REAL de actividad en niveles posteriores.
        // NOTA: almacenProceso (tiene_modelo=true) NO dispara solo la cascada de Fases 0/1
        //       porque puede setearse antes de que Admin haya enviado los dibujos.

        // Si hay paso final generado, Almacén debió cargar formatos firmados
        if ($pasoFinalGenerado) {
            $firmadosCargados = true;
        }

        // Si hay carga de formatos firmados, Almacén 2 debió recibir alerta y revisar
        if ($firmadosCargados || $pasoFinalGenerado) {
            $alertaAlmacen2 = true;
            $vistosAlmacen2 = true;
        }

        // Si hay actividad en Almacén 2 o posterior, Calidad debió completar formatos
        if ($alertaAlmacen2 || $firmadosCargados || $pasoFinalGenerado) {
            $tieneLiberacion = true;
            $tieneScar = true;
            $formatoCompletado = true;
        }

        // Si Calidad tiene liberación o hay actividad posterior, debió recibir la alerta
        if ($tieneLiberacion || $formatoCompletado || $alertaAlmacen2 || $firmadosCargados || $pasoFinalGenerado) {
            $alertaCalidad = true;
            $revisadosCalidad = true;
        }

        // Si Calidad ya fue alertada o hay actividad posterior, Fase 2 debió completarse
        if ($alertaCalidad || $revisadosCalidad || $tieneLiberacion || $alertaAlmacen2 || $firmadosCargados || $pasoFinalGenerado) {
            $almacenProceso = true;
            if (!$history->tiene_modelo) {
                $preordenAutorizada = true;
            }
        }

        // Fases 0 y 1 se auto-completan cuando hay evidencia de actividad real en Fase 2 o posteriores
        // (almacenProceso solo ya no es suficiente: esperamos alerta a Calidad, liberación, o más allá)
        if (
            $alertaCalidad || $tieneLiberacion || $alertaAlmacen2 || $firmadosCargados || $pasoFinalGenerado
            || $almacenProceso || $preordenAutorizada
        ) {
            $tieneArchivos = true;
            $alertaAlmacen = true;
            $vistosAlmacen = true;
        }

        // --- FASE 0: Administración (Programación) ---
        $estado0 = 'Incompleto';
        $label0 = 'Administración: Dibujos y Ayudas Visuales de Fundición Completadas';
        if (!$tieneArchivos) {
            $estado0 = 'En Espera';
            $label0 = 'Administración: Programación (Esperando Dibujos)';
        } elseif (!$alertaAlmacen) {
            $estado0 = 'En Espera';
            $label0 = 'Administración: Programación (Falta Alerta a Almacén)';
        } else {
            $estado0 = 'Completado';
        }

        $pasos['fase0'] = [
            'label' => $label0,
            'estado' => $estado0,
            'tooltip' => $label0,
            'subPasos' => [
                ['label' => 'Subida de Dibujos y Ayudas Visuales', 'estado' => $tieneArchivos ? 'Completado' : 'En Espera'],
                ['label' => 'Envío de Alerta a Almacén', 'estado' => $alertaAlmacen ? 'Completado' : 'En Espera'],
            ],
        ];

        // --- FASE 1: Almacén (Revisión de Dibujos) ---
        $estado1 = 'Incompleto';
        $label1 = 'Almacén: Revisión de Dibujos';
        if ($estado0 !== 'Completado') {
            $estado1 = 'Incompleto';
        } elseif (!$vistosAlmacen) {
            $estado1 = 'Revisando';
            $label1 = 'Almacén: Alerta Enviada (Esperando Revisión)';
        } else {
            $estado1 = 'Completado';
            $label1 = 'Almacén: Dibujos y Ayudas Visuales de Fundición Revisados';
        }

        $pasos['fase1'] = [
            'label' => $label1,
            'estado' => $estado1,
            'tooltip' => $label1,
            'subPasos' => [
                ['label' => 'Revisión de Dibujos por Almacén', 'estado' => $vistosAlmacen ? 'Completado' : 'Revisando'],
            ],
        ];


        // --- FASE 2: Almacén (Modelo) — DOS CAMINOS POSIBLES ---
        $estado2 = 'Incompleto';
        $label2 = 'Almacén: Gestión de Modelo';

        if ($estado1 !== 'Completado') {
            $estado2 = 'Incompleto';
        } elseif (!$almacenProceso) {
            $estado2 = 'En Espera';
            $label2 = 'Almacén: Esperando Evaluación de Modelo';
        } elseif ($history->tiene_modelo) {
            // CAMINO A: tiene modelo físico → sólo escaneo
            if (!$alertaCalidad) {
                $estado2 = 'Revisando';
                $label2 = 'Almacén: Escaneado de Modelo Existente (Falta Enviar a Calidad)';
            } else {
                $estado2 = 'Completado';
                $label2 = 'Almacén: Escaneado de Modelo Existente';
            }
        } else {
            // CAMINO B: no tiene modelo → preorden + escaneo firmado
            if (!$preordenAutorizada) {
                $estado2 = 'Revisando';
                $label2 = 'Almacén: Preorden Generada (Falta Subir Escaneo Firmado)';
            } else {
                $estado2 = 'Completado';
                $label2 = 'Almacén: Preorden de Modelo Autorizada';
            }
        }

        // Subpasos de Fase 2 según el camino
        if ($history->tiene_modelo) {
            // Camino A
            $subPasosFase2 = [
                ['label' => 'Evaluación de Modelo Físico', 'estado' => $almacenProceso ? 'Completado' : 'En Espera'],
                ['label' => 'Decisión: ¿Se tiene el modelo?', 'estado' => $almacenProceso ? 'Completado' : 'Incompleto', 'detalle' => $almacenProceso ? 'Se cuenta con el modelo físico' : ''],
                ['label' => 'Subida de Escaneo del Modelo Existente', 'estado' => $alertaCalidad ? 'Completado' : ($almacenProceso ? 'En Espera' : 'Incompleto')],
            ];
        } else {
            // Camino B
            $subPasosFase2 = [
                ['label' => 'Evaluación de Modelo Físico', 'estado' => $almacenProceso ? 'Completado' : 'En Espera'],
                ['label' => 'Decisión: ¿Se tiene el modelo?', 'estado' => $almacenProceso ? 'Completado' : 'Incompleto', 'detalle' => $almacenProceso ? 'No se tiene el modelo físico' : ''],
                ['label' => 'Generación de Preorden de Modelo', 'estado' => $almacenProceso ? ($preordenAutorizada ? 'Completado' : 'Revisando') : 'Incompleto'],
                ['label' => 'Subida de Preorden Firmada (Escaneo)', 'estado' => $preordenAutorizada ? 'Completado' : ($almacenProceso ? 'En Espera' : 'Incompleto')],
            ];
        }

        $pasos['fase2'] = [
            'label' => $label2,
            'estado' => $estado2,
            'tooltip' => $label2,
            'subPasos' => $subPasosFase2,
        ];


        // --- FASE 3: Calidad (Liberación de modelo) ---
        $labelFormatoFinal = $cicloRechazado ? 'Calidad: Formato de Rechazo (FDRM) y SCAR' : 'Calidad: Formato de Liberación (FDLM)';

        $estado3 = 'Incompleto';
        $label3 = 'Calidad: Liberación de Modelo';

        if ($estado2 !== 'Completado') {
            $estado3 = 'Incompleto';
        } elseif (!$alertaCalidad) {
            $estado3 = 'En Espera';
            $label3 = 'Calidad: Esperando Alerta de Almacén';
        } elseif (!$revisadosCalidad) {
            $estado3 = 'Revisando';
            $label3 = 'Calidad: Alerta Recibida (Esperando Evaluación)';
        } elseif (!$formatoCompletado) {
            $estado3 = 'Revisando';
            $label3 = 'Calidad: Documentos Revisados (Falta Generar Formato)';
        } else {
            $estado3 = 'Completado';
            $label3 = $labelFormatoFinal;
        }

        if ($alertaAlmacen2 || $firmadosCargados || $pasoFinalGenerado) {
            $estado3 = 'Completado';
            if (strpos($label3, 'Falta') !== false || $label3 === 'Calidad: Liberación de Modelo' || $label3 === 'Calidad: Esperando Alerta de Almacén' || $label3 === 'Calidad: Alerta Recibida (Esperando Evaluación)') {
                $label3 = $labelFormatoFinal;
            }
        }

        $detalleDecision = $tieneLiberacion ? ($cicloRechazado ? 'Modelo Rechazado' : 'Modelo Aprobado') : '';
        $labelGeneraFormato = $cicloRechazado ? 'Generación de FDRM (Rechazo) + SCAR' : 'Generación de FDLM (Liberación de Modelo)';

        $pasos['fase3'] = [
            'label' => $label3,
            'estado' => $estado3,
            'tooltip' => $label3,
            'subPasos' => [
                ['label' => 'Recepción de Alerta por Calidad', 'estado' => $alertaCalidad ? 'Completado' : 'En Espera'],
                ['label' => 'Evaluación del Modelo', 'estado' => $tieneLiberacion ? 'Completado' : ($alertaCalidad ? 'Revisando' : 'Incompleto'), 'detalle' => $detalleDecision],
                ['label' => $labelGeneraFormato, 'estado' => $formatoCompletado ? 'Completado' : ($tieneLiberacion ? 'Revisando' : 'Incompleto')],
            ],
        ];


        // --- FASE 4: Almacén (Casting si aprobado / Reproceso si rechazado) ---
        $esCaminoReproceso = $cicloRechazado; // El camino lo define la decisión de Calidad, no el ciclo estructural
        $labelPasoFinal = $esCaminoReproceso ? 'Almacén: Ciclo de Reproceso Iniciado' : 'Almacén: Enviado a Proveedor (Casting)';
        $labelStepFinal = $esCaminoReproceso ? 'Inicio de Reproceso de Modelo' : 'Generación de Preorden de Casting';
        $castingEnviado = $history->calidad_revision_status === 'casting_aprobado';

        $estado4 = 'Incompleto';
        $label4 = 'Almacén: Proceso de Casting';

        if ($estado3 !== 'Completado') {
            $estado4 = 'Incompleto';
        } elseif (!$alertaAlmacen2) {
            $estado4 = 'En Espera';
            $label4 = 'Almacén: Esperando Alerta de Calidad';
        } elseif (!$vistosAlmacen2) {
            $estado4 = 'Revisando';
            $label4 = 'Almacén: Alerta Recibida (Esperando Revisión)';
        } elseif (!$firmadosCargados) {
            $estado4 = 'En Espera';
            $label4 = 'Almacén: Falta Subir Formatos Firmados de Calidad';
        } elseif (!$pasoFinalGenerado) {
            $estado4 = 'En Espera';
            $label4 = $esCaminoReproceso ? 'Almacén: Iniciando Ciclo de Reproceso...' : 'Almacén: Falta Generar Preorden de Casting';
        } elseif (!$esCaminoReproceso && !$castingEnviado) {
            $estado4 = 'Revisando';
            $label4 = 'Almacén: Falta Enviar Preorden a Proveedor';
        } else {
            $estado4 = 'Completado';
            $label4 = $labelPasoFinal;
        }

        $subPasos4 = [
            ['label' => 'Revisión de Respuesta de Calidad', 'estado' => $vistosAlmacen2 ? 'Completado' : ($alertaAlmacen2 ? 'Revisando' : 'En Espera')],
            ['label' => 'Carga de Formatos Firmados al Sistema', 'estado' => $firmadosCargados ? 'Completado' : ($vistosAlmacen2 ? 'En Espera' : 'Incompleto')],
            ['label' => $labelStepFinal, 'estado' => $pasoFinalGenerado ? 'Completado' : ($firmadosCargados ? 'En Espera' : 'Incompleto')],
        ];

        if (!$esCaminoReproceso) {
            $subPasos4[] = ['label' => 'Envío de Preorden a Proveedor', 'estado' => $castingEnviado ? 'Completado' : ($pasoFinalGenerado ? 'En Espera' : 'Incompleto')];
        }

        $pasos['fase4'] = [
            'label' => $label4,
            'estado' => $estado4,
            'tooltip' => $label4,
            'subPasos' => $subPasos4,
        ];

        return [
            'esReproceso' => $esReproceso,
            'isBadgeVisible' => $isBadgeVisible,
            'badgeText' => $badgeText,
            'pasos' => $pasos,
        ];
    }


    public function showViewPiecesInProgress()
    {
        // ── OPTIMIZACIÓN: eager loading evita N+1 de clases, moldura, procesos, fechas y piezas ──
        $wOInProgress = array();
        $workOrders = Orden_trabajo::query()
            ->whereHas('clases', function ($q) {
                $q->where('finalizada', 0);
            })
            ->with([
                'clases' => function ($q) {
                    $q->where('finalizada', 0);
                },
                'clases.procesos',
                'clases.fechasProcesos',
                'clases.piezas',
                'moldura'
            ])
            // Ordenar por prioridad ascendente; las OTs sin prioridad (NULL)
            // se ubican al final gracias al NULLS LAST implícito en MySQL.
            ->orderByRaw('prioridad IS NULL ASC')
            ->orderBy('prioridad')
            ->orderBy('id')
            ->get();

        foreach ($workOrders as $workOrder) {
            $classes = $workOrder->clases; // Ya están filtradas en la query del database
            if ($classes->count() > 0) {
                foreach ($classes as $index => $class) {
                    $process = $class->procesos;
                    if ($process) {
                        if ($index === $classes->keys()->first()) {
                            $wOInProgress[$workOrder->id] = array();
                            $wOInProgress[$workOrder->id]['molding'] = $workOrder->moldura ? $workOrder->moldura->nombre : '?';
                            $wOInProgress[$workOrder->id]['classes'] = array();
                        }
                        $this->insertClassesData($wOInProgress[$workOrder->id]['classes'], $class);
                    }
                }
            }
        }

        // ── Datos de cards PTA (para las OTs actualmente en progreso) ────────
        // buildCardData devuelve null si la clase no tiene registros en PTA.
        $ptaCardsData = [];
        foreach (array_keys($wOInProgress) as $otId) {
            foreach ($wOInProgress[$otId]['classes'] as $className => $classData) {
                if (!isset($classData['id']))
                    continue;
                $claseId = $classData['id'];
                $cardData = PtaResultsController::buildCardData((string) $otId, $claseId);
                if ($cardData !== null) {
                    if (!isset($ptaCardsData[$otId])) {
                        $ptaCardsData[$otId] = [];
                    }
                    $ptaCardsData[$otId][$claseId] = $cardData;
                }
            }
        }

        // ── Checklist de Fundición (solo OTs con flujo activo en fundicion_history) ──
        // Se construye en bloque; OTs sin flujo de fundición retornan null y se omiten.
        $fundicionChecklist = [];
        foreach (array_keys($wOInProgress) as $otId) {
            $fundicionChecklist[$otId] = [];
            foreach ($wOInProgress[$otId]['classes'] as $className => $classData) {
                $checklistData = $this->buildFundicionChecklistForOt((string) $otId, $className);
                if ($checklistData !== null) {
                    $fundicionChecklist[$otId][$className] = $checklistData;
                }
            }
            if (empty($fundicionChecklist[$otId])) {
                unset($fundicionChecklist[$otId]);
            }
        }

        $userProfile = (int) (auth()->user()->perfil ?? 0);
        if ($userProfile === 4 || ($userProfile === 3 && request('sec') === 'calidad')) {
            [$pieces_Released, $info_Pieces] = $this->releasedPiecesController->piecesToBeReleased();
        } else {
            $pieces_Released = [];
            $info_Pieces = [];
        }
        $orderedOtIds = array_keys($wOInProgress);

        return view('pieces_views.piecesInProgress_view', compact(
            'wOInProgress',
            'orderedOtIds',
            'pieces_Released',
            'info_Pieces',
            'ptaCardsData',
            'fundicionChecklist'
        ));
    }

    // ──────────────────────────────────────────────────────────────────────
    // ENDPOINTS PARA GESTIÓN DE PRIORIDADES DE OTs
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Muestra el panel de gestión de prioridades de las OTs en progreso.
     * Solo accesible para perfiles 1 (Master) y 3 (Admin/Gerencia).
     * GET /piecesInProgress/priorityManager
     */
    public function showPriorityManager()
    {
        // Guard de perfil: solo Master (1) y Admin/Gerencia (3)
        $userProfile = auth()->user()->perfil ?? null;
        if (!in_array($userProfile, ['1', '3'])) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        // Traer todas las OTs activas (con al menos una clase no finalizada)
        $workOrders = Orden_trabajo::query()
            ->whereHas('clases', function ($q) {
                $q->where('finalizada', 0);
            })
            ->with([
                'clases' => function ($q) {
                    $q->where('finalizada', 0)->select('id', 'id_ot', 'nombre');
                },
                'moldura' => function ($q) {
                    $q->select('id', 'nombre');
                }
            ])
            ->get();

        // Agrupar por semana idéntico a prioritiesView
        $groupedWOs = [];
        foreach ($workOrders as $wo) {
            $semanaRaw = preg_replace('/[^0-9]/', '', $wo->semana_entrega_cliente ?? '');
            $semana = empty($semanaRaw) ? 'Sin Semana' : $semanaRaw;
            $groupedWOs[$semana][] = $wo;
        }

        // Ordenar semanas de menor a mayor (ascendente)
        uksort($groupedWOs, function ($a, $b) {
            if ($a === 'Sin Semana') return 1;
            if ($b === 'Sin Semana') return -1;
            return (int)$a - (int)$b;
        });

        // Aplanar ordenando OTs dentro de cada semana por prioridad
        $flatOrderedWorkOrders = collect();
        foreach ($groupedWOs as $semana => $wos) {
            $sortedWeekWOs = collect($wos)->sort(function ($a, $b) {
                $pA = ($a->prioridad === null || $a->prioridad == 0) ? 999999 : (int)$a->prioridad;
                $pB = ($b->prioridad === null || $b->prioridad == 0) ? 999999 : (int)$b->prioridad;
                if ($pA === $pB) {
                    return strcmp((string)$a->id, (string)$b->id);
                }
                return $pA <=> $pB;
            });
            foreach ($sortedWeekWOs as $wo) {
                $flatOrderedWorkOrders->push($wo);
            }
        }

        // Transformar a array plano para el frontend con prioridad idéntica a su posición en pantalla
        $otPriorities = $flatOrderedWorkOrders->values()->map(function ($ot, $index) {
            return [
                'ot_id' => $ot->id,
                'moldura' => $ot->moldura ? $ot->moldura->nombre : '—',
                'clases' => $ot->clases->pluck('nombre')->toArray(),
                'prioridad' => $index + 1,
            ];
        })->toArray();

        return view('pieces_views.priorityManager_view', compact('otPriorities'));
    }

    /**
     * Guarda el nuevo orden de prioridades de las OTs.
     * Solo accesible para perfiles 1 (Master) y 3 (Admin/Gerencia).
     * POST /piecesInProgress/priorities
     *
     * Payload esperado: { "priorities": [{"ot_id": "6748", "prioridad": 1}, ...] }
     */
    public function savePriorities(Request $request)
    {
        // Guard de perfil: solo Master (1) y Admin/Gerencia (3)
        $userProfile = auth()->user()->perfil ?? null;
        if (!in_array($userProfile, ['1', '3'])) {
            return response()->json(['success' => false, 'message' => 'Sin permiso.'], 403);
        }

        $priorities = $request->input('priorities');
        if (!is_array($priorities) || count($priorities) === 0) {
            return response()->json(['success' => false, 'message' => 'Payload inválido.'], 422);
        }

        try {
            DB::transaction(function () use ($priorities) {
                foreach ($priorities as $item) {
                    // Validar que los campos necesarios existen
                    $updateData = ['prioridad' => (int) $item['prioridad']];
                    if (isset($item['semana_entrega_cliente']) && $item['semana_entrega_cliente'] !== '') {
                        $updateData['semana_entrega_cliente'] = $item['semana_entrega_cliente'];
                    }
                    Orden_trabajo::query()->where('id', '=', $item['ot_id'], 'and')
                        ->update($updateData);
                }
            });

            return response()->json(['success' => true, 'message' => 'Prioridades guardadas correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error al guardar prioridades OT', [
                'error' => $e->getMessage(),
                'payload' => $priorities,
            ]);
            return response()->json(['success' => false, 'message' => 'Error al guardar prioridades.'], 500);
        }
    }

    /**
     * AJAX: devuelve JSON con los datos actualizados de la card PTA para una OT.
     * GET /piecesInProgress/ptaCard/{otId}
     */
    public function getPtaCardData(string $otId, string $claseId)
    {
        $data = PtaResultsController::buildCardData($otId, (int) $claseId);
        if ($data === null) {
            return response()->json(['error' => 'No PTA data'], 200);
        }
        return response()->json($data);
    }

    /**
     * AJAX: devuelve el checklist de fundición actualizado para una OT.
     * GET /piecesInProgress/fundicionChecklist/{otId}
     *
     * Retorna 404 si la OT no tiene flujo de fundición activo.
     * Usado por el polling de 30s del componente FundicionChecklistCard en el frontend.
     *
     * @param  string $otId  Número de OT (ej. "6748" o "6748_R1")
     */
    public function getFundicionChecklist(string $otId, ?string $className = null)
    {
        $data = $this->buildFundicionChecklistForOt($otId, $className);
        if ($data === null) {
            return response()->json(['error' => 'No fundicion data'], 200);
        }
        return response()->json($data);
    }

    /**
     * AJAX: devuelve el checklist de Tratamiento Térmico actualizado para una OT.
     * GET /piecesInProgress/termicoChecklist/{otId}
     */
    public function getTermicoChecklist(string $otId)
    {
        $otIdClean = explode('_', $otId)[0];
        $ot = Orden_trabajo::with('clases')->find($otIdClean);
        if (!$ot) {
            return response()->json([]);
        }

        $termicoData = [];
        foreach ($ot->clases as $clase) {
            if ($clase->finalizada == 0) {
                $tratadas = TratamientoTermico::where('id_clase', '=', $clase->id, 'and')->sum('cantidad');
                $termicoData[$clase->nombre] = [
                    'tratadas' => (int) $tratadas,
                    'pieces' => (int) $clase->piezas
                ];
            }
        }

        return response()->json($termicoData);
    }

    /**
     * AJAX: devuelve los datos básicos de las clases de una OT (pedido, piezas).
     * GET /piecesInProgress/classesData/{otId}
     */
    public function getClassesData(string $otId)
    {
        $otIdClean = explode('_', $otId)[0];
        $ot = Orden_trabajo::with('clases')->find($otIdClean);
        if (!$ot) {
            return response()->json([]);
        }

        $classesData = [];
        foreach ($ot->clases as $clase) {
            $classesData[] = [
                'id' => $clase->id,
                'pedido' => (int) $clase->pedido,
                'piezas' => (int) $clase->piezas
            ];
        }

        return response()->json($classesData);
    }

    /**
     * AJAX: devuelve el checklist de planeación actualizado para una OT (por clase).
     * GET /piecesInProgress/planeacionChecklist/{otId}
     */
    public function getPlaneacionChecklist(string $otId)
    {
        $otIdClean = explode('_', $otId)[0];
        $ot = Orden_trabajo::with('clases')->find($otIdClean);
        if (!$ot) {
            return response()->json([]);
        }

        $baseOtStr = "OT " . $ot->id . ($ot->moldura ? " - " . $ot->moldura->nombre : "");

        $baseOtClean = str_replace(['—', '–', "\xc2\xa0"], '-', $baseOtStr);
        $baseOtClean = mb_strtoupper($baseOtClean, 'UTF-8');
        $baseOtClean = preg_replace('/\s+/', ' ', $baseOtClean);
        $baseOtClean = trim(preg_replace('/[\/\\\\]/', '', preg_replace('/\.\.+/', '', $baseOtClean)));

        $dibujosDir = 'DOCUMENTACION_GIS/DIBUJOS_MAQUINADOS/' . $baseOtClean;
        $oldDibujosDir = 'DIBUJOS_GIS/' . $baseOtClean;

        $checklist = [];

        foreach ($ot->clases as $clase) {
            if ($clase->finalizada)
                continue;

            $claseNameClean = trim(preg_replace('/[\/\\\\]/', '', preg_replace('/\.\.+/', '', $clase->nombre)));

            // Calculate active processes exactly as they will be rendered in UI
            $procesosActivosParaCotas = 0;
            $procesosTotalesAsignados = 0;
            $cotasGuardadas = 0;

            $procesosFounded = Procesos::query()->where('id_clase', $clase->id)->first();
            $processesInOrder = [];
            $baseType = $clase->getBaseType();
            switch ($baseType) {
                case "Bombillo":
                case "Molde":
                    $processesInOrder = ["cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoBombillo", "acabadoMolde", "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado"];
                    break;
                case 'Corona':
                    $processesInOrder = ["cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado"];
                    break;
                case "Obturador":
                case "Fondo":
                    $processesInOrder = ["operacionEquipo", "soldadura", "soldaduraPTA"];
                    break;
                case "Candado Obturador":
                    $processesInOrder = ["operacionEquipo"];
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

            $soldaduraBand = false;
            if ($procesosFounded) {
                $anyActive = false;
                foreach ($processesInOrder as $p) {
                    if ($procesosFounded[$p] != 0) {
                        $anyActive = true;
                        break;
                    }
                }

                foreach ($processesInOrder as $process) {
                    $isActive = $anyActive ? ($procesosFounded[$process] != 0) : true;
                    if ($isActive) {
                        if (str_contains($process, "soldadura") && $soldaduraBand) {
                            continue;
                        }
                        $soldaduraBand = str_contains($process, "soldadura") ? true : false;
                        $field = $process == "operacionEquipo" ? ["1 operacion", "2 operacion"] : [$process];

                        foreach ($field as $processField) {
                            $procesosTotalesAsignados++;

                            $processName = "";
                            if (count($field) > 1) {
                                $processName = "Operacion Equipo_" . $processField;
                            } else {
                                if (str_contains($processField, "soldadura")) {
                                    $processName = "Soldadura y Soldadura PTA";
                                } else {
                                    $processName = $this->nombreProceso($processField);
                                }
                            }

                            if (!in_array($processName, ['Soldadura y Soldadura PTA', 'Asentado', 'Rectificado'])) {
                                $procesosActivosParaCotas++;

                                $searchProcess = explode('_', $processName)[0];
                                $hasCota = SystemLog::query()->where('id_ot', '=', $ot->id, 'and')
                                    ->where('clase', '=', $clase->nombre, 'and')
                                    ->where('action', '=', 'Cargo/Modificación Cotas Nominales', 'and')
                                    ->where('proceso', '=', $searchProcess, 'and')
                                    ->exists();
                                if ($hasCota) {
                                    $cotasGuardadas++;
                                }
                            }
                        }
                    }
                }
            }
            // 1. Dibujos de maquinados subidos
            $dibujosSubidosCount = 0;
            $newPath = $dibujosDir . '/' . $claseNameClean;
            $oldPath = $oldDibujosDir . '/' . $claseNameClean;

            if (Storage::disk('local')->exists($newPath)) {
                $files = Storage::disk('local')->files($newPath);
                $dibujosSubidosCount = count(array_filter($files, fn($f) => strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf'));
            }
            if ($dibujosSubidosCount === 0 && Storage::disk('local')->exists($oldPath)) {
                $files = Storage::disk('local')->files($oldPath);
                $dibujosSubidosCount = count(array_filter($files, fn($f) => strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf'));
            }
            $hasDibujos = $dibujosSubidosCount > 0;

            // Obtener fecha del primer dibujo subido
            $fechaPrimerDibujo = null;
            if ($dibujosSubidosCount > 0 && isset($files) && is_array($files)) {
                $earliestTime = null;
                foreach ($files as $f) {
                    if (strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'pdf') {
                        $mtime = Storage::disk('local')->lastModified($f);
                        if ($earliestTime === null || $mtime < $earliestTime) {
                            $earliestTime = $mtime;
                        }
                    }
                }
                if ($earliestTime) {
                    $fechaPrimerDibujo = \Carbon\Carbon::createFromTimestamp($earliestTime);
                }
            }

            // Obtener fecha de la primera cota registrada
            $fechaPrimeraCota = null;
            if ($cotasGuardadas > 0) {
                $logCota = SystemLog::query()->where('id_ot', '=', $ot->id, 'and')
                    ->where('clase', '=', $clase->nombre, 'and')
                    ->where('action', '=', 'Cargo/Modificación Cotas Nominales', 'and')
                    ->where('proceso', '!=', 'none', 'and')
                    ->orderBy('created_at', 'asc')
                    ->first();
                if ($logCota) {
                    $fechaPrimeraCota = $logCota->created_at;
                }
            }

            $cotasVaPrimero = false;
            if ($fechaPrimeraCota && $fechaPrimerDibujo) {
                $cotasVaPrimero = $fechaPrimeraCota->lt($fechaPrimerDibujo);
            } elseif ($fechaPrimeraCota && !$fechaPrimerDibujo) {
                $cotasVaPrimero = true;
            }

            // 2. Cotas finalizadas
            $hasCotas = $procesosActivosParaCotas > 0 ? ($cotasGuardadas >= $procesosActivosParaCotas) : true;

            $hasProceso = $procesosTotalesAsignados > 0 ? true : false;
            // Armar el estado del checklist
            $pasos = [];

            // Paso 1
            $pasos['fase1'] = [
                'label' => "Procesos de Producción ({$procesosTotalesAsignados})",
                'estado' => $hasProceso ? 'completado' : 'pendiente'
            ];

            $pasoDibujos = [
                'label' => $dibujosSubidosCount > 0 ? "Dibujos de maquinados subidos ({$dibujosSubidosCount})" : 'Dibujos de maquinados subidos',
            ];

            $pasoCotas = [
                'label' => $procesosActivosParaCotas > 0 ? "Cotas de OT/Clase subidas (Admin) ({$cotasGuardadas}/{$procesosActivosParaCotas})" : "Cotas de OT/Clase subidas (Admin) (N/A)",
            ];

            if ($cotasVaPrimero) {
                if ($procesosActivosParaCotas == 0) {
                    $pasoCotas['estado'] = 'no_aplica';
                } else {
                    $pasoCotas['estado'] = $hasCotas ? 'completado' : ($hasProceso ? 'pendiente' : 'inactivo');
                }
                $pasoDibujos['estado'] = $hasDibujos ? 'completado' : (($hasCotas || $cotasGuardadas > 0 || $procesosActivosParaCotas == 0) ? 'pendiente' : 'inactivo');

                $pasos['fase2'] = $pasoCotas;
                $pasos['fase3'] = $pasoDibujos;
            } else {
                $pasoDibujos['estado'] = $hasDibujos ? 'completado' : ($hasProceso ? 'pendiente' : 'inactivo');
                if ($procesosActivosParaCotas == 0) {
                    $pasoCotas['estado'] = 'no_aplica';
                } else {
                    $pasoCotas['estado'] = $hasCotas ? 'completado' : ($hasDibujos ? 'pendiente' : 'inactivo');
                }

                $pasos['fase2'] = $pasoDibujos;
                $pasos['fase3'] = $pasoCotas;
            }

            $checklist[$clase->id] = $pasos;
        }

        return response()->json($checklist);
    }

    /**
     * AJAX: actualiza un flag booleano en fundicion_history para el flujo de 12 pasos.
     * POST /fundicion/updateFlag
     */
    public function markFundicionFlag(Request $request)
    {
        $otId = $request->input('ot');
        $flag = $request->input('flag');

        $history = FundicionHistory::query()
            ->where(function ($q) use ($otId) {
                $q->where('ot', 'LIKE', "OT {$otId} - %")
                    ->orWhere('ot', 'LIKE', "OT {$otId}_R% - %")
                    ->orWhere('ot', '=', "OT {$otId}")
                    ->orWhere('ot', 'LIKE', "OT {$otId}_R%");
            })
            ->where('status', '!=', 'inactiva')
            ->orderByDesc('updated_at')
            ->first();

        if (!$history) {
            return response()->json(['success' => false, 'message' => 'No se encontró el flujo de fundición para esta OT.']);
        }

        $allowedFlags = [
            'dibujos_vistos_almacen',
            'pre_orden_autorizada',
            'alerta_calidad_sent',
            'documentos_revisados_calidad',
            'alerta_almacen_2_sent',
            'documentos_vistos_almacen_2',
            'documentos_firmados_cargados'
        ];

        if (in_array($flag, $allowedFlags)) {
            $history->$flag = true;
            $history->save();
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Flag no válido.']);
    }
    /**
     * @param string $date
     * @param string $time
     */
    public function getStringDate($date, $time)
    {
        $formattedDate = new DateTime($date);
        $formattedDate = $formattedDate->format('d-m-Y');

        //Establecer la fecha en español
        $dayName = new DateTime($date);
        $dayName = $dayName->format('l');

        switch ($dayName) {
            case "Monday":
                $dayName = "Lunes";
                break;
            case "Tuesday":
                $dayName = "Martes";
                break;
            case "Wednesday":
                $dayName = "Miercoles";
                break;
            case "Thursday":
                $dayName = "Jueves";
                break;
            case "Friday":
                $dayName = "Viernes";
                break;
            case "Saturday":
                $dayName = "Sabado";
                break;
            case "Sunday":
                $dayName = "Domingo";
                break;
        }

        $formattedTime = new DateTime($time);
        $formattedTime = $formattedTime->format('H:i:s A');

        return $dayName . " " . $formattedDate . " " . $formattedTime;
    }
    /**
     * @param string $proceso
     */
    public function nombreProceso($proceso)
    {
        switch ($proceso) {
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
            case "soldadura":
                return "Soldadura";
            case "soldaduraPTA":
                return "Soldadura PTA";
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
            case "cavidades":
                return "Cavidades";
            case "barreno_profundidad":
                return "Barreno Profundidad";
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
            case "operacionEquipo":
                return "Operación Equipo";
            case "embudoCM":
                return "Embudo CM";
            case "primeraOperacionCabezaSoplo":
                return "Primera Operacion Cabeza Soplo";
            case "segundaOperacionCabezaSoplo":
                return "Segunda Operacion Cabeza Soplo";
        }
    }
    function finishOrder(Request $request)
    {
        // Algoritmo para finalizar el pedido de una clase
        $class = Clase::query()->where('id_ot', '=', $request->input('wOrderName'), 'and')->where('nombre', '=', $request->input('className'), 'and')->first();
        $arrayProcesses = $this->insertProcessesData($class);

        $counterRejected = 0;
        $text = "";
        $bandSold = false;
        foreach ($arrayProcesses as $key => $process) {
            $text = "No se puede finalizar el pedido porque las piezas no se han completado en " . $key;
            $total = 0;
            // Sumar las piezas rechazadas de soldadura y soldadura pta
            if (str_contains($key, "Soldadura")) {
                if (!$bandSold) {
                    $bandSold = true;
                    foreach (["Soldadura", "Soldadura PTA"] as $processSold) {
                        $counterRejected += array_key_exists($processSold, $arrayProcesses) ? $arrayProcesses[$processSold]["pieces"]["bad"] : 0;
                    }
                }
            } else { // Sumar las piezas rechazadas del proceso actual
                $counterRejected += $process["pieces"]["bad"];
            }

            //Sumar las piezas buenas de los procesos
            if (($key == "Soldadura" || $key == "Soldadura PTA")) {
                if (array_key_exists("Soldadura", $arrayProcesses) && array_key_exists("Soldadura PTA", $arrayProcesses)) {
                    foreach (["Soldadura", "Soldadura PTA"] as $processSold) {
                        $total += array_key_exists($processSold, $arrayProcesses) ? $arrayProcesses[$processSold]["pieces"]["good"] : 0;
                    }
                }
                $text = "No se puede finalizar el pedido porque las piezas no se han completado en las soldaduras";
            } else {
                $total = $process["pieces"]["good"];
            }

            $total += $counterRejected; // Sumar las piezas rechazadas de los anteriores procesos con las piezas buenas del proceso

            if ($total < $class->piezas) {
                $finishOrder = ["error", $text];
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json($finishOrder);
                }
                return redirect()->back()->with('finishOrder', $finishOrder);
            }
        }
        $class->finalizada = 1;
        $class->save();
        $finishOrder = ["success", "Se ha finalizado el pedido correctamente"];
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($finishOrder);
        }
        return redirect()->route('showPiecesInProgress')->with('finishOrder', $finishOrder);
    }
    /**
     * @param mixed $class
     * @param string $processName
     * @param array $piecesBadData
     */
    function getPieces($class, $processName, &$piecesBadData)
    {
        $setStoredParts = array();
        $piecesArray = ['good' => [], 'bad' => [], 'total' => 0];
        $processNamesArray = $processName === 'Soldadura y Soldadura PTA'
            ? ['Soldadura', 'Soldadura PTA']
            : [$processName];

        // ── OPTIMIZACIÓN: pre-cargar users en memoria de forma estática (se ejecuta solo una vez por request) ──
        static $usersCache = null;
        if ($usersCache === null) {
            $usersCache = User::query()
                ->select(['matricula', 'nombre', 'a_paterno', 'a_materno'])
                ->get()
                ->keyBy('matricula');
        }

        foreach ($processNamesArray as $pName) {
            // ── OPTIMIZACIÓN: buscar piezas en memoria usando la relación pre-cargada de la clase ──
            $piezasRelation = $class->relationLoaded('piezas')
                ? $class->getRelation('piezas')
                : $class->piezas()->get();
            $pieces = $piezasRelation->where('proceso', $pName);

            if ($pieces->isEmpty())
                continue;

            // ── Mapa n_pieza → pieza para buscar H/M sin queries adicionales ──
            $piecesMap = $pieces->keyBy('n_pieza');

            foreach ($pieces as $piece) {
                if (substr($piece->n_pieza, -1, 1) !== 'J') {
                    $pares = true;
                    preg_match('/^\d+/', $piece->n_pieza, $noSet);
                    $noSet = $noSet[0];

                    if (!in_array($noSet, $setStoredParts)) {
                        $setStoredParts[] = $noSet;

                        // ── 0 queries: buscar H/M desde el mapa en memoria ──
                        $pFemale = $piecesMap->get($noSet . 'H');
                        $pMale = $piecesMap->get($noSet . 'M');

                        if ($pFemale && $pMale) {
                            if ($pFemale->liberacion == 0) {
                                if ($pFemale->error === 'Ninguno' && $pMale->error === 'Ninguno') {
                                    array_push($piecesArray['good'], $pFemale, $pMale);
                                } else {
                                    if ($processName === 'Soldadura PTA') {
                                        if (in_array($pFemale->error, ['Fundicion', 'Fundición']) || in_array($pMale->error, ['Fundicion', 'Fundición'])) {
                                            array_push($piecesArray['bad'], $pFemale, $pMale);
                                            if (in_array($pFemale->error, ['Fundicion', 'Fundición']))
                                                array_push($piecesBadData, $this->getBadPiecesData($pFemale, null, '- - - ', $usersCache));
                                            if (in_array($pMale->error, ['Fundicion', 'Fundición']))
                                                array_push($piecesBadData, $this->getBadPiecesData($pMale, null, '- - - ', $usersCache));
                                        } else {
                                            array_push($piecesArray['good'], $pFemale, $pMale);
                                        }
                                    } else {
                                        array_push($piecesArray['bad'], $pFemale, $pMale);
                                        if ($pFemale->error !== 'Ninguno')
                                            array_push($piecesBadData, $this->getBadPiecesData($pFemale, null, '- - - ', $usersCache));
                                        if ($pMale->error !== 'Ninguno')
                                            array_push($piecesBadData, $this->getBadPiecesData($pMale, null, '- - - ', $usersCache));
                                    }
                                }
                            } elseif ($pFemale->liberacion == 1) {
                                array_push($piecesArray['good'], $pFemale, $pMale);
                            } else {
                                array_push($piecesArray['bad'], $pFemale, $pMale);
                                $piecesBadData[] = $this->getBadPiecesData($pFemale, $pFemale->error !== 'Ninguno' ? null : 'Rechazada', '- - - ', $usersCache);
                                $piecesBadData[] = $this->getBadPiecesData($pMale, $pMale->error !== 'Ninguno' ? null : 'Rechazada', '- - - ', $usersCache);
                            }
                        } else {
                            $incompletePiece = $pFemale ?? $pMale;
                            if ($incompletePiece && $incompletePiece->liberacion == 2) {
                                array_push($piecesArray['bad'], $incompletePiece, $incompletePiece);
                                $piecesBadData[] = $this->getBadPiecesData($incompletePiece, 'Rechazada', '- - - ', $usersCache);
                            }
                        }
                    }
                } else {
                    $pares = false;
                    if ($piece->liberacion == 0) {
                        if ($piece->error === 'Ninguno') {
                            array_push($piecesArray['good'], $piece);
                        } else {
                            if ($processName === 'Soldadura PTA') {
                                if (in_array($piece->error, ['Fundicion', 'Fundición'])) {
                                    array_push($piecesArray['bad'], $piece);
                                    $piecesBadData[] = $this->getBadPiecesData($piece, null, '- - - ', $usersCache);
                                } else {
                                    array_push($piecesArray['good'], $piece);
                                }
                            } else {
                                array_push($piecesArray['bad'], $piece);
                                $piecesBadData[] = $this->getBadPiecesData($piece, null, '- - - ', $usersCache);
                            }
                        }
                    } elseif ($piece->liberacion == 1) {
                        array_push($piecesArray['good'], $piece);
                    } else {
                        array_push($piecesArray['bad'], $piece);
                        $piecesBadData[] = $this->getBadPiecesData($piece, $piece->error !== 'Ninguno' ? null : 'Rechazada', '- - - ', $usersCache);
                    }
                }
            }
        }

        if (isset($pares)) {
            $goodCount = 0;
            foreach ($piecesArray['good'] as $p) {
                $goodCount += (substr($p->n_pieza, -1) === 'J') ? 1 : 0.5;
            }
            $badCount = 0;
            foreach ($piecesArray['bad'] as $p) {
                $badCount += (substr($p->n_pieza, -1) === 'J') ? 1 : 0.5;
            }
            $piecesArray['good'] = $goodCount;
            $piecesArray['bad'] = $badCount;
            $piecesArray['total'] = $goodCount + $badCount;
        } else {
            $piecesArray['good'] = 0;
            $piecesArray['bad'] = 0;
            $piecesArray['total'] = 0;
        }
        return $piecesArray;
    }

    /**
     * @param Pieza $piece
     * @param mixed $rechazada
     * @param string $operation
     * @param mixed $usersCache
     */
    function getBadPiecesData($piece, $rechazada = null, $operation = '- - - ', $usersCache = null)
    {
        $array = array();
        // ── Usa cache si está disponible; si no, hace la query ──
        $operador = $usersCache
            ? $usersCache->get($piece->id_operador)
            : User::query()
                ->select(['matricula', 'nombre', 'a_paterno', 'a_materno'])
                ->where('matricula', '=', $piece->id_operador, 'and')
                ->first();

        $array['piece'] = $piece->n_pieza;
        preg_match('/^\d+/', $piece->n_pieza, $n_juego);
        $array['setNumber'] = $n_juego[0] . 'J';
        $array['operator'] = $operador ? "{$operador->nombre} {$operador->a_paterno} {$operador->a_materno}" : '(desconocido)';
        $array['process'] = $piece->proceso;
        $array['operation'] = $operation;
        $array['error'] = $rechazada ?? $piece->error;
        return $array;
    }
}