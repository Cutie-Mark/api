<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\OlimpiadaController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\PostulanteController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\CronogramaController;
use App\Http\Controllers\NivelCompetenciaController;
use App\Http\Controllers\OrdenPagoController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FaseController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\ServicioController;


Route::get('/rutas', function () {
    return collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
        ];
    })->filter(fn ($route) => str_starts_with($route['uri'], 'api/'))->values();
});

// =========================
//          AREA
// =========================
Route::prefix('/areas')->group(function () {
    Route::post('/', [AreaController::class, 'guardar']);                     // Crear un área
    Route::get('/', [AreaController::class, 'listar']);                      // Obtener todas las áreas
    Route::get('/buscar', [AreaController::class, 'buscar']);                 // Obtener áreas por nombre
    Route::put('/{id}/deactivate', [AreaController::class, 'desactivar']);  // Desactivar un área por ID
    Route::put('/{id}/activate', [AreaController::class, 'activar']);      // Activar un área por ID
    Route::delete('/{id}', [AreaController::class, 'eliminar']);             // Eliminar un área por ID
});



// =========================
//          CATEGORIA
// =========================
Route::prefix('/categorias')->group(function () {
    Route::post('/', [CategoriaController::class, 'guardar']);                    // Crear una categoría
    Route::get('/', [CategoriaController::class, 'listar']);                     // Obtener todas las categorías
    Route::get('/buscar', [CategoriaController::class, 'buscar']);                // Obtener categorías por nombre
    Route::put('/{id}', [CategoriaController::class, 'actualizar']);                // Actualizar una categoría por ID
    Route::put('/{id}/deactivate', [CategoriaController::class, 'desactivar']); // Desactivar una categoría por ID
    Route::put('/{id}/activate', [CategoriaController::class, 'activar']);     // Activar una categoría por ID
    Route::delete('/{id}', [CategoriaController::class, 'eliminar']);            // Eliminar una categoría por ID
});



// =========================
//         CRONOGRAMA
// =========================
Route::prefix('/cronogramas')->group(function () {

    Route::put('/fases/olimpiada', [CronogramaController::class, 'guardarFases']);
    Route::put('/fases/fechas', [CronogramaController::class, 'guardarFechas']);
});


// =========================
//         OLIMPIADA
// =========================
Route::prefix('/olimpiadas')->group(function () {
    Route::get('/', [OlimpiadaController::class, 'listar']);                                         // Obtener todas las olimpiadas
    Route::get('/conFases', [OlimpiadaController::class, 'listarConFases']);                                         // Obtener todas las olimpiadas
    Route::post('/por-tipos', [OlimpiadaController::class, 'listarOlimpiadasPorTipos']); // Obtener olimpiadas por tipos o fases
    Route::get('/hoy', [OlimpiadaController::class, 'listarOlimpiadasEnCurso']);                      // Consultar si hay olimpiada en curso
    //-Route::get('/hoy/{id}', [OlimpiadaController::class, 'getOlimpiadaWithFaseEnCurso']);
    Route::get('/{olimpiada_id}/inscripciones-detalladas', [InscripcionController::class, 'obtenerInscripcionesDetalladasPorOlimpiada']); // Obtener inscripciones detalladas por olimpiada
    Route::get('/{olimpiada_id}/reporteDeInscripciones', [InscripcionController::class, 'obtenerReporteDeInscripciones']); // Obtener inscripciones detalladas por olimpiada
    Route::post('/', [OlimpiadaController::class, 'guardar']);                                        // Crear una olimpiada
    Route::delete('/clearAllPlantillasL', [OlimpiadaController::class, 'limpiarPlantillas']);       // Eliminar url_plantilla de todas las olimpiadas
    Route::get('/{id}', [OlimpiadaController::class, 'mostrar']);                                      // Obtener una olimpiada por ID
    Route::put('/{id}', [OlimpiadaController::class, 'actualizar']);                                    // Actualizar las fechas de una olimpiada
    Route::delete('/{id}', [OlimpiadaController::class, 'eliminar']);                                // Eliminar una olimpiada por ID
    Route::delete('/{id}/plantilla', [OlimpiadaController::class, 'mostrarUrlPlantilla']);             // Obtener la plantilla de una olimpiada por ID
    Route::get('/{id}/cronogramas', [OlimpiadaController::class, 'listarOlimpiadasConCronogramas']);   // Obtener olimpiadas con sus cronogramas
    Route::post('/upload-excel', [OlimpiadaController::class, 'subirExcelFormato']);               // Subir archivo Excel
    Route::get('/{id}/download-excel', [OlimpiadaController::class, 'descargarExcelFormato']);       // Descargar archivo Excel
    //Route::get('/{id}/download-excel', [OlimpiadaController::class, 'downloadExcelFormato']);       // Descargar archivo Excel
                // Obtener olimpiadas futuras
});




