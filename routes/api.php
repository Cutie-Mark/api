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
    //-Route::get('/', [CronogramaController::class, 'index']);                        // Obtener todos los cronogramas
    //-Route::post('/', [CronogramaController::class, 'store']);                       // Crear un plazo en el cronograma
    //-Route::post('/fases', [CronogramaController::class, 'createOlimpiadaFases']);   // Crear fases de una olimpiada
    //-Route::put('/{id}', [CronogramaController::class, 'update']);                   // Actualizar un cronograma
    //-Route::delete('/{id}', [CronogramaController::class, 'destroy']);               // Borrar un plazo del cronograma

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
    Route::get('/{olimpiada_id}/inscripciones-detalladas', [InscripcionController::class, 'getInscripcionesDetalladasPorOlimpiada']); // Obtener inscripciones detalladas por olimpiada
    Route::get('/{olimpiada_id}/reporteDeInscripciones', [InscripcionController::class, 'getReporteDeInscripciones']); // Obtener inscripciones detalladas por olimpiada
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
// Asignar una categoria a un area en una olimpiada
//-Route::post('/categoria/area/olimpiada', [NivelCompetenciaController::class, 'attach']);

//-
// Desligar una categoria de un area
// Route::delete('/categoria/area/olimpiada', [NivelCompetenciaController::class, 'detach']);

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
/*
// Asignar categorias a un area
Route::post('/categoria/areas', [NivelCompetenciaController::class, 'attachMultipleCategoriasToArea']);

// Asignar una categoria a areas
Route::post('/areas/categoria', [NivelCompetenciaController::class, 'attachCategoriaToMultipleAreas']);
*/

// Filtrar areas por categoria
//-Route::get('categorias/{categoriaId}/areas/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getAreasByCategoria']);

// Filtrar categorias de un area
//-Route::get('/areas/{id}/categorias/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getCategoriasByArea']);

// Filtrar categorias con sus areas
Route::get('/categorias/areas/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'listarCategoriasConAreas']);

// Filtras las areas con sus categorias
Route::get('/areas/categorias/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'listarAreasConCategorias']);

// Filtrar las areas segun cursos asociados
//-Route::get('/curso/{curso}/areas/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getAreasByCurso']);

// Filtrar las categorias segun el area y curso deseados
//-Route::get('/area/{area}/curso/{curso}/categorias/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getCategoriasByAreaCurso']);

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
    //-Route::post('/',    [ColegioController::class, 'store']);      // Crear colegio
    Route::get('/',     [ColegioController::class, 'listar']);      // Listar todos
    //-Route::get('/{id}', [ColegioController::class, 'show']);       // Mostrar uno
    //-Route::put('/{id}',    [ColegioController::class, 'update']);  // Actualizar
});



// =========================
//          DEPARTAMENTO
// =========================
Route::prefix('departamentos')->group(function () {
    //-Route::post('/', [DepartamentoController::class, 'store']);                                     // Crear departamento
    Route::get('/', [DepartamentoController::class, 'listar']);                                      // Listar departamentos
    Route::get('/conProvincias', [DepartamentoController::class, 'listarConProvincias']);           // Listar con departamentos con provincias
    //-Route::get('/{id}', [DepartamentoController::class, 'show']);                                   // Mostrar por ID
    //-Route::get('/abreviatura/{abreviatura}', [DepartamentoController::class, 'showByAbreviatura']); // Mostrar por abreviatura
    //-Route::put('/{id}', [DepartamentoController::class, 'update']);                                 // Actualizar nombre de Departamento
});



// =========================
//          PROVINCIA
// =========================
Route::prefix('provincias')->group(function () {
    //-Route::post('/', [ProvinciaController::class, 'store']);        // Crear Provincia
    Route::get('/', [ProvinciaController::class, 'listar']);         // Listar Provincias
    //-Route::get('/{id}', [ProvinciaController::class, 'show']);      // Mostrar una Provincia
    //-Route::put('/{id}', [ProvinciaController::class, 'update']);    // Modificar nombre de Provincia
});


