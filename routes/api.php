<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\ProvinciaController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\OlimpiadaController;
//use App\Http\Controllers\AreaCategoriaController;
use App\Http\Controllers\ColegioController;
use App\Http\Controllers\PostulanteController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\ListaController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\CronogramaController;
//use App\Http\Controllers\AreaOlimpiadaController;
use App\Http\Controllers\NivelCompetenciaController;
use App\Http\Controllers\AuthController;


Route::get('/rutas', function () {
    return collect(Route::getRoutes())->map(function ($route) {
        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'action' => $route->getActionName(),
        ];
    })->filter(fn ($route) => str_starts_with($route['uri'], 'api/'))->values();
});


// Crear un área
Route::post('/areas', [AreaController::class, 'store']);

// Obtener todas las áreas
Route::get('/areas', [AreaController::class, 'index']);

// Obtener areas por nombre
Route::get('/areas/buscar', [AreaController::class, 'find']);

// Eliminar un área por ID
Route::delete('/areas/{id}', [AreaController::class, 'destroy']);


// Crear una categoria
Route::post('/categorias', [CategoriaController::class, 'store']);

// Obtener todas las categorias
Route::get('/categorias', [CategoriaController::class, 'index']);

// Actualizar una categoria por ID
Route::put('/categorias/{id}', [CategoriaController::class, 'update']);

// Eliminar un categoria por ID
Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);

// Obtener categorias por nombre
Route::get('/categorias/buscar', [CategoriaController::class, 'find']);


// Asignar una categoria a un area
Route::post('/categoria/area', [NivelCompetenciaController::class, 'attachCategoriaToArea']);

// Desligar una categoria de un area
Route::delete('/categoria/area', [NivelCompetenciaController::class, 'detachCategoriaFromArea']);
/*
// Asignar categorias a un area
Route::post('/categoria/areas', [NivelCompetenciaController::class, 'attachMultipleCategoriasToArea']);

// Asignar una categoria a areas
Route::post('/areas/categoria', [NivelCompetenciaController::class, 'attachCategoriaToMultipleAreas']);
*/

// Filtrar categorias de un area
Route::get('/areas/{id}/categorias', [NivelCompetenciaController::class, 'findCategoriasByArea']);

// Filtrar categorias con sus areas
Route::get('/categorias/areas', [NivelCompetenciaController::class, 'getAllCategoriasWithAreas']);

// Filtras las areas con sus categorias
Route::get('/areas/categorias', [NivelCompetenciaController::class, 'getAllAreasWithCategorias']);

// Filtrar las areas segun cursos asociados
Route::get('/curso/{curso}/areas', [NivelCompetenciaController::class, 'getAreasByCurso']);

// Filtrar las categorias segun el area y curso deseados
Route::get('/area/{area}/curso/{curso}/categorias', [NivelCompetenciaController::class, 'getCategoriasByAreaCurso']);

// Filtrar las categorias con areas segun curso
Route::get('/categorias/areas/curso/{curso}', [NivelCompetenciaController::class, 'getCategoriasByCurso']);


// Consultar si hay olimpiada en curso
Route::get('/olimpiadas/hoy', [OlimpiadaController::class, 'checkOlimpiadaEnCurso']);

// Crear una olimpiada
Route::post('/olimpiadas', [OlimpiadaController::class, 'store']);

// Obtener todas las olimpiadas
Route::get('/olimpiadas', [OlimpiadaController::class, 'index']);

// Actualizar las fechas de una olimpiadas
Route::put('/olimpiadas/{id}', [OlimpiadaController::class, 'update']);

// Eliminar una olimpiada por id
Route::delete('/olimpiadas/{id}', [OlimpiadaController::class, 'destroy']);



// Obtener areas ligadas a una olimpiada
Route::get('/olimpiadas/{id}/area', [NivelCompetenciaController::class, 'getAreasByOlimpiada']);

// Obtener olimpiadas con sus cronogramas
Route::get('/olimpiadas/{id}/cronogramas', [OlimpiadaController::class, 'getOlimpiadaWithCronogramas']);

// Obtener todos los cronogramas
Route::get('/cronogramas', [CronogramaController::class, 'index']);

//Crear un plazo en el cronograma
Route::post('/cronogramas', [CronogramaController::class, 'store']);

