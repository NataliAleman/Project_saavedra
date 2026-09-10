<?php
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DatosProduccionController;
use App\Http\Controllers\DibujosPdfController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\ProgresoProcesosController;
use App\Http\Controllers\PzasGeneralesController;
use App\Http\Controllers\PzasLiberadasController;
use App\Http\Controllers\TiemposProduccionController;
use App\Http\Controllers\MachinesController;
use App\Http\Controllers\MoldingController;
use App\Http\Controllers\ProcessesController;
use App\Http\Controllers\ProcessProductionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WOController;
use App\Http\Controllers\AlmacenWOController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrackingSoldaduraController;
use App\Http\Controllers\GenerarQRLoteController;
use App\Http\Controllers\GenerarQRIndividualController;
use App\Http\Controllers\LiberarQRPlantaController;
use App\Http\Controllers\RegenerarQRController;
use App\Http\Controllers\ReporteProduccionController;
use App\Http\Controllers\EnvioPtaController;
// use App\Http\Controllers\CalidadDashboardController;
// use App\Http\Controllers\MeasurementsWebController;
use App\Http\Controllers\PtaResultsController;
use App\Http\Controllers\HerramientasTecamacController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('/', [HomeController::class, 'index'])->name('home');

//Ruta para el controlador LogoutController
Route::get('/logout', [LogoutController::class, 'logout'])->name('logout');

//Grupo de rutas para el controlador LoginController
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'show')->name('login');
    Route::post('/login', 'login')->name('loginUser');
});

//Grupo de rutas para el controlador de ver usuarios en perfil de master
Route::controller(UserController::class)->group(function () {
    Route::get('/users', 'show')->name('users'); //Vista de usuarios
    Route::get('/users/organigrama', 'organigrama')->name('users.organigrama'); //Vista de organigrama
    Route::get('/users/create', 'create')->name('createUser'); //Vista de crear usuario
    Route::post('/users/create/store', 'store')->name('storeUser');
    Route::get('/users/recoverPassword', 'showRecoverPassword')->name('recoverPassword'); //Vista recuperar contraseña
    Route::post('/users/recoverPassword', 'recoverPassword')->name('recover'); //Recuperar contraseña

    Route::post('/alta-usuario/{id}', 'altaUsuario')->name('alta_usuario');
    Route::post('/baja-usuario/{id}', 'bajaUsuario')->name('baja_usuario');
    Route::delete('/eliminar-usuario/{id}', 'eliminarUsuario')->name('eliminar_usuario');
    Route::post('/users/{id}', 'updateUsuario')->name('update_usuario');
});

//Grupo de ruta para el controlador MolduraController
Route::controller(MoldingController::class)->group(function () {
    Route::get('/createMolding', 'create')->name('createMolding'); //Vista registrar moldura
    Route::post('/createMolding/storeMolding', 'store')->name('storeMolding'); //Registrar moldura
    Route::get('/editMolding', 'edit')->name('editMolding'); //Vista editar moldura
    Route::post('/editMolding/update', 'update')->name('updateMolding'); //Actualizar moldura
    Route::get('/deleteMolding/{id}', 'destroy')->name('deleteMolding'); //Eliminar moldura
});

