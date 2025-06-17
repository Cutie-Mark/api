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
    Route::post('/', [AreaController::class, 'store']);                     // Crear un área
    Route::get('/', [AreaController::class, 'index']);                      // Obtener todas las áreas
    Route::get('/buscar', [AreaController::class, 'find']);                 // Obtener áreas por nombre
    Route::put('/{id}/deactivate', [AreaController::class, 'deactivate']);  // Desactivar un área por ID
    Route::put('/{id}/activate', [AreaController::class, 'activate']);      // Activar un área por ID
    Route::delete('/{id}', [AreaController::class, 'destroy']);             // Eliminar un área por ID
});



// =========================
//          CATEGORIA
// =========================
Route::prefix('/categorias')->group(function () {
    Route::post('/', [CategoriaController::class, 'store']);                    // Crear una categoría
    Route::get('/', [CategoriaController::class, 'index']);                     // Obtener todas las categorías
    Route::get('/buscar', [CategoriaController::class, 'find']);                // Obtener categorías por nombre
    Route::put('/{id}', [CategoriaController::class, 'update']);                // Actualizar una categoría por ID
    Route::put('/{id}/deactivate', [CategoriaController::class, 'deactivate']); // Desactivar una categoría por ID
    Route::put('/{id}/activate', [CategoriaController::class, 'activate']);     // Activar una categoría por ID
    Route::delete('/{id}', [CategoriaController::class, 'destroy']);            // Eliminar una categoría por ID
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

    Route::put('/fases/olimpiada', [CronogramaController::class, 'syncFasesOfOlimpiada']);
    Route::put('/fases/fechas', [CronogramaController::class, 'completeCronogramas']);
});


// =========================
//         OLIMPIADA
// =========================
Route::prefix('/olimpiadas')->group(function () {
    Route::get('/', [OlimpiadaController::class, 'index']);                                         // Obtener todas las olimpiadas
    Route::post('/por-tipos', [OlimpiadaController::class, 'getOlimpiadasPorTipos']); // Obtener olimpiadas por tipos o fases
    Route::get('/hoy', [OlimpiadaController::class, 'checkOlimpiadaEnCurso']);                      // Consultar si hay olimpiada en curso
    //-Route::get('/hoy/{id}', [OlimpiadaController::class, 'getOlimpiadaWithFaseEnCurso']);
    Route::get('/{olimpiada_id}/inscripciones-detalladas', [InscripcionController::class, 'getInscripcionesDetalladasPorOlimpiada']); // Obtener inscripciones detalladas por olimpiada
    Route::get('/{olimpiada_id}/reporteDeInscripciones', [InscripcionController::class, 'getReporteDeInscripciones']); // Obtener inscripciones detalladas por olimpiada
    Route::post('/', [OlimpiadaController::class, 'store']);                                        // Crear una olimpiada
    Route::delete('/clearAllPlantillasL', [OlimpiadaController::class, 'clearAllPlantillas']);       // Eliminar url_plantilla de todas las olimpiadas
    Route::get('/{id}', [OlimpiadaController::class, 'show']);                                      // Obtener una olimpiada por ID
    Route::put('/{id}', [OlimpiadaController::class, 'update']);                                    // Actualizar las fechas de una olimpiada
    Route::delete('/{id}', [OlimpiadaController::class, 'destroy']);                                // Eliminar una olimpiada por ID
    Route::delete('/{id}/plantilla', [OlimpiadaController::class, 'showUrlPlantilla']);             // Obtener la plantilla de una olimpiada por ID
    Route::get('/{id}/cronogramas', [OlimpiadaController::class, 'getOlimpiadaWithCronogramas']);   // Obtener olimpiadas con sus cronogramas
    Route::post('/upload-excel', [OlimpiadaController::class, 'uploadExcelFormato']);               // Subir archivo Excel
    Route::get('/{id}/download-excel', [OlimpiadaController::class, 'downloadExcelFormato']);       // Descargar archivo Excel
    Route::get('/{id}/download-excel', [OlimpiadaController::class, 'downloadExcelFormato']);       // Descargar archivo Excel
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
Route::post('/olimpiada/area', [NivelCompetenciaController::class, 'attachAreaOlimpiada']);

// Desligar una categoria de un area
Route::delete('/olimpiada/area', [NivelCompetenciaController::class, 'detachByOlimpiadaAndArea']);

// Desactivas un nivel de competencia
Route::put('/categoria/area/olimpiada/deactivate', [NivelCompetenciaController::class, 'deactivate']);

// Activas un nivel de competencia
Route::put('/categoria/area/olimpiada/activate', [NivelCompetenciaController::class, 'activate']);

// Asignar una categoria a un area
Route::put('/categorias/area/olimpiada', [NivelCompetenciaController::class, 'syncCategorias']);
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
Route::get('/categorias/areas/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getAllCategoriasWithAreas']);

// Filtras las areas con sus categorias
Route::get('/areas/categorias/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getAllAreasWithCategorias']);

