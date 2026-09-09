<?php

namespace App\Http\Controllers;

use App\Models\Clase;
use App\Models\Metas;
use App\Models\Moldura;
use App\Models\Orden_trabajo;
use App\Models\Procesos;
use App\Models\tiempoproduccion;
use Carbon\Carbon;
use Illuminate\Http\Request;

class tiemposProduccionController extends Controller
{
    /** @var \App\Http\Controllers\PzasLiberadasController */
    protected $controladorPzas;
    /** @var \App\Http\Controllers\ClassController */
    protected $classController;
    public function __construct()
    {
        $this->controladorPzas = new PzasLiberadasController();
        $this->classController = new ClassController();
        $this->middleware('auth');
    }
        /**
     * @param mixed $clase
     */
    public function show($clase = false)
    {

        $wOrdersFounded = Orden_trabajo::all();
        $workOrders = array();
        if (count($wOrdersFounded) > 0) {
            // OPTIMIZACIÓN: solo columnas necesarias para evitar memoria agotada con tablas grandes
            $allTiempos = tiempoproduccion::query()
                ->select(['id_clase', 'proceso', 'tiempo', 'tamanio'])
                ->get()
                ->groupBy('id_clase'); // Precargar todos los tiempos

            foreach ($wOrdersFounded as $workOrder) {
                $classes = $this->classController->getClasses($workOrder);
                if (count($classes) > 0) {
                    $workOrders[$workOrder->id] = array();
                    foreach ($classes as $class) {
                        $workOrders[$workOrder->id][$class->nombre] = array();
                        $tiemposProduccion = $allTiempos->get($class->id, collect()); // Buscar en memoria
                        if ($tiemposProduccion->count() > 0) {
                            foreach ($tiemposProduccion as $tiempo) {
                                // Inicializar el array si no existe
                                $workOrders[$workOrder->id][$class->nombre][$tiempo->proceso] = $workOrders[$workOrder->id][$class->nombre][$tiempo->proceso] ?? [];
                                // Inicializar el array de tiempos si no existe
                                foreach ($tiempo->toArray() as $columna => $valor) {
                                    if ($columna == 'id_clase' || $columna == 'clase' || $columna == 'proceso' || $columna == 'tamanio' || $columna == 'created_at' || $columna == 'updated_at') {
                                        continue;
                                    }
                                    $workOrders[$workOrder->id][$class->nombre][$tiempo->proceso][$columna] = $valor;
                                }
                            }
                        } else {
                            $workOrders[$workOrder->id][$class->nombre] = null;
                        }
                    }
                }
            }
        }

        if ($clase) {
            return view('processes_views.productionTimes', compact('workOrders', 'clase'));
        }
        return view('processes_views.productionTimes', compact('workOrders'));
    }
        /**
     * @param mixed $class
     */
    public function getProductionTimes($class)
    {
        $baseType = $class->getBaseType();
        switch ($baseType) {
            case "Bombillo":
                return match ($class->tamanio) {
                    'Chico' => ['Cepillado' => 52, 'Desbaste Exterior' => 22, 'Revision Laterales' => 20, 'Primera Operacion' => 24, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 24, 'Soldadura' => 24, 'Soldadura PTA' => 24, 'Rectificado' => 12, 'Asentado' => 20, 'Calificado' => 22, 'Acabado Bombillo' => 25, 'Barreno Profundidad' => 27, 'Cavidades' => 42, 'Copiado' => 27, 'Off Set' => 16, 'Palomas' => 12, 'Rebajes' => 20, 'Grabado' => 12,],
                    'Mediano' => ['Cepillado' => 60, 'Desbaste Exterior' => 30, 'Revision Laterales' => 24, 'Primera Operacion' => 28, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 28, 'Soldadura' => 30, 'Soldadura PTA' => 30, 'Rectificado' => 13, 'Asentado' => 24, 'Calificado' => 24, 'Acabado Bombillo' => 27, 'Barreno Profundidad' => 40, 'Cavidades' => 34, 'Copiado' => 29, 'Off Set' => 16, 'Palomas' => 12, 'Rebajes' => 20, 'Grabado' => 12,],
                    'Grande' => ['Cepillado' => 90, 'Desbaste Exterior' => 35, 'Revision Laterales' => 26, 'Primera Operacion' => 30, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 28, 'Soldadura' => 34, 'Soldadura PTA' => 34, 'Rectificado' => 14, 'Asentado' => 30, 'Calificado' => 26, 'Acabado Bombillo' => 28, 'Barreno Profundidad' => 60, 'Cavidades' => 26, 'Copiado' => 0, 'Off Set' => 0, 'Palomas' => 0, 'Rebajes' => 0, 'Grabado' => 0,],
                    default => ['Cepillado' => 52, 'Desbaste Exterior' => 22, 'Revision Laterales' => 20, 'Primera Operacion' => 24, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 24, 'Soldadura' => 24, 'Soldadura PTA' => 24, 'Rectificado' => 12, 'Asentado' => 20, 'Calificado' => 22, 'Acabado Bombillo' => 25, 'Barreno Profundidad' => 27, 'Cavidades' => 42, 'Copiado' => 27, 'Off Set' => 16, 'Palomas' => 12, 'Rebajes' => 20, 'Grabado' => 12,],
                };

            case "Molde":
                return match ($class->tamanio) {
                    'Chico' => ['Cepillado' => 53, 'Desbaste Exterior' => 22, 'Revision Laterales' => 20, 'Primera Operacion' => 20, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 24, 'Soldadura' => 24, 'Soldadura PTA' => 24, 'Rectificado' => 12, 'Asentado' => 20, 'Calificado' => 22, 'Acabado Molde' => 24, 'Barreno Profundidad' => 28, 'Cavidades' => 21, 'Copiado' => 0, 'Off Set' => 0, 'Palomas' => 0, 'Rebajes' => 0, 'Grabado' => 0,],
                    'Mediano' => ['Cepillado' => 64, 'Desbaste Exterior' => 30, 'Revision Laterales' => 24, 'Primera Operacion' => 24, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 28, 'Soldadura' => 30, 'Soldadura PTA' => 30, 'Rectificado' => 13, 'Asentado' => 24, 'Calificado' => 24, 'Acabado Molde' => 26, 'Barreno Profundidad' => 40, 'Cavidades' => 17, 'Copiado' => 0, 'Off Set' => 0, 'Palomas' => 0, 'Rebajes' => 0, 'Grabado' => 0,],
                    'Grande' => ['Cepillado' => 120, 'Desbaste Exterior' => 35, 'Revision Laterales' => 26, 'Primera Operacion' => 26, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 30, 'Soldadura' => 70, 'Soldadura PTA' => 70, 'Rectificado' => 20, 'Asentado' => 30, 'Calificado' => 26, 'Acabado Molde' => 30, 'Barreno Profundidad' => 90, 'Cavidades' => 13, 'Copiado' => 0, 'Off Set' => 0, 'Palomas' => 0, 'Rebajes' => 0, 'Grabado' => 0,],
                    default => ['Cepillado' => 53, 'Desbaste Exterior' => 22, 'Revision Laterales' => 20, 'Primera Operacion' => 20, 'Barreno Maniobra' => 15, 'Segunda Operacion' => 24, 'Soldadura' => 24, 'Soldadura PTA' => 24, 'Rectificado' => 12, 'Asentado' => 20, 'Calificado' => 22, 'Acabado Molde' => 24, 'Barreno Profundidad' => 28, 'Cavidades' => 21, 'Copiado' => 0, 'Off Set' => 0, 'Palomas' => 0, 'Rebajes' => 0, 'Grabado' => 0,],
                };
            case "Obturador":
            case "Fondo":
            case "Embudo":
            case "Candado Obturador":
                return ['Operacion Equipo' => 24, 'Soldadura' => 30, 'Soldadura PTA' => 15];
            case "Corona":
                return ['Cepillado' => 35, 'Desbaste Exterior' => 26, 'Primera Operacion' => 24, 'Segunda Operacion' => 24, 'Soldadura' => 24, 'Soldadura PTA' => 24, 'Rectificado' => 12, 'Asentado' => 20, 'Calificado' => 22, 'Acabado Bombillo' => 15];
            case "Plato":
                return ['Barreno Maniobra' => 15, 'Operacion Equipo' => 24];
            case "Cabeza de Soplo":
                return match ($class->tamanio) {
                    'Chico' => ['Primera Operacion Cabeza Soplo' => 24, 'Segunda Operacion Cabeza Soplo' => 24],
                    'Mediano' => ['Primera Operacion Cabeza Soplo' => 28, 'Segunda Operacion Cabeza Soplo' => 28],
                    'Grande' => ['Primera Operacion Cabeza Soplo' => 32, 'Segunda Operacion Cabeza Soplo' => 32],
                    default => ['Primera Operacion Cabeza Soplo' => 24, 'Segunda Operacion Cabeza Soplo' => 24],
                };
            default:
                return null;
        }
    }
        /**
     * @param mixed $class
     */
    public function setProductionTimes($class)
    {
        $productionTimes = $this->getProductionTimes($class);
        if ($productionTimes != null) {
            foreach ($productionTimes as $process => $time) {
                $processName = $this->get_processName($process);
                $tiempo = tiempoproduccion::query()->where('id_clase', $class->id)->where('proceso', $processName)->first();
                if ($tiempo) {
                    if ($tiempo->tamanio == $class->tamanio) {
                        $tiempo->tiempo = $tiempo->tiempo != 0 ? $tiempo->tiempo : $time;
                    } else {
                        $tiempo->tiempo = $time;
                    }
                    $tiempo->tamanio = $class->tamanio;
                } else {
                    $tiempo = new tiempoproduccion();
                    $tiempo->id_clase = $class->id;
                    $tiempo->clase = $class->nombre;
                    $tiempo->tamanio = $class->tamanio;
                    $tiempo->proceso = $processName;
                    $tiempo->tiempo = $time;
                }
                $tiempo->save();
            }
        }
    }
        /**
     * @param Request $request
     */
    public function store(Request $request)
    {
        $classObj = Clase::query()->where('nombre', $request->input('class'))->where("id_ot", $request->input('workOrder'))->first();
        if (!$classObj) return redirect()->back()->with('error', 'Clase no encontrada.');
        
        $productionTimes = $this->getProductionTimes($classObj);
        $tiemposClase = tiempoproduccion::query()->where('id_clase', $classObj->id)->get()->keyBy('proceso'); // Pre-cargar tiempos

        foreach ($request->all() as $key => $value) {
            if ($key == '_token' || $key == "class" || $key == "workOrder") {
                continue;
            }
            $class = $classObj; // Se usa el cargado fuera del loop
            $tiempo = $tiemposClase->get($key);

            $processName = $this->get_processNormalName($key);
            if ($tiempo) {
                $tiempo->tamanio = $class->tamanio;
                $tiempo->tiempo = $value != 0 ? $value : ($productionTimes[$processName]) ?? 0;
            } else {
                $tiempo = new tiempoproduccion();
                $tiempo->id_clase = $class->id;
                $tiempo->clase = $request->input('class');
                $tiempo->tamanio = $class->tamanio;
                $tiempo->proceso = $key;
                $tiempo->tiempo = $value != 0 ? $value : ($productionTimes[$processName]) ?? 0;
            }
            $tiempo->save();
        }
        $clase = $request->input('class');


        //Actualizar todas las Clases
        $this->update();
        return redirect()->route("showTimes", compact('clase'))->with('success', 'Tiempos de producción actualizados correctamente.');
    }
    public function update()
    {
        $clases = $this->guardarClasesInArray();
        if ($clases != null) {
            //Se hace el algoritmo
            foreach ($clases as $clase) {
                //Se obtienen los procesos de la clase
                $procesos = $this->asignarProcesos($clase[0]->nombre);
                if ($procesos != null) {
                    $this->calcularFechas($procesos, $clase);
                    $this->updateMetas();
                }
            }
        }
    }
    public function guardarClasesInArray()
    {
        //Se obtienen todas las clases de la tabla fechas_procesos
        $idClase = Procesos::query()->select('id_clase')->distinct()->get();

        if ($idClase->count() == 0) {
            return null;
        }
        //Se guardan los procesos de cada clase en una array bidimensional
        $contadorClases = 0;
        $clases = array();
        foreach ($idClase as $id) {
            //Obtener la clase por el id
            $clase = Clase::query()->find($id->id_clase);
            $clases[$contadorClases] = array();
            $clases[$contadorClases][0] = $clase;
            $clases[$contadorClases][1] = array();
            // $clases[$contadorClases][0] = $clase->id; //Distinguir como se conforma el array

            //Obtener todos los procesos creados (tiempos) de esa clase
            $procesos = $this->getProcesos($clase);
            $clases[$contadorClases][1] = $procesos;
            $contadorClases++;
        }
        return $clases;
    }
        /**
     * @param mixed $clase
     */
    public function asignarProcesos($clase)
    {
        $baseType = \App\Models\Clase::normalizeClassName(is_string($clase) ? $clase : ($clase->nombre ?? ''));
        switch ($baseType) {
            case "Bombillo":
                return array("cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoBombillo", "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado");
            case "Molde":
                return array("cepillado", "desbaste_exterior", "revision_laterales", "pOperacion", "barreno_maniobra", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado", "acabadoMolde", "barreno_profundidad", "cavidades", "copiado", "offSet", "palomas", "rebajes", "grabado");
            case "Obturador":
            case "Fondo":
                return array("operacionEquipo", "soldadura", "soldaduraPTA");
                break;
            case "Candado Obturador":
                return array("operacionEquipo");
                break;
            case "Corona":
                return array("cepillado", "desbaste_exterior", "pOperacion", "sOperacion", "soldadura", "soldaduraPTA", "rectificado", "asentado", "calificado");
            case "Plato":
                return array("barreno_maniobra", "operacionEquipo");
            case "Embudo":
                return array("operacionEquipo", "embudoCM");
            case "Cabeza de Soplo":
                return array("primeraOperacionCabezaSoplo", "segundaOperacionCabezaSoplo");
            default:
                return;
        }
    }
        /**
     * @param mixed $clase
     */
    public function getProcesos($clase)
    {
        $registroProcesos = Procesos::query()->where('id_clase', $clase->id)->first();
        if ($registroProcesos) {
            $columnas = $registroProcesos->getAttributes();

            $procesos = array_keys(array_filter($columnas, function ($value) {
                return $value != 0;
            }));

            //Eliminar los campos que no son procesos
            $procesos = array_slice($procesos, 2);
            return $procesos;
        }
    }
        /**
     * @param mixed $procesos
     * @param mixed $clase
     */
    public function calcularFechas($procesos, $clase)
    {
        $noProceso = 0;
        $procesoFechas = null;
        for ($i = 0; $i < count($procesos); $i++) {
            $pos = array_search($procesos[$i], $clase[1]);
            if ($pos !== false) {
                $maquinas = $this->obtenerMaquinasClase($clase[0]->id, $clase[1][$pos]);
                $procesoFechas = $this->classController->registerProcessDates($clase[0], $procesos, $i, $noProceso, $maquinas);
                $noProceso++;
            }
        }

        //Guardar unicamente la fecha de termino si se calculó algún proceso
        if ($procesoFechas) {
            $clase = Clase::query()->find($clase[0]->id);
            $clase->fecha_termino = Carbon::parse($procesoFechas->fecha_fin)->format('Y-m-d');
            $clase->hora_termino = Carbon::parse($procesoFechas->fecha_fin)->format('H:i:s');
            $clase->save();
        }
    }
        /**
     * @param mixed $claseID
     * @param mixed $proceso
     */
    public function obtenerMaquinasClase($claseID, $proceso)
    {
        $maquinas = Procesos::query()->where('id_clase', $claseID)->distinct()->value($proceso);
        return $maquinas;
    }
    public function updateMetas()
    {
        // OPTIMIZACIÓN: solo columnas necesarias — Metas tiene 6,400+ filas y tiempos 2,600+
        $metas = Metas::query()
            ->select(['id', 'id_clase', 'proceso', 'h_inicio', 'h_termino', 't_estandar', 'meta'])
            ->get();
        $allTiemposDeClases = tiempoproduccion::query()
            ->select(['id_clase', 'proceso', 'tiempo', 'tamanio'])
            ->get()
            ->groupBy('id_clase'); // Pre-cargar todos los tiempos

        if ($metas->count() > 0) {
            foreach ($metas as $meta) {
                //Asignar tiempo estándar
                $processName = $this->get_processName($meta->proceso);
                $tiemposClase = $allTiemposDeClases->get($meta->id_clase, collect());
                $tiempo = $tiemposClase->firstWhere('proceso', $processName);
                $meta->t_estandar = $tiempo->tiempo ?? 0;

                //Calcular las horas de trabajo de cada operador
                if ($tiempo) {
                    $workHrs = $this->calculateHrs($meta->h_inicio, $meta->h_termino);
                    $tiempo = $tiempo->tiempo != 0 ? round(($workHrs / $tiempo->tiempo)) : 0;
                    $meta->meta = $tiempo; //Asignar la meta calculada
                } else {
                    $meta->meta = 0; //Si no se encuentra el tiempo, se asigna 0 a la meta
                }
                $meta->save(); //Guardar los cambios en la base de datos
            }
        }
    }
        /**
     * @param mixed $h_inicio
     * @param mixed $h_termino
     */
    public function calculateHrs($h_inicio, $h_termino) //Función para calcular las horas trabajadas.
    {
        // $carbon1 = Carbon::createFromFormat('H:i', $h_inicio);
        $carbon1 = Carbon::parse($h_inicio);
        $carbon2 = Carbon::parse($h_termino);
        // $carbon2 = Carbon::createFromFormat('H:i', $h_termino);

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
     * @param mixed $processName
     */
    public function get_processName($processName)
    {
        $process = match ($processName) {
            'Cepillado' => 'cepillado',
            'Desbaste Exterior' => 'desbaste',
            'Revision Laterales' => 'revLaterales',
            'Primera Operacion' => 'primeraOpeSoldadura',
            'Barreno Maniobra' => 'barrenoManiobra',
            'Segunda Operacion' => 'segundaOpeSoldadura',
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
            'Operacion Equipo' => 'operacionEquipo',
            'Operacion Equipo_1 operacion' => 'operacionEquipo',
            'Operacion Equipo_2 operacion' => 'operacionEquipo',
            'Embudo CM' => 'embudoCM',
            'Soldadura' => 'soldadura',
            'Soldadura PTA' => 'soldaduraPTA',
            'Primera Operacion Cabeza Soplo' => 'primeraOperacionCabezaSoplo',
            'Segunda Operacion Cabeza Soplo' => 'segundaOperacionCabezaSoplo',
        };
        return $process;
    }

        /**
     * @param mixed $processName
     */
    public function get_processNormalName($processName)
    {
        $process = match ($processName) {
            'cepillado' => 'Cepillado',
            'desbaste' => 'Desbaste Exterior',
            'revLaterales' => 'Revision Laterales',
            'primeraOpeSoldadura' => 'Primera Operacion',
            'barrenoManiobra' => 'Barreno Maniobra',
            'segundaOpeSoldadura' => 'Segunda Operacion',
            'rectificado' => 'Rectificado',
            'asentado' => 'Asentado',
            'revCalificado' => 'Calificado',
            'acabadoBombillo' => 'Acabado Bombillo',
            'acabadoMolde' => 'Acabado Molde',
            'barrenoProfundidad' => 'Barreno Profundidad',
            'cavidades' => 'Cavidades',
            'copiado' => 'Copiado',
            'offset' => 'Off Set',
            'palomas' => 'Palomas',
            'rebajes' => 'Rebajes',
            'grabado' => 'Grabado',
            'operacionEquipo' => 'Operacion Equipo',
            'operacionEquipo_1' => 'Operacion Equipo_1',
            'operacionEquipo_2' => 'Operacion Equipo_2',
            'embudoCM' => 'Embudo CM',
            'soldadura' => 'Soldadura',
            'soldaduraPTA' => 'Soldadura PTA',
            'primeraOperacionCabezaSoplo' => 'Primera Operacion Cabeza Soplo',
            'segundaOperacionCabezaSoplo' => 'Segunda Operacion Cabeza Soplo',
        };
        return $process;
    }
}