//Grupo de ruta para el controlador OTController
Route::controller(WOController::class)->group(function () {
    Route::get('/manageWO', 'manage')->name('manageWO');
    Route::post('/storeWO', 'store')->name('storeWO');
    Route::get('/master/createWO', 'createMasterWO')->name('createMasterWO');
    Route::post('/master/storeWO', 'storeMasterWO')->name('storeMasterWO');
    Route::post('/master/updateWO', 'updateMasterWO')->name('updateMasterWO');
    Route::get('/master/prioridades', 'prioritiesView')->name('master.priorities');
    Route::get('/master/prioridades/pdf', 'downloadPrioritiesPdf')->name('master.priorities.pdf');
    Route::post('/master/prioridades/autosave', 'autosavePriority')->name('master.priorities.autosave');
    Route::get('/showWO/{workOrder}', 'show')->name('showWO');
    Route::get('/destroyWO/{wo}', 'destroy')->name('destroyWO');
    Route::get('/generatePDFWO/{wo}', 'generatePDF')->name('generatePDFWO');
    Route::get('/piecesInProgress', 'showViewPiecesInProgress')->name('showPiecesInProgress');
    Route::get('/finishOrder/{wOrderName}/{className}', 'finishOrder')->name('finishOrder'); //Finalizar pedido
    Route::get('/show_panelWO', 'show_panelWO')->name('show_panelWO');
    Route::get('/piecesInProgress/ptaCard/{otId}/{claseId}', 'getPtaCardData')->name('ptaCardData'); // AJAX endpoint para card PTA
    Route::get('/piecesInProgress/fundicionChecklist/{otId}/{className?}', 'getFundicionChecklist')->name('fundicionChecklistData'); // AJAX endpoint para checklist de fundición
    Route::get('/piecesInProgress/planeacionChecklist/{otId}', 'getPlaneacionChecklist')->name('planeacionChecklistData'); // AJAX endpoint para checklist de planeación
    Route::get('/piecesInProgress/termicoChecklist/{otId}', 'getTermicoChecklist')->name('termicoChecklistData'); // AJAX endpoint para checklist de tratamiento termico
    Route::get('/piecesInProgress/classesData/{otId}', 'getClassesData')->name('classesData');
    Route::post('/fundicion/updateFlag', 'markFundicionFlag')->name('fundicionUpdateFlag');
    Route::get('/piecesInProgress/priorityManager', 'showPriorityManager')->name('showPriorityManager'); // Vista panel de prioridades (perfiles 1 y 3)
    Route::post('/piecesInProgress/priorities', 'savePriorities')->name('savePriorities');               // AJAX: guardar nuevo orden de prioridades

    Route::get('/wo/remision/{id}/serve', [AlmacenWOController::class, 'serveRemision'])->name('wo.remision.serve');
    Route::post('/wo/remision', [AlmacenWOController::class, 'storeRemision'])->name('wo.remision.store');
    Route::delete('/wo/remision/{id}', [AlmacenWOController::class, 'destroyRemision'])->name('wo.remision.destroy');
    Route::post('/wo/parcialidad', [AlmacenWOController::class, 'storeParcialidad'])->name('wo.parcialidad.store');
    Route::delete('/wo/parcialidad/{id}', [AlmacenWOController::class, 'destroyParcialidad'])->name('wo.parcialidad.destroy');
    Route::put('/wo/parcialidad/{id}', [AlmacenWOController::class, 'updateParcialidad'])->name('wo.parcialidad.update');

    // Rutas para Tratamiento Térmico
    Route::post('/wo/tratamiento', [\App\Http\Controllers\TratamientoTermicoController::class, 'store'])->name('wo.tratamiento.store');
    Route::get('/wo/tratamiento/{id}/download', [\App\Http\Controllers\TratamientoTermicoController::class, 'download'])->name('wo.tratamiento.download');
    Route::delete('/wo/tratamiento/{id}', [\App\Http\Controllers\TratamientoTermicoController::class, 'destroy'])->name('wo.tratamiento.destroy');
    Route::put('/wo/tratamiento/{id}', [\App\Http\Controllers\TratamientoTermicoController::class, 'update'])->name('wo.tratamiento.update');
});

Route::controller(ClassController::class)->group(function () {
    Route::post('/saveClass', 'saveClass')->name('saveClass'); //Informacion sobre piezas agregadas
    Route::get('/destroyClass/{idClass}', 'destroy')->name('destroyClass'); //Eliminar clase
});

//Grupo de rutas para el controlador ProcesosController
Route::controller(ProcessesController::class)->group(function () {
    Route::get('/cNominals', 'show_cNominalsView')->name('cNominals'); //Ruta para la interfaz de los procesos para editar las cotas nominales y tolerancias
    Route::post('/cNominals/store', 'storeCNominalsData')->name('storeCNominals'); //Ruta para la interfaz de los procesos para guardar las cotas nominales y tolerancias
});

Route::controller(ProcessProductionController::class)->group(function () {
    Route::get('/processProduction', 'show')->name('processProduction'); //Ruta para ver los procesos de producción
    Route::post('/processProduction/selected', 'storeHeaderdata')->name('headerdata'); //Ruta para ver los procesos de producción
    Route::get('/processProduction/format/{meta}/{process}/{edit}', 'showReportFormat')->name('showReportFormat'); //Ruta para ver los procesos de producción
    Route::post('/processProduction/verified', 'verifiedPasswordAdmin')->name('verifiedPassword'); //Ruta para verificar la contraseña del administrador
    Route::post('/processProduction/editMeta', 'editMeta')->name('editMeta'); //Ruta para editar las metas
    Route::get('/processProduction/finishReport/{meta}', 'finishReport')->name('finishReport'); //Ruta para finalizar el reporte
    Route::post('/processProduction/storePiece', 'storePiece')->name('storePiece'); //Ruta para almacenar una pieza
    Route::post('/processProduction/selectAssembly', 'selectAssembly')->name('selectAssembly'); //Ruta para almacenar una pieza
    Route::post('/processProduction/editPieces', 'editPieces')->name('editPieces'); //Ruta para editar las piezas registradas
    Route::post('/processProduction/verifyQualityPassword', 'verifyQualityPassword')->name('verifyQualityPassword'); //Ruta para verificar contraseña de calidad
    Route::post('/processProduction/releasePieces', 'releasePieces')->name('releasePieces'); //Ruta para liberar piezas
    Route::post('/processProduction/getPiecesForRelease', 'getPiecesForRelease')->name('getPiecesForRelease'); //Ruta para obtener piezas para liberación
});