// Filtrar las areas segun cursos asociados
//-Route::get('/curso/{curso}/areas/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getAreasByCurso']);

// Filtrar las categorias segun el area y curso deseados
//-Route::get('/area/{area}/curso/{curso}/categorias/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getCategoriasByAreaCurso']);

// Filtrar las categorias con areas segun curso
Route::get('/categorias/areas/curso/{curso}/olimpiada/{olimpiadaId}', [NivelCompetenciaController::class, 'getCategoriasByCurso']);

// Filtrar categorias de una olimpiada ordenadas y agrupadas por grado
Route::get('/categorias/olimpiada/{id}', [NivelCompetenciaController::class, 'getSortCategoriasByOlimpiada']);

// Obtener areas ligadas a una olimpiada
Route::get('/olimpiadas/{id}/area', [NivelCompetenciaController::class, 'getAreasByOlimpiada']);



// =========================
//          COLEGIO
// =========================
Route::prefix('colegios')->group(function () {
    //-Route::post('/',    [ColegioController::class, 'store']);      // Crear colegio
    Route::get('/',     [ColegioController::class, 'index']);      // Listar todos
    //-Route::get('/{id}', [ColegioController::class, 'show']);       // Mostrar uno
    //-Route::put('/{id}',    [ColegioController::class, 'update']);  // Actualizar
});



// =========================
//          DEPARTAMENTO
// =========================
Route::prefix('departamentos')->group(function () {
    //-Route::post('/', [DepartamentoController::class, 'store']);                                     // Crear departamento
    Route::get('/', [DepartamentoController::class, 'index']);                                      // Listar departamentos
    Route::get('/with-provinces', [DepartamentoController::class, 'indexWithProvinces']);           // Listar con departamentos con provincias
    //-Route::get('/{id}', [DepartamentoController::class, 'show']);                                   // Mostrar por ID
    //-Route::get('/abreviatura/{abreviatura}', [DepartamentoController::class, 'showByAbreviatura']); // Mostrar por abreviatura
    //-Route::put('/{id}', [DepartamentoController::class, 'update']);                                 // Actualizar nombre de Departamento
});



// =========================
//          PROVINCIA
// =========================
Route::prefix('provincias')->group(function () {
    //-Route::post('/', [ProvinciaController::class, 'store']);        // Crear Provincia
    Route::get('/', [ProvinciaController::class, 'index']);         // Listar Provincias
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
    Route::post('/', [OrdenPagoController::class, 'store']);             // Crea una nueva orden de pago
    Route::get('/', [OrdenPagoController::class, 'index']);              // Listar todas las órdenes
    Route::get('/lista/{codigo_lista}', [OrdenPagoController::class, 'showByCodLista']); // Obtiene orden asociada a un código de lista
    Route::get('/numero/{n_orden}', [OrdenPagoController::class, 'showByNOrden']);       // Obtiene una orden por su número de orden
    Route::get('/datos-previos/{codigo_lista}', [OrdenPagoController::class, 'datosPrevios']);
    Route::patch('/pagar', [OrdenPagoController::class, 'pagar']);
});


// =========================
//          COMPROBANTE
// =========================
Route::prefix('comprobantes')->group(function () {
    Route::get('/{id}', [ComprobanteController::class, 'show']); // Mostrar un comprobante por ID
});

// =========================
//          FASES
// =========================
// Grupo de rutas para fases
Route::prefix('fases')->group(function () {
    Route::get('/', [FaseController::class, 'index']);                                // Crear orden
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
    Route::get('/', [RolController::class, 'index']);
    Route::post('/', [RolController::class, 'store']);
    //-Route::delete('/{id}', [RolController::class, 'destroy']);
    Route::put('/usuario', [RolController::class, 'setRolUsuario']);
    Route::put('/servicios', [RolController::class, 'setServiciosRol']);
});


// =========================
//          SERVICIOS
// =========================
// Grupo de rutas para roles
Route::prefix('servicios')->group(function () {
    Route::get('/', [ServicioController::class, 'index']);

});



Route::post('/usuarios', [UsuarioController::class, 'store']);
Route::get('/usuarios', [UsuarioController::class, 'index']);


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/protegida', function () {
    return response()->json(['message' => '¡Esta ruta está protegida por CSRF!']);
});