// =========================
//          POSTULANTE
// =========================
Route::prefix('postulantes')->group(function () {
    Route::post('/', [PostulanteController::class, 'store']);       // Crear Postulante
    Route::get('/', [PostulanteController::class, 'index']);        // Listar Postulantes
    Route::get('/{id}', [PostulanteController::class, 'show']);     // Obtener Postulante
    Route::put('/{id}', [PostulanteController::class, 'update']);   // Editar Postulante
});



// =========================
//          RESPONSABLE
// =========================
Route::prefix('responsables')->group(function () {
    Route::post('/', [ResponsableController::class, 'store']);       // Crear responsable
    Route::get('/', [ResponsableController::class, 'index']);        // Listar responsables
    Route::get('/{id}', [ResponsableController::class, 'show']);     // Obtener un responsable
    Route::put('/{id}', [ResponsableController::class, 'update']);   // Actualizar un responsable
    Route::get('/ci/{ci}', [ResponsableController::class, 'showByCi']);
});



// =========================
//          LISTA
// =========================
Route::prefix('listas')->group(function () {
    Route::post('/', [ListaController::class, 'store']);                                // Crear lista
    Route::get('/', [ListaController::class, 'index']);                                 // Listar todas las listas
    Route::get('/{id}', [ListaController::class, 'show']);                              // Mostrar lista por ID
    Route::get('/codigo/{codigo}', [ListaController::class, 'showByCodigo']);           // Mostrar lista por código
    Route::put('/{codigo}/estado', [ListaController::class, 'updateEstado']);           // Actualizar estado de una lista
    Route::get('/responsable/{ci}', [ListaController::class, 'getByResponsableCi']);    // Listas de un responsable (por CI)
    Route::get('/estado/{estado}', [ListaController::class, 'getListasByEstado']);      // Listas por estado
    Route::get('/responsable/{ci}/estado/{estado}',[ListaController::class, 'getListasByEstadoYResponsable']);// Listas de un responsable y estado
    Route::get('/olimpiada/{olimpiadaId}', [ListaController::class, 'getByOlimpiada']); // Mostrar listas de una olimpiada
    Route::delete('/{codigo}/eliminar', [ListaController::class, 'destroyEmpty']);
});


// =========================
//          INSCRIPCION
// =========================
Route::prefix('inscripciones')->group(function () {
    Route::post('/', [InscripcionController::class, 'store']);
    Route::get('/{id}', [InscripcionController::class, 'show']);
    Route::get('/olimpiada/{olimpiadaId}/estado/{estado}', [InscripcionController::class, 'getByEstado']);
    Route::get('/area/{areaId}/count', [InscripcionController::class, 'countByArea']);
    Route::get('/categoria/{categoriaId}/count', [InscripcionController::class, 'countByCategoria']);
    Route::get('/area/{areaId}', [InscripcionController::class, 'getInscripcionesByArea']);
    Route::get('/categoria/{categoriaId}', [InscripcionController::class, 'getInscripcionesByCategoria']);
    Route::patch('/{ci}/estado', [InscripcionController::class, 'updateEstadoInscripcion']);
    Route::post('/bulk', [InscripcionController::class, 'storeBulk'])->name('inscripciones.bulk');
    Route::get('/olimpiada/{olimpiada_id}', [InscripcionController::class, 'getInscripcionesDetalladasPorOlimpiada']);
    Route::get('/ci/{ci}', [InscripcionController::class, 'showByCi']);
    Route::get('/{ci}/olimpiadas', [InscripcionController::class, 'showByCiOlimimpiadas']);
    Route::get('/postulante/{ci}/olimpiada/{olimpiadaId}', [InscripcionController::class, 'showPostulanteDetailsByCi']);
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
    //-Route::get('/{id}', [FaseController::class, 'show']);                                // Crear orden
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