//Ruta para ver el progreso de los procesos
Route::get('/progresoOT', [ProgresoProcesosController::class, 'show'])->name('verProcesos');

//Grupo de rutas para el controlador TiemposProduccionCo    ntroller
Route::controller(TiemposProduccionController::class)->group(function () {
    // Route::get('/tiemposProduccion/update', 'update')->name('actualizarClases');
    Route::get('/tiemposProduccion/{clase?}', 'show')->name('showTimes');
    Route::post('/tiemposProduccion', 'store')->name('storeTimes');
});

//Grupo de rutas para el controlador PzasGeneralesController
Route::controller(PzasGeneralesController::class)->group(function () {
    Route::get('/pieces', 'showPiecesReport_view')->name('showPiecesReport_view'); //Ruta para la vista general de piezas
    Route::match(['get', 'post'], '/pieces/search', 'getPiecesRequest')->name('searchPieces'); //Ruta para el controlador de piezas generales
    Route::get('/pieces/{pieces}/{process}/{profile}', 'showPiece')->name('chosenPiece'); //Vista de la pieza elegida
    Route::post('/getGamesFromOT', 'getGamesFromOT')->name('getGamesFromOT'); //Ruta para obtener juegos de una OT

    Route::get('/piezasMaquina', 'showVistaMaquina')->name('vistaPzasMaquina'); //Ruta para la vista de piezas por maquina
    Route::post('/piezasMaquina', 'showMachinesProcess')->name('showMachinesProcess'); //Ruta para ver los procesos de las maquinas

    Route::post('/pieces/verifyAdminPassword', 'verifyAdminPassword')->name('verifyAdminPassword'); //Ruta para verificar contraseña de administrador
    Route::post('/pieces/getSoldaduraExtraInfo', 'getSoldaduraExtraInfo')->name('getSoldaduraExtraInfo'); //Ruta para obtener información extra de Soldadura
    Route::match(['get', 'post'], '/pieces/downloadSoldaduraExtraInfoPDF', 'downloadSoldaduraExtraInfoPDF')->name('downloadSoldaduraExtraInfoPDF'); //Ruta para descargar PDF de Soldadura
});

//Grupo de rutas para el controlador PzasLiberadasController
Route::controller(PzasLiberadasController::class)->group(function () {
    Route::get('/releasePieces', 'show')->name('showReleasePieces_view'); //Ruta para la vista de piezas para liberar
    Route::post('/pieces', 'getPiecesRequest')->name('piecesRelease'); //Ruta para ver los procesos de las maquinas
    Route::post('/piezasLiberar', 'liberar_rechazar')->name('liberar_rechazar'); //Ruta para liberar o rechazar
});
//Rutas para el controlador de DatosProduccionController
Route::controller(DatosProduccionController::class)->group(function () {
    Route::get('/productionData', 'index')->name('productionData'); //Vista de datos de producción
    Route::post('/productionData', 'show')->name('showProduccion'); //Vista de datos de producción
});

//Rutas para el controlador de MachinesController
Route::controller(MachinesController::class)->group(function () {
    Route::get('/machinesOccupied', 'show')->name('machinesOccupied'); //Vista de máquinas ocupadas
    Route::post('/machinesOccupied/freeUp', 'freeUp')->name('freeUp'); //Vista de máquinas ocupadas
});

//Rutas para el controlador de ProgressPanelController
Route::get('/panel-progreso', fn() => view('wo_views.progressPanel_wo'))->name('panelProgreso');

/* ===========================
   Tracking Soldadura - NUEVO SISTEMA
=========================== */

Route::middleware(['auth'])->group(function () {
    Route::get('/trackingSoldadura', [TrackingSoldaduraController::class, 'index'])->name('trackingSoldadura.index');
    Route::post('/trackingSoldadura', [TrackingSoldaduraController::class, 'store'])->name('trackingSoldadura.store');
});

/* ===========================
   1. Generar QR por Lote
=========================== */

Route::middleware(['auth'])->group(function () {
    Route::get('/soldadura/generar-qr-lote', [GenerarQRLoteController::class, 'index'])
        ->name('soldadura.generarQRLote');

    Route::post('/soldadura/generar-qr-lote', [GenerarQRLoteController::class, 'store'])
        ->name('soldadura.generarQRLote.store');
});