//Actualizar un cronograma
Route::put('/cronogramas/{id}', [CronogramaController::class, 'update']);

// Borrar un plazo del cronograma 
Route::delete('/cronogramas/{id}', [CronogramaController::class, 'destroy']);




// Obtener todos los departamentos
Route::get('/departamentos', [DepartamentoController::class, 'index']); 

// Obtener un departamento por ID
Route::get('/departamentos/{id}', [DepartamentoController::class, 'show']); 

Route::get('departamentos/{id}/provincias', [DepartamentoController::class, 'getProvinciasByDepartamento']);

Route::get('departamentos-con-provincias', [DepartamentoController::class, 'getAllDepartamentosWithProvincias']);


// Obtener todas las provincias
Route::get('/provincias', [ProvinciaController::class, 'index']); 

// Obtener una provincia por ID
Route::get('/provincias/{id}', [ProvinciaController::class, 'show']);


// =========================
//          RESPONSABLE
// =========================
Route::prefix('responsables')->group(function () {
    Route::post('/', [ResponsableController::class, 'store']); 
    Route::get('/', [ResponsableController::class, 'index']);
    Route::get('/{id}', [ResponsableController::class, 'show']);
});


// =========================
//          POSTULANTE
// =========================
Route::prefix('postulantes')->group(function () {
    Route::post('/', [PostulanteController::class, 'store']);
    Route::get('/', [PostulanteController::class, 'index']);
    Route::get('/{id}', [PostulanteController::class, 'show']);
});


// =========================
//          LISTA
// =========================
// Grupo de rutas para listas
Route::prefix('listas')->group(function () {
    Route::post('/', [ListaController::class, 'store']);
    Route::get('/', [ListaController::class, 'index']);
    Route::get('/{id}', [ListaController::class, 'show']);
    Route::get('/responsables/{ci}/listas', [ListaController::class, 'getByResponsableCi']);
    Route::get('/estado/{estado}', [ListaController::class, 'getListasByEstado']);
    Route::get('/responsables/{ci}/listas/{estado}', [ListaController::class, 'getListasByEstadoYResponsable']);
    Route::get('/codigo/{codigo}', [ListaController::class, 'showByCodigo']);
    Route::put('/estado/{codigo}', [ListaController::class, 'updateEstado']);
});


// =========================
//          INSCRIPCION
// =========================
// Grupo de rutas para inscripciones
Route::prefix('inscripciones')->group(function () {
    Route::post('/', [InscripcionController::class, 'store']);
    Route::get('/', [InscripcionController::class, 'index']);
    Route::get('/{id}', [InscripcionController::class, 'show']);
    Route::get('/estado/{estado}', [InscripcionController::class, 'getByEstado']);
    Route::get('/area/{areaId}/count', [InscripcionController::class, 'countByArea']);
    Route::get('/categoria/{categoriaId}/count', [InscripcionController::class, 'countByCategoria']);
    Route::get('/area/{areaId}', [InscripcionController::class, 'getInscripcionesByArea']);
    Route::get('/categoria/{categoriaId}', [InscripcionController::class, 'getInscripcionesByCategoria']);
    Route::put('/{id}/estado', [InscripcionController::class, 'updateEstadoInscripcion']);
    Route::get('/postulante/{ci}', [InscripcionController::class, 'getInscripcionByCI']);
});




// Obtener provincias por departamento
Route::get('/departamento/{departamentoId}/provincias', [DepartamentoController::class, 'getProvinciasByDepartamento']); 

// Obtener todos los departamentos con sus provincias
Route::get('/departamentos/provincias', [DepartamentoController::class, 'getAllDepartamentosWithProvincias']); 


// Obtener todos los colegios
Route::get('/colegios', [ColegioController::class, 'index']); 

// Registrar un nuevo colegio
Route::post('/colegios', [ColegioController::class, 'store']); 

// Eliminar un colegio por ID
Route::delete('/colegios/{id}', [ColegioController::class, 'destroy']); 


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);







Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/protegida', function () {
    return response()->json(['message' => '¡Esta ruta está protegida por CSRF!']);
});

Route::get('/hola', function () {
    return 'HOLA';
});


Route::post('/test-post', function () {
    return response()->json(['message' => 'POST request received']);
});