// =========================
//       NIVEL COMPETENCIA
// =========================

// Ligar un area a una olimpiada
Route::post('/olimpiada/area', [NivelCompetenciaController::class, 'asignarAreaOlimpiada']);

// Desligar una categoria de un area
Route::delete('/olimpiada/area', [NivelCompetenciaController::class, 'desasignarPorOlimpiadaArea']);

// Desactivas un nivel de competencia
Route::put('/categoria/area/olimpiada/deactivate', [NivelCompetenciaController::class, 'desactivar']);

// Activas un nivel de competencia
Route::put('/categoria/area/olimpiada/activate', [NivelCompetenciaController::class, 'activar']);

// Asignar una categoria a un area
Route::put('/categorias/area/olimpiada', [NivelCompetenciaController::class, 'actualizarMultiplesCategorias']);

// Filtrar categorias con sus areas
Route::get('/categorias/areas/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'listarCategoriasConAreas']);

// Filtras las areas con sus categorias
Route::get('/areas/categorias/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'listarAreasConCategorias']);

// Filtrar las categorias con areas segun curso
Route::get('/categorias/areas/curso/{curso}/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'listarCategoriasPorCurso']);

// Filtrar categorias de una olimpiada ordenadas y agrupadas por grado
Route::get('/categorias/olimpiada/{id}', [NivelCompetenciaController::class, 'listarCategoriasOrdenPorOlimpiada']);

// Obtener areas ligadas a una olimpiada
Route::get('/olimpiadas/{id}/area', [NivelCompetenciaController::class, 'listarAreasPorOlimpiada']);



// =========================
//          COLEGIO
// =========================
Route::prefix('colegios')->group(function () {
    Route::get('/',     [ColegioController::class, 'listar']);      // Listar todos
});



// =========================
//          DEPARTAMENTO
// =========================
Route::prefix('departamentos')->group(function () {
    Route::get('/', [DepartamentoController::class, 'listar']);                                      // Listar departamentos
    Route::get('/with-provinces', [DepartamentoController::class, 'listarConProvincias']);           // Listar con departamentos con provincias

});



// =========================
//          PROVINCIA
// =========================
Route::prefix('provincias')->group(function () {
    Route::get('/', [ProvinciaController::class, 'listar']);         // Listar Provincias

});


// =========================
//          POSTULANTE
// =========================
Route::prefix('postulantes')->group(function () {
    Route::post('/', [PostulanteController::class, 'crear']);       // Crear Postulante
    Route::get('/', [PostulanteController::class, 'listar']);        // Listar Postulantes
    Route::get('/{id}', [PostulanteController::class, 'mostrar']);     // Obtener Postulante
    Route::put('/{id}', [PostulanteController::class, 'actualizar']);   // Editar Postulante
});



// =========================
//          RESPONSABLE
// =========================
Route::prefix('responsables')->group(function () {
    Route::post('/', [ResponsableController::class, 'crear']);       // Crear responsable
    Route::get('/', [ResponsableController::class, 'listar']);        // Listar responsables
    Route::get('/{id}', [ResponsableController::class, 'mostrar']);     // Obtener un responsable
    Route::put('/{id}', [ResponsableController::class, 'actualizar']);   // Actualizar un responsable
    Route::get('/ci/{ci}', [ResponsableController::class, 'mostrarPorCi']); // Obtener responsable por CI
});



// =========================
//          LISTA
// =========================
Route::prefix('listas')->group(function () {
    Route::post('/', [ListaController::class, 'crear']);                                // Crear lista
    Route::get('/', [ListaController::class, 'listar']);                                 // Listar todas las listas
    Route::get('/{id}', [ListaController::class, 'mostrar']);                              // Mostrar lista por ID
    Route::get('/codigo/{codigo}', [ListaController::class, 'mostrarPorCodigo']);           // Mostrar lista por código
    Route::put('/{codigo}/estado', [ListaController::class, 'ActualizarEstado']);           // Actualizar estado de una lista
    Route::get('/responsable/{ci}', [ListaController::class, 'mostrarResponsablePorCI']);    // Listas de un responsable (por CI)
    Route::get('/estado/{estado}', [ListaController::class, 'mostrarListasPorEstado']);      // Listas por estado
    Route::get('/responsable/{ci}/estado/{estado}',[ListaController::class, 'mostraListasPorEstadoYResponsable']);// Listas de un responsable y estado
    Route::get('/olimpiada/{olimpiadaId}', [ListaController::class, 'mostrarPorOlimpiada']); // Mostrar listas de una olimpiada
    Route::delete('/{codigo}/eliminar', [ListaController::class, 'eliminarListaVacia']);
});


// =========================
//          INSCRIPCION
// =========================
Route::prefix('inscripciones')->group(function () {
    Route::post('/', [InscripcionController::class, 'crear']);
    Route::get('/{id}', [InscripcionController::class, 'mostrar']);
    Route::get('/olimpiada/{olimpiadaId}/estado/{estado}', [InscripcionController::class, 'obtenerPorEstado']);
    Route::get('/area/{areaId}/count', [InscripcionController::class, 'contarPorArea']);
    Route::get('/categoria/{categoriaId}/count', [InscripcionController::class, 'contarPorCategoria']);
    Route::get('/area/{areaId}', [InscripcionController::class, 'obtenerInscripcionesPorArea']);
    Route::get('/categoria/{categoriaId}', [InscripcionController::class, 'obtenerInscripcionesPorCategoria']);
    Route::patch('/{ci}/estado', [InscripcionController::class, 'actualizarEstadoInscripcion']);
    Route::post('/bulk', [InscripcionController::class, 'crearMasivo'])->name('inscripciones.bulk');
    Route::get('/olimpiada/{olimpiada_id}', [InscripcionController::class, 'obtenerInscripcionesDetalladasPorOlimpiada']);
    Route::get('/ci/{ci}', [InscripcionController::class, 'mostrarPorCi']);
    Route::get('/{ci}/olimpiadas', [InscripcionController::class, 'mostrarPorCiOlimpiadas']);
    Route::get('/postulante/{ci}/olimpiada/{olimpiadaId}', [InscripcionController::class, 'mostrarDetallesPostulantePorCi']);
});


// =========================
//          ORDEN DE PAGO
// =========================
// Grupo de rutas para ordenes de pago
Route::prefix('ordenes-pago')->group(function () {
    Route::post('/', [OrdenPagoController::class, 'crear']);             // Crea una nueva orden de pago
    Route::get('/', [OrdenPagoController::class, 'listar']);              // Listar todas las órdenes
    Route::get('/lista/{codigo_lista}', [OrdenPagoController::class, 'mostrarPorCodigoLista']); // Obtiene orden asociada a un código de lista
    Route::get('/numero/{n_orden}', [OrdenPagoController::class, 'mostrarPorNumeroOrden']);       // Obtiene una orden por su número de orden
    Route::get('/datos-previos/{codigo_lista}', [OrdenPagoController::class, 'datosPrevios']);
    Route::patch('/pagar', [OrdenPagoController::class, 'pagar']);
});


// =========================
//          COMPROBANTE
// =========================
Route::prefix('comprobantes')->group(function () {
    Route::get('/{id}', [ComprobanteController::class, 'mostrar']); // Mostrar un comprobante por ID
});

// =========================
//          FASES
// =========================
// Grupo de rutas para fases
Route::prefix('fases')->group(function () {
    Route::get('/', [FaseController::class, 'listar']);                                // Crear orden
});


// =========================
//          COMPROBANTE
// =========================
// Grupo de rutas para comprobantes
Route::prefix('comprobantes')->group(function () {
    Route::get('codigo/{codigo}', [ComprobanteController::class, 'getByCodigo']);
    Route::get('orden/{ordenId}', [ComprobanteController::class, 'getByOrdenId']);
    Route::get('nit/{nit}', [ComprobanteController::class, 'getByCINIT']);
});


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


// =========================
//          ROLES
// =========================
// Grupo de rutas para roles
Route::prefix('roles')->group(function () {
    Route::get('/', [RolController::class, 'listar']);
    Route::post('/', [RolController::class, 'guardar']);
    //-Route::delete('/{id}', [RolController::class, 'destroy']);
    Route::put('/usuario', [RolController::class, 'asignarRolesUsuario']);
    Route::put('/servicios', [RolController::class, 'asignarServiciosRol']);
});


// =========================
//          SERVICIOS
// =========================
// Grupo de rutas para roles
Route::prefix('servicios')->group(function () {
    Route::get('/', [ServicioController::class, 'listar']);

});



Route::post('/usuarios', [UsuarioController::class, 'guardar']);
Route::get('/usuarios', [UsuarioController::class, 'listar']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*Route::post('/protegida', function () {
    return response()->json(['message' => '¡Esta ruta está protegida por CSRF!']);
});
*/