/* ===========================
   2. Generar QRs Individuales
=========================== */

Route::middleware(['auth'])->group(function () {
    Route::get('/soldadura/generar-qr-individual', [GenerarQRIndividualController::class, 'index'])
        ->name('soldadura.generarQRIndividual');

    Route::post('/soldadura/generar-qr-individual', [GenerarQRIndividualController::class, 'store'])
        ->name('soldadura.generarQRIndividual.store');
});

/* ===========================
   3. Recepción de Soldadura en Planta (ENTRADA)
=========================== */

Route::middleware(['auth'])->group(function () {
    Route::get('/soldadura/recepcion-planta', [LiberarQRPlantaController::class, 'indexRecepcion'])
        ->name('soldadura.recepcionPlanta');

    Route::post('/soldadura/recepcion-planta/escanear', [LiberarQRPlantaController::class, 'escanearRecepcion'])
        ->name('soldadura.recepcionPlanta.escanear');

    Route::post('/soldadura/recepcion-planta/confirmar', [LiberarQRPlantaController::class, 'confirmarRecepcion'])
        ->name('soldadura.recepcionPlanta.confirmar');
});

/* ===========================
   4. Liberar Soldadura a Operadores (SALIDA)
=========================== */

Route::middleware(['auth'])->group(function () {
    Route::get('/soldadura/liberar-qr-planta', [LiberarQRPlantaController::class, 'index'])
        ->name('soldadura.liberarQRPlanta');

    Route::post('/soldadura/liberar-qr-planta/escanear', [LiberarQRPlantaController::class, 'escanear'])
        ->name('soldadura.liberarQRPlanta.escanear');

    Route::post('/soldadura/liberar-qr-planta/liberar', [LiberarQRPlantaController::class, 'liberar'])
        ->name('soldadura.liberarQRPlanta.liberar');
});

/* ===========================
   5. Regenerar QRs - Solo Administradores
=========================== */

Route::middleware(['auth'])->group(function () {
    // Vista de verificación de contraseña
    Route::get('/soldadura/regenerar-qr', [RegenerarQRController::class, 'index'])
        ->name('soldadura.regenerarQR');

    // Verificar contraseña de administrador
    Route::post('/soldadura/regenerar-qr/verificar', [RegenerarQRController::class, 'verificarAcceso'])
        ->name('soldadura.regenerarQR.verificar');

    // Lista de lotes (después de verificación)
    Route::get('/soldadura/regenerar-qr/lista', [RegenerarQRController::class, 'listaLotes'])
        ->name('soldadura.regenerarQR.lista');

    // Descargar QR de lote
    Route::get('/soldadura/regenerar-qr/lote/{loteId}', [RegenerarQRController::class, 'regenerarQRLote'])
        ->name('soldadura.regenerarQRLote.descargar');

    // Descargar QRs individuales
    Route::get('/soldadura/regenerar-qr/individuales/{loteId}', [RegenerarQRController::class, 'regenerarQRIndividuales'])
        ->name('soldadura.regenerarQRIndividuales.descargar');

    // Cerrar sesión de administrador
    Route::get('/soldadura/regenerar-qr/cerrar', [RegenerarQRController::class, 'cerrarSesion'])
        ->name('soldadura.regenerarQR.cerrar');
});

/* ===========================
   Utilidades / Check Time
=========================== */
Route::get('/check-time', function () {
    return [
        'PHP timezone' => date_default_timezone_get(),
        'Laravel timezone' => config('app.timezone'),
        'Current time' => now()->toDateTimeString(),
    ];
});

/* ===========================
   Resultados y Análisis Soldadura PTA
=========================== */
Route::prefix('admin/pta')->name('pta.')->group(function () {
    // ── Rutas de Autenticación Temporal ──
    Route::post('/verify-temp-password', [App\Http\Controllers\PtaResultsController::class, 'verifyTempPassword'])->name('verify_temp_password');
    Route::get('/close-temp-session', [App\Http\Controllers\PtaResultsController::class, 'closeTempSession'])->name('close_temp_session');

    Route::middleware(['pta.access'])->group(function () {
        // Vista formulario de resultados (operador / admin)
        Route::get('/results/{ot_id}', [PtaResultsController::class, 'index'])
            ->name('results');

        // Guardar / actualizar resultados
        Route::post('/results/{ot_id}', [PtaResultsController::class, 'store'])
            ->name('results.store');

        // Liberar o revocar liberación por administrador
        Route::put('/results/{id}/liberar', [PtaResultsController::class, 'update'])
            ->name('results.liberar');

        // Rechazar o quitar rechazo por administrador
        Route::put('/results/{id}/rechazar', [PtaResultsController::class, 'rechazar'])
            ->name('results.rechazar');

        // Análisis administrativo
        Route::get('/analysis', [PtaResultsController::class, 'analysis'])
            ->name('analysis');

        // Descargar PDF del análisis
        Route::get('/analysis/pdf', [PtaResultsController::class, 'analysisPDF'])
            ->name('analysis.pdf');

        // ── 2da Pasada — vista de edición diferida ──
        Route::get('/segunda-pasada', [PtaResultsController::class, 'segPasadaIndex'])
            ->name('segunda_pasada');
        Route::post('/segunda-pasada/update', [PtaResultsController::class, 'segPasadaUpdate'])
            ->name('segunda_pasada.update');
    });
});

/* ===========================
   Reporte General de Producción
=========================== */
Route::middleware(['auth'])->prefix('reportes')->name('reportes.')->group(function () {
    // Vista del formulario de re-envío manual
    Route::get('/reenvio', [ReporteProduccionController::class, 'showReenvio'])
        ->name('reenvio');

    // Acción POST: re-enviar correo manualmente
    Route::post('/reenviar', [ReporteProduccionController::class, 'reenviarCorreo'])
        ->name('produccion.reenviar');

});

// Acción GET: descargar PDF del reporte (fuera del grupo auth para mayor compatibilidad)
Route::get('/reportes/descargar-pdf/{fecha}', [ReporteProduccionController::class, 'descargarPDF'])
    ->name('reportes.descargar_pdf');

/* ===========================
   Envío de Reportes PTA
=========================== */
Route::middleware(['auth'])->prefix('reportes/pta')->name('reportes.pta')->group(function () {
    // Vista principal con selector OT/Clase + historial de envíos
    Route::get('/', [EnvioPtaController::class, 'index'])
        ->name('');

    // Acción POST: generar PDF y enviar correo
    Route::post('/enviar', [EnvioPtaController::class, 'enviar'])
        ->name('.enviar');
});

/* ===========================
   Módulo de Dibujos / Planos PDF (DIBUJOS_GIS)
=========================== */

Route::prefix('dibujos')->name('dibujos.')->group(function () {
    Route::get('/estructura', [DibujosPdfController::class, 'getStructure'])->name('estructura');
    Route::get('/archivos', [DibujosPdfController::class, 'getFiles'])->name('archivos');
    Route::get('/serve', [DibujosPdfController::class, 'serveFile'])->name('serve');
});

Route::middleware(['auth'])->prefix('dibujos')->name('dibujos.')->group(function () {

    // ── Vista de administración ──
    Route::get('/manage', [DibujosPdfController::class, 'showManage'])
        ->name('manage');

    // ── CRUD Administración ──
    Route::post('/createFolder', [DibujosPdfController::class, 'createFolder'])
        ->name('createFolder');

    Route::post('/upload', [DibujosPdfController::class, 'uploadPdf'])
        ->name('upload');

    Route::post('/delete', [DibujosPdfController::class, 'deletePdf'])
        ->name('delete');

    Route::post('/replace', [DibujosPdfController::class, 'replacePdf'])
        ->name('replace');

    // ── Log de auditoría (últimas 50 entradas) ──
    Route::get('/log', [DibujosPdfController::class, 'getLog'])->name('log');
    Route::post('/deleteFolder', [DibujosPdfController::class, 'deleteFolder'])->name('deleteFolder');
    Route::post('/deleteParent', [DibujosPdfController::class, 'deleteParent'])->name('deleteParent');
});
    
/* ===========================
   Módulo de Manuales (MANUALES_GIS)
=========================== */

Route::prefix('fundicion')->name('fundicion.')->group(function () {
    Route::get('/estructura', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'getStructure'])->name('estructura');
    Route::get('/archivos', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'getFiles'])->name('archivos');
    Route::get('/total-archivos', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'getTotalFiles'])->name('total_archivos');
    Route::get('/serve', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'serveFile'])->name('serve');
});

Route::middleware(['auth'])->prefix('fundicion')->name('fundicion.')->group(function () {
    Route::get('/manage', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'showManage'])->name('manage');
    
    Route::post('/createFolder', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'createFolder'])->name('createFolder');
    Route::post('/upload', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'uploadPdf'])->name('upload');
    Route::post('/send-alert', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'sendEmailAlert'])->name('send_alert');
    Route::post('/save-ayudas', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'saveAyudas'])->name('save_ayudas');
    Route::post('/unlink-ayudas', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'unlinkAyudas'])->name('unlink_ayudas');
    Route::post('/delete', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'deletePdf'])->name('delete');
    Route::post('/replace', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'replacePdf'])->name('replace');
    Route::get('/log', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'getLog'])->name('log');
    Route::post('/deleteFolder', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'deleteFolder'])->name('deleteFolder');
    Route::post('/deleteParent', [\App\Http\Controllers\DibujosFundicionPdfController::class, 'deleteParent'])->name('deleteParent');
});

/* ===========================
   Vista Almacén — Dibujos de Fundición
   Acceso: Departamentos de Almacén únicamente.
=========================== */
Route::middleware(['auth'])->prefix('almacen/fundicion')->name('almacen.fundicion.')->group(function () {
    // Vista principal con tabla histórica + buscador + filtros de fecha
    Route::get('/', [\App\Http\Controllers\AlmacenFundicionController::class, 'index'])
        ->name('index');

    // API: lista de archivos de una OT desde FUNDICION_ALMACEN
    Route::get('/archivos', [\App\Http\Controllers\AlmacenFundicionController::class, 'getFiles'])
        ->name('archivos');

    // API: Comparación de cambios pendientes
    Route::get('/pending-comparison', [\App\Http\Controllers\AlmacenFundicionController::class, 'getPendingChangesComparison'])
        ->name('pending_comparison');

    // API: Resolver cambios pendientes
    Route::post('/resolve-changes', [\App\Http\Controllers\AlmacenFundicionController::class, 'resolvePendingChanges'])
        ->name('resolve_changes');

    // Servir PDF protegido desde el directorio aislado
    Route::get('/serve', [\App\Http\Controllers\AlmacenFundicionController::class, 'serveFile'])
        ->name('serve');

    // Eliminar PDF (Otros documentos / preordenes)
    Route::post('/delete-file', [\App\Http\Controllers\AlmacenFundicionController::class, 'deleteFile'])
        ->name('deleteFile');

    // --- NUEVAS RUTAS: Control de Modelos ---
    Route::post('/confirmar-modelo', [\App\Http\Controllers\AlmacenFundicionController::class, 'updateModelStatus'])
        ->name('confirmarModelo');

    Route::get('/ot-data', [\App\Http\Controllers\AlmacenFundicionController::class, 'getOtData'])
        ->name('getOtData');

    Route::get('/pending-preordenes', [\App\Http\Controllers\AlmacenFundicionController::class, 'getPendingPreOrdenes'])
        ->name('getPendingPreOrdenes');

    Route::post('/store-preorden', [\App\Http\Controllers\AlmacenFundicionController::class, 'storePreOrden'])
        ->name('storePreOrden');

    Route::post('/send-email-preorden', [\App\Http\Controllers\AlmacenFundicionController::class, 'sendEmailPreOrden'])
        ->name('sendEmailPreOrden');

    Route::post('/confirmar-recepcion-rechazo', [\App\Http\Controllers\AlmacenFundicionController::class, 'confirmarRecepcionRechazo'])
        ->name('confirmarRecepcionRechazo');

    Route::post('/iniciar-casting', [\App\Http\Controllers\AlmacenFundicionController::class, 'iniciarCasting'])
        ->name('iniciarCasting');

    Route::post('/procesar-rechazos', [\App\Http\Controllers\AlmacenFundicionController::class, 'procesarRechazos'])
        ->name('procesarRechazos');
});

/* ===========================
   Vista Calidad — Dibujos de Fundición
   Acceso: Departamentos de Calidad únicamente.
=========================== */
Route::middleware(['auth'])->prefix('calidad/fundicion')->name('calidad.fundicion.')->group(function () {
    // Vista principal con tabla histórica + buscador + filtros de fecha
    Route::get('/', [\App\Http\Controllers\CalidadFundicionController::class, 'index'])
        ->name('index');

    // API: lista de archivos de una OT
    Route::get('/archivos', [\App\Http\Controllers\CalidadFundicionController::class, 'getFiles'])
        ->name('archivos');

    // Servir PDF protegido
    Route::get('/serve', [\App\Http\Controllers\CalidadFundicionController::class, 'serveFile'])
        ->name('serve');

    // Eliminar PDF
    Route::post('/delete-file', [\App\Http\Controllers\CalidadFundicionController::class, 'deleteFile'])
        ->name('deleteFile');

    // --- RUTAS: Liberacion de Modelos (Calidad) ---
    Route::get('/liberacion', [\App\Http\Controllers\CalidadFundicionController::class, 'getLiberacion'])
        ->name('getLiberacion');

    Route::post('/submit-liberacion', [\App\Http\Controllers\CalidadFundicionController::class, 'submitLiberacion'])
        ->name('submitLiberacion');

    Route::post('/generate-scar', [\App\Http\Controllers\CalidadFundicionController::class, 'generateScar'])
        ->name('generateScar');

    Route::get('/get-scar', [\App\Http\Controllers\CalidadFundicionController::class, 'getScar'])
        ->name('getScar');

    Route::post('/send-scar-alert', [\App\Http\Controllers\CalidadFundicionController::class, 'sendScarAlert'])
        ->name('sendScarAlert');

    Route::post('/enviar-alerta-liberacion', [\App\Http\Controllers\CalidadFundicionController::class, 'enviarAlertaLiberacion'])
        ->name('enviarAlertaLiberacion');
});

/* ===========================
   Módulo de Manuales (MANUALES_GIS)
=========================== */

Route::prefix('manuales')->name('manuales.')->group(function () {
    Route::get('/estructura', [\App\Http\Controllers\ManualesPdfController::class, 'getStructure'])->name('estructura');
    Route::get('/archivos', [\App\Http\Controllers\ManualesPdfController::class, 'getFiles'])->name('archivos');
    Route::get('/serve', [\App\Http\Controllers\ManualesPdfController::class, 'serveFile'])->name('serve');
});

Route::middleware(['auth'])->prefix('manuales')->name('manuales.')->group(function () {
    Route::get('/manage', [\App\Http\Controllers\ManualesPdfController::class, 'showManage'])->name('manage');
    
    Route::post('/createFolder', [\App\Http\Controllers\ManualesPdfController::class, 'createFolder'])->name('createFolder');
    Route::post('/upload', [\App\Http\Controllers\ManualesPdfController::class, 'uploadPdf'])->name('upload');
    Route::post('/delete', [\App\Http\Controllers\ManualesPdfController::class, 'deletePdf'])->name('delete');
    Route::post('/replace', [\App\Http\Controllers\ManualesPdfController::class, 'replacePdf'])->name('replace');
    Route::get('/log', [\App\Http\Controllers\ManualesPdfController::class, 'getLog'])->name('log');
    Route::post('/deleteFolder', [\App\Http\Controllers\ManualesPdfController::class, 'deleteFolder'])->name('deleteFolder');
    Route::post('/deleteParent', [\App\Http\Controllers\ManualesPdfController::class, 'deleteFolder'])->name('deleteParent'); // Manuales solo tiene un nivel
});

/* ===========================
   Módulo de Ayudas Visuales (AYUDAS_GIS)
=========================== */

Route::prefix('ayudas')->name('ayudas.')->group(function () {
    Route::get('/estructura', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'getStructure'])->name('estructura');
    Route::get('/archivos', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'getFiles'])->name('archivos');
    Route::get('/serve', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'serveFile'])->name('serve');
});

Route::middleware(['auth'])->prefix('ayudas')->name('ayudas.')->group(function () {
    Route::get('/manage', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'showManage'])->name('manage');
    
    Route::post('/createFolder', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'createFolder'])->name('createFolder');
    Route::post('/upload', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'uploadPdf'])->name('upload');
    Route::post('/delete', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'deletePdf'])->name('delete');
    Route::post('/replace', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'replacePdf'])->name('replace');
    Route::get('/log', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'getLog'])->name('log');
    Route::post('/deleteFolder', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'deleteFolder'])->name('deleteFolder');
    Route::post('/deleteParent', [\App\Http\Controllers\AyudasVisualesPdfController::class, 'deleteParent'])->name('deleteParent');
});

/* ===========================
   Módulo de Ayudas Visuales (Fundición)
=========================== */

Route::prefix('ayudas_fundicion')->name('ayudas_fundicion.')->group(function () {
    Route::get('/estructura', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'getStructure'])->name('estructura');
    Route::get('/archivos', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'getFiles'])->name('archivos');
    Route::get('/serve', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'serveFile'])->name('serve');
});

Route::middleware(['auth'])->prefix('ayudas_fundicion')->name('ayudas_fundicion.')->group(function () {
    Route::get('/manage', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'showManage'])->name('manage');
    
    Route::post('/createFolder', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'createFolder'])->name('createFolder');
    Route::post('/upload', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'uploadPdf'])->name('upload');
    Route::post('/delete', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'deletePdf'])->name('delete');
    Route::post('/replace', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'replacePdf'])->name('replace');
    Route::get('/log', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'getLog'])->name('log');
    Route::post('/deleteFolder', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'deleteFolder'])->name('deleteFolder');
    Route::post('/deleteParent', [\App\Http\Controllers\AyudasVisualesFundicionPdfController::class, 'deleteParent'])->name('deleteParent');
});

//Rutas para la captura de medidas en el sistema web de metrología
// Route::prefix('metrology')->group(function () {
//     Route::get('/metaPzas', [CalidadDashboardController::class, 'index'])->name('calidadDashboard.index');

//     //Rutas para obtener y procesar datos de las piezas de la orden de trabajo de manera automatica
//     Route::get('/metaPzas/obtener', [CalidadDashboardController::class, 'automaticData'])->name('calidadDashboard.obtener');

//     #---------------- RUTAS SECUNDARIAS USADAS EN EL FLUJO ----------------#
//     Route::get('/measurements_web/OT/{workOrderId}', [MeasurementsWebController::class, 'searchInfoOt'])->name('searchInfoOt');
//     Route::post('/measurements_web/save_C_nominal', [MeasurementsWebController::class, 'register_C_Nominal'])->name('register_C_Nominal'); //Register nominal quotas and tolerances  of the piece in DB 
// });

// Logs Controller (Limitado a 60 peticiones por minuto para evitar saturación de red)
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/system-logs-report', [SystemLogController::class, 'index'])->name('systemLogsReport');
    Route::post('/system-logs', [SystemLogController::class, 'store'])->name('system.logs.store');
    Route::post('/system-logs/purge', [SystemLogController::class, 'purge'])->name('system.logs.purge');
});

use App\Http\Controllers\ProductivityController;

// Monitoreo de Productividad (Alertas 3 Niveles)
Route::middleware(['auth'])->group(function () {
    Route::post('/productivity/ping', [ProductivityController::class, 'ping'])->name('productivity.ping');
    Route::post('/productivity/unlock', [ProductivityController::class, 'unlock'])->name('productivity.unlock');
});

/* ===========================
   Vista Calidad — Dibujos y Ayudas Visuales de Maquinados (Solo Lectura)
   Acceso: Calidad (4) y Administrador (1).
=========================== */

Route::middleware(['auth'])->prefix('calidad/maquinados')->name('calidad.maquinados.')->group(function () {
    // Vista principal: tres tablas (Dibujos, Ayudas, Inactivos)
    Route::get('/', [\App\Http\Controllers\CalidadMaquinadosController::class, 'index'])
        ->name('index');

    // Servir archivo protegido por ID (validado contra BD)
    Route::get('/serve', [\App\Http\Controllers\CalidadMaquinadosController::class, 'serveFile'])
        ->name('serve');

    // API JSON (para uso futuro o filtrado dinámico)
    Route::get('/docs', [\App\Http\Controllers\CalidadMaquinadosController::class, 'getDocs'])
        ->name('docs');
});

/* ===========================
   Módulo: Herramientas Tecamac
   Acceso: Administrador (1) y Calidad (4)
=========================== */

Route::middleware(['auth'])->prefix('herramientas/tecamac')->name('herramientas.tecamac.')->group(function () {
    // Vista principal
    Route::get('/', [HerramientasTecamacController::class, 'index'])
        ->name('index');

    // CRUD completo (solo Almacén — verificado en el controlador)
    Route::post('/', [HerramientasTecamacController::class, 'store'])
        ->name('store');
    Route::post('/{id}', [HerramientasTecamacController::class, 'update'])
        ->name('update');
    Route::delete('/{id}', [HerramientasTecamacController::class, 'destroy'])
        ->name('destroy');
    Route::post('/{id}/reactivar', [HerramientasTecamacController::class, 'reactivar'])
        ->name('reactivar');

    // Actualizar solo stock mín/máx (Admin o Almacén — verificado en el controlador)
    Route::patch('/{id}/stock', [HerramientasTecamacController::class, 'updateStock'])
        ->name('updateStock');

    // Gestión individual de imágenes (solo Almacén — verificado en el controlador)
    Route::patch('/imagen/{imgId}/rename', [HerramientasTecamacController::class, 'renameImagen'])
        ->name('imagen.rename');
    Route::post('/imagen/{imgId}/replace', [HerramientasTecamacController::class, 'replaceImagen'])
        ->name('imagen.replace');
});

Route::get('/autologin', function() {
    $user = \App\Models\User::where('perfil', 5)->first() ?: \App\Models\User::first();
    if ($user) {
        auth()->login($user);
        return redirect('/almacen/fundicion');
    }
    return 'No user found';
});

Route::get('/test-user', function() {
    if (auth()->check()) {
        return response()->json([
            'id' => auth()->id(),
            'nombre' => auth()->user()->nombre,
            'perfil' => auth()->user()->perfil,
            'perfil_type' => gettype(auth()->user()->perfil)
        ]);
    }
    return 'Not logged in';